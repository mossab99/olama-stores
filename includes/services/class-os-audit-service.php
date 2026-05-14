<?php
/**
 * OS_Audit_Service — writes immutable audit entries to os_audit_log.
 * Uses LONGTEXT for old_values / new_values (Correction #5: not JSON column type).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_Audit_Service {

    /**
     * Write an audit log entry.
     *
     * @param string $table     Short table name (without WP prefix), e.g. 'os_items'.
     * @param int    $record_id Primary key of the record acted upon.
     * @param string $action    'create' | 'update' | 'delete' | 'view'.
     * @param mixed  $old       Previous values (array/object or null).
     * @param mixed  $new       New values (array/object or null).
     */
    public static function log( $table, $record_id, $action, $old = null, $new = null ) {
        global $wpdb;

        // Correction #5: stored as LONGTEXT via json_encode (not native JSON column).
        $wpdb->insert(
            "{$wpdb->prefix}os_audit_log",
            array(
                'table_name' => sanitize_key( $table ),
                'record_id'  => (int) $record_id,
                'action'     => sanitize_key( $action ),
                'old_values' => $old !== null ? wp_json_encode( $old ) : null,
                'new_values' => $new !== null ? wp_json_encode( $new ) : null,
                'user_id'    => get_current_user_id(),
                'ip_address' => self::get_ip(),
                'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 300 ) : '',
                'created_at' => current_time( 'mysql', 1 ),
            ),
            array( '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
        );

        if ( $wpdb->last_error ) {
            error_log( '[Olama Stores Audit] DB Error: ' . $wpdb->last_error );
        }
    }

    /** @return string */
    private static function get_ip() {
        foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
                return substr( explode( ',', $ip )[0], 0, 45 );
            }
        }
        return '';
    }

    /**
     * Get audit entries for a specific table/record.
     *
     * @param  string $table
     * @param  int    $record_id
     * @return array
     */
    public static function get_history( $table, $record_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}os_audit_log WHERE table_name = %s AND record_id = %d ORDER BY created_at DESC",
            $table,
            $record_id
        ) );
    }

    /**
     * Get recent audit entries.
     *
     * @param  int $limit
     * @return array
     */
    public static function get_recent( $limit = 50 ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT a.*, u.display_name
             FROM {$wpdb->prefix}os_audit_log a
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
             ORDER BY a.created_at DESC LIMIT %d",
            max( 1, (int) $limit )
        ) );
    }
}
