<?php
/**
 * Unified data layer (PRD §13.10 / R5).
 *
 * Every list page used to assemble its own WHERE clause, prepare() calls, count
 * query and fetch — around 40 lines each, at uneven quality. That is the source
 * of the prepare() inconsistency noted in §10.1 and of stat cards disagreeing
 * with the table beneath them, because the PIC scope was applied to one query
 * and not the other.
 *
 * Declaring the searchable and filterable fields once per entity means prepare()
 * is always used and scoping is always applied.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Field definitions per entity.
 */
function gti_entity_schema( $entity = null ) {
    global $wpdb;

    $schema = array(
        'equipment' => array(
            'table'   => $wpdb->prefix . 'gti_equipment',
            'search'  => array( 'name', 'equipment_code', 'brand', 'model' ),
            'filters' => array( 'type', 'category', 'brand', 'status', 'condition_status' ),
            'soft'    => 'deleted_at',
            'order'   => 'created_at',
            'scope'   => null,
        ),
        'spare_parts' => array(
            'table'   => $wpdb->prefix . 'gti_spare_parts',
            'search'  => array( 'name', 'part_code', 'brand', 'part_number' ),
            'filters' => array( 'category', 'brand', 'status', 'supplier' ),
            'soft'    => 'deleted_at',
            'order'   => 'created_at',
            'scope'   => null,
        ),
        'requests' => array(
            'table'   => $wpdb->prefix . 'gti_requests',
            'search'  => array( 'request_id', 'customer_name', 'customer_company', 'customer_email', 'equipment' ),
            'filters' => array( 'status', 'brand', 'category', 'sales_pic', 'assigned_to' ),
            'soft'    => null,
            'order'   => 'created_at',
            'scope'   => 'request',
        ),
        'quotations' => array(
            'table'   => $wpdb->prefix . 'gti_quotations',
            'search'  => array( 'quotation_id', 'customer_name', 'customer_company', 'customer_email' ),
            'filters' => array( 'status', 'sales_pic', 'assigned_to', 'equipment_type', 'payment_terms' ),
            'soft'    => null,
            'order'   => 'created_at',
            'scope'   => 'quotation',
        ),
        'sell_requests' => array(
            'table'   => $wpdb->prefix . 'gti_sell_requests',
            'search'  => array( 'customer_name', 'customer_company', 'customer_email', 'equipment_name', 'equipment_brand' ),
            'filters' => array( 'status', 'equipment_brand', 'sales_pic', 'assigned_to' ),
            'soft'    => null,
            'order'   => 'created_at',
            'scope'   => 'sell',
        ),
        'customers' => array(
            'table'   => $wpdb->prefix . 'gti_customers',
            'search'  => array( 'name', 'company', 'email', 'phone', 'customer_id' ),
            'filters' => array( 'status', 'industry', 'city', 'province', 'source' ),
            'soft'    => null,
            'order'   => 'registered_date',
            'scope'   => null,
        ),
    );

    if ( $entity === null ) {
        return $schema;
    }

    return isset( $schema[ $entity ] ) ? $schema[ $entity ] : null;
}

/**
 * Build the WHERE clause and its bound parameters.
 *
 * @return array{sql: string, params: array}
 */
