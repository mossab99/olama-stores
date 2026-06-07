<?php
/**
 * OS_Activator — creates / updates all Olama Stores database tables.
 *
 * Corrections applied vs. spec:
 *  #1  academic_year VARCHAR  → academic_year_id INT UNSIGNED FK
 *  #2  assignee_id INT       → assignee_id VARCHAR(50) (stores student_uid or WP user ID as string)
 *  #4  GENERATED columns     → removed; calculated at query time in PHP
 *  #5  JSON column type      → LONGTEXT (dbDelta + MySQL <5.7.8 safe)
 *  ENUMs                    → VARCHAR (dbDelta compatibility)
 *  `condition`              → `return_condition` (avoids MySQL reserved word)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_Activator {

    public static function create_tables() {
        global $wpdb;
        $p   = $wpdb->prefix;
        $col = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Force migration for provider_id if dbDelta missed it
        if ( ! $wpdb->get_var( "SHOW COLUMNS FROM {$p}os_items LIKE 'provider_id'" ) ) {
            $wpdb->query( "ALTER TABLE {$p}os_items ADD COLUMN provider_id INT UNSIGNED DEFAULT NULL AFTER unit_price" );
        }

        if ( ! $wpdb->get_var( "SHOW COLUMNS FROM {$p}os_warehouses LIKE 'type'" ) ) {
            $wpdb->query( "ALTER TABLE {$p}os_warehouses ADD COLUMN type VARCHAR(20) DEFAULT 'items' AFTER location" );
        }

        if ( ! $wpdb->get_var( "SHOW COLUMNS FROM {$p}os_providers LIKE 'is_active'" ) ) {
            $wpdb->query( "ALTER TABLE {$p}os_providers ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 0 AFTER contact_person" );
        }

        // Migration: add grade_id / section_id / academic_year_id to uniform sizes table if missing
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$p}os_student_uniform_sizes'" ) ) {
            if ( ! $wpdb->get_var( "SHOW COLUMNS FROM {$p}os_student_uniform_sizes LIKE 'grade_id'" ) ) {
                $wpdb->query( "ALTER TABLE {$p}os_student_uniform_sizes
                    ADD COLUMN grade_id INT UNSIGNED DEFAULT NULL AFTER academic_year,
                    ADD COLUMN section_id INT UNSIGNED DEFAULT NULL AFTER grade,
                    ADD COLUMN academic_year_id INT UNSIGNED DEFAULT NULL AFTER student_uid,
                    MODIFY COLUMN grade VARCHAR(100) NOT NULL" );
            }
        }

        // Migration: add include_in_survey flag to custom_models table if missing
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$p}os_custom_models'" ) ) {
            if ( ! $wpdb->get_var( "SHOW COLUMNS FROM {$p}os_custom_models LIKE 'include_in_survey'" ) ) {
                $wpdb->query( "ALTER TABLE {$p}os_custom_models ADD COLUMN include_in_survey TINYINT(1) NOT NULL DEFAULT 1 AFTER name" );
            }
            // Migration: add calculation_type column if missing
            if ( ! $wpdb->get_var( "SHOW COLUMNS FROM {$p}os_custom_models LIKE 'calculation_type'" ) ) {
                $wpdb->query( "ALTER TABLE {$p}os_custom_models ADD COLUMN calculation_type ENUM('auto','manual') NOT NULL DEFAULT 'auto' AFTER include_in_survey" );
            }
        }

        $tables = array();

        // ── os_categories ────────────────────────────────────────────────────
        $tables[] = "CREATE TABLE {$p}os_categories (
            id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name                VARCHAR(150) NOT NULL,
            name_ar             VARCHAR(150) DEFAULT NULL,
            parent_id           INT UNSIGNED DEFAULT NULL,
            description         TEXT DEFAULT NULL,
            is_active           TINYINT(1)   NOT NULL DEFAULT 1,
            created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY parent_id (parent_id)
        ) $col;";

        // ── os_units ─────────────────────────────────────────────────────────
        $tables[] = "CREATE TABLE {$p}os_units (
            id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name    VARCHAR(50)  NOT NULL,
            name_ar VARCHAR(50)  DEFAULT NULL,
            symbol  VARCHAR(10)  DEFAULT NULL,
            PRIMARY KEY (id)
        ) $col;";

        // ── os_custom_models ─────────────────────────────────────────────────
        $tables[] = "CREATE TABLE {$p}os_custom_models (
            id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name                VARCHAR(150)  NOT NULL,
            include_in_survey   TINYINT(1)    NOT NULL DEFAULT 1,
            calculation_type    ENUM('auto','manual') NOT NULL DEFAULT 'auto',
            PRIMARY KEY (id)
        ) $col;";

        // ── os_fabrics ───────────────────────────────────────────────────────
        $tables[] = "CREATE TABLE {$p}os_fabrics (
            id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name    VARCHAR(150)  NOT NULL,
            PRIMARY KEY (id)
        ) $col;";

        // ── os_colors ───────────────────────────────────────────────────────
        $tables[] = "CREATE TABLE {$p}os_colors (
            id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name    VARCHAR(150)  NOT NULL,
            PRIMARY KEY (id)
        ) $col;";

        // ── os_sizes ─────────────────────────────────────────────────────────
        $tables[] = "CREATE TABLE {$p}os_sizes (
            id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name    VARCHAR(150)  NOT NULL,
            PRIMARY KEY (id)
        ) $col;";

        // ── os_providers ─────────────────────────────────────────────────────
        $tables[] = "CREATE TABLE {$p}os_providers (
            id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_name   VARCHAR(200) NOT NULL,
            mobile_contact VARCHAR(50)  DEFAULT NULL,
            location       VARCHAR(255) DEFAULT NULL,
            contact_person VARCHAR(150) DEFAULT NULL,
            is_active      TINYINT(1)   NOT NULL DEFAULT 0,
            created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $col;";

        // ── os_items ─────────────────────────────────────────────────────────
        // Correction #1: academic_year_id INT (FK to olama_academic_years)
        // Correction #5: specifications LONGTEXT (not JSON)
        $tables[] = "CREATE TABLE {$p}os_items (
            id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
            sku             VARCHAR(100)  NOT NULL,
            name            VARCHAR(200)  NOT NULL,
            name_ar         VARCHAR(200)  DEFAULT NULL,
            category_id     INT UNSIGNED  NOT NULL DEFAULT 0,
            unit_id         INT UNSIGNED  NOT NULL DEFAULT 0,
            description     TEXT          DEFAULT NULL,
            specifications  LONGTEXT      DEFAULT NULL,
            min_stock_level INT UNSIGNED  NOT NULL DEFAULT 0,
            image_url       VARCHAR(500)  DEFAULT NULL,
            barcode         VARCHAR(100)  DEFAULT NULL,
            unit_price      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            provider_id     INT UNSIGNED  DEFAULT NULL,
            is_active       TINYINT(1)    NOT NULL DEFAULT 1,
            academic_year_id INT UNSIGNED DEFAULT NULL,
            base_item_id    INT UNSIGNED  DEFAULT NULL,
            created_by      BIGINT UNSIGNED DEFAULT NULL,
            created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at      DATETIME      DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY sku (sku),
            KEY category_id (category_id),
            KEY unit_id (unit_id),
            KEY academic_year_id (academic_year_id),
            KEY barcode (barcode),
            KEY name (name)
        ) $col;";

        // ── os_warehouses ─────────────────────────────────────────────────────
        $tables[] = "CREATE TABLE {$p}os_warehouses (
            id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            name       VARCHAR(150)    NOT NULL,
            name_ar    VARCHAR(150)    DEFAULT NULL,
            location   VARCHAR(250)    DEFAULT NULL,
            type       VARCHAR(20)     NOT NULL DEFAULT 'items',
            manager_id BIGINT UNSIGNED DEFAULT NULL,
            is_active  TINYINT(1)      NOT NULL DEFAULT 1,
            created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $col;";

        // ── os_stock ─────────────────────────────────────────────────────────
        // Correction #4: removed GENERATED quantity_available column
        //   => calculated via os_qty_available() or SQL: (quantity_on_hand - quantity_reserved)
        $tables[] = "CREATE TABLE {$p}os_stock (
            id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
            item_id           INT UNSIGNED NOT NULL,
            warehouse_id      INT UNSIGNED NOT NULL,
            quantity_on_hand  INT          NOT NULL DEFAULT 0,
            quantity_reserved INT          NOT NULL DEFAULT 0,
            last_counted_at   DATETIME     DEFAULT NULL,
            last_updated_at   DATETIME     DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_item_warehouse (item_id, warehouse_id),
            KEY item_id (item_id),
            KEY warehouse_id (warehouse_id)
        ) $col;";

        // ── os_stock_movements ────────────────────────────────────────────────
        // Correction #1: academic_year_id INT
        // movement_type VARCHAR (not ENUM — dbDelta-safe)
        $tables[] = "CREATE TABLE {$p}os_stock_movements (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            item_id         INT UNSIGNED    NOT NULL,
            warehouse_id    INT UNSIGNED    NOT NULL,
            movement_type   VARCHAR(30)     NOT NULL,
            quantity        INT             NOT NULL,
            reference_id    INT UNSIGNED    DEFAULT NULL,
            reference_type  VARCHAR(50)     DEFAULT NULL,
            notes           TEXT            DEFAULT NULL,
            academic_year_id INT UNSIGNED   DEFAULT NULL,
            performed_by    BIGINT UNSIGNED NOT NULL,
            performed_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY item_id (item_id),
            KEY warehouse_id (warehouse_id),
            KEY idx_item_type (item_id, movement_type),
            KEY idx_performed_at (performed_at),
            KEY academic_year_id (academic_year_id)
        ) $col;";

        // ── os_inventory_counts ───────────────────────────────────────────────
        // Correction #1: academic_year_id INT
        // status VARCHAR (not ENUM)
        $tables[] = "CREATE TABLE {$p}os_inventory_counts (
            id               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            warehouse_id     INT UNSIGNED    NOT NULL,
            academic_year_id INT UNSIGNED    DEFAULT NULL,
            status           VARCHAR(20)     NOT NULL DEFAULT 'draft',
            count_date       DATE            NOT NULL,
            notes            TEXT            DEFAULT NULL,
            created_by       BIGINT UNSIGNED DEFAULT NULL,
            confirmed_by     BIGINT UNSIGNED DEFAULT NULL,
            confirmed_at     DATETIME        DEFAULT NULL,
            created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY warehouse_id (warehouse_id),
            KEY academic_year_id (academic_year_id)
        ) $col;";

        // ── os_inventory_count_lines ──────────────────────────────────────────
        // Correction #4: removed GENERATED variance column
        //   => calculated via os_count_variance($counted, $system) or SQL: (counted_qty - system_qty)
        $tables[] = "CREATE TABLE {$p}os_inventory_count_lines (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            count_id    INT UNSIGNED NOT NULL,
            item_id     INT UNSIGNED NOT NULL,
            system_qty  INT          NOT NULL DEFAULT 0,
            counted_qty INT          NOT NULL DEFAULT 0,
            notes       TEXT         DEFAULT NULL,
            PRIMARY KEY (id),
            KEY count_id (count_id),
            KEY item_id (item_id)
        ) $col;";

        // ── os_assignments ────────────────────────────────────────────────────
        // Correction #1: academic_year_id INT
        // Correction #2: assignee_id VARCHAR(50)
        //   - For employees: stores WP user ID cast to string  (e.g. "42")
        //   - For students:  stores student_uid                (e.g. "STU-2024-001")
        //   Use assignee_type to disambiguate.
        // status / assignee_type VARCHAR (not ENUM)
        $tables[] = "CREATE TABLE {$p}os_assignments (
            id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            assignee_type       VARCHAR(20)     NOT NULL,
            assignee_id         VARCHAR(50)     NOT NULL,
            item_id             INT UNSIGNED    NOT NULL,
            warehouse_id        INT UNSIGNED    NOT NULL,
            quantity_assigned   INT UNSIGNED    NOT NULL,
            quantity_returned   INT UNSIGNED    NOT NULL DEFAULT 0,
            status              VARCHAR(30)     NOT NULL DEFAULT 'active',
            assigned_date       DATE            NOT NULL,
            expected_return_date DATE           DEFAULT NULL,
            notes               TEXT            DEFAULT NULL,
            academic_year_id    INT UNSIGNED    DEFAULT NULL,
            assigned_by         BIGINT UNSIGNED NOT NULL,
            created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_assignee (assignee_type, assignee_id),
            KEY idx_status (status),
            KEY item_id (item_id),
            KEY academic_year_id (academic_year_id)
        ) $col;";

        // ── os_assignment_returns ─────────────────────────────────────────────
        // `return_condition` replaces spec's `condition` (MySQL reserved word)
        $tables[] = "CREATE TABLE {$p}os_assignment_returns (
            id               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            assignment_id    INT UNSIGNED    NOT NULL,
            quantity         INT UNSIGNED    NOT NULL,
            return_condition VARCHAR(20)     NOT NULL DEFAULT 'good',
            return_date      DATE            NOT NULL,
            notes            TEXT            DEFAULT NULL,
            processed_by     BIGINT UNSIGNED NOT NULL,
            created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY assignment_id (assignment_id)
        ) $col;";

        // ── os_transfers ──────────────────────────────────────────────────────
        $tables[] = "CREATE TABLE {$p}os_transfers (
            id             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            from_warehouse INT UNSIGNED    NOT NULL,
            to_warehouse   INT UNSIGNED    NOT NULL,
            item_id        INT UNSIGNED    NOT NULL,
            quantity       INT UNSIGNED    NOT NULL,
            status         VARCHAR(20)     NOT NULL DEFAULT 'pending',
            transfer_date  DATE            NOT NULL,
            notes          TEXT            DEFAULT NULL,
            requested_by   BIGINT UNSIGNED DEFAULT NULL,
            approved_by    BIGINT UNSIGNED DEFAULT NULL,
            created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY item_id (item_id),
            KEY from_warehouse (from_warehouse),
            KEY to_warehouse (to_warehouse)
        ) $col;";

        // ── os_student_uniform_sizes ──────────────────────────────────────────────
        // Future-ready: separate polo/hoodie/pants columns + audit who/when.
        // grade_id / section_id are FKs to olama_grades / olama_sections for proper integration.
        $tables[] = "CREATE TABLE {$p}os_student_uniform_sizes (
            id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            student_uid     VARCHAR(50)     NOT NULL,
            academic_year_id INT UNSIGNED  DEFAULT NULL,
            academic_year   VARCHAR(20)     NOT NULL,
            grade_id        INT UNSIGNED    DEFAULT NULL,
            grade           VARCHAR(100)    NOT NULL,
            section_id      INT UNSIGNED    DEFAULT NULL,
            section         VARCHAR(20)     DEFAULT NULL,
            uniform_size    TINYINT UNSIGNED NOT NULL,
            polo_size       TINYINT UNSIGNED DEFAULT NULL,
            hoodie_size     TINYINT UNSIGNED DEFAULT NULL,
            pants_size      TINYINT UNSIGNED DEFAULT NULL,
            notes           TEXT            DEFAULT NULL,
            measured_by     BIGINT UNSIGNED DEFAULT NULL,
            measured_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_student_year (student_uid, academic_year),
            KEY idx_grade_section (grade_id, section_id),
            KEY idx_academic_year (academic_year_id),
            KEY idx_uniform_size (uniform_size)
        ) $col;";


        // ── os_audit_log ──────────────────────────────────────────────────────
        // Correction #5: old_values/new_values LONGTEXT (not JSON)
        // action VARCHAR (not ENUM)
        $tables[] = "CREATE TABLE {$p}os_audit_log (
            id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            table_name VARCHAR(100)    NOT NULL,
            record_id  INT UNSIGNED    NOT NULL,
            action     VARCHAR(20)     NOT NULL,
            old_values LONGTEXT        DEFAULT NULL,
            new_values LONGTEXT        DEFAULT NULL,
            user_id    BIGINT UNSIGNED NOT NULL,
            ip_address VARCHAR(45)     DEFAULT NULL,
            user_agent VARCHAR(300)    DEFAULT NULL,
            created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_table_record (table_name, record_id),
            KEY idx_user (user_id),
            KEY idx_created_at (created_at)
        ) $col;";

        // ── os_entitlements ──────────────────────────────────────────────────
        $tables[] = "CREATE TABLE {$p}os_entitlements (
            id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
            academic_year_id INT UNSIGNED NOT NULL,
            grade_id         INT UNSIGNED NOT NULL,
            custom_model_id  INT UNSIGNED NOT NULL,
            quantity         INT UNSIGNED NOT NULL DEFAULT 1,
            created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_grade_model_year (academic_year_id, grade_id, custom_model_id),
            KEY academic_year_id (academic_year_id),
            KEY grade_id (grade_id),
            KEY custom_model_id (custom_model_id)
        ) $col;";


        foreach ( $tables as $sql ) {
            dbDelta( $sql );
        }

        if ( $wpdb->last_error ) {
            error_log( '[Olama Stores] DB Error during create_tables: ' . $wpdb->last_error );
        }

        // Seed default fabrics
        $fabric_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$p}os_fabrics" );
        if ( $fabric_count == 0 ) {
            $default_fabrics = array( 'Cotton 60% - Polyster 40%', 'Indian Fleece', 'Linen', 'Gabardine' );
            foreach ( $default_fabrics as $fabric ) {
                $wpdb->insert( "{$p}os_fabrics", array( 'name' => $fabric ) );
            }
        }

        // Seed default colors
        $color_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$p}os_colors" );
        if ( $color_count == 0 ) {
            $default_colors = array( 'Red', 'Blue', 'Mix Blue-Red' );
            foreach ( $default_colors as $color ) {
                $wpdb->insert( "{$p}os_colors", array( 'name' => $color ) );
            }
        }

        // Seed default sizes
        $size_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$p}os_sizes" );
        if ( $size_count == 0 ) {
            $default_sizes = array( '22', '24', '26', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50', '52', '54' );
            foreach ( $default_sizes as $size ) {
                $wpdb->insert( "{$p}os_sizes", array( 'name' => $size ) );
            }
        }
    }

    /**
     * List all tables managed by this plugin (used for reference / cleanup).
     */
    public static function get_tables() {
        global $wpdb;
        $p = $wpdb->prefix;
        return array(
            "{$p}os_categories",
            "{$p}os_units",
            "{$p}os_custom_models",
            "{$p}os_providers",
            "{$p}os_items",
            "{$p}os_warehouses",
            "{$p}os_stock",
            "{$p}os_stock_movements",
            "{$p}os_inventory_counts",
            "{$p}os_inventory_count_lines",
            "{$p}os_assignments",
            "{$p}os_assignment_returns",
            "{$p}os_transfers",
            "{$p}os_audit_log",
            "{$p}os_student_uniform_sizes",
            "{$p}os_entitlements",
        );
    }
}
