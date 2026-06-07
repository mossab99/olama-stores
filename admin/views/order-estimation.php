<?php
/**
 * View: Custom Order Estimation
 * Estimates uniform purchase quantities based on grade counts & historical size distributions.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Nonce for AJAX
$estimation_nonce   = wp_create_nonce( 'os_order_estimation_nonce' );
$uniform_size_nonce = wp_create_nonce( 'os_uniform_size_nonce' );

// Load saved drafts list
$saved_drafts = get_option( 'os_estimation_drafts', array() );

// Load saved distribution (null = use JS defaults)
$saved_distribution = get_option( 'os_estimation_distribution', null );

// ── Olama School integration: active year & semester ─────────────────────────
$active_year     = class_exists( 'Olama_School_Academic' ) ? Olama_School_Academic::get_active_year() : null;
$active_semester = ( $active_year && class_exists( 'Olama_School_Academic' ) )
    ? Olama_School_Academic::get_active_semester( $active_year->id )
    : null;

$active_year_id   = $active_year     ? (int) $active_year->id                 : 0;
$active_year_name = $active_year     ? esc_html( $active_year->year_name )     : '';
$active_sem_id    = $active_semester ? (int) $active_semester->id              : 0;
$active_sem_name  = $active_semester ? esc_html( $active_semester->semester_name ) : '';

// ── Grades from Olama School DB ───────────────────────────────────────────────
$school_grades = class_exists( 'Olama_School_Grade' )
    ? Olama_School_Grade::get_grades()
    : array();

// ── Custom Models for estimation survey filter ─────────────────────────────────
global $wpdb;
$all_custom_models = $wpdb->get_results(
    "SELECT id, name, include_in_survey, calculation_type FROM {$wpdb->prefix}os_custom_models ORDER BY name ASC"
);
?>


<div class="wrap os-wrap" id="os-estimation-page">

    <h1 class="os-page-title">
        <span class="dashicons dashicons-cart"></span>
        <?php esc_html_e( 'Custom Order Estimation', 'olama-stores' ); ?>
    </h1>

    <!-- ── Tab Nav ─────────────────────────────────────────── -->
    <nav class="os-est-tabs" id="os-est-tab-nav">
        <button class="os-est-tab active" data-tab="tab-input">
            <span class="dashicons dashicons-edit"></span>
            <?php esc_html_e( 'Input & Estimate', 'olama-stores' ); ?>
        </button>
        <button class="os-est-tab" data-tab="tab-size-reg">
            <span class="dashicons dashicons-id"></span>
            <?php esc_html_e( 'Student Size Registration', 'olama-stores' ); ?>
        </button>
        <button class="os-est-tab" data-tab="tab-distribution">
            <span class="dashicons dashicons-performance"></span>
            <?php esc_html_e( 'Distribution Config', 'olama-stores' ); ?>
        </button>
        <button class="os-est-tab" data-tab="tab-results">
            <span class="dashicons dashicons-chart-bar"></span>
            <?php esc_html_e( 'Results & Charts', 'olama-stores' ); ?>
        </button>
        <button class="os-est-tab" data-tab="tab-supplier">
            <span class="dashicons dashicons-list-view"></span>
            <?php esc_html_e( 'Supplier Summary', 'olama-stores' ); ?>
        </button>
        <button class="os-est-tab" data-tab="tab-drafts">
            <span class="dashicons dashicons-portfolio"></span>
            <?php esc_html_e( 'Saved Drafts', 'olama-stores' ); ?>
        </button>
    </nav>

    <!-- ═══════════════════════════════════════════════════════
         TAB 1 — INPUT
    ══════════════════════════════════════════════════════════ -->
    <div class="os-est-tab-content active" id="tab-input">

        <div class="os-est-grid-2">

            <!-- Grade Inputs Card -->
            <div class="os-est-card">
                <div class="os-est-card-header">
                    <span class="dashicons dashicons-groups"></span>
                    <?php esc_html_e( 'Expected Students Per Grade', 'olama-stores' ); ?>
                </div>
                <div class="os-est-card-body">
                    <div class="os-est-grade-grid">
                        <?php
                        $grades = array(
                            'KG1'       => __( 'KG1',          'olama-stores' ),
                            'KG2'       => __( 'KG2',          'olama-stores' ),
                            'G1'        => __( 'Grade 1',      'olama-stores' ),
                            'G2'        => __( 'Grade 2',      'olama-stores' ),
                            'G3'        => __( 'Grade 3',      'olama-stores' ),
                            'G4'        => __( 'Grade 4',      'olama-stores' ),
                            'G5'        => __( 'Grade 5',      'olama-stores' ),
                            'G6'        => __( 'Grade 6',      'olama-stores' ),
                            'G7'        => __( 'Grade 7',      'olama-stores' ),
                            'G8'        => __( 'Grade 8',      'olama-stores' ),
                            'G9'        => __( 'Grade 9',      'olama-stores' ),
                            'G10_12'    => __( 'G10/G11/G12',  'olama-stores' ),
                        );
                        foreach ( $grades as $key => $label ) :
                        ?>
                            <div class="os-est-grade-row">
                                <label for="grade-<?php echo esc_attr( $key ); ?>" class="os-est-grade-label">
                                    <?php echo esc_html( $label ); ?>
                                </label>
                                <input
                                    type="number"
                                    id="grade-<?php echo esc_attr( $key ); ?>"
                                    class="os-est-grade-input"
                                    data-grade="<?php echo esc_attr( $key ); ?>"
                                    min="0"
                                    value="0"
                                    placeholder="0"
                                />
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Options Card -->
            <div class="os-est-card">
                <div class="os-est-card-header">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e( 'Estimation Options', 'olama-stores' ); ?>
                </div>
                <div class="os-est-card-body">

                    <!-- Safety Margin -->
                    <div class="os-est-option-group">
                        <label class="os-est-option-label">
                            <span class="dashicons dashicons-shield"></span>
                            <?php esc_html_e( 'Safety Margin Buffer', 'olama-stores' ); ?>
                        </label>
                        <div class="os-est-margin-btns">
                            <button class="os-est-margin-btn active" data-margin="0">0%</button>
                            <button class="os-est-margin-btn" data-margin="5">5%</button>
                            <button class="os-est-margin-btn" data-margin="10">10%</button>
                            <button class="os-est-margin-btn" data-margin="custom"><?php esc_html_e( 'Custom', 'olama-stores' ); ?></button>
                        </div>
                        <div id="os-custom-margin-wrap" style="display:none; margin-top:10px;">
                            <label><?php esc_html_e( 'Custom %:', 'olama-stores' ); ?></label>
                            <input type="number" id="os-custom-margin-val" min="0" max="100" value="0" style="width:80px;" />
                        </div>
                        <p class="os-est-hint"><?php esc_html_e( 'Adds a buffer on top of calculated quantities to cover unexpected demand.', 'olama-stores' ); ?></p>
                    </div>

                    <!-- Manual Adjustment Toggle -->
                    <div class="os-est-option-group">
                        <label class="os-est-option-label">
                            <span class="dashicons dashicons-edit-page"></span>
                            <?php esc_html_e( 'Manual Adjustment Mode', 'olama-stores' ); ?>
                        </label>
                        <label class="os-est-toggle">
                            <input type="checkbox" id="os-manual-mode" />
                            <span class="os-est-toggle-slider"></span>
                        </label>
                        <p class="os-est-hint"><?php esc_html_e( 'Allows overriding calculated quantities in the results table before export.', 'olama-stores' ); ?></p>
                    </div>

                    <!-- Draft Name -->
                    <div class="os-est-option-group">
                        <label class="os-est-option-label">
                            <span class="dashicons dashicons-portfolio"></span>
                            <?php esc_html_e( 'Draft Name (for saving)', 'olama-stores' ); ?>
                        </label>
                        <input type="text" id="os-draft-name" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. 2025-2026 Estimate', 'olama-stores' ); ?>" />
                    </div>

                </div>
                <div class="os-est-card-footer">
                    <button class="button button-primary button-hero" id="os-calculate-btn">
                        <span class="dashicons dashicons-calculator"></span>
                        <?php esc_html_e( 'Calculate Estimation', 'olama-stores' ); ?>
                    </button>
                    <button class="button" id="os-save-draft-btn">
                        <span class="dashicons dashicons-saved"></span>
                        <?php esc_html_e( 'Save Draft', 'olama-stores' ); ?>
                    </button>
                    <button class="button" id="os-reset-btn">
                        <span class="dashicons dashicons-undo"></span>
                        <?php esc_html_e( 'Reset', 'olama-stores' ); ?>
                    </button>
                </div>
            </div>

        </div><!-- /.os-est-grid-2 -->

        <!-- ── Survey Custom Models Selector ──────────────────────── -->
        <div class="os-est-card" style="margin-top: 20px;" id="os-cat-selector-card">
            <div class="os-est-card-header">
                <span class="dashicons dashicons-tag"></span>
                <?php esc_html_e( 'Uniform Models for Estimation', 'olama-stores' ); ?>
                <small style="font-weight: normal; font-size: 12px; margin-left: 8px; color: var(--est-muted, #6b7280);">
                    <?php esc_html_e( '— Only models marked "Include in Survey" can be selected', 'olama-stores' ); ?>
                </small>
            </div>
            <div class="os-est-card-body">
                <?php if ( empty( $all_custom_models ) ) : ?>
                    <p class="os-est-hint">
                        <?php esc_html_e( 'No custom models found. Add models in Settings → Custom Models.', 'olama-stores' ); ?>
                    </p>
                <?php else : ?>
                    <div id="os-cat-chips-wrap" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">
                        <?php foreach ( $all_custom_models as $model ) :
                            $in_survey = (int) $model->include_in_survey === 1;
                            $checked   = $in_survey ? 'checked' : '';
                            $disabled  = ! $in_survey ? 'disabled' : '';
                        ?>
                        <label class="os-cat-chip <?php echo $in_survey ? 'os-cat-chip-survey' : 'os-cat-chip-optional'; ?>"
                               for="os-model-<?php echo esc_attr( $model->id ); ?>"
                               title="<?php echo $in_survey ? esc_attr__( 'Include in Survey — toggleable', 'olama-stores' ) : esc_attr__( 'Not marked as survey item — excluded', 'olama-stores' ); ?>">
                            <input type="checkbox"
                                   id="os-model-<?php echo esc_attr( $model->id ); ?>"
                                   class="os-cat-selector"
                                   data-cat-id="<?php echo esc_attr( $model->id ); ?>"
                                   data-cat-name="<?php echo esc_attr( $model->name ); ?>"
                                   data-in-survey="<?php echo esc_attr( $in_survey ? '1' : '0' ); ?>"
                                   <?php echo $checked; ?>
                                   <?php echo $disabled; ?>
                                   style="display:none;">
                            <?php if ( $in_survey ) : ?>
                                <span class="dashicons dashicons-yes-alt" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span>
                            <?php else : ?>
                                <span class="dashicons dashicons-minus" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span>
                            <?php endif; ?>
                            <?php echo esc_html( $model->name ); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="os-est-hint" style="margin: 0;">
                        <span class="dashicons dashicons-info" style="color: var(--est-primary, #3b82f6);"></span>
                        <?php esc_html_e( 'Click a green category to toggle it on/off. Grey categories are excluded from standard survey estimation (configured in Settings → Categories).', 'olama-stores' ); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>



        <!-- Per-grade estimation preview -->
        <div id="os-grade-tables-wrap" style="display:none;">
            <div class="os-est-card" style="margin-top:20px;">
                <div class="os-est-card-header">
                    <span class="dashicons dashicons-editor-table"></span>
                    <?php esc_html_e( 'Per-Grade Size Estimation', 'olama-stores' ); ?>
                </div>
                <div class="os-est-card-body" id="os-grade-tables-inner">
                    <!-- dynamically populated -->
                </div>
            </div>
        </div>

    </div><!-- /#tab-input -->

    <!-- ═══════════════════════════════════════════════════════
         TAB 2 — DISTRIBUTION CONFIG
    ══════════════════════════════════════════════════════════ -->
    <div class="os-est-tab-content" id="tab-distribution">
        <div class="os-est-card">
            <div class="os-est-card-header">
                <span class="dashicons dashicons-performance"></span>
                <?php esc_html_e( 'Size Distribution Per Grade', 'olama-stores' ); ?>
                <div class="os-est-card-header-actions">
                    <button class="button" id="os-dist-reset-all-btn">
                        <span class="dashicons dashicons-undo"></span>
                        <?php esc_html_e( 'Reset All to Defaults', 'olama-stores' ); ?>
                    </button>
                    <button class="button button-primary" id="os-dist-save-btn">
                        <span class="dashicons dashicons-saved"></span>
                        <?php esc_html_e( 'Save Distribution', 'olama-stores' ); ?>
                    </button>
                </div>
            </div>
            <div class="os-est-card-body">
                <p class="os-est-hint" style="margin-bottom:16px;">
                    <span class="dashicons dashicons-info" style="color:var(--est-primary);"></span>
                    <?php esc_html_e( 'Adjust the size distribution percentages for each grade. Each grade\'s percentages must total 100%. You can add or remove sizes per grade.', 'olama-stores' ); ?>
                </p>
                <div id="os-dist-grades-container" class="os-dist-grades-grid">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
        </div>
    </div><!-- /#tab-distribution -->

    <!-- ═══════════════════════════════════════════════════════
         TAB 3 — RESULTS & CHARTS
    ══════════════════════════════════════════════════════════ -->
    <div class="os-est-tab-content" id="tab-results">

        <div id="os-no-results-msg" class="os-est-notice">
            <span class="dashicons dashicons-info"></span>
            <?php esc_html_e( 'Please enter grade counts and click "Calculate Estimation" first.', 'olama-stores' ); ?>
        </div>

        <div id="os-results-wrap" style="display:none;">

            <!-- Grand Total Table -->
            <div class="os-est-card">
                <div class="os-est-card-header">
                    <span class="dashicons dashicons-editor-table"></span>
                    <?php esc_html_e( 'Grand Total — All Grades Combined', 'olama-stores' ); ?>
                    <div class="os-est-card-header-actions">
                        <button class="button" id="os-print-btn">
                            <span class="dashicons dashicons-printer"></span>
                            <?php esc_html_e( 'Print', 'olama-stores' ); ?>
                        </button>
                        <button class="button" id="os-export-csv-btn">
                            <span class="dashicons dashicons-media-spreadsheet"></span>
                            <?php esc_html_e( 'Export CSV', 'olama-stores' ); ?>
                        </button>
                        <button class="button button-primary" id="os-export-excel-btn">
                            <span class="dashicons dashicons-media-spreadsheet"></span>
                            <?php esc_html_e( 'Export Excel', 'olama-stores' ); ?>
                        </button>
                    </div>
                </div>
                <div class="os-est-card-body">
                    <div id="os-grand-total-wrap"></div>
                </div>
            </div>

            <!-- Uniform Items Breakdown -->
            <div class="os-est-card" style="margin-top:20px;">
                <div class="os-est-card-header">
                    <span class="dashicons dashicons-shirt"></span>
                    <?php esc_html_e( 'Uniform Items Breakdown', 'olama-stores' ); ?>
                </div>
                <div class="os-est-card-body" id="os-items-breakdown-wrap"></div>
            </div>

            <!-- Charts -->
            <div class="os-est-grid-2" style="margin-top:20px;">
                <div class="os-est-card">
                    <div class="os-est-card-header">
                        <span class="dashicons dashicons-chart-pie"></span>
                        <?php esc_html_e( 'Size Distribution (Pie)', 'olama-stores' ); ?>
                    </div>
                    <div class="os-est-card-body os-chart-wrap">
                        <canvas id="os-pie-chart" height="300"></canvas>
                    </div>
                </div>
                <div class="os-est-card">
                    <div class="os-est-card-header">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php esc_html_e( 'Grade vs. Sizes (Bar)', 'olama-stores' ); ?>
                    </div>
                    <div class="os-est-card-body os-chart-wrap">
                        <canvas id="os-bar-chart" height="300"></canvas>
                    </div>
                </div>
            </div>

        </div><!-- /#os-results-wrap -->

    </div><!-- /#tab-results -->

    <!-- ═══════════════════════════════════════════════════════
         TAB 3 — SUPPLIER SUMMARY
    ══════════════════════════════════════════════════════════ -->
    <div class="os-est-tab-content" id="tab-supplier">

        <div id="os-supplier-wrap">
            <div class="os-est-card">
                <div class="os-est-card-header">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="dashicons dashicons-store"></span>
                        <?php esc_html_e( 'Supplier Purchase Summary', 'olama-stores' ); ?>
                        <select id="os-supplier-report-type" style="font-weight:normal; min-width:250px; font-size: 13px;">
                            <option value="1">1. Estimation Purchase Report</option>
                            <option value="2">2. Actual Scan Purchase Report</option>
                            <option value="3">3. Estimation → Inventory Report</option>
                            <option value="4">4. Actual Scan → Inventory Report</option>
                            <option value="5">5. Estimation Purchase with Cost</option>
                            <option value="6">6. Actual Scan with Cost</option>
                            <option value="7">7. Estimation → Inventory with Cost</option>
                            <option value="8">8. Actual Scan → Inventory with Cost</option>
                        </select>
                    </div>
                    <div class="os-est-card-header-actions">
                        <button class="button" id="os-export-supplier-csv-btn">
                            <span class="dashicons dashicons-media-spreadsheet"></span>
                            <?php esc_html_e( 'Export CSV', 'olama-stores' ); ?>
                        </button>
                        <button class="button button-primary" id="os-export-supplier-excel-btn">
                            <span class="dashicons dashicons-media-spreadsheet"></span>
                            <?php esc_html_e( 'Export Excel', 'olama-stores' ); ?>
                        </button>
                    </div>
                </div>
                <div class="os-est-card-body">
                    <div id="os-no-supplier-msg" class="os-est-notice">
                        <span class="dashicons dashicons-info"></span>
                        <?php esc_html_e( 'Calculate an estimation first to generate this report.', 'olama-stores' ); ?>
                    </div>
                    <div id="os-supplier-table-wrap" style="display:none;"></div>
                </div>
            </div>
        </div>

    </div><!-- /#tab-supplier -->

    <!-- ═══════════════════════════════════════════════════════
         TAB 4 — SAVED DRAFTS
    ══════════════════════════════════════════════════════════ -->
    <div class="os-est-tab-content" id="tab-drafts">
        <div class="os-est-card">
            <div class="os-est-card-header">
                <span class="dashicons dashicons-portfolio"></span>
                <?php esc_html_e( 'Saved Estimation Drafts', 'olama-stores' ); ?>
            </div>
            <div class="os-est-card-body" id="os-drafts-list-wrap">
                <?php if ( empty( $saved_drafts ) ) : ?>
                    <p class="os-est-notice">
                        <span class="dashicons dashicons-info"></span>
                        <?php esc_html_e( 'No drafts saved yet. Run an estimation and click "Save Draft".', 'olama-stores' ); ?>
                    </p>
                <?php else : ?>
                    <table class="wp-list-table widefat striped os-drafts-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Name', 'olama-stores' ); ?></th>
                                <th><?php esc_html_e( 'Saved On', 'olama-stores' ); ?></th>
                                <th><?php esc_html_e( 'Total Students', 'olama-stores' ); ?></th>
                                <th><?php esc_html_e( 'Actions', 'olama-stores' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $saved_drafts as $idx => $draft ) :
                                $total_students = array_sum( $draft['grades'] ?? array() );
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html( $draft['name'] ?? '-' ); ?></strong></td>
                                <td><?php echo esc_html( $draft['saved_at'] ?? '-' ); ?></td>
                                <td><?php echo esc_html( $total_students ); ?></td>
                                <td>
                                    <button class="button os-load-draft-btn" data-idx="<?php echo esc_attr( $idx ); ?>">
                                        <span class="dashicons dashicons-download"></span>
                                        <?php esc_html_e( 'Load', 'olama-stores' ); ?>
                                    </button>
                                    <button class="button os-delete-draft-btn" data-idx="<?php echo esc_attr( $idx ); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                        <?php esc_html_e( 'Delete', 'olama-stores' ); ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div><!-- /#tab-drafts -->
    <!-- ═══════════════════════════════════════════════════════
         TAB — STUDENT SIZE REGISTRATION
    ══════════════════════════════════════════════════════════ -->
    <div class="os-est-tab-content" id="tab-size-reg">

        <?php if ( ! $active_year ) : ?>
            <div class="os-est-notice">
                <span class="dashicons dashicons-warning"></span>
                <?php esc_html_e( 'No active academic year found in Olama School. Please activate an academic year first.', 'olama-stores' ); ?>
            </div>
        <?php else : ?>

        <!-- ── Context Banner (read-only) ── -->
        <div class="os-sr-context-banner">
            <div class="os-sr-context-item">
                <span class="os-sr-context-icon dashicons dashicons-calendar-alt"></span>
                <div>
                    <div class="os-sr-context-label"><?php esc_html_e( 'Academic Year', 'olama-stores' ); ?></div>
                    <div class="os-sr-context-value"><?php echo esc_html( $active_year_name ); ?></div>
                </div>
                <span class="os-sr-context-badge os-sr-badge-active"><?php esc_html_e( 'Active', 'olama-stores' ); ?></span>
            </div>
            <div class="os-sr-context-divider"></div>
            <div class="os-sr-context-item">
                <span class="os-sr-context-icon dashicons dashicons-book-alt"></span>
                <div>
                    <div class="os-sr-context-label"><?php esc_html_e( 'Semester', 'olama-stores' ); ?></div>
                    <div class="os-sr-context-value">
                        <?php echo $active_sem_name ?: '<em>' . esc_html__( 'No active semester', 'olama-stores' ) . '</em>'; ?>
                    </div>
                </div>
                <?php if ( $active_sem_name ) : ?>
                    <span class="os-sr-context-badge os-sr-badge-active"><?php esc_html_e( 'Active', 'olama-stores' ); ?></span>
                <?php endif; ?>
            </div>
            <!-- hidden inputs carry the IDs for JS -->
            <input type="hidden" id="os-sr-year-id"  value="<?php echo esc_attr( $active_year_id ); ?>">
            <input type="hidden" id="os-sr-year-name" value="<?php echo esc_attr( $active_year_name ); ?>">
            <input type="hidden" id="os-sr-sem-id"   value="<?php echo esc_attr( $active_sem_id ); ?>">
        </div>

        <!-- ── Filters Bar ── -->
        <div class="os-sr-filters-bar">
            <!-- Grade: real data from olama_grades -->
            <div class="os-sr-filter-group">
                <label class="os-sr-filter-label" for="os-sr-grade">
                    <span class="dashicons dashicons-groups"></span>
                    <?php esc_html_e( 'Grade', 'olama-stores' ); ?>
                </label>
                <select id="os-sr-grade" class="os-sr-select">
                    <option value=""><?php esc_html_e( '— Select Grade —', 'olama-stores' ); ?></option>
                    <?php foreach ( $school_grades as $g ) : ?>
                        <option value="<?php echo esc_attr( $g->id ); ?>"
                                data-name="<?php echo esc_attr( $g->grade_name ); ?>">
                            <?php echo esc_html( $g->grade_name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Section: populated via AJAX after grade is chosen -->
            <div class="os-sr-filter-group">
                <label class="os-sr-filter-label" for="os-sr-section">
                    <span class="dashicons dashicons-networking"></span>
                    <?php esc_html_e( 'Section', 'olama-stores' ); ?>
                </label>
                <select id="os-sr-section" class="os-sr-select" disabled>
                    <option value=""><?php esc_html_e( '— Select Grade First —', 'olama-stores' ); ?></option>
                </select>
                <span id="os-sr-section-spinner" class="os-est-spinner" style="display:none;margin-top:4px;"></span>
            </div>

            <!-- Quick filter -->
            <div class="os-sr-filter-group">
                <label class="os-sr-filter-label" for="os-sr-filter">
                    <span class="dashicons dashicons-filter"></span>
                    <?php esc_html_e( 'Show', 'olama-stores' ); ?>
                </label>
                <select id="os-sr-filter" class="os-sr-select">
                    <option value="all"><?php esc_html_e( 'All Students', 'olama-stores' ); ?></option>
                    <option value="unsized"><?php esc_html_e( 'Unsized Only', 'olama-stores' ); ?></option>
                    <option value="recent"><?php esc_html_e( 'Recently Sized', 'olama-stores' ); ?></option>
                </select>
            </div>

            <button id="os-sr-load-btn" class="button button-primary os-sr-load-btn">
                <span class="dashicons dashicons-search"></span>
                <?php esc_html_e( 'Load Students', 'olama-stores' ); ?>
            </button>
        </div><!-- /.os-sr-filters-bar -->

        <?php endif; // end active_year check ?>

        <!-- ── Stats Bar ── -->
        <div id="os-sr-stats-bar" class="os-sr-stats-bar" style="display:none;">
            <div class="os-sr-stat os-sr-stat-total">
                <span class="os-sr-stat-icon dashicons dashicons-groups"></span>
                <div>
                    <div class="os-sr-stat-num" id="os-sr-stat-total">0</div>
                    <div class="os-sr-stat-lbl"><?php esc_html_e( 'Total Students', 'olama-stores' ); ?></div>
                </div>
            </div>
            <div class="os-sr-stat os-sr-stat-sized">
                <span class="os-sr-stat-icon dashicons dashicons-yes-alt"></span>
                <div>
                    <div class="os-sr-stat-num" id="os-sr-stat-sized">0</div>
                    <div class="os-sr-stat-lbl"><?php esc_html_e( 'Sized', 'olama-stores' ); ?></div>
                </div>
            </div>
            <div class="os-sr-stat os-sr-stat-unsized">
                <span class="os-sr-stat-icon dashicons dashicons-clock"></span>
                <div>
                    <div class="os-sr-stat-num" id="os-sr-stat-unsized">0</div>
                    <div class="os-sr-stat-lbl"><?php esc_html_e( 'Remaining', 'olama-stores' ); ?></div>
                </div>
            </div>
            <div class="os-sr-stat os-sr-stat-pct">
                <div class="os-sr-progress-wrap">
                    <div class="os-sr-progress-ring-wrap">
                        <svg class="os-sr-ring" viewBox="0 0 36 36">
                            <circle class="os-sr-ring-bg" cx="18" cy="18" r="15.9155" />
                            <circle class="os-sr-ring-fill" id="os-sr-ring-fill" cx="18" cy="18" r="15.9155"
                                stroke-dasharray="0 100" />
                        </svg>
                        <span class="os-sr-ring-pct" id="os-sr-ring-pct">0%</span>
                    </div>
                    <div class="os-sr-stat-lbl"><?php esc_html_e( 'Completion', 'olama-stores' ); ?></div>
                </div>
            </div>

            <div class="os-sr-stats-actions">
                <button id="os-sr-export-csv-btn" class="button">
                    <span class="dashicons dashicons-media-spreadsheet"></span>
                    <?php esc_html_e( 'Export CSV', 'olama-stores' ); ?>
                </button>
                <button id="os-sr-print-empty-btn" class="button">
                    <span class="dashicons dashicons-media-text"></span>
                    <?php esc_html_e( 'Print Empty Form', 'olama-stores' ); ?>
                </button>
                <button id="os-sr-print-btn" class="button">
                    <span class="dashicons dashicons-printer"></span>
                    <?php esc_html_e( 'Print', 'olama-stores' ); ?>
                </button>
            </div>
        </div>

        <!-- ── Sizes Summary ── -->
        <div id="os-sr-size-summary" class="os-sr-size-summary" style="display:none;">
            <!-- populated by JS -->
        </div>

        <!-- ── Student Table ── -->
        <div id="os-sr-table-wrap" class="os-sr-table-wrap" style="display:none;">
            <div class="os-sr-table-header">
                <div class="os-sr-search-wrap">
                    <span class="dashicons dashicons-search"></span>
                    <input type="text" id="os-sr-search" placeholder="<?php esc_attr_e( 'Search student name…', 'olama-stores' ); ?>" class="os-sr-search-input" />
                </div>
                <div class="os-sr-legend">
                    <span class="os-sr-legend-item os-sr-legend-sized"><?php esc_html_e( 'Sized', 'olama-stores' ); ?></span>
                    <span class="os-sr-legend-item os-sr-legend-unsized"><?php esc_html_e( 'Not Sized', 'olama-stores' ); ?></span>
                </div>
            </div>
            <div class="os-sr-scroll-wrap">
                <table class="os-sr-table" id="os-sr-students-table">
                    <thead id="os-sr-table-head"></thead>
                    <tbody id="os-sr-table-body"></tbody>
                </table>
            </div>
            <div class="os-sr-table-footer">
                <span id="os-sr-row-count" class="os-sr-row-count"></span>
            </div>
        </div>

        <!-- ── Empty / Loading states ── -->
        <div id="os-sr-empty" class="os-est-notice" style="display:none;">
            <span class="dashicons dashicons-info"></span>
            <?php esc_html_e( 'No students found for the selected criteria.', 'olama-stores' ); ?>
        </div>
        <div id="os-sr-initial" class="os-est-notice">
            <span class="dashicons dashicons-id"></span>
            <?php esc_html_e( 'Select an academic year, grade and section, then click "Load Students".', 'olama-stores' ); ?>
        </div>
        <div id="os-sr-spinner" style="display:none; text-align:center; padding:30px;">
            <span class="os-est-spinner"></span>
            <?php esc_html_e( 'Loading students…', 'olama-stores' ); ?>
        </div>

    </div><!-- /#tab-size-reg -->


</div><!-- /#os-estimation-page -->

<!-- Hidden data for JS -->
<script id="os-estimation-data" type="application/json">
<?php echo wp_json_encode( array(
    'nonce'             => $estimation_nonce,
    'uniformSizeNonce'  => $uniform_size_nonce,
    'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
    'savedDrafts'       => $saved_drafts,
    'savedDistribution' => $saved_distribution,
    'activeYear'        => $active_year_name,
    'activeYearId'      => $active_year_id,
    'activeSemId'       => $active_sem_id,
    'activeSemName'     => $active_sem_name,
    'allCustomModels'   => array_map( function( $m ) {
        return array(
            'id'                => (int) $m->id,
            'name'              => $m->name,
            'include_in_survey' => (int) $m->include_in_survey,
            'calculation_type'  => $m->calculation_type ?? 'auto',
        );
    }, $all_custom_models ),
) ); ?>
</script>




<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<!-- SheetJS for Excel export -->
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>

<?php
// Enqueue the estimation JS
wp_enqueue_script(
    'os-order-estimation',
    OS_URL . 'admin/assets/js/os-order-estimation.js',
    array( 'jquery' ),
    OS_VERSION,
    true
);
// Enqueue the estimation CSS
wp_enqueue_style(
    'os-order-estimation',
    OS_URL . 'admin/assets/css/os-order-estimation.css',
    array(),
    OS_VERSION
);
?>
<style>
/* ── Category Chip Selector ── */
.os-cat-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    user-select: none;
    transition: all 0.2s ease;
    border: 2px solid transparent;
}
.os-cat-chip-survey {
    background: #dcfce7;
    color: #15803d;
    border-color: #86efac;
}
.os-cat-chip-survey:hover {
    background: #bbf7d0;
    border-color: #4ade80;
}
.os-cat-chip-survey.os-cat-deselected {
    background: #f9fafb;
    color: #9ca3af;
    border-color: #e5e7eb;
}
.os-cat-chip-survey.os-cat-deselected:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}
.os-cat-chip-optional {
    background: #f3f4f6;
    color: #9ca3af;
    border-color: #e5e7eb;
    cursor: not-allowed;
    opacity: 0.7;
}
</style>

