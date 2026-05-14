<?php
/**
 * OS_Assignment model — CRUD for assignments.
 * Correction #2: assignee_id is VARCHAR(50) — student_uid or WP user ID string.
 * Correction #1: academic_year_id is INT.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_Assignment {

    public static function get_list( $args = array() ) {
        global $wpdb;
        $where  = array( '1=1' );
        $params = array();

        if ( ! empty( $args['assignee_type'] ) ) {
            $where[] = 'a.assignee_type = %s'; $params[] = $args['assignee_type'];
        }
        if ( ! empty( $args['assignee_id'] ) ) {
            // Correction #2: string comparison for VARCHAR assignee_id
            $where[] = 'a.assignee_id = %s'; $params[] = (string) $args['assignee_id'];
        }
        if ( ! empty( $args['status'] ) ) {
            $where[] = 'a.status = %s'; $params[] = $args['status'];
        }
        if ( ! empty( $args['item_id'] ) ) {
            $where[] = 'a.item_id = %d'; $params[] = (int) $args['item_id'];
        }
        if ( ! empty( $args['academic_year_id'] ) ) {
            // Correction #1: INT comparison
            $where[] = 'a.academic_year_id = %d'; $params[] = (int) $args['academic_year_id'];
        }
        if ( ! empty( $args['warehouse_id'] ) ) {
            $where[] = 'a.warehouse_id = %d'; $params[] = (int) $args['warehouse_id'];
        }

        $where_sql = implode( ' AND ', $where );
        $sql = "SELECT a.*, i.name AS item_name, i.sku, w.name AS warehouse_name
                FROM {$wpdb->prefix}os_assignments a
                LEFT JOIN {$wpdb->prefix}os_items i ON a.item_id = i.id
                LEFT JOIN {$wpdb->prefix}os_warehouses w ON a.warehouse_id = w.id
                WHERE $where_sql ORDER BY a.created_at DESC";

        return $params
            ? $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) )
            : $wpdb->get_results( $sql );
    }

    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT a.*, i.name AS item_name, i.sku, w.name AS warehouse_name
             FROM {$wpdb->prefix}os_assignments a
             LEFT JOIN {$wpdb->prefix}os_items i ON a.item_id = i.id
             LEFT JOIN {$wpdb->prefix}os_warehouses w ON a.warehouse_id = w.id
             WHERE a.id = %d", $id
        ) );
    }

    /** Get all active assignments for an assignee (by type + VARCHAR id). */
    public static function get_for_assignee( $type, $assignee_id, $academic_year_id = 0 ) {
        return self::get_list( array_filter( array(
            'assignee_type'    => $type,
            'assignee_id'      => (string) $assignee_id, // Correction #2
            'status'           => null, // all statuses
            'academic_year_id' => $academic_year_id ?: null,
        ) ) );
    }
}
