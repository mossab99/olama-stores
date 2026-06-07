<?php
/**
 * Books Withdrawal View.
 * Path: admin/views/books-withdrawal.php
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Nonces
$books_withdrawal_nonce = wp_create_nonce( 'os_books_withdrawal_nonce' );

// Get Active Year Info
$active_year_id   = os_get_active_year_id();
$active_year_name = os_get_active_year_name();

// Load School Integration Metadata
$grades   = array();
$sections = array();
if ( class_exists( 'OS_School_Integration' ) ) {
    $grades   = OS_School_Integration::get_grades();
    $sections = OS_School_Integration::get_sections( $active_year_id );
}

// Get Book Warehouses and Fallback
global $wpdb;
$warehouses = $wpdb->get_results( "SELECT id, name, type FROM {$wpdb->prefix}os_warehouses WHERE type = 'books' AND is_active = 1 ORDER BY name ASC" );
if ( empty( $warehouses ) ) {
    // If no dedicated books warehouse is found, query any active warehouse
    $warehouses = $wpdb->get_results( "SELECT id, name, type FROM {$wpdb->prefix}os_warehouses WHERE is_active = 1 ORDER BY name ASC" );
}

// Get Book Items (residing in warehouses of type books, or fallback to all items)
$books_warehouses_ids = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}os_warehouses WHERE type = 'books' AND is_active = 1" );
if ( ! empty( $books_warehouses_ids ) ) {
    $books = $wpdb->get_results( "SELECT DISTINCT i.id, i.name, i.sku, i.barcode
                                  FROM {$wpdb->prefix}os_items i
                                  JOIN {$wpdb->prefix}os_stock s ON i.id = s.item_id
                                  WHERE i.is_active = 1 AND s.warehouse_id IN (" . implode( ',', array_map( 'intval', $books_warehouses_ids ) ) . ")
                                  ORDER BY i.name ASC" );
} else {
    $books = $wpdb->get_results( "SELECT id, name, sku, barcode FROM {$wpdb->prefix}os_items WHERE is_active = 1 ORDER BY name ASC" );
}

// Get Categories for Report Filtering
$categories = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}os_categories WHERE is_active = 1 ORDER BY name ASC" );

// Get current allocations mapping for initial JS state
$allocations = get_option( 'os_book_allocations', array() );
?>

<div class="wrap os-wrap" id="os-books-withdrawal-page">
    <h1 class="os-page-title">
        <span class="dashicons dashicons-welcome-learn-more"></span>
        <?php esc_html_e( 'Books Withdrawal & Distribution', 'olama-stores' ); ?>
        <span class="os-year-badge"><?php echo esc_html( $active_year_name ?: __( 'No Active Year', 'olama-stores' ) ); ?></span>
    </h1>

    <!-- Tabs Navigation -->
    <nav class="os-books-withdrawal-tabs" id="os-books-withdrawal-tab-nav">
        <button class="os-books-withdrawal-tab active" data-tab="tab-family">
            <span class="dashicons dashicons-groups"></span>
            <?php esc_html_e( 'Family Distribution', 'olama-stores' ); ?>
        </button>
        <button class="os-books-withdrawal-tab" data-tab="tab-class">
            <span class="dashicons dashicons-welcome-widgets-menus"></span>
            <?php esc_html_e( 'Grade-Section Distribution', 'olama-stores' ); ?>
        </button>
        <button class="os-books-withdrawal-tab" data-tab="tab-allocations">
            <span class="dashicons dashicons-admin-settings"></span>
            <?php esc_html_e( 'Book Allocations', 'olama-stores' ); ?>
        </button>
        <button class="os-books-withdrawal-tab" data-tab="tab-reports">
            <span class="dashicons dashicons-chart-bar"></span>
            <?php esc_html_e( 'Distribution Reports', 'olama-stores' ); ?>
        </button>
    </nav>

    <!-- TAB 1: Family-Based Distribution -->
    <div class="os-books-withdrawal-tab-content active" id="tab-family">
        <div class="os-books-withdrawal-grid-2">
            <!-- Left Panel: Family Search & Member Selection -->
            <div class="os-books-withdrawal-card">
                <div class="os-books-withdrawal-card-header">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e( 'Search Family', 'olama-stores' ); ?>
                </div>
                <div class="os-books-withdrawal-card-body">
                    <div class="os-books-withdrawal-form-row">
                        <label for="os-family-search-input"><?php esc_html_e( 'Family Number or Family Name', 'olama-stores' ); ?></label>
                        <div style="display:flex; gap:10px; width:100%;">
                            <input type="text" id="os-family-search-input" class="os-books-withdrawal-filter-input" style="flex:1;" placeholder="<?php esc_attr_e( 'e.g. FAM001 or Al-Husseini...', 'olama-stores' ); ?>">
                            <button type="button" id="os-btn-family-search" class="button button-primary"><?php esc_html_e( 'Find Family', 'olama-stores' ); ?></button>
                        </div>
                    </div>

                    <div id="os-family-members-wrap" style="margin-top:20px; display:none;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px;">
                            <h3 style="margin:0;"><?php esc_html_e( 'Select Students to Issue Books To:', 'olama-stores' ); ?></h3>
                            <button type="button" id="os-btn-load-allocated-family" class="button button-small" style="display:none;">
                                <span class="dashicons dashicons-admin-settings"></span>
                                <?php esc_html_e( 'Load Grade Allocated Books', 'olama-stores' ); ?>
                            </button>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="wp-list-table widefat striped" id="os-table-family-students">
                                <thead>
                                    <tr>
                                        <th style="width: 30px;"><input type="checkbox" id="os-family-select-all" checked></th>
                                        <th><?php esc_html_e( 'Student Name', 'olama-stores' ); ?></th>
                                        <th><?php esc_html_e( 'Grade', 'olama-stores' ); ?></th>
                                        <th><?php esc_html_e( 'Section', 'olama-stores' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="os-family-students-tbody"></tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div id="os-family-empty-state" class="os-books-withdrawal-notice">
                        <span class="dashicons dashicons-info"></span>
                        <?php esc_html_e( 'Enter a family search term and click Find Family.', 'olama-stores' ); ?>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Warehouse & Books Selection -->
            <div class="os-books-withdrawal-card">
                <div class="os-books-withdrawal-card-header">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                    <?php esc_html_e( 'Select Books & Warehouse', 'olama-stores' ); ?>
                </div>
                <div class="os-books-withdrawal-card-body">
                    <div class="os-books-withdrawal-form-row">
                        <label for="os-family-warehouse"><?php esc_html_e( 'Source Warehouse (Locked to Books Warehouse)', 'olama-stores' ); ?></label>
                        <select id="os-family-warehouse" style="width:100%;">
                            <?php foreach ( $warehouses as $wh ) : ?>
                                <option value="<?php echo esc_attr( $wh->id ); ?>" <?php selected( $wh->type, 'books' ); ?>>
                                    <?php echo esc_html( $wh->name ); ?> <?php echo $wh->type === 'books' ? esc_html( '(' . __( 'Books Only', 'olama-stores' ) . ')' ) : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-top:20px;">
                        <label><h3><?php esc_html_e( 'Books list to distribute:', 'olama-stores' ); ?></h3></label>
                        <div class="os-books-withdrawal-selector-wrap">
                            <input type="text" id="os-family-book-search" class="os-books-withdrawal-filter-input" placeholder="<?php esc_attr_e( 'Search and add books to list...', 'olama-stores' ); ?>">
                            <div class="os-books-dropdown" id="os-family-book-dropdown" style="display:none;"></div>
                        </div>

                        <div style="overflow-x:auto; margin-top:15px;">
                            <table class="wp-list-table widefat striped" id="os-table-family-books-list">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Book Name', 'olama-stores' ); ?></th>
                                        <th><?php esc_html_e( 'SKU', 'olama-stores' ); ?></th>
                                        <th style="width:80px; text-align:center;"><?php esc_html_e( 'Qty', 'olama-stores' ); ?></th>
                                        <th style="width:50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="os-family-books-tbody">
                                    <tr class="os-empty-row"><td colspan="4" style="text-align:center; color:#999;"><?php esc_html_e( 'No books added yet.', 'olama-stores' ); ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="os-books-withdrawal-form-row" style="margin-top: 20px;">
                        <label for="os-family-notes"><?php esc_html_e( 'Distribution Notes', 'olama-stores' ); ?></label>
                        <textarea id="os-family-notes" placeholder="<?php esc_attr_e( 'e.g. Batch book withdrawal for active term.', 'olama-stores' ); ?>" style="width:100%; height:80px;"></textarea>
                    </div>
                </div>
                <div class="os-books-withdrawal-card-footer">
                    <button type="button" id="os-btn-issue-family" class="button button-primary" disabled>
                        <span class="dashicons dashicons-saved"></span>
                        <?php esc_html_e( 'Issue Batch to Selected Students', 'olama-stores' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: Grade-Section-Based Distribution -->
    <div class="os-books-withdrawal-tab-content" id="tab-class">
        <div class="os-books-withdrawal-grid-2">
            <!-- Left Panel: Grade Section & Student Preview -->
            <div class="os-books-withdrawal-card">
                <div class="os-books-withdrawal-card-header">
                    <span class="dashicons dashicons-welcome-widgets-menus"></span>
                    <?php esc_html_e( 'Select Grade & Section', 'olama-stores' ); ?>
                </div>
                <div class="os-books-withdrawal-card-body">
                    <div class="os-books-withdrawal-grid-2" style="grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div class="os-books-withdrawal-form-row">
                            <label for="os-class-grade"><?php esc_html_e( 'Grade', 'olama-stores' ); ?></label>
                            <select id="os-class-grade" style="width:100%;">
                                <option value=""><?php esc_html_e( '-- Select Grade --', 'olama-stores' ); ?></option>
                                <?php foreach ( $grades as $g ) : ?>
                                    <option value="<?php echo esc_attr( $g->id ); ?>"><?php echo esc_html( $g->grade_name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="os-books-withdrawal-form-row">
                            <label for="os-class-section"><?php esc_html_e( 'Section', 'olama-stores' ); ?></label>
                            <select id="os-class-section" style="width:100%;" disabled>
                                <option value=""><?php esc_html_e( '-- Select Section --', 'olama-stores' ); ?></option>
                            </select>
                        </div>
                    </div>

                    <div id="os-class-students-wrap" style="display:none;">
                        <h3><?php esc_html_e( 'Class Students Preview:', 'olama-stores' ); ?> <span id="os-class-students-count" class="os-badge os-badge-info">0</span></h3>
                        <div style="overflow-x:auto; max-height: 250px;">
                            <table class="wp-list-table widefat striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'UID', 'olama-stores' ); ?></th>
                                        <th><?php esc_html_e( 'Student Name', 'olama-stores' ); ?></th>
                                        <th><?php esc_html_e( 'Family ID', 'olama-stores' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="os-class-students-tbody"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="os-class-empty-state" class="os-books-withdrawal-notice">
                        <span class="dashicons dashicons-info"></span>
                        <?php esc_html_e( 'Select a grade and section to preview the list of enrolled students.', 'olama-stores' ); ?>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Warehouse & Books Selection -->
            <div class="os-books-withdrawal-card">
                <div class="os-books-withdrawal-card-header">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                    <?php esc_html_e( 'Select Books & Warehouse', 'olama-stores' ); ?>
                </div>
                <div class="os-books-withdrawal-card-body">
                    <div class="os-books-withdrawal-form-row">
                        <label for="os-class-warehouse"><?php esc_html_e( 'Source Warehouse (Locked to Books Warehouse)', 'olama-stores' ); ?></label>
                        <select id="os-class-warehouse" style="width:100%;">
                            <?php foreach ( $warehouses as $wh ) : ?>
                                <option value="<?php echo esc_attr( $wh->id ); ?>" <?php selected( $wh->type, 'books' ); ?>>
                                    <?php echo esc_html( $wh->name ); ?> <?php echo $wh->type === 'books' ? esc_html( '(' . __( 'Books Only', 'olama-stores' ) . ')' ) : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-top:20px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <label><h3><?php esc_html_e( 'Books list to distribute:', 'olama-stores' ); ?></h3></label>
                            <button type="button" id="os-btn-load-allocated-class" class="button button-small" style="display:none;">
                                <span class="dashicons dashicons-admin-settings"></span>
                                <?php esc_html_e( 'Load Grade Allocated Books', 'olama-stores' ); ?>
                            </button>
                        </div>
                        
                        <div class="os-books-withdrawal-selector-wrap" style="margin-top:10px;">
                            <input type="text" id="os-class-book-search" class="os-books-withdrawal-filter-input" placeholder="<?php esc_attr_e( 'Search and add books to list...', 'olama-stores' ); ?>">
                            <div class="os-books-dropdown" id="os-class-book-dropdown" style="display:none;"></div>
                        </div>

                        <div style="overflow-x:auto; margin-top:15px;">
                            <table class="wp-list-table widefat striped" id="os-table-class-books-list">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Book Name', 'olama-stores' ); ?></th>
                                        <th><?php esc_html_e( 'SKU', 'olama-stores' ); ?></th>
                                        <th style="width:80px; text-align:center;"><?php esc_html_e( 'Qty', 'olama-stores' ); ?></th>
                                        <th style="width:50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="os-class-books-tbody">
                                    <tr class="os-empty-row"><td colspan="4" style="text-align:center; color:#999;"><?php esc_html_e( 'No books added yet.', 'olama-stores' ); ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="os-books-withdrawal-form-row" style="margin-top: 20px;">
                        <label for="os-class-notes"><?php esc_html_e( 'Distribution Notes', 'olama-stores' ); ?></label>
                        <textarea id="os-class-notes" placeholder="<?php esc_attr_e( 'e.g. Batch book withdrawal for selected grade section.', 'olama-stores' ); ?>" style="width:100%; height:80px;"></textarea>
                    </div>
                </div>
                <div class="os-books-withdrawal-card-footer">
                    <button type="button" id="os-btn-issue-class" class="button button-primary" disabled>
                        <span class="dashicons dashicons-saved"></span>
                        <?php esc_html_e( 'Issue Batch to Entire Class', 'olama-stores' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: Grade Book Allocations Configuration -->
    <div class="os-books-withdrawal-tab-content" id="tab-allocations">
        <div class="os-books-withdrawal-card">
            <div class="os-books-withdrawal-card-header">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php esc_html_e( 'Configure Book Allocations Per Grade', 'olama-stores' ); ?>
            </div>
            <div class="os-books-withdrawal-card-body">
                <div class="os-books-withdrawal-form-row" style="max-width:400px; margin-bottom: 25px;">
                    <label for="os-alloc-grade-selector"><?php esc_html_e( 'Select Grade', 'olama-stores' ); ?></label>
                    <select id="os-alloc-grade-selector" style="width:100%;">
                        <option value=""><?php esc_html_e( '-- Select Grade --', 'olama-stores' ); ?></option>
                        <?php foreach ( $grades as $g ) : ?>
                            <option value="<?php echo esc_attr( $g->id ); ?>"><?php echo esc_html( $g->grade_name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="os-allocations-list-wrap" style="display:none;">
                    <h3><?php esc_html_e( 'Select Books Allocated to this Grade:', 'olama-stores' ); ?></h3>
                    <div style="overflow-x:auto;">
                        <table class="wp-list-table widefat striped" id="os-table-allocations">
                            <thead>
                                <tr>
                                    <th style="width:30px;"><input type="checkbox" id="os-allocations-select-all"></th>
                                    <th><?php esc_html_e( 'Book Name', 'olama-stores' ); ?></th>
                                    <th><?php esc_html_e( 'SKU', 'olama-stores' ); ?></th>
                                    <th><?php esc_html_e( 'Barcode', 'olama-stores' ); ?></th>
                                </tr>
                            </thead>
                            <tbody id="os-allocations-tbody"></tbody>
                        </table>
                    </div>
                </div>

                <div id="os-allocations-empty-state" class="os-books-withdrawal-notice">
                    <span class="dashicons dashicons-info"></span>
                    <?php esc_html_e( 'Select a grade above to configure its book allocations list.', 'olama-stores' ); ?>
                </div>
            </div>
            <div class="os-books-withdrawal-card-footer" id="os-allocations-footer" style="display:none;">
                <button type="button" id="os-btn-save-allocations" class="button button-primary">
                    <span class="dashicons dashicons-saved"></span>
                    <?php esc_html_e( 'Save Book Allocations', 'olama-stores' ); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- TAB 4: Books Reporting System -->
    <div class="os-books-withdrawal-tab-content" id="tab-reports">
        <div class="os-books-withdrawal-card">
            <div class="os-books-withdrawal-card-header">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e( 'Student Book Distribution Reports', 'olama-stores' ); ?>
            </div>
            <div class="os-books-withdrawal-card-body">
                <!-- Report Selector -->
                <div class="os-books-withdrawal-grid-2" style="grid-template-columns: 1fr 2fr; gap:20px; align-items:flex-end; margin-bottom: 25px;">
                    <div class="os-books-withdrawal-form-row">
                        <label for="os-rpt-selector"><?php esc_html_e( 'Report Type', 'olama-stores' ); ?></label>
                        <select id="os-rpt-selector" style="width:100%;">
                            <option value="store-stock"><?php esc_html_e( '1. Store Stock Report (Book Warehouses)', 'olama-stores' ); ?></option>
                            <option value="books-received"><?php esc_html_e( '2. Student Books Received Log', 'olama-stores' ); ?></option>
                            <option value="missing-books"><?php esc_html_e( '3. Missing Books Report', 'olama-stores' ); ?></option>
                            <option value="grade-coverage"><?php esc_html_e( '4. Class Grade Coverage Metrics', 'olama-stores' ); ?></option>
                        </select>
                    </div>

                    <!-- Dynamic Report Filters -->
                    <div class="os-rpt-filters-wrap" style="display:flex; flex-wrap:wrap; gap:12px;">
                        
                        <!-- Date range (for Received Log) -->
                        <div class="os-rpt-filter-item rpt-filter-date" style="display:none;">
                            <label><?php esc_html_e( 'From Date', 'olama-stores' ); ?></label>
                            <input type="date" id="os-rpt-date-from" class="os-books-withdrawal-filter-input">
                        </div>
                        <div class="os-rpt-filter-item rpt-filter-date" style="display:none;">
                            <label><?php esc_html_e( 'To Date', 'olama-stores' ); ?></label>
                            <input type="date" id="os-rpt-date-to" class="os-books-withdrawal-filter-input">
                        </div>

                        <!-- Grade & Section (for Received Log, Missing Books) -->
                        <div class="os-rpt-filter-item rpt-filter-grade" style="display:none;">
                            <label><?php esc_html_e( 'Grade', 'olama-stores' ); ?></label>
                            <select id="os-rpt-grade" style="min-width:140px;">
                                <option value=""><?php esc_html_e( 'All Grades', 'olama-stores' ); ?></option>
                                <?php foreach ( $grades as $g ) : ?>
                                    <option value="<?php echo esc_attr( $g->id ); ?>"><?php echo esc_html( $g->grade_name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="os-rpt-filter-item rpt-filter-section" style="display:none;">
                            <label><?php esc_html_e( 'Section', 'olama-stores' ); ?></label>
                            <select id="os-rpt-section" style="min-width:140px;">
                                <option value=""><?php esc_html_e( 'All Sections', 'olama-stores' ); ?></option>
                            </select>
                        </div>

                        <!-- Family ID (for Received Log, Missing Books) -->
                        <div class="os-rpt-filter-item rpt-filter-family" style="display:none;">
                            <label><?php esc_html_e( 'Family ID', 'olama-stores' ); ?></label>
                            <input type="text" id="os-rpt-family" class="os-books-withdrawal-filter-input" placeholder="<?php esc_attr_e( 'e.g. FAM054', 'olama-stores' ); ?>" style="width:120px;">
                        </div>

                        <!-- Search keyword (for Stock report, Received Log) -->
                        <div class="os-rpt-filter-item rpt-filter-search">
                            <label><?php esc_html_e( 'Keyword Search', 'olama-stores' ); ?></label>
                            <input type="text" id="os-rpt-search" class="os-books-withdrawal-filter-input" placeholder="<?php esc_attr_e( 'Search books or students...', 'olama-stores' ); ?>" style="width:180px;">
                        </div>

                        <div style="display:flex; align-items:flex-end; gap:8px;">
                            <button type="button" id="os-btn-load-report" class="button button-primary">
                                <span class="dashicons dashicons-calculator"></span>
                                <?php esc_html_e( 'Run Report', 'olama-stores' ); ?>
                            </button>
                            <button type="button" id="os-btn-print-report" class="button no-print" disabled>
                                <span class="dashicons dashicons-printer"></span>
                                <?php esc_html_e( 'Print', 'olama-stores' ); ?>
                            </button>
                            <button type="button" id="os-btn-export-report" class="button no-print" disabled>
                                <span class="dashicons dashicons-media-spreadsheet"></span>
                                <?php esc_html_e( 'Export CSV', 'olama-stores' ); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <hr style="border:0; border-top:1px solid var(--os-border); margin:20px 0;">

                <!-- Report Results Table Container -->
                <div id="os-report-results-wrap">
                    <div class="os-books-withdrawal-notice" id="os-report-initial-state">
                        <span class="dashicons dashicons-info"></span>
                        <?php esc_html_e( 'Adjust filters above and click "Run Report" to display metrics.', 'olama-stores' ); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JSON Hydration Block -->
<script id="os-books-withdrawal-data" type="application/json">
<?php echo wp_json_encode( array(
    'nonce'        => $books_withdrawal_nonce,
    'apiRoot'      => esc_url_raw( rest_url( 'olama-stores/v1' ) ),
    'activeYearId' => $active_year_id,
    'grades'       => $grades,
    'sections'     => $sections,
    'books'        => $books,
    'allocations'  => $allocations,
    'i18n'         => array(
        'errorGeneric'         => __( 'An error occurred. Please try again.', 'olama-stores' ),
        'loading'              => __( 'Loading…', 'olama-stores' ),
        'searching'            => __( 'Searching…', 'olama-stores' ),
        'noResults'            => __( 'No matching records found.', 'olama-stores' ),
        'savedSuccess'         => __( 'Settings saved successfully!', 'olama-stores' ),
        'confirmBatchIssue'    => __( 'Are you sure you want to perform this batch book withdrawal? This will generate custody logs for all selected students.', 'olama-stores' ),
        'selectWarehouse'      => __( 'Please select a source warehouse.', 'olama-stores' ),
        'addBooks'             => __( 'Please add at least one book to the list.', 'olama-stores' ),
        'noStudents'           => __( 'No students selected or found in the system.', 'olama-stores' ),
        'saving'               => __( 'Saving…', 'olama-stores' ),
        'selectGrade'          => __( 'Please select a grade first.', 'olama-stores' ),
        'selectSection'        => __( 'Please select a section.', 'olama-stores' ),
        'distributionSuccess'  => __( 'Batch distribution complete! Custody cards created and stock levels decremented.', 'olama-stores' ),
        'emptyBooksList'       => __( 'No books added yet.', 'olama-stores' ),
        'remove'               => __( 'Remove', 'olama-stores' ),
        'bookName'             => __( 'Book Name', 'olama-stores' ),
        'sku'                  => __( 'SKU', 'olama-stores' ),
        'barcode'              => __( 'Barcode', 'olama-stores' ),
        'qty'                  => __( 'Qty', 'olama-stores' ),
        'available'            => __( 'Available', 'olama-stores' ),
        'totalExpected'        => __( 'Expected Distributions', 'olama-stores' ),
        'totalReceived'        => __( 'Received Distributions', 'olama-stores' ),
        'coverage'             => __( 'Coverage Pct', 'olama-stores' ),
        'students'             => __( 'Students Count', 'olama-stores' ),
        'missingBooks'         => __( 'Missing Books', 'olama-stores' ),
        'student'              => __( 'Student Name', 'olama-stores' ),
        'uid'                  => __( 'Student UID', 'olama-stores' ),
        'family'               => __( 'Family ID', 'olama-stores' ),
        'grade'                => __( 'Grade', 'olama-stores' ),
        'section'              => __( 'Section', 'olama-stores' ),
        'onHand'               => __( 'On Hand', 'olama-stores' ),
        'reserved'             => __( 'Reserved', 'olama-stores' ),
        'warehouse'            => __( 'Warehouse', 'olama-stores' ),
        'date'                 => __( 'Date', 'olama-stores' ),
        'performedBy'          => __( 'Performed By', 'olama-stores' ),
        'notes'                => __( 'Notes', 'olama-stores' ),
    ),
) ); ?>
</script>

<?php
// Enqueue Script & Stylesheets in the Footer
wp_enqueue_script(
    'os-books-withdrawal',
    OS_URL . 'admin/assets/js/os-books-withdrawal.js',
    array( 'jquery', 'wp-api-fetch' ),
    OS_Helpers::asset_version( 'admin/assets/js/os-books-withdrawal.js' ),
    true
);

wp_enqueue_style(
    'os-books-withdrawal',
    OS_URL . 'admin/assets/css/os-books-withdrawal.css',
    array(),
    OS_Helpers::asset_version( 'admin/assets/css/os-books-withdrawal.css' )
);
?>
