<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap os-wrap" id="os-reports-page">
    <h1 class="os-page-title">
        <span class="dashicons dashicons-chart-area"></span>
        <?php esc_html_e( 'Reports Center', 'olama-stores' ); ?>
    </h1>

    <div class="os-filters tablenav top">
        <select id="os-rpt-warehouse"><option value=""><?php esc_html_e( 'All Warehouses', 'olama-stores' ); ?></option></select>
        <input type="date" id="os-rpt-date-from" placeholder="<?php esc_attr_e( 'From', 'olama-stores' ); ?>">
        <input type="date" id="os-rpt-date-to"   placeholder="<?php esc_attr_e( 'To', 'olama-stores' ); ?>">
        <select id="os-rpt-movement-type">
            <option value=""><?php esc_html_e( 'All Movement Types', 'olama-stores' ); ?></option>
            <option value="purchase_receipt"><?php esc_html_e( 'Purchase Receipt', 'olama-stores' ); ?></option>
            <option value="issue_employee"><?php esc_html_e( 'Issue to Employee', 'olama-stores' ); ?></option>
            <option value="issue_student"><?php esc_html_e( 'Issue to Student', 'olama-stores' ); ?></option>
            <option value="return_employee"><?php esc_html_e( 'Return from Employee', 'olama-stores' ); ?></option>
            <option value="return_student"><?php esc_html_e( 'Return from Student', 'olama-stores' ); ?></option>
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
<script>
(function($){
    wp.apiFetch({ path:'/olama-stores/v1/warehouses' }).then(function(r){
        var opts='<option value=""><?php esc_html_e("All Warehouses","olama-stores");?></option>';
        r.forEach(function(w){ opts+='<option value="'+w.id+'">'+w.name+'</option>'; });
        $('#os-rpt-warehouse').html(opts);
    });

    $('#os-rpt-load').on('click', function(){
        var p='?limit=500&academic_year_id='+(olamaStores.activeYearId||'');
        if($('#os-rpt-warehouse').val())       p+='&warehouse_id='+$('#os-rpt-warehouse').val();
        if($('#os-rpt-date-from').val())       p+='&date_from='+$('#os-rpt-date-from').val();
        if($('#os-rpt-date-to').val())         p+='&date_to='+$('#os-rpt-date-to').val();
        if($('#os-rpt-movement-type').val())   p+='&movement_type='+$('#os-rpt-movement-type').val();
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
                    +'<td><span class="os-badge os-badge-'+m.movement_type+'">'+m.movement_type.replace(/_/g,' ')+'</span></td>'
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
})(jQuery);
</script>
