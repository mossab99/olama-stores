<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap os-wrap" id="os-stock-page">
    <h1 class="os-page-title">
        <span class="dashicons dashicons-chart-bar"></span>
        <?php esc_html_e( 'Stock Management', 'olama-stores' ); ?>
        <?php if ( OS_Roles::can( 'os_receive_stock' ) ): ?>
            <a href="#" id="os-btn-receive-stock" class="page-title-action"><?php esc_html_e( 'Record Receipt', 'olama-stores' ); ?></a>
        <?php endif; ?>
        <?php if ( OS_Roles::can( 'os_adjust_stock' ) ): ?>
            <a href="#" id="os-btn-adjust-stock" class="page-title-action"><?php esc_html_e( 'Manual Adjustment', 'olama-stores' ); ?></a>
        <?php endif; ?>
    </h1>

    <div class="os-filters tablenav top">
        <select id="os-filter-warehouse">
            <option value=""><?php esc_html_e( 'All Warehouses', 'olama-stores' ); ?></option>
        </select>
        <select id="os-filter-stock-category">
            <option value=""><?php esc_html_e( 'All Categories', 'olama-stores' ); ?></option>
        </select>
        <label>
            <input type="checkbox" id="os-filter-low-stock">
            <?php esc_html_e( 'Low stock only', 'olama-stores' ); ?>
        </label>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=olama-stores-reports' ) ); ?>" class="button">
            <?php esc_html_e( 'Movements History', 'olama-stores' ); ?>
        </a>
        <button class="button" id="os-btn-export-stock"><?php esc_html_e( 'Export Excel', 'olama-stores' ); ?></button>
    </div>

    <div id="os-stock-table-wrap">
        <span class="os-loading"><?php esc_html_e( 'Loading stock…', 'olama-stores' ); ?></span>
    </div>

    <!-- Stock Receipt Modal -->
    <div id="os-receipt-modal" class="os-modal" style="display:none;">
        <div class="os-modal-content">
            <h2><?php esc_html_e( 'Record Stock Receipt', 'olama-stores' ); ?></h2>
            <div class="os-form-row">
                <label><?php esc_html_e( 'Type', 'olama-stores' ); ?></label>
                <select id="os-receipt-type">
                    <option value="purchase_receipt"><?php esc_html_e( 'Purchase Receipt', 'olama-stores' ); ?></option>
                    <option value="opening_balance"><?php esc_html_e( 'Opening Balance', 'olama-stores' ); ?></option>
                </select>
            </div>
            <div class="os-form-row">
                <label><?php esc_html_e( 'Item', 'olama-stores' ); ?></label>
                <div class="os-input-group">
                    <input type="text" class="os-modal-item-search" data-target="#os-receipt-item" placeholder="<?php esc_attr_e( 'Search items...', 'olama-stores' ); ?>">
                    <select id="os-receipt-item"></select>
                </div>
            </div>
            <div class="os-form-row">
                <label><?php esc_html_e( 'Warehouse', 'olama-stores' ); ?></label>
                <select id="os-receipt-warehouse"></select>
            </div>
            <div class="os-form-row">
                <label><?php esc_html_e( 'Quantity', 'olama-stores' ); ?></label>
                <input type="number" id="os-receipt-qty" min="1" value="1">
            </div>
            <div class="os-form-row">
                <label><?php esc_html_e( 'Notes', 'olama-stores' ); ?></label>
                <textarea id="os-receipt-notes"></textarea>
            </div>
            <div class="os-form-actions">
                <button type="button" class="button button-primary" id="os-receipt-submit"><?php esc_html_e( 'Record Receipt', 'olama-stores' ); ?></button>
                <button type="button" class="button os-modal-close"><?php esc_html_e( 'Cancel', 'olama-stores' ); ?></button>
            </div>
        </div>
    </div>

    <!-- Manual Adjustment Modal -->
    <div id="os-adjust-modal" class="os-modal" style="display:none;">
        <div class="os-modal-content">
            <h2><?php esc_html_e( 'Manual Stock Adjustment', 'olama-stores' ); ?></h2>
            <div class="os-form-row">
                <label><?php esc_html_e( 'Item', 'olama-stores' ); ?></label>
                <div class="os-input-group">
                    <input type="text" class="os-modal-item-search" data-target="#os-adjust-item" placeholder="<?php esc_attr_e( 'Search items...', 'olama-stores' ); ?>">
                    <select id="os-adjust-item"></select>
                </div>
            </div>
            <div class="os-form-row">
                <label><?php esc_html_e( 'Warehouse', 'olama-stores' ); ?></label>
                <select id="os-adjust-warehouse"></select>
            </div>
            <div class="os-form-row">
                <label><?php esc_html_e( 'Quantity (+/-)', 'olama-stores' ); ?></label>
                <input type="number" id="os-adjust-qty" value="0">
                <small><?php esc_html_e( 'Use negative to reduce stock', 'olama-stores' ); ?></small>
            </div>
            <div class="os-form-row">
                <label><?php esc_html_e( 'Reason (required)', 'olama-stores' ); ?></label>
                <textarea id="os-adjust-notes" required></textarea>
            </div>
            <div class="os-form-actions">
                <button type="button" class="button button-primary" id="os-adjust-submit"><?php esc_html_e( 'Post Adjustment', 'olama-stores' ); ?></button>
                <button type="button" class="button os-modal-close"><?php esc_html_e( 'Cancel', 'olama-stores' ); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
