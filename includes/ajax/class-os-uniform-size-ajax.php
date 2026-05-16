<?php
/**
 * OS_Uniform_Size_Ajax — AJAX handlers for Student Uniform Size Registration.
 *
 * Integration: reads grades/sections/years directly from Olama School DB tables.
 * All endpoints require manage_options capability and nonce verification.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_Uniform_Size_Ajax {

    public static function register() {
        add_action( 'wp_ajax_os_get_students_for_sizing',  array( __CLASS__, 'get_students' ) );
        add_action( 'wp_ajax_os_save_student_size',        array( __CLASS__, 'save_size' ) );
        add_action( 'wp_ajax_os_delete_student_size',      array( __CLASS__, 'delete_size' ) );
        add_action( 'wp_ajax_os_get_size_stats',           array( __CLASS__, 'get_stats' ) );
        add_action( 'wp_ajax_os_export_sizes_csv',         array( __CLASS__, 'export_csv' ) );
        add_action( 'wp_ajax_os_get_size_dashboard',       array( __CLASS__, 'get_dashboard' ) );
        // NEW: cascade dropdown — sections for a grade in the active year
        add_action( 'wp_ajax_os_get_sections_for_grade',   array( __CLASS__, 'get_sections_for_grade' ) );
    }

    // ── Auth helper ─────────────────────────────────────────────────────────────
    private static function check_auth( string $action = 'os_uniform_size_nonce' ) {
        check_ajax_referer( $action, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
        }
    }

    // ── Sections for a grade (cascade dropdown) ─────────────────────────────────
    public static function get_sections_for_grade() {
        self::check_auth();

        $grade_id   = (int) ( $_POST['grade_id']   ?? 0 );
        $year_id    = (int) ( $_POST['year_id']    ?? 0 );

        if ( ! $grade_id || ! $year_id ) {
            wp_send_json_error( array( 'message' => 'grade_id and year_id are required.' ) );
        }

        global $wpdb;
        $sections = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, section_name
             FROM {$wpdb->prefix}olama_sections
             WHERE grade_id = %d AND academic_year_id = %d
             ORDER BY section_name ASC",
            $grade_id,
            $year_id
        ), ARRAY_A );

        wp_send_json_success( array( 'sections' => $sections ?: array() ) );
    }

    // ── Get students + existing sizes for a grade/section/year ───────────────────
    public static function get_students() {
        self::check_auth();

        $year_id    = (int) ( $_POST['year_id']    ?? 0 );
        $grade_id   = (int) ( $_POST['grade_id']   ?? 0 );
        $section_id = (int) ( $_POST['section_id'] ?? 0 );
        $filter     = sanitize_text_field( wp_unslash( $_POST['filter'] ?? 'all' ) ); // all|unsized|recent

        if ( ! $year_id || ! $grade_id ) {
            wp_send_json_error( array( 'message' => 'year_id and grade_id are required.' ) );
        }

        // Resolve year_name and grade_name for display and DB storage
        global $wpdb;
        $year_row  = $wpdb->get_row( $wpdb->prepare(
            "SELECT year_name FROM {$wpdb->prefix}olama_academic_years WHERE id = %d",
            $year_id
        ) );
        $grade_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT grade_name FROM {$wpdb->prefix}olama_grades WHERE id = %d",
            $grade_id
        ) );

        if ( ! $year_row || ! $grade_row ) {
            wp_send_json_error( array( 'message' => 'Invalid year_id or grade_id.' ) );
        }

        $year_name  = $year_row->year_name;
        $grade_name = $grade_row->grade_name;

        // Load students from Olama School enrollment
        $students = self::fetch_students( $year_id, $grade_id, $section_id );

        // Load existing sizes — key by student_uid
        $sized_map = OS_Uniform_Size::get_by_grade_id( $year_name, $grade_id, $section_id );

        // Allowed sizes: use grade_name mapping
        $allowed_sizes = OS_Uniform_Size::get_allowed_sizes_for_grade_name( $grade_name );

        // Merge size data into students
        $total = count( $students );
        $sized = 0;
        foreach ( $students as &$s ) {
            $uid = $s['student_uid'] ?? '';
            if ( isset( $sized_map[ $uid ] ) ) {
                $s['current_size']   = (int) $sized_map[ $uid ]['uniform_size'];
                $s['measured_at']    = $sized_map[ $uid ]['measured_at'];
                $s['size_record_id'] = (int) $sized_map[ $uid ]['id'];
                $sized++;
            } else {
                $s['current_size']   = null;
                $s['measured_at']    = null;
                $s['size_record_id'] = null;
            }
        }
        unset( $s );

        // Apply filter
        if ( $filter === 'unsized' ) {
            $students = array_values( array_filter( $students, fn( $s ) => $s['current_size'] === null ) );
        } elseif ( $filter === 'recent' ) {
            $students = array_values( array_filter( $students, fn( $s ) => $s['current_size'] !== null ) );
            usort( $students, fn( $a, $b ) => strcmp( $b['measured_at'] ?? '', $a['measured_at'] ?? '' ) );
        }

        $pct = $total > 0 ? round( ( $sized / $total ) * 100, 1 ) : 0;

        wp_send_json_success( array(
            'students'      => $students,
            'allowed_sizes' => $allowed_sizes,
            'grade_name'    => $grade_name,
            'year_name'     => $year_name,
            'completion'    => array(
                'total'      => $total,
                'sized'      => $sized,
                'unsized'    => $total - $sized,
                'percentage' => $pct,
            ),
        ) );
    }

    // ── Save a single student size ──────────────────────────────────────────────
    public static function save_size() {
        self::check_auth();

        $student_uid  = sanitize_text_field( wp_unslash( $_POST['student_uid']  ?? '' ) );
        $year_id      = (int) ( $_POST['year_id']    ?? 0 );
        $grade_id     = (int) ( $_POST['grade_id']   ?? 0 );
        $section_id   = (int) ( $_POST['section_id'] ?? 0 );
        $uniform_size = (int) ( $_POST['uniform_size'] ?? 0 );
        $notes        = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );

        if ( ! $student_uid || ! $year_id || ! $grade_id || ! $uniform_size ) {
            wp_send_json_error( array( 'message' => 'Missing required fields.' ) );
        }

        global $wpdb;
        $year_row  = $wpdb->get_row( $wpdb->prepare(
            "SELECT year_name FROM {$wpdb->prefix}olama_academic_years WHERE id = %d", $year_id ) );
        $grade_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT grade_name FROM {$wpdb->prefix}olama_grades WHERE id = %d", $grade_id ) );
        $section_row = $section_id ? $wpdb->get_row( $wpdb->prepare(
            "SELECT section_name FROM {$wpdb->prefix}olama_sections WHERE id = %d", $section_id ) ) : null;

        $year_name    = $year_row  ? $year_row->year_name    : '';
        $grade_name   = $grade_row ? $grade_row->grade_name  : '';
        $section_name = $section_row ? $section_row->section_name : '';

        $result = OS_Uniform_Size::save( array(
            'student_uid'      => $student_uid,
            'academic_year_id' => $year_id,
            'academic_year'    => $year_name,
            'grade_id'         => $grade_id,
            'grade'            => $grade_name,
            'section_id'       => $section_id ?: null,
            'section'          => $section_name,
            'uniform_size'     => $uniform_size,
            'notes'            => $notes,
            'measured_by'      => get_current_user_id(),
        ) );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        // Return updated completion stats
        $sized_map   = OS_Uniform_Size::get_by_grade_id( $year_name, $grade_id, $section_id );
        $all_students = self::fetch_students( $year_id, $grade_id, $section_id );
        $total = count( $all_students );
        $sized = count( $sized_map );
        $pct   = $total > 0 ? round( ( $sized / $total ) * 100, 1 ) : 0;

        wp_send_json_success( array(
            'record_id'  => $result,
            'completion' => array(
                'total'      => $total,
                'sized'      => $sized,
                'unsized'    => $total - $sized,
                'percentage' => $pct,
            ),
        ) );
    }

    // ── Delete a student's size record ──────────────────────────────────────────
    public static function delete_size() {
        self::check_auth();

        $student_uid = sanitize_text_field( wp_unslash( $_POST['student_uid']   ?? '' ) );
        $year_id     = (int) ( $_POST['year_id'] ?? 0 );

        if ( ! $student_uid || ! $year_id ) {
            wp_send_json_error( array( 'message' => 'Missing student_uid or year_id.' ) );
        }

        global $wpdb;
        $year_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT year_name FROM {$wpdb->prefix}olama_academic_years WHERE id = %d", $year_id ) );
        $year_name = $year_row ? $year_row->year_name : '';

        $ok = OS_Uniform_Size::delete( $student_uid, $year_name );
        if ( ! $ok ) {
            wp_send_json_error( array( 'message' => 'Delete failed or record not found.' ) );
        }

        wp_send_json_success( array( 'deleted' => true ) );
    }

    // ── Get stats ────────────────────────────────────────────────────────────────
    public static function get_stats() {
        self::check_auth();

        $year_id    = (int) ( $_POST['year_id']    ?? 0 );
        $grade_id   = (int) ( $_POST['grade_id']   ?? 0 );
        $section_id = (int) ( $_POST['section_id'] ?? 0 );

        global $wpdb;
        $year_name = $wpdb->get_var( $wpdb->prepare(
            "SELECT year_name FROM {$wpdb->prefix}olama_academic_years WHERE id = %d", $year_id ) );

        $size_totals  = OS_Uniform_Size::get_size_totals_by_grade_id( $year_name, $grade_id );
        $all_students = self::fetch_students( $year_id, $grade_id, $section_id );
        $sized_map    = OS_Uniform_Size::get_by_grade_id( $year_name, $grade_id, $section_id );
        $total = count( $all_students );
        $sized = count( $sized_map );

        wp_send_json_success( array(
            'size_totals' => $size_totals,
            'completion'  => array(
                'total'      => $total,
                'sized'      => $sized,
                'unsized'    => $total - $sized,
                'percentage' => $total > 0 ? round( ( $sized / $total ) * 100, 1 ) : 0,
            ),
        ) );
    }

    // ── Export CSV ───────────────────────────────────────────────────────────────
    public static function export_csv() {
        self::check_auth();

        $year_id    = (int) ( $_POST['year_id']    ?? 0 );
        $grade_id   = (int) ( $_POST['grade_id']   ?? 0 );
        $section_id = (int) ( $_POST['section_id'] ?? 0 );

        global $wpdb;
        $year_row  = $wpdb->get_row( $wpdb->prepare(
            "SELECT year_name FROM {$wpdb->prefix}olama_academic_years WHERE id = %d", $year_id ) );
        $grade_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT grade_name FROM {$wpdb->prefix}olama_grades WHERE id = %d", $grade_id ) );

        $year_name  = $year_row  ? $year_row->year_name   : (string) $year_id;
        $grade_name = $grade_row ? $grade_row->grade_name : (string) $grade_id;

        $students  = self::fetch_students( $year_id, $grade_id, $section_id );
        $sized_map = OS_Uniform_Size::get_by_grade_id( $year_name, $grade_id, $section_id );

        $rows   = array();
        $rows[] = array( 'Student UID', 'Student Name', 'Grade', 'Section', 'Uniform Size', 'Measured At', 'Notes' );

        foreach ( $students as $s ) {
            $uid    = $s['student_uid'] ?? '';
            $record = $sized_map[ $uid ] ?? null;
            $rows[] = array(
                $uid,
                $s['student_name'] ?? '',
                $s['grade_name']   ?? $grade_name,
                $s['section_name'] ?? '',
                $record ? $record['uniform_size'] : '',
                $record ? $record['measured_at']  : '',
                $record ? $record['notes']        : '',
            );
        }

        $filename = sanitize_file_name( 'uniform-sizes-' . $grade_name . '-' . $year_name . '.csv' );
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        $out = fopen( 'php://output', 'w' );
        // BOM for Excel UTF-8 compatibility
        fputs( $out, "\xEF\xBB\xBF" );
        foreach ( $rows as $row ) {
            fputcsv( $out, $row );
        }
        fclose( $out );
        exit;
    }

    // ── Dashboard (all grades for a year) ────────────────────────────────────────
    public static function get_dashboard() {
        self::check_auth();

        $year_id = (int) ( $_POST['year_id'] ?? 0 );

        global $wpdb;
        $year_name = $year_id
            ? $wpdb->get_var( $wpdb->prepare( "SELECT year_name FROM {$wpdb->prefix}olama_academic_years WHERE id = %d", $year_id ) )
            : sanitize_text_field( wp_unslash( $_POST['academic_year'] ?? '' ) );

        if ( ! $year_name ) {
            wp_send_json_error( array( 'message' => 'Invalid academic year.' ) );
        }

        $table       = $wpdb->prefix . 'os_student_uniform_sizes';
        $size_totals = OS_Uniform_Size::get_size_totals( $year_name );

        $grades = $wpdb->get_results( $wpdb->prepare(
            "SELECT u.grade, u.grade_id, COUNT(*) as cnt
             FROM {$table} u
             WHERE u.academic_year = %s
             GROUP BY u.grade_id, u.grade
             ORDER BY u.grade ASC",
            $year_name
        ), ARRAY_A );

        $total_sized = array_sum( array_column( $grades, 'cnt' ) );
        arsort( $size_totals );
        $top_sizes = array_slice( $size_totals, 0, 5, true );

        wp_send_json_success( array(
            'size_totals'  => $size_totals,
            'top_sizes'    => $top_sizes,
            'by_grade'     => $grades,
            'total_sized'  => $total_sized,
        ) );
    }

    // ── Internal: fetch enrolled students ────────────────────────────────────────
    /**
     * Returns rows from olama_students joined to enrollment for the given year+grade+section.
     *
     * @param int $year_id
     * @param int $grade_id
     * @param int $section_id  0 = all sections for this grade
     * @return array
     */
    private static function fetch_students( int $year_id, int $grade_id, int $section_id = 0 ): array {
        if ( ! class_exists( 'Olama_School_DB' ) ) {
            return array();
        }
        global $wpdb;

        $sql = "SELECT s.student_uid, s.student_name, g.grade_name, sec.section_name,
                       sec.id AS section_id, e.academic_year_id
                FROM {$wpdb->prefix}olama_students s
                JOIN {$wpdb->prefix}olama_student_enrollment e   ON s.student_uid = e.student_uid
                JOIN {$wpdb->prefix}olama_sections           sec ON e.section_id  = sec.id
                JOIN {$wpdb->prefix}olama_grades             g   ON sec.grade_id  = g.id
                WHERE e.academic_year_id = %d
                  AND g.id = %d
                  AND e.status = 'active'";

        $params = array( $year_id, $grade_id );

        if ( $section_id > 0 ) {
            $sql     .= ' AND sec.id = %d';
            $params[] = $section_id;
        }

        $sql .= ' ORDER BY s.student_name ASC';

        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
        return $rows ?: array();
    }
}
