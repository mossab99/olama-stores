<?php
/**
 * OS_Item — Item Registry model.
 * Correction #5: specifications stored as LONGTEXT, decoded via json_decode().
 * Correction #1: academic_year_id is INT.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_Item {

    public static function get_list( $args = array() ) {
        global $wpdb;
        $where  = array( 'i.is_active = 1' );
        $params = array();

        if ( ! empty( $args['category_id'] ) ) {
            $where[] = 'i.category_id = %d'; $params[] = (int) $args['category_id'];
        }
        if ( isset( $args['provider_id_exact'] ) && $args['provider_id_exact'] > 0 ) {
            $where[] = 'i.provider_id = %d'; $params[] = (int) $args['provider_id_exact'];
        }
        // Filter by model_id stored in JSON specifications (e.g. {"model_id":"3", ...})
        if ( ! empty( $args['model_id'] ) ) {
            $like    = '%"model_id":"' . (int) $args['model_id'] . '"%';
            $where[] = 'i.specifications LIKE %s';
            $params[] = $like;
        }
        if ( isset( $args['is_active'] ) ) {
            $where[0] = 'i.is_active = ' . ( $args['is_active'] ? '1' : '0' );
        }
        if ( ! empty( $args['search'] ) ) {
            $keywords = explode( ' ', sanitize_text_field( $args['search'] ) );
            foreach ( $keywords as $kw ) {
                $kw = trim( $kw );
                if ( empty( $kw ) ) continue;
                $like = '%' . $wpdb->esc_like( $kw ) . '%';
                $where[] = '(i.name LIKE %s OR i.sku LIKE %s OR i.barcode LIKE %s OR i.specifications LIKE %s)';
                $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
            }
        }
        // Filter: is_custom = true → items that have stock in a warehouse of type 'custom'
        if ( ! empty( $args['is_custom'] ) ) {
            $where[] = "EXISTS (
                SELECT 1 FROM {$wpdb->prefix}os_stock s
                INNER JOIN {$wpdb->prefix}os_warehouses wh ON s.warehouse_id = wh.id
                WHERE s.item_id = i.id AND wh.type = 'custom'
            )";
        }

        $where_sql = implode( ' AND ', $where );

        // Sorting — whitelist allowed columns
        $allowed_orderby = array(
            'name'            => 'i.name',
            'sku'             => 'i.sku',
            'category_name'   => 'c.name',
            'unit_name'       => 'u.name',
            'unit_price'      => 'i.unit_price',
            'provider_name'   => 'p.company_name',
            'min_stock_level' => 'i.min_stock_level',
        );
        $orderby_col = $allowed_orderby[ $args['orderby'] ?? '' ] ?? 'i.name';
        $order_dir   = strtoupper( $args['order'] ?? 'ASC' ) === 'DESC' ? 'DESC' : 'ASC';

        // Secondary sort: always group by provider after the primary column (or by name when sorting by provider)
        $secondary_sort = ( $orderby_col === 'p.company_name' )
            ? ', i.name ASC'
            : ', p.company_name ASC, i.name ASC';

        $sql = "SELECT i.*, c.name AS category_name, u.name AS unit_name, u.symbol AS unit_symbol, p.company_name AS provider_name
                FROM {$wpdb->prefix}os_items i
                LEFT JOIN {$wpdb->prefix}os_categories c ON i.category_id = c.id
                LEFT JOIN {$wpdb->prefix}os_units u ON i.unit_id = u.id
                LEFT JOIN {$wpdb->prefix}os_providers p ON i.provider_id = p.id
                WHERE $where_sql ORDER BY $orderby_col $order_dir$secondary_sort";

        // Pagination
        if ( ! empty( $args['limit'] ) ) {
            $limit  = (int) $args['limit'];
            $offset = isset( $args['offset'] ) ? (int) $args['offset'] : 0;
            $sql .= " LIMIT $offset, $limit";
        }

        $rows = ! empty( $params ) ? $wpdb->get_results( $wpdb->prepare( $sql, $params ) ) : $wpdb->get_results( $sql );

        // Correction #5: decode LONGTEXT specifications as JSON in PHP
        foreach ( $rows as $row ) {
            $row->specifications = $row->specifications ? json_decode( $row->specifications, true ) : array();
        }
        return $rows;
    }

    public static function count( $args = array() ) {
        global $wpdb;
        $where  = array( 'i.is_active = 1' );
        $params = array();

        if ( ! empty( $args['category_id'] ) ) {
            $where[] = 'i.category_id = %d'; $params[] = (int) $args['category_id'];
        }
        if ( isset( $args['provider_id_exact'] ) && $args['provider_id_exact'] > 0 ) {
            $where[] = 'i.provider_id = %d'; $params[] = (int) $args['provider_id_exact'];
        }
        // Filter by model_id stored in JSON specifications
        if ( ! empty( $args['model_id'] ) ) {
            $like    = '%"model_id":"' . (int) $args['model_id'] . '"%';
            $where[] = 'i.specifications LIKE %s';
            $params[] = $like;
        }
        if ( ! empty( $args['search'] ) ) {
            $keywords = explode( ' ', sanitize_text_field( $args['search'] ) );
            foreach ( $keywords as $kw ) {
                $kw = trim( $kw );
                if ( empty( $kw ) ) continue;
                $like = '%' . $wpdb->esc_like( $kw ) . '%';
                $where[] = '(i.name LIKE %s OR i.sku LIKE %s OR i.barcode LIKE %s OR i.specifications LIKE %s)';
                $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
            }
        }
        // Filter: is_custom = true → items that have stock in a warehouse of type 'custom'
        if ( ! empty( $args['is_custom'] ) ) {
            $where[] = "EXISTS (
                SELECT 1 FROM {$wpdb->prefix}os_stock s
                INNER JOIN {$wpdb->prefix}os_warehouses wh ON s.warehouse_id = wh.id
                WHERE s.item_id = i.id AND wh.type = 'custom'
            )";
        }

        $where_sql = implode( ' AND ', $where );
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}os_items i WHERE $where_sql";

        return (int) ( ! empty( $params ) ? $wpdb->get_var( $wpdb->prepare( $sql, $params ) ) : $wpdb->get_var( $sql ) );
    }

    public static function get( $id ) {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT i.*, c.name AS category_name, u.name AS unit_name, u.symbol AS unit_symbol, p.company_name AS provider_name
             FROM {$wpdb->prefix}os_items i
             LEFT JOIN {$wpdb->prefix}os_categories c ON i.category_id = c.id
             LEFT JOIN {$wpdb->prefix}os_units u ON i.unit_id = u.id
             LEFT JOIN {$wpdb->prefix}os_providers p ON i.provider_id = p.id
             WHERE i.id = %d", $id
        ) );
        if ( $row ) {
            $row->specifications = $row->specifications ? json_decode( $row->specifications, true ) : array();
        }
        return $row;
    }

    public static function create( $data ) {
        global $wpdb;
        $old = null;
        $payload = self::build_payload( $data );
        if ( is_wp_error( $payload ) ) { return $payload; }

        $wpdb->insert( "{$wpdb->prefix}os_items", $payload );
        $id = $wpdb->insert_id;
        if ( ! $id ) { return new WP_Error( 'db_error', $wpdb->last_error ); }

        OS_Audit_Service::log( 'os_items', $id, 'create', null, $payload );
        return $id;
    }

    public static function update( $id, $data ) {
        global $wpdb;
        $old = self::get( $id );
        if ( ! $old ) { return new WP_Error( 'not_found', __( 'Item not found.', 'olama-stores' ) ); }

        $payload = self::build_payload( $data, $id );
        if ( is_wp_error( $payload ) ) { return $payload; }
        $payload['updated_at'] = current_time( 'mysql', 1 );

        $wpdb->update( "{$wpdb->prefix}os_items", $payload, array( 'id' => $id ) );
        OS_Audit_Service::log( 'os_items', $id, 'update', $old, $payload );
        return $id;
    }

    public static function delete( $id ) {
        global $wpdb;
        $old = self::get( $id );
        $wpdb->update( "{$wpdb->prefix}os_items", array( 'is_active' => 0 ), array( 'id' => $id ) );
        OS_Audit_Service::log( 'os_items', $id, 'delete', $old, null );
        return true;
    }

    private static function build_payload( $data, $exclude_id = 0 ) {
        global $wpdb;
        $sku = sanitize_text_field( $data['sku'] ?? '' );
        if ( empty( $sku ) ) { $sku = self::generate_sku(); }

        // Duplicate SKU check
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}os_items WHERE sku = %s AND id != %d", $sku, $exclude_id
        ) );
        if ( $exists ) { return new WP_Error( 'duplicate_sku', __( 'SKU already exists.', 'olama-stores' ) ); }

        // Correction #5: encode specifications array as JSON string for LONGTEXT column
        $specs = ! empty( $data['specifications'] ) ? wp_json_encode( $data['specifications'] ) : null;

        return array(
            'sku'             => $sku,
            'name'            => sanitize_text_field( $data['name'] ?? '' ),
            'name_ar'         => sanitize_text_field( $data['name_ar'] ?? '' ),
            'category_id'     => (int) ( $data['category_id'] ?? 0 ),
            'unit_id'         => (int) ( $data['unit_id'] ?? 0 ),
            'description'     => sanitize_textarea_field( $data['description'] ?? '' ),
            'specifications'  => $specs,
            'min_stock_level' => (int) ( $data['min_stock_level'] ?? 0 ),
            'image_url'       => esc_url_raw( $data['image_url'] ?? '' ),
            'barcode'         => sanitize_text_field( $data['barcode'] ?? '' ),
            'unit_price'      => floatval( $data['unit_price'] ?? 0 ),
            'provider_id'     => ( isset( $data['provider_id'] ) && '' !== $data['provider_id'] ) ? (int) $data['provider_id'] : null,
            'is_active'       => (int) ( $data['is_active'] ?? 1 ),
            // Correction #1: integer FK
            'academic_year_id'=> ! empty( $data['academic_year_id'] ) ? (int) $data['academic_year_id'] : null,
            'base_item_id'    => ! empty( $data['base_item_id'] ) ? (int) $data['base_item_id'] : null,
            'created_by'      => get_current_user_id(),
        );
    }

    private static function generate_sku() {
        $prefix = get_option( 'os_sku_prefix', 'SKU' );
        global $wpdb;
        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}os_items" );
        return strtoupper( $prefix ) . '-' . str_pad( $count + 1, 5, '0', STR_PAD_LEFT );
    }
}