function gti_build_where( $entity, array $args ) {
    $schema = gti_entity_schema( $entity );
    if ( ! $schema ) {
        return array( 'sql' => ' WHERE 1=0', 'params' => array() );
    }

    $where  = array( '1=1' );
    $params = array();

    if ( $schema['soft'] ) {
        $where[] = "`{$schema['soft']}` IS NULL";
    }

    $search = isset( $args['search'] ) ? trim( (string) $args['search'] ) : '';
    if ( $search !== '' && $schema['search'] ) {
        $like  = '%' . $GLOBALS['wpdb']->esc_like( $search ) . '%';
        $parts = array();
        foreach ( $schema['search'] as $field ) {
            $parts[]  = "`{$field}` LIKE %s";
            $params[] = $like;
        }
        $where[] = '(' . implode( ' OR ', $parts ) . ')';
    }

    // Filters the caller pins (e.g. type = 'used'), not settable from the request.
    foreach ( (array) ( $args['fixed'] ?? array() ) as $field => $value ) {
        $where[]  = "`{$field}` = %s";
        $params[] = (string) $value;
    }

    foreach ( (array) ( $args['filters'] ?? array() ) as $field => $value ) {
        if ( $value === '' || $value === null ) {
            continue;
        }
        // Only fields declared above may be filtered on; anything else is ignored.
        if ( ! in_array( $field, $schema['filters'], true ) ) {
            continue;
        }
        if ( is_array( $value ) ) {
            $placeholders = implode( ',', array_fill( 0, count( $value ), '%s' ) );
            $where[]      = "`{$field}` IN ({$placeholders})";
            $params       = array_merge( $params, array_map( 'strval', $value ) );
        } else {
            $where[]  = "`{$field}` = %s";
            $params[] = (string) $value;
        }
    }

    // Date range on the ordering column.
    if ( ! empty( $args['date_from'] ) ) {
        $where[]  = "`{$schema['order']}` >= %s";
        $params[] = $args['date_from'] . ' 00:00:00';
    }
    if ( ! empty( $args['date_to'] ) ) {
        $where[]  = "`{$schema['order']}` <= %s";
        $params[] = $args['date_to'] . ' 23:59:59';
    }

    $sql = ' WHERE ' . implode( ' AND ', $where );

    // Row-level PIC scope, applied to list, count and stat queries alike.
    if ( $schema['scope'] && empty( $args['ignore_scope'] ) ) {
        $sql .= gti_scope_where_sql( $schema['scope'] );
    }

    return array( 'sql' => $sql, 'params' => $params );
}

/**
 * Paged list query.
 *
 * @return array{items: array, total: int, pages: int, page: int, per_page: int, offset: int}
 */
function gti_query_list( $entity, array $args = array() ) {
    global $wpdb;

    $args = array_merge( array(
        'search' => '', 'filters' => array(), 'page' => 1, 'per_page' => 10,
        'orderby' => '', 'order' => 'DESC', 'date_from' => '', 'date_to' => '',
        // Templates written against $wpdb->get_results() read rows as objects;
        // pass OBJECT so they can adopt this without rewriting their markup.
        'output' => ARRAY_A,
    ), $args );

    $schema = gti_entity_schema( $entity );
    if ( ! $schema ) {
        return array( 'items' => array(), 'total' => 0, 'pages' => 0, 'page' => 1, 'per_page' => 10, 'offset' => 0 );
    }

    $where    = gti_build_where( $entity, $args );
    $table    = $schema['table'];
    $page     = max( 1, (int) $args['page'] );
    $per_page = max( 1, (int) $args['per_page'] );
    $offset   = ( $page - 1 ) * $per_page;

    // Order column comes from the declared list, never straight from the request.
    $allowed = array_merge( $schema['search'], $schema['filters'], array( 'id', $schema['order'], 'updated_at' ) );
    $orderby = in_array( $args['orderby'], $allowed, true ) ? $args['orderby'] : $schema['order'];
    $order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

    $count_sql = "SELECT COUNT(*) FROM {$table}" . $where['sql'];
    $total     = (int) ( $where['params']
        ? $wpdb->get_var( $wpdb->prepare( $count_sql, $where['params'] ) )
        : $wpdb->get_var( $count_sql ) );

    $list_sql = "SELECT * FROM {$table}" . $where['sql']
              . " ORDER BY `{$orderby}` {$order}, id {$order} LIMIT %d OFFSET %d";

    $items = $wpdb->get_results(
        $wpdb->prepare( $list_sql, array_merge( $where['params'], array( $per_page, $offset ) ) ),
        $args['output']
    );

    return array(
        'items'    => $items ?: array(),
        'total'    => $total,
        'pages'    => (int) ceil( $total / $per_page ),
        'page'     => $page,
        'per_page' => $per_page,
        'offset'   => $offset,
    );
}

/**
 * Counts grouped by status — one query, not four to six separate COUNT(*)s
 * (PRD §10.2).
 *
 * @return array status => count, plus 'all'
 */
