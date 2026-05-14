<?php
/** OS_Stock model — stock level queries. Correction #4: quantity_available computed in PHP. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_Stock {
    public static function get_for_item( $item_id ) {
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT s.*, w.name AS warehouse_name
             FROM {$wpdb->prefix}os_stock s
             LEFT JOIN {$wpdb->prefix}os_warehouses w ON s.warehouse_id = w.id
             WHERE s.item_id = %d", $item_id
        ) );
        foreach ( $rows as $r ) {
            $r->quantity_available = os_qty_available( $r->quantity_on_hand, $r->quantity_reserved );
        }
        return $rows;
    }
}