(function($){
    function loadStock(){
        var wh   = $('#os-filter-warehouse').val();
        var cat  = $('#os-filter-stock-category').val();
        var low  = $('#os-filter-low-stock').is(':checked') ? '&low_stock_only=1' : '';
        var p    = '?' + (wh?'warehouse_id='+wh+'&':'') + (cat?'category_id='+cat+'&':'') + low;
        $('#os-stock-table-wrap').html('<span class="os-loading"><?php esc_html_e("Loading…","olama-stores");?></span>');
        wp.apiFetch({ path:'/olama-stores/v1/stock'+p }).then(function(rows){
            if(!rows.length){ $('#os-stock-table-wrap').html('<p><?php esc_html_e("No stock found.","olama-stores");?></p>'); return; }
            var html='<table class="wp-list-table widefat striped"><thead><tr>'
                +'<th><?php esc_html_e("Item","olama-stores");?></th><th><?php esc_html_e("SKU","olama-stores");?></th>'
                +'<th><?php esc_html_e("Warehouse","olama-stores");?></th><th><?php esc_html_e("On Hand","olama-stores");?></th>'
                +'<th><?php esc_html_e("Reserved","olama-stores");?></th><th><?php esc_html_e("Available","olama-stores");?></th>'
                +'<th><?php esc_html_e("Min Level","olama-stores");?></th></tr></thead><tbody>';
            rows.forEach(function(r){
                // Correction #4: quantity_available comes from server PHP calculation (not GENERATED column)
                var avail = parseInt(r.quantity_available);
                var min   = parseInt(r.min_stock_level);
                var cls   = avail <= 0 ? 'os-row-danger' : (avail <= min ? 'os-row-warning' : '');
                html+='<tr class="'+cls+'"><td><strong>'+r.name+'</strong><br><small dir="rtl">'+r.name_ar+'</small></td>'
                    +'<td><code>'+r.sku+'</code></td><td>'+r.warehouse_name+'</td>'
                    +'<td>'+r.quantity_on_hand+'</td><td>'+r.quantity_reserved+'</td>'
                    +'<td><strong>'+(avail<0?'<span class="os-badge os-badge-lost">'+avail+'</span>':avail)+'</strong></td>'
                    +'<td>'+min+'</td></tr>';
            });
            html+='</tbody></table>';
            $('#os-stock-table-wrap').html(html);
        });
    }

    // Load initial data
    Promise.all([
        wp.apiFetch({ path:'/olama-stores/v1/warehouses' }),
        wp.apiFetch({ path:'/olama-stores/v1/categories' }),
    ]).then(function(r){
        var whOpts='<option value=""><?php esc_html_e("All Warehouses","olama-stores");?></option>';
        r[0].forEach(function(w){ whOpts+='<option value="'+w.id+'">'+w.name+'</option>'; });
        $('#os-filter-warehouse, #os-receipt-warehouse, #os-adjust-warehouse').html(whOpts);

        var catOpts='<option value=""><?php esc_html_e("All Categories","olama-stores");?></option>';
        r[1].forEach(function(c){ catOpts+='<option value="'+c.id+'">'+c.name+'</option>'; });
        $('#os-filter-stock-category').html(catOpts);

        loadStock();
    });


    $('#os-filter-warehouse, #os-filter-stock-category').on('change', loadStock);
    $('#os-filter-low-stock').on('change', loadStock);

    // Receipt modal
    $('#os-btn-receive-stock').on('click', function(e){
        e.preventDefault();
        window.osSearchItems('', $('#os-receipt-item'));
        $('#os-receipt-modal').find('.os-modal-item-search').val('');
        $('#os-receipt-modal').show();
    });
    $('#os-receipt-submit').on('click', function(){
        wp.apiFetch({ path:'/olama-stores/v1/stock/receive', method:'POST', data:{
            movement_type: $('#os-receipt-type').val(),
            item_id: parseInt($('#os-receipt-item').val()),
            warehouse_id: parseInt($('#os-receipt-warehouse').val()),
            quantity: parseInt($('#os-receipt-qty').val()),
            notes: $('#os-receipt-notes').val(),
            academic_year_id: olamaStores.activeYearId  // INT — Correction #1
        }}).then(function(){ $('#os-receipt-modal').hide(); loadStock(); })
           .catch(function(e){ alert(e.message); });
    });

    // Adjust modal
    $('#os-btn-adjust-stock').on('click', function(e){
        e.preventDefault();
        window.osSearchItems('', $('#os-adjust-item'));
        $('#os-adjust-modal').find('.os-modal-item-search').val('');
        $('#os-adjust-modal').show();
    });
    $('#os-adjust-submit').on('click', function(){
        wp.apiFetch({ path:'/olama-stores/v1/stock/adjust', method:'POST', data:{
            item_id: parseInt($('#os-adjust-item').val()),
            warehouse_id: parseInt($('#os-adjust-warehouse').val()),
            quantity: parseInt($('#os-adjust-qty').val()),
            notes: $('#os-adjust-notes').val(),
            academic_year_id: olamaStores.activeYearId
        }}).then(function(){ $('#os-adjust-modal').hide(); loadStock(); })
           .catch(function(e){ alert(e.message); });
    });

    // Export
    $('#os-btn-export-stock').on('click', function(){
        window.location = olamaStores.apiRoot + '/reports/export/stock?_wpnonce=' + olamaStores.nonce;
    });
})(jQuery);
</script>

<style>
.os-row-danger td { background: #fff0f0 !important; }
.os-row-warning td { background: #fffbeb !important; }
</style>
