<?php
/**
 * Per-student payment clearance for custom withdrawals.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_Custom_Withdrawal_Approval {

    public static function is_approved( $student_uid, $academic_year_id ) {
        global $wpdb;
        return (bool) $wpdb->get_var( $wpdb->prepare(
            "SELECT is_approved
             FROM {$wpdb->prefix}os_custom_withdrawal_approvals
             WHERE student_uid = %s AND academic_year_id = %d
             LIMIT 1",
            sanitize_text_field( (string) $student_uid ),
            (int) $academic_year_id
        ) );
    }

    public static function decorate_students( $students, $academic_year_id ) {
        global $wpdb;
        if ( ! $students ) {
            return array();
        }

        $uids = array_values( array_unique( array_filter( array_map( static function ( $student ) {
            return sanitize_text_field( (string) $student->student_uid );
        }, $students ) ) ) );

        if ( ! $uids ) {
            return $students;
        }

        $placeholders = implode( ',', array_fill( 0, count( $uids ), '%s' ) );
        $params       = array_merge( array( (int) $academic_year_id ), $uids );
        $approved     = $wpdb->get_col( $wpdb->prepare(
            "SELECT student_uid
             FROM {$wpdb->prefix}os_custom_withdrawal_approvals
             WHERE academic_year_id = %d
               AND is_approved = 1
               AND student_uid IN ({$placeholders})",
            $params
        ) );
        $approved_map = array_fill_keys( $approved ?: array(), true );

        foreach ( $students as $student ) {
            $student->custom_withdrawal_allowed = isset( $approved_map[ (string) $student->student_uid ] );
        }
        return $students;
    }

    public static function save_family( $family_uid, $academic_year_id, $students, $approved_uids ) {
        global $wpdb;

        $family_uid       = sanitize_text_field( (string) $family_uid );
        $academic_year_id = (int) $academic_year_id;
        $approved_uids    = array_fill_keys( array_map( 'strval', $approved_uids ), true );
        $table            = $wpdb->prefix . 'os_custom_withdrawal_approvals';
        $user_id          = get_current_user_id();
        $now              = current_time( 'mysql', true );
        $changed          = 0;

        if ( ! $family_uid || ! $academic_year_id || ! $students ) {
            return new WP_Error( 'invalid_approval_scope', __( 'Family, students, and academic year are required.', 'olama-stores' ) );
        }

        $wpdb->query( 'START TRANSACTION' );
        foreach ( $students as $student ) {
            $student_uid = sanitize_text_field( (string) $student->student_uid );
            $is_approved = isset( $approved_uids[ $student_uid ] ) ? 1 : 0;
            $existing    = $wpdb->get_row( $wpdb->prepare(
                "SELECT id, is_approved FROM {$table}
                 WHERE student_uid = %s AND academic_year_id = %d LIMIT 1",
                $student_uid,
                $academic_year_id
            ) );

            $payload = array(
                'family_uid'       => $family_uid,
                'is_approved'      => $is_approved,
                'approved_by'      => $is_approved ? $user_id : null,
                'approved_at'      => $is_approved ? $now : null,
                'updated_by'       => $user_id,
                'updated_at'       => $now,
            );

            if ( $existing ) {
                $result = $wpdb->update( $table, $payload, array( 'id' => (int) $existing->id ) );
                if ( false === $result ) {
                    $wpdb->query( 'ROLLBACK' );
                    return new WP_Error( 'approval_update_failed', __( 'Could not update custom withdrawal approval.', 'olama-stores' ) );
                }
                if ( (int) $existing->is_approved !== $is_approved ) {
                    $changed++;
                }
            } else {
                $payload['student_uid']      = $student_uid;
                $payload['academic_year_id'] = $academic_year_id;
                $result = $wpdb->insert( $table, $payload );
                if ( false === $result ) {
                    $wpdb->query( 'ROLLBACK' );
                    return new WP_Error( 'approval_insert_failed', __( 'Could not save custom withdrawal approval.', 'olama-stores' ) );
                }
                if ( $is_approved ) {
                    $changed++;
                }
            }
        }
        $wpdb->query( 'COMMIT' );

        OS_Audit_Service::log( 'os_custom_withdrawal_approvals', 0, 'update', null, array(
            'family_uid'       => $family_uid,
            'academic_year_id' => $academic_year_id,
            'approved_students'=> array_keys( $approved_uids ),
        ) );

        return array(
            'changed'        => $changed,
            'approved_count' => count( $approved_uids ),
        );
    }
}
