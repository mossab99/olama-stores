<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap os-wrap" id="os-reports-page">
    <h1 class="os-page-title">
        <span class="dashicons dashicons-chart-area"></span>
        <?php esc_html_e( 'Reports Center', 'olama-stores' ); ?>
    </h1>

    <style>
        #os-reports-page { --report-blue:#2563eb; --report-navy:#172554; --report-border:#dbe3ef; --report-muted:#64748b; max-width:1400px; }
        #os-reports-page .nav-tab-wrapper { display:flex; gap:6px; padding:0; border-bottom:1px solid var(--report-border); }
        #os-reports-page .nav-tab { display:flex; align-items:center; gap:7px; margin:0; padding:11px 16px; border:1px solid var(--report-border); border-bottom:0; border-radius:8px 8px 0 0; background:#f8fafc; color:#475569; font-weight:600; }
        #os-reports-page .nav-tab:hover { color:var(--report-blue); background:#fff; }
        #os-reports-page .nav-tab-active { color:var(--report-blue); background:#fff; box-shadow:inset 0 3px 0 var(--report-blue); }
        #os-reports-page .os-rpt-tab-content { padding-top:20px !important; }
        #os-reports-page .os-report-intro { margin:0 0 14px; color:var(--report-muted); font-size:14px; }
        #os-reports-page .os-report-filter-card { display:flex; align-items:flex-end; flex-wrap:wrap; gap:14px; height:auto; margin:0 0 20px; padding:18px; border:1px solid var(--report-border); border-radius:12px; background:#fff; box-shadow:0 1px 3px rgba(15,23,42,.05); }
        #os-reports-page .os-report-filter { display:flex; flex:1 1 155px; min-width:140px; flex-direction:column; gap:6px; }
        #os-reports-page .os-report-filter-wide { flex-basis:260px; }
        #os-reports-page .os-report-filter label { color:#334155; font-size:12px; font-weight:700; letter-spacing:.02em; }
        #os-reports-page .os-report-filter select, #os-reports-page .os-report-filter input { width:100%; min-height:40px; margin:0; border-color:#cbd5e1; border-radius:7px; background-color:#fff; color:#0f172a; }
        #os-reports-page .os-report-filter select:focus, #os-reports-page .os-report-filter input:focus { border-color:var(--report-blue); box-shadow:0 0 0 1px var(--report-blue); }
        #os-reports-page .os-report-actions { display:flex; align-items:center; flex:0 0 auto; gap:8px; padding-bottom:1px; }
        #os-reports-page .os-report-actions .button { display:inline-flex; align-items:center; justify-content:center; gap:6px; min-height:40px; padding:0 16px; border-radius:7px; font-weight:600; }
        #os-reports-page .os-report-actions .button-primary { border-color:var(--report-blue); background:var(--report-blue); }
        #os-reports-page .os-report-results { min-height:110px; }
        #os-reports-page .os-report-summary { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin:0 0 16px; }
        #os-reports-page .os-report-stat { position:relative; overflow:hidden; padding:16px 18px; border:1px solid var(--report-border); border-radius:11px; background:#fff; box-shadow:0 1px 3px rgba(15,23,42,.05); }
        #os-reports-page .os-report-stat:before { position:absolute; inset-block:0; inset-inline-start:0; width:4px; background:#64748b; content:""; }
        #os-reports-page .os-report-stat-primary:before { background:var(--report-blue); }
        #os-reports-page .os-report-stat-warning:before { background:#f59e0b; }
        #os-reports-page .os-report-stat-success:before { background:#16a34a; }
        #os-reports-page .os-report-stat-danger:before { background:#dc2626; }
        #os-reports-page .os-report-stat-label { display:block; margin-bottom:5px; color:var(--report-muted); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
        #os-reports-page .os-report-stat-value { display:block; color:var(--report-navy); font-size:25px; font-weight:750; line-height:1.15; }
        #os-reports-page .os-report-table { overflow:hidden; border:1px solid var(--report-border); border-radius:12px; background:#fff; box-shadow:0 1px 3px rgba(15,23,42,.05); }
        #os-reports-page .os-report-table-scroll { overflow-x:auto; }
        #os-reports-page .os-report-table table { margin:0; border:0; box-shadow:none; }
        #os-reports-page .os-report-table th { padding:12px 10px; border:0; background:var(--report-blue); color:#fff; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.025em; }
        #os-reports-page .os-report-table td { padding:11px 10px; border-bottom:1px solid #edf2f7; vertical-align:middle; }
        #os-reports-page .os-report-table tbody tr:last-child td { border-bottom:0; }
        #os-reports-page .os-report-table code { color:#475569; background:#f1f5f9; }
        #os-reports-page .os-report-state { display:flex; align-items:center; justify-content:center; min-height:110px; box-sizing:border-box; padding:20px; border:1px dashed #cbd5e1; border-radius:12px; background:#f8fafc; color:var(--report-muted); text-align:center; }
        #os-reports-page .os-report-progress { display:flex; align-items:center; gap:8px; }
        #os-reports-page .os-report-progress-track { flex:1; height:8px; overflow:hidden; border-radius:99px; background:#e2e8f0; }
        #os-reports-page .os-report-progress-value { height:100%; border-radius:99px; background:var(--report-blue); }
        @media (max-width:900px) { #os-reports-page .os-report-summary { grid-template-columns:repeat(2,minmax(130px,1fr)); } #os-reports-page .nav-tab-wrapper { overflow-x:auto; } #os-reports-page .nav-tab { flex:0 0 auto; } }
        @media (max-width:600px) { #os-reports-page .os-report-filter { flex-basis:100%; } #os-reports-page .os-report-actions { width:100%; } #os-reports-page .os-report-actions .button { flex:1; } #os-reports-page .os-report-summary { grid-template-columns:1fr 1fr; } }
    </style>

    <!-- REC-18, REC-19: Tab Navigation -->
    <div class="nav-tab-wrapper" style="margin-bottom:0;">
        <a href="#rpt-movements" class="nav-tab nav-tab-active" data-rpt-tab="movements">
            <span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Stock Movements', 'olama-stores' ); ?>
        </a>
        <a href="#rpt-provider-spend" class="nav-tab" data-rpt-tab="provider-spend">
            <span class="dashicons dashicons-store"></span> <?php esc_html_e( 'Provider Spend', 'olama-stores' ); ?>
        </a>
        <a href="#rpt-custody-aging" class="nav-tab" data-rpt-tab="custody-aging">
            <span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Custody Aging', 'olama-stores' ); ?>
        </a>
        <a href="#rpt-custom-stock" class="nav-tab" data-rpt-tab="custom-stock">
            <span class="dashicons dashicons-filter"></span> <?php esc_html_e( 'Custom Reports', 'olama-stores' ); ?>
        </a>
    </div>

    <!-- ── TAB: Stock Movements (existing) ──────────────────────────────────── -->
    <div id="rpt-movements" class="os-rpt-tab-content" style="padding-top:16px;">
        <p class="os-report-intro"><?php esc_html_e( 'Review stock activity by warehouse, item, period, and movement type.', 'olama-stores' ); ?></p>
        <div class="os-filters tablenav top os-report-filter-card">
            <div class="os-report-filter"><label for="os-rpt-warehouse"><?php esc_html_e( 'Warehouse', 'olama-stores' ); ?></label><select id="os-rpt-warehouse"><option value=""><?php esc_html_e( 'All Warehouses', 'olama-stores' ); ?></option></select></div>
            <!-- REC-11: Item filter -->
            <div class="os-input-group os-report-filter os-report-filter-wide">
                <label for="os-rpt-item-search"><?php esc_html_e( 'Stock Item', 'olama-stores' ); ?></label>
                <input type="text" id="os-rpt-item-search" class="os-modal-item-search" data-target="#os-rpt-item" placeholder="<?php esc_attr_e( 'Filter by item...', 'olama-stores' ); ?>">
                <select id="os-rpt-item"><option value=""><?php esc_html_e( 'All Items', 'olama-stores' ); ?></option></select>
            </div>
            <div class="os-report-filter"><label for="os-rpt-date-from"><?php esc_html_e( 'From Date', 'olama-stores' ); ?></label><input type="date" id="os-rpt-date-from"></div>
            <div class="os-report-filter"><label for="os-rpt-date-to"><?php esc_html_e( 'To Date', 'olama-stores' ); ?></label><input type="date" id="os-rpt-date-to"></div>
            <div class="os-report-filter"><label for="os-rpt-movement-type"><?php esc_html_e( 'Movement Type', 'olama-stores' ); ?></label><select id="os-rpt-movement-type">
                <option value=""><?php esc_html_e( 'All Movement Types', 'olama-stores' ); ?></option>
                <option value="purchase_receipt"><?php esc_html_e( 'Stock Receipt', 'olama-stores' ); ?></option>
                <option value="opening_balance"><?php esc_html_e( 'Opening Balance', 'olama-stores' ); ?></option>
                <option value="issue_employee"><?php esc_html_e( 'Issued to Employee', 'olama-stores' ); ?></option>
                <option value="issue_student"><?php esc_html_e( 'Issued to Student', 'olama-stores' ); ?></option>
                <option value="return_employee"><?php esc_html_e( 'Returned by Employee', 'olama-stores' ); ?></option>
                <option value="return_student"><?php esc_html_e( 'Returned by Student', 'olama-stores' ); ?></option>
                <option value="transfer_in"><?php esc_html_e( 'Transfer In', 'olama-stores' ); ?></option>
                <option value="transfer_out"><?php esc_html_e( 'Transfer Out', 'olama-stores' ); ?></option>
                <option value="adjustment_add"><?php esc_html_e( 'Adjustment (+)', 'olama-stores' ); ?></option>
                <option value="adjustment_sub"><?php esc_html_e( 'Adjustment (-)', 'olama-stores' ); ?></option>
                <option value="damage_loss"><?php esc_html_e( 'Damage / Loss', 'olama-stores' ); ?></option>
                <option value="inventory_count"><?php esc_html_e( 'Inventory Count', 'olama-stores' ); ?></option>
            </select></div>
            <div class="os-report-actions"><button class="button button-primary" id="os-rpt-load"><span class="dashicons dashicons-update"></span><?php esc_html_e( 'Load Report', 'olama-stores' ); ?></button>
            <button class="button" id="os-rpt-export"><span class="dashicons dashicons-download"></span><?php esc_html_e( 'Export Excel', 'olama-stores' ); ?></button></div>
        </div>
        <div id="os-movements-table-wrap" class="os-report-results">
            <p class="os-report-state"><?php esc_html_e( 'Select filters and click Load Report.', 'olama-stores' ); ?></p>
        </div>
    </div>

    <!-- ── TAB: Provider Spend (REC-18) ─────────────────────────────────────── -->
    <div id="rpt-provider-spend" class="os-rpt-tab-content" style="display:none; padding-top:16px;">
        <p class="os-report-intro"><?php esc_html_e( 'Compare purchasing activity and total spend across providers.', 'olama-stores' ); ?></p>
        <div class="os-filters tablenav top os-report-filter-card">
            <div class="os-report-filter"><label for="os-spend-date-from"><?php esc_html_e( 'From Date', 'olama-stores' ); ?></label><input type="date" id="os-spend-date-from"></div>
            <div class="os-report-filter"><label for="os-spend-date-to"><?php esc_html_e( 'To Date', 'olama-stores' ); ?></label><input type="date" id="os-spend-date-to"></div>
            <div class="os-report-actions"><button class="button button-primary" id="os-spend-load"><span class="dashicons dashicons-update"></span><?php esc_html_e( 'Load Report', 'olama-stores' ); ?></button></div>
        </div>
        <div id="os-spend-table-wrap" class="os-report-results">
            <p class="os-report-state"><?php esc_html_e( 'Select a date range and click Load Report.', 'olama-stores' ); ?></p>
        </div>
    </div>

    <!-- ── TAB: Custody Aging (REC-19) ──────────────────────────────────────── -->
    <div id="rpt-custody-aging" class="os-rpt-tab-content" style="display:none; padding-top:16px;">
        <p class="os-report-intro"><?php esc_html_e( 'Identify overdue employee custody and student withdrawal records.', 'olama-stores' ); ?></p>
        <div class="os-filters tablenav top os-report-filter-card">
            <div class="os-report-filter"><label for="os-aging-type"><?php esc_html_e( 'Custody Type', 'olama-stores' ); ?></label><select id="os-aging-type">
                <option value="employee"><?php esc_html_e( 'Employee Custody', 'olama-stores' ); ?></option>
                <option value="student"><?php esc_html_e( 'Student Withdrawals', 'olama-stores' ); ?></option>
            </select></div>
            <div class="os-report-actions"><button class="button button-primary" id="os-aging-load"><span class="dashicons dashicons-update"></span><?php esc_html_e( 'Load Report', 'olama-stores' ); ?></button>
            <button class="button" id="os-aging-export"><span class="dashicons dashicons-download"></span><?php esc_html_e( 'Export Excel', 'olama-stores' ); ?></button></div>
        </div>
        <div id="os-aging-table-wrap" class="os-report-results">
            <p class="os-report-state"><?php esc_html_e( 'Load the report to see overdue records sorted by days overdue.', 'olama-stores' ); ?></p>
        </div>
    </div>

    <div id="rpt-custom-stock" class="os-rpt-tab-content" style="display:none; padding-top:16px;">
        <p class="os-report-intro"><?php esc_html_e( 'Combine any filters below to analyze current stock availability. Results update automatically.', 'olama-stores' ); ?></p>
        <div class="os-filters tablenav top os-report-filter-card" id="os-custom-report-filters">
            <div class="os-report-filter"><label for="os-cr-warehouse"><?php esc_html_e( 'Warehouse', 'olama-stores' ); ?></label><select id="os-cr-warehouse"><option value=""><?php esc_html_e( 'All Warehouses', 'olama-stores' ); ?></option></select></div>
            <div class="os-report-filter"><label for="os-cr-category"><?php esc_html_e( 'Category', 'olama-stores' ); ?></label><select id="os-cr-category"><option value=""><?php esc_html_e( 'All Categories', 'olama-stores' ); ?></option></select></div>
            <div class="os-report-filter"><label for="os-cr-unit"><?php esc_html_e( 'Unit', 'olama-stores' ); ?></label><select id="os-cr-unit"><option value=""><?php esc_html_e( 'All Units', 'olama-stores' ); ?></option></select></div>
            <div class="os-report-filter"><label for="os-cr-provider"><?php esc_html_e( 'Provider', 'olama-stores' ); ?></label><select id="os-cr-provider"><option value=""><?php esc_html_e( 'All Providers', 'olama-stores' ); ?></option></select></div>
            <div class="os-report-filter"><label for="os-cr-model"><?php esc_html_e( 'Model', 'olama-stores' ); ?></label><select id="os-cr-model"><option value=""><?php esc_html_e( 'All Models', 'olama-stores' ); ?></option></select></div>
            <div class="os-report-filter"><label for="os-cr-fabric"><?php esc_html_e( 'Fabric', 'olama-stores' ); ?></label><select id="os-cr-fabric"><option value=""><?php esc_html_e( 'All Fabrics', 'olama-stores' ); ?></option></select></div>
            <div class="os-report-filter"><label for="os-cr-color"><?php esc_html_e( 'Color', 'olama-stores' ); ?></label><select id="os-cr-color"><option value=""><?php esc_html_e( 'All Colors', 'olama-stores' ); ?></option></select></div>
            <div class="os-report-filter"><label for="os-cr-size"><?php esc_html_e( 'Size', 'olama-stores' ); ?></label><select id="os-cr-size"><option value=""><?php esc_html_e( 'All Sizes', 'olama-stores' ); ?></option></select></div>
            <div class="os-report-actions"><button type="button" class="button" id="os-cr-reset"><span class="dashicons dashicons-image-rotate"></span><?php esc_html_e( 'Reset Filters', 'olama-stores' ); ?></button></div>
        </div>
        <div id="os-custom-stock-table-wrap" class="os-report-results"><p class="os-report-state"><?php esc_html_e( 'Loading stock availability…', 'olama-stores' ); ?></p></div>
    </div>

</div>
<script>
(function($){
    // ── Tab switching ──────────────────────────────────────────────────────────
    $('[data-rpt-tab]').on('click', function(e){
        e.preventDefault();
        $('[data-rpt-tab]').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.os-rpt-tab-content').hide();
        $('#rpt-' + $(this).data('rpt-tab')).show();
    });

    // REC-15: Human-readable movement type labels
    var OS_MOVEMENT_LABELS = {
        purchase_receipt:  '<?php esc_html_e( 'Stock Receipt', 'olama-stores' ); ?>',
        opening_balance:   '<?php esc_html_e( 'Opening Balance', 'olama-stores' ); ?>',
        adjustment_add:    '<?php esc_html_e( 'Adjustment (+)', 'olama-stores' ); ?>',
        adjustment_sub:    '<?php esc_html_e( 'Adjustment (−)', 'olama-stores' ); ?>',
        issue_employee:    '<?php esc_html_e( 'Issued to Employee', 'olama-stores' ); ?>',
        return_employee:   '<?php esc_html_e( 'Returned by Employee', 'olama-stores' ); ?>',
        issue_student:     '<?php esc_html_e( 'Issued to Student', 'olama-stores' ); ?>',
        reverse_student:   '<?php esc_html_e( 'Withdrawal Reversed', 'olama-stores' ); ?>',
        transfer_out:      '<?php esc_html_e( 'Transfer Out', 'olama-stores' ); ?>',
        transfer_in:       '<?php esc_html_e( 'Transfer In', 'olama-stores' ); ?>',
        inventory_count:   '<?php esc_html_e( 'Inventory Count', 'olama-stores' ); ?>',
        damage_loss:       '<?php esc_html_e( 'Damage / Loss', 'olama-stores' ); ?>',
    };
    function movementLabel(type) { return OS_MOVEMENT_LABELS[type] || type.replace(/_/g, ' '); }

    // ── Load warehouses ────────────────────────────────────────────────────────
    wp.apiFetch({ path:'/olama-stores/v1/warehouses' }).then(function(r){
        var opts='<option value=""><?php esc_html_e("All Warehouses","olama-stores");?></option>';
        r.forEach(function(w){ opts+='<option value="'+w.id+'">'+w.name+'</option>'; });
        $('#os-rpt-warehouse').html(opts);
        fillReportFilter('#os-cr-warehouse', r, 'name');
    });

    function esc(value) { return $('<div>').text(value == null ? '' : value).html(); }
    function reportState(message, extraClass) {
        return '<div class="os-report-state '+(extraClass||'')+'">'+message+'</div>';
    }
    function reportTable(table) {
        return '<div class="os-report-table"><div class="os-report-table-scroll">'+table+'</div></div>';
    }
    function reportStat(label, value, tone) {
        return '<div class="os-report-stat os-report-stat-'+(tone||'primary')+'"><span class="os-report-stat-label">'+label+'</span><span class="os-report-stat-value">'+value+'</span></div>';
    }
    function fillReportFilter(selector, rows, labelKey, valueKey) {
        var $select = $(selector), first = $select.find('option').first().prop('outerHTML');
        var options = first;
        valueKey = valueKey || 'id';
        rows.forEach(function(row){ options += '<option value="'+esc(row[valueKey])+'">'+esc(row[labelKey])+'</option>'; });
        $select.html(options);
    }

    Promise.all([
        wp.apiFetch({path:'/olama-stores/v1/categories'}),
        wp.apiFetch({path:'/olama-stores/v1/units'}),
        wp.apiFetch({path:'/olama-stores/v1/providers'}),
        wp.apiFetch({path:'/olama-stores/v1/custom-models'}),
        wp.apiFetch({path:'/olama-stores/v1/fabrics'}),
        wp.apiFetch({path:'/olama-stores/v1/colors'}),
        wp.apiFetch({path:'/olama-stores/v1/sizes'})
    ]).then(function(data){
        fillReportFilter('#os-cr-category', data[0], 'name');
        fillReportFilter('#os-cr-unit', data[1], 'name');
        fillReportFilter('#os-cr-provider', data[2], 'company_name');
        fillReportFilter('#os-cr-model', data[3], 'name');
        fillReportFilter('#os-cr-fabric', data[4], 'name', 'name');
        fillReportFilter('#os-cr-color', data[5], 'name', 'name');
        fillReportFilter('#os-cr-size', data[6], 'name', 'name');
    });

    // REC-12: Pre-fill date range with active academic year start → today
    var today = new Date().toISOString().split('T')[0];
    $('#os-rpt-date-to, #os-spend-date-to').val(today);
    var yearStart = (olamaStores.activeYearStart) ? olamaStores.activeYearStart : (new Date().getFullYear() + '-01-01');
    $('#os-rpt-date-from, #os-spend-date-from').val(yearStart);

    // ── TAB: Stock Movements ───────────────────────────────────────────────────
    $('#os-rpt-load').on('click', function(){
        var p='?limit=500&academic_year_id='+(olamaStores.activeYearId||'');
        if($('#os-rpt-warehouse').val())     p+='&warehouse_id='+$('#os-rpt-warehouse').val();
        if($('#os-rpt-item').val())          p+='&item_id='+$('#os-rpt-item').val();
        if($('#os-rpt-date-from').val())     p+='&date_from='+$('#os-rpt-date-from').val();
        if($('#os-rpt-date-to').val())       p+='&date_to='+$('#os-rpt-date-to').val();
        if($('#os-rpt-movement-type').val()) p+='&movement_type='+$('#os-rpt-movement-type').val();
        $('#os-movements-table-wrap').html(reportState('<?php esc_html_e("Loading report…","olama-stores");?>'));
        wp.apiFetch({ path:'/olama-stores/v1/reports/item-movements'+p }).then(function(rows){
            if(!rows.length){ $('#os-movements-table-wrap').html(reportState('<?php esc_html_e("No movements found for the selected filters.","olama-stores");?>')); return; }
            var html='<table class="wp-list-table widefat striped"><thead><tr>'
                +'<th><?php esc_html_e("Date","olama-stores");?></th>'
                +'<th><?php esc_html_e("Item","olama-stores");?></th>'
                +'<th><?php esc_html_e("Movement","olama-stores");?></th>'
                +'<th><?php esc_html_e("Qty","olama-stores");?></th>'
                +'<th><?php esc_html_e("Warehouse","olama-stores");?></th>'
                +'<th><?php esc_html_e("Performed By","olama-stores");?></th>'
                +'<th><?php esc_html_e("Notes","olama-stores");?></th>'
                +'</tr></thead><tbody>';
            rows.forEach(function(m){
                html+='<tr><td>'+m.performed_at+'</td>'
                    +'<td><strong>'+m.item_name+'</strong><br><code>'+m.sku+'</code></td>'
                    +'<td><span class="os-badge os-badge-'+m.movement_type+'">'+movementLabel(m.movement_type)+'</span></td>'
                    +'<td>'+m.quantity+'</td>'
                    +'<td>'+m.warehouse_name+'</td>'
                    +'<td>'+(m.performed_by_name||'')+'</td>'
                    +'<td>'+(m.notes||'')+'</td></tr>';
            });
            html+='</tbody></table>';
            var movementSummary = '<div class="os-report-summary">'
                + reportStat('<?php esc_html_e("Matching Movements","olama-stores");?>', rows.length.toLocaleString(), 'primary')
                + '</div>';
            $('#os-movements-table-wrap').html(movementSummary + reportTable(html));
        });
    });

    $('#os-rpt-export').on('click', function(){
        var p='?limit=2000&academic_year_id='+(olamaStores.activeYearId||'');
        if($('#os-rpt-warehouse').val())     p+='&warehouse_id='+$('#os-rpt-warehouse').val();
        if($('#os-rpt-date-from').val())     p+='&date_from='+$('#os-rpt-date-from').val();
        if($('#os-rpt-date-to').val())       p+='&date_to='+$('#os-rpt-date-to').val();
        if($('#os-rpt-movement-type').val()) p+='&movement_type='+$('#os-rpt-movement-type').val();
        window.location = olamaStores.apiRoot+'/reports/export/stock'+p+'&_wpnonce='+olamaStores.nonce;
    });

    // ── TAB: Provider Spend (REC-18) ──────────────────────────────────────────
    $('#os-spend-load').on('click', function(){
        var p = '?';
        if($('#os-spend-date-from').val()) p+='date_from='+$('#os-spend-date-from').val()+'&';
        if($('#os-spend-date-to').val())   p+='date_to='+$('#os-spend-date-to').val()+'&';
        p += 'academic_year_id='+(olamaStores.activeYearId||'');
        $('#os-spend-table-wrap').html(reportState('<?php esc_html_e("Loading report…","olama-stores");?>'));
        wp.apiFetch({ path:'/olama-stores/v1/reports/provider-spend'+p }).then(function(rows){
            if(!rows.length){
                $('#os-spend-table-wrap').html(reportState('<?php esc_html_e("No provider spend data found for this period.","olama-stores");?>'));
                return;
            }
            var totalSpend = rows.reduce(function(s,r){ return s + parseFloat(r.total_spend||0); }, 0);
            var html = '<table class="wp-list-table widefat striped"><thead><tr>'
                + '<th><?php esc_html_e("Provider","olama-stores");?></th>'
                + '<th style="text-align:right;"><?php esc_html_e("Receipts","olama-stores");?></th>'
                + '<th style="text-align:right;"><?php esc_html_e("Total Units","olama-stores");?></th>'
                + '<th style="text-align:right;"><?php esc_html_e("Total Spend","olama-stores");?></th>'
                + '<th><?php esc_html_e("Share","olama-stores");?></th>'
                + '</tr></thead><tbody>';
            rows.forEach(function(r){
                var spend = parseFloat(r.total_spend||0);
                var pct   = totalSpend > 0 ? Math.round((spend/totalSpend)*100) : 0;
                html += '<tr>'
                    + '<td><strong>'+(r.provider_name||'—')+'</strong></td>'
                    + '<td style="text-align:right;">'+(r.receipt_count||0)+'</td>'
                    + '<td style="text-align:right;">'+(r.total_units||0)+'</td>'
                    + '<td style="text-align:right; font-weight:600;">'+spend.toLocaleString(undefined,{minimumFractionDigits:0,maximumFractionDigits:0})+'</td>'
                    + '<td><div class="os-report-progress">'
                    + '<div class="os-report-progress-track">'
                    + '<div class="os-report-progress-value" style="width:'+pct+'%;"></div>'
                    + '</div><span style="min-width:32px;font-size:.8em;">'+pct+'%</span></div></td>'
                    + '</tr>';
            });
            html += '</tbody></table>';
            var spendSummary = '<div class="os-report-summary">'
                + reportStat('<?php esc_html_e("Providers","olama-stores");?>', rows.length.toLocaleString(), 'primary')
                + reportStat('<?php esc_html_e("Total Spend","olama-stores");?>', totalSpend.toLocaleString(undefined,{minimumFractionDigits:0,maximumFractionDigits:0}), 'success')
                + '</div>';
            $('#os-spend-table-wrap').html(spendSummary + reportTable(html));
        }).catch(function(){
            $('#os-spend-table-wrap').html(reportState('<?php esc_html_e("Error loading provider spend data.","olama-stores");?>', 'os-error'));
        });
    });

    // ── TAB: Custody Aging (REC-19) ───────────────────────────────────────────
    function loadCustodyAging() {
        var type = $('#os-aging-type').val();
        var p = '?assignee_type='+type+'&academic_year_id='+(olamaStores.activeYearId||'');
        $('#os-aging-table-wrap').html(reportState('<?php esc_html_e("Loading report…","olama-stores");?>'));
        wp.apiFetch({ path:'/olama-stores/v1/reports/custody-aging'+p }).then(function(rows){
            if(!rows.length){
                $('#os-aging-table-wrap').html(reportState('<?php esc_html_e("No overdue records found. All custody is on time!","olama-stores");?>'));
                return;
            }
            var html = '<table class="wp-list-table widefat striped"><thead><tr>'
                + '<th><?php esc_html_e("Employee / Student","olama-stores");?></th>'
                + '<th><?php esc_html_e("Item","olama-stores");?></th>'
                + '<th style="text-align:center;"><?php esc_html_e("Qty","olama-stores");?></th>'
                + '<th><?php esc_html_e("Assigned","olama-stores");?></th>'
                + '<th><?php esc_html_e("Due Date","olama-stores");?></th>'
                + '<th style="text-align:center;"><?php esc_html_e("Days Overdue","olama-stores");?></th>'
                + '</tr></thead><tbody>';
            rows.forEach(function(r){
                var days = parseInt(r.days_overdue) || 0;
                var color = days > 60 ? '#d63638' : (days > 30 ? '#d97706' : '#ca8a04');
                html += '<tr>'
                    + '<td><strong>'+(r.assignee_name||r.assignee_id)+'</strong></td>'
                    + '<td>'+(r.item_name||'')+'<br><code style="font-size:.75em;">'+(r.sku||'')+'</code></td>'
                    + '<td style="text-align:center;">'+(r.quantity_assigned||0)+'</td>'
                    + '<td>'+(r.assigned_date||'')+'</td>'
                    + '<td>'+(r.expected_return_date||'')+'</td>'
                    + '<td style="text-align:center; font-weight:700; color:'+color+';">'+days+'</td>'
                    + '</tr>';
            });
            html += '</tbody></table>';
            var agingSummary = '<div class="os-report-summary">'
                + reportStat('<?php esc_html_e("Overdue Records","olama-stores");?>', rows.length.toLocaleString(), 'danger')
                + '</div>';
            $('#os-aging-table-wrap').html(agingSummary + reportTable(html));
        }).catch(function(){
            $('#os-aging-table-wrap').html(reportState('<?php esc_html_e("Error loading custody aging data.","olama-stores");?>', 'os-error'));
        });
    }

    $('#os-aging-load').on('click', loadCustodyAging);

    $('#os-aging-export').on('click', function(){
        var type = $('#os-aging-type').val();
        window.location = olamaStores.apiRoot + '/reports/export/assignments?status=active&assignee_type=' + type
            + '&academic_year_id=' + (olamaStores.activeYearId||'') + '&_wpnonce=' + olamaStores.nonce;
    });

    // Dynamic custom stock report.
    var customReportTimer;
    function loadCustomStock() {
        var filterMap = {
            warehouse_id: '#os-cr-warehouse', category_id: '#os-cr-category',
            unit_id: '#os-cr-unit', provider_id: '#os-cr-provider',
            model_id: '#os-cr-model', fabric: '#os-cr-fabric',
            color: '#os-cr-color', size: '#os-cr-size'
        };
        var query = [];
        $.each(filterMap, function(key, selector){
            var value = $(selector).val();
            if (value) query.push(encodeURIComponent(key)+'='+encodeURIComponent(value));
        });
        $('#os-custom-stock-table-wrap').html(reportState('<?php esc_html_e("Loading stock availability…","olama-stores");?>'));
        wp.apiFetch({path:'/olama-stores/v1/reports/custom-stock'+(query.length ? '?'+query.join('&') : '')}).then(function(rows){
            if (!rows.length) {
                $('#os-custom-stock-table-wrap').html(reportState('<?php esc_html_e("No stock items match the selected filters.","olama-stores");?>'));
                return;
            }
            var totalOnHand = 0, totalReserved = 0, totalAvailable = 0;
            rows.forEach(function(row){
                totalOnHand += parseInt(row.quantity_on_hand, 10) || 0;
                totalReserved += parseInt(row.quantity_reserved, 10) || 0;
                totalAvailable += parseInt(row.quantity_available, 10) || 0;
            });
            var summary = '<div class="os-report-summary">'
                + reportStat('<?php esc_html_e("Matching Rows","olama-stores");?>', rows.length.toLocaleString(), 'primary')
                + reportStat('<?php esc_html_e("On Hand","olama-stores");?>', totalOnHand.toLocaleString(), 'primary')
                + reportStat('<?php esc_html_e("Reserved","olama-stores");?>', totalReserved.toLocaleString(), 'warning')
                + reportStat('<?php esc_html_e("Available","olama-stores");?>', totalAvailable.toLocaleString(), 'success')
                + '</div>';
            var html = '<table class="wp-list-table widefat striped"><thead><tr>'
                + '<th><?php esc_html_e("Item","olama-stores");?></th><th><?php esc_html_e("Warehouse","olama-stores");?></th>'
                + '<th><?php esc_html_e("Category","olama-stores");?></th><th><?php esc_html_e("Unit","olama-stores");?></th>'
                + '<th><?php esc_html_e("Provider","olama-stores");?></th><th><?php esc_html_e("Model","olama-stores");?></th>'
                + '<th><?php esc_html_e("Fabric","olama-stores");?></th><th><?php esc_html_e("Color","olama-stores");?></th>'
                + '<th><?php esc_html_e("Size","olama-stores");?></th><th style="text-align:right;"><?php esc_html_e("On Hand","olama-stores");?></th>'
                + '<th style="text-align:right;"><?php esc_html_e("Reserved","olama-stores");?></th><th style="text-align:right;"><?php esc_html_e("Available","olama-stores");?></th>'
                + '</tr></thead><tbody>';
            rows.forEach(function(row){
                html += '<tr><td><strong>'+esc(row.item_name)+'</strong><br><code>'+esc(row.sku)+'</code></td>'
                    + '<td>'+esc(row.warehouse_name)+'</td><td>'+esc(row.category_name||'—')+'</td>'
                    + '<td>'+esc(row.unit_symbol||row.unit_name||'—')+'</td><td>'+esc(row.provider_name||'—')+'</td>'
                    + '<td>'+esc(row.model_name||'—')+'</td><td>'+esc(row.fabric||'—')+'</td>'
                    + '<td>'+esc(row.color||'—')+'</td><td>'+esc(row.size||'—')+'</td>'
                    + '<td style="text-align:right;">'+esc(row.quantity_on_hand)+'</td><td style="text-align:right;">'+esc(row.quantity_reserved)+'</td>'
                    + '<td style="text-align:right;font-weight:700;">'+esc(row.quantity_available)+'</td></tr>';
            });
            $('#os-custom-stock-table-wrap').html(summary + reportTable(html+'</tbody></table>'));
        }).catch(function(error){
            $('#os-custom-stock-table-wrap').html(reportState(esc(error.message||'<?php esc_html_e("Error loading the custom report.","olama-stores");?>'), 'os-error'));
        });
    }

    $('#os-custom-report-filters').on('change', 'select', function(){
        clearTimeout(customReportTimer);
        customReportTimer = setTimeout(loadCustomStock, 150);
    });
    $('#os-cr-reset').on('click', function(){
        $('#os-custom-report-filters select').val('');
        loadCustomStock();
    });
    $('[data-rpt-tab="custom-stock"]').one('click', loadCustomStock);

})(jQuery);
</script>
