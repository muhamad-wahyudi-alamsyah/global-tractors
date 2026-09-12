<?php
/**
 * Status machine — one source of truth for status keys, labels, badge classes
 * and the transitions each entity allows (PRD §5.1).
 *
 * Before this existed, every template mapped status → label → CSS class with
 * its own if/elseif chain, and two adjacent pages used different class naming
 * conventions for the same thing. Anything that renders or changes a status
 * must go through here.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Status map for an entity.
 *
 * @param string $entity_type request|quotation|sell
 * @return array status key => ['label', 'class', 'next' => [], 'requires' => null|'attachment']
 */
function gti_status_map( $entity_type ) {
    $maps = array(
        'request' => array(
            'new'           => array( 'label' => 'New',           'class' => 'new',           'next' => array( 'processing', 'closed' ) ),
            'processing'    => array( 'label' => 'Processing',    'class' => 'processing',    'next' => array( 'proposal_sent', 'on_hold', 'closed' ) ),
            'proposal_sent' => array( 'label' => 'Proposal Sent', 'class' => 'proposal-sent', 'next' => array( 'closed', 'processing' ), 'requires' => 'attachment' ),
            'on_hold'       => array( 'label' => 'On Hold',       'class' => 'on-hold',       'next' => array( 'processing', 'closed' ) ),
            'closed'        => array( 'label' => 'Closed',        'class' => 'closed',        'next' => array( 'processing' ) ),
        ),
        'quotation' => array(
            'new'              => array( 'label' => 'New',        'class' => 'new',        'next' => array( 'processing', 'rejected' ) ),
            'processing'       => array( 'label' => 'Processing', 'class' => 'processing', 'next' => array( 'waiting_customer', 'rejected' ) ),
            // Key stays 'waiting_customer' so existing rows remain valid; only the label changed.
            'waiting_customer' => array( 'label' => 'Waiting Quotation Approval', 'class' => 'waiting', 'next' => array( 'approved', 'rejected', 'processing' ), 'requires' => 'attachment' ),
            'approved'         => array( 'label' => 'Approved',   'class' => 'approved',   'next' => array( 'completed', 'rejected' ) ),
            'rejected'         => array( 'label' => 'Rejected',   'class' => 'rejected',   'next' => array( 'processing' ) ),
            'completed'        => array( 'label' => 'Completed',  'class' => 'completed',  'next' => array() ),
        ),
        'sell' => array(
            'new'               => array( 'label' => 'New',               'class' => 'new',        'next' => array( 'processing', 'rejected' ) ),
            'processing'        => array( 'label' => 'Processing',        'class' => 'processing', 'next' => array( 'approved', 'rejected' ) ),
            'approved'          => array( 'label' => 'Approved',          'class' => 'approved',   'next' => array( 'invoice_requested', 'completed', 'rejected' ) ),
            'invoice_requested' => array( 'label' => 'Invoice Requested', 'class' => 'waiting',    'next' => array( 'completed', 'rejected' ) ),
            'rejected'          => array( 'label' => 'Rejected',          'class' => 'rejected',   'next' => array( 'processing' ) ),
            'completed'         => array( 'label' => 'Completed',         'class' => 'completed',  'next' => array() ),
        ),
    );

    $map = isset( $maps[ $entity_type ] ) ? $maps[ $entity_type ] : array();

    foreach ( $map as $key => $spec ) {
        $map[ $key ] = array_merge( array( 'next' => array(), 'requires' => null ), $spec );
    }

    return $map;
}

/**
 * Human label for a status. Falls back to a title-cased key so an unknown
 * legacy value still renders as something readable.
 */
function gti_status_label( $entity_type, $status ) {
    $map = gti_status_map( $entity_type );
    if ( isset( $map[ $status ]['label'] ) ) {
        return $map[ $status ]['label'];
    }
    return ucwords( str_replace( '_', ' ', (string) $status ) );
}

/**
 * Badge CSS modifier for a status.
 */
function gti_status_class( $entity_type, $status ) {
    $map = gti_status_map( $entity_type );
    if ( isset( $map[ $status ]['class'] ) ) {
        return $map[ $status ]['class'];
    }
    return sanitize_html_class( str_replace( '_', '-', (string) $status ) );
}

/**
 * Statuses reachable from the current one.
 *
 * @return array status key => label
 */
