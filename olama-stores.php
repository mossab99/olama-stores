<?php
/**
 * Plugin Name: Olama Stores
 * Plugin URI:  https://olama.online/olama-stores
 * Description: School warehouse management for Olama School System. Tracks inventory, stock movements, and employee/student item assignments.
 * Version:     1.0.50
 * Author:      د. مصعب الحنيطي
 * Text Domain: olama-stores
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'OS_VERSION',           '1.1.0' );
define( 'OS_PATH',              plugin_dir_path( __FILE__ ) );
define( 'OS_URL',               plugin_dir_url( __FILE__ ) );
define( 'OS_FILE',              __FILE__ );
define( 'OS_BASENAME',          plugin_basename( __FILE__ ) );
define( 'OS_MIN_SCHOOL_VER',    '2.4.0' );

// ── Dependency guard ──────────────────────────────────────────────────────────
function os_check_deps() {
    if ( ! class_exists( 'Olama_School_DB' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>Olama Stores:</strong> '
                . esc_html__( 'Requires the Olama School System plugin to be active.', 'olama-stores' )
                . '</p></div>';
        } );
        return false;
    }
    if ( defined( 'OLAMA_SCHOOL_VERSION' ) && version_compare( OLAMA_SCHOOL_VERSION, OS_MIN_SCHOOL_VER, '<' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>'
                . sprintf( esc_html__( 'Olama Stores requires Olama School System %s or higher.', 'olama-stores' ), OS_MIN_SCHOOL_VER )
                . '</p></div>';
        } );
        return false;
    }
    return true;
}

// ── Load includes ─────────────────────────────────────────────────────────────
function os_load_includes() {
    require_once OS_PATH . 'includes/class-os-activator.php';
    require_once OS_PATH . 'includes/class-os-roles.php';
    require_once OS_PATH . 'includes/class-os-helpers.php';
    require_once OS_PATH . 'includes/integrations/class-os-school-integration.php';
    require_once OS_PATH . 'includes/services/class-os-audit-service.php';
    require_once OS_PATH . 'includes/services/class-os-stock-service.php';
    require_once OS_PATH . 'includes/services/class-os-export-service.php';
    require_once OS_PATH . 'includes/models/class-os-item.php';
    require_once OS_PATH . 'includes/models/class-os-stock.php';
    require_once OS_PATH . 'includes/models/class-os-assignment.php';
    require_once OS_PATH . 'includes/models/class-os-movement.php';
    require_once OS_PATH . 'includes/models/class-os-warehouse.php';
    require_once OS_PATH . 'includes/api/class-os-api-items.php';
    require_once OS_PATH . 'includes/api/class-os-api-stock.php';
    require_once OS_PATH . 'includes/api/class-os-api-assignments.php';
    require_once OS_PATH . 'includes/api/class-os-api-reports.php';
    require_once OS_PATH . 'includes/api/class-os-api-books-withdrawal.php';
    require_once OS_PATH . 'includes/ajax/class-os-estimation-ajax.php';
    require_once OS_PATH . 'includes/models/class-os-uniform-size.php';
    require_once OS_PATH . 'includes/ajax/class-os-uniform-size-ajax.php';
    OS_Estimation_Ajax::register();
    OS_Uniform_Size_Ajax::register();
    if ( is_admin() ) {
        require_once OS_PATH . 'admin/class-os-admin.php';
    }
}

// ── Activation ────────────────────────────────────────────────────────────────
register_activation_hook( __FILE__, 'os_activate' );
function os_activate() {
    ob_start();
    try {
        if ( ! class_exists( 'Olama_School_DB' ) ) {
            deactivate_plugins( OS_BASENAME );
            wp_die( 'Olama Stores requires Olama School System to be active.', 'Dependency Error', array( 'back_link' => true ) );
        }
        if ( ! class_exists( 'OS_Activator' ) ) { require_once OS_PATH . 'includes/class-os-activator.php'; }
        if ( ! class_exists( 'OS_Roles' ) )     { require_once OS_PATH . 'includes/class-os-roles.php'; }
        OS_Activator::create_tables();
        OS_Roles::add_roles_and_caps();
        update_option( 'os_version', OS_VERSION );
        flush_rewrite_rules();
    } catch ( Exception $e ) {
        error_log( 'Olama Stores Activation Error: ' . $e->getMessage() );
    }
    if ( ob_get_length() > 0 ) { ob_end_clean(); }
}

// ── Deactivation ──────────────────────────────────────────────────────────────
register_deactivation_hook( __FILE__, 'os_deactivate' );
function os_deactivate() {
    wp_clear_scheduled_hook( 'os_low_stock_check' );
    flush_rewrite_rules();
}

// ── Init ──────────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', 'os_init', 10 );
function os_init() {
    if ( ! os_check_deps() ) { return; }
    os_load_includes();

    // Schema migration on version bump
    $installed = get_option( 'os_version', '0' );
    if ( version_compare( $installed, OS_VERSION, '<' ) ) {
        OS_Activator::create_tables();
        OS_Roles::add_roles_and_caps();
        update_option( 'os_version', OS_VERSION );
    }

    add_action( 'rest_api_init', function () {
        OS_API_Items::register_routes();
        OS_API_Stock::register_routes();
        OS_API_Assignments::register_routes();
        OS_API_Reports::register_routes();
        OS_API_Books_Withdrawal::register_routes();
    } );

    if ( is_admin() ) { new OS_Admin(); }

    // WP-Cron low-stock check (daily)
    if ( ! wp_next_scheduled( 'os_low_stock_check' ) ) {
        wp_schedule_event( time(), 'daily', 'os_low_stock_check' );
    }
    add_action( 'os_low_stock_check', array( 'OS_Stock_Service', 'run_low_stock_check' ) );

    load_plugin_textdomain( 'olama-stores', false, dirname( OS_BASENAME ) . '/languages' );
}

// ── Global helpers ────────────────────────────────────────────────────────────
/** Get active academic year ID from Olama School (Correction #1: returns INT, not VARCHAR). */
function os_get_active_year_id() {
    if ( class_exists( 'Olama_School_Academic' ) ) {
        $year = Olama_School_Academic::get_active_year();
        return $year ? (int) $year->id : null;
    }
    return null;
}

/** Get active academic year label. */
function os_get_active_year_name() {
    if ( class_exists( 'Olama_School_Academic' ) ) {
        $year = Olama_School_Academic::get_active_year();
        return $year ? $year->year_name : '';
    }
    return '';
}

/** Calculate quantity_available — replaces removed GENERATED column (Correction #4). */
function os_qty_available( $on_hand, $reserved ) {
    return max( 0, (int) $on_hand - (int) $reserved );
}

/** Calculate count variance — replaces removed GENERATED column (Correction #4). */
function os_count_variance( $counted, $system ) {
    return (int) $counted - (int) $system;
}

// ── Translation Filter ────────────────────────────────────────────────────────
function os_translate_strings( $translated, $text, $domain ) {
    if ( $domain === 'olama-stores' && class_exists( 'OS_Helpers' ) && OS_Helpers::is_arabic() ) {
        return OS_Helpers::translate( $text );
    }
    return $translated;
}
add_filter( 'gettext', 'os_translate_strings', 10, 3 );
