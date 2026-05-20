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
        <?php if ( current_user_can( 'manage_options' ) || OS_Roles::can( 'os_manage_settings' ) ): ?>
            <a href="#" id="os-btn-delete-transactions" class="page-title-action os-btn-danger"><?php esc_html_e( 'Delete Transactions', 'olama-stores' ); ?></a>
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
    <!-- Delete Transactions Modal (Testing Only) -->
    <div id="os-delete-tx-modal" class="os-modal" style="display:none;">
        <div class="os-modal-content" style="max-width: 500px;">
            <h2><span class="dashicons dashicons-trash" style="color: #d63638; font-size: 24px; vertical-align: middle; margin-right: 8px;"></span><?php esc_html_e( 'Delete Transactions (Testing)', 'olama-stores' ); ?></h2>
            <div style="background-color: #fcf1f1; border-left: 4px solid #d63638; padding: 12px; margin-bottom: 20px; color: #d63638;">
                <strong><?php esc_html_e( 'WARNING:', 'olama-stores' ); ?></strong> <?php esc_html_e( 'This will permanently delete transactions and adjust stock quantities based on reversed transactions. This tool is for testing only.', 'olama-stores' ); ?>
            </div>

            <div class="os-form-row">
                <label><?php esc_html_e( 'Start Date', 'olama-stores' ); ?></label>
                <input type="date" id="os-delete-tx-start-date" style="width: 100%;">
            </div>

            <div class="os-form-row">
                <label><?php esc_html_e( 'End Date', 'olama-stores' ); ?></label>
                <input type="date" id="os-delete-tx-end-date" style="width: 100%;">
            </div>

            <div class="os-form-row">
                <label><?php esc_html_e( 'Provider', 'olama-stores' ); ?></label>
                <select id="os-delete-tx-provider" style="width: 100%;">
                    <option value=""><?php esc_html_e( 'All Providers', 'olama-stores' ); ?></option>
                </select>
            </div>

            <div class="os-form-row">
                <label><?php esc_html_e( 'Item', 'olama-stores' ); ?></label>
                <div class="os-input-group" style="width: 100%;">
                    <input type="text" class="os-modal-item-search" data-target="#os-delete-tx-item" placeholder="<?php esc_attr_e( 'Search items...', 'olama-stores' ); ?>">
                    <select id="os-delete-tx-item" style="width: 100%;"></select>
                </div>
            </div>

            <div class="os-form-actions" style="margin-top: 24px;">
                <button type="button" class="button os-btn-danger" id="os-delete-tx-submit"><?php esc_html_e( 'Delete Matching Transactions', 'olama-stores' ); ?></button>
                <button type="button" class="button os-modal-close"><?php esc_html_e( 'Cancel', 'olama-stores' ); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
