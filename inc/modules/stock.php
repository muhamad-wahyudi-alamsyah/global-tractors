<?php
/**
 * Stock movement — spare part inventory leaves the shelf when a quotation for
 * it is completed.
 *
 * Until this module existed nothing ever wrote wp_gti_spare_parts.stock except
 * the add form and the edit modal, so an admin had to remember to subtract the
 * quantity by hand after every delivery. The catalogue kept advertising parts
 * that were already gone.
 *
 * Hooked on gti_status_changed rather than on the inbox handler, so it runs for
 * every path that commits a status change (PRD §5.1) and never for one that
 * failed to write.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Take a completed quotation's quantity out of the part's stock.
 *
 * 'completed' is terminal in the quotation status map, so a quotation can enter
 * it at most once and no restock path is needed. Assignment logging re-fires
 * gti_status_changed with from === to, hence the guard.
 */
add_action( 'gti_status_changed', 'gti_stock_consume_on_quotation_completed', 10, 4 );

function gti_stock_consume_on_quotation_completed( $entity_type, $id, $from, $to ) {
    if ( ! gti_stock_should_consume( $entity_type, $from, $to ) ) {
        return;
    }

    $row = gti_entity_row( 'quotation', $id );
    if ( ! $row || 'spare_part' !== ( $row['equipment_type'] ?? '' ) ) {
        return;
    }

    gti_stock_consume_spare_part(
        (int) ( $row['equipment_id'] ?? 0 ),
        (int) ( $row['quantity'] ?? 0 ),
        gti_entity_ref( 'quotation', $row )
    );
}

/**
 * Does this status change take stock off the shelf?
 *
 * Kept separate from the hook so the decision is testable without a database.
 */
function gti_stock_should_consume( $entity_type, $from, $to ) {
    return 'quotation' === $entity_type && 'completed' === $to && $from !== $to;
}

/**
 * Subtract a quantity from a spare part and re-derive its status.
 *
 * @param string $ref Human reference for the activity log ("Q-202609-0001").
 * @return bool Whether a row was decremented.
 */
function gti_stock_consume_spare_part( $part_id, $qty, $ref = '' ) {
    global $wpdb;

    if ( $part_id <= 0 || $qty <= 0 ) {
        return false;
    }

    $table = $wpdb->prefix . 'gti_spare_parts';

    // One statement: the read and the write cannot interleave with a concurrent
    // completion of the same part, and GREATEST() keeps stock off negative
    // numbers when the quantity sold exceeds what was recorded on hand.
    $updated = $wpdb->query( $wpdb->prepare(
        "UPDATE {$table} SET stock = GREATEST(0, stock - %d) WHERE id = %d AND status <> 'draft'",
        $qty, $part_id
    ) );

    if ( ! $updated ) {
        return false;
    }

    // Status is derived from the quantity everywhere else (dashboard badge,
    // catalogue card, catalogue filter). Re-derive it here through the same
    // helper so a sale cannot leave the three disagreeing.
    $part = $wpdb->get_row( $wpdb->prepare(
        "SELECT name, stock, minimum_stock FROM {$table} WHERE id = %d",
        $part_id
    ) );

    if ( ! $part ) {
        return true;
    }

    $wpdb->update(
        $table,
        array( 'status' => gti_spare_stock_status( $part->stock, $part->minimum_stock ) ),
        array( 'id' => $part_id ),
        array( '%s' ),
        array( '%d' )
    );

    gti_log_current_activity(
        'stock_out',
        sprintf( '%s: -%d, sisa %d%s', $part->name, $qty, (int) $part->stock, $ref ? ' (' . $ref . ')' : '' ),
        'spare_part',
        $part_id
    );

    return true;
}
