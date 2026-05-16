<?php
/**
 * OS_API_Items — REST API for Item Registry CRUD.
 * Namespace: olama-stores/v1
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_API_Items {

    const NS = 'olama-stores/v1';

    public static function register_routes() {
        register_rest_route( self::NS, '/items', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_items' ),  'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_item'), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/items/(?P<id>\d+)', array(
            array( 'methods' => 'GET',    'callback' => array( __CLASS__, 'get_item' ),    'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_item' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_item' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/categories', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_categories' ), 'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_category'), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/categories/(?P<id>\d+)', array(
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_category' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_category' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/units', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_units' ), 'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_unit'), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/units/(?P<id>\d+)', array(
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_unit' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_unit' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/grades', array(
            array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'get_grades' ), 'permission_callback' => array( __CLASS__, 'can_view' ) ),
        ) );
        register_rest_route( self::NS, '/subjects/(?P<grade_id>\d+)', array(
            array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'get_subjects' ), 'permission_callback' => array( __CLASS__, 'can_view' ) ),
        ) );
        register_rest_route( self::NS, '/providers', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_providers' ),  'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_provider'), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/providers/(?P<id>\d+)', array(
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_provider' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_provider' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/custom-models', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_custom_models' ), 'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_custom_model'), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/custom-models/(?P<id>\d+)', array(
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_custom_model' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_custom_model' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/fabrics', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_fabrics' ), 'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_fabric'), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/fabrics/(?P<id>\d+)', array(
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_fabric' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_fabric' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
    }

    // ── Permission callbacks ───────────────────────────────────────────────────
    public static function can_view()   { return OS_Roles::can( 'os_view_items' ); }
    public static function can_manage() { return OS_Roles::can( 'os_manage_items' ); }

    // ── Item endpoints ────────────────────────────────────────────────────────
    public static function get_items( $request ) {
        $per_page = (int) $request->get_param( 'per_page' );
        $page     = (int) $request->get_param( 'page' );
        $is_custom = $request->get_param( 'is_custom' );

        $args = array(
            'category_id'     => (int) $request->get_param( 'category_id' ),
            'search'          => sanitize_text_field( $request->get_param( 'search' ) ),
            // Correction #1: academic_year_id as INT
            'academic_year_id'=> (int) $request->get_param( 'academic_year_id' ),
            'is_active'       => $request->get_param( 'is_active' ) !== null ? (bool) $request->get_param( 'is_active' ) : true,
            // Filter: only items belonging to the custom warehouse (have model_id in specs)
            'is_custom'       => ( $is_custom !== null && $is_custom !== '' && $is_custom !== '0' && $is_custom !== false ),
        );

        if ( $per_page > 0 ) {
            $args['limit']  = $per_page;
            $args['offset'] = ( max( 1, $page ) - 1 ) * $per_page;
        }

        $items = OS_Item::get_list( array_filter( $args ) );
        $total = OS_Item::count( array_filter( $args ) );

        $response = rest_ensure_response( $items );
        $response->header( 'X-WP-Total', (int) $total );
        $response->header( 'X-WP-TotalPages', $per_page > 0 ? ceil( $total / $per_page ) : 1 );

        return $response;
    }

    public static function get_item( $request ) {
        $item = OS_Item::get( (int) $request['id'] );
        if ( ! $item ) { return new WP_Error( 'not_found', __( 'Item not found.', 'olama-stores' ), array( 'status' => 404 ) ); }
        return rest_ensure_response( $item );
    }

    public static function create_item( $request ) {
        $result = OS_Item::create( $request->get_json_params() );
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( array( 'id' => $result ), 201 );
    }

    public static function update_item( $request ) {
        $result = OS_Item::update( (int) $request['id'], $request->get_json_params() );
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( OS_Item::get( (int) $request['id'] ) );
    }

    public static function delete_item( $request ) {
        OS_Item::delete( (int) $request['id'] );
        return rest_ensure_response( array( 'deleted' => true ) );
    }

    // ── Categories ────────────────────────────────────────────────────────────
    public static function get_categories() {
        global $wpdb;
        return rest_ensure_response( $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}os_categories WHERE is_active = 1 ORDER BY name ASC"
        ) );
    }

    public static function create_category( $request ) {
        global $wpdb;
        $data = $request->get_json_params();
        $wpdb->insert( "{$wpdb->prefix}os_categories", array(
            'name'      => sanitize_text_field( $data['name'] ?? '' ),
            'name_ar'   => sanitize_text_field( $data['name_ar'] ?? '' ),
            'parent_id' => ! empty( $data['parent_id'] ) ? (int) $data['parent_id'] : null,
            'description'=> sanitize_textarea_field( $data['description'] ?? '' ),
            'is_active' => 1,
        ) );
        return rest_ensure_response( array( 'id' => $wpdb->insert_id ), 201 );
    }

    public static function update_category( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $data = $request->get_json_params();
        $wpdb->update( "{$wpdb->prefix}os_categories", array(
            'name' => sanitize_text_field( $data['name'] ?? '' ),
        ), array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    public static function delete_category( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $wpdb->delete( "{$wpdb->prefix}os_categories", array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    // ── Units ─────────────────────────────────────────────────────────────────
    public static function get_units() {
        global $wpdb;
        return rest_ensure_response( $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}os_units ORDER BY name ASC" ) );
    }

    public static function create_unit( $request ) {
        global $wpdb;
        $data = $request->get_json_params();
        $wpdb->insert( "{$wpdb->prefix}os_units", array(
            'name'    => sanitize_text_field( $data['name'] ?? '' ),
            'name_ar' => sanitize_text_field( $data['name_ar'] ?? '' ),
            'symbol'  => sanitize_text_field( $data['symbol'] ?? '' ),
        ) );
        return rest_ensure_response( array( 'id' => $wpdb->insert_id ), 201 );
    }

    public static function update_unit( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $data = $request->get_json_params();
        $wpdb->update( "{$wpdb->prefix}os_units", array(
            'name'   => sanitize_text_field( $data['name'] ?? '' ),
            'symbol' => sanitize_text_field( $data['symbol'] ?? '' ),
        ), array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    public static function delete_unit( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $wpdb->delete( "{$wpdb->prefix}os_units", array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    // ── Grades & Subjects (from eval 02) ──────────────────────────────────────
    public static function get_grades() {
        if ( ! class_exists( 'Olama_School_Grade' ) ) { return rest_ensure_response( array() ); }
        return rest_ensure_response( Olama_School_Grade::get_grades() );
    }

    public static function get_subjects( $request ) {
        if ( ! class_exists( 'Olama_School_Subject' ) ) { return rest_ensure_response( array() ); }
        return rest_ensure_response( Olama_School_Subject::get_by_grade( (int) $request['grade_id'], true ) );
    }

    // ── Providers ─────────────────────────────────────────────────────────────
    public static function get_providers() {
        global $wpdb;
        return rest_ensure_response( $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}os_providers ORDER BY company_name ASC" ) );
    }

    public static function create_provider( $request ) {
        global $wpdb;
        $data = $request->get_json_params();
        $is_active = ! empty( $data['is_active'] ) ? 1 : 0;

        if ( $is_active ) {
            $wpdb->query( "UPDATE {$wpdb->prefix}os_providers SET is_active = 0" );
        }

        $wpdb->insert( "{$wpdb->prefix}os_providers", array(
            'company_name'   => sanitize_text_field( $data['company_name'] ?? '' ),
            'mobile_contact' => sanitize_text_field( $data['mobile_contact'] ?? '' ),
            'location'       => sanitize_text_field( $data['location'] ?? '' ),
            'contact_person' => sanitize_text_field( $data['contact_person'] ?? '' ),
            'is_active'      => $is_active,
        ) );
        return rest_ensure_response( array( 'id' => $wpdb->insert_id ), 201 );
    }

    public static function update_provider( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $data = $request->get_json_params();
        
        $update_data = array(
            'company_name'   => sanitize_text_field( $data['company_name'] ?? '' ),
            'mobile_contact' => sanitize_text_field( $data['mobile_contact'] ?? '' ),
            'location'       => sanitize_text_field( $data['location'] ?? '' ),
            'contact_person' => sanitize_text_field( $data['contact_person'] ?? '' ),
        );

        if ( isset( $data['is_active'] ) ) {
            $is_active = ! empty( $data['is_active'] ) ? 1 : 0;
            if ( $is_active ) {
                $wpdb->query( "UPDATE {$wpdb->prefix}os_providers SET is_active = 0" );
            }
            $update_data['is_active'] = $is_active;
        }

        $wpdb->update( "{$wpdb->prefix}os_providers", $update_data, array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    public static function delete_provider( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        // Check if provider is used by any item
        $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}os_items WHERE provider_id = %d", $id ) );
        if ( $count > 0 ) {
            return new WP_Error( 'provider_used', __( 'Cannot delete provider because it is linked to items.', 'olama-stores' ), array( 'status' => 400 ) );
        }
        $wpdb->delete( "{$wpdb->prefix}os_providers", array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    // ── Custom Models ─────────────────────────────────────────────────────────
    public static function get_custom_models() {
        global $wpdb;
        return rest_ensure_response( $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}os_custom_models ORDER BY name ASC" ) );
    }

    public static function create_custom_model( $request ) {
        global $wpdb;
        $data = $request->get_json_params();
        $wpdb->insert( "{$wpdb->prefix}os_custom_models", array(
            'name'    => sanitize_text_field( $data['name'] ?? '' ),
        ) );
        return rest_ensure_response( array( 'id' => $wpdb->insert_id ), 201 );
    }

    public static function update_custom_model( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $data = $request->get_json_params();
        $wpdb->update( "{$wpdb->prefix}os_custom_models", array(
            'name'    => sanitize_text_field( $data['name'] ?? '' ),
        ), array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    public static function delete_custom_model( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $wpdb->delete( "{$wpdb->prefix}os_custom_models", array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    // ── Fabrics ─────────────────────────────────────────────────────────
    public static function get_fabrics() {
        global $wpdb;
        return rest_ensure_response( $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}os_fabrics ORDER BY name ASC" ) );
    }

    public static function create_fabric( $request ) {
        global $wpdb;
        $data = $request->get_json_params();
        $wpdb->insert( "{$wpdb->prefix}os_fabrics", array(
            'name'    => sanitize_text_field( $data['name'] ?? '' ),
        ) );
        return rest_ensure_response( array( 'id' => $wpdb->insert_id ), 201 );
    }

    public static function update_fabric( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $data = $request->get_json_params();
        $wpdb->update( "{$wpdb->prefix}os_fabrics", array(
            'name'    => sanitize_text_field( $data['name'] ?? '' ),
        ), array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    public static function delete_fabric( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $wpdb->delete( "{$wpdb->prefix}os_fabrics", array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }
}