(function($){
    var currentPaged = 1;
    var currentPerPage = 20;
    var currentOrderby = 'name';
    var currentOrder = 'ASC';

    function loadStock(){
        var wh   = $('#os-filter-warehouse').val();
        var cat  = $('#os-filter-stock-category').val();
        var low  = $('#os-filter-low-stock').is(':checked') ? '&low_stock_only=1' : '';
        var p    = '?' + (wh?'warehouse_id='+wh+'&':'') 
                     + (cat?'category_id='+cat+'&':'') 
                     + 'paged=' + currentPaged 
                     + '&per_page=' + currentPerPage 
                     + '&orderby=' + currentOrderby 
                     + '&order=' + currentOrder 
                     + low;

        $('#os-stock-table-wrap').html('<span class="os-loading"><?php esc_html_e("Loading…","olama-stores");?></span>');
        wp.apiFetch({ path:'/olama-stores/v1/stock'+p }).then(function(res){
            var rows = res.items || [];
            var total = res.total || 0;
            var pages = res.pages || 0;

            if(!rows.length){ $('#os-stock-table-wrap').html('<p><?php esc_html_e("No stock found.","olama-stores");?></p>'); return; }

            var getHeaderClass = function(col) {
                var cls = 'sortable';
                if (currentOrderby === col) {
                    cls += ' sorted ' + currentOrder.toLowerCase();
                }
                return cls;
            };

            var html='<table class="wp-list-table widefat striped"><thead><tr>'
                +'<th class="'+getHeaderClass('name')+'" data-orderby="name"><?php esc_html_e("Item","olama-stores");?></th>'
                +'<th class="'+getHeaderClass('sku')+'" data-orderby="sku"><?php esc_html_e("SKU","olama-stores");?></th>'
                +'<th class="'+getHeaderClass('warehouse_name')+'" data-orderby="warehouse_name"><?php esc_html_e("Warehouse","olama-stores");?></th>'
                +'<th class="'+getHeaderClass('quantity_on_hand')+'" data-orderby="quantity_on_hand"><?php esc_html_e("On Hand","olama-stores");?></th>'
                +'<th class="'+getHeaderClass('quantity_reserved')+'" data-orderby="quantity_reserved"><?php esc_html_e("Reserved","olama-stores");?></th>'
                +'<th class="'+getHeaderClass('quantity_available')+'" data-orderby="quantity_available"><?php esc_html_e("Available","olama-stores");?></th>'
                +'<th class="'+getHeaderClass('min_stock_level')+'" data-orderby="min_stock_level"><?php esc_html_e("Min Level","olama-stores");?></th></tr></thead><tbody>';

            rows.forEach(function(r){
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

            // Build pagination HTML
            var startEntry = (currentPaged - 1) * currentPerPage + 1;
            var endEntry = Math.min(currentPaged * currentPerPage, total);
            if (total === 0) { startEntry = 0; endEntry = 0; }

            var pagHtml = '<div class="os-pagination-container">'
                + '<div class="os-per-page-select-wrap">'
                + '<span><?php esc_html_e("Show","olama-stores");?></span>'
                + '<select id="os-per-page-select">'
                + '<option value="10" '+(currentPerPage===10?'selected':'')+'>10</option>'
                + '<option value="20" '+(currentPerPage===20?'selected':'')+'>20</option>'
                + '<option value="50" '+(currentPerPage===50?'selected':'')+'>50</option>'
                + '<option value="100" '+(currentPerPage===100?'selected':'')+'>100</option>'
                + '</select>'
                + '<span><?php esc_html_e("entries","olama-stores");?></span>'
                + '</div>'
                + '<div class="os-pagination-info">'
                + '<?php esc_html_e("Showing","olama-stores");?> ' + startEntry + ' <?php esc_html_e("to","olama-stores");?> ' + endEntry + ' <?php esc_html_e("of","olama-stores");?> ' + total + ' <?php esc_html_e("entries","olama-stores");?>'
                + '</div>'
                + '<div class="os-pagination-controls">';

            // Prev button
            var prevDisabled = currentPaged === 1 ? 'disabled' : '';
            pagHtml += '<button type="button" class="os-pagination-btn" id="os-pag-prev" '+prevDisabled+'>&laquo;</button>';

            // Page buttons
            var startPage = Math.max(1, currentPaged - 2);
            var endPage = Math.min(pages, currentPaged + 2);

            if (startPage > 1) {
                pagHtml += '<button type="button" class="os-pagination-btn '+(currentPaged===1?'active':'')+'" data-page="1">1</button>';
                if (startPage > 2) {
                    pagHtml += '<span style="padding: 0 4px; color: var(--os-text-muted);">...</span>';
                }
            }

            for (var pIdx = startPage; pIdx <= endPage; pIdx++) {
                var activeCls = currentPaged === pIdx ? 'active' : '';
                pagHtml += '<button type="button" class="os-pagination-btn '+activeCls+'" data-page="'+pIdx+'">'+pIdx+'</button>';
            }

            if (endPage < pages) {
                if (endPage < pages - 1) {
                    pagHtml += '<span style="padding: 0 4px; color: var(--os-text-muted);">...</span>';
                }
                pagHtml += '<button type="button" class="os-pagination-btn '+(currentPaged===pages?'active':'')+'" data-page="'+pages+'">'+pages+'</button>';
            }

            // Next button
            var nextDisabled = currentPaged === pages ? 'disabled' : '';
            pagHtml += '<button type="button" class="os-pagination-btn" id="os-pag-next" '+nextDisabled+'>&raquo;</button>';

            pagHtml += '</div></div>';

            $('#os-stock-table-wrap').html(html + pagHtml);
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

    $('#os-filter-warehouse, #os-filter-stock-category, #os-filter-low-stock').on('change', function() {
        currentPaged = 1;
        loadStock();
    });

    // Sorting headers click handler
    $(document).on('click', '#os-stock-table-wrap th.sortable', function() {
        var clickedOrderby = $(this).data('orderby');
        if (currentOrderby === clickedOrderby) {
            currentOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
        } else {
            currentOrderby = clickedOrderby;
            currentOrder = 'ASC';
        }
        currentPaged = 1; // reset page on sort
        loadStock();
    });

    // Pagination page numbers handler
    $(document).on('click', '#os-stock-table-wrap .os-pagination-btn[data-page]', function() {
        currentPaged = parseInt($(this).data('page'));
        loadStock();
    });

    // Prev page button
    $(document).on('click', '#os-stock-table-wrap #os-pag-prev', function() {
        if (currentPaged > 1) {
            currentPaged--;
            loadStock();
        }
    });

    // Next page button
    $(document).on('click', '#os-stock-table-wrap #os-pag-next', function() {
        currentPaged++;
        loadStock();
    });

    // Change per page selection
    $(document).on('change', '#os-stock-table-wrap #os-per-page-select', function() {
        currentPerPage = parseInt($(this).val());
        currentPaged = 1; // reset page on page size change
        loadStock();
    });

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

    // Delete Transactions Modal
    $('#os-btn-delete-transactions').on('click', function(e) {
        e.preventDefault();
        $('#os-delete-tx-start-date').val('');
        $('#os-delete-tx-end-date').val('');
        $('#os-delete-tx-provider').html('<option value=""><?php esc_html_e( "All Providers", "olama-stores" ); ?></option>');
        $('#os-delete-tx-modal').find('.os-modal-item-search').val('');
        window.osSearchItems('', $('#os-delete-tx-item'));

        wp.apiFetch({ path: '/olama-stores/v1/providers' }).then(function(providers) {
            var opts = '<option value=""><?php esc_html_e( "All Providers", "olama-stores" ); ?></option>';
            providers.forEach(function(p) {
                opts += '<option value="' + p.id + '">' + p.company_name + '</option>';
            });
            $('#os-delete-tx-provider').html(opts);
        });

        $('#os-delete-tx-modal').show();
    });

    $('#os-delete-tx-submit').on('click', function() {
        var itemId = $('#os-delete-tx-item').val();
        var providerId = $('#os-delete-tx-provider').val();
        var startDate = $('#os-delete-tx-start-date').val();
        var endDate = $('#os-delete-tx-end-date').val();

        if (!itemId && !providerId && !startDate && !endDate) {
            alert('<?php esc_html_e( "Please select at least one filter (date, provider, or item) to delete transactions.", "olama-stores" ); ?>');
            return;
        }

        var confirmMsg = '<?php echo esc_js( __( "WARNING: You are about to permanently delete transaction records matching your filters and adjust their stock levels. This action cannot be undone. Are you sure you want to proceed?", "olama-stores" ) ); ?>';
        if (!confirm(confirmMsg)) {
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php esc_html_e( "Deleting...", "olama-stores" ); ?>');

        wp.apiFetch({
            path: '/olama-stores/v1/stock/delete-transactions',
            method: 'POST',
            data: {
                item_id: itemId ? parseInt(itemId) : null,
                provider_id: providerId ? parseInt(providerId) : null,
                start_date: startDate || null,
                end_date: endDate || null
            }
        }).then(function() {
            alert('<?php esc_html_e( "Transactions deleted and stock levels adjusted successfully.", "olama-stores" ); ?>');
            $('#os-delete-tx-modal').hide();
            loadStock();
        }).catch(function(e) {
            alert(e.message || '<?php esc_html_e( "An error occurred while deleting transactions.", "olama-stores" ); ?>');
        }).finally(function() {
            $btn.prop('disabled', false).text('<?php esc_html_e( "Delete Matching Transactions", "olama-stores" ); ?>');
        });
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
.os-btn-danger {
    background-color: #d63638 !important;
    border-color: #d63638 !important;
    color: #fff !important;
    text-shadow: none !important;
}
.os-btn-danger:hover, .os-btn-danger:focus {
    background-color: #b32d2e !important;
    border-color: #b32d2e !important;
    color: #fff !important;
}
</style>
