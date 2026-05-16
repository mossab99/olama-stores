<?php
/**
 * OS_API_Assignments — REST endpoints for assignments and returns.
 * Correction #2: assignee_id treated as VARCHAR throughout.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_API_Assignments {
    const NS = 'olama-stores/v1';

    public static function register_routes() {
        register_rest_route( self::NS, '/assignments', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_assignments' ),
                   'permission_callback' => function() { return OS_Roles::can( 'os_view_assignments' ); } ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_assignment' ),
                   'permission_callback' => function() { return OS_Roles::can( 'os_process_assignments' ); } ),
        ) );
        register_rest_route( self::NS, '/assignments/(?P<id>\d+)', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'get_assignment' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_view_assignments' ); },
        ) );
        register_rest_route( self::NS, '/assignments/returns', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'process_return' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_process_assignments' ); },
        ) );
        // Reverse a specific withdrawal (marks as reversed, restores stock, logs movement)
        register_rest_route( self::NS, '/assignments/(?P<id>\d+)/reverse', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'reverse_assignment' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_process_assignments' ); },
        ) );
        // Get all assignments for an assignee (employee or student)
        register_rest_route( self::NS, '/assignees/(?P<type>employee|student)/(?P<assignee_id>[^/]+)', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'get_for_assignee' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_view_assignments' ); },
        ) );
        // Employees / Students lookup endpoints (school integration)
        register_rest_route( self::NS, '/employees', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'get_employees' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_view_assignments' ); },
        ) );
        register_rest_route( self::NS, '/students', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'get_students' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_view_assignments' ); },
        ) );
    }

    public static function get_assignments( $request ) {
        $args = array_filter( array(
            'assignee_type'    => sanitize_key( $request->get_param( 'assignee_type' ) ),
            // Correction #2: string assignee_id
            'assignee_id'      => sanitize_text_field( $request->get_param( 'assignee_id' ) ),
            'status'           => sanitize_key( $request->get_param( 'status' ) ),
            'item_id'          => (int) $request->get_param( 'item_id' ),
            // Correction #1: INT year
            'academic_year_id' => (int) $request->get_param( 'academic_year_id' ),
        ) );
        return rest_ensure_response( OS_Assignment::get_list( $args ) );
    }

    public static function get_assignment( $request ) {
        $rec = OS_Assignment::get( (int) $request['id'] );
        if ( ! $rec ) { return new WP_Error( 'not_found', __( 'Assignment not found.', 'olama-stores' ), array( 'status' => 404 ) ); }
        return rest_ensure_response( $rec );
    }

    public static function create_assignment( $request ) {
        $data = $request->get_json_params();
        // Correction #1 & #2 enforced in OS_Stock_Service::issue_items()
        $data['academic_year_id'] = ! empty( $data['academic_year_id'] ) ? (int) $data['academic_year_id'] : os_get_active_year_id();
        $result = OS_Stock_Service::issue_items( $data );
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( array( 'assignment_id' => $result ), 201 );
    }

    public static function process_return( $request ) {
        $result = OS_Stock_Service::process_return( $request->get_json_params() );
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( array( 'return_id' => $result ), 201 );
    }

    public static function reverse_assignment( $request ) {
        $id    = (int) $request['id'];
        $data  = $request->get_json_params() ?: array();
        $notes = sanitize_textarea_field( $data['notes'] ?? '' );
        $result = OS_Stock_Service::reverse_assignment( $id, $notes );
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( array( 'movement_id' => $result ), 201 );
    }

    public static function get_for_assignee( $request ) {
        $type        = sanitize_key( $request['type'] );
        // Correction #2: assignee_id is VARCHAR (student_uid or WP user ID string)
        $assignee_id = sanitize_text_field( $request['assignee_id'] );
        $year_id     = (int) $request->get_param( 'academic_year_id' );
        $assignments = OS_Assignment::get_for_assignee( $type, $assignee_id, $year_id );
        return rest_ensure_response( $assignments );
    }

    // Correction #3/#6: proxies that call actual static class methods
    public static function get_employees() {
        $employees = OS_School_Integration::get_employees();
        return rest_ensure_response( $employees );
    }

    public static function get_students( $request ) {
        $search  = sanitize_text_field( $request->get_param( 'search' ) );
        $year_id = (int) $request->get_param( 'academic_year_id' );

        // If searching, prioritize family-based lookup for the Withdrawals workflow
        if ( ! empty( $search ) ) {
            $students = OS_School_Integration::get_students_by_family( $search, $year_id );
            return rest_ensure_response( $students );
        }

        $args = array_filter( array(
            'academic_year_id' => $year_id,
            'section_id'       => (int) $request->get_param( 'section_id' ),
        ) );
        $students = OS_School_Integration::get_students( $args );
        return rest_ensure_response( $students );
    }
}
