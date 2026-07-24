<?php
/**
 * Core-first data integration for Olama Stores.
 *
 * Olama Core owns family, student, student-year, employee, and academic snapshot
 * data. The legacy Olama School readers remain as fallbacks for installations
 * that have not completed their Core migration yet.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_School_Integration {

    public static function is_core_available() {
        return function_exists( 'olama_core' ) && class_exists( 'Olama_Core_Container' );
    }

    public static function get_employees() {
        if ( self::is_core_available() ) {
            global $wpdb;
            $table = $wpdb->prefix . 'olama_core_employees';
            $rows  = $wpdb->get_results(
                "SELECT * FROM {$table} ORDER BY CAST(employee_id AS UNSIGNED), full_name ASC"
            );

            return array_map( static function ( $employee ) {
                // Keep the response shape consumed by the existing custody UI.
                $employee->ID           = (string) $employee->employee_id;
                $employee->display_name = (string) $employee->full_name;
                $employee->user_email   = '';
                $employee->phone_number = (string) ( $employee->phones ?? '' );
                $employee->data_source  = 'olama_core';
                return $employee;
            }, $rows ?: array() );
        }

        if ( class_exists( 'Olama_School_Teacher' ) ) {
            return Olama_School_Teacher::get_teachers();
        }

        return self::get_local_employees();
    }

    public static function get_employee( $employee_id ) {
        if ( self::is_core_available() ) {
            $row = olama_core()->employees()->get_by_employee_id( (string) $employee_id );
            if ( ! $row ) {
                return null;
            }
            $employee               = (object) $row;
            $employee->ID           = (string) $employee->employee_id;
            $employee->display_name = (string) $employee->full_name;
            $employee->user_email   = '';
            $employee->phone_number = (string) ( $employee->phones ?? '' );
            $employee->data_source  = 'olama_core';
            return $employee;
        }

        foreach ( self::get_employees() as $employee ) {
            if ( (string) $employee->ID === (string) $employee_id ) {
                return $employee;
            }
        }
        return null;
    }

    private static function get_local_employees() {
        $users = get_users( array(
            'role__in' => array( 'administrator', 'editor', 'supervisor', 'teacher', 'assistant', 'author' ),
            'fields'   => array( 'ID', 'display_name', 'user_email' ),
        ) );
        return array_map( static function ( $user ) {
            $user->employee_id  = (string) $user->ID;
            $user->phone_number = '';
            $user->data_source  = 'wordpress';
            return $user;
        }, $users );
    }

    public static function get_families( $args = array() ) {
        if ( ! self::is_core_available() ) {
            return array();
        }

        global $wpdb;
        $table         = $wpdb->prefix . 'olama_core_families';
        $student_years = $wpdb->prefix . 'olama_core_student_years';
        $limit         = max( 1, min( 1000, (int) ( $args['limit'] ?? 100 ) ) );
        $offset        = max( 0, (int) ( $args['offset'] ?? 0 ) );
        $term          = sanitize_text_field( $args['search'] ?? '' );
        $study_year    = self::resolve_study_year( $args['academic_year_id'] ?? ( $args['study_year'] ?? '' ) );
        $where         = array();
        $params = array();

        if ( '' !== $study_year ) {
            $where[] = "EXISTS (
                SELECT 1 FROM {$student_years} sy
                WHERE sy.family_uid = f.family_uid
                  AND sy.study_year = %s
            )";
            $params[] = $study_year;
        }

        if ( '' !== $term ) {
            $like   = '%' . $wpdb->esc_like( $term ) . '%';
            $where[] = '(f.family_uid LIKE %s OR f.oracle_family_id LIKE %s OR f.sponsor_full_name LIKE %s
                        OR f.father_name LIKE %s OR f.mother_name LIKE %s OR f.father_mobile LIKE %s OR f.mother_mobile LIKE %s)';
            $params = array_merge( $params, array_fill( 0, 7, $like ) );
        }

        $where_sql = $where ? ' WHERE ' . implode( ' AND ', $where ) : '';
        $sql      = "SELECT f.* FROM {$table} f{$where_sql}
                     ORDER BY CAST(f.oracle_family_id AS UNSIGNED), f.oracle_family_id
                     LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;

        return $wpdb->get_results( $wpdb->prepare( $sql, $params ) ) ?: array();
    }

    public static function get_family( $identifier ) {
        if ( ! self::is_core_available() ) {
            return null;
        }

        $identifier = sanitize_text_field( (string) $identifier );
        $family = olama_core()->families()->get_by_uid( $identifier );
        if ( ! $family ) {
            $family = olama_core()->families()->get_by_oracle_id( $identifier );
        }
        return $family ? (object) $family : null;
    }

    public static function get_students( $args = array() ) {
        if ( self::is_core_available() ) {
            return self::get_core_students( $args );
        }

        if ( class_exists( 'Olama_School_Student' ) ) {
            return Olama_School_Student::get_students( $args );
        }
        return array();
    }

    private static function get_core_students( $args ) {
        global $wpdb;

        $students = $wpdb->prefix . 'olama_core_students';
        $years    = $wpdb->prefix . 'olama_core_student_years';
        $where    = array( '1=1' );
        $params   = array();
        $study_year = self::resolve_study_year( $args['academic_year_id'] ?? ( $args['study_year'] ?? '' ) );

        $year_join = "LEFT JOIN {$years} sy ON sy.id = (
            SELECT sy2.id FROM {$years} sy2
            WHERE sy2.student_uid = s.student_uid";
        if ( '' !== $study_year ) {
            $year_join .= ' AND sy2.study_year = %s';
            $params[] = $study_year;
            $where[] = 'sy.id IS NOT NULL';
        }
        $year_join .= ' ORDER BY sy2.study_year DESC, sy2.id DESC LIMIT 1)';

        if ( ! empty( $args['section_id'] ) ) {
            $where[]  = 'sy.section_id = %s';
            $params[] = sanitize_text_field( (string) $args['section_id'] );
        }
        if ( ! empty( $args['grade_id'] ) ) {
            $where[]  = 'sy.class_id = %s';
            $params[] = sanitize_text_field( (string) $args['grade_id'] );
        }
        if ( ! empty( $args['family_uid'] ) ) {
            $where[]  = 's.family_uid = %s';
            $params[] = sanitize_text_field( (string) $args['family_uid'] );
        }
        if ( ! empty( $args['student_uid'] ) ) {
            $where[]  = 's.student_uid = %s';
            $params[] = sanitize_text_field( (string) $args['student_uid'] );
        }

        $sql = "SELECT s.*,
                       COALESCE(NULLIF(s.family_uid, ''), sy.family_uid) AS family_uid,
                       COALESCE(NULLIF(s.oracle_family_id, ''), sy.oracle_family_id) AS oracle_family_id,
                       COALESCE(NULLIF(s.oracle_family_id, ''), sy.oracle_family_id) AS family_id,
                       sy.study_year, sy.school_id, sy.school_name,
                       sy.class_id AS grade_id, sy.class_name AS grade_name,
                       sy.section_id, sy.section_name,
                       sy.student_status AS enrollment_status,
                       sy.student_status_name AS enrollment_status_name
                FROM {$students} s
                {$year_join}
                WHERE " . implode( ' AND ', $where ) . '
                ORDER BY s.student_name ASC';

        $rows = $params
            ? $wpdb->get_results( $wpdb->prepare( $sql, $params ) )
            : $wpdb->get_results( $sql );

        return self::tag_source( $rows ?: array(), 'olama_core' );
    }

    public static function get_student_by_uid( $student_uid, $academic_year_id = 0 ) {
        $students = self::get_students( array(
            'academic_year_id' => $academic_year_id,
            'student_uid'      => $student_uid,
        ) );
        return $students ? reset( $students ) : null;
    }

    public static function get_students_by_family( $family_identifier, $academic_year_id = 0 ) {
        $family_identifier = sanitize_text_field( (string) $family_identifier );
        if ( '' === $family_identifier ) {
            return array();
        }

        if ( self::is_core_available() ) {
            global $wpdb;
            $families = $wpdb->prefix . 'olama_core_families';
            $exact    = self::get_family( $family_identifier );
            if ( $exact ) {
                $family_uids = array( $exact->family_uid );
            } else {
                $term = '%' . $wpdb->esc_like( $family_identifier ) . '%';
                $family_uids = $wpdb->get_col( $wpdb->prepare(
                    "SELECT family_uid FROM {$families}
                     WHERE family_uid LIKE %s OR oracle_family_id LIKE %s OR sponsor_full_name LIKE %s
                        OR father_name LIKE %s OR mother_name LIKE %s OR father_mobile LIKE %s OR mother_mobile LIKE %s",
                    $term, $term, $term, $term, $term, $term, $term
                ) );
            }

            if ( ! $family_uids ) {
                return array();
            }

            $all = array();
            foreach ( $family_uids as $family_uid ) {
                $all = array_merge( $all, self::get_students( array(
                    'family_uid'       => $family_uid,
                    'academic_year_id' => $academic_year_id,
                ) ) );
            }
            return $all;
        }

        if ( ! class_exists( 'Olama_School_DB' ) ) {
            return array();
        }

        global $wpdb;
        $query = "SELECT s.*, sec.section_name, g.grade_name, g.id AS grade_id
                  FROM {$wpdb->prefix}olama_students s
                  JOIN {$wpdb->prefix}olama_student_enrollment e ON s.student_uid = e.student_uid
                  JOIN {$wpdb->prefix}olama_sections sec ON e.section_id = sec.id
                  JOIN {$wpdb->prefix}olama_grades g ON sec.grade_id = g.id
                  LEFT JOIN {$wpdb->prefix}olama_families f ON s.family_id = f.family_uid
                  WHERE (s.family_id LIKE %s OR f.family_name LIKE %s OR f.family_uid LIKE %s)";
        $term   = '%' . $wpdb->esc_like( $family_identifier ) . '%';
        $params = array( $term, $term, $term );
        if ( $academic_year_id ) {
            $query   .= ' AND e.academic_year_id = %d';
            $params[] = $academic_year_id;
        }
        $query .= ' ORDER BY s.student_name ASC';
        return $wpdb->get_results( $wpdb->prepare( $query, $params ) ) ?: array();
    }

    public static function get_active_year() {
        if ( self::is_core_available() ) {
            $study_year = (string) olama_core()->academic()->latest_study_year();
            if ( '' === $study_year ) {
                $years = self::get_all_years();
                $study_year = $years ? (string) $years[0]->year_name : '';
            }
            if ( '' !== $study_year ) {
                return (object) array(
                    'id'         => self::study_year_to_id( $study_year ),
                    'year_name'  => $study_year,
                    'study_year' => $study_year,
                    'data_source'=> 'olama_core',
                );
            }
        }
        return class_exists( 'Olama_School_Academic' )
            ? Olama_School_Academic::get_active_year()
            : null;
    }

    public static function get_all_years() {
        if ( self::is_core_available() ) {
            global $wpdb;
            $table = $wpdb->prefix . 'olama_core_student_years';
            $years = $wpdb->get_col( "SELECT DISTINCT study_year FROM {$table} WHERE study_year <> '' ORDER BY study_year DESC" );
            return array_map( static function ( $year ) {
                return (object) array(
                    'id'         => self::study_year_to_id( $year ),
                    'year_name'  => $year,
                    'study_year' => $year,
                    'data_source'=> 'olama_core',
                );
            }, $years ?: array() );
        }
        return class_exists( 'Olama_School_Academic' )
            ? Olama_School_Academic::get_years()
            : array();
    }

    public static function activate_year( $year_id ) {
        if ( self::is_core_available() ) {
            return false;
        }
        return class_exists( 'Olama_School_Academic' )
            ? (bool) Olama_School_Academic::activate_year( $year_id )
            : false;
    }

    public static function get_grades() {
        if ( self::is_core_available() ) {
            $rows = olama_core()->academic()->grades();
            return array_map( static function ( $row ) {
                return (object) array_merge( $row, array(
                    'id'          => $row['grade_id'],
                    'grade_level' => is_numeric( $row['grade_id'] ) ? (int) $row['grade_id'] : 0,
                    'data_source' => 'olama_core',
                ) );
            }, $rows ?: array() );
        }

        global $wpdb;
        return $wpdb->get_results(
            "SELECT id, grade_name, grade_level FROM {$wpdb->prefix}olama_grades WHERE is_active = 1 ORDER BY grade_level ASC"
        ) ?: array();
    }

    public static function get_sections( $academic_year_id = 0 ) {
        if ( self::is_core_available() ) {
            $study_year = self::resolve_study_year( $academic_year_id );
            if ( '' === $study_year ) {
                return array();
            }
            return array_map( static function ( $row ) {
                return (object) array_merge( $row, array(
                    'id'          => $row['section_id'],
                    'data_source' => 'olama_core',
                ) );
            }, olama_core()->academic()->grade_sections( $study_year ) ?: array() );
        }

        global $wpdb;
        $sql = "SELECT sec.id, sec.grade_id, sec.section_name, g.grade_name
                FROM {$wpdb->prefix}olama_sections sec
                JOIN {$wpdb->prefix}olama_grades g ON sec.grade_id = g.id";
        if ( $academic_year_id ) {
            $sql .= $wpdb->prepare( ' WHERE sec.academic_year_id = %d', $academic_year_id );
        }
        $sql .= ' ORDER BY g.grade_level, sec.section_name';
        return $wpdb->get_results( $sql ) ?: array();
    }

    public static function get_assignee_label( $type, $id ) {
        if ( 'employee' === $type ) {
            $employee = self::get_employee( $id );
            if ( $employee ) {
                return (string) $employee->display_name;
            }
            // Historical assignments may still contain a WordPress user ID.
            $user = get_userdata( (int) $id );
            return $user ? $user->display_name : "#{$id}";
        }
        if ( 'student' === $type ) {
            $student = self::get_student_by_uid( $id );
            return $student ? $student->student_name : $id;
        }
        return $id;
    }

    public static function resolve_study_year( $year = '' ) {
        if ( is_string( $year ) && preg_match( '/^\d{4}\D+\d{4}$/', $year ) ) {
            return sanitize_text_field( $year );
        }

        if ( $year && class_exists( 'Olama_School_Academic' ) ) {
            global $wpdb;
            $legacy = $wpdb->get_var( $wpdb->prepare(
                "SELECT year_name FROM {$wpdb->prefix}olama_academic_years WHERE id = %d",
                (int) $year
            ) );
            if ( $legacy ) {
                return (string) $legacy;
            }
        }

        if ( self::is_core_available() ) {
            $years = self::get_all_years();
            foreach ( $years as $item ) {
                if ( (int) $item->id === (int) $year ) {
                    return (string) $item->year_name;
                }
            }
            if ( $years ) {
                return (string) $years[0]->year_name;
            }
            return (string) olama_core()->academic()->latest_study_year();
        }
        return '';
    }

    private static function study_year_to_id( $study_year ) {
        if ( class_exists( 'Olama_School_Academic' ) ) {
            global $wpdb;
            $legacy_id = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}olama_academic_years WHERE year_name = %s LIMIT 1",
                (string) $study_year
            ) );
            if ( $legacy_id ) {
                return (int) $legacy_id;
            }
        }

        $digits = preg_replace( '/\D+/', '', (string) $study_year );
        return $digits ? (int) substr( $digits, 0, 9 ) : abs( crc32( (string) $study_year ) );
    }

    private static function tag_source( $rows, $source ) {
        foreach ( $rows as $row ) {
            $row->data_source = $source;
        }
        return $rows;
    }
}
