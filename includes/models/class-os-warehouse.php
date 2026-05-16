<?php
/** OS_Warehouse model. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_Warehouse {
    public static function get_all( $active_only = true ) {
        global $wpdb;
        $sql = "SELECT * FROM {$wpdb->prefix}os_warehouses";
        if ( $active_only ) { $sql .= ' WHERE is_active = 1'; }
        return $wpdb->get_results( $sql . ' ORDER BY name ASC' );
    }

    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}os_warehouses WHERE id = %d", $id ) );
    }

    public static function create( $data ) {
        global $wpdb;
        $wpdb->insert( "{$wpdb->prefix}os_warehouses", array(
            'name'       => sanitize_text_field( $data['name'] ),
            'name_ar'    => sanitize_text_field( $data['name_ar'] ?? '' ),
            'location'   => sanitize_text_field( $data['location'] ?? '' ),
            'type'       => sanitize_key( $data['type'] ?? 'items' ),
            'manager_id' => ! empty( $data['manager_id'] ) ? (int) $data['manager_id'] : null,
            'is_active'  => 1,
            'created_at' => current_time( 'mysql', 1 ),
        ) );
        $id = $wpdb->insert_id;
        OS_Audit_Service::log( 'os_warehouses', $id, 'create', null, $data );
        return $id;
    }

    public static function update( $id, $data ) {
        global $wpdb;
        $old = self::get( $id );
        $wpdb->update( "{$wpdb->prefix}os_warehouses", array(
            'name'       => sanitize_text_field( $data['name'] ),
            'name_ar'    => sanitize_text_field( $data['name_ar'] ?? '' ),
            'location'   => sanitize_text_field( $data['location'] ?? '' ),
            'type'       => sanitize_key( $data['type'] ?? 'items' ),
            'manager_id' => ! empty( $data['manager_id'] ) ? (int) $data['manager_id'] : null,
            'is_active'  => (int) ( $data['is_active'] ?? 1 ),
        ), array( 'id' => $id ) );
        OS_Audit_Service::log( 'os_warehouses', $id, 'update', $old, $data );
        return $id;
    }
}
