<?php
/**
 * OS_Admin — registers admin menu and enqueues assets.
 */
if (!defined('ABSPATH')) {
    exit;
}

class OS_Admin
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'register_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_head', array($this, 'print_head_scripts'));
        add_action('admin_notices', array($this, 'low_stock_notice'));
    }

    /**
     * Print critical boot scripts in <head> to prevent "wp is not defined".
     */
    public function print_head_scripts()
    {
        if (!isset($_GET['page']) || strpos($_GET['page'], 'olama-stores') === false) {
            return;
        }

        // 1. Guarantee window.wp and window.olamaStores exist immediately
        $config = array(
            'restBase'       => esc_url_raw( rest_url() ),
            'apiRoot'        => esc_url_raw( rest_url( 'olama-stores/v1' ) ),
            'nonce'          => wp_create_nonce( 'wp_rest' ),
            'activeYearId'   => os_get_active_year_id(),
            'activeYearName' => os_get_active_year_name(),
            'activeYearStart' => ( function() {
                if ( class_exists( 'Olama_School_Academic' ) ) {
                    $year = Olama_School_Academic::get_active_year();
                    if ( $year && ! empty( $year->start_date ) ) {
                        return $year->start_date; // expects 'Y-m-d' format
                    }
                    // Fallback: derive from year_name if it looks like "2024-2025"
                    if ( $year && preg_match( '/^(\d{4})/', $year->year_name, $m ) ) {
                        return $m[1] . '-09-01'; // assume Sep 1 school year start
                    }
                }
                return gmdate( 'Y' ) . '-01-01';
            } )(),
            'pluginUrl' => OS_URL,
            'caps' => array(
                'manage_items'      => OS_Roles::can('os_manage_items'),
                'adjust_stock'      => OS_Roles::can('os_adjust_stock'),
                'run_count'         => OS_Roles::can('os_run_inventory_count'),
                'manage_settings'   => OS_Roles::can('os_manage_settings'),
                'manage_warehouses' => OS_Roles::can('os_manage_warehouses'),
                'is_admin'          => current_user_can('manage_options'),
            ),
        );

        echo '<script type="text/javascript">
            window.olamaStores = ' . wp_json_encode($config) . ';
            window.wp = window.wp || {};
        </script>';
    }

    public function register_menus()
    {
        if (!OS_Roles::can('os_view_items')) {
            return;
        }

        add_menu_page(
            __('Olama Stores', 'olama-stores'),
            __('Olama Stores', 'olama-stores'),
            'os_view_items',
            'olama-stores',
            array($this, 'page_dashboard'),
            'dashicons-archive',
            30
        );

        add_submenu_page('olama-stores', __('Dashboard', 'olama-stores'), __('Dashboard', 'olama-stores'), 'os_view_items', 'olama-stores', array($this, 'page_dashboard'));
        add_submenu_page('olama-stores', __('Item Registry', 'olama-stores'), __('Item Registry', 'olama-stores'), 'os_view_items', 'olama-stores-items', array($this, 'page_items'));
        add_submenu_page('olama-stores', __('Add Items', 'olama-stores'), __('Add Items', 'olama-stores'), 'os_manage_items', 'olama-stores-add-items', array($this, 'page_add_items'));
        add_submenu_page('olama-stores', __('Stock', 'olama-stores'), __('Stock', 'olama-stores'), 'os_view_stock', 'olama-stores-stock', array($this, 'page_stock'));
        add_submenu_page('olama-stores', __('Employee Custody', 'olama-stores'), __('Employee Custody', 'olama-stores'), 'os_view_assignments', 'olama-stores-assignments', array($this, 'page_assignments'));
        add_submenu_page('olama-stores', __('Student Withdrawals', 'olama-stores'), __('Student Withdrawals', 'olama-stores'), 'os_view_assignments', 'olama-stores-withdrawals', array($this, 'page_withdrawals'));
        add_submenu_page('olama-stores', __('Books Withdrawal', 'olama-stores'), __('Books Withdrawal', 'olama-stores'), 'os_view_assignments', 'olama-stores-books-withdrawal', array($this, 'page_books_withdrawal'));
        add_submenu_page('olama-stores', __('Reports', 'olama-stores'), __('Reports', 'olama-stores'), 'os_view_reports', 'olama-stores-reports', array($this, 'page_reports'));
        add_submenu_page('olama-stores', __('Order Estimation', 'olama-stores'), __('Order Estimation', 'olama-stores'), 'os_manage_order_estimation', 'olama-stores-order-estimation', array($this, 'page_order_estimation'));

        // REC-06: Inventory Count page
        if ( OS_Roles::can( 'os_run_inventory_count' ) ) {
            add_submenu_page('olama-stores', __('Inventory Count', 'olama-stores'), __('Inventory Count', 'olama-stores'), 'os_run_inventory_count', 'olama-stores-inventory-count', array($this, 'page_inventory_count'));
        }

        if (OS_Roles::can('os_manage_settings')) {
            add_submenu_page('olama-stores', __('Settings', 'olama-stores'), __('Settings', 'olama-stores'), 'os_manage_settings', 'olama-stores-settings', array($this, 'page_settings'));
        }
    }

    public function enqueue_assets($hook)
    {
        if (strpos($hook, 'olama-stores') === false) {
            return;
        }

        wp_enqueue_style(
            'olama-stores-admin',
            OS_URL . 'admin/assets/css/os-admin.css',
            array(),
            OS_Helpers::asset_version( 'admin/assets/css/os-admin.css' )
        );

        wp_enqueue_script(
            'olama-stores-admin',
            OS_URL . 'admin/assets/js/os-admin.js',
            array('jquery', 'wp-api-fetch', 'wp-url'),
            OS_Helpers::asset_version( 'admin/assets/js/os-admin.js' ),
            false
        );

        // Student size registration JS — only on estimation page
        if ( isset( $_GET['page'] ) && $_GET['page'] === 'olama-stores-order-estimation' ) {
            wp_enqueue_script(
                'os-student-size-reg',
                OS_URL . 'admin/assets/js/os-student-size-registration.js',
                array( 'jquery' ),
                OS_Helpers::asset_version( 'admin/assets/js/os-student-size-registration.js' ),
                true
            );
        }
    }

    // ── Page renderers ────────────────────────────────────────────────────────
    public function page_dashboard()
    {
        $this->render_view('dashboard');
    }
    public function page_items()
    {
        $this->render_view('items');
    }
    public function page_add_items()
    {
        $this->render_view('add-items');
    }
    public function page_stock()
    {
        $this->render_view('stock');
    }
    public function page_assignments()
    {
        $this->render_view('assignments');
    }
    public function page_withdrawals()
    {
        $this->render_view('withdrawals');
    }
    public function page_books_withdrawal()
    {
        $this->render_view('books-withdrawal');
    }
    public function page_reports()
    {
        $this->render_view('reports');
    }
    public function page_order_estimation()
    {
        $this->render_view('order-estimation');
    }
    // REC-06
    public function page_inventory_count()
    {
        $this->render_view('inventory-count');
    }
    public function page_settings()
    {
        $this->render_view('settings');
    }

    private function render_view($name)
    {
        $file = OS_PATH . "admin/views/{$name}.php";
        if (file_exists($file)) {
            include $file;
        } else {
            echo '<div class="wrap"><h1>' . esc_html(ucfirst($name)) . '</h1><p>' . esc_html__('View file not found.', 'olama-stores') . '</p></div>';
        }
    }

    // ── Low-stock admin notice ────────────────────────────────────────────────
    public function low_stock_notice()
    {
        // Only show this notice on Olama Stores pages
        if (!isset($_GET['page']) || strpos($_GET['page'], 'olama-stores') === false) {
            return;
        }

        $low = get_transient('os_low_stock_items');
        if (empty($low)) {
            return;
        }
        $count = count($low);
        printf(
            '<div class="notice notice-warning is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
            sprintf(esc_html(_n('%d item is below minimum stock level.', '%d items are below minimum stock level.', $count, 'olama-stores')), $count),
            esc_url(admin_url('admin.php?page=olama-stores-stock&filter=low_stock')),
            esc_html__('View low stock items →', 'olama-stores')
        );
    }
}
