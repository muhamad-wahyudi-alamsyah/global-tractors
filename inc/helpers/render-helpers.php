<?php
/**
 * Shared render components (PRD §13.9 / R4).
 *
 * Each of these replaces markup that was assembled by hand on every list page.
 * gti_render_pagination() matters most: it is the single place the page query
 * parameter is named, which is what makes A-01 (the 'paged' 404) structurally
 * impossible to reintroduce.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The query var used for dashboard paging.
 *
 * Never 'paged' — WordPress owns that one, and using it on a dashboard URL
 * makes the request 404 before the template is reached.
 */
const GTI_PAGE_QUERY_VAR = 'page_num';

/**
 * Read the current page number from the request.
 */
function gti_current_page_num() {
    return isset( $_GET[ GTI_PAGE_QUERY_VAR ] ) ? max( 1, (int) $_GET[ GTI_PAGE_QUERY_VAR ] ) : 1;
}

/**
 * Pagination block.
 *
 * @param array $args {
 *     @type int    $total    Total rows.
 *     @type int    $per_page Rows per page.
 *     @type int    $current  Current page (1-based).
 *     @type string $base_url Page URL without a query string.
 *     @type array  $params   Query params to carry across (search, filters…).
 * }
 */
function gti_render_pagination( array $args ) {
    $args = array_merge( array(
        'total' => 0, 'per_page' => 10, 'current' => 1,
        'base_url' => '', 'params' => array(),
    ), $args );

    $total    = (int) $args['total'];
    $per_page = max( 1, (int) $args['per_page'] );
    $current  = max( 1, (int) $args['current'] );
    $pages    = (int) ceil( $total / $per_page );
    $offset   = ( $current - 1 ) * $per_page;

    $params = array_filter( $args['params'], function ( $v ) { return $v !== '' && $v !== null; } );

    $link = function ( $page ) use ( $args, $params ) {
        return esc_url( $args['base_url'] . '?' . http_build_query(
            array_merge( $params, array( GTI_PAGE_QUERY_VAR => (int) $page ) )
        ) );
    };
    ?>
    <div class="gti-ue-pagination">
        <div class="gti-ue-pagination-info">
            <?php if ( $total > 0 ) : ?>
                Showing <?php echo esc_html( $offset + 1 ); ?>-<?php echo esc_html( min( $offset + $per_page, $total ) ); ?>
                of <?php echo esc_html( $total ); ?> entries
            <?php else : ?>
                No entries
            <?php endif; ?>
        </div>
        <div class="gti-ue-pagination-controls">
            <?php if ( $current > 1 ) : ?>
                <a href="<?php echo $link( $current - 1 ); ?>" class="gti-ue-page-btn" aria-label="Previous page"><i class="fas fa-chevron-left"></i></a>
            <?php else : ?>
                <button class="gti-ue-page-btn" disabled aria-label="Previous page"><i class="fas fa-chevron-left"></i></button>
            <?php endif; ?>

            <?php
            $start = max( 1, $current - 2 );
            $end   = min( $pages, $current + 2 );

            if ( $start > 1 ) : ?>
                <a href="<?php echo $link( 1 ); ?>" class="gti-ue-page-btn">1</a>
                <?php if ( $start > 2 ) : ?><span class="gti-ue-page-dots">...</span><?php endif; ?>
            <?php endif; ?>

            <?php for ( $i = $start; $i <= $end; $i++ ) : ?>
                <?php if ( $i === $current ) : ?>
                    <button class="gti-ue-page-btn active" aria-current="page"><?php echo (int) $i; ?></button>
                <?php else : ?>
                    <a href="<?php echo $link( $i ); ?>" class="gti-ue-page-btn"><?php echo (int) $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ( $end < $pages ) : ?>
                <?php if ( $end < $pages - 1 ) : ?><span class="gti-ue-page-dots">...</span><?php endif; ?>
                <a href="<?php echo $link( $pages ); ?>" class="gti-ue-page-btn"><?php echo (int) $pages; ?></a>
            <?php endif; ?>

            <?php if ( $current < $pages ) : ?>
                <a href="<?php echo $link( $current + 1 ); ?>" class="gti-ue-page-btn" aria-label="Next page"><i class="fas fa-chevron-right"></i></a>
            <?php else : ?>
                <button class="gti-ue-page-btn" disabled aria-label="Next page"><i class="fas fa-chevron-right"></i></button>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Status badge, driven by gti_status_map().
 *
 * Replaces the if/elseif chains that mapped status → class differently on
 * neighbouring pages (PRD §13.9).
 */
function gti_render_status_badge( $entity_type, $status ) {
    printf(
        '<span class="gti-ue-status-badge status-%s">%s</span>',
        esc_attr( gti_status_class( $entity_type, $status ) ),
        esc_html( gti_status_label( $entity_type, $status ) )
    );
}

/**
 * Stat card row.
 *
 * Same markup as the cards the list pages write by hand: label above value, and
 * the tone (available / reserved / sold) on the icon, which is where
 * dashboard.css colours it.
 *
 * @param array $cards [['label','value','icon','tone'], …]
 */
function gti_render_stat_cards( array $cards ) {
    echo '<div class="gti-ue-stats-row">';

    foreach ( $cards as $card ) {
        $card = array_merge( array( 'label' => '', 'value' => 0, 'icon' => 'fa-chart-bar', 'tone' => '' ), $card );
        ?>
        <div class="gti-ue-stat-card">
            <div class="<?php echo esc_attr( trim( 'gti-ue-stat-icon ' . $card['tone'] ) ); ?>"><i class="fas <?php echo esc_attr( $card['icon'] ); ?>"></i></div>
            <div class="gti-ue-stat-info">
                <p class="gti-ue-stat-label"><?php echo esc_html( $card['label'] ); ?></p>
                <p class="gti-ue-stat-value"><?php echo esc_html( $card['value'] ); ?></p>
            </div>
        </div>
        <?php
    }

    echo '</div>';
}

/**
 * Row action menu (three-dot dropdown).
 *
 * @param array $items [['label','icon','onclick'|'href','class'], …]
 */
function gti_render_action_menu( array $items ) {
    ?>
    <div class="gti-ue-action-menu">
        <button type="button" class="gti-ue-action-toggle" aria-label="Actions"><i class="fas fa-ellipsis-v"></i></button>
        <div class="gti-ue-action-dropdown">
            <?php foreach ( $items as $item ) :
                $item = array_merge( array( 'label' => '', 'icon' => '', 'onclick' => '', 'href' => '', 'class' => '' ), $item );
                if ( $item['href'] ) : ?>
                    <a href="<?php echo esc_url( $item['href'] ); ?>" class="gti-ue-action-item <?php echo esc_attr( $item['class'] ); ?>">
                        <i class="fas <?php echo esc_attr( $item['icon'] ); ?>"></i> <?php echo esc_html( $item['label'] ); ?>
                    </a>
                <?php else : ?>
                    <button type="button" class="gti-ue-action-item <?php echo esc_attr( $item['class'] ); ?>"
                            <?php if ( $item['onclick'] ) : ?>onclick="<?php echo esc_attr( $item['onclick'] ); ?>"<?php endif; ?>>
                        <i class="fas <?php echo esc_attr( $item['icon'] ); ?>"></i> <?php echo esc_html( $item['label'] ); ?>
                    </button>
                <?php endif;
            endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Empty-state block for a table with no rows. It goes inside
 * <td class="gti-ue-empty-cell">, which carries the spacing.
 */
function gti_render_empty_state( $icon, $title, $description = '' ) {
    ?>
    <div class="gti-ue-empty">
        <i class="fas <?php echo esc_attr( $icon ); ?>"></i>
        <p class="gti-ue-empty-title"><?php echo esc_html( $title ); ?></p>
        <?php if ( $description ) : ?><p><?php echo esc_html( $description ); ?></p><?php endif; ?>
    </div>
    <?php
}

/**
 * One drawer label/value row.
 *
 * @param bool $raw Pass true when $value is already-escaped HTML.
 */
function gti_render_drawer_row( $label, $value, $class = '', $raw = false ) {
    $display = ( $value === '' || $value === null ) ? '—' : $value;
    ?>
    <div class="gti-drawer-row">
        <span class="gti-drawer-label"><?php echo esc_html( $label ); ?></span>
        <span class="gti-drawer-value <?php echo esc_attr( $class ); ?>"><?php
            echo $raw ? wp_kses_post( $display ) : esc_html( $display );
        ?></span>
    </div>
    <?php
}

/**
 * Timeline built from wp_gti_status_history (PRD §3.3 C-06).
 *
 * The old timeline was inferred from created_at/updated_at and could therefore
 * only ever show two points. The markup and order are still the old drawer's —
 * oldest first, the latest event last with the gold dot — and inbox-ui.js
 * renderTimeline() builds the same thing.
 */
function gti_render_timeline( $entity_type, $entity_id ) {
    // History comes newest first; the timeline reads top to bottom in time.
    $events = array_reverse( gti_get_status_history( $entity_type, $entity_id ) );

    if ( ! $events ) {
        echo '<p class="gti-drawer-empty">Belum ada riwayat.</p>';
        return;
    }

    $last = count( $events ) - 1;
    ?>
    <div class="gti-drawer-timeline">
        <?php foreach ( $events as $i => $event ) : ?>
            <div class="gti-drawer-timeline-item">
                <div class="gti-drawer-timeline-dot<?php echo $i === $last ? ' is-active' : ''; ?>"></div>
                <div class="gti-drawer-timeline-event"><?php echo esc_html( gti_status_label( $entity_type, $event['to_status'] ) ); ?></div>
                <?php if ( ! empty( $event['note'] ) ) : ?>
                    <div class="gti-drawer-timeline-meta"><?php echo esc_html( $event['note'] ); ?></div>
                <?php endif; ?>
                <div class="gti-drawer-timeline-meta">
                    <?php echo esc_html( gti_format_date( $event['created_at'], 'd M Y, H:i' ) ); ?>
                    <?php if ( ! empty( $event['actor'] ) ) : ?>&middot; by <?php echo esc_html( $event['actor'] ); ?><?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Attached documents list for a drawer.
 */
function gti_render_documents( $entity_type, $entity_id, $kind = null ) {
    $documents = gti_get_attachments( $entity_type, $entity_id, $kind );

    if ( ! $documents ) {
        echo '<p class="gti-drawer-empty">Belum ada dokumen.</p>';
        return;
    }
    ?>
    <div class="gti-doc-list">
        <?php foreach ( $documents as $document ) : ?>
            <div class="gti-doc-item">
                <i class="fas fa-file-alt"></i>
                <div class="gti-doc-meta">
                    <strong><?php echo esc_html( $document['original_name'] ); ?></strong>
                    <span>
                        <?php echo esc_html( $document['size_text'] ); ?>
                        &middot; <?php echo esc_html( gti_format_date( $document['created_at'], 'd M Y' ) ); ?>
                    </span>
                </div>
                <?php if ( $document['url'] ) : ?>
                    <a href="<?php echo esc_url( $document['url'] ); ?>" class="gti-doc-dl" download target="_blank" rel="noopener" aria-label="Download">
                        <i class="fas fa-download"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Email history list for a drawer (PRD §6.6.6).
 */
function gti_render_email_history( $entity_type, $entity_id, $limit = 5 ) {
    $rows = GTI_Mailer::history( $entity_type, $entity_id, $limit );

    if ( ! $rows ) {
        echo '<p class="gti-drawer-empty">Belum ada email terkirim.</p>';
        return;
    }
    ?>
    <div class="gti-doc-list">
        <?php foreach ( $rows as $row ) : ?>
            <div class="gti-doc-item">
                <i class="fas <?php echo $row['send_result'] ? 'fa-envelope-open-text' : 'fa-envelope'; ?>"
                   style="<?php echo $row['send_result'] ? '' : 'color:#dc2626'; ?>"></i>
                <div class="gti-doc-meta">
                    <strong><?php echo esc_html( $row['subject'] ); ?></strong>
                    <span>
                        <?php echo esc_html( $row['recipient_email'] ); ?>
                        &middot; <?php echo esc_html( gti_format_date( $row['sent_at'], 'd M Y, H:i' ) ); ?>
                        <?php if ( ! $row['send_result'] ) : ?> &middot; gagal<?php endif; ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Normalise an Indonesian phone number for a wa.me link (PRD §6.7.1).
 *
 * Returns '' when there is nothing usable, so the caller can disable the button.
 */
function gti_wa_number( $phone ) {
    $digits = preg_replace( '/\D+/', '', (string) $phone );

    if ( $digits === '' ) {
        return '';
    }

    if ( strpos( $digits, '62' ) === 0 ) {
        // already country-coded
    } elseif ( strpos( $digits, '0' ) === 0 ) {
        $digits = '62' . substr( $digits, 1 );
    } elseif ( strpos( $digits, '8' ) === 0 ) {
        $digits = '62' . $digits;
    }

    return strlen( $digits ) >= 9 ? $digits : '';
}

/**
 * WhatsApp deep link with a prefilled follow-up message.
 */
function gti_wa_link( $phone, $message ) {
    $number = gti_wa_number( $phone );

    return $number ? 'https://wa.me/' . $number . '?text=' . rawurlencode( $message ) : '';
}

/**
 * "2020 – 2021", or the single year when only one end is known.
 */
function gti_year_range( $min, $max ) {
    $min = (int) $min;
    $max = (int) $max;

    if ( $min && $max ) {
        return $min === $max ? (string) $min : $min . ' – ' . $max;
    }

    return (string) ( $min ?: $max ?: '' );
}

/**
 * Row payload handed to the drawer as JSON.
 *
 * Values are pre-formatted server-side so the client never has to re-derive a
 * label or a currency format — and so the drawer cannot disagree with the table.
 */
function gti_request_row_payload( array $row ) {
    return array(
        'id'                => (int) $row['id'],
        'ref'               => $row['request_id'],
        'customer_name'     => $row['customer_name'],
        'customer_company'  => $row['customer_company'],
        'customer_email'    => $row['customer_email'],
        'customer_phone'    => $row['customer_phone'],
        'customer_address'  => $row['customer_address'] ?? '',
        'category'          => $row['category'],
        'brand'             => $row['brand'],
        'year_range'        => gti_year_range( $row['year_min'] ?? 0, $row['year_max'] ?? 0 ),
        'condition'         => $row['equipment_condition'] ?? '',
        'quantity'          => $row['quantity'],
        'duration'          => $row['duration'] ?? '',
        'location'          => $row['location'],
        'budget_text'       => $row['budget'] ? 'Rp ' . number_format( (float) $row['budget'], 0, ',', '.' ) : '—',
        'required_date'     => $row['required_date'] ?? '',
        'usage_purpose'     => $row['usage_purpose'] ?? '',
        'message'           => $row['message'] ?? ( $row['notes'] ?? '' ),
        'request_date_text' => gti_format_date( $row['request_date'] ?: $row['created_at'], 'd M Y' ),
        'sales_pic'         => $row['sales_pic'] ?? '',
        'assigned_to'       => (int) ( $row['assigned_to'] ?? 0 ),
        'status'            => $row['status'],
        'status_label'      => gti_status_label( 'request', $row['status'] ),
        'status_class'      => gti_status_class( 'request', $row['status'] ),
        'next'              => gti_status_next( 'request', $row['status'] ),
    );
}

/**
 * Row payload for Request Quotation.
 */
function gti_quotation_row_payload( array $row ) {
    $items = json_decode( (string) $row['items'], true );
    $items = is_array( $items ) ? $items : array();

    // The rental/spare-part answers the inquiry form collects (§7.2) used to end
    // up either appended to the message or buried inside the items JSON, so the
    // drawer never showed them. Lift them out once here, so the list and the
    // drawer read the same fields.
    $total_items = 0;
    $extras      = array( 'operator_needed' => '', 'unit_model' => '', 'urgency' => '' );

    foreach ( $items as $index => $item ) {
        $total_items += (int) ( $item['quantity'] ?? 1 );

        $split                    = gti_quotation_split_notes( $item['notes'] ?? '' );
        $items[ $index ]['notes'] = $split['text'];

        $extras['operator_needed'] = $extras['operator_needed'] ?: ( $item['operator_needed'] ?? '' ) ?: $split['operator_needed'];
        $extras['unit_model']      = $extras['unit_model']      ?: ( $item['unit_model'] ?? '' );
        $extras['urgency']         = $extras['urgency']         ?: ( $item['urgency'] ?? '' );
    }

    $notes = gti_quotation_split_notes( $row['additional_notes'] ?? '' );

    $extras['operator_needed'] = $extras['operator_needed'] ?: $notes['operator_needed'];

    $type_labels = array(
        'used'       => 'Used Equipment',
        'rental'     => 'Rental',
        'spare_part' => 'Spare Part',
    );

    return array(
        'id'                => (int) $row['id'],
        'ref'               => $row['quotation_id'],
        'quotation_number'  => $row['quotation_number'] ?? '',
        'customer_name'     => $row['customer_name'],
        'customer_company'  => $row['customer_company'],
        'customer_email'    => $row['customer_email'],
        'customer_phone'    => $row['customer_phone'],
        'customer_address'  => $row['customer_address'] ?? '',
        'items'             => $items,
        'total_items'       => $total_items ?: count( $items ),
        'total_text'        => $row['total'] ? 'Rp ' . number_format( (float) $row['total'], 0, ',', '.' ) : '—',
        'budget_text'       => ! empty( $row['budget'] ) ? 'Rp ' . number_format( (float) $row['budget'], 0, ',', '.' ) : '—',
        'quantity'          => $row['quantity'] ?? 1,
        'equipment_type'    => $row['equipment_type'] ?? '',
        'equipment_type_label' => $type_labels[ $row['equipment_type'] ?? '' ] ?? '',
        'payment_terms'     => gti_payment_term_label( $row['payment_terms'] ?? '' ),
        'delivery_location' => $row['delivery_location'] ?? '',
        'needed_date'       => ! empty( $row['needed_date'] ) ? gti_format_date( $row['needed_date'], 'd M Y' ) : '',
        'rental_period'     => gti_rental_period_text( $row ),
        'operator_needed'   => $extras['operator_needed'] ? ucfirst( $extras['operator_needed'] ) : '',
        'unit_model'        => $extras['unit_model'],
        'urgency'           => $extras['urgency'] ? ucfirst( $extras['urgency'] ) : '',
        'valid_until'       => ( ! empty( $row['valid_until'] ) && $row['valid_until'] !== '0000-00-00' )
                                ? gti_format_date( $row['valid_until'], 'd M Y' ) : '',
        'notes'             => $notes['text'],
        'source_url'        => $row['source_url'] ?? '',
        'request_date_text' => gti_format_date( $row['request_date'] ?: $row['created_at'], 'd M Y' ),
        'sales_pic'         => $row['sales_pic'] ?? '',
        'assigned_to'       => (int) ( $row['assigned_to'] ?? 0 ),
        'status'            => $row['status'],
        'status_label'      => gti_status_label( 'quotation', $row['status'] ),
        'status_class'      => gti_status_class( 'quotation', $row['status'] ),
        'next'              => gti_status_next( 'quotation', $row['status'] ),
    );
}

/**
 * Row payload for Sell Equipment.
 */
function gti_sell_row_payload( array $row ) {
    $phone = $row['customer_whatsapp'] ?: $row['customer_phone'];

    $unit = trim( implode( ' ', array_filter( array(
        $row['equipment_name'] ?? '', $row['equipment_brand'] ?? '', $row['equipment_model'] ?? '',
    ) ) ) );

    $message = sprintf(
        'Halo %s, terima kasih atas penawaran unit %s (%s %s, %s) kepada PT Global Tractors Indonesia. Kami ingin menindaklanjuti penawaran Anda.',
        $row['customer_name'],
        $row['equipment_name'] ?? '-',
        $row['equipment_brand'] ?? '-',
        $row['equipment_model'] ?? '-',
        $row['equipment_year'] ?? '-'
    );

    return array(
        'id'                 => (int) $row['id'],
        'ref'                => gti_entity_ref( 'sell', $row ),
        'customer_name'      => $row['customer_name'],
        'customer_company'   => $row['customer_company'],
        'customer_email'     => $row['customer_email'],
        'customer_phone'     => $row['customer_phone'],
        'whatsapp'           => gti_wa_link( $phone, $message ),
        'equipment_name'     => $row['equipment_name'] ?? '',
        'unit'               => $unit,
        'brand'              => $row['equipment_brand'] ?? '',
        'model'              => $row['equipment_model'] ?? '',
        'year'               => $row['equipment_year'] ?? '',
        'hours'              => $row['equipment_hours'] ?? '',
        'condition'          => $row['equipment_condition'] ?? '',
        'availability'       => $row['availability'] ?? '',
        'equipment_location' => $row['equipment_location'] ?? '',
        'price_text'         => $row['offered_price'] ? 'Rp ' . number_format( (float) $row['offered_price'], 0, ',', '.' ) : '—',
        'message'            => $row['message'] ?? ( $row['notes'] ?? '' ),
        'images'             => gti_resolve_image_urls( $row['images'] ?? '' ),
        'submitted_text'     => gti_format_date( $row['created_at'], 'd M Y' ),
        'updated_text'       => ! empty( $row['updated_at'] ) ? gti_format_date( $row['updated_at'], 'd M Y' ) : '',
        'invoice_requested'  => ! empty( $row['invoice_requested_at'] ) ? gti_format_date( $row['invoice_requested_at'], 'd M Y' ) : '',
        'sales_pic'          => $row['sales_pic'] ?? '',
        'assigned_to'        => (int) ( $row['assigned_to'] ?? 0 ),
        'status'             => $row['status'],
        'status_label'       => gti_status_label( 'sell', $row['status'] ),
        'status_class'       => gti_status_class( 'sell', $row['status'] ),
        'next'               => gti_status_next( 'sell', $row['status'] ),
    );
}

/**
 * Human label for a payment term key.
 */
function gti_payment_term_label( $key ) {
    $options = gti_payment_terms_options();

    return isset( $options[ $key ] ) ? $options[ $key ] : '';
}

/**
 * "12 Jan 2026 – 12 Apr 2026 (3 bulan)" for a rental quotation.
 */
function gti_rental_period_text( array $row ) {
    $start = $row['rental_start_date'] ?? '';
    $end   = $row['rental_end_date'] ?? '';

    if ( ! $start && ! $end ) {
        return $row['rental_duration'] ?? '';
    }

    $text = trim(
        ( $start ? gti_format_date( $start, 'd M Y' ) : '?' ) . ' – ' .
        ( $end ? gti_format_date( $end, 'd M Y' ) : '?' )
    );

    if ( ! empty( $row['rental_duration'] ) ) {
        $text .= ' (' . $row['rental_duration'] . ')';
    }

    return $text;
}

/**
 * Turn a stored images JSON blob into usable URLs.
 *
 * Entries may be a URL or a bare WP attachment ID. The previous code discarded
 * anything numeric, so images saved as attachment IDs simply vanished from the
 * drawer (PRD §6.7 note).
 */
function gti_resolve_image_urls( $raw ) {
    if ( empty( $raw ) ) {
        return array();
    }

    $list = is_array( $raw ) ? $raw : json_decode( (string) $raw, true );
    if ( ! is_array( $list ) ) {
        $list = array_filter( array_map( 'trim', explode( ',', (string) $raw ) ) );
    }

    $urls = array();
    foreach ( $list as $entry ) {
        if ( is_array( $entry ) ) {
            $entry = $entry['url'] ?? ( $entry['id'] ?? '' );
        }
        if ( $entry === '' || $entry === null ) {
            continue;
        }
        if ( is_numeric( $entry ) ) {
            $url = wp_get_attachment_url( (int) $entry );
            if ( $url ) {
                $urls[] = $url;
            }
            continue;
        }
        $urls[] = (string) $entry;
    }

    return $urls;
}
