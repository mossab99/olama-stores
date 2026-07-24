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
        $core_employees = $wpdb->prefix . 'olama_core_employees';
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
        $employee_join = OS_School_Integration::is_core_available()
            ? "LEFT JOIN {$core_employees} ce ON (a.assignee_type = 'employee' AND a.assignee_id = ce.employee_id)"
            : 'LEFT JOIN (SELECT NULL AS employee_id, NULL AS full_name) ce ON 1=0';

        $sql = "SELECT a.*, i.name AS item_name, i.sku, i.specifications,
                    w.name AS warehouse_name, w.type AS warehouse_type,
                    COALESCE(ce.full_name, u.display_name) AS assignee_name,
                    issuer.display_name AS issued_by_name
                FROM {$wpdb->prefix}os_assignments a
                LEFT JOIN {$wpdb->prefix}os_items i ON a.item_id = i.id
                LEFT JOIN {$wpdb->prefix}os_warehouses w ON a.warehouse_id = w.id
                {$employee_join}
                LEFT JOIN {$wpdb->users} u ON (a.assignee_type = 'employee' AND CAST(a.assignee_id AS UNSIGNED) = u.ID)
                LEFT JOIN {$wpdb->users} issuer ON a.assigned_by = issuer.ID
                WHERE $where_sql ORDER BY a.created_at DESC";

        $rows = $params
            ? $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) )
            : $wpdb->get_results( $sql );

        foreach ( $rows as $row ) {
            if ( isset( $row->specifications ) ) {
                $row->specifications = $row->specifications ? json_decode( $row->specifications, true ) : array();
            }
        }
        return $rows;
    }

    public static function get( $id ) {
        global $wpdb;
        $core_employees = $wpdb->prefix . 'olama_core_employees';
        $employee_join = OS_School_Integration::is_core_available()
            ? "LEFT JOIN {$core_employees} ce ON (a.assignee_type = 'employee' AND a.assignee_id = ce.employee_id)"
            : 'LEFT JOIN (SELECT NULL AS employee_id, NULL AS full_name) ce ON 1=0';
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT a.*, i.name AS item_name, i.sku,
                 w.name AS warehouse_name, w.type AS warehouse_type,
                 COALESCE(ce.full_name, u.display_name) AS assignee_name,
                 issuer.display_name AS issued_by_name
             FROM {$wpdb->prefix}os_assignments a
             LEFT JOIN {$wpdb->prefix}os_items i ON a.item_id = i.id
             LEFT JOIN {$wpdb->prefix}os_warehouses w ON a.warehouse_id = w.id
             {$employee_join}
             LEFT JOIN {$wpdb->users} u ON (a.assignee_type = 'employee' AND CAST(a.assignee_id AS UNSIGNED) = u.ID)
             LEFT JOIN {$wpdb->users} issuer ON a.assigned_by = issuer.ID
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
