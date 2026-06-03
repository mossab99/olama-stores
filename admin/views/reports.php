<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap os-wrap" id="os-reports-page">
    <h1 class="os-page-title">
        <span class="dashicons dashicons-chart-area"></span>
        <?php esc_html_e( 'Reports Center', 'olama-stores' ); ?>
    </h1>

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
    </div>

    <!-- ── TAB: Stock Movements (existing) ──────────────────────────────────── -->
    <div id="rpt-movements" class="os-rpt-tab-content" style="padding-top:16px;">
        <div class="os-filters tablenav top">
            <select id="os-rpt-warehouse"><option value=""><?php esc_html_e( 'All Warehouses', 'olama-stores' ); ?></option></select>
            <!-- REC-11: Item filter -->
            <div class="os-input-group" style="display:inline-flex; min-width:220px; vertical-align:middle;">
                <input type="text" id="os-rpt-item-search" class="os-modal-item-search" data-target="#os-rpt-item" placeholder="<?php esc_attr_e( 'Filter by item...', 'olama-stores' ); ?>">
                <select id="os-rpt-item"><option value=""><?php esc_html_e( 'All Items', 'olama-stores' ); ?></option></select>
            </div>
            <input type="date" id="os-rpt-date-from" placeholder="<?php esc_attr_e( 'From', 'olama-stores' ); ?>">
            <input type="date" id="os-rpt-date-to"   placeholder="<?php esc_attr_e( 'To', 'olama-stores' ); ?>">
            <select id="os-rpt-movement-type">
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
            </select>
            <button class="button button-primary" id="os-rpt-load"><?php esc_html_e( 'Load', 'olama-stores' ); ?></button>
            <button class="button" id="os-rpt-export"><?php esc_html_e( 'Export Excel', 'olama-stores' ); ?></button>
        </div>
        <div id="os-movements-table-wrap">
            <p class="os-loading"><?php esc_html_e( 'Select filters and click Load.', 'olama-stores' ); ?></p>
        </div>
    </div>

    <!-- ── TAB: Provider Spend (REC-18) ─────────────────────────────────────── -->
    <div id="rpt-provider-spend" class="os-rpt-tab-content" style="display:none; padding-top:16px;">
        <div class="os-filters tablenav top">
            <input type="date" id="os-spend-date-from" placeholder="<?php esc_attr_e( 'From', 'olama-stores' ); ?>">
            <input type="date" id="os-spend-date-to" placeholder="<?php esc_attr_e( 'To', 'olama-stores' ); ?>">
            <button class="button button-primary" id="os-spend-load"><?php esc_html_e( 'Load', 'olama-stores' ); ?></button>
        </div>
        <div id="os-spend-table-wrap">
            <p><?php esc_html_e( 'Select a date range and click Load to see provider spending.', 'olama-stores' ); ?></p>
        </div>
    </div>

    <!-- ── TAB: Custody Aging (REC-19) ──────────────────────────────────────── -->
    <div id="rpt-custody-aging" class="os-rpt-tab-content" style="display:none; padding-top:16px;">
        <div class="os-filters tablenav top">
            <select id="os-aging-type">
                <option value="employee"><?php esc_html_e( 'Employee Custody', 'olama-stores' ); ?></option>
                <option value="student"><?php esc_html_e( 'Student Withdrawals', 'olama-stores' ); ?></option>
            </select>
            <button class="button button-primary" id="os-aging-load"><?php esc_html_e( 'Load', 'olama-stores' ); ?></button>
            <button class="button" id="os-aging-export"><?php esc_html_e( 'Export Excel', 'olama-stores' ); ?></button>
        </div>
        <div id="os-aging-table-wrap">
            <p><?php esc_html_e( 'Load to see all overdue custodians sorted by days overdue.', 'olama-stores' ); ?></p>
        </div>
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
        $('#os-movements-table-wrap').html('<span class="os-loading"><?php esc_html_e("Loading…","olama-stores");?></span>');
        wp.apiFetch({ path:'/olama-stores/v1/reports/item-movements'+p }).then(function(rows){
            if(!rows.length){ $('#os-movements-table-wrap').html('<p><?php esc_html_e("No movements found.","olama-stores");?></p>'); return; }
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
            $('#os-movements-table-wrap').html(html);
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
        $('#os-spend-table-wrap').html('<span class="os-loading"><?php esc_html_e("Loading…","olama-stores");?></span>');
        wp.apiFetch({ path:'/olama-stores/v1/reports/provider-spend'+p }).then(function(rows){
            if(!rows.length){
                $('#os-spend-table-wrap').html('<p><?php esc_html_e("No provider spend data found for this period.","olama-stores");?></p>');
                return;
            }
            var totalSpend = rows.reduce(function(s,r){ return s + parseFloat(r.total_spend||0); }, 0);
            var html = '<p><strong><?php esc_html_e("Total spend this period:","olama-stores");?></strong> '
                + totalSpend.toLocaleString(undefined,{minimumFractionDigits:0,maximumFractionDigits:0}) + '</p>'
                + '<table class="wp-list-table widefat striped"><thead><tr>'
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
                    + '<td><div style="display:flex;align-items:center;gap:8px;">'
                    + '<div style="flex:1;height:8px;border-radius:4px;background:#e2e8f0;overflow:hidden;">'
                    + '<div style="height:100%;width:'+pct+'%;background:var(--os-primary,#0073aa);border-radius:4px;transition:width .4s;"></div>'
                    + '</div><span style="min-width:32px;font-size:.8em;">'+pct+'%</span></div></td>'
                    + '</tr>';
            });
            html += '</tbody></table>';
            $('#os-spend-table-wrap').html(html);
        }).catch(function(){
            $('#os-spend-table-wrap').html('<p class="os-error"><?php esc_html_e("Error loading provider spend data.","olama-stores");?></p>');
        });
    });

    // ── TAB: Custody Aging (REC-19) ───────────────────────────────────────────
    function loadCustodyAging() {
        var type = $('#os-aging-type').val();
        var p = '?assignee_type='+type+'&academic_year_id='+(olamaStores.activeYearId||'');
        $('#os-aging-table-wrap').html('<span class="os-loading"><?php esc_html_e("Loading…","olama-stores");?></span>');
        wp.apiFetch({ path:'/olama-stores/v1/reports/custody-aging'+p }).then(function(rows){
            if(!rows.length){
                $('#os-aging-table-wrap').html('<p><?php esc_html_e("No overdue records found. All custody is on time!","olama-stores");?></p>');
                return;
            }
            var html = '<p><strong>' + rows.length + ' <?php esc_html_e("overdue records","olama-stores");?></strong></p>'
                + '<table class="wp-list-table widefat striped"><thead><tr>'
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
            $('#os-aging-table-wrap').html(html);
        }).catch(function(){
            $('#os-aging-table-wrap').html('<p class="os-error"><?php esc_html_e("Error loading custody aging data.","olama-stores");?></p>');
        });
    }

    $('#os-aging-load').on('click', loadCustodyAging);

    $('#os-aging-export').on('click', function(){
        var type = $('#os-aging-type').val();
        window.location = olamaStores.apiRoot + '/reports/export/assignments?status=active&assignee_type=' + type
            + '&academic_year_id=' + (olamaStores.activeYearId||'') + '&_wpnonce=' + olamaStores.nonce;
    });

})(jQuery);
</script>
</style>
