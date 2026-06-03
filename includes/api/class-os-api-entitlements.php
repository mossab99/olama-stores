<?php
/**
 * OS_API_Entitlements — REST endpoints for uniform entitlements.
 * Namespace: olama-stores/v1
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_API_Entitlements {
    const NS = 'olama-stores/v1';

    public static function register_routes() {
        register_rest_route( self::NS, '/entitlements', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_entitlements' ),  'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'save_entitlement' ),  'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/entitlements/(?P<id>\d+)', array(
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_entitlement' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/uniform-sizes/student/(?P<student_uid>[^/]+)', array(
            array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'get_student_sizes' ), 'permission_callback' => array( __CLASS__, 'can_view' ) )
        ) );
    }

    public static function can_view()   { return OS_Roles::can( 'os_view_assignments' ); }
    public static function can_manage() { return OS_Roles::can( 'os_manage_settings' ); }

    public static function get_entitlements( $request ) {
        $args = array_filter( array(
            'academic_year_id' => (int) $request->get_param( 'academic_year_id' ),
            'grade_id'         => (int) $request->get_param( 'grade_id' ),
            'custom_model_id'  => (int) $request->get_param( 'custom_model_id' ),
        ) );
        return rest_ensure_response( OS_Entitlement::get_list( $args ) );
    }

    public static function save_entitlement( $request ) {
        $result = OS_Entitlement::create_or_update( $request->get_json_params() );
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( array( 'id' => $result ), 201 );
    }

    public static function delete_entitlement( $request ) {
        $ok = OS_Entitlement::delete( (int) $request['id'] );
        return rest_ensure_response( array( 'success' => $ok ) );
    }

    public static function get_student_sizes( $request ) {
        $uid = sanitize_text_field( $request['student_uid'] );
        $year_id = (int) $request->get_param( 'academic_year_id' );
        if ( ! $uid || ! $year_id ) {
            return new WP_Error( 'missing_params', __( 'student_uid and academic_year_id are required.', 'olama-stores' ), array( 'status' => 400 ) );
        }
        $row = OS_Uniform_Size::get_by_student_uid( $uid, $year_id );
        return rest_ensure_response( $row ?: new stdClass() );
    }
}
