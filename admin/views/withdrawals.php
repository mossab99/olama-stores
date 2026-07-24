<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$custom_withdrawal_nonce = wp_create_nonce( 'wp_rest' );
?>
<div class="wrap os-wrap" id="os-withdrawals-page">
    <h1 class="os-page-title">
        <span class="dashicons dashicons-cart"></span>
        <?php esc_html_e( 'Student Withdrawals', 'olama-stores' ); ?>
        <!-- REC-17: Barcode scanner mode toggle -->
        <button type="button" id="os-barcode-toggle" class="page-title-action" title="<?php esc_attr_e( 'Toggle Barcode Scanner Mode', 'olama-stores' ); ?>" style="background:none; border:1px solid #ddd; border-radius:4px; padding:4px 10px; cursor:pointer; vertical-align:middle;">
            <span class="dashicons dashicons-performance" style="vertical-align:middle;"></span>
            <span id="os-barcode-mode-label"><?php esc_html_e( 'Scan Mode: OFF', 'olama-stores' ); ?></span>
        </button>
        <span class="os-year-badge"><?php echo esc_html( os_get_active_year_name() ); ?></span>
    </h1>

    <nav class="os-cwa-tabs" id="os-cwa-tab-nav">
        <button type="button" class="os-cwa-tab active" data-tab="os-cwa-approval-tab">
            <span class="dashicons dashicons-yes-alt"></span>
            <?php esc_html_e( '1. Withdrawal Approval', 'olama-stores' ); ?>
        </button>
        <button type="button" class="os-cwa-tab" data-tab="os-cwa-withdraw-tab">
            <span class="dashicons dashicons-cart"></span>
            <?php esc_html_e( '2. Custom Withdrawal', 'olama-stores' ); ?>
        </button>
    </nav>

    <div class="os-cwa-tab-content active" id="os-cwa-approval-tab">
        <div class="os-cwa-notice">
            <span class="dashicons dashicons-lock"></span>
            <?php esc_html_e( 'Students are blocked from custom withdrawal by default. Confirm payment, select the permitted students, then save approval.', 'olama-stores' ); ?>
        </div>

        <div class="os-cwa-card">
            <div class="os-cwa-card-header">
                <span class="dashicons dashicons-search"></span>
                <?php esc_html_e( 'Find Family for Approval', 'olama-stores' ); ?>
            </div>
            <div class="os-cwa-card-body">
                <div class="os-cwa-lookup">
                    <input type="text" id="os-cwa-family-id" placeholder="<?php esc_attr_e( 'Enter Family Number / Name...', 'olama-stores' ); ?>">
                    <button type="button" id="os-cwa-load-family" class="button button-primary">
                        <?php esc_html_e( 'Search Families', 'olama-stores' ); ?>
                    </button>
                </div>
                <div id="os-cwa-family-matches" class="os-cwa-family-matches" hidden aria-live="polite"></div>
            </div>
        </div>

        <div class="os-cwa-card" id="os-cwa-results" hidden>
            <div class="os-cwa-card-header">
                <span class="dashicons dashicons-groups"></span>
                <span id="os-cwa-family-title"></span>
            </div>
            <div class="os-cwa-card-body">
                <div class="os-cwa-table-wrap">
                    <table class="os-cwa-table">
                        <thead>
                            <tr>
                                <th class="os-cwa-check-column">
                                    <input type="checkbox" id="os-cwa-check-all">
                                </th>
                                <th><?php esc_html_e( 'Student', 'olama-stores' ); ?></th>
                                <th><?php esc_html_e( 'Grade / Section', 'olama-stores' ); ?></th>
                                <th><?php esc_html_e( 'Approval Status', 'olama-stores' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="os-cwa-students"></tbody>
                    </table>
                </div>
            </div>
            <div class="os-cwa-card-footer">
                <span id="os-cwa-selected-count"></span>
                <button type="button" id="os-cwa-save" class="button button-primary">
                    <?php esc_html_e( 'Save Student Approvals', 'olama-stores' ); ?>
                </button>
            </div>
        </div>
    </div>

    <div class="os-cwa-tab-content" id="os-cwa-withdraw-tab">
        <div class="os-cwa-notice">
            <span class="dashicons dashicons-info"></span>
            <?php esc_html_e( 'Enter the family ID again. Only students approved in step 1 can select and withdraw custom items.', 'olama-stores' ); ?>
        </div>

    <!-- Family Lookup Section -->
    <div class="os-section-card" style="margin-bottom: 2rem;">
        <div class="os-section-header">
            <h2><span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Find Family', 'olama-stores' ); ?></h2>
        </div>
        <div class="os-filters">
            <input type="text" id="os-family-search" class="os-filter-input" style="min-width: 300px;" placeholder="<?php esc_attr_e( 'Enter Family Number / Name...', 'olama-stores' ); ?>">
            <button type="button" id="os-btn-family-lookup" class="button button-primary"><?php esc_html_e( 'Retrieve Family Students', 'olama-stores' ); ?></button>
        </div>
    </div>

    <!-- Family Results Container -->
    <div id="os-family-results-wrap" style="display:none;">
        <div class="os-section-card">
            <div class="os-section-header">
                <h2><span class="dashicons dashicons-groups"></span> <?php esc_html_e( 'Family Members:', 'olama-stores' ); ?> <span id="os-active-family-id" style="color:var(--os-primary);"></span></h2>
            </div>
            <div id="os-family-students-list"></div>
        </div>
        
        <div class="os-section-card" style="margin-top: 2rem; display:none;" id="os-family-withdrawals-wrap">
            <div class="os-section-header">
                <h2><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Withdrawals History', 'olama-stores' ); ?></h2>
                <button type="button" id="os-btn-family-receipt" class="button os-cwa-family-receipt no-print" style="display:none;">
                    <span class="dashicons dashicons-printer"></span>
                    <?php esc_html_e( 'Family Receipt', 'olama-stores' ); ?>
                </button>
            </div>
            <div id="os-family-withdrawals-list"></div>
        </div>
    </div>
    </div>

    <!-- Batch Assignment Modal (hidden) -->
    <div id="os-withdraw-modal" class="os-modal" style="display:none;">
        <div class="os-modal-content">
            <h2 id="os-withdraw-modal-title"><?php esc_html_e( 'Withdraw Items', 'olama-stores' ); ?></h2>
            
            <div class="os-form-row">
                <label><?php esc_html_e( 'Student', 'olama-stores' ); ?></label>
                <input type="text" id="os-wd-student-name" readonly>
                <input type="hidden" id="os-wd-student-uid">
            </div>

            <!-- Student Sizes Display -->
            <div id="os-wd-student-sizes-wrap" style="margin-top: 10px; padding: 10px 14px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; display:none; font-size: 0.85rem; color: #1e3a8a;">
                <strong><?php esc_html_e( 'Registered Uniform Sizes:', 'olama-stores' ); ?></strong>
                <span id="os-wd-student-sizes-text" style="margin-left: 8px;"></span>
            </div>

            <!-- Entitlements Checklist Panel -->
            <div id="os-wd-entitlements-panel" style="margin-top: 10px; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; display:none;">
                <h4 style="margin: 0 0 6px 0; font-size: 0.85rem; font-weight:600; color: #334155;"><?php esc_html_e( 'Academic Year Entitlement Checklist:', 'olama-stores' ); ?></h4>
                <div id="os-wd-entitlements-status" style="display:flex; gap:15px; flex-wrap:wrap; font-size:0.8rem; color: #475569;"></div>
            </div>
            
            <div class="os-form-row">
                <label><?php esc_html_e( 'Warehouse', 'olama-stores' ); ?></label>
                <select id="os-wd-warehouse" required></select>
            </div>

            <div style="margin-top: 24px; width: 100%;">
                <div class="os-items-header">
                    <div style="width: 80px; flex: 0 0 80px;"><?php esc_html_e( 'Qty', 'olama-stores' ); ?></div>
                    <div style="flex: 1; padding-left: 12px;"><?php esc_html_e( 'Item (Search & Select)', 'olama-stores' ); ?></div>
                    <div style="width: 36px; flex: 0 0 36px;"></div>
                </div>
                <div class="os-withdrawal-items" id="os-withdrawal-items-container">
                    <!-- Dynamic rows injected by JS -->
                </div>
                <div style="margin-top: 10px;">
                    <button type="button" id="os-btn-add-item-row" class="button">
                        <span class="dashicons dashicons-plus-alt2" style="margin-top:3px;"></span>
                        <?php esc_html_e( 'Add Item', 'olama-stores' ); ?>
                    </button>
                    <span id="os-wd-row-limit-msg" style="display:none; margin-left:10px; color:#d63638; font-size:0.85em;">
                        <?php esc_html_e( 'Maximum 10 items per withdrawal.', 'olama-stores' ); ?>
                    </span>
                </div>
            </div>


            <div class="os-form-row" style="margin-top: 24px;">
                <label><?php esc_html_e( 'Notes', 'olama-stores' ); ?></label>
                <textarea id="os-wd-notes" placeholder="<?php esc_attr_e( 'e.g. Uniform distribution', 'olama-stores' ); ?>"></textarea>
            </div>

            <div class="os-form-actions">
                <button type="button" class="button button-primary" id="os-btn-confirm-wd"><?php esc_html_e( 'Confirm Withdrawal', 'olama-stores' ); ?></button>
                <button type="button" class="button os-modal-close"><?php esc_html_e( 'Cancel', 'olama-stores' ); ?></button>
            </div>
        </div>
    </div>

    <!-- REC-07: Hidden Print Receipt (shown only when printing) -->
    <div id="os-print-receipt" style="display:none;">
        <div class="os-receipt-header">
            <h2><?php esc_html_e( 'Withdrawal Receipt', 'olama-stores' ); ?></h2>
            <p><strong><?php esc_html_e( 'Date:', 'olama-stores' ); ?></strong> <span id="os-receipt-date"></span></p>
            <p><strong><?php esc_html_e( 'Academic Year:', 'olama-stores' ); ?></strong> <?php echo esc_html( os_get_active_year_name() ); ?></p>
        </div>
        <p><strong><?php esc_html_e( 'Recipient:', 'olama-stores' ); ?></strong> <span id="os-receipt-student-name"></span></p>
        <div id="os-receipt-items-table"></div>
        <div class="os-receipt-footer">
            <div class="os-receipt-sig-line"><?php esc_html_e( 'Received by:', 'olama-stores' ); ?> ___________________________</div>
            <div class="os-receipt-sig-line"><?php esc_html_e( 'Issued by:', 'olama-stores' ); ?> ___________________________</div>
        </div>
    </div>

</div>

<script>
(function($){
    var warehouses = [], items = [];

    // Preload meta
    wp.apiFetch({ path: '/olama-stores/v1/warehouses' }).then(function(res){
        warehouses = res;
        var whOpts = '<option value=""></option>';
        warehouses.forEach(function(w){ whOpts += '<option value="'+w.id+'">'+w.name+'</option>'; });
        $('#os-wd-warehouse').html(whOpts);
    });



    // Lookup
    $('#os-btn-family-lookup').on('click', function(){
        var search = $('#os-family-search').val();
        if(!search) return;
        
        $('#os-active-family-id').text(search);
        $('#os-family-results-wrap').show();
        $('#os-btn-family-receipt').hide();
        $('#os-family-students-list').html('<p class="os-loading"><?php esc_html_e("Searching families...","olama-stores");?></p>');

        wp.apiFetch({ path: '/olama-stores/v1/students?search=' + encodeURIComponent(search) + '&academic_year_id=' + olamaStores.activeYearId }).then(function(students){
            if(!students.length){
                $('#os-family-students-list').html('<p><?php esc_html_e("No students found for this family.","olama-stores");?></p>');
                $('#os-family-withdrawals-wrap').hide();
                $(document).trigger('os:cwa-family-history', [{ family: {}, students: [], withdrawals: [] }]);
                return;
            }
            renderStudents(students);
            fetchFamilyWithdrawals(students, search);
        });
    });

    function fetchFamilyWithdrawals(students, familyId) {
        $('#os-family-withdrawals-wrap').show();
        $('#os-family-withdrawals-list').html('<p class="os-loading"><?php esc_html_e("Loading history...","olama-stores");?></p>');
        
        var promises = students.map(function(s) {
            return wp.apiFetch({ path: '/olama-stores/v1/assignees/student/' + s.student_uid + '?academic_year_id=' + olamaStores.activeYearId });
        });
        var familyPromise = wp.apiFetch({
            path: '/olama-stores/v1/custom-withdrawal/approvals?family_id='
                + encodeURIComponent(familyId)
                + '&academic_year_id='
                + olamaStores.activeYearId
        });

        Promise.all([Promise.all(promises), familyPromise]).then(function(response) {
            var results = response[0];
            var familyScope = response[1] || {};
            var allWithdrawals = [];
            results.forEach(function(withdrawals, index) {
                var studentName = students[index].student_name;
                withdrawals.forEach(function(w) {
                    if (w.warehouse_type === 'custom') {
                        w.student_name = studentName;
                        allWithdrawals.push(w);
                    }
                });
            });

            // Sort by date desc
            allWithdrawals.sort(function(a, b) {
                return new Date(b.created_at) - new Date(a.created_at);
            });

            students.forEach(function(student) {
                student.custom_issued = allWithdrawals.some(function(withdrawal) {
                    return withdrawal.assignee_id === student.student_uid
                        && withdrawal.status === 'active'
                        && (parseInt(withdrawal.quantity_assigned || 0, 10) - parseInt(withdrawal.quantity_returned || 0, 10)) > 0;
                });
            });

            renderStudents(students);
            renderWithdrawals(allWithdrawals);
            $(document).trigger('os:cwa-family-history', [{
                family: familyScope.family || {},
                students: students,
                withdrawals: allWithdrawals
            }]);
        }).catch(function(error) {
            $('#os-family-withdrawals-list').html('<p><?php esc_html_e("Unable to load family withdrawal history.","olama-stores");?></p>');
            $(document).trigger('os:cwa-family-history', [{ family: {}, students: students, withdrawals: [] }]);
            if (window.console && console.error) {
                console.error(error);
            }
        });
    }

    function renderWithdrawals(withdrawals) {
        if (!withdrawals.length) {
            $('#os-family-withdrawals-list').html('<p><?php esc_html_e("No withdrawals recorded for this family yet.","olama-stores");?></p>');
            return;
        }

        var html = '<table class="wp-list-table widefat striped">'
            + '<thead><tr>'
            + '<th><?php esc_html_e("Date","olama-stores");?></th>'
            + '<th><?php esc_html_e("Student","olama-stores");?></th>'
            + '<th><?php esc_html_e("Item","olama-stores");?></th>'
            + '<th style="text-align:center;"><?php esc_html_e("Qty","olama-stores");?></th>'
            + '<th><?php esc_html_e("Warehouse","olama-stores");?></th>'
            + '<th><?php esc_html_e("Status","olama-stores");?></th>'
            + '<th><?php esc_html_e("Action","olama-stores");?></th>'
            + '</tr></thead><tbody>';

        withdrawals.forEach(function(w) {
            var date      = new Date(w.assigned_date).toLocaleDateString();
            var isActive  = (w.status === 'active');
            var isReversed= (w.status === 'reversed');

            // Row styling: muted for reversed rows
            var rowStyle  = isReversed ? ' style="opacity:0.6;"' : '';

            // Transaction type pill
            var txType = isReversed
                ? '<span class="os-tx-pill os-tx-return">↩ <?php esc_html_e("Reversed","olama-stores");?></span>'
                : '<span class="os-tx-pill os-tx-issue">↓ <?php esc_html_e("Issued","olama-stores");?></span>';

            // Action column
            var actionCell = '';
            if (isActive) {
                actionCell = '<button class="button button-small os-btn-reverse" '
                    + 'data-id="' + w.id + '" '
                    + 'data-item="' + w.item_name + '" '
                    + 'data-qty="' + w.quantity_assigned + '">'
                    + '↩ <?php esc_html_e("Reverse","olama-stores");?>'
                    + '</button>';
            } else if (isReversed) {
                actionCell = '<span style="color:#6c757d; font-size:0.8rem;"><?php esc_html_e("Reversed","olama-stores");?></span>';
            }

            html += '<tr' + rowStyle + '>'
                + '<td>' + date + '<br><small style="color:#6c757d;">' + txType + '</small></td>'
                + '<td>' + w.student_name + '</td>'
                + '<td><strong>' + w.item_name + '</strong><br><small>' + w.sku + '</small></td>'
                + '<td style="text-align:center;">' + w.quantity_assigned + '</td>'
                + '<td>' + w.warehouse_name + '</td>'
                + '<td><span class="os-badge os-badge-' + w.status + '">' + w.status.replace('_',' ') + '</span></td>'
                + '<td>' + actionCell + '</td>'
                + '</tr>';
        });

        html += '</tbody></table>';
        $('#os-family-withdrawals-list').html(html);
    }

    // Handle Reverse button click
    $(document).on('click', '.os-btn-reverse', function() {
        var $btn = $(this);
        var id   = $btn.data('id');
        var item = $btn.data('item');
        var qty  = $btn.data('qty');

        var msg = '<?php esc_html_e("Reverse withdrawal of","olama-stores");?> ' + qty + 'x ' + item + '?\n'
                + '<?php esc_html_e("Stock will be restored to the warehouse.","olama-stores");?>';

        if (!confirm(msg)) return;

        $btn.prop('disabled', true).text('<?php esc_html_e("Processing...","olama-stores");?>');

        wp.apiFetch({
            path:   '/olama-stores/v1/assignments/' + id + '/reverse',
            method: 'POST',
            data:   { notes: '<?php esc_html_e("Reversed via Student Withdrawals admin panel.","olama-stores");?>' }
        }).then(function() {
            // Refresh family lookup to show updated table
            $('#os-btn-family-lookup').trigger('click');
        }).catch(function(e) {
            alert(e.message || '<?php esc_html_e("Reversal failed. Please try again.","olama-stores");?>');
            $btn.prop('disabled', false).text('↩ <?php esc_html_e("Reverse","olama-stores");?>');
        });
    });


    function renderStudents(students){
        var html = '<table class="wp-list-table widefat striped"><thead><tr>'
            + '<th><?php esc_html_e("Name","olama-stores");?></th>'
            + '<th><?php esc_html_e("Grade / Section","olama-stores");?></th>'
            + '<th><?php esc_html_e("Approval","olama-stores");?></th>'
            + '<th><?php esc_html_e("Issue Status","olama-stores");?></th>'
            + '<th><?php esc_html_e("Actions","olama-stores");?></th>'
            + '</tr></thead><tbody>';
        students.forEach(function(s){
            var allowed = s.custom_withdrawal_allowed === true || s.custom_withdrawal_allowed === 1;
            var status = allowed
                ? '<span class="os-cwa-status os-cwa-status-approved"><?php esc_html_e("Approved","olama-stores");?></span>'
                : '<span class="os-cwa-status os-cwa-status-blocked"><?php esc_html_e("Blocked","olama-stores");?></span>';
            var action = allowed
                ? '<button class="button button-small os-btn-wd" data-uid="'+s.student_uid+'" data-name="'+s.student_name+'" data-grade-id="'+(s.grade_id||'')+'"><?php esc_html_e("Select Items","olama-stores");?></button>'
                : '<button class="button button-small" disabled title="<?php esc_attr_e("Approval is required before custom withdrawal.","olama-stores");?>"><?php esc_html_e("Approval Required","olama-stores");?></button>';
            var issueStatus = s.custom_issued
                ? '<span class="os-cwa-status os-cwa-status-issued"><?php esc_html_e("Issued","olama-stores");?></span>'
                : '<span class="os-cwa-status os-cwa-status-not-issued"><?php esc_html_e("Not Issued","olama-stores");?></span>';
            html += '<tr>'
                + '<td><strong>'+s.student_name+'</strong><br><small>UID: '+s.student_uid+'</small></td>'
                + '<td>'+(s.grade_name||'')+' — '+(s.section_name||'')+'</td>'
                + '<td>'+status+'</td>'
                + '<td>'+issueStatus+'</td>'
                + '<td>'+action+'</td>'
                + '</tr>';
        });
        html += '</tbody></table>';
        $('#os-family-students-list').html(html);
    }

    // ── Dynamic item row management (REC-05) ──────────────────────────────────
    var OS_WD_MAX_ROWS = 10;

    function makeItemRow() {
        var rowIndex = Date.now(); // unique key per row
        return $('<div class="os-item-row">').append(
            $('<div style="display:flex;gap:12px;align-items:center;width:100%;">').append(
                $('<input type="number" class="os-wd-qty-input" min="1" value="1">').css({width:'80px',flex:'0 0 80px'}),
                $('<div class="os-input-group" style="flex:1;min-width:0;">').append(
                    $('<input type="text" class="os-modal-item-search">').attr('placeholder', '<?php esc_attr_e( 'Search items...', 'olama-stores' ); ?>'),
                    $('<select class="os-wd-item-select"></select>')
                ),
                $('<button type="button" class="button button-small os-btn-remove-row" title="<?php esc_attr_e( 'Remove row', 'olama-stores' ); ?>">').html('&times;')
            ),
            $('<div class="os-row-stock-avail"></div>')
        );
    }

    function resetWithdrawalRows() {
        var $container = $('#os-withdrawal-items-container');
        $container.empty();
        var $firstRow = makeItemRow();
        $firstRow.find('.os-btn-remove-row').hide(); // always keep at least 1 row
        $container.append($firstRow);
        $('#os-wd-row-limit-msg').hide();
    }

    function updateRemoveButtons() {
        var rows = $('#os-withdrawal-items-container .os-item-row');
        rows.find('.os-btn-remove-row').show();
        if (rows.length === 1) rows.first().find('.os-btn-remove-row').hide();
        var atMax = rows.length >= OS_WD_MAX_ROWS;
        $('#os-btn-add-item-row').prop('disabled', atMax);
        $('#os-wd-row-limit-msg').toggle(atMax);
    }

    $('#os-btn-add-item-row').on('click', function(){
        var $container = $('#os-withdrawal-items-container');
        if ($container.find('.os-item-row').length >= OS_WD_MAX_ROWS) return;
        $container.append(makeItemRow());
        // Pre-load default custom items in the new row's dropdown
        var $newRow = $container.find('.os-item-row').last();
        window.osSearchItems('', $newRow.find('.os-wd-item-select'), null, { per_page: 5, is_custom: 1 });
        updateRemoveButtons();
    });

    $(document).on('click', '.os-btn-remove-row', function(){
        $(this).closest('.os-item-row').remove();
        updateRemoveButtons();
    });
    // ─────────────────────────────────────────────────────────────────────────

    // Open Modal
    $(document).on('click', '.os-btn-wd', function(){
        var uid = $(this).data('uid'), name = $(this).data('name'), gradeId = $(this).data('grade-id');
        $('#os-wd-student-uid').val(uid);
        $('#os-wd-student-name').val(name);

        // Reset to 1 fresh row
        resetWithdrawalRows();
        updateRemoveButtons();

        // Hide panels
        $('#os-wd-student-sizes-wrap').hide();
        $('#os-wd-entitlements-panel').hide();
        $('#os-wd-entitlements-status').empty();

        // Reset warehouse to custom by default and lock it
        var customWh = warehouses.find(function(w){ return w.type === 'custom'; });
        if (customWh) {
            $('#os-wd-warehouse').val(customWh.id).prop('disabled', true);
        } else {
            $('#os-wd-warehouse').prop('disabled', false).val('');
        }

        $('#os-withdraw-modal').show();

        window.osStudentActiveItems = [];

        // Fetch student active assignments, sizes, and entitlements in parallel
        Promise.all([
            wp.apiFetch({ path: '/olama-stores/v1/assignees/student/' + uid + '?academic_year_id=' + (olamaStores.activeYearId||'') }),
            wp.apiFetch({ path: '/olama-stores/v1/uniform-sizes/student/' + uid + '?academic_year_id=' + (olamaStores.activeYearId||'') }),
            wp.apiFetch({ path: '/olama-stores/v1/entitlements?academic_year_id=' + (olamaStores.activeYearId||'') + '&grade_id=' + gradeId })
        ]).then(function(results){
            var assignments = results[0];
            var sizes = results[1];
            var entitlements = results[2];

            window.osStudentActiveItems = assignments.filter(function(a){ return a.status === 'active'; });

            // 1. Display registered sizes if available
            if (sizes && sizes.id) {
                var sizeText = '';
                if (sizes.polo_size) sizeText += 'Polo: ' + sizes.polo_size + ' | ';
                if (sizes.hoodie_size) sizeText += 'Hoodie: ' + sizes.hoodie_size + ' | ';
                if (sizes.pants_size) sizeText += 'Pants: ' + sizes.pants_size + ' | ';
                sizeText += 'General Size: ' + sizes.uniform_size;
                $('#os-wd-student-sizes-text').text(sizeText);
                $('#os-wd-student-sizes-wrap').show();
            }

            // 2. Display entitlements checklist
            var statusHtml = '';
            var activeEntitlements = [];
            
            entitlements.forEach(function(ent) {
                // Find how many items for this model have already been issued
                var issued = 0;
                window.osStudentActiveItems.forEach(function(a) {
                    if (a.specifications && parseInt(a.specifications.model_id) === parseInt(ent.custom_model_id)) {
                        issued += parseInt(a.quantity_assigned) - parseInt(a.quantity_returned);
                    }
                });

                var limit = parseInt(ent.quantity);
                var remaining = limit - issued;
                var color = remaining <= 0 ? '#10b981' : '#f59e0b'; // green if full, orange if remaining
                var text = ent.model_name + ': ' + issued + '/' + limit + ' (' + remaining + ' left)';
                
                statusHtml += '<span style="background:#fff; border:1px solid #e2e8f0; padding:4px 8px; border-radius:4px; font-weight:600; color:'+color+';">' + text + '</span>';
                
                if (remaining > 0) {
                    activeEntitlements.push({
                        model_id: parseInt(ent.custom_model_id),
                        model_name: ent.model_name,
                        remaining: remaining,
                        expected_size: (function(){
                            if (ent.model_name.toLowerCase().indexOf('polo') !== -1 && sizes.polo_size) return parseInt(sizes.polo_size);
                            if (ent.model_name.toLowerCase().indexOf('hoodie') !== -1 && sizes.hoodie_size) return parseInt(sizes.hoodie_size);
                            if (ent.model_name.toLowerCase().indexOf('pant') !== -1 && sizes.pants_size) return parseInt(sizes.pants_size);
                            return parseInt(sizes.uniform_size) || null;
                        })()
                    });
                }
            });

            if (entitlements.length) {
                $('#os-wd-entitlements-status').html(statusHtml);
                $('#os-wd-entitlements-panel').show();
            }

            // 3. Auto-populate items for remaining entitlements
            if (activeEntitlements.length > 0) {
                var $container = $('#os-withdrawal-items-container').empty();
                var promises = activeEntitlements.map(function(ent) {
                    return wp.apiFetch({
                        path: '/olama-stores/v1/items?model_id=' + ent.model_id + '&per_page=50&is_custom=1'
                    }).then(function(items) {
                        return { entitlement: ent, items: items };
                    });
                });

                Promise.all(promises).then(function(entItemsList) {
                    entItemsList.forEach(function(itemData) {
                        var ent = itemData.entitlement;
                        var items = itemData.items;
                        if (!items || !items.length) return;

                        // Create the row
                        var $row = makeItemRow();
                        // Find matching item by size
                        var selectedItem = items.find(function(it) {
                            return it.specifications && parseInt(it.specifications.size) === ent.expected_size;
                        });
                        if (!selectedItem) {
                            selectedItem = items[0]; // fallback to first item
                        }

                        // Populate select options
                        var opts = '<option value=""></option>';
                        items.forEach(function(i) {
                            var isCustomFlag = (i.specifications && i.specifications.model_id) ? 1 : 0;
                            var isBooks      = (i.specifications && i.specifications.type === 'grade_books') ? 1 : 0;
                            opts += '<option value="' + i.id + '" data-custom="' + isCustomFlag + '" data-books="' + isBooks + '">' + i.name + ' (' + i.sku + ')' + '</option>';
                        });

                        $row.find('.os-wd-item-select').html(opts).val(selectedItem.id);
                        $row.find('.os-modal-item-search').val(selectedItem.name + ' (' + selectedItem.sku + ')');
                        $row.find('.os-wd-qty-input').val(1);
                        
                        $container.append($row);
                        checkRowStock($row);
                    });
                    updateRemoveButtons();
                });
            } else {
                // If no remaining entitlements, pre-load first 5 custom items in default row
                window.osSearchItems('', $('#os-withdrawal-items-container .os-wd-item-select'), null, { per_page: 5, is_custom: 1 });
            }
        });
    });

    // Stock check helper for multi-rows + Warehouse locking logic
    $(document).on('change', '.os-wd-item-select, #os-wd-warehouse', function(){
        var $row = $(this).closest('.os-item-row');
        
        // ── Warehouse Locking Logic ──────────────────────────────────────────
        var hasCustom = false, hasBooks = false, hasGeneral = false;
        $('.os-wd-item-select').each(function(){
            var val = $(this).val();
            if (!val) return;
            var $opt = $(this).find('option:selected');
            if ($opt.data('custom') == '1') hasCustom = true;
            else if ($opt.data('books') == '1') hasBooks = true;
            else hasGeneral = true;
        });

        var $whSelect = $('#os-wd-warehouse');
        var customWh = warehouses.find(function(w){ return w.type === 'custom'; });
        var booksWh  = warehouses.find(function(w){ return w.type === 'books'; });

        if (hasCustom && customWh) {
            $whSelect.val(customWh.id).prop('disabled', true);
        } else if (hasBooks && booksWh) {
            $whSelect.val(booksWh.id).prop('disabled', true);
        } else if (customWh) {
            // Default to custom and lock as requested
            $whSelect.val(customWh.id).prop('disabled', true);
        } else {
            $whSelect.prop('disabled', false);
        }
        // ─────────────────────────────────────────────────────────────────────

        if (!$row.length) {
            // Warehouse changed, refresh all active rows
            $('.os-item-row').each(function(){ checkRowStock($(this)); });
            return;
        }
        checkRowStock($row);
    });

    function checkRowStock($row) {
        var itemId = $row.find('.os-wd-item-select').val();
        var whId   = $('#os-wd-warehouse').val();
        var $avail = $row.find('.os-row-stock-avail');
        if (!itemId || !whId) { $avail.text('').removeClass('os-stock-none'); return; }
        
        wp.apiFetch({ path: '/olama-stores/v1/stock?item_id=' + itemId + '&warehouse_id=' + whId }).then(function(stock){
            if (!stock.length) {
                $avail.text('<?php esc_html_e("Available:","olama-stores");?> 0').addClass('os-stock-none');
                return;
            }
            var row = stock[0];
            var onHand   = parseInt(row.quantity_on_hand)   || 0;
            var reserved = parseInt(row.quantity_reserved)  || 0;
            var avail    = parseInt(row.quantity_available) || (onHand - reserved);
            avail = Math.max(0, avail);
            $avail
                .text('<?php esc_html_e("Available:","olama-stores");?> ' + avail)
                .toggleClass('os-stock-none', avail <= 0);
        }).catch(function(){
            $avail.text('').removeClass('os-stock-none');
        });
    }

    // Confirm
    $('#os-btn-confirm-wd').on('click', function(){
        var items = [];
        var entitlementIssues = [];

        $('.os-item-row').each(function(){
            var $row = $(this);
            var itemId = parseInt($row.find('.os-wd-item-select').val());
            var qty = parseInt($row.find('.os-wd-qty-input').val());
            if (itemId > 0 && qty > 0) {
                items.push({ item_id: itemId, quantity: qty });
                
                var $opt = $row.find('.os-wd-item-select option:selected');
                var isCustom = $opt.data('custom') == '1';
                var itemName = $opt.text();
                entitlementIssues.push({ item_id: itemId, quantity: qty, is_custom: isCustom, name: itemName });
            }
        });

        if (!items.length) {
            alert('<?php esc_html_e("Please select at least one item.","olama-stores");?>');
            return;
        }

        var studentName = $('#os-wd-student-name').val();
        var studentUid = $('#os-wd-student-uid').val();

        // ── Entitlement Check before posting ───────────────────────────────────
        var checkEntitlementsPromise = Promise.resolve(true);
        var gradeId = $('.os-btn-wd[data-uid="'+studentUid+'"]').data('grade-id');

        if (gradeId) {
            checkEntitlementsPromise = wp.apiFetch({
                path: '/olama-stores/v1/entitlements?academic_year_id=' + (olamaStores.activeYearId||'') + '&grade_id=' + gradeId
            }).then(function(entitlements) {
                if (!entitlements || !entitlements.length) return true; // no entitlements configured

                var errors = [];
                var requestedByModel = {};
                
                // Fetch the selected items' specifications in parallel to inspect model_id
                var specPromises = entitlementIssues.map(function(issue) {
                    return wp.apiFetch({ path: '/olama-stores/v1/items/' + issue.item_id }).then(function(item) {
                        if (item && item.specifications && item.specifications.model_id) {
                            var modelId = parseInt(item.specifications.model_id);
                            requestedByModel[modelId] = (requestedByModel[modelId] || 0) + issue.quantity;
                        }
                    });
                });

                return Promise.all(specPromises).then(function() {
                    entitlements.forEach(function(ent) {
                        var modelId = parseInt(ent.custom_model_id);
                        var reqQty = requestedByModel[modelId] || 0;
                        if (reqQty === 0) return;

                        // Calculate already issued
                        var issued = 0;
                        window.osStudentActiveItems.forEach(function(a) {
                            if (a.specifications && parseInt(a.specifications.model_id) === modelId) {
                                issued += parseInt(a.quantity_assigned) - parseInt(a.quantity_returned);
                            }
                        });

                        var limit = parseInt(ent.quantity);
                        if (issued + reqQty > limit) {
                            errors.push(ent.model_name + ' (' + '<?php esc_html_e("Limit:","olama-stores");?> ' + limit + ', ' + '<?php esc_html_e("Already issued:","olama-stores");?> ' + issued + ', ' + '<?php esc_html_e("Requesting:","olama-stores");?> ' + reqQty + ')');
                        }
                    });

                    if (errors.length > 0) {
                        var errMsg = '<?php esc_html_e("⚠ Uniform Entitlement Exceeded:","olama-stores");?>\n\n' 
                            + errors.join('\n') + '\n\n'
                            + '<?php esc_html_e("Do you want to override the limit and issue anyway?","olama-stores");?>';
                        if (!confirm(errMsg)) {
                            return Promise.reject({ is_entitlement_error: true });
                        }
                    }
                    return true;
                });
            });
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php esc_html_e("Processing...","olama-stores");?>');

        checkEntitlementsPromise.then(function() {
            var payload = {
                assignee_type: 'student',
                assignee_id: studentUid,
                warehouse_id: parseInt($('#os-wd-warehouse').val()),
                items: items,
                assigned_date: new Date().toISOString().split('T')[0],
                notes: $('#os-wd-notes').val(),
                academic_year_id: olamaStores.activeYearId,
                override_entitlement: true
            };

            return wp.apiFetch({ path: '/olama-stores/v1/assignments', method: 'POST', data: payload }).then(function(){
                $('#os-withdraw-modal').hide();
                alert('<?php esc_html_e("Withdrawal successful. Use Family Receipt after the family history refreshes.","olama-stores");?>');
                $('#os-btn-family-lookup').click();
            });
        }).catch(function(e){ 
            if (e && e.is_entitlement_error) {
                return;
            }
            alert(e.message || '<?php esc_html_e( 'Error processing request.', 'olama-stores' ); ?>');
        }).finally(function(){
            $btn.prop('disabled', false).text('<?php esc_html_e("Confirm Withdrawal","olama-stores");?>');
        });
    });
})(jQuery);

</script>

<style>
/* REC-10: Duplicate item warning banner */
.os-wd-dup-warning {
    background: #fff8e1;
    border-left: 4px solid #f9a825;
    border-radius: 3px;
    color: #7a5c00;
    font-size: 0.82em;
    padding: 5px 10px;
    margin-top: 4px;
}

/* REC-07: Print receipt styles */
@media print {
    body > * { display: none !important; }
    #os-print-receipt { display: block !important; font-family: Arial, sans-serif; padding: 20px; }
}
.os-receipt-header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 16px; }
.os-receipt-footer { margin-top: 40px; display: flex; gap: 80px; }
.os-receipt-sig-line { border-top: 1px solid #333; padding-top: 6px; min-width: 200px; font-size: 0.9em; }
</style>
<?php

// REC-10: Inline the duplicate-check JS (needs PHP context for i18n strings)
?>
<script>
(function($){
    // REC-10: Store active items for the selected student is now managed globally via window.osStudentActiveItems

    // Check for duplicates whenever an item select changes in the withdrawal modal
    $(document).on('change', '#os-withdrawal-items-container .os-wd-item-select', function(){
        var $select = $(this);
        var itemId  = parseInt($select.val());
        var $row    = $select.closest('.os-item-row');

        // Remove any existing warning in this row
        $row.find('.os-wd-dup-warning').remove();

        if (!itemId || !window.osStudentActiveItems || !window.osStudentActiveItems.length) return;

        var match = window.osStudentActiveItems.find(function(a){ return parseInt(a.item_id) === itemId; });
        if (match) {
            var warnMsg = '<?php esc_html_e( '⚠ Already issued to this student', 'olama-stores' ); ?>'
                + ' (' + '<?php esc_html_e( 'qty:', 'olama-stores' ); ?> ' + match.quantity_assigned + '). '
                + '<?php esc_html_e( 'Confirm to issue again.', 'olama-stores' ); ?>';
            $row.find('.os-row-stock-avail').after(
                $('<div class="os-wd-dup-warning">').text(warnMsg)
            );
        }
    });
})(jQuery);
</script>

<script>
// REC-17: Barcode Scanner Mode
(function($){
    var scanModeActive  = false;
    var scanBuffer      = '';
    var scanTimer       = null;
    var SCAN_SPEED_MS   = 80; // scanners deliver all chars within ~80ms

    $('#os-barcode-toggle').on('click', function(){
        scanModeActive = !scanModeActive;
        if (scanModeActive) {
            $(this).css({ background:'#22c55e', color:'#fff', borderColor:'#16a34a' });
            $('#os-barcode-mode-label').text('<?php esc_html_e( 'Scan Mode: ON', 'olama-stores' ); ?>');
        } else {
            $(this).css({ background:'', color:'', borderColor:'#ddd' });
            $('#os-barcode-mode-label').text('<?php esc_html_e( 'Scan Mode: OFF', 'olama-stores' ); ?>');
        }
    });

    // Listen globally for keydown when scan mode is active
    $(document).on('keydown', function(e){
        if (!scanModeActive) return;
        // Only capture when focus is in the withdrawal modal or nowhere specific
        if ($('#os-withdraw-modal').css('display') === 'none') return;

        if (e.key === 'Enter') {
            e.preventDefault();
            var sku = scanBuffer.trim();
            scanBuffer = '';
            clearTimeout(scanTimer);
            if (!sku) return;

            // Find item by SKU via REST API
            wp.apiFetch({ path: '/olama-stores/v1/items?search=' + encodeURIComponent(sku) + '&per_page=5' }).then(function(items){
                var item = items.find(function(i){ return i.sku === sku; });
                if (!item) {
                    // Try partial match
                    item = items[0];
                }
                if (!item) {
                    alert('<?php esc_html_e( 'Barcode not found:', 'olama-stores' ); ?> ' + sku);
                    return;
                }
                // Find the first empty item select row and fill it in
                var $emptySelect = null;
                $('#os-withdrawal-items-container .os-wd-item-select').each(function(){
                    if (!$(this).val()) { $emptySelect = $(this); return false; }
                });
                // If no empty row, add one
                if (!$emptySelect) {
                    $('#os-add-wd-item-row').click();
                    $emptySelect = $('#os-withdrawal-items-container .os-wd-item-select').last();
                }
                // Inject option and select it
                if ($emptySelect.find('option[value="'+item.id+'"]').length === 0) {
                    $emptySelect.append('<option value="'+item.id+'">'+item.name+' ('+item.sku+')</option>');
                }
                $emptySelect.val(item.id).trigger('change');
            });
        } else if (e.key.length === 1) {
            // Regular character — accumulate in buffer
            scanBuffer += e.key;
            clearTimeout(scanTimer);
            // If the user types slowly (human), clear the buffer after SCAN_SPEED_MS
            scanTimer = setTimeout(function(){ scanBuffer = ''; }, SCAN_SPEED_MS * 5);
        }
    });
})(jQuery);
</script>

<script id="os-cwa-data" type="application/json">
<?php echo wp_json_encode( array(
    'nonce'        => $custom_withdrawal_nonce,
    'apiPath'      => '/olama-stores/v1/custom-withdrawal/approvals',
    'familiesApiPath' => '/olama-stores/v1/families',
    'activeYearId' => os_get_active_year_id(),
    'activeYearName' => os_get_active_year_name(),
    'i18n'         => array(
        'loading'          => __( 'Loading…', 'olama-stores' ),
        'saving'           => __( 'Saving…', 'olama-stores' ),
        'errorGeneric'     => __( 'An error occurred. Please try again.', 'olama-stores' ),
        'familyRequired'   => __( 'Enter a family ID.', 'olama-stores' ),
        'familyMembers'    => __( 'Family Members', 'olama-stores' ),
        'approved'         => __( 'Approved', 'olama-stores' ),
        'blocked'          => __( 'Blocked', 'olama-stores' ),
        'selected'         => __( '%d of %d students approved', 'olama-stores' ),
        'saved'            => __( 'Student withdrawal approvals saved.', 'olama-stores' ),
        'loadFamily'       => __( 'Search Families', 'olama-stores' ),
        'saveApprovals'    => __( 'Save Student Approvals', 'olama-stores' ),
        'studentUid'       => __( 'UID:', 'olama-stores' ),
        'matchingFamilies' => __( 'Matching Families', 'olama-stores' ),
        'familyNumber'     => __( 'Family Number', 'olama-stores' ),
        'familyName'       => __( 'Family / Parent Names', 'olama-stores' ),
        'selectFamily'     => __( 'Select Family', 'olama-stores' ),
        'noFamilies'       => __( 'No families matched this number or name.', 'olama-stores' ),
        'familyReceipt'    => __( 'Family Receipt', 'olama-stores' ),
        'receiptTitle'     => __( 'Family Custom Withdrawal Receipt', 'olama-stores' ),
        'familyId'         => __( 'Family ID', 'olama-stores' ),
        'fatherName'       => __( 'Father Name', 'olama-stores' ),
        'actionDates'      => __( 'Action Date(s)', 'olama-stores' ),
        'issuedBy'         => __( 'Issued By', 'olama-stores' ),
        'student'          => __( 'Student', 'olama-stores' ),
        'item'             => __( 'Item', 'olama-stores' ),
        'quantity'         => __( 'Quantity', 'olama-stores' ),
        'actionDate'       => __( 'Action Date', 'olama-stores' ),
        'academicYear'     => __( 'Academic Year', 'olama-stores' ),
        'printedOn'        => __( 'Printed On', 'olama-stores' ),
        'noReceiptItems'   => __( 'No active custom withdrawals are available for this family.', 'olama-stores' ),
        'receiptPopupBlocked' => __( 'The receipt window was blocked. Allow pop-ups and try again.', 'olama-stores' ),
        'unknownAdministrator' => __( 'Unknown administrator', 'olama-stores' ),
    ),
) ); ?>
</script>

<?php
wp_enqueue_script(
    'os-custom-withdrawal-approval',
    OS_URL . 'admin/assets/js/os-custom-withdrawal-approval.js',
    array( 'jquery', 'wp-api-fetch' ),
    OS_Helpers::asset_version( 'admin/assets/js/os-custom-withdrawal-approval.js' ),
    true
);

wp_enqueue_style(
    'os-custom-withdrawal-approval',
    OS_URL . 'admin/assets/css/os-custom-withdrawal-approval.css',
    array( 'olama-stores-admin' ),
    OS_Helpers::asset_version( 'admin/assets/css/os-custom-withdrawal-approval.css' )
);