function gti_count_by_status( $entity, array $args = array() ) {
    global $wpdb;

    $schema = gti_entity_schema( $entity );
    if ( ! $schema ) {
        return array( 'all' => 0 );
    }

    // Status itself must not narrow the per-status counts, but a pinned filter
    // like type = 'used' must still apply or the cards count the wrong table half.
    unset( $args['filters']['status'] );

    $where = gti_build_where( $entity, $args );
    $sql   = "SELECT status, COUNT(*) AS total FROM {$schema['table']}" . $where['sql'] . ' GROUP BY status';

    $rows = $where['params']
        ? $wpdb->get_results( $wpdb->prepare( $sql, $where['params'] ), ARRAY_A )
        : $wpdb->get_results( $sql, ARRAY_A );

    $counts = array( 'all' => 0 );
    foreach ( (array) $rows as $row ) {
        $counts[ $row['status'] ] = (int) $row['total'];
        $counts['all']           += (int) $row['total'];
    }

    return $counts;
}

/**
 * One row by primary key, with the PIC scope applied.
 */
function gti_get_row( $entity, $id ) {
    global $wpdb;

    $schema = gti_entity_schema( $entity );
    if ( ! $schema || ! $id ) {
        return null;
    }

    $sql = "SELECT * FROM {$schema['table']} WHERE id = %d";
    if ( $schema['scope'] ) {
        $sql .= gti_scope_where_sql( $schema['scope'] );
    }

    $row = $wpdb->get_row( $wpdb->prepare( $sql, (int) $id ), ARRAY_A );

    return $row ?: null;
}

/**
 * Insert or update a row.
 *
 * @return int|false Row ID, or false on failure.
 */
function gti_save_row( $entity, array $data, $id = 0 ) {
    global $wpdb;

    $schema = gti_entity_schema( $entity );
    if ( ! $schema ) {
        return false;
    }

    // Drop keys that are not real columns, so a stray form field cannot
    // turn the whole write into a silent failure.
    $columns = $wpdb->get_col( "SHOW COLUMNS FROM {$schema['table']}" );
    $data    = array_intersect_key( $data, array_flip( (array) $columns ) );
    unset( $data['id'] );

    if ( ! $data ) {
        return false;
    }

    if ( $id ) {
        if ( in_array( 'updated_at', (array) $columns, true ) ) {
            $data['updated_at'] = current_time( 'mysql' );
        }
        $result = $wpdb->update( $schema['table'], $data, array( 'id' => (int) $id ), null, array( '%d' ) );
        return $result === false ? false : (int) $id;
    }

    if ( in_array( 'created_at', (array) $columns, true ) && empty( $data['created_at'] ) ) {
        $data['created_at'] = current_time( 'mysql' );
    }

    $result = $wpdb->insert( $schema['table'], $data );

    return $result === false ? false : (int) $wpdb->insert_id;
}

/**
 * Delete a row — soft where the entity supports it.
 */
function gti_delete_row( $entity, $id, $soft = true ) {
    global $wpdb;

    $schema = gti_entity_schema( $entity );
    if ( ! $schema || ! $id ) {
        return false;
    }

    if ( $soft && $schema['soft'] ) {
        return false !== $wpdb->update(
            $schema['table'],
            array( $schema['soft'] => current_time( 'mysql' ) ),
            array( 'id' => (int) $id ),
            array( '%s' ), array( '%d' )
        );
    }

    return false !== $wpdb->delete( $schema['table'], array( 'id' => (int) $id ), array( '%d' ) );
}

/**
 * Distinct values of a column, for populating filter dropdowns.
 */
function gti_distinct_values( $entity, $column, array $fixed = array() ) {
    global $wpdb;

    $schema = gti_entity_schema( $entity );
    if ( ! $schema || ! in_array( $column, $schema['filters'], true ) ) {
        return array();
    }

    $sql    = "SELECT DISTINCT `{$column}` FROM {$schema['table']} WHERE `{$column}` <> '' AND `{$column}` IS NOT NULL";
    $params = array();

    if ( $schema['soft'] ) {
        $sql .= " AND `{$schema['soft']}` IS NULL";
    }

    // Without this, the Used Equipment page offers categories that only exist
    // among rental units, and vice versa.
    foreach ( $fixed as $field => $value ) {
        if ( in_array( $field, $schema['filters'], true ) ) {
            $sql     .= " AND `{$field}` = %s";
            $params[] = (string) $value;
        }
    }

    $sql .= " ORDER BY `{$column}` ASC";

    $values = $params
        ? $wpdb->get_col( $wpdb->prepare( $sql, $params ) )
        : $wpdb->get_col( $sql );

    return array_filter( (array) $values );
}
