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
    }

    public static function get_supplier_report_data() {
        check_ajax_referer( 'os_order_estimation_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Insufficient permissions.' );

        $year_id = (int) ( $_POST['year_id'] ?? 0 );
        global $wpdb;

        // 1. Actual Scans
        $table_sizes = $wpdb->prefix . 'os_student_uniform_sizes';
        $scan_rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT uniform_size as sz, COUNT(*) as count 
             FROM {$table_sizes} 
             WHERE academic_year_id = %d AND uniform_size > 0 
             GROUP BY uniform_size", 
             $year_id
        ) );
        $actual_scans = array();
        foreach ( $scan_rows as $row ) {
            $actual_scans[ (int) $row->sz ] = (int) $row->count;
        }

        // 2. Inventory (Polo, Hoody, Pants)
        $items_table = $wpdb->prefix . 'os_items';
        $stock_table = $wpdb->prefix . 'os_stock';
        $like_polo = '%' . $wpdb->esc_like('Polo') . '%';
        $like_hoody = '%' . $wpdb->esc_like('Hoody') . '%';
        $like_pant = '%' . $wpdb->esc_like('Pant') . '%';

        // Stock is warehouse-wide — do NOT filter by year (items may belong to a different year than current active year)
        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT i.id, i.name, i.specifications, COALESCE(SUM(s.quantity_on_hand - s.quantity_reserved), 0) as stock
             FROM {$items_table} i
             LEFT JOIN {$stock_table} s ON s.item_id = i.id
             WHERE (i.name LIKE %s OR i.name LIKE %s OR i.name LIKE %s)
             GROUP BY i.id",
             $like_polo, $like_hoody, $like_pant
        ) );
        
        $inventory = array( 'Polo' => array(), 'Hoody' => array(), 'Pants' => array() );
        
        foreach ( $items as $item ) {
            $specs = json_decode( $item->specifications, true );
            
            // Determine type from item name (specs contain colors/fabrics, not type keywords)
            $type = '';
            if ( stripos( $item->name, 'Polo' ) !== false )      $type = 'Polo';
            elseif ( stripos( $item->name, 'Hoody' ) !== false )  $type = 'Hoody';
            elseif ( stripos( $item->name, 'Pant' ) !== false )   $type = 'Pants';
            
            if ( ! $type ) continue;
            
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
                if ( preg_match( '/\b(22|24|26|28|30|32|34|36|38|40|42|44|46|48|50|52|54)\b/', $item->name, $m ) ) {
                    $size = (int) $m[1];
                }
            }
            
            if ( $size ) {
                if ( ! isset( $inventory[ $type ][ $size ] ) ) {
                    $inventory[ $type ][ $size ] = 0;
                }
                $inventory[ $type ][ $size ] += (int) $item->stock;
            }
        }

        // 3. Supplier Pricing
        $providers_table = $wpdb->prefix . 'os_providers';
        $custom_models_table = $wpdb->prefix . 'os_custom_models';
        
        $active_provider = $wpdb->get_row("SELECT * FROM {$providers_table} WHERE is_active = 1 LIMIT 1");
        $supplier_pricing = array();
        
        if ($active_provider) {
            $provider_items = $wpdb->get_results($wpdb->prepare(
                "SELECT i.id, i.name, i.specifications, i.unit_price
                 FROM {$items_table} i
                 WHERE i.provider_id = %d AND i.unit_price > 0",
                 $active_provider->id
            ));
            
            foreach ($provider_items as $item) {
                $specs = json_decode($item->specifications, true);
                $type = '';
                if (!$type) {
                    if (stripos($item->name, 'Polo') !== false) $type = 'Polo';
                    elseif (stripos($item->name, 'Hoody') !== false) $type = 'Hoody';
                    elseif (stripos($item->name, 'Pant') !== false) $type = 'Pants';
                }
                
                if (!$type) continue;
                
                $size = 0;
                if (is_array($specs)) {
                    foreach ($specs as $k => $v) {
                        if (stripos($k, 'size') !== false || stripos($k, 'مقاس') !== false) {
                            $size = (int)$v;
                            break;
                        }
                    }
                    if (!$size) {
                        foreach ($specs as $v) {
                            if (is_numeric($v)) { $size = (int)$v; break; }
                        }
                    }
                }
                if (!$size) {
                    if (preg_match('/\b(22|24|26|28|30|32|34|36|38|40|42|44|46|48|50|52|54)\b/', $item->name, $m)) {
                        $size = (int)$m[1];
                    }
                }
                
                if ($size) {
                    if (!isset($supplier_pricing[$type])) $supplier_pricing[$type] = array();
                    $supplier_pricing[$type][$size] = (float)$item->unit_price;
                }
            }
        }

        wp_send_json_success( array(
            'actual_scans'     => $actual_scans,
            'inventory'        => $inventory,
            'supplier_pricing' => $supplier_pricing,
            'active_supplier'  => $active_provider ? $active_provider->company_name : null,
        ) );
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
            $grade_key        = sanitize_key( $grade );
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
