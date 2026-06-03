<?php
/**
 * OS_Uniform_Size — Model class for wp_os_student_uniform_sizes.
 *
 * Integrates with Olama School: stores grade_id, section_id, academic_year_id
 * as proper FK integers alongside human-readable names for reporting.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_Uniform_Size {

    /**
     * Save (insert or update) a student's uniform size record.
     *
     * @param array $data {
     *   student_uid      string  required
     *   academic_year    string  required (e.g. "2025-2026")
     *   academic_year_id int     optional FK to olama_academic_years
     *   grade            string  required (grade_name from DB, e.g. "الصف الأول")
     *   grade_id         int     optional FK to olama_grades
     *   section          string  optional (section_name)
     *   section_id       int     optional FK to olama_sections
     *   uniform_size     int     required
     *   polo_size        int|null
     *   hoodie_size      int|null
     *   pants_size       int|null
     *   notes            string
     *   measured_by      int
     * }
     * @return int|WP_Error
     */
    public static function save( array $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'os_student_uniform_sizes';

        $student_uid      = sanitize_text_field( $data['student_uid']      ?? '' );
        $academic_year    = sanitize_text_field( $data['academic_year']    ?? '' );
        $academic_year_id = isset( $data['academic_year_id'] ) ? (int) $data['academic_year_id'] : null;
        $grade            = sanitize_text_field( $data['grade']            ?? '' );
        $grade_id         = isset( $data['grade_id'] ) ? (int) $data['grade_id'] : null;
        $section          = sanitize_text_field( $data['section']          ?? '' );
        $section_id       = ( isset( $data['section_id'] ) && $data['section_id'] ) ? (int) $data['section_id'] : null;
        $uniform_size     = (int) ( $data['uniform_size'] ?? 0 );
        $polo_size        = isset( $data['polo_size'] )   ? (int) $data['polo_size']   : null;
        $hoodie_size      = isset( $data['hoodie_size'] ) ? (int) $data['hoodie_size'] : null;
        $pants_size       = isset( $data['pants_size'] )  ? (int) $data['pants_size']  : null;
        $notes            = sanitize_textarea_field( $data['notes']        ?? '' );
        $measured_by      = isset( $data['measured_by'] ) ? (int) $data['measured_by'] : get_current_user_id();

        if ( ! $student_uid || ! $academic_year || ! $grade || ! $uniform_size ) {
            return new WP_Error( 'missing_fields', 'Required: student_uid, academic_year, grade, uniform_size.' );
        }

        // Check for existing record
        $existing_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE student_uid = %s AND academic_year = %s",
            $student_uid, $academic_year
        ) );

        $row_data = array(
            'uniform_size'     => $uniform_size,
            'polo_size'        => $polo_size,
            'hoodie_size'      => $hoodie_size,
            'pants_size'       => $pants_size,
            'grade'            => $grade,
            'grade_id'         => $grade_id,
            'section'          => $section,
            'section_id'       => $section_id,
            'academic_year_id' => $academic_year_id,
            'notes'            => $notes,
            'measured_by'      => $measured_by,
            'measured_at'      => current_time( 'mysql' ),
        );

        if ( $existing_id ) {
            $result = $wpdb->update( $table, $row_data, array( 'id' => (int) $existing_id ) );
            if ( false === $result ) {
                return new WP_Error( 'db_error', $wpdb->last_error );
            }
            return (int) $existing_id;
        } else {
            $row_data['student_uid']   = $student_uid;
            $row_data['academic_year'] = $academic_year;
            $result = $wpdb->insert( $table, $row_data );
            if ( false === $result ) {
                return new WP_Error( 'db_error', $wpdb->last_error );
            }
            return (int) $wpdb->insert_id;
        }
    }

    /**
     * Get all sized students for a given grade/section/year (by string grade name).
     * Legacy method kept for backward compat.
     */
    public static function get_by_grade( string $academic_year, string $grade, string $section = '' ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'os_student_uniform_sizes';
        $where = $wpdb->prepare( 'academic_year = %s AND grade = %s', $academic_year, $grade );
        if ( $section !== '' ) {
            $where .= $wpdb->prepare( ' AND section = %s', $section );
        }
        $rows    = $wpdb->get_results( "SELECT * FROM {$table} WHERE {$where}", ARRAY_A );
        $indexed = array();
        foreach ( $rows as $row ) { $indexed[ $row['student_uid'] ] = $row; }
        return $indexed;
    }

    /**
     * Get all sized students keyed by grade_id and optionally section_id.
     *
     * @param string $academic_year
     * @param int    $grade_id
     * @param int    $section_id   0 = all sections
     * @return array  [ student_uid => row ]
     */
    public static function get_by_grade_id( string $academic_year, int $grade_id, int $section_id = 0 ): array {
        global $wpdb;
        $table  = $wpdb->prefix . 'os_student_uniform_sizes';
        $sql    = "SELECT * FROM {$table} WHERE academic_year = %s AND grade_id = %d";
        $params = array( $academic_year, $grade_id );
        if ( $section_id > 0 ) {
            $sql     .= ' AND section_id = %d';
            $params[] = $section_id;
        }
        $rows    = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
        $indexed = array();
        foreach ( $rows as $row ) { $indexed[ $row['student_uid'] ] = $row; }
        return $indexed;
    }

    /**
     * Get size totals by grade_id.
     */
    public static function get_size_totals_by_grade_id( string $academic_year, int $grade_id ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'os_student_uniform_sizes';
        $rows  = $wpdb->get_results( $wpdb->prepare(
            "SELECT uniform_size, COUNT(*) as cnt FROM {$table}
             WHERE academic_year = %s AND grade_id = %d
             GROUP BY uniform_size ORDER BY uniform_size ASC",
            $academic_year, $grade_id
        ), ARRAY_A );
        $totals = array();
        foreach ( $rows as $row ) { $totals[ (int) $row['uniform_size'] ] = (int) $row['cnt']; }
        return $totals;
    }

    /**
     * Get size totals for a year (all grades or filtered by name).
     */
    public static function get_size_totals( string $academic_year, string $grade = '' ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'os_student_uniform_sizes';
        if ( $grade ) {
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT uniform_size, COUNT(*) as cnt FROM {$table}
                 WHERE academic_year = %s AND grade = %s
                 GROUP BY uniform_size ORDER BY uniform_size ASC",
                $academic_year, $grade
            ), ARRAY_A );
        } else {
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT uniform_size, COUNT(*) as cnt FROM {$table}
                 WHERE academic_year = %s
                 GROUP BY uniform_size ORDER BY uniform_size ASC",
                $academic_year
            ), ARRAY_A );
        }
        $totals = array();
        foreach ( $rows as $row ) { $totals[ (int) $row['uniform_size'] ] = (int) $row['cnt']; }
        return $totals;
    }

    /**
     * Delete a size record by student_uid + academic_year.
     */
    public static function delete( string $student_uid, string $academic_year ): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'os_student_uniform_sizes';
        return (bool) $wpdb->delete(
            $table,
            array( 'student_uid' => $student_uid, 'academic_year' => $academic_year ),
            array( '%s', '%s' )
        );
    }

    /**
     * Return allowed sizes for a grade_name from the Olama School DB.
     *
     * @param string $grade_name  e.g. "الصف الأول" or "Grade 1"
     * @return int[]
     */
    public static function get_allowed_sizes_for_grade_name( string $grade_name ): array {
        $key = self::grade_name_to_key( $grade_name );
        return self::get_allowed_sizes_for_grade( $key );
    }

    /**
     * Return allowed sizes for a grade key.
     * Checks the saved distribution config first, falls back to defaults.
     *
     * @param string $grade  e.g. "KG1", "G1"
     * @return int[]
     */
    public static function get_allowed_sizes_for_grade( string $grade ): array {
        $saved = get_option( 'os_estimation_distribution', array() );
        
        // Normalize saved keys to uppercase (sanitize_key might have made them lowercase)
        $saved_upper = array();
        if ( is_array( $saved ) ) {
            foreach ( $saved as $k => $v ) {
                $saved_upper[ strtoupper( $k ) ] = $v;
            }
        }

        if ( ! empty( $saved_upper ) && isset( $saved_upper[ strtoupper($grade) ] ) ) {
            $sizes = array_keys( $saved_upper[ strtoupper($grade) ] );
            if ( ! empty( $sizes ) ) {
                return array_map( 'intval', $sizes );
            }
        }

        // Fallback to hardcoded defaults
        $map = array(
            'KG1'    => array( 22, 24 ),
            'KG2'    => array( 24, 26, 28 ),
            'G1'     => array( 28, 30 ),
            'G2'     => array( 30, 32 ),
            'G3'     => array( 32, 34 ),
            'G4'     => array( 36, 38 ),
            'G5'     => array( 36, 38 ),
            'G6'     => array( 40, 42 ),
            'G7'     => array( 40, 42 ),
            'G8'     => array( 40, 42, 44 ),
            'G9'     => array( 42, 44 ),
            'G10_12' => array( 44, 46, 48, 50, 52, 54 ),
        );
        return $map[ $grade ] ?? array( 28, 30, 32, 34, 36, 38, 40, 42, 44 );
    }

    /**
     * Convert grade_name to a grade key (KG1, G1, etc.).
     * Uses grade_level from DB if available for accurate mapping.
     */
    public static function grade_name_to_key( string $grade_name ): string {
        $name = trim( $grade_name );

        // If already a key
        if ( preg_match( '/^(KG\d+|G\d+(_\d+)?)$/', $name ) ) { return $name; }

        // Try DB lookup for level
        if ( class_exists( 'Olama_School_DB' ) ) {
            global $wpdb;
            $level = $wpdb->get_var( $wpdb->prepare(
                "SELECT grade_level FROM {$wpdb->prefix}olama_grades WHERE grade_name = %s LIMIT 1",
                $grade_name
            ) );
            if ( $level !== null ) {
                $level = (int) $level;
                if ( $level === -2 ) return 'KG1';
                if ( $level === -1 ) return 'KG2';
                if ( $level >= 1 && $level <= 9 ) return 'G' . $level;
                if ( $level >= 10 && $level <= 12 ) return 'G10_12';
            }
        }

        // Fallback pattern matching
        if ( preg_match( '/^Grade\s*(\d+)$/i', $name, $m ) )   { return 'G' . $m[1]; }
        if ( preg_match( '/^KG\s*(\d+)$/i', $name, $m ) )      { return 'KG' . $m[1]; }
        if ( preg_match( '/G10/i', $name ) )                    { return 'G10_12'; }

        return sanitize_key( str_replace( array( ' ', '/' ), array( '', '_' ), $name ) );
    }

    /** Get completion stats for a grade/section/year. */
    public static function get_completion( string $academic_year, string $grade, string $section, int $total_students ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'os_student_uniform_sizes';
        $where = $wpdb->prepare( 'academic_year = %s AND grade = %s', $academic_year, $grade );
        if ( $section ) { $where .= $wpdb->prepare( ' AND section = %s', $section ); }
        $sized = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$where}" );
        $pct   = $total_students > 0 ? round( ( $sized / $total_students ) * 100, 1 ) : 0;
        return array( 'sized' => $sized, 'total' => $total_students, 'percentage' => $pct );
    }

    /** Get sized UIDs for grade (estimation engine). */
    public static function get_sized_uids_for_grade( string $academic_year, string $grade_key ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'os_student_uniform_sizes';
        $rows  = $wpdb->get_results( $wpdb->prepare(
            "SELECT student_uid, uniform_size FROM {$table} WHERE academic_year = %s AND grade = %s",
            $academic_year, $grade_key
        ), ARRAY_A );
        $result = array();
        foreach ( $rows as $row ) { $result[ $row['student_uid'] ] = (int) $row['uniform_size']; }
        return $result;
    }

    /** Alias for estimation engine. */
    public static function get_actual_size_totals( string $academic_year, string $grade_key = '' ): array {
        return self::get_size_totals( $academic_year, $grade_key );
    }

    /**
     * Get uniform size record for a student by UID and year ID.
     */
    public static function get_by_student_uid( string $student_uid, int $academic_year_id ): ?array {
        global $wpdb;
        $table = $wpdb->prefix . 'os_student_uniform_sizes';
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE student_uid = %s AND academic_year_id = %d LIMIT 1",
            $student_uid, $academic_year_id
        ), ARRAY_A );
        return $row ?: null;
    }
}
