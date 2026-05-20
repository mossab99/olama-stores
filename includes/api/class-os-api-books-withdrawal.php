<?php
/**
 * OS_API_Books_Withdrawal — REST endpoints for book batch distribution and reports.
 * Namespace: olama-stores/v1
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_API_Books_Withdrawal {

    const NS = 'olama-stores/v1';

    public static function register_routes() {
        // Permissions
        $perm_process = function() { return OS_Roles::can( 'os_process_assignments' ); };
        $perm_view    = function() { return OS_Roles::can( 'os_view_assignments' ); };
        $perm_report  = function() { return OS_Roles::can( 'os_view_reports' ); };
        $perm_settings= function() { return OS_Roles::can( 'os_manage_settings' ); };

        // Distribution pathways
        register_rest_route( self::NS, '/books-withdrawal/distribute/family', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'distribute_family' ),
            'permission_callback' => $perm_process,
        ) );
        register_rest_route( self::NS, '/books-withdrawal/distribute/class', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'distribute_class' ),
            'permission_callback' => $perm_process,
        ) );

        // Book allocations
        register_rest_route( self::NS, '/books-withdrawal/allocations', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_allocations' ), 'permission_callback' => $perm_view ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'save_allocations' ), 'permission_callback' => $perm_settings ),
        ) );

        // Reporting system
        register_rest_route( self::NS, '/books-withdrawal/reports/stock-report', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'get_store_stock_report' ),
            'permission_callback' => $perm_report,
        ) );
        register_rest_route( self::NS, '/books-withdrawal/reports/books-received', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'get_books_received_report' ),
            'permission_callback' => $perm_report,
        ) );
        register_rest_route( self::NS, '/books-withdrawal/reports/missing-books', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'get_missing_books_report' ),
            'permission_callback' => $perm_report,
        ) );
        register_rest_route( self::NS, '/books-withdrawal/reports/grade-coverage', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'get_grade_coverage_report' ),
            'permission_callback' => $perm_report,
        ) );
    }

    // ── Batch Distribution Pathways ──────────────────────────────────────────

    public static function distribute_family( $request ) {
        if ( ! OS_Roles::can( 'os_process_assignments' ) ) {
            return new WP_Error( 'unauthorized', __( 'You do not have permission to process assignments.', 'olama-stores' ), array( 'status' => 403 ) );
        }
        $data = $request->get_json_params();
        $family_id = sanitize_text_field( $data['family_id'] ?? '' );
        $warehouse_id = (int) ( $data['warehouse_id'] ?? 0 );
        $items = $data['items'] ?? array();
        $notes = sanitize_textarea_field( $data['notes'] ?? '' );
        $academic_year_id = ! empty( $data['academic_year_id'] ) ? (int) $data['academic_year_id'] : os_get_active_year_id();

        if ( empty( $family_id ) ) {
            return new WP_Error( 'invalid_data', __( 'Family search term/UID is required.', 'olama-stores' ), array( 'status' => 400 ) );
        }
        if ( $warehouse_id <= 0 ) {
            return new WP_Error( 'invalid_data', __( 'Warehouse ID is required.', 'olama-stores' ), array( 'status' => 400 ) );
        }
        if ( empty( $items ) || ! is_array( $items ) ) {
            return new WP_Error( 'invalid_data', __( 'At least one book must be selected.', 'olama-stores' ), array( 'status' => 400 ) );
        }

        // Fetch students in family
        $students = OS_School_Integration::get_students_by_family( $family_id, $academic_year_id );
        if ( empty( $students ) ) {
            return new WP_Error( 'no_students', __( 'No students found in this family for the current academic year.', 'olama-stores' ), array( 'status' => 404 ) );
        }

        global $wpdb;
        $wpdb->query( 'START TRANSACTION' );

        $issued_count = 0;
        foreach ( $students as $student ) {
            $issue_data = array(
                'assignee_type'    => 'student',
                'assignee_id'      => $student->student_uid,
                'warehouse_id'     => $warehouse_id,
                'academic_year_id' => $academic_year_id,
                'assigned_date'    => current_time( 'Y-m-d' ),
                'notes'            => $notes ?: __( 'Family-based batch distribution.', 'olama-stores' ),
                'items'            => $items,
            );

            $result = OS_Stock_Service::issue_items_batch( $issue_data );
            if ( is_wp_error( $result ) ) {
                $wpdb->query( 'ROLLBACK' );
                return $result;
            }
            $issued_count++;
        }

        $wpdb->query( 'COMMIT' );
        return rest_ensure_response( array(
            'success' => true,
            'message' => sprintf( __( 'Successfully issued books to %d student(s) in family.', 'olama-stores' ), $issued_count ),
        ) );
    }

    public static function distribute_class( $request ) {
        if ( ! OS_Roles::can( 'os_process_assignments' ) ) {
            return new WP_Error( 'unauthorized', __( 'You do not have permission to process assignments.', 'olama-stores' ), array( 'status' => 403 ) );
        }
        $data = $request->get_json_params();
        $section_id = (int) ( $data['section_id'] ?? 0 );
        $warehouse_id = (int) ( $data['warehouse_id'] ?? 0 );
        $items = $data['items'] ?? array();
        $notes = sanitize_textarea_field( $data['notes'] ?? '' );
        $academic_year_id = ! empty( $data['academic_year_id'] ) ? (int) $data['academic_year_id'] : os_get_active_year_id();

        if ( $section_id <= 0 ) {
            return new WP_Error( 'invalid_data', __( 'Section is required.', 'olama-stores' ), array( 'status' => 400 ) );
        }
        if ( $warehouse_id <= 0 ) {
            return new WP_Error( 'invalid_data', __( 'Warehouse ID is required.', 'olama-stores' ), array( 'status' => 400 ) );
        }
        if ( empty( $items ) || ! is_array( $items ) ) {
            return new WP_Error( 'invalid_data', __( 'At least one book must be selected.', 'olama-stores' ), array( 'status' => 400 ) );
        }

        // Fetch students in section
        $students = OS_School_Integration::get_students( array(
            'academic_year_id' => $academic_year_id,
            'section_id'       => $section_id,
        ) );
        if ( empty( $students ) ) {
            return new WP_Error( 'no_students', __( 'No students enrolled in this section for the current academic year.', 'olama-stores' ), array( 'status' => 404 ) );
        }

        global $wpdb;
        $wpdb->query( 'START TRANSACTION' );

        $issued_count = 0;
        foreach ( $students as $student ) {
            $issue_data = array(
                'assignee_type'    => 'student',
                'assignee_id'      => $student->student_uid,
                'warehouse_id'     => $warehouse_id,
                'academic_year_id' => $academic_year_id,
                'assigned_date'    => current_time( 'Y-m-d' ),
                'notes'            => $notes ?: __( 'Grade-Section batch distribution.', 'olama-stores' ),
                'items'            => $items,
            );

            $result = OS_Stock_Service::issue_items_batch( $issue_data );
            if ( is_wp_error( $result ) ) {
                $wpdb->query( 'ROLLBACK' );
                return $result;
            }
            $issued_count++;
        }

        $wpdb->query( 'COMMIT' );
        return rest_ensure_response( array(
            'success' => true,
            'message' => sprintf( __( 'Successfully issued books to %d student(s) in class.', 'olama-stores' ), $issued_count ),
        ) );
    }

    // ── Book Allocations Configuration ────────────────────────────────────────

    public static function get_allocations( $request ) {
        $grade_id = (int) $request->get_param( 'grade_id' );
        $allocations = get_option( 'os_book_allocations', array() );
        if ( $grade_id > 0 ) {
            $item_ids = $allocations[$grade_id] ?? array();
            $items = array();
            foreach ( $item_ids as $id ) {
                $item = OS_Item::get( $id );
                if ( $item ) {
                    $items[] = $item;
                }
            }
            return rest_ensure_response( $items );
        }
        return rest_ensure_response( $allocations );
    }

    public static function save_allocations( $request ) {
        if ( ! OS_Roles::can( 'os_manage_settings' ) ) {
            return new WP_Error( 'unauthorized', __( 'You do not have permission to manage settings.', 'olama-stores' ), array( 'status' => 403 ) );
        }
        $data = $request->get_json_params();
        $grade_id = (int) ( $data['grade_id'] ?? 0 );
        $item_ids = $data['item_ids'] ?? array();

        if ( $grade_id <= 0 ) {
            return new WP_Error( 'invalid_data', __( 'Grade ID is required.', 'olama-stores' ), array( 'status' => 400 ) );
        }

        $allocations = get_option( 'os_book_allocations', array() );
        $allocations[$grade_id] = array_map( 'intval', $item_ids );
        update_option( 'os_book_allocations', $allocations );

        return rest_ensure_response( array( 'success' => true ) );
    }

    // ── Reporting System ──────────────────────────────────────────────────────

    public static function get_store_stock_report( $request ) {
        global $wpdb;
        $books_warehouses = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}os_warehouses WHERE type = 'books' AND is_active = 1" );
        
        if ( empty( $books_warehouses ) ) {
            return rest_ensure_response( array() );
        }

        $where = array( 's.warehouse_id IN (' . implode( ',', array_map( 'intval', $books_warehouses ) ) . ')' );
        $params = array();

        $category_id = (int) $request->get_param( 'category_id' );
        if ( $category_id > 0 ) {
            $where[] = 'i.category_id = %d';
            $params[] = $category_id;
        }

        $search = sanitize_text_field( $request->get_param( 'search' ) );
        if ( ! empty( $search ) ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $where[] = '(i.name LIKE %s OR i.sku LIKE %s)';
            $params[] = $like;
            $params[] = $like;
        }

        $where_sql = implode( ' AND ', $where );
        $sql = "SELECT s.*,
                       (s.quantity_on_hand - s.quantity_reserved) AS quantity_available,
                       i.name, i.name_ar, i.sku, i.barcode, i.min_stock_level, i.provider_id, i.category_id,
                       c.name AS category_name,
                       u.name AS unit_name, u.symbol AS unit_symbol,
                       w.name AS warehouse_name
                FROM {$wpdb->prefix}os_stock s
                JOIN {$wpdb->prefix}os_items i ON s.item_id = i.id
                LEFT JOIN {$wpdb->prefix}os_categories c ON i.category_id = c.id
                LEFT JOIN {$wpdb->prefix}os_units u ON i.unit_id = u.id
                LEFT JOIN {$wpdb->prefix}os_warehouses w ON s.warehouse_id = w.id
                WHERE $where_sql
                ORDER BY i.name ASC";

        $results = $params
            ? $wpdb->get_results( $wpdb->prepare( $sql, $params ) )
            : $wpdb->get_results( $sql );

        return rest_ensure_response( $results );
    }

    public static function get_books_received_report( $request ) {
        global $wpdb;
        $academic_year_id = ! empty( $request->get_param( 'academic_year_id' ) ) ? (int) $request->get_param( 'academic_year_id' ) : os_get_active_year_id();

        $where = array( "a.assignee_type = 'student'", "w.type = 'books'" );
        $params = array();

        if ( $academic_year_id > 0 ) {
            $where[] = 'a.academic_year_id = %d';
            $params[] = $academic_year_id;
        }

        $date_from = sanitize_text_field( $request->get_param( 'date_from' ) );
        if ( ! empty( $date_from ) ) {
            $where[] = 'a.assigned_date >= %s';
            $params[] = $date_from;
        }

        $date_to = sanitize_text_field( $request->get_param( 'date_to' ) );
        if ( ! empty( $date_to ) ) {
            $where[] = 'a.assigned_date <= %s';
            $params[] = $date_to;
        }

        $grade_id = (int) $request->get_param( 'grade_id' );
        $section_id = (int) $request->get_param( 'section_id' );
        $family_id = sanitize_text_field( $request->get_param( 'family_id' ) );
        $student_name = sanitize_text_field( $request->get_param( 'student_name' ) );

        $join_enrollment = "";
        if ( $grade_id > 0 || $section_id > 0 || ! empty( $family_id ) || ! empty( $student_name ) ) {
            $join_enrollment = "JOIN {$wpdb->prefix}olama_student_enrollment e ON a.assignee_id = e.student_uid
                                JOIN {$wpdb->prefix}olama_students s ON e.student_uid = s.student_uid";
            if ( $academic_year_id > 0 ) {
                $where[] = 'e.academic_year_id = %d';
                $params[] = $academic_year_id;
            }
            if ( $grade_id > 0 ) {
                $join_enrollment .= " JOIN {$wpdb->prefix}olama_sections sec ON e.section_id = sec.id";
                $where[] = 'sec.grade_id = %d';
                $params[] = $grade_id;
            }
            if ( $section_id > 0 ) {
                $where[] = 'e.section_id = %d';
                $params[] = $section_id;
            }
            if ( ! empty( $family_id ) ) {
                $where[] = 's.family_id = %s';
                $params[] = $family_id;
            }
            if ( ! empty( $student_name ) ) {
                $like = '%' . $wpdb->esc_like( $student_name ) . '%';
                $where[] = '(s.student_name LIKE %s OR s.student_uid LIKE %s)';
                $params[] = $like;
                $params[] = $like;
            }
        }

        $where_sql = implode( ' AND ', $where );
        $sql = "SELECT a.*, i.name AS item_name, i.sku, w.name AS warehouse_name,
                       stud.student_name, g.grade_name, sec.section_name, stud.family_id
                FROM {$wpdb->prefix}os_assignments a
                LEFT JOIN {$wpdb->prefix}os_items i ON a.item_id = i.id
                LEFT JOIN {$wpdb->prefix}os_warehouses w ON a.warehouse_id = w.id
                JOIN {$wpdb->prefix}olama_students stud ON a.assignee_id = stud.student_uid
                LEFT JOIN {$wpdb->prefix}olama_student_enrollment enroll ON stud.student_uid = enroll.student_uid " . ( $academic_year_id > 0 ? " AND enroll.academic_year_id = {$academic_year_id}" : "" ) . "
                LEFT JOIN {$wpdb->prefix}olama_sections sec ON enroll.section_id = sec.id
                LEFT JOIN {$wpdb->prefix}olama_grades g ON sec.grade_id = g.id
                $join_enrollment
                WHERE $where_sql
                GROUP BY a.id
                ORDER BY a.assigned_date DESC, a.created_at DESC";

        $results = $params
            ? $wpdb->get_results( $wpdb->prepare( $sql, $params ) )
            : $wpdb->get_results( $sql );

        return rest_ensure_response( $results );
    }

    public static function get_missing_books_report( $request ) {
        global $wpdb;
        $academic_year_id = ! empty( $request->get_param( 'academic_year_id' ) ) ? (int) $request->get_param( 'academic_year_id' ) : os_get_active_year_id();
        $grade_id = (int) $request->get_param( 'grade_id' );
        $section_id = (int) $request->get_param( 'section_id' );
        $family_id = sanitize_text_field( $request->get_param( 'family_id' ) );
        $student_uid = sanitize_text_field( $request->get_param( 'student_uid' ) );

        $allocations = get_option( 'os_book_allocations', array() );

        $query = "SELECT s.student_uid, s.student_name, s.family_id, sec.grade_id, sec.section_name, g.grade_name, e.section_id
                  FROM {$wpdb->prefix}olama_students s
                  JOIN {$wpdb->prefix}olama_student_enrollment e ON s.student_uid = e.student_uid
                  JOIN {$wpdb->prefix}olama_sections sec ON e.section_id = sec.id
                  JOIN {$wpdb->prefix}olama_grades g ON sec.grade_id = g.id
                  WHERE 1=1";

        $params = array();
        if ( $academic_year_id > 0 ) {
            $query .= " AND e.academic_year_id = %d";
            $params[] = $academic_year_id;
        }
        if ( $grade_id > 0 ) {
            $query .= " AND sec.grade_id = %d";
            $params[] = $grade_id;
        }
        if ( $section_id > 0 ) {
            $query .= " AND e.section_id = %d";
            $params[] = $section_id;
        }
        if ( ! empty( $family_id ) ) {
            $query .= " AND s.family_id = %s";
            $params[] = $family_id;
        }
        if ( ! empty( $student_uid ) ) {
            $query .= " AND s.student_uid = %s";
            $params[] = $student_uid;
        }

        $query .= " ORDER BY g.grade_level ASC, sec.section_name ASC, s.student_name ASC";
        $students = $params ? $wpdb->get_results( $wpdb->prepare( $query, $params ) ) : $wpdb->get_results( $query );

        $report = array();
        foreach ( $students as $stud ) {
            $stud_grade_id = (int) $stud->grade_id;
            $stud_allocated_items = $allocations[$stud_grade_id] ?? array();

            if ( empty( $stud_allocated_items ) ) {
                continue; 
            }

            $received_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT item_id FROM {$wpdb->prefix}os_assignments
                 WHERE assignee_type = 'student' AND assignee_id = %s AND status = 'active'" . ( $academic_year_id > 0 ? " AND academic_year_id = %d" : "" ),
                $stud->student_uid, $academic_year_id
            ) );

            $missing_ids = array_diff( $stud_allocated_items, $received_ids );

            if ( ! empty( $missing_ids ) ) {
                $missing_books = array();
                foreach ( $missing_ids as $m_id ) {
                    $item = OS_Item::get( $m_id );
                    if ( $item ) {
                        $missing_books[] = array(
                            'id'   => $item->id,
                            'name' => $item->name,
                            'sku'  => $item->sku,
                        );
                    }
                }

                if ( ! empty( $missing_books ) ) {
                    $report[] = array(
                        'student_uid'   => $stud->student_uid,
                        'student_name'  => $stud->student_name,
                        'family_id'     => $stud->family_id,
                        'grade_name'    => $stud->grade_name,
                        'section_name'  => $stud->section_name,
                        'missing_books' => $missing_books,
                    );
                }
            }
        }

        return rest_ensure_response( $report );
    }

    public static function get_grade_coverage_report( $request ) {
        global $wpdb;
        $academic_year_id = ! empty( $request->get_param( 'academic_year_id' ) ) ? (int) $request->get_param( 'academic_year_id' ) : os_get_active_year_id();

        $allocations = get_option( 'os_book_allocations', array() );
        $sections = OS_School_Integration::get_sections( $academic_year_id );

        $report = array();
        foreach ( $sections as $sec ) {
            $section_id = (int) $sec->id;
            $grade_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT grade_id FROM {$wpdb->prefix}olama_sections WHERE id = %d", $section_id ) );
            $allocated_items = $allocations[$grade_id] ?? array();
            $allocated_count = count( $allocated_items );

            $students = $wpdb->get_results( $wpdb->prepare(
                "SELECT student_uid FROM {$wpdb->prefix}olama_student_enrollment
                 WHERE section_id = %d" . ( $academic_year_id > 0 ? " AND academic_year_id = %d" : "" ),
                $section_id, $academic_year_id
            ) );
            $student_count = count( $students );

            if ( $student_count === 0 ) {
                $report[] = array(
                    'section_id'      => $section_id,
                    'section_name'    => $sec->section_name,
                    'grade_name'      => $sec->grade_name,
                    'student_count'   => 0,
                    'allocated_count' => $allocated_count,
                    'total_expected'  => 0,
                    'total_received'  => 0,
                    'coverage_pct'    => 100,
                );
                continue;
            }

            if ( $allocated_count === 0 ) {
                $report[] = array(
                    'section_id'      => $section_id,
                    'section_name'    => $sec->section_name,
                    'grade_name'      => $sec->grade_name,
                    'student_count'   => $student_count,
                    'allocated_count' => 0,
                    'total_expected'  => 0,
                    'total_received'  => 0,
                    'coverage_pct'    => 0,
                );
                continue;
            }

            $student_uids = array_map( function( $s ) { return $s->student_uid; }, $students );
            
            $placeholders = implode( ',', array_fill( 0, count( $allocated_items ), '%d' ) );
            $student_placeholders = implode( ',', array_fill( 0, count( $student_uids ), '%s' ) );

            $query = $wpdb->prepare(
                "SELECT COUNT(DISTINCT assignee_id, item_id)
                 FROM {$wpdb->prefix}os_assignments
                 WHERE assignee_type = 'student'
                   AND assignee_id IN ($student_placeholders)
                   AND item_id IN ($placeholders)
                   AND status = 'active'" . ( $academic_year_id > 0 ? " AND academic_year_id = %d" : "" ),
                array_merge( $student_uids, $allocated_items, $academic_year_id ? array( $academic_year_id ) : array() )
            );

            $total_received = (int) $wpdb->get_var( $query );
            $total_expected = $student_count * $allocated_count;
            $coverage_pct = round( ( $total_received / $total_expected ) * 100 );

            $report[] = array(
                'section_id'      => $section_id,
                'section_name'    => $sec->section_name,
                'grade_name'      => $sec->grade_name,
                'student_count'   => $student_count,
                'allocated_count' => $allocated_count,
                'total_expected'  => $total_expected,
                'total_received'  => $total_received,
                'coverage_pct'    => $coverage_pct,
            );
        }

        return rest_ensure_response( $report );
    }
}
