<?php
/** OS_Movement — stock movement history queries. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_Movement {
    public static function get_list( $args = array() ) {
        global $wpdb;
        $where = array( '1=1' ); $params = array();

        if ( ! empty( $args['item_id'] ) )        { $where[] = 'm.item_id = %d';           $params[] = (int) $args['item_id']; }
        if ( ! empty( $args['warehouse_id'] ) )    { $where[] = 'm.warehouse_id = %d';       $params[] = (int) $args['warehouse_id']; }
        if ( ! empty( $args['movement_type'] ) )   { $where[] = 'm.movement_type = %s';      $params[] = $args['movement_type']; }
        if ( ! empty( $args['performed_by'] ) )    { $where[] = 'm.performed_by = %d';       $params[] = (int) $args['performed_by']; }
        if ( ! empty( $args['academic_year_id'] ) ){ $where[] = 'm.academic_year_id = %d';   $params[] = (int) $args['academic_year_id']; }
        if ( ! empty( $args['date_from'] ) )       { $where[] = 'm.performed_at >= %s';      $params[] = $args['date_from'] . ' 00:00:00'; }
        if ( ! empty( $args['date_to'] ) )         { $where[] = 'm.performed_at <= %s';      $params[] = $args['date_to'] . ' 23:59:59'; }

        $where_sql = implode( ' AND ', $where );
        $limit     = isset( $args['limit'] ) ? (int) $args['limit'] : 100;
        $sql = "SELECT m.*, i.name AS item_name, i.sku, w.name AS warehouse_name, u.display_name AS performed_by_name
                FROM {$wpdb->prefix}os_stock_movements m
                LEFT JOIN {$wpdb->prefix}os_items i ON m.item_id = i.id
                LEFT JOIN {$wpdb->prefix}os_warehouses w ON m.warehouse_id = w.id
                LEFT JOIN {$wpdb->users} u ON m.performed_by = u.ID
                WHERE $where_sql ORDER BY m.performed_at DESC LIMIT %d";

        $params[] = $limit;
        return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
    }
}
