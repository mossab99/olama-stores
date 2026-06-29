<?php
/** OS_API_Reports — REST endpoints for warehouse reports. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_API_Reports {
    const NS = 'olama-stores/v1';

    public static function register_routes() {
        $perm = function() { return OS_Roles::can( 'os_view_reports' ); };

        register_rest_route( self::NS, '/reports/stock-balance', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'stock_balance' ), 'permission_callback' => $perm,
        ) );
        register_rest_route( self::NS, '/reports/item-movements', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'item_movements' ), 'permission_callback' => $perm,
        ) );
        register_rest_route( self::NS, '/reports/assignee-custody', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'assignee_custody' ), 'permission_callback' => $perm,
        ) );
        register_rest_route( self::NS, '/reports/export/stock', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'export_stock' ), 'permission_callback' => $perm,
        ) );
        register_rest_route( self::NS, '/reports/export/assignments', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'export_assignments' ), 'permission_callback' => $perm,
        ) );
        register_rest_route( self::NS, '/reports/export/items', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'export_items' ), 'permission_callback' => $perm,
        ) );
        register_rest_route( self::NS, '/reports/dashboard', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'dashboard_kpis' ), 'permission_callback' => $perm,
        ) );
        // REC-13: Top items by value for dashboard expanded layout
        register_rest_route( self::NS, '/reports/dashboard/top-items', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'top_items_by_value' ), 'permission_callback' => $perm,
        ) );
        // REC-06: Inventory count route
        register_rest_route( self::NS, '/reports/inventory-count', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'inventory_count_status' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_run_inventory_count' ); },
        ) );
        // REC-18: Provider spend report
        register_rest_route( self::NS, '/reports/provider-spend', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'provider_spend' ),
            'permission_callback' => $perm,
        ) );
        // REC-19: Custody aging report
        register_rest_route( self::NS, '/reports/custody-aging', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'custody_aging' ),
            'permission_callback' => $perm,
        ) );
        register_rest_route( self::NS, '/reports/custom-stock', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'custom_stock' ),
            'permission_callback' => $perm,
        ) );
        // REC-14: Year-end transition
        register_rest_route( self::NS, '/reports/year-transition/summary', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'year_transition_summary' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_manage_settings' ); },
        ) );
        register_rest_route( self::NS, '/reports/year-transition/run', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'year_transition_run' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_manage_settings' ); },
        ) );
    }

    public static function stock_balance( $request ) {
        $args = array_filter( array(
            'warehouse_id' => (int) $request->get_param( 'warehouse_id' ),
            'category_id'  => (int) $request->get_param( 'category_id' ),
        ) );
        return rest_ensure_response( OS_Stock_Service::get_stock_levels( $args ) );
    }

    /**
     * Flexible stock availability report. Item attributes stored in the
     * specifications JSON are combined with the relational item fields.
     */
    public static function custom_stock( $request ) {
        global $wpdb;

        $filters = array(
            'warehouse_id' => (int) $request->get_param( 'warehouse_id' ),
            'category_id'  => (int) $request->get_param( 'category_id' ),
            'unit_id'      => (int) $request->get_param( 'unit_id' ),
            'provider_id'  => (int) $request->get_param( 'provider_id' ),
            'model_id'     => (int) $request->get_param( 'model_id' ),
            'fabric'       => sanitize_text_field( (string) $request->get_param( 'fabric' ) ),
            'color'        => sanitize_text_field( (string) $request->get_param( 'color' ) ),
            'size'         => sanitize_text_field( (string) $request->get_param( 'size' ) ),
        );

        $where  = array( 'i.is_active = 1' );
        $params = array();
        $columns = array(
            'warehouse_id' => 's.warehouse_id',
            'category_id'  => 'i.category_id',
            'unit_id'      => 'i.unit_id',
            'provider_id'  => 'i.provider_id',
        );
        foreach ( $columns as $key => $column ) {
            if ( $filters[ $key ] ) {
                $where[]  = "$column = %d";
                $params[] = $filters[ $key ];
            }
        }

        $spec = "CASE WHEN JSON_VALID(i.specifications) THEN i.specifications ELSE '{}' END";
        if ( $filters['model_id'] ) {
            $where[]  = "CAST(JSON_UNQUOTE(JSON_EXTRACT($spec, '$.model_id')) AS UNSIGNED) = %d";
            $params[] = $filters['model_id'];
        }
        foreach ( array( 'fabric', 'color' ) as $key ) {
            if ( $filters[ $key ] !== '' ) {
                $where[]  = "LOWER(TRIM(JSON_UNQUOTE(JSON_EXTRACT($spec, '$.$key')))) = LOWER(TRIM(%s))";
                $params[] = $filters[ $key ];
            }
        }
        if ( $filters['size'] !== '' ) {
            $where[]  = "(
                LOWER(TRIM(JSON_UNQUOTE(JSON_EXTRACT($spec, '$.size')))) = LOWER(TRIM(%s))
                OR LOWER(TRIM(i.name)) LIKE CONCAT('%', LOWER(TRIM(%s)), '%')
            )";
            $params[] = $filters['size'];
            $params[] = $filters['size'];
        }

        $sql = "SELECT
                    i.id AS item_id, i.sku, i.name AS item_name,
                    w.id AS warehouse_id, w.name AS warehouse_name,
                    c.name AS category_name, u.name AS unit_name, u.symbol AS unit_symbol,
                    p.company_name AS provider_name, cm.name AS model_name,
                    JSON_UNQUOTE(JSON_EXTRACT($spec, '$.fabric')) AS fabric,
                    JSON_UNQUOTE(JSON_EXTRACT($spec, '$.color')) AS color,
                    JSON_UNQUOTE(JSON_EXTRACT($spec, '$.size')) AS size,
                    s.quantity_on_hand, s.quantity_reserved,
                    (s.quantity_on_hand - s.quantity_reserved) AS quantity_available
                FROM {$wpdb->prefix}os_stock s
                INNER JOIN {$wpdb->prefix}os_items i ON i.id = s.item_id
                INNER JOIN {$wpdb->prefix}os_warehouses w ON w.id = s.warehouse_id
                LEFT JOIN {$wpdb->prefix}os_categories c ON c.id = i.category_id
                LEFT JOIN {$wpdb->prefix}os_units u ON u.id = i.unit_id
                LEFT JOIN {$wpdb->prefix}os_providers p ON p.id = i.provider_id
                LEFT JOIN {$wpdb->prefix}os_custom_models cm
                    ON cm.id = CAST(JSON_UNQUOTE(JSON_EXTRACT($spec, '$.model_id')) AS UNSIGNED)
                WHERE " . implode( ' AND ', $where ) . "
                ORDER BY i.name ASC, w.name ASC";

        $rows = $params
            ? $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) )
            : $wpdb->get_results( $sql );

        return rest_ensure_response( $rows ?: array() );
    }

    public static function item_movements( $request ) {
        $args = array_filter( array(
            'item_id'          => (int) $request->get_param( 'item_id' ),    // REC-11: item filter
            'warehouse_id'     => (int) $request->get_param( 'warehouse_id' ),
            'academic_year_id' => (int) $request->get_param( 'academic_year_id' ),
            'date_from'        => sanitize_text_field( $request->get_param( 'date_from' ) ),
            'date_to'          => sanitize_text_field( $request->get_param( 'date_to' ) ),
            'movement_type'    => sanitize_key( $request->get_param( 'movement_type' ) ),
            'limit'            => 500,
        ) );
        return rest_ensure_response( OS_Movement::get_list( $args ) );
    }

    public static function assignee_custody( $request ) {
        $type        = sanitize_key( $request->get_param( 'assignee_type' ) );
        $assignee_id = sanitize_text_field( $request->get_param( 'assignee_id' ) );
        $year_id     = (int) $request->get_param( 'academic_year_id' );
        return rest_ensure_response( OS_Assignment::get_for_assignee( $type, $assignee_id, $year_id ) );
    }

    public static function export_stock( $request ) {
        $args = array_filter( array(
            'warehouse_id' => (int) $request->get_param( 'warehouse_id' ),
            'category_id'  => (int) $request->get_param( 'category_id' ),
        ) );
        OS_Export_Service::export_stock_balance( $args );
    }

    public static function export_assignments( $request ) {
        $args = array_filter( array(
            'assignee_type'    => sanitize_key( $request->get_param( 'assignee_type' ) ),
            'academic_year_id' => (int) $request->get_param( 'academic_year_id' ),
            'status'           => sanitize_key( $request->get_param( 'status' ) ),
        ) );
        OS_Export_Service::export_assignments( $args );
    }

    public static function export_items( $request ) {
        $args = array_filter( array(
            'search'      => sanitize_text_field( (string) $request->get_param( 'search' ) ),
            'category_id' => (int) $request->get_param( 'category_id' ),
        ) );
        OS_Export_Service::export_items( $args );
    }

    public static function dashboard_kpis() {
        global $wpdb;
        $year_id   = os_get_active_year_id();
        $low_count = OS_Stock_Service::count_low_stock();

        $total_skus         = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}os_items WHERE is_active = 1" );
        $active_assignments = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}os_assignments WHERE status = 'active' AND academic_year_id = %d",
            $year_id
        ) );
        $pending_returns    = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}os_assignments
             WHERE status = 'active'
               AND expected_return_date IS NOT NULL
               AND expected_return_date < CURDATE()
               AND academic_year_id = %d",
            $year_id
        ) );

        // REC-09: Total inventory value = SUM(quantity_on_hand * unit_price)
        $inventory_value = (float) $wpdb->get_var(
            "SELECT COALESCE(SUM(s.quantity_on_hand * COALESCE(i.unit_price, 0)), 0)
             FROM {$wpdb->prefix}os_stock s
             LEFT JOIN {$wpdb->prefix}os_items i ON s.item_id = i.id
             WHERE i.is_active = 1"
        );

        $recent_movements = OS_Movement::get_list( array( 'limit' => 10 ) );

        return rest_ensure_response( array(
            'total_skus'          => $total_skus,
            'low_stock_count'     => $low_count,
            'active_assignments'  => $active_assignments,
            'pending_returns'     => $pending_returns,
            'inventory_value'     => round( $inventory_value, 2 ),  // REC-09
            'active_year_id'      => $year_id,
            'active_year_name'    => os_get_active_year_name(),
            'recent_movements'    => $recent_movements,
        ) );
    }

    // REC-13: Top 5 items by total stock value for dashboard expanded panel
    public static function top_items_by_value() {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT i.name, i.sku,
                    SUM(s.quantity_on_hand) AS total_qty,
                    COALESCE(i.unit_price, 0) AS unit_price,
                    SUM(s.quantity_on_hand * COALESCE(i.unit_price, 0)) AS total_value
             FROM {$wpdb->prefix}os_stock s
             LEFT JOIN {$wpdb->prefix}os_items i ON s.item_id = i.id
             WHERE i.is_active = 1
             GROUP BY s.item_id
             ORDER BY total_value DESC
             LIMIT 8"
        );
        return rest_ensure_response( $rows );
    }

    // REC-06: Inventory count status — returns all items with current stock vs last count
    public static function inventory_count_status( $request ) {
        global $wpdb;
        $warehouse_id = (int) $request->get_param( 'warehouse_id' );

        $where = $warehouse_id ? $wpdb->prepare( 'AND s.warehouse_id = %d', $warehouse_id ) : '';

        $rows = $wpdb->get_results(
            "SELECT i.id AS item_id, i.name, i.sku,
                    s.warehouse_id, w.name AS warehouse_name,
                    s.quantity_on_hand AS system_qty,
                    NULL AS counted_qty
             FROM {$wpdb->prefix}os_stock s
             LEFT JOIN {$wpdb->prefix}os_items i ON s.item_id = i.id
             LEFT JOIN {$wpdb->prefix}os_warehouses w ON s.warehouse_id = w.id
             WHERE i.is_active = 1 $where
             ORDER BY i.name ASC"
        );
        return rest_ensure_response( $rows );
    }

    // ── REC-18: Provider Spend Report ────────────────────────────────────────
    public static function provider_spend( $request ) {
        global $wpdb;
        $year_id   = (int) $request->get_param( 'academic_year_id' ) ?: os_get_active_year_id();
        $date_from = sanitize_text_field( $request->get_param( 'date_from' ) );
        $date_to   = sanitize_text_field( $request->get_param( 'date_to' ) );

        $where_parts = array( "sm.movement_type = 'purchase_receipt'" );
        $params      = array();

        if ( $date_from ) { $where_parts[] = 'sm.performed_at >= %s'; $params[] = $date_from . ' 00:00:00'; }
        if ( $date_to )   { $where_parts[] = 'sm.performed_at <= %s'; $params[] = $date_to . ' 23:59:59'; }

        $where_sql = implode( ' AND ', $where_parts );

        // Main query: spend by provider grouped = receipts × unit_price
        $sql = "SELECT
                    COALESCE(p.company_name, '—') AS provider_name,
                    p.id AS provider_id,
                    COUNT(DISTINCT sm.id) AS receipt_count,
                    SUM(sm.quantity) AS total_units,
                    SUM(sm.quantity * COALESCE(i.unit_price, 0)) AS total_spend
                FROM {$wpdb->prefix}os_stock_movements sm
                LEFT JOIN {$wpdb->prefix}os_items i ON sm.item_id = i.id
                LEFT JOIN {$wpdb->prefix}os_providers p ON i.provider_id = p.id
                WHERE $where_sql
                GROUP BY p.id
                ORDER BY total_spend DESC";

        $rows = $params
            ? $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) )
            : $wpdb->get_results( $sql );

        return rest_ensure_response( $rows ?: array() );
    }

    // ── REC-19: Custody Aging Report ─────────────────────────────────────────
    public static function custody_aging( $request ) {
        global $wpdb;
        $year_id      = (int) $request->get_param( 'academic_year_id' ) ?: os_get_active_year_id();
        $assignee_type = sanitize_key( $request->get_param( 'assignee_type' ) ?: 'employee' );

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT
                a.id,
                a.assignee_id,
                a.assignee_type,
                COALESCE(u.display_name, a.assignee_id) AS assignee_name,
                i.name AS item_name,
                i.sku,
                w.name AS warehouse_name,
                a.quantity_assigned,
                a.assigned_date,
                a.expected_return_date,
                a.status,
                DATEDIFF(CURDATE(), a.expected_return_date) AS days_overdue
             FROM {$wpdb->prefix}os_assignments a
             LEFT JOIN {$wpdb->prefix}os_items i       ON a.item_id = i.id
             LEFT JOIN {$wpdb->prefix}os_warehouses w  ON a.warehouse_id = w.id
             LEFT JOIN {$wpdb->users} u                ON (a.assignee_type = 'employee' AND CAST(a.assignee_id AS UNSIGNED) = u.ID)
             WHERE a.status = 'active'
               AND a.academic_year_id = %d
               AND a.assignee_type = %s
               AND a.expected_return_date IS NOT NULL
               AND a.expected_return_date < CURDATE()
             ORDER BY days_overdue DESC",
            $year_id,
            $assignee_type
        ) );

        return rest_ensure_response( $rows ?: array() );
    }

    // ── REC-14: Academic Year Transition ─────────────────────────────────────
    public static function year_transition_summary() {
        global $wpdb;
        $year_id = os_get_active_year_id();

        $open_custody = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}os_assignments WHERE status = 'active' AND assignee_type = 'employee' AND academic_year_id = %d",
            $year_id
        ) );
        $open_student = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}os_assignments WHERE status = 'active' AND assignee_type = 'student' AND academic_year_id = %d",
            $year_id
        ) );
        $overdue_custody = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}os_assignments
             WHERE status = 'active' AND assignee_type = 'employee'
               AND expected_return_date IS NOT NULL AND expected_return_date < CURDATE()
               AND academic_year_id = %d",
            $year_id
        ) );
        $total_stock_value = (float) $wpdb->get_var(
            "SELECT COALESCE(SUM(s.quantity_on_hand * COALESCE(i.unit_price, 0)), 0)
             FROM {$wpdb->prefix}os_stock s LEFT JOIN {$wpdb->prefix}os_items i ON s.item_id = i.id WHERE i.is_active = 1"
        );

        return rest_ensure_response( array(
            'year_id'          => $year_id,
            'year_name'        => os_get_active_year_name(),
            'open_custody'     => $open_custody,
            'open_student'     => $open_student,
            'overdue_custody'  => $overdue_custody,
            'total_stock_value'=> round( $total_stock_value, 2 ),
        ) );
    }

    public static function year_transition_run( $request ) {
        global $wpdb;
        $data    = $request->get_json_params();
        $action  = sanitize_key( $data['action'] ?? '' );
        $year_id = os_get_active_year_id();

        if ( $action === 'flag_overdue_custody' ) {
            // Add a note to all overdue custody records flagging them for follow-up
            $updated = $wpdb->query( $wpdb->prepare(
                "UPDATE {$wpdb->prefix}os_assignments
                 SET notes = CONCAT(COALESCE(notes,''), ' [YEAR-END: overdue at transition]')
                 WHERE status = 'active' AND assignee_type = 'employee'
                   AND expected_return_date IS NOT NULL AND expected_return_date < CURDATE()
                   AND academic_year_id = %d",
                $year_id
            ) );
            return rest_ensure_response( array( 'success' => true, 'flagged' => $updated ) );
        }

        if ( $action === 'export_balances' ) {
            // Trigger stock balance export for archiving before year close
            OS_Export_Service::export_stock_balance( array() );
            exit; // export sends headers
        }

        return new WP_Error( 'invalid_action', __( 'Unknown action.', 'olama-stores' ), array( 'status' => 400 ) );
    }
}
