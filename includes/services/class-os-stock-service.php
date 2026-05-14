<?php
/**
 * OS_Stock_Service — core stock business logic with integrity locks.
 *
 * Corrections applied:
 *  #1  academic_year_id INT (not VARCHAR)
 *  #4  quantity_available computed in PHP via os_qty_available() — no GENERATED column
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_Stock_Service {

    // Valid movement type constants
    const OPENING_BALANCE  = 'opening_balance';
    const PURCHASE_RECEIPT = 'purchase_receipt';
    const ISSUE_EMPLOYEE   = 'issue_employee';
    const ISSUE_STUDENT    = 'issue_student';
    const RETURN_EMPLOYEE  = 'return_employee';
    const RETURN_STUDENT   = 'return_student';
    const TRANSFER_OUT     = 'transfer_out';
    const TRANSFER_IN      = 'transfer_in';
    const ADJUSTMENT_ADD   = 'adjustment_add';
    const ADJUSTMENT_SUB   = 'adjustment_sub';
    const DAMAGE_LOSS      = 'damage_loss';
    const INVENTORY_COUNT  = 'inventory_count';

    public static function get_valid_movement_types() {
        return array(
            self::OPENING_BALANCE, self::PURCHASE_RECEIPT,
            self::ISSUE_EMPLOYEE, self::ISSUE_STUDENT,
            self::RETURN_EMPLOYEE, self::RETURN_STUDENT,
            self::TRANSFER_OUT, self::TRANSFER_IN,
            self::ADJUSTMENT_ADD, self::ADJUSTMENT_SUB,
            self::DAMAGE_LOSS, self::INVENTORY_COUNT,
        );
    }

    // ── Stock receipt (opening balance / purchase) ────────────────────────────

    /**
     * Record a stock receipt (stock in).
     *
     * @param  array $data  item_id, warehouse_id, quantity, movement_type, notes, academic_year_id (INT).
     * @return int|WP_Error  New movement ID or error.
     */
    public static function record_receipt( $data ) {
        global $wpdb;

        $item_id         = (int) ( $data['item_id'] ?? 0 );
        $warehouse_id    = (int) ( $data['warehouse_id'] ?? 0 );
        $quantity        = (int) ( $data['quantity'] ?? 0 );
        $movement_type   = sanitize_key( $data['movement_type'] ?? self::PURCHASE_RECEIPT );
        $notes           = sanitize_textarea_field( $data['notes'] ?? '' );
        // Correction #1: academic_year_id is INT
        $academic_year_id = (int) ( $data['academic_year_id'] ?? os_get_active_year_id() );

        if ( $item_id <= 0 || $warehouse_id <= 0 || $quantity <= 0 ) {
            return new WP_Error( 'invalid_data', __( 'Invalid item, warehouse, or quantity.', 'olama-stores' ) );
        }

        if ( ! in_array( $movement_type, array( self::PURCHASE_RECEIPT, self::OPENING_BALANCE ), true ) ) {
            return new WP_Error( 'invalid_type', __( 'Invalid movement type for receipt.', 'olama-stores' ) );
        }

        $wpdb->query( 'START TRANSACTION' );

        // Get old stock for audit
        $old_stock = self::get_stock_row( $item_id, $warehouse_id );

        // Upsert stock row
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}os_stock WHERE item_id = %d AND warehouse_id = %d",
            $item_id, $warehouse_id
        ) );

        if ( $existing ) {
            $ok = $wpdb->query( $wpdb->prepare(
                "UPDATE {$wpdb->prefix}os_stock SET quantity_on_hand = quantity_on_hand + %d, last_updated_at = %s WHERE id = %d",
                $quantity, current_time( 'mysql', 1 ), $existing
            ) );
        } else {
            $ok = $wpdb->insert( "{$wpdb->prefix}os_stock", array(
                'item_id'          => $item_id,
                'warehouse_id'     => $warehouse_id,
                'quantity_on_hand' => $quantity,
                'quantity_reserved'=> 0,
                'last_updated_at'  => current_time( 'mysql', 1 ),
            ) );
        }

        if ( false === $ok ) {
            $wpdb->query( 'ROLLBACK' );
            return new WP_Error( 'db_error', __( 'Failed to update stock.', 'olama-stores' ) );
        }

        // Insert movement record
        $wpdb->insert( "{$wpdb->prefix}os_stock_movements", array(
            'item_id'          => $item_id,
            'warehouse_id'     => $warehouse_id,
            'movement_type'    => $movement_type,
            'quantity'         => $quantity,
            'notes'            => $notes,
            'academic_year_id' => $academic_year_id ?: null,
            'performed_by'     => get_current_user_id(),
            'performed_at'     => current_time( 'mysql', 1 ),
        ) );
        $movement_id = $wpdb->insert_id;

        if ( ! $movement_id ) {
            $wpdb->query( 'ROLLBACK' );
            return new WP_Error( 'db_error', __( 'Failed to record movement.', 'olama-stores' ) );
        }

        $wpdb->query( 'COMMIT' );

        // Audit
        $new_stock = self::get_stock_row( $item_id, $warehouse_id );
        OS_Audit_Service::log( 'os_stock', $existing ?: $wpdb->insert_id, 'update', $old_stock, $new_stock );

        do_action( 'os_after_stock_receipt', $item_id, $warehouse_id, $quantity, $movement_id );

        return $movement_id;
    }

    // ── Issue items ───────────────────────────────────────────────────────────

    /**
     * Issue items to an employee or student.
     *
     * @param  array $data  assignee_type, assignee_id, item_id, warehouse_id, quantity_assigned,
     *                      academic_year_id (INT), assigned_date, notes, expected_return_date.
     * @return int|WP_Error  New assignment ID or error.
     */
    public static function issue_items( $data ) {
        global $wpdb;

        $assignee_type   = sanitize_key( $data['assignee_type'] ?? '' );
        // Correction #2: assignee_id is VARCHAR(50) — student_uid or WP user ID string
        $assignee_id     = sanitize_text_field( $data['assignee_id'] ?? '' );
        $item_id         = (int) ( $data['item_id'] ?? 0 );
        $warehouse_id    = (int) ( $data['warehouse_id'] ?? 0 );
        $quantity        = (int) ( $data['quantity_assigned'] ?? 0 );
        $academic_year_id = (int) ( $data['academic_year_id'] ?? os_get_active_year_id() );
        $assigned_date   = sanitize_text_field( $data['assigned_date'] ?? current_time( 'Y-m-d' ) );
        $notes           = sanitize_textarea_field( $data['notes'] ?? '' );
        $expected_return = ! empty( $data['expected_return_date'] ) ? sanitize_text_field( $data['expected_return_date'] ) : null;

        if ( ! in_array( $assignee_type, array( 'employee', 'student' ), true ) || empty( $assignee_id ) ) {
            return new WP_Error( 'invalid_assignee', __( 'Invalid assignee type or ID.', 'olama-stores' ) );
        }
        if ( $item_id <= 0 || $warehouse_id <= 0 || $quantity <= 0 ) {
            return new WP_Error( 'invalid_data', __( 'Invalid item, warehouse, or quantity.', 'olama-stores' ) );
        }

        $wpdb->query( 'START TRANSACTION' );

        // Lock: check available stock
        $stock = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}os_stock WHERE item_id = %d AND warehouse_id = %d FOR UPDATE",
            $item_id, $warehouse_id
        ) );

        // Correction #4: calculate available in PHP, not GENERATED column
        $available = $stock ? os_qty_available( $stock->quantity_on_hand, $stock->quantity_reserved ) : 0;

        if ( $available < $quantity ) {
            $wpdb->query( 'ROLLBACK' );
            return new WP_Error( 'insufficient_stock', sprintf(
                __( 'Insufficient stock. Available: %d, Requested: %d', 'olama-stores' ),
                $available, $quantity
            ) );
        }



        // Reserve stock (increase reserved quantity)
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}os_stock SET quantity_reserved = quantity_reserved + %d, last_updated_at = %s WHERE item_id = %d AND warehouse_id = %d",
            $quantity, current_time( 'mysql', 1 ), $item_id, $warehouse_id
        ) );

        // Create assignment
        $wpdb->insert( "{$wpdb->prefix}os_assignments", array(
            'assignee_type'       => $assignee_type,
            'assignee_id'         => $assignee_id,
            'item_id'             => $item_id,
            'warehouse_id'        => $warehouse_id,
            'quantity_assigned'   => $quantity,
            'quantity_returned'   => 0,
            'status'              => 'active',
            'assigned_date'       => $assigned_date,
            'expected_return_date'=> $expected_return,
            'notes'               => $notes,
            'academic_year_id'    => $academic_year_id ?: null,
            'assigned_by'         => get_current_user_id(),
            'created_at'          => current_time( 'mysql', 1 ),
        ) );
        $assignment_id = $wpdb->insert_id;

        if ( ! $assignment_id ) {
            $wpdb->query( 'ROLLBACK' );
            return new WP_Error( 'db_error', __( 'Failed to create assignment.', 'olama-stores' ) );
        }

        // Stock movement
        $movement_type = ( $assignee_type === 'employee' ) ? self::ISSUE_EMPLOYEE : self::ISSUE_STUDENT;
        $wpdb->insert( "{$wpdb->prefix}os_stock_movements", array(
            'item_id'          => $item_id,
            'warehouse_id'     => $warehouse_id,
            'movement_type'    => $movement_type,
            'quantity'         => $quantity,
            'reference_id'     => $assignment_id,
            'reference_type'   => 'assignment',
            'notes'            => $notes,
            'academic_year_id' => $academic_year_id ?: null,
            'performed_by'     => get_current_user_id(),
            'performed_at'     => current_time( 'mysql', 1 ),
        ) );

        $wpdb->query( 'COMMIT' );

        OS_Audit_Service::log( 'os_assignments', $assignment_id, 'create', null, array(
            'assignee_type' => $assignee_type,
            'assignee_id'   => $assignee_id,
            'item_id'       => $item_id,
            'quantity'      => $quantity,
        ) );

        do_action( 'os_after_issue_items', $assignment_id, $item_id, $warehouse_id, $quantity );

        return $assignment_id;
    }

    // ── Process return ────────────────────────────────────────────────────────

    /**
     * Process a return of items from an assignee.
     *
     * @param  array $data  assignment_id, quantity, return_condition, return_date, notes.
     * @return int|WP_Error  Return record ID or error.
     */
    public static function process_return( $data ) {
        global $wpdb;

        $assignment_id    = (int) ( $data['assignment_id'] ?? 0 );
        $quantity         = (int) ( $data['quantity'] ?? 0 );
        $return_condition = sanitize_key( $data['return_condition'] ?? 'good' );
        $return_date      = sanitize_text_field( $data['return_date'] ?? current_time( 'Y-m-d' ) );
        $notes            = sanitize_textarea_field( $data['notes'] ?? '' );

        if ( $assignment_id <= 0 || $quantity <= 0 ) {
            return new WP_Error( 'invalid_data', __( 'Invalid assignment or quantity.', 'olama-stores' ) );
        }

        $assignment = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}os_assignments WHERE id = %d", $assignment_id
        ) );

        if ( ! $assignment ) {
            return new WP_Error( 'not_found', __( 'Assignment not found.', 'olama-stores' ) );
        }

        $remaining = (int) $assignment->quantity_assigned - (int) $assignment->quantity_returned;
        if ( $quantity > $remaining ) {
            return new WP_Error( 'over_return', sprintf(
                __( 'Cannot return %d — only %d outstanding.', 'olama-stores' ),
                $quantity, $remaining
            ) );
        }

        $wpdb->query( 'START TRANSACTION' );

        // Insert return record
        $wpdb->insert( "{$wpdb->prefix}os_assignment_returns", array(
            'assignment_id'    => $assignment_id,
            'quantity'         => $quantity,
            'return_condition' => $return_condition,
            'return_date'      => $return_date,
            'notes'            => $notes,
            'processed_by'     => get_current_user_id(),
            'created_at'       => current_time( 'mysql', 1 ),
        ) );
        $return_id = $wpdb->insert_id;

        if ( ! $return_id ) {
            $wpdb->query( 'ROLLBACK' );
            return new WP_Error( 'db_error', __( 'Failed to record return.', 'olama-stores' ) );
        }

        // Update assignment
        $new_returned = (int) $assignment->quantity_returned + $quantity;
        $new_remaining = (int) $assignment->quantity_assigned - $new_returned;
        $status = $new_remaining <= 0 ? 'fully_returned' : 'partially_returned';
        if ( $return_condition === 'lost' ) { $status = 'lost'; }

        $wpdb->update( "{$wpdb->prefix}os_assignments",
            array( 'quantity_returned' => $new_returned, 'status' => $status ),
            array( 'id' => $assignment_id )
        );

        // Update stock — release reserved, add back to on_hand if condition != lost
        $movement_type = ( $assignment->assignee_type === 'employee' ) ? self::RETURN_EMPLOYEE : self::RETURN_STUDENT;

        if ( $return_condition === 'lost' ) {
            // Remove from reserved only (item is lost, not back in stock)
            $wpdb->query( $wpdb->prepare(
                "UPDATE {$wpdb->prefix}os_stock SET quantity_reserved = GREATEST(0, quantity_reserved - %d), last_updated_at = %s WHERE item_id = %d AND warehouse_id = %d",
                $quantity, current_time( 'mysql', 1 ), $assignment->item_id, $assignment->warehouse_id
            ) );
            // Additional damage_loss movement for audit
            $wpdb->insert( "{$wpdb->prefix}os_stock_movements", array(
                'item_id'       => $assignment->item_id,
                'warehouse_id'  => $assignment->warehouse_id,
                'movement_type' => self::DAMAGE_LOSS,
                'quantity'      => $quantity,
                'reference_id'  => $return_id,
                'reference_type'=> 'return',
                'notes'         => 'Item marked as lost during return.',
                'academic_year_id' => $assignment->academic_year_id,
                'performed_by'  => get_current_user_id(),
                'performed_at'  => current_time( 'mysql', 1 ),
            ) );
        } else {
            // Good or damaged — put back in stock
            $wpdb->query( $wpdb->prepare(
                "UPDATE {$wpdb->prefix}os_stock
                 SET quantity_on_hand  = quantity_on_hand + %d,
                     quantity_reserved = GREATEST(0, quantity_reserved - %d),
                     last_updated_at   = %s
                 WHERE item_id = %d AND warehouse_id = %d",
                $quantity, $quantity, current_time( 'mysql', 1 ), $assignment->item_id, $assignment->warehouse_id
            ) );
        }

        // Return movement
        $wpdb->insert( "{$wpdb->prefix}os_stock_movements", array(
            'item_id'          => $assignment->item_id,
            'warehouse_id'     => $assignment->warehouse_id,
            'movement_type'    => $movement_type,
            'quantity'         => $quantity,
            'reference_id'     => $return_id,
            'reference_type'   => 'return',
            'notes'            => $notes,
            'academic_year_id' => $assignment->academic_year_id,
            'performed_by'     => get_current_user_id(),
            'performed_at'     => current_time( 'mysql', 1 ),
        ) );

        $wpdb->query( 'COMMIT' );

        OS_Audit_Service::log( 'os_assignment_returns', $return_id, 'create', null, array(
            'assignment_id' => $assignment_id, 'quantity' => $quantity, 'condition' => $return_condition,
        ) );

        do_action( 'os_after_process_return', $return_id, $assignment_id, $quantity, $return_condition );

        return $return_id;
    }

    // ── Manual adjustment ─────────────────────────────────────────────────────

    /**
     * Post a manual stock adjustment (requires os_adjust_stock capability).
     *
     * @param  array $data  item_id, warehouse_id, quantity (signed: + or -), notes, academic_year_id.
     * @return int|WP_Error  Movement ID or error.
     */
    public static function manual_adjustment( $data ) {
        global $wpdb;

        if ( ! OS_Roles::can( 'os_adjust_stock' ) ) {
            return new WP_Error( 'unauthorized', __( 'Insufficient permissions for stock adjustment.', 'olama-stores' ) );
        }

        $item_id         = (int) ( $data['item_id'] ?? 0 );
        $warehouse_id    = (int) ( $data['warehouse_id'] ?? 0 );
        $quantity        = (int) ( $data['quantity'] ?? 0 );
        $notes           = sanitize_textarea_field( $data['notes'] ?? '' );
        $academic_year_id= (int) ( $data['academic_year_id'] ?? os_get_active_year_id() );

        if ( $item_id <= 0 || $warehouse_id <= 0 || $quantity === 0 ) {
            return new WP_Error( 'invalid_data', __( 'Provide a valid item, warehouse, and non-zero quantity.', 'olama-stores' ) );
        }
        if ( empty( $notes ) ) {
            return new WP_Error( 'notes_required', __( 'Adjustment reason is required.', 'olama-stores' ) );
        }

        $movement_type = $quantity > 0 ? self::ADJUSTMENT_ADD : self::ADJUSTMENT_SUB;
        $abs_qty       = abs( $quantity );

        $wpdb->query( 'START TRANSACTION' );

        $stock = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}os_stock WHERE item_id = %d AND warehouse_id = %d FOR UPDATE",
            $item_id, $warehouse_id
        ) );

        if ( $quantity < 0 && ( ! $stock || ( $stock->quantity_on_hand + $quantity ) < 0 ) ) {
            $wpdb->query( 'ROLLBACK' );
            return new WP_Error( 'below_zero', __( 'Adjustment would make stock negative.', 'olama-stores' ) );
        }

        if ( $stock ) {
            $wpdb->query( $wpdb->prepare(
                "UPDATE {$wpdb->prefix}os_stock SET quantity_on_hand = quantity_on_hand + %d, last_updated_at = %s WHERE id = %d",
                $quantity, current_time( 'mysql', 1 ), $stock->id
            ) );
        } else {
            $wpdb->insert( "{$wpdb->prefix}os_stock", array(
                'item_id' => $item_id, 'warehouse_id' => $warehouse_id,
                'quantity_on_hand' => $abs_qty, 'quantity_reserved' => 0,
                'last_updated_at' => current_time( 'mysql', 1 ),
            ) );
        }

        $wpdb->insert( "{$wpdb->prefix}os_stock_movements", array(
            'item_id'          => $item_id,
            'warehouse_id'     => $warehouse_id,
            'movement_type'    => $movement_type,
            'quantity'         => $abs_qty,
            'notes'            => $notes,
            'academic_year_id' => $academic_year_id ?: null,
            'performed_by'     => get_current_user_id(),
            'performed_at'     => current_time( 'mysql', 1 ),
        ) );
        $movement_id = $wpdb->insert_id;

        $wpdb->query( 'COMMIT' );

        OS_Audit_Service::log( 'os_stock', $item_id, 'update', array( 'qty' => $stock ? $stock->quantity_on_hand : 0 ),
            array( 'qty' => ( $stock ? $stock->quantity_on_hand : 0 ) + $quantity, 'type' => $movement_type ) );

        return $movement_id;
    }

    /**
     * Reverse a stock movement (Delete addition). Admin only.
     */
    public static function reverse_movement( $movement_id ) {
        global $wpdb;

        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'unauthorized', __( 'Only admins can reverse movements.', 'olama-stores' ) );
        }

        $m = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}os_stock_movements WHERE id = %d", $movement_id
        ) );

        if ( ! $m ) {
            return new WP_Error( 'not_found', __( 'Movement not found.', 'olama-stores' ) );
        }

        // Only allow reversing additions for now (as per user request)
        $additions = array( self::PURCHASE_RECEIPT, self::OPENING_BALANCE, self::ADJUSTMENT_ADD );
        if ( ! in_array( $m->movement_type, $additions, true ) ) {
            return new WP_Error( 'invalid_type', __( 'Only stock additions can be reversed from this screen.', 'olama-stores' ) );
        }

        $wpdb->query( 'START TRANSACTION' );

        // Lock stock
        $stock = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}os_stock WHERE item_id = %d AND warehouse_id = %d FOR UPDATE",
            $m->item_id, $m->warehouse_id
        ) );

        if ( ! $stock || $stock->quantity_on_hand < $m->quantity ) {
            $wpdb->query( 'ROLLBACK' );
            return new WP_Error( 'insufficient_stock', __( 'Cannot reverse: resulting stock would be negative.', 'olama-stores' ) );
        }

        // Subtract from on_hand
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}os_stock SET quantity_on_hand = quantity_on_hand - %d, last_updated_at = %s WHERE id = %d",
            $m->quantity, current_time( 'mysql', 1 ), $stock->id
        ) );

        // Delete movement
        $wpdb->delete( "{$wpdb->prefix}os_stock_movements", array( 'id' => $movement_id ) );

        $wpdb->query( 'COMMIT' );

        OS_Audit_Service::log( 'os_stock_movements', $movement_id, 'reverse_delete', $m, null );

        return true;
    }

    // ── Stock query helpers ───────────────────────────────────────────────────

    /**
     * Get a single stock row. Adds quantity_available in PHP.
     * Correction #4: no GENERATED column — calculated here.
     */
    public static function get_stock_row( $item_id, $warehouse_id ) {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}os_stock WHERE item_id = %d AND warehouse_id = %d",
            $item_id, $warehouse_id
        ) );
        if ( $row ) {
            $row->quantity_available = os_qty_available( $row->quantity_on_hand, $row->quantity_reserved );
        }
        return $row;
    }

    /**
     * Get all stock across all warehouses, with quantity_available computed.
     * Correction #4: SELECT adds computed column in SQL for reporting convenience.
     *
     * @param  array $args  Optional filters: warehouse_id, category_id, academic_year_id, low_stock_only.
     * @return array
     */
    public static function get_stock_levels( $args = array() ) {
        global $wpdb;

        $where   = array( '1=1' );
        $params  = array();

        if ( ! empty( $args['warehouse_id'] ) ) {
            $where[]  = 's.warehouse_id = %d';
            $params[] = (int) $args['warehouse_id'];
        }
        if ( ! empty( $args['category_id'] ) ) {
            $where[]  = 'i.category_id = %d';
            $params[]  = (int) $args['category_id'];
        }

        $where_sql = implode( ' AND ', $where );

        $sql = "SELECT
                    s.*,
                    (s.quantity_on_hand - s.quantity_reserved) AS quantity_available,
                    i.name, i.name_ar, i.sku, i.barcode, i.min_stock_level, i.provider_id,
                    i.category_id,
                    c.name AS category_name,
                    u.name AS unit_name, u.symbol AS unit_symbol,
                    w.name AS warehouse_name
                FROM {$wpdb->prefix}os_stock s
                JOIN {$wpdb->prefix}os_items i ON s.item_id = i.id
                LEFT JOIN {$wpdb->prefix}os_categories c ON i.category_id = c.id
                LEFT JOIN {$wpdb->prefix}os_units u ON i.unit_id = u.id
                LEFT JOIN {$wpdb->prefix}os_warehouses w ON s.warehouse_id = w.id
                WHERE $where_sql
                ORDER BY i.name ASC";

        $results = $params
            ? $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) )
            : $wpdb->get_results( $sql );

        // Apply low_stock_only filter in PHP (avoids complex SQL with computed column in WHERE)
        if ( ! empty( $args['low_stock_only'] ) ) {
            $results = array_filter( $results, function ( $row ) {
                return (int) $row->quantity_available <= (int) $row->min_stock_level;
            } );
        }

        return array_values( $results );
    }

    public static function count_low_stock() {
        global $wpdb;
        $sql = "SELECT COUNT(*)
                FROM {$wpdb->prefix}os_stock s
                JOIN {$wpdb->prefix}os_items i ON s.item_id = i.id
                WHERE (s.quantity_on_hand - s.quantity_reserved) <= i.min_stock_level";
        return (int) $wpdb->get_var( $sql );
    }

    /** Run daily low-stock check and send admin notice. */
    public static function run_low_stock_check() {
        $low = self::get_stock_levels( array( 'low_stock_only' => true ) );
        if ( empty( $low ) ) { return; }

        $admin_email = get_option( 'admin_email' );
        $subject     = sprintf( __( '[%s] Olama Stores: Low Stock Alert', 'olama-stores' ), get_bloginfo( 'name' ) );
        $lines       = array( __( 'The following items are at or below minimum stock level:', 'olama-stores' ), '' );
        foreach ( $low as $row ) {
            $lines[] = sprintf( '• %s (%s) — %s: %d %s',
                $row->name, $row->sku, $row->warehouse_name,
                $row->quantity_available, $row->unit_symbol
            );
        }
        wp_mail( $admin_email, $subject, implode( "\n", $lines ) );

        // Also store as transient for admin notice
        set_transient( 'os_low_stock_items', $low, DAY_IN_SECONDS );
    }
}
