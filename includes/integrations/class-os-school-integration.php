<?php
/**
 * OS_School_Integration — abstraction layer between Olama Stores and Olama School.
 *
 * Correction #3/#6: Uses actual static class methods (Olama_School_Teacher::get_teachers(),
 * Olama_School_Student::get_students(), Olama_School_Academic::*) instead of the
 * non-existent global functions (olama_get_employees(), olama_get_students(), etc.)
 * assumed in the spec.
 *
 * When Olama School is absent, falls back to local/manual data sources.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_School_Integration {

    // ── Employees ─────────────────────────────────────────────────────────────

    /**
     * Get all employees (WP users with school staff roles).
     * Returns array of objects with: ID, display_name, user_email, employee_id, phone_number.
     *
     * Correction #3: Calls Olama_School_Teacher::get_teachers() static method,
     * NOT the assumed olama_get_employees() global function.
     *
     * @return array
     */
    public static function get_employees() {
        if ( class_exists( 'Olama_School_Teacher' ) ) {
            return Olama_School_Teacher::get_teachers();
        }
        return self::get_local_employees();
    }

    /**
     * Get a single employee by WP user ID.
     *
     * @param  int $user_id  WP user ID.
     * @return object|null
     */
    public static function get_employee( $user_id ) {
        $employees = self::get_employees();
        foreach ( $employees as $emp ) {
            if ( (int) $emp->ID === (int) $user_id ) {
                return $emp;
            }
        }
        return null;
    }

    /**
     * Fallback: build a minimal employee list from WP users with relevant roles.
     * Used when Olama School is not active.
     */
    private static function get_local_employees() {
        $users = get_users( array(
            'role__in' => array( 'administrator', 'editor', 'supervisor', 'teacher', 'assistant', 'author' ),
            'fields'   => array( 'ID', 'display_name', 'user_email' ),
        ) );
        return array_map( function ( $u ) {
            $u->employee_id  = '';
            $u->phone_number = '';
            return $u;
        }, $users );
    }

    // ── Students ──────────────────────────────────────────────────────────────

    /**
     * Get students, optionally filtered by academic year and/or section.
     *
     * Correction #3: Calls Olama_School_Student::get_students($args) static method,
     * NOT the assumed olama_get_students() global function.
     *
     * Correction #2: Students are identified by student_uid (VARCHAR), not integer ID.
     *
     * @param  array $args  Optional: academic_year_id (INT), section_id (INT).
     * @return array        Objects including student_uid, student_name, grade_name, section_name.
     */
    public static function get_students( $args = array() ) {
        if ( class_exists( 'Olama_School_Student' ) ) {
            // Correction #1: pass academic_year_id as INT (not VARCHAR label)
            return Olama_School_Student::get_students( $args );
        }
        return array();
    }

    /**
     * Get a single student by student_uid.
     *
     * Correction #2: students are keyed by student_uid (VARCHAR), not auto-increment ID.
     *
     * @param  string $student_uid
     * @return object|null
     */
    public static function get_student_by_uid( $student_uid ) {
        if ( class_exists( 'Olama_School_Student' ) ) {
            global $wpdb;
            return $wpdb->get_row( $wpdb->prepare(
                "SELECT s.*, se.section_id, g.grade_name, sec.section_name
                 FROM {$wpdb->prefix}olama_students s
                 LEFT JOIN {$wpdb->prefix}olama_student_enrollment se ON s.student_uid = se.student_uid
                 LEFT JOIN {$wpdb->prefix}olama_sections sec ON se.section_id = sec.id
                 LEFT JOIN {$wpdb->prefix}olama_grades g ON sec.grade_id = g.id
                 WHERE s.student_uid = %s
                 ORDER BY se.id DESC
                 LIMIT 1",
                $student_uid
            ) );
        }
        return null;
    }

    // ── Academic Years ────────────────────────────────────────────────────────

    /**
     * Get the active academic year object.
     *
     * Correction #1: Returns the full object including ->id (INT) and ->year_name.
     * Olama Stores must store academic_year_id (INT FK), not a VARCHAR label.
     *
     * @return object|null
     */
    public static function get_active_year() {
        if ( class_exists( 'Olama_School_Academic' ) ) {
            return Olama_School_Academic::get_active_year();
        }
        return null;
    }

    /**
     * Get all academic years.
     *
     * @return array
     */
    public static function get_all_years() {
        if ( class_exists( 'Olama_School_Academic' ) ) {
            return Olama_School_Academic::get_years();
        }
        return array();
    }

    /**
     * Activate an academic year by ID.
     *
     * @param  int $year_id
     * @return bool
     */
    public static function activate_year( $year_id ) {
        if ( class_exists( 'Olama_School_Academic' ) ) {
            return (bool) Olama_School_Academic::activate_year( $year_id );
        }
        return false;
    }

    // ── Grades & Sections ─────────────────────────────────────────────────────

    /**
     * Get all grades.
     * Correction #6: direct DB query (no dedicated static accessor in School plugin).
     *
     * @return array
     */
    public static function get_grades() {
        global $wpdb;
        return $wpdb->get_results( "SELECT id, grade_name, grade_level FROM {$wpdb->prefix}olama_grades WHERE is_active = 1 ORDER BY grade_level ASC" );
    }

    /**
     * Get sections, optionally filtered by academic_year_id.
     *
     * Correction #1: filter by academic_year_id (INT), not a VARCHAR year label.
     *
     * @param  int $academic_year_id
     * @return array
     */
    public static function get_sections( $academic_year_id = 0 ) {
        global $wpdb;
        if ( $academic_year_id ) {
            return $wpdb->get_results( $wpdb->prepare(
                "SELECT sec.id, sec.section_name, g.grade_name
                 FROM {$wpdb->prefix}olama_sections sec
                 JOIN {$wpdb->prefix}olama_grades g ON sec.grade_id = g.id
                 WHERE sec.academic_year_id = %d
                 ORDER BY g.grade_level, sec.section_name",
                $academic_year_id
            ) );
        }
        return $wpdb->get_results(
            "SELECT sec.id, sec.section_name, g.grade_name
             FROM {$wpdb->prefix}olama_sections sec
             JOIN {$wpdb->prefix}olama_grades g ON sec.grade_id = g.id
             ORDER BY g.grade_level, sec.section_name"
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Resolve a display name for an assignee (employee or student).
     *
     * @param  string $type  'employee' | 'student'
     * @param  string $id    WP user ID (string) for employee, student_uid for student.
     * @return string
     */
    public static function get_assignee_label( $type, $id ) {
        if ( $type === 'employee' ) {
            $user = get_userdata( (int) $id );
            return $user ? $user->display_name : "#{$id}";
        }
        if ( $type === 'student' ) {
            $student = self::get_student_by_uid( $id );
            return $student ? $student->student_name : $id;
        }
        return $id;
    }
    /**
     * Get all students associated with a family number/UID.
     *
     * @param  string $family_uid
     * @param  int    $academic_year_id
     * @return array
     */
    public static function get_students_by_family( $family_uid, $academic_year_id = 0 ) {
        if ( ! class_exists( 'Olama_School_DB' ) ) { return array(); }
        global $wpdb;

        $query = "SELECT s.*, sec.section_name, g.grade_name
                 FROM {$wpdb->prefix}olama_students s
                 JOIN {$wpdb->prefix}olama_student_enrollment e ON s.student_uid = e.student_uid
                 JOIN {$wpdb->prefix}olama_sections sec ON e.section_id = sec.id
                 JOIN {$wpdb->prefix}olama_grades g ON sec.grade_id = g.id
                 LEFT JOIN {$wpdb->prefix}olama_families f ON s.family_id = f.family_uid
                 WHERE (s.family_id LIKE %s OR f.family_name LIKE %s OR f.family_uid LIKE %s)";

        $term = '%' . $wpdb->esc_like( $family_uid ) . '%';
        $params = array( $term, $term, $term );

        if ( $academic_year_id ) {
            $query .= " AND e.academic_year_id = %d";
            $params[] = $academic_year_id;
        }

        $query .= " ORDER BY s.student_name ASC";

        return $wpdb->get_results( $wpdb->prepare( $query, $params ) );
    }
}
