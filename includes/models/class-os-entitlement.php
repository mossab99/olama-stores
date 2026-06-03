<?php
/**
 * OS_Entitlement — Model class for Uniform Entitlements.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_Entitlement {

    /**
     * Get entitlements with model and grade names joined.
     *
     * @param array $args Optional filters: academic_year_id, grade_id, custom_model_id.
     * @return array
     */
    public static function get_list( $args = array() ) {
        global $wpdb;
        $p = $wpdb->prefix;
        $where = array();
        $params = array();

        if ( ! empty( $args['academic_year_id'] ) ) {
            $where[] = 'e.academic_year_id = %d';
            $params[] = (int) $args['academic_year_id'];
        }
        if ( ! empty( $args['grade_id'] ) ) {
            $where[] = 'e.grade_id = %d';
            $params[] = (int) $args['grade_id'];
        }
        if ( ! empty( $args['custom_model_id'] ) ) {
            $where[] = 'e.custom_model_id = %d';
            $params[] = (int) $args['custom_model_id'];
        }

        $where_sql = ! empty( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '';

        $sql = "SELECT e.*, m.name AS model_name, g.grade_name
                FROM {$p}os_entitlements e
                LEFT JOIN {$p}os_custom_models m ON e.custom_model_id = m.id
                LEFT JOIN {$p}olama_grades g ON e.grade_id = g.id
                $where_sql
                ORDER BY g.grade_level ASC, m.name ASC";

        if ( ! empty( $params ) ) {
            return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
        }
        return $wpdb->get_results( $sql );
    }

    /**
     * Create or update an entitlement.
     *
     * @param array $data academic_year_id, grade_id, custom_model_id, quantity.
     * @return int|WP_Error ID or error.
     */
    public static function create_or_update( $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'os_entitlements';
        $academic_year_id = (int) ($data['academic_year_id'] ?? 0);
        $grade_id         = (int) ($data['grade_id'] ?? 0);
        $custom_model_id  = (int) ($data['custom_model_id'] ?? 0);
        $quantity         = (int) ($data['quantity'] ?? 1);

        if ( ! $academic_year_id || ! $grade_id || ! $custom_model_id || $quantity <= 0 ) {
            return new WP_Error( 'invalid_data', __( 'Invalid entitlement data. Grade, Model, and Quantity are required.', 'olama-stores' ) );
        }

        $existing_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE academic_year_id = %d AND grade_id = %d AND custom_model_id = %d",
            $academic_year_id, $grade_id, $custom_model_id
        ) );

        $payload = array(
            'academic_year_id' => $academic_year_id,
            'grade_id'         => $grade_id,
            'custom_model_id'  => $custom_model_id,
            'quantity'         => $quantity,
        );

        if ( $existing_id ) {
            $result = $wpdb->update( $table, $payload, array( 'id' => (int) $existing_id ) );
            if ( false === $result ) {
                return new WP_Error( 'db_error', $wpdb->last_error );
            }
            OS_Audit_Service::log( 'os_entitlements', $existing_id, 'update', null, $payload );
            return (int) $existing_id;
        } else {
            $result = $wpdb->insert( $table, $payload );
            if ( false === $result ) {
                return new WP_Error( 'db_error', $wpdb->last_error );
            }
            $id = $wpdb->insert_id;
            OS_Audit_Service::log( 'os_entitlements', $id, 'create', null, $payload );
            return $id;
        }
    }

    /**
     * Delete an entitlement rule.
     *
     * @param int $id
     * @return bool
     */
    public static function delete( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'os_entitlements';
        $old = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
        if ( ! $old ) { return false; }
        $result = $wpdb->delete( $table, array( 'id' => $id ) );
        if ( $result ) {
            OS_Audit_Service::log( 'os_entitlements', $id, 'delete', $old, null );
            return true;
        }
        return false;
    }
}
