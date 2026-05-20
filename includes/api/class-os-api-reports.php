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
    }

    public static function stock_balance( $request ) {
        $args = array_filter( array(
            'warehouse_id' => (int) $request->get_param( 'warehouse_id' ),
            'category_id'  => (int) $request->get_param( 'category_id' ),
        ) );
        return rest_ensure_response( OS_Stock_Service::get_stock_levels( $args ) );
    }

    public static function item_movements( $request ) {
        $args = array_filter( array(
            'item_id'          => (int) $request->get_param( 'item_id' ),
            'warehouse_id'     => (int) $request->get_param( 'warehouse_id' ),
            'academic_year_id' => (int) $request->get_param( 'academic_year_id' ),
            'date_from'        => sanitize_text_field( $request->get_param( 'date_from' ) ),
            'date_to'          => sanitize_text_field( $request->get_param( 'date_to' ) ),
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
        $year_id  = os_get_active_year_id();
        $low_count = OS_Stock_Service::count_low_stock();

        $total_skus       = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}os_items WHERE is_active = 1" );
        $active_assignments = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}os_assignments WHERE status = 'active'" );
        $recent_movements = OS_Movement::get_list( array( 'limit' => 10 ) );

        return rest_ensure_response( array(
            'total_skus'          => $total_skus,
            'low_stock_count'     => $low_count,
            'active_assignments'  => $active_assignments,
            'active_year_id'      => $year_id,
            'active_year_name'    => os_get_active_year_name(),
            'recent_movements'    => $recent_movements,
        ) );
    }
}
