<?php
/**
 * OS_API_Items — REST API for Item Registry CRUD.
 * Namespace: olama-stores/v1
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_API_Items {

    const NS = 'olama-stores/v1';

    public static function register_routes() {
        register_rest_route( self::NS, '/items', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_items' ),  'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_item'), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/items/copy-provider', array(
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'copy_provider_items' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/items/delete-by-provider', array(
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'delete_provider_items' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/items/(?P<id>\d+)', array(
            array( 'methods' => 'GET',    'callback' => array( __CLASS__, 'get_item' ),    'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_item' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_item' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/categories', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_categories' ), 'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_category'), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/categories/(?P<id>\d+)', array(
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_category' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_category' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/units', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_units' ), 'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_unit'), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/units/(?P<id>\d+)', array(
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_unit' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_unit' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/grades', array(
            array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'get_grades' ), 'permission_callback' => array( __CLASS__, 'can_view' ) ),
        ) );
        register_rest_route( self::NS, '/subjects/(?P<grade_id>\d+)', array(
            array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'get_subjects' ), 'permission_callback' => array( __CLASS__, 'can_view' ) ),
        ) );
        register_rest_route( self::NS, '/providers', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_providers' ),  'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_provider'), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/providers/(?P<id>\d+)', array(
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_provider' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_provider' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/custom-models', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_custom_models' ), 'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_custom_model'), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/custom-models/(?P<id>\d+)', array(
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_custom_model' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_custom_model' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/fabrics', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_fabrics' ), 'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_fabric'), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/fabrics/(?P<id>\d+)', array(
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_fabric' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_fabric' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/colors', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_colors' ), 'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_color'), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/colors/(?P<id>\d+)', array(
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_color' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_color' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/sizes', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_sizes' ), 'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_size'), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
        register_rest_route( self::NS, '/sizes/(?P<id>\d+)', array(
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_size' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_size' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ),
        ) );
    }

    // ── Permission callbacks ───────────────────────────────────────────────────
    public static function can_view()   { return OS_Roles::can( 'os_view_items' ); }
    public static function can_manage() { return OS_Roles::can( 'os_manage_items' ); }

    // ── Item endpoints ────────────────────────────────────────────────────────
    public static function get_items( $request ) {
        $per_page = (int) $request->get_param( 'per_page' );
        $page     = (int) $request->get_param( 'page' );
        $is_custom = $request->get_param( 'is_custom' );

        $provider_id_exact = $request->get_param( 'provider_id_exact' );
        $model_id_param    = $request->get_param( 'model_id' );

        $args = array(
            'category_id'      => (int) $request->get_param( 'category_id' ),
            'search'           => sanitize_text_field( $request->get_param( 'search' ) ),
            // Correction #1: academic_year_id as INT
            'academic_year_id' => (int) $request->get_param( 'academic_year_id' ),
            'is_active'        => $request->get_param( 'is_active' ) !== null ? (bool) $request->get_param( 'is_active' ) : true,
            // Filter: only items belonging to the custom warehouse (have model_id in specs)
            'is_custom'        => ( $is_custom !== null && $is_custom !== '' && $is_custom !== '0' && $is_custom !== false ),
            'orderby'          => sanitize_key( (string) $request->get_param( 'orderby' ) ),
            'order'            => sanitize_key( (string) $request->get_param( 'order' ) ),
            // Filter by a specific provider (exact match — used by Copy Provider Items preview)
            'provider_id_exact'=> $provider_id_exact !== null && $provider_id_exact !== '' ? (int) $provider_id_exact : null,
            // Filter by model_id in specifications JSON (used by Copy Provider Items model filter)
            'model_id'         => $model_id_param !== null && $model_id_param !== '' ? (int) $model_id_param : null,
        );

        if ( $per_page > 0 ) {
            $args['limit']  = $per_page;
            $args['offset'] = ( max( 1, $page ) - 1 ) * $per_page;
        }

        $items = OS_Item::get_list( array_filter( $args ) );
        $total = OS_Item::count( array_filter( $args ) );

        $response = rest_ensure_response( $items );
        $response->header( 'X-WP-Total', (int) $total );
        $response->header( 'X-WP-TotalPages', $per_page > 0 ? ceil( $total / $per_page ) : 1 );

        return $response;
    }

    public static function get_item( $request ) {
        $item = OS_Item::get( (int) $request['id'] );
        if ( ! $item ) { return new WP_Error( 'not_found', __( 'Item not found.', 'olama-stores' ), array( 'status' => 404 ) ); }
        return rest_ensure_response( $item );
    }

    public static function create_item( $request ) {
        $result = OS_Item::create( $request->get_json_params() );
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( array( 'id' => $result ), 201 );
    }

    public static function update_item( $request ) {
        $result = OS_Item::update( (int) $request['id'], $request->get_json_params() );
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( OS_Item::get( (int) $request['id'] ) );
    }

    public static function delete_item( $request ) {
        OS_Item::delete( (int) $request['id'] );
        return rest_ensure_response( array( 'deleted' => true ) );
    }

    /**
     * Copy all items from one provider to another.
     * Each item gets a brand-new auto-generated SKU; the original item stays untouched.
     * The source provider's name is replaced with the target provider's name in item names.
     */
    public static function copy_provider_items( $request ) {
        global $wpdb;

        $params         = $request->get_json_params();
        $from_id        = (int) ( $params['from_provider_id'] ?? 0 );
        $to_id          = (int) ( $params['to_provider_id']   ?? 0 );
        $model_id       = isset( $params['model_id'] ) && $params['model_id'] > 0 ? (int) $params['model_id'] : null;

        if ( ! $from_id || ! $to_id ) {
            return new WP_Error( 'missing_params', __( 'Both from_provider_id and to_provider_id are required.', 'olama-stores' ), array( 'status' => 400 ) );
        }
        if ( $from_id === $to_id ) {
            return new WP_Error( 'same_provider', __( 'Source and destination providers must be different.', 'olama-stores' ), array( 'status' => 400 ) );
        }

        // Fetch provider names for the name-substitution logic
        $from_provider = $wpdb->get_row( $wpdb->prepare(
            "SELECT company_name FROM {$wpdb->prefix}os_providers WHERE id = %d", $from_id
        ) );
        $to_provider   = $wpdb->get_row( $wpdb->prepare(
            "SELECT company_name FROM {$wpdb->prefix}os_providers WHERE id = %d", $to_id
        ) );

        $from_name = $from_provider ? trim( $from_provider->company_name ) : '';
        $to_name   = $to_provider   ? trim( $to_provider->company_name )   : '';

        // Fetch all active items of the source provider (no pagination = all rows)
        $source_args = array( 'provider_id_exact' => $from_id );
        if ( $model_id ) {
            $source_args['model_id'] = $model_id;
        }
        $source_items = OS_Item::get_list( $source_args );

        if ( empty( $source_items ) ) {
            return new WP_Error( 'no_items', __( 'No items found matching the selected criteria.', 'olama-stores' ), array( 'status' => 404 ) );
        }

        $copied = 0;
        $errors = array();

        foreach ( $source_items as $item ) {
            // Replace source provider name with target provider name in both name fields
            $new_name    = $item->name;
            $new_name_ar = $item->name_ar ?? '';
            if ( $from_name !== '' && $to_name !== '' ) {
                $new_name    = str_ireplace( $from_name, $to_name, $new_name );
                $new_name_ar = str_ireplace( $from_name, $to_name, $new_name_ar );
            }

            $payload = array(
                'sku'             => '',          // auto-generate a new unique SKU
                'name'            => $new_name,
                'name_ar'         => $new_name_ar,
                'category_id'     => $item->category_id,
                'unit_id'         => $item->unit_id,
                'description'     => $item->description ?? '',
                'specifications'  => is_array( $item->specifications ) ? $item->specifications : array(),
                'min_stock_level' => (int) $item->min_stock_level,
                'barcode'         => '',          // blank barcode to avoid duplicate barcode errors
                'unit_price'      => (float) $item->unit_price,
                'provider_id'     => $to_id,      // KEY: new provider
                'is_active'       => 1,
                'academic_year_id'=> $item->academic_year_id ?? null,
                'base_item_id'    => $item->id,   // track origin
            );

            $result = OS_Item::create( $payload );
            if ( is_wp_error( $result ) ) {
                $errors[] = $item->name . ': ' . $result->get_error_message();
            } else {
                $copied++;
            }
        }

        return rest_ensure_response( array(
            'copied' => $copied,
            'errors' => $errors,
        ) );
    }

    /**
     * Delete (soft-delete) items belonging to a provider, with optional model filter.
     * Items that have any stock on hand (quantity_on_hand > 0 in any warehouse) are skipped.
     */
    public static function delete_provider_items( $request ) {
        global $wpdb;

        $params      = $request->get_json_params();
        $provider_id = (int) ( $params['provider_id'] ?? 0 );
        $model_id    = isset( $params['model_id'] ) && $params['model_id'] > 0 ? (int) $params['model_id'] : null;

        if ( ! $provider_id ) {
            return new WP_Error( 'missing_params', __( 'provider_id is required.', 'olama-stores' ), array( 'status' => 400 ) );
        }

        // Fetch all active items matching the filter (no pagination)
        $source_args = array( 'provider_id_exact' => $provider_id );
        if ( $model_id ) {
            $source_args['model_id'] = $model_id;
        }
        $items = OS_Item::get_list( $source_args );

        if ( empty( $items ) ) {
            return new WP_Error( 'no_items', __( 'No items found matching the selected criteria.', 'olama-stores' ), array( 'status' => 404 ) );
        }

        $deleted = 0;
        $skipped = array(); // names of items with stock > 0

        foreach ( $items as $item ) {
            // Check total stock across all warehouses
            $total_stock = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COALESCE(SUM(quantity_on_hand), 0) FROM {$wpdb->prefix}os_stock WHERE item_id = %d",
                $item->id
            ) );

            if ( $total_stock > 0 ) {
                $skipped[] = $item->name . ' (' . sprintf(
                    /* translators: %d = stock quantity */
                    __( 'stock: %d', 'olama-stores' ),
                    $total_stock
                ) . ')';
                continue;
            }

            OS_Item::delete( $item->id );
            $deleted++;
        }

        return rest_ensure_response( array(
            'deleted' => $deleted,
            'skipped' => $skipped,
        ) );
    }

    // ── Categories ────────────────────────────────────────────────────────────

    public static function get_categories() {
        global $wpdb;
        return rest_ensure_response( $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}os_categories WHERE is_active = 1 ORDER BY name ASC"
        ) );
    }

    public static function create_category( $request ) {
        global $wpdb;
        $data = $request->get_json_params();
        $wpdb->insert( "{$wpdb->prefix}os_categories", array(
            'name'       => sanitize_text_field( $data['name'] ?? '' ),
            'name_ar'    => sanitize_text_field( $data['name_ar'] ?? '' ),
            'parent_id'  => ! empty( $data['parent_id'] ) ? (int) $data['parent_id'] : null,
            'description'=> sanitize_textarea_field( $data['description'] ?? '' ),
            'is_active'  => 1,
        ) );
        return rest_ensure_response( array( 'id' => $wpdb->insert_id ), 201 );
    }

    public static function update_category( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $data = $request->get_json_params();
        $wpdb->update( "{$wpdb->prefix}os_categories", array(
            'name' => sanitize_text_field( $data['name'] ?? '' ),
        ), array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    public static function delete_category( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $wpdb->delete( "{$wpdb->prefix}os_categories", array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    // ── Units ─────────────────────────────────────────────────────────────────
    public static function get_units() {
        global $wpdb;
        return rest_ensure_response( $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}os_units ORDER BY name ASC" ) );
    }

    public static function create_unit( $request ) {
        global $wpdb;
        $data = $request->get_json_params();
        $wpdb->insert( "{$wpdb->prefix}os_units", array(
            'name'    => sanitize_text_field( $data['name'] ?? '' ),
            'name_ar' => sanitize_text_field( $data['name_ar'] ?? '' ),
            'symbol'  => sanitize_text_field( $data['symbol'] ?? '' ),
        ) );
        return rest_ensure_response( array( 'id' => $wpdb->insert_id ), 201 );
    }

    public static function update_unit( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $data = $request->get_json_params();
        $wpdb->update( "{$wpdb->prefix}os_units", array(
            'name'   => sanitize_text_field( $data['name'] ?? '' ),
            'symbol' => sanitize_text_field( $data['symbol'] ?? '' ),
        ), array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    public static function delete_unit( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $wpdb->delete( "{$wpdb->prefix}os_units", array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    // ── Grades & Subjects (from eval 02) ──────────────────────────────────────
    public static function get_grades() {
        return rest_ensure_response( OS_School_Integration::get_grades() );
    }

    public static function get_subjects( $request ) {
        if ( OS_School_Integration::is_core_available() ) {
            return rest_ensure_response( olama_core()->academic()->grade_subjects(
                OS_School_Integration::resolve_study_year( os_get_active_year_id() ),
                sanitize_text_field( (string) $request['grade_id'] )
            ) );
        }
        if ( ! class_exists( 'Olama_School_Subject' ) ) { return rest_ensure_response( array() ); }
        return rest_ensure_response( Olama_School_Subject::get_by_grade( (int) $request['grade_id'], true ) );
    }

    // ── Providers ─────────────────────────────────────────────────────────────
    public static function get_providers() {
        global $wpdb;
        return rest_ensure_response( $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}os_providers ORDER BY company_name ASC" ) );
    }

    public static function create_provider( $request ) {
        global $wpdb;
        $data = $request->get_json_params();
        $is_active = ! empty( $data['is_active'] ) ? 1 : 0;

        if ( $is_active ) {
            $wpdb->query( "UPDATE {$wpdb->prefix}os_providers SET is_active = 0" );
        }

        $wpdb->insert( "{$wpdb->prefix}os_providers", array(
            'company_name'   => sanitize_text_field( $data['company_name'] ?? '' ),
            'mobile_contact' => sanitize_text_field( $data['mobile_contact'] ?? '' ),
            'location'       => sanitize_text_field( $data['location'] ?? '' ),
            'contact_person' => sanitize_text_field( $data['contact_person'] ?? '' ),
            'is_active'      => $is_active,
        ) );
        return rest_ensure_response( array( 'id' => $wpdb->insert_id ), 201 );
    }

    public static function update_provider( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $data = $request->get_json_params();
        
        $update_data = array(
            'company_name'   => sanitize_text_field( $data['company_name'] ?? '' ),
            'mobile_contact' => sanitize_text_field( $data['mobile_contact'] ?? '' ),
            'location'       => sanitize_text_field( $data['location'] ?? '' ),
            'contact_person' => sanitize_text_field( $data['contact_person'] ?? '' ),
        );

        if ( isset( $data['is_active'] ) ) {
            $is_active = ! empty( $data['is_active'] ) ? 1 : 0;
            if ( $is_active ) {
                $wpdb->query( "UPDATE {$wpdb->prefix}os_providers SET is_active = 0" );
            }
            $update_data['is_active'] = $is_active;
        }

        $wpdb->update( "{$wpdb->prefix}os_providers", $update_data, array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    public static function delete_provider( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        // Check if provider is used by any item
        $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}os_items WHERE provider_id = %d", $id ) );
        if ( $count > 0 ) {
            return new WP_Error( 'provider_used', __( 'Cannot delete provider because it is linked to items.', 'olama-stores' ), array( 'status' => 400 ) );
        }
        $wpdb->delete( "{$wpdb->prefix}os_providers", array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    // ── Custom Models ─────────────────────────────────────────────────────────
    public static function get_custom_models() {
        global $wpdb;
        return rest_ensure_response( $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}os_custom_models ORDER BY name ASC" ) );
    }

    public static function create_custom_model( $request ) {
        global $wpdb;
        $data = $request->get_json_params();
        $calc_type = in_array( $data['calculation_type'] ?? 'auto', array( 'auto', 'manual' ), true )
            ? $data['calculation_type']
            : 'auto';
        $wpdb->insert( "{$wpdb->prefix}os_custom_models", array(
            'name'              => sanitize_text_field( $data['name'] ?? '' ),
            'include_in_survey' => isset( $data['include_in_survey'] ) ? (int) (bool) $data['include_in_survey'] : 1,
            'calculation_type'  => $calc_type,
        ) );
        return rest_ensure_response( array( 'id' => $wpdb->insert_id ), 201 );
    }

    public static function update_custom_model( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $data = $request->get_json_params();
        $update_data = array(
            'name' => sanitize_text_field( $data['name'] ?? '' ),
        );
        if ( isset( $data['include_in_survey'] ) ) {
            $update_data['include_in_survey'] = (int) (bool) $data['include_in_survey'];
        }
        if ( isset( $data['calculation_type'] ) && in_array( $data['calculation_type'], array( 'auto', 'manual' ), true ) ) {
            $update_data['calculation_type'] = $data['calculation_type'];
        }
        $wpdb->update( "{$wpdb->prefix}os_custom_models", $update_data, array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    public static function delete_custom_model( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $wpdb->delete( "{$wpdb->prefix}os_custom_models", array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    // ── Fabrics ─────────────────────────────────────────────────────────
    public static function get_fabrics() {
        global $wpdb;
        return rest_ensure_response( $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}os_fabrics ORDER BY name ASC" ) );
    }

    public static function create_fabric( $request ) {
        global $wpdb;
        $data = $request->get_json_params();
        $wpdb->insert( "{$wpdb->prefix}os_fabrics", array(
            'name'    => sanitize_text_field( $data['name'] ?? '' ),
        ) );
        return rest_ensure_response( array( 'id' => $wpdb->insert_id ), 201 );
    }

    public static function update_fabric( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $data = $request->get_json_params();
        $wpdb->update( "{$wpdb->prefix}os_fabrics", array(
            'name'    => sanitize_text_field( $data['name'] ?? '' ),
        ), array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    public static function delete_fabric( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $wpdb->delete( "{$wpdb->prefix}os_fabrics", array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    // ── Colors ─────────────────────────────────────────────────────────
    public static function get_colors() {
        global $wpdb;
        return rest_ensure_response( $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}os_colors ORDER BY name ASC" ) );
    }

    public static function create_color( $request ) {
        global $wpdb;
        $data = $request->get_json_params();
        $wpdb->insert( "{$wpdb->prefix}os_colors", array(
            'name'    => sanitize_text_field( $data['name'] ?? '' ),
        ) );
        return rest_ensure_response( array( 'id' => $wpdb->insert_id ), 201 );
    }

    public static function update_color( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $data = $request->get_json_params();
        $wpdb->update( "{$wpdb->prefix}os_colors", array(
            'name'    => sanitize_text_field( $data['name'] ?? '' ),
        ), array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    public static function delete_color( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $wpdb->delete( "{$wpdb->prefix}os_colors", array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    // ── Sizes ─────────────────────────────────────────────────────────
    public static function get_sizes() {
        global $wpdb;
        // Cast name to unsigned for natural sorting if they are numeric
        return rest_ensure_response( $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}os_sizes ORDER BY CAST(name AS UNSIGNED) ASC, name ASC" ) );
    }

    public static function create_size( $request ) {
        global $wpdb;
        $data = $request->get_json_params();
        $wpdb->insert( "{$wpdb->prefix}os_sizes", array(
            'name'    => sanitize_text_field( $data['name'] ?? '' ),
        ) );
        return rest_ensure_response( array( 'id' => $wpdb->insert_id ), 201 );
    }

    public static function update_size( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $data = $request->get_json_params();
        $wpdb->update( "{$wpdb->prefix}os_sizes", array(
            'name'    => sanitize_text_field( $data['name'] ?? '' ),
        ), array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    public static function delete_size( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $wpdb->delete( "{$wpdb->prefix}os_sizes", array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }
}