function gti_status_next( $entity_type, $from ) {
    $map  = gti_status_map( $entity_type );
    $next = isset( $map[ $from ]['next'] ) ? $map[ $from ]['next'] : array();

    $out = array();
    foreach ( $next as $status ) {
        $out[ $status ] = gti_status_label( $entity_type, $status );
    }
    return $out;
}

/**
 * Is this transition allowed?
 *
 * A no-op transition (from === to) is rejected: callers treat a successful
 * return as "something changed, send the email".
 */
function gti_status_can_transition( $entity_type, $from, $to ) {
    $map = gti_status_map( $entity_type );

    if ( ! isset( $map[ $to ] ) ) {
        return false;
    }
    if ( $from === $to ) {
        return false;
    }
    // An unrecognised current status (legacy row) may move to any known status.
    if ( ! isset( $map[ $from ] ) ) {
        return true;
    }

    return in_array( $to, $map[ $from ]['next'], true );
}

/**
 * Does moving to this status require a document upload?
 */
function gti_status_requires_attachment( $entity_type, $status ) {
    $map = gti_status_map( $entity_type );
    return isset( $map[ $status ]['requires'] ) && $map[ $status ]['requires'] === 'attachment';
}

/**
 * Table name for an entity type.
 */
function gti_entity_table( $entity_type ) {
    global $wpdb;

    $tables = array(
        'request'   => $wpdb->prefix . 'gti_requests',
        'quotation' => $wpdb->prefix . 'gti_quotations',
        'sell'      => $wpdb->prefix . 'gti_sell_requests',
    );

    return isset( $tables[ $entity_type ] ) ? $tables[ $entity_type ] : '';
}

/**
 * Human reference for a row ("REQ-2026-001"), used in email subjects and logs.
 */
function gti_entity_ref( $entity_type, array $row ) {
    switch ( $entity_type ) {
        case 'request':
            return ! empty( $row['request_id'] ) ? $row['request_id'] : 'REQ-' . $row['id'];
        case 'quotation':
            return ! empty( $row['quotation_id'] ) ? $row['quotation_id'] : 'QUO-' . $row['id'];
        case 'sell':
            return ! empty( $row['sell_id'] ) ? $row['sell_id'] : 'SELL-' . $row['id'];
    }
    return (string) ( isset( $row['id'] ) ? $row['id'] : '' );
}

/**
 * Fetch one inbox row.
 *
 * @return array|null
 */
function gti_entity_row( $entity_type, $id ) {
    global $wpdb;

    $table = gti_entity_table( $entity_type );
    if ( ! $table || ! $id ) {
        return null;
    }

    $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );

    return $row ?: null;
}

/**
 * Record a status change and fire the side-effect hook.
 *
 * Order matters: the caller must have already committed its UPDATE. The mailer
 * listens on gti_status_changed, so an email can only follow a successful write
 * (PRD §3.1 A-04).
 *
 * @param array $context attachment_ids[], note, email (bool, default true)
 */
function gti_record_status_change( $entity_type, $id, $from, $to, array $context = array() ) {
    global $wpdb;

    $context = array_merge(
        array( 'attachment_ids' => array(), 'note' => '', 'email' => true ),
        $context
    );

    $wpdb->insert(
        $wpdb->prefix . 'gti_status_history',
        array(
            'entity_type' => $entity_type,
            'entity_id'   => $id,
            'from_status' => $from ?: null,
            'to_status'   => $to,
            'note'        => $context['note'] ?: null,
            'user_id'     => get_current_user_id() ?: null,
            'created_at'  => current_time( 'mysql' ),
        ),
        array( '%s', '%d', '%s', '%s', '%s', '%d', '%s' )
    );

    $history_id = (int) $wpdb->insert_id;

    /**
     * Fires after a status change is committed and recorded.
     *
     * @param string $entity_type request|quotation|sell
     * @param int    $id
     * @param string $from
     * @param string $to
     * @param array  $context
     */
    do_action( 'gti_status_changed', $entity_type, $id, $from, $to, $context );

    return $history_id;
}

/**
 * Timeline entries for a row, newest first.
 */
function gti_get_status_history( $entity_type, $id, $limit = 50 ) {
    global $wpdb;

    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT h.*, u.display_name AS actor
           FROM {$wpdb->prefix}gti_status_history h
      LEFT JOIN {$wpdb->users} u ON u.ID = h.user_id
          WHERE h.entity_type = %s AND h.entity_id = %d
       ORDER BY h.created_at DESC, h.id DESC
          LIMIT %d",
        $entity_type, $id, $limit
    ), ARRAY_A );

    return $rows ?: array();
}
