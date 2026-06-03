<?php
/** OS_API_Stock — REST endpoints for stock levels, receipts, adjustments, movements. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_API_Stock {
    const NS = 'olama-stores/v1';

    public static function register_routes() {
        register_rest_route( self::NS, '/stock', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'get_stock' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_view_stock' ); },
        ) );
        register_rest_route( self::NS, '/stock/reset-testing', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'reset_testing' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_manage_settings' ); },
        ) );
        register_rest_route( self::NS, '/stock/delete-transactions', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'delete_transactions' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_manage_settings' ) || current_user_can( 'manage_options' ); },
        ) );
        register_rest_route( self::NS, '/stock/receive', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'receive_stock' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_receive_stock' ); },
        ) );
        // REC-04: Batch multi-item shipment receipt
        register_rest_route( self::NS, '/stock/receive-batch', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'receive_batch' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_receive_stock' ); },
        ) );
        // REC-08: Inter-warehouse transfer
        register_rest_route( self::NS, '/stock/transfer', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'transfer_stock' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_receive_stock' ); },
        ) );
        register_rest_route( self::NS, '/stock/bulk-validate', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'bulk_validate_stock' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_receive_stock' ); },
        ) );
        register_rest_route( self::NS, '/stock/bulk-receive', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'bulk_receive_stock' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_receive_stock' ); },
        ) );
        register_rest_route( self::NS, '/stock/adjust', array(
            'methods' => 'POST', 'callback' => array( __CLASS__, 'adjust_stock' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_adjust_stock' ); },
        ) );
        register_rest_route( self::NS, '/stock/movements', array(
            'methods' => 'GET', 'callback' => array( __CLASS__, 'get_movements' ),
            'permission_callback' => function() { return OS_Roles::can( 'os_view_stock' ); },
        ) );
        register_rest_route( self::NS, '/stock/movements/(?P<id>\d+)', array(
            'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_movement' ),
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ) );
        register_rest_route( self::NS, '/warehouses', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_warehouses' ), 'permission_callback' => function() { return OS_Roles::can( 'os_view_stock' ); } ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_warehouse'), 'permission_callback' => function() { return OS_Roles::can( 'os_manage_warehouses' ); } ),
        ) );
        register_rest_route( self::NS, '/warehouses/(?P<id>\d+)', array(
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_warehouse' ), 'permission_callback' => function() { return OS_Roles::can( 'os_manage_warehouses' ); } ),
        ) );
    }

    public static function get_stock( $request ) {
        $args = array_filter( array(
            'warehouse_id'    => (int) $request->get_param( 'warehouse_id' ),
            'item_id'         => (int) $request->get_param( 'item_id' ),
            'category_id'     => (int) $request->get_param( 'category_id' ),
            'low_stock_only'  => (bool) $request->get_param( 'low_stock_only' ),
            'orderby'         => sanitize_text_field( $request->get_param( 'orderby' ) ),
            'order'           => sanitize_text_field( $request->get_param( 'order' ) ),
            'paged'           => (int) $request->get_param( 'paged' ),
            'per_page'        => (int) $request->get_param( 'per_page' ),
        ) );
        return rest_ensure_response( OS_Stock_Service::get_stock_levels( $args ) );
    }

    public static function reset_testing( $request ) {
        $result = OS_Stock_Service::reset_store_data_testing();
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( array( 'success' => true ) );
    }

    public static function receive_stock( $request ) {
        $data   = $request->get_json_params();
        // Correction #1: ensure academic_year_id is int
        $data['academic_year_id'] = ! empty( $data['academic_year_id'] ) ? (int) $data['academic_year_id'] : os_get_active_year_id();
        $result = OS_Stock_Service::record_receipt( $data );
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( array( 'movement_id' => $result ), 201 );
    }

    public static function bulk_validate_stock( $request ) {
        global $wpdb;
        $params = $request->get_json_params();
        $rows = isset( $params['rows'] ) && is_array( $params['rows'] ) ? $params['rows'] : array();
        
        $resolved = array();
        
        // Cache warehouses to avoid repeated queries
        $warehouses = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}os_warehouses" );
        $wh_map = array();
        foreach ( $warehouses as $wh ) {
            $wh_map[ strtolower( trim( $wh->name ) ) ] = (int) $wh->id;
            $wh_map[ (string) $wh->id ] = (int) $wh->id;
        }

        foreach ( $rows as $index => $row ) {
            $sku = isset( $row['sku'] ) ? sanitize_text_field( trim( $row['sku'] ) ) : '';
            $qty = isset( $row['quantity'] ) ? (int) $row['quantity'] : 0;
            $notes = isset( $row['notes'] ) ? sanitize_textarea_field( trim( $row['notes'] ) ) : '';
            $wh_input = isset( $row['warehouse'] ) ? sanitize_text_field( trim( $row['warehouse'] ) ) : '';

            $row_res = array(
                'index'      => $index,
                'sku'        => $sku,
                'quantity'   => $qty,
                'notes'      => $notes,
                'warehouse'  => $wh_input,
                'item_id'    => null,
                'item_name'  => null,
                'wh_id'      => null,
                'wh_name'    => null,
                'valid'      => true,
                'error'      => '',
            );

            if ( empty( $sku ) ) {
                $row_res['valid'] = false;
                $row_res['error'] = __( 'SKU or Barcode is empty.', 'olama-stores' );
                $resolved[] = $row_res;
                continue;
            }

            if ( $qty <= 0 ) {
                $row_res['valid'] = false;
                $row_res['error'] = __( 'Quantity must be greater than 0.', 'olama-stores' );
                $resolved[] = $row_res;
                continue;
            }

            // Lookup item by SKU or Barcode
            $item = $wpdb->get_row( $wpdb->prepare(
                "SELECT id, name, sku, unit_id FROM {$wpdb->prefix}os_items WHERE (sku = %s OR barcode = %s) AND is_active = 1 LIMIT 1",
                $sku, $sku
            ) );

            if ( ! $item ) {
                $row_res['valid'] = false;
                $row_res['error'] = sprintf( __( 'Item with SKU/Barcode "%s" not found.', 'olama-stores' ), $sku );
                $resolved[] = $row_res;
                continue;
            }

            $row_res['item_id']   = (int) $item->id;
            $row_res['item_name'] = $item->name;
            $row_res['sku']       = $item->sku; // Normalize SKU if matched by barcode

            // Lookup unit symbol
            if ( ! empty( $item->unit_id ) ) {
                $unit_symbol = $wpdb->get_var( $wpdb->prepare(
                    "SELECT symbol FROM {$wpdb->prefix}os_units WHERE id = %d",
                    $item->unit_id
                ) );
                $row_res['unit_symbol'] = $unit_symbol ?: '';
            } else {
                $row_res['unit_symbol'] = '';
            }

            // Resolve Warehouse if input
            if ( ! empty( $wh_input ) ) {
                $wh_key = strtolower( $wh_input );
                if ( isset( $wh_map[ $wh_key ] ) ) {
                    $row_res['wh_id'] = $wh_map[ $wh_key ];
                    // Get nice name
                    foreach ( $warehouses as $wh ) {
                        if ( (int) $wh->id === $row_res['wh_id'] ) {
                            $row_res['wh_name'] = $wh->name;
                            break;
                        }
                    }
                } else {
                    $row_res['valid'] = false;
                    $row_res['error'] = sprintf( __( 'Warehouse "%s" not found.', 'olama-stores' ), $wh_input );
                }
            }

            $resolved[] = $row_res;
        }

        return rest_ensure_response( $resolved );
    }

    public static function bulk_receive_stock( $request ) {
        if ( ! OS_Roles::can( 'os_receive_stock' ) ) {
            return new WP_Error( 'unauthorized', __( 'Insufficient permissions to add items.', 'olama-stores' ), array( 'status' => 403 ) );
        }

        $params = $request->get_json_params();
        $rows = isset( $params['rows'] ) && is_array( $params['rows'] ) ? $params['rows'] : array();
        $default_wh_id = isset( $params['default_warehouse_id'] ) ? (int) $params['default_warehouse_id'] : 0;
        $academic_year_id = ! empty( $params['academic_year_id'] ) ? (int) $params['academic_year_id'] : os_get_active_year_id();

        if ( empty( $rows ) ) {
            return new WP_Error( 'empty_rows', __( 'No rows to import.', 'olama-stores' ), array( 'status' => 400 ) );
        }

        $imported_count = 0;

        foreach ( $rows as $index => $row ) {
            $item_id = isset( $row['item_id'] ) ? (int) $row['item_id'] : 0;
            $wh_id = ! empty( $row['wh_id'] ) ? (int) $row['wh_id'] : $default_wh_id;
            $qty = isset( $row['quantity'] ) ? (int) $row['quantity'] : 0;
            $notes = isset( $row['notes'] ) ? sanitize_textarea_field( $row['notes'] ) : '';

            if ( $item_id <= 0 ) {
                return new WP_Error( 'invalid_item', sprintf( __( 'Row %d: Invalid item ID.', 'olama-stores' ), $index + 1 ), array( 'status' => 400 ) );
            }

            if ( $wh_id <= 0 ) {
                return new WP_Error( 'invalid_warehouse', sprintf( __( 'Row %d: Warehouse not specified.', 'olama-stores' ), $index + 1 ), array( 'status' => 400 ) );
            }

            if ( $qty <= 0 ) {
                return new WP_Error( 'invalid_quantity', sprintf( __( 'Row %d: Quantity must be greater than 0.', 'olama-stores' ), $index + 1 ), array( 'status' => 400 ) );
            }

            $receipt_data = array(
                'item_id'          => $item_id,
                'warehouse_id'     => $wh_id,
                'quantity'         => $qty,
                'movement_type'          => 'opening_balance',
                'notes'            => $notes,
                'academic_year_id' => $academic_year_id,
            );

            $result = OS_Stock_Service::record_receipt( $receipt_data );
            if ( is_wp_error( $result ) ) {
                return $result;
            }
            $imported_count++;
        }

        return rest_ensure_response( array( 'success' => true, 'count' => $imported_count ) );
    }

    public static function adjust_stock( $request ) {
        $data   = $request->get_json_params();
        $data['academic_year_id'] = ! empty( $data['academic_year_id'] ) ? (int) $data['academic_year_id'] : os_get_active_year_id();
        $result = OS_Stock_Service::manual_adjustment( $data );
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( array( 'movement_id' => $result ), 201 );
    }

    public static function get_movements( $request ) {
        $args = array_filter( array(
            'item_id'          => (int) $request->get_param( 'item_id' ),
            'warehouse_id'     => (int) $request->get_param( 'warehouse_id' ),
            'movement_type'    => sanitize_key( $request->get_param( 'movement_type' ) ),
            'performed_by'     => (int) $request->get_param( 'performed_by' ),
            'academic_year_id' => (int) $request->get_param( 'academic_year_id' ),
            'date_from'        => sanitize_text_field( $request->get_param( 'date_from' ) ),
            'date_to'          => sanitize_text_field( $request->get_param( 'date_to' ) ),
            'limit'            => (int) ( $request->get_param( 'limit' ) ?: 100 ),
        ) );
        return rest_ensure_response( OS_Movement::get_list( $args ) );
    }

    public static function delete_movement( $request ) {
        $id = (int) $request['id'];
        $result = OS_Stock_Service::reverse_movement( $id );
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( array( 'success' => true ) );
    }

    public static function get_warehouses() {
        return rest_ensure_response( OS_Warehouse::get_all() );
    }

    public static function create_warehouse( $request ) {
        $id = OS_Warehouse::create( $request->get_json_params() );
        return rest_ensure_response( array( 'id' => $id ), 201 );
    }

    public static function update_warehouse( $request ) {
        $id = OS_Warehouse::update( (int) $request['id'], $request->get_json_params() );
        return rest_ensure_response( array( 'id' => $id ) );
    }

    public static function delete_transactions( $request ) {
        $data = $request->get_json_params() ?: array();

        $filters = array();
        if ( ! empty( $data['item_id'] ) ) {
            $filters['item_id'] = (int) $data['item_id'];
        }
        if ( ! empty( $data['provider_id'] ) ) {
            $filters['provider_id'] = (int) $data['provider_id'];
        }
        if ( ! empty( $data['start_date'] ) ) {
            $filters['start_date'] = sanitize_text_field( $data['start_date'] );
        }
        if ( ! empty( $data['end_date'] ) ) {
            $filters['end_date'] = sanitize_text_field( $data['end_date'] );
        }

        if ( empty( $filters ) ) {
            return new WP_Error( 'missing_filters', __( 'Please specify at least one filter.', 'olama-stores' ), array( 'status' => 400 ) );
        }

        $result = OS_Stock_Service::delete_transactions_testing( $filters );
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( array( 'success' => true ) );
    }

    // ── REC-04: Batch shipment receipt ────────────────────────────────────────
    /**
     * Receive a multi-item shipment in a single atomic transaction.
     * Expects: { movement_type, notes, academic_year_id, items: [{ item_id, warehouse_id, quantity, notes }] }
     */
    public static function receive_batch( $request ) {
        global $wpdb;
        $data             = $request->get_json_params();
        $movement_type    = sanitize_key( $data['movement_type'] ?? 'purchase_receipt' );
        $shared_notes     = sanitize_textarea_field( $data['notes'] ?? '' );
        $academic_year_id = ! empty( $data['academic_year_id'] ) ? (int) $data['academic_year_id'] : os_get_active_year_id();
        $items            = isset( $data['items'] ) && is_array( $data['items'] ) ? $data['items'] : array();

        if ( empty( $items ) ) {
            return new WP_Error( 'empty_items', __( 'No items provided.', 'olama-stores' ), array( 'status' => 400 ) );
        }

        // Validate each line before any DB writes
        foreach ( $items as $i => $line ) {
            if ( empty( $line['item_id'] ) || (int) $line['item_id'] <= 0 ) {
                return new WP_Error( 'invalid_item', sprintf( __( 'Row %d: Please select an item.', 'olama-stores' ), $i + 1 ), array( 'status' => 400 ) );
            }
            if ( empty( $line['warehouse_id'] ) || (int) $line['warehouse_id'] <= 0 ) {
                return new WP_Error( 'invalid_warehouse', sprintf( __( 'Row %d: Please select a warehouse.', 'olama-stores' ), $i + 1 ), array( 'status' => 400 ) );
            }
            if ( empty( $line['quantity'] ) || (int) $line['quantity'] <= 0 ) {
                return new WP_Error( 'invalid_qty', sprintf( __( 'Row %d: Quantity must be greater than 0.', 'olama-stores' ), $i + 1 ), array( 'status' => 400 ) );
            }
        }

        $wpdb->query( 'START TRANSACTION' );
        $movement_ids = array();

        foreach ( $items as $line ) {
            $row_data = array(
                'item_id'          => (int) $line['item_id'],
                'warehouse_id'     => (int) $line['warehouse_id'],
                'quantity'         => (int) $line['quantity'],
                'movement_type'    => $movement_type,
                'notes'            => sanitize_textarea_field( $line['notes'] ?? $shared_notes ),
                'academic_year_id' => $academic_year_id,
            );

            // Call record_receipt without its own transaction (we're wrapping it)
            $result = OS_Stock_Service::record_receipt_no_tx( $row_data );
            if ( is_wp_error( $result ) ) {
                $wpdb->query( 'ROLLBACK' );
                return $result;
            }
            $movement_ids[] = $result;
        }

        $wpdb->query( 'COMMIT' );
        return rest_ensure_response( array( 'success' => true, 'count' => count( $movement_ids ), 'movement_ids' => $movement_ids ), 201 );
    }

    // ── REC-08: Inter-warehouse transfer ─────────────────────────────────────
    /**
     * Transfer stock between warehouses atomically.
     * Expects: { item_id, from_warehouse_id, to_warehouse_id, quantity, notes }
     */
    public static function transfer_stock( $request ) {
        global $wpdb;
        $data             = $request->get_json_params();
        $item_id          = (int) ( $data['item_id'] ?? 0 );
        $from_wh          = (int) ( $data['from_warehouse_id'] ?? 0 );
        $to_wh            = (int) ( $data['to_warehouse_id'] ?? 0 );
        $quantity         = (int) ( $data['quantity'] ?? 0 );
        $notes            = sanitize_textarea_field( $data['notes'] ?? '' );
        $academic_year_id = os_get_active_year_id();

        if ( $item_id <= 0 || $from_wh <= 0 || $to_wh <= 0 || $quantity <= 0 ) {
            return new WP_Error( 'invalid_data', __( 'Item, warehouses, and quantity are all required.', 'olama-stores' ), array( 'status' => 400 ) );
        }
        if ( $from_wh === $to_wh ) {
            return new WP_Error( 'same_warehouse', __( 'Source and destination warehouse must be different.', 'olama-stores' ), array( 'status' => 400 ) );
        }

        // Check available stock in source warehouse
        $available = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT GREATEST(0, quantity_on_hand - quantity_reserved) FROM {$wpdb->prefix}os_stock
             WHERE item_id = %d AND warehouse_id = %d",
            $item_id, $from_wh
        ) );

        if ( $available < $quantity ) {
            return new WP_Error( 'insufficient_stock',
                sprintf( __( 'Insufficient available stock. Available: %d, Requested: %d.', 'olama-stores' ), $available, $quantity ),
                array( 'status' => 400 )
            );
        }

        $wpdb->query( 'START TRANSACTION' );

        // transfer_out: reduce source warehouse
        $out_data = array(
            'item_id'          => $item_id,
            'warehouse_id'     => $from_wh,
            'quantity'         => -$quantity,
            'movement_type'    => OS_Stock_Service::TRANSFER_OUT,
            'notes'            => $notes,
            'academic_year_id' => $academic_year_id,
        );
        $result_out = OS_Stock_Service::record_raw_movement( $out_data );
        if ( is_wp_error( $result_out ) ) {
            $wpdb->query( 'ROLLBACK' );
            return $result_out;
        }

        // transfer_in: increase destination warehouse
        $in_data = array(
            'item_id'          => $item_id,
            'warehouse_id'     => $to_wh,
            'quantity'         => $quantity,
            'movement_type'    => OS_Stock_Service::TRANSFER_IN,
            'notes'            => $notes,
            'academic_year_id' => $academic_year_id,
        );
        $result_in = OS_Stock_Service::record_raw_movement( $in_data );
        if ( is_wp_error( $result_in ) ) {
            $wpdb->query( 'ROLLBACK' );
            return $result_in;
        }

        $wpdb->query( 'COMMIT' );
        return rest_ensure_response( array(
            'success'          => true,
            'transfer_out_id'  => $result_out,
            'transfer_in_id'   => $result_in,
        ), 201 );
    }
}
