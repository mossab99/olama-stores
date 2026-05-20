<?php
/** OS_API_Stock — REST endpoints for stock levels, receipts, adjustments, movements. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_API_Stock {
    const NS = 'olama-stores/v1';

    public static function register_routes() {
        register_rest_route( self::NS, '/stock', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'get_stock' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_view_stock' ); },
        ) );
        register_rest_route( self::NS, '/stock/reset-testing', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'reset_testing' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_manage_settings' ); },
        ) );
        register_rest_route( self::NS, '/stock/delete-transactions', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'delete_transactions' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_manage_settings' ) || current_user_can( 'manage_options' ); },
        ) );
        register_rest_route( self::NS, '/stock/receive', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'receive_stock' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_receive_stock' ); },
        ) );
        register_rest_route( self::NS, '/stock/adjust', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'adjust_stock' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_adjust_stock' ); },
        ) );
        register_rest_route( self::NS, '/stock/movements', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'get_movements' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_view_stock' ); },
        ) );
        register_rest_route( self::NS, '/stock/movements/(?P<id>\d+)', array(
            'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_movement' ),
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ) );
        register_rest_route( self::NS, '/warehouses', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_warehouses' ), 'permission_callback' => function() { return OS_Roles::can( 'os_view_stock' ); } ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_warehouse'), 'permission_callback' => function() { return OS_Roles::can( 'os_manage_warehouses' ); } ),
        ) );
        register_rest_route( self::NS, '/warehouses/(?P<id>\d+)', array(
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_warehouse' ), 'permission_callback' => function() { return OS_Roles::can( 'os_manage_warehouses' ); } ),
        ) );
    }

    public static function get_stock( $request ) {
        $args = array_filter( array(
            'warehouse_id'    => (int) $request->get_param( 'warehouse_id' ),
            'item_id'         => (int) $request->get_param( 'item_id' ),
            'category_id'     => (int) $request->get_param( 'category_id' ),
            'low_stock_only'  => (bool) $request->get_param( 'low_stock_only' ),
            'orderby'         => sanitize_text_field( $request->get_param( 'orderby' ) ),
            'order'           => sanitize_text_field( $request->get_param( 'order' ) ),
            'paged'           => (int) $request->get_param( 'paged' ),
            'per_page'        => (int) $request->get_param( 'per_page' ),
        ) );
        return rest_ensure_response( OS_Stock_Service::get_stock_levels( $args ) );
    }

    public static function reset_testing( $request ) {
        $result = OS_Stock_Service::reset_store_data_testing();
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( array( 'success' => true ) );
    }

    public static function receive_stock( $request ) {
        $data   = $request->get_json_params();
        // Correction #1: ensure academic_year_id is int
        $data['academic_year_id'] = ! empty( $data['academic_year_id'] ) ? (int) $data['academic_year_id'] : os_get_active_year_id();
        $result = OS_Stock_Service::record_receipt( $data );
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( array( 'movement_id' => $result ), 201 );
    }

    public static function adjust_stock( $request ) {
        $data   = $request->get_json_params();
        $data['academic_year_id'] = ! empty( $data['academic_year_id'] ) ? (int) $data['academic_year_id'] : os_get_active_year_id();
        $result = OS_Stock_Service::manual_adjustment( $data );
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( array( 'movement_id' => $result ), 201 );
    }

    public static function get_movements( $request ) {
        $args = array_filter( array(
            'item_id'          => (int) $request->get_param( 'item_id' ),
            'warehouse_id'     => (int) $request->get_param( 'warehouse_id' ),
            'movement_type'    => sanitize_key( $request->get_param( 'movement_type' ) ),
            'performed_by'     => (int) $request->get_param( 'performed_by' ),
            'academic_year_id' => (int) $request->get_param( 'academic_year_id' ),
            'date_from'        => sanitize_text_field( $request->get_param( 'date_from' ) ),
            'date_to'          => sanitize_text_field( $request->get_param( 'date_to' ) ),
            'limit'            => (int) ( $request->get_param( 'limit' ) ?: 100 ),
        ) );
        return rest_ensure_response( OS_Movement::get_list( $args ) );
    }

    public static function delete_movement( $request ) {
        $id = (int) $request['id'];
        $result = OS_Stock_Service::reverse_movement( $id );
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( array( 'success' => true ) );
    }

    public static function get_warehouses() {
        return rest_ensure_response( OS_Warehouse::get_all() );
    }

    public static function create_warehouse( $request ) {
        $id = OS_Warehouse::create( $request->get_json_params() );
        return rest_ensure_response( array( 'id' => $id ), 201 );
    }

    public static function update_warehouse( $request ) {
        $id = OS_Warehouse::update( (int) $request['id'], $request->get_json_params() );
        return rest_ensure_response( array( 'id' => $id ) );
    }

    public static function delete_transactions( $request ) {
        $data = $request->get_json_params() ?: array();

        $filters = array();
        if ( ! empty( $data['item_id'] ) ) {
            $filters['item_id'] = (int) $data['item_id'];
        }
        if ( ! empty( $data['provider_id'] ) ) {
            $filters['provider_id'] = (int) $data['provider_id'];
        }
        if ( ! empty( $data['start_date'] ) ) {
            $filters['start_date'] = sanitize_text_field( $data['start_date'] );
        }
        if ( ! empty( $data['end_date'] ) ) {
            $filters['end_date'] = sanitize_text_field( $data['end_date'] );
        }

        if ( empty( $filters ) ) {
            return new WP_Error( 'missing_filters', __( 'Please specify at least one filter.', 'olama-stores' ), array( 'status' => 400 ) );
        }

        $result = OS_Stock_Service::delete_transactions_testing( $filters );
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( array( 'success' => true ) );
    }
}
