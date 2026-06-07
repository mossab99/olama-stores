<?php
/**
 * OS_Estimation_Ajax — AJAX handlers for Save/Delete estimation drafts.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_Estimation_Ajax {

    public static function register() {
        add_action( 'wp_ajax_os_save_estimation_draft',        array( __CLASS__, 'save_draft' ) );
        add_action( 'wp_ajax_os_delete_estimation_draft',      array( __CLASS__, 'delete_draft' ) );
        add_action( 'wp_ajax_os_save_estimation_distribution', array( __CLASS__, 'save_distribution' ) );
        add_action( 'wp_ajax_os_get_supplier_report_data',     array( __CLASS__, 'get_supplier_report_data' ) );
        add_action( 'wp_ajax_os_get_survey_categories',        array( __CLASS__, 'get_survey_categories' ) );
    }

    /**
     * Return all custom models with their include_in_survey flag.
     * Used by the estimation UI to build the model selector and filter calculations.
     */
    public static function get_survey_categories() {
        check_ajax_referer( 'os_order_estimation_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Insufficient permissions.' );

        global $wpdb;
        $models = $wpdb->get_results(
            "SELECT id, name, include_in_survey FROM {$wpdb->prefix}os_custom_models ORDER BY name ASC"
        );

        wp_send_json_success( $models );
    }

    public static function get_supplier_report_data() {
        check_ajax_referer( 'os_order_estimation_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Insufficient permissions.' );

        $year_id = (int) ( $_POST['year_id'] ?? 0 );
        global $wpdb;

        // ── 0. Determine qualifying custom models ───────────────────────────────
        // Get all custom models with include_in_survey flag
        $all_models = $wpdb->get_results(
            "SELECT id, name, include_in_survey, calculation_type FROM {$wpdb->prefix}os_custom_models ORDER BY name ASC"
        );

        // Build a map of id → model for quick lookup
        $model_map = array();
        foreach ( $all_models as $model ) {
            $model_map[ (int) $model->id ] = $model;
        }

        // Parse optional selected_model_ids sent by JS (JSON array of int IDs)
        $selected_raw = wp_unslash( $_POST['selected_model_ids'] ?? '' );
        $selected_ids = array();
        if ( $selected_raw ) {
            $decoded = json_decode( $selected_raw, true );
            if ( is_array( $decoded ) ) {
                $selected_ids = array_map( 'intval', $decoded );
            }
        }

        // Qualifying = selected by user AND include_in_survey = 1
        // If no selection passed (legacy call), fall back to all survey models
        $qualifying_categories = array();
        foreach ( $all_models as $model ) {
            $model_id  = (int) $model->id;
            $survey_ok = (int) $model->include_in_survey === 1;
            $selected_ok = empty( $selected_ids ) || in_array( $model_id, $selected_ids, true );
            if ( $survey_ok && $selected_ok ) {
                $qualifying_categories[] = array(
                    'id'               => $model_id,
                    'name'             => $model->name,
                    'calculation_type' => $model->calculation_type ?? 'auto',
                );
            }
        }


        $qualifying_ids = array_column( $qualifying_categories, 'id' );

        // ── 1. Actual Scans (per size, independent of category) ────────────────
        $table_sizes = $wpdb->prefix . 'os_student_uniform_sizes';
        $actual_scans = array();
        if ( $year_id ) {
            $scan_rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT uniform_size as sz, COUNT(*) as count 
                 FROM {$table_sizes} 
                 WHERE academic_year_id = %d AND uniform_size > 0 
                 GROUP BY uniform_size",
                $year_id
            ) );
            foreach ( $scan_rows as $row ) {
                $actual_scans[ (int) $row->sz ] = (int) $row->count;
            }
        }

        // ── 2. Inventory per custom model per size ──────────────────────────────
        // Items store model_id as JSON spec: {"model_id":"3", ...}
        $items_table = $wpdb->prefix . 'os_items';
        $stock_table = $wpdb->prefix . 'os_stock';
        $inventory   = array(); // model_name => [ size => qty ]

        foreach ( $qualifying_categories as $qm ) {
            $model_id   = (int) $qm['id'];
            $model_name = $qm['name'];
            $like_val   = '%"model_id":"' . $model_id . '"%';

            $inv_items = $wpdb->get_results( $wpdb->prepare(
                "SELECT i.id, i.name, i.specifications,
                        COALESCE(SUM(s.quantity_on_hand - s.quantity_reserved), 0) as stock
                 FROM {$items_table} i
                 LEFT JOIN {$stock_table} s ON s.item_id = i.id
                 WHERE i.specifications LIKE %s AND i.is_active = 1
                 GROUP BY i.id",
                $like_val
            ) );

            foreach ( $inv_items as $item ) {
                $specs = json_decode( $item->specifications, true );
                $size  = self::extract_size( $specs, $item->name );
                if ( ! $size ) continue;

                if ( ! isset( $inventory[ $model_name ] ) ) {
                    $inventory[ $model_name ] = array();
                }
                if ( ! isset( $inventory[ $model_name ][ $size ] ) ) {
                    $inventory[ $model_name ][ $size ] = 0;
                }
                $inventory[ $model_name ][ $size ] += (int) $item->stock;
            }
        }

        // ── 3. Supplier Pricing per custom model per size ───────────────────────
        $providers_table = $wpdb->prefix . 'os_providers';
        $active_provider = $wpdb->get_row( "SELECT * FROM {$providers_table} WHERE is_active = 1 LIMIT 1" );
        $supplier_pricing = array(); // model_name => [ size => unit_price ]

        if ( $active_provider && ! empty( $qualifying_categories ) ) {
            foreach ( $qualifying_categories as $qm ) {
                $model_id   = (int) $qm['id'];
                $model_name = $qm['name'];
                $like_val   = '%"model_id":"' . $model_id . '"%';

                $provider_items = $wpdb->get_results( $wpdb->prepare(
                    "SELECT i.id, i.name, i.specifications, i.unit_price
                     FROM {$items_table} i
                     WHERE i.provider_id = %d AND i.unit_price > 0
                           AND i.specifications LIKE %s AND i.is_active = 1",
                    $active_provider->id,
                    $like_val
                ) );

                foreach ( $provider_items as $item ) {
                    $specs = json_decode( $item->specifications, true );
                    $size  = self::extract_size( $specs, $item->name );
                    if ( ! $size ) continue;

                    if ( ! isset( $supplier_pricing[ $model_name ] ) ) {
                        $supplier_pricing[ $model_name ] = array();
                    }
                    $supplier_pricing[ $model_name ][ $size ] = (float) $item->unit_price;
                }
            }
        }

        wp_send_json_success( array(
            'actual_scans'           => $actual_scans,
            'inventory'              => $inventory,
            'supplier_pricing'       => $supplier_pricing,
            'active_supplier'        => $active_provider ? $active_provider->company_name : null,
            'active_supplier_details'=> $active_provider ? array(
                'company_name'   => $active_provider->company_name,
                'mobile_contact' => $active_provider->mobile_contact,
                'location'       => $active_provider->location,
                'contact_person' => $active_provider->contact_person,
            ) : null,
            'qualifying_categories'  => $qualifying_categories,
        ) );
    }

    /**
     * Extract numeric size from item specifications JSON or item name.
     * Checks spec keys for 'size'/'مقاس', then numeric values, then name pattern.
     *
     * @param array|null $specs Decoded specifications array.
     * @param string     $name  Item name string.
     * @return int  Size number, or 0 if not found.
     */
    private static function extract_size( $specs, $name ) {
        $size = 0;
        if ( is_array( $specs ) ) {
            foreach ( $specs as $k => $v ) {
                if ( stripos( $k, 'size' ) !== false || stripos( $k, 'مقاس' ) !== false ) {
                    $size = (int) $v;
                    break;
                }
            }
            if ( ! $size ) {
                foreach ( $specs as $v ) {
                    if ( is_numeric( $v ) ) { $size = (int) $v; break; }
                }
            }
        }
        if ( ! $size ) {
            if ( preg_match( '/\b(22|24|26|28|30|32|34|36|38|40|42|44|46|48|50|52|54)\b/', $name, $m ) ) {
                $size = (int) $m[1];
            }
        }
        return $size;
    }



    /**
     * Save a draft estimation.
     */
    public static function save_draft() {
        check_ajax_referer( 'os_order_estimation_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.', 403 );
        }

        $name   = sanitize_text_field( wp_unslash( $_POST['name']   ?? '' ) );
        $margin = (float) ( $_POST['margin'] ?? 0 );
        $raw    = wp_unslash( $_POST['grades'] ?? '{}' );
        $grades_raw = json_decode( $raw, true );

        if ( ! is_array( $grades_raw ) ) {
            wp_send_json_error( 'Invalid grades data.' );
        }

        // Sanitize grade values (positive integers only)
        $grades = array();
        foreach ( $grades_raw as $grade => $count ) {
            $grade_key        = strtoupper( sanitize_key( $grade ) );
            $grades[ $grade_key ] = max( 0, (int) $count );
        }

        if ( empty( $name ) ) {
            wp_send_json_error( 'Draft name is required.' );
        }

        $drafts = get_option( 'os_estimation_drafts', array() );
        $drafts[] = array(
            'name'     => $name,
            'grades'   => $grades,
            'margin'   => $margin,
            'saved_at' => current_time( 'Y-m-d H:i' ),
        );

        // Keep last 50 drafts only
        if ( count( $drafts ) > 50 ) {
            $drafts = array_slice( $drafts, -50 );
        }

        update_option( 'os_estimation_drafts', $drafts );

        wp_send_json_success( array( 'drafts' => $drafts ) );
    }

    /**
     * Delete a draft by index.
     */
    public static function delete_draft() {
        check_ajax_referer( 'os_order_estimation_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.', 403 );
        }

        $idx    = (int) ( $_POST['idx'] ?? -1 );
        $drafts = get_option( 'os_estimation_drafts', array() );

        if ( $idx < 0 || ! isset( $drafts[ $idx ] ) ) {
            wp_send_json_error( 'Invalid draft index.' );
        }

        array_splice( $drafts, $idx, 1 );
        update_option( 'os_estimation_drafts', $drafts );

        wp_send_json_success( array( 'drafts' => $drafts ) );
    }

    /**
     * Save custom size distribution.
     */
    public static function save_distribution() {
        check_ajax_referer( 'os_order_estimation_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.', 403 );
        }

        $raw  = wp_unslash( $_POST['distribution'] ?? '{}' );
        $data = json_decode( $raw, true );

        if ( ! is_array( $data ) ) {
            wp_send_json_error( 'Invalid distribution data.' );
        }

        // Sanitize: grade keys, size keys (int), pct values (float)
        $clean = array();
        foreach ( $data as $grade => $sizes ) {
            if ( ! is_array( $sizes ) ) continue;
            $grade_key = strtoupper( sanitize_key( $grade ) );
            $clean[ $grade_key ] = array();
            foreach ( $sizes as $sz => $pct ) {
                $clean[ $grade_key ][ (int) $sz ] = round( (float) $pct, 4 );
            }
        }

        update_option( 'os_estimation_distribution', $clean );
        wp_send_json_success( array( 'saved' => true ) );
    }
}
