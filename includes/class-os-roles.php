<?php
/**
 * OS_Roles — registers Olama Stores WordPress roles and capabilities.
 *
 * Role names use os_ prefix, capability names use os_ prefix.
 * Olama School's Olama_School_Permissions::init() only iterates its own known
 * role list, so these new roles are safe and non-colliding.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_Roles {

    /**
     * All capabilities grouped by feature.
     */
    public static function get_caps() {
        return array(
            // Items
            'os_view_items'           => __( 'View Item Registry', 'olama-stores' ),
            'os_manage_items'         => __( 'Add / Edit Items', 'olama-stores' ),
            'os_delete_items'         => __( 'Delete Items', 'olama-stores' ),
            // Stock
            'os_view_stock'           => __( 'View Stock Levels', 'olama-stores' ),
            'os_receive_stock'        => __( 'Record Stock Receipt', 'olama-stores' ),
            'os_adjust_stock'         => __( 'Manual Stock Adjustment (elevated)', 'olama-stores' ),
            // Assignments
            'os_process_assignments'  => __( 'Issue & Return Items', 'olama-stores' ),
            'os_view_assignments'     => __( 'View Assignments', 'olama-stores' ),
            // Inventory count
            'os_run_inventory_count'  => __( 'Run Inventory Count', 'olama-stores' ),
            // Transfers
            'os_manage_transfers'     => __( 'Manage Warehouse Transfers', 'olama-stores' ),
            // Reports
            'os_view_reports'         => __( 'View Reports', 'olama-stores' ),
            // Settings
            'os_manage_settings'      => __( 'Manage Stores Settings', 'olama-stores' ),
            // Warehouses
            'os_manage_warehouses'    => __( 'Manage Warehouses', 'olama-stores' ),
            // Audit log
            'os_view_audit_log'       => __( 'View Audit Log', 'olama-stores' ),
        );
    }

    /**
     * Add custom roles and assign capabilities.
     * Called on activation and on version bump.
     */
    public static function add_roles_and_caps() {
        // ── Register custom roles ─────────────────────────────────────────────
        if ( ! get_role( 'os_warehouse_manager' ) ) {
            add_role( 'os_warehouse_manager', __( 'Warehouse Manager', 'olama-stores' ), array( 'read' => true ) );
        }
        if ( ! get_role( 'os_warehouse_staff' ) ) {
            add_role( 'os_warehouse_staff', __( 'Warehouse Staff', 'olama-stores' ), array( 'read' => true ) );
        }
        if ( ! get_role( 'os_viewer' ) ) {
            add_role( 'os_viewer', __( 'Stores Viewer', 'olama-stores' ), array( 'read' => true ) );
        }

        $all_caps = array_keys( self::get_caps() );

        // Administrator — all caps
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            foreach ( $all_caps as $cap ) { $admin->add_cap( $cap ); }
        }

        // Olama School supervisor — all caps (they supervise everything)
        $supervisor = get_role( 'supervisor' );
        if ( $supervisor ) {
            foreach ( $all_caps as $cap ) { $supervisor->add_cap( $cap ); }
        }

        // os_warehouse_manager — full inventory operations
        $manager_caps = $all_caps; // All caps
        $manager = get_role( 'os_warehouse_manager' );
        if ( $manager ) {
            foreach ( $manager_caps as $cap ) { $manager->add_cap( $cap ); }
        }

        // os_warehouse_staff — operational caps, no item master edit / settings / adjust
        $staff_caps = array(
            'os_view_items',
            'os_view_stock',
            'os_receive_stock',
            'os_process_assignments',
            'os_view_assignments',
            'os_view_reports',
        );
        $staff = get_role( 'os_warehouse_staff' );
        if ( $staff ) {
            foreach ( $all_caps as $cap ) { $staff->remove_cap( $cap ); }
            foreach ( $staff_caps as $cap ) { $staff->add_cap( $cap ); }
        }

        // os_viewer — read-only
        $viewer_caps = array(
            'os_view_items',
            'os_view_stock',
            'os_view_assignments',
            'os_view_reports',
        );
        $viewer = get_role( 'os_viewer' );
        if ( $viewer ) {
            foreach ( $all_caps as $cap ) { $viewer->remove_cap( $cap ); }
            foreach ( $viewer_caps as $cap ) { $viewer->add_cap( $cap ); }
        }
    }

    /**
     * Remove all Olama Stores capabilities and custom roles.
     * Called on uninstall (not deactivation — data is kept on deactivation).
     */
    public static function remove_roles_and_caps() {
        $all_caps = array_keys( self::get_caps() );
        foreach ( array( 'administrator', 'supervisor', 'os_warehouse_manager', 'os_warehouse_staff', 'os_viewer' ) as $role_name ) {
            $role = get_role( $role_name );
            if ( $role ) {
                foreach ( $all_caps as $cap ) { $role->remove_cap( $cap ); }
            }
        }
        remove_role( 'os_warehouse_manager' );
        remove_role( 'os_warehouse_staff' );
        remove_role( 'os_viewer' );
    }

    /**
     * Check if current user has a given Olama Stores capability.
     * Admins always pass.
     */
    public static function can( $cap, $user_id = null ) {
        if ( ! $user_id ) { $user_id = get_current_user_id(); }
        if ( ! $user_id ) { return false; }
        if ( user_can( $user_id, 'manage_options' ) ) { return true; }
        // Olama School supervisors get full Stores access
        $user = get_userdata( $user_id );
        if ( $user && in_array( 'supervisor', (array) $user->roles, true ) ) { return true; }
        return user_can( $user_id, $cap );
    }
}
