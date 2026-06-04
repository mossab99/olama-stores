<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap os-wrap" id="os-stock-page">
    <h1 class="os-page-title" style="display: flex; justify-content: flex-start; align-items: center; gap: 10px; margin-bottom: 15px;">
        <span class="dashicons dashicons-chart-bar" style="float: none; margin: 0;"></span>
        <span style="float: none; margin: 0;"><?php esc_html_e( 'Stock Management', 'olama-stores' ); ?></span>
    </h1>

    <div class="os-action-buttons">
        <?php if ( OS_Roles::can( 'os_receive_stock' ) ): ?>
            <a href="#" id="os-btn-receive-shipment" class="os-btn-pill os-btn-green"><span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Receive Shipment', 'olama-stores' ); ?></a>
            <a href="#" id="os-btn-receive-stock" class="os-btn-pill os-btn-blue"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Single Receipt', 'olama-stores' ); ?></a>
            <a href="#" id="os-btn-transfer-stock" class="os-btn-pill os-btn-purple"><span class="dashicons dashicons-leftright"></span> <?php esc_html_e( 'Transfer Stock', 'olama-stores' ); ?></a>
        <?php endif; ?>
        <?php if ( OS_Roles::can( 'os_adjust_stock' ) ): ?>
            <a href="#" id="os-btn-adjust-stock" class="os-btn-pill os-btn-orange"><span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Manual Adjustment', 'olama-stores' ); ?></a>
        <?php endif; ?>
        <?php if ( current_user_can( 'manage_options' ) || OS_Roles::can( 'os_manage_settings' ) ): ?>
            <a href="#" id="os-btn-delete-transactions" class="os-btn-pill os-btn-red"><span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Delete Transactions', 'olama-stores' ); ?></a>
        <?php endif; ?>
    </div>

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

    <!-- REC-04: Batch Shipment Drawer -->
    <div id="os-shipment-drawer" style="display:none; background:#fff; border:1px solid #ddd; border-radius:6px; padding:24px; margin-bottom:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="margin:0;"><span class="dashicons dashicons-download" style="margin-right:6px;"></span><?php esc_html_e( 'Receive Shipment', 'olama-stores' ); ?></h2>
            <button type="button" class="button" id="os-shipment-drawer-close">&times; <?php esc_html_e( 'Close', 'olama-stores' ); ?></button>
        </div>
        <div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:20px;">
            <div class="os-form-row" style="flex:1; min-width:160px;">
                <label><?php esc_html_e( 'Receipt Type', 'olama-stores' ); ?></label>
                <select id="os-shipment-type" style="width:100%;">
                    <option value="purchase_receipt"><?php esc_html_e( 'Purchase Receipt', 'olama-stores' ); ?></option>
                    <option value="opening_balance"><?php esc_html_e( 'Opening Balance', 'olama-stores' ); ?></option>
                </select>
            </div>
            <div class="os-form-row" style="flex:2; min-width:200px;">
                <label><?php esc_html_e( 'Notes (applies to all lines)', 'olama-stores' ); ?></label>
                <input type="text" id="os-shipment-notes" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. Invoice #1234', 'olama-stores' ); ?>">
            </div>
        </div>

        <table class="wp-list-table widefat" id="os-shipment-lines-table">
            <thead><tr>
                <th style="width:40%;"><?php esc_html_e( 'Item', 'olama-stores' ); ?></th>
                <th style="width:25%;"><?php esc_html_e( 'Warehouse', 'olama-stores' ); ?></th>
                <th style="width:15%;"><?php esc_html_e( 'Quantity', 'olama-stores' ); ?></th>
                <th style="width:15%;"><?php esc_html_e( 'Line Notes', 'olama-stores' ); ?></th>
                <th style="width:5%;"></th>
            </tr></thead>
            <tbody id="os-shipment-lines-body">
                <!-- Rows injected by JS -->
            </tbody>
        </table>

        <div style="margin-top:12px; display:flex; gap:12px; align-items:center;">
            <button type="button" class="button" id="os-shipment-add-row">
                <span class="dashicons dashicons-plus-alt2" style="margin-top:3px;"></span>
                <?php esc_html_e( 'Add Line', 'olama-stores' ); ?>
            </button>
            <span id="os-shipment-row-limit-msg" style="display:none; color:#d63638; font-size:0.85em;"><?php esc_html_e( 'Maximum 50 lines.', 'olama-stores' ); ?></span>
            <div style="margin-left:auto; display:flex; gap:8px;">
                <button type="button" class="button button-primary" id="os-shipment-submit"><?php esc_html_e( 'Post Shipment', 'olama-stores' ); ?></button>
                <button type="button" class="button" id="os-shipment-drawer-close2"><?php esc_html_e( 'Cancel', 'olama-stores' ); ?></button>
            </div>
        </div>
    </div>

    <!-- REC-08: Transfer Stock Modal -->
    <div id="os-transfer-modal" class="os-modal" style="display:none;">
        <div class="os-modal-content">
            <h2><?php esc_html_e( 'Transfer Stock Between Warehouses', 'olama-stores' ); ?></h2>
            <div class="os-form-row">
                <label><?php esc_html_e( 'Item', 'olama-stores' ); ?></label>
                <div class="os-input-group">
                    <input type="text" class="os-modal-item-search" data-target="#os-transfer-item" placeholder="<?php esc_attr_e( 'Search items...', 'olama-stores' ); ?>">
                    <select id="os-transfer-item"></select>
                </div>
            </div>
            <div class="os-form-row">
                <label><?php esc_html_e( 'From Warehouse', 'olama-stores' ); ?></label>
                <select id="os-transfer-from-wh"></select>
            </div>
            <div class="os-form-row">
                <label><?php esc_html_e( 'To Warehouse', 'olama-stores' ); ?></label>
                <select id="os-transfer-to-wh"></select>
            </div>
            <div class="os-form-row">
                <label><?php esc_html_e( 'Quantity', 'olama-stores' ); ?></label>
                <input type="number" id="os-transfer-qty" min="1" value="1">
                <small id="os-transfer-avail" style="color:#0073aa;"></small>
            </div>
            <div class="os-form-row">
                <label><?php esc_html_e( 'Notes', 'olama-stores' ); ?></label>
                <textarea id="os-transfer-notes"></textarea>
            </div>
            <div class="os-form-actions">
                <button type="button" class="button button-primary" id="os-transfer-submit"><?php esc_html_e( 'Execute Transfer', 'olama-stores' ); ?></button>
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

            var html='<table class="wp-list-table widefat striped os-table-card"><thead><tr>'
                +'<th class="'+getHeaderClass('name')+'" data-orderby="name"><?php esc_html_e("Item","olama-stores");?></th>'
                +'<th class="'+getHeaderClass('sku')+'" data-orderby="sku"><?php esc_html_e("SKU","olama-stores");?></th>'
                +'<th class="'+getHeaderClass('warehouse_name')+'" data-orderby="warehouse_name"><?php esc_html_e("Warehouse","olama-stores");?></th>'
                +'<th class="os-num-col '+getHeaderClass('quantity_on_hand')+'" data-orderby="quantity_on_hand"><?php esc_html_e("On Hand","olama-stores");?></th>'
                +'<th class="os-num-col '+getHeaderClass('quantity_reserved')+'" data-orderby="quantity_reserved"><?php esc_html_e("Reserved","olama-stores");?></th>'
                +'<th class="os-num-col '+getHeaderClass('quantity_available')+'" data-orderby="quantity_available"><?php esc_html_e("Available","olama-stores");?></th>'
                +'<th class="os-num-col '+getHeaderClass('min_stock_level')+'" data-orderby="min_stock_level"><?php esc_html_e("Min Level","olama-stores");?></th></tr></thead><tbody>';

            rows.forEach(function(r){
                var avail = parseInt(r.quantity_available);
                var min   = parseInt(r.min_stock_level);
                var badgeCls = avail <= 0 ? 'os-status-badge-red' : (avail <= min ? 'os-status-badge-yellow' : 'os-status-badge-green');
                var statusText = avail <= 0 ? '<?php esc_html_e("Critical","olama-stores");?>' : (avail <= min ? '<?php esc_html_e("Low","olama-stores");?>' : '<?php esc_html_e("Healthy","olama-stores");?>');
                var availDisplay = avail < 0 ? '<span class="os-badge os-badge-lost">'+avail+'</span>' : avail;
                
                html+='<tr><td class="os-item-name"><strong>'+r.name+'</strong><small dir="rtl">'+r.name_ar+'</small></td>'
                    +'<td class="os-item-sku"><code>'+r.sku+'</code></td>'
                    +'<td>'+r.warehouse_name+'</td>'
                    +'<td class="os-num-col">'+r.quantity_on_hand+'</td>'
                    +'<td class="os-num-col">'+r.quantity_reserved+'</td>'
                    +'<td class="os-num-col"><strong>'+availDisplay+'</strong> <span class="os-status-badge '+badgeCls+'">'+statusText+'</span></td>'
                    +'<td class="os-num-col">'+min+'</td></tr>';
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
            var maxButtons = 5;
            var startPage = 1;
            var endPage = pages;

            if (pages > maxButtons) {
                var half = Math.floor(maxButtons / 2);
                startPage = currentPaged - half;
                endPage = currentPaged + half;

                if (startPage < 1) {
                    endPage = maxButtons;
                    startPage = 1;
                } else if (endPage > pages) {
                    startPage = pages - maxButtons + 1;
                    endPage = pages;
                }
            }

            if (startPage > 1) {
                pagHtml += '<button type="button" class="os-pagination-btn" data-page="1">1</button>';
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
                pagHtml += '<button type="button" class="os-pagination-btn" data-page="'+pages+'">'+pages+'</button>';
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
        // Cache warehouses for batch drawer + transfer modal
        shipmentWarehouses = r[0];

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

    // ── REC-04: Batch Shipment Drawer ─────────────────────────────────────────
    var OS_SHIP_MAX = 50;
    var shipmentWarehouses = [];

    function makeShipmentRow(warehouses) {
        var whOpts = '<option value=""></option>';
        warehouses.forEach(function(w){ whOpts += '<option value="'+w.id+'">'+w.name+'</option>'; });

        var $tr = $('<tr class="os-shipment-line">').append(
            $('<td>').append(
                $('<div class="os-input-group">').append(
                    $('<input type="text" class="os-modal-item-search os-shipment-item-search">').attr('placeholder', '<?php esc_attr_e( 'Search items...', 'olama-stores' ); ?>'),
                    $('<select class="os-shipment-item-select"></select>')
                )
            ),
            $('<td>').append( $('<select class="os-shipment-wh-select" style="width:100%;"></select>').html(whOpts) ),
            $('<td>').append( $('<input type="number" class="os-shipment-qty" min="1" value="1" style="width:80px;">') ),
            $('<td>').append( $('<input type="text" class="os-shipment-line-notes" style="width:100%;" placeholder="<?php esc_attr_e( 'Optional', 'olama-stores' ); ?>">') ),
            $('<td>').append( $('<button type="button" class="button button-small os-shipment-remove-row">&times;</button>') )
        );
        return $tr;
    }

    function updateShipmentRowCount() {
        var count = $('#os-shipment-lines-body .os-shipment-line').length;
        var atMax = count >= OS_SHIP_MAX;
        $('#os-shipment-add-row').prop('disabled', atMax);
        $('#os-shipment-row-limit-msg').toggle(atMax);
        // Hide remove btn if only 1 row
        $('#os-shipment-lines-body .os-shipment-remove-row').show();
        if (count === 1) $('#os-shipment-lines-body .os-shipment-remove-row').first().hide();
    }

    $('#os-btn-receive-shipment').on('click', function(e){
        e.preventDefault();
        var $body = $('#os-shipment-lines-body');
        $body.empty();
        // Use already-loaded warehouses list
        var whs = shipmentWarehouses.length ? shipmentWarehouses : [];
        $body.append(makeShipmentRow(whs));
        window.osSearchItems('', $body.find('.os-shipment-item-select'), null, { per_page: 20 });
        updateShipmentRowCount();
        $('#os-shipment-drawer').slideDown(200);
        $('html, body').animate({ scrollTop: $('#os-shipment-drawer').offset().top - 80 }, 300);
    });

    $('#os-shipment-add-row').on('click', function(){
        if ($('#os-shipment-lines-body .os-shipment-line').length >= OS_SHIP_MAX) return;
        var $newRow = makeShipmentRow(shipmentWarehouses);
        $('#os-shipment-lines-body').append($newRow);
        window.osSearchItems('', $newRow.find('.os-shipment-item-select'), null, { per_page: 20 });
        updateShipmentRowCount();
    });

    $(document).on('click', '.os-shipment-remove-row', function(){
        $(this).closest('.os-shipment-line').remove();
        updateShipmentRowCount();
    });

    $('#os-shipment-drawer-close, #os-shipment-drawer-close2').on('click', function(){
        $('#os-shipment-drawer').slideUp(200);
    });

    $('#os-shipment-submit').on('click', function(){
        var $btn = $(this);
        var items = [];
        var hasError = false;

        $('#os-shipment-lines-body .os-shipment-line').each(function(i){
            var itemId = parseInt($(this).find('.os-shipment-item-select').val());
            var whId   = parseInt($(this).find('.os-shipment-wh-select').val());
            var qty    = parseInt($(this).find('.os-shipment-qty').val());
            var notes  = $(this).find('.os-shipment-line-notes').val();

            if (!itemId || !whId || !qty || qty <= 0) {
                alert('<?php esc_html_e( 'Row', 'olama-stores' ); ?> ' + (i+1) + ': <?php esc_html_e( 'Please fill in item, warehouse, and quantity.', 'olama-stores' ); ?>');
                hasError = true;
                return false; // break each
            }
            items.push({ item_id: itemId, warehouse_id: whId, quantity: qty, notes: notes });
        });

        if (hasError || !items.length) return;

        $btn.prop('disabled', true).text('<?php esc_html_e( 'Posting...', 'olama-stores' ); ?>');

        wp.apiFetch({
            path: '/olama-stores/v1/stock/receive-batch',
            method: 'POST',
            data: {
                movement_type:    $('#os-shipment-type').val(),
                notes:            $('#os-shipment-notes').val(),
                academic_year_id: olamaStores.activeYearId,
                items:            items
            }
        }).then(function(res){
            alert('<?php esc_html_e( 'Shipment posted successfully!', 'olama-stores' ); ?> (' + res.count + ' <?php esc_html_e( 'items)', 'olama-stores' ); ?>');
            $('#os-shipment-drawer').slideUp(200);
            loadStock();
        }).catch(function(e){
            alert(e.message || '<?php esc_html_e( 'Error posting shipment.', 'olama-stores' ); ?>');
        }).finally(function(){
            $btn.prop('disabled', false).text('<?php esc_html_e( 'Post Shipment', 'olama-stores' ); ?>');
        });
    });

    // ── REC-08: Transfer Stock Modal ──────────────────────────────────────────
    function updateTransferAvailStock() {
        var itemId = $('#os-transfer-item').val();
        var whId   = $('#os-transfer-from-wh').val();
        if (!itemId || !whId) { $('#os-transfer-avail').text(''); return; }
        wp.apiFetch({ path: '/olama-stores/v1/stock?item_id=' + itemId + '&warehouse_id=' + whId }).then(function(rows){
            if (!rows.items || !rows.items.length) { $('#os-transfer-avail').text('<?php esc_html_e( 'Available: 0', 'olama-stores' ); ?>'); return; }
            var avail = parseInt(rows.items[0].quantity_available) || 0;
            $('#os-transfer-avail').text('<?php esc_html_e( 'Available:', 'olama-stores' ); ?> ' + avail);
        });
    }

    $('#os-btn-transfer-stock').on('click', function(e){
        e.preventDefault();
        var whOpts = '<option value=""></option>';
        shipmentWarehouses.forEach(function(w){ whOpts += '<option value="'+w.id+'">'+w.name+'</option>'; });
        $('#os-transfer-from-wh, #os-transfer-to-wh').html(whOpts);
        window.osSearchItems('', $('#os-transfer-item'));
        $('#os-transfer-modal').find('.os-modal-item-search').val('');
        $('#os-transfer-avail').text('');
        $('#os-transfer-qty').val(1);
        $('#os-transfer-notes').val('');
        $('#os-transfer-modal').show();
    });

    $('#os-transfer-item, #os-transfer-from-wh').on('change', updateTransferAvailStock);

    $('#os-transfer-submit').on('click', function(){
        var $btn = $(this);
        var payload = {
            item_id:           parseInt($('#os-transfer-item').val()),
            from_warehouse_id: parseInt($('#os-transfer-from-wh').val()),
            to_warehouse_id:   parseInt($('#os-transfer-to-wh').val()),
            quantity:          parseInt($('#os-transfer-qty').val()),
            notes:             $('#os-transfer-notes').val()
        };
        if (!payload.item_id || !payload.from_warehouse_id || !payload.to_warehouse_id || !payload.quantity) {
            alert('<?php esc_html_e( 'Please fill in all required fields.', 'olama-stores' ); ?>');
            return;
        }
        $btn.prop('disabled', true).text('<?php esc_html_e( 'Transferring...', 'olama-stores' ); ?>');
        wp.apiFetch({ path: '/olama-stores/v1/stock/transfer', method: 'POST', data: payload }).then(function(){
            alert('<?php esc_html_e( 'Transfer completed successfully.', 'olama-stores' ); ?>');
            $('#os-transfer-modal').hide();
            loadStock();
        }).catch(function(e){
            alert(e.message || '<?php esc_html_e( 'Transfer failed.', 'olama-stores' ); ?>');
        }).finally(function(){
            $btn.prop('disabled', false).text('<?php esc_html_e( 'Execute Transfer', 'olama-stores' ); ?>');
        });
    });
    // ─────────────────────────────────────────────────────────────────────────

})(jQuery);
</script>

<style>
/* New Actions Row */
.os-action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
    margin-bottom: 20px;
}
.os-btn-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 1px solid transparent;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.os-btn-pill:focus {
    box-shadow: 0 0 0 2px #fff, 0 0 0 4px var(--os-primary, #007cba);
    outline: none;
}
.os-btn-pill .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}
.os-btn-green { background: #e6f4ea; color: #1e8e3e; border-color: #ceead6; }
.os-btn-green:hover { background: #ceead6; color: #137333; }
.os-btn-blue { background: #e8f0fe; color: #1a73e8; border-color: #d2e3fc; }
.os-btn-blue:hover { background: #d2e3fc; color: #174ea6; }
.os-btn-purple { background: #f3e8fd; color: #9334e6; border-color: #e9d2fd; }
.os-btn-purple:hover { background: #e9d2fd; color: #7627bb; }
.os-btn-orange { background: #fef7e0; color: #ea8600; border-color: #fce8b2; }
.os-btn-orange:hover { background: #fce8b2; color: #c26c00; }
.os-btn-red { background: #fce8e6; color: #d93025; border-color: #fad2cf; }
.os-btn-red:hover { background: #fad2cf; color: #b31412; }

/* Filters Bar */
.os-filters.tablenav.top {
    background: #fff;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
    height: auto;
    clear: both;
}
.os-filters select, .os-filters input, .os-filters .button {
    margin: 0 !important;
    vertical-align: middle;
}
.os-filters label {
    display: flex;
    align-items: center;
    gap: 4px;
    margin: 0;
}

/* Table Card styling */
#os-stock-table-wrap {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
    border: 1px solid #e2e8f0;
    overflow: hidden;
}
table.os-table-card {
    border: none !important;
    margin: 0 !important;
    box-shadow: none !important;
}

/* Typography & Badges */
.os-item-name strong { font-size: 1.05em; color: #1e293b; display: block; margin-bottom: 2px; }
.os-item-name small { color: #64748b; font-size: 0.9em; }
.os-item-sku code { background: #f1f5f9; color: #64748b; padding: 3px 6px; border-radius: 4px; font-size: 0.85em; font-weight: 500; border: 1px solid #e2e8f0; }
.os-num-col { text-align: end !important; font-variant-numeric: tabular-nums; }

.os-status-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-inline-start: 6px;
    vertical-align: middle;
}
.os-status-badge-green { background: #dcfce7; color: #166534; }
.os-status-badge-yellow { background: #fef08a; color: #854d0e; }
.os-status-badge-red { background: #fee2e2; color: #991b1b; }

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

/* Enhanced table headers */
.os-wrap table.wp-list-table thead th {
    font-size: 0.92rem !important;
    text-transform: none !important;
    font-weight: 600 !important;
    letter-spacing: 0.01em !important;
    padding: 14px 12px !important;
    color: #ffffff !important;
}

.os-wrap table.wp-list-table thead th.sortable:hover {
    background-color: #0b5ed7 !important;
}

/* Footer & Pagination adjustments */
.os-pagination-container {
    background: #f1f5f9 !important; /* Darker modern grey footer */
    border: 1px solid #cbd5e1 !important;
    border-top: none !important;
    margin-top: 0 !important;
    border-radius: 0 0 var(--os-radius) var(--os-radius) !important;
    padding: 14px 20px !important;
}

/* Style and resize the "Show X entries" dropdown */
.os-per-page-select-wrap select {
    width: 75px !important;
    max-width: 75px !important;
    min-width: 75px !important;
    height: 34px !important;
    padding: 2px 24px 2px 8px !important;
    font-size: 0.875rem !important;
    font-weight: 600 !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 6px !important;
    background-color: #ffffff !important;
    color: #1e293b !important;
    cursor: pointer !important;
    display: inline-block !important;
    vertical-align: middle !important;
    box-sizing: border-box !important;
}

.os-per-page-select-wrap, .os-pagination-info {
    font-weight: 500 !important;
    color: #334155 !important;
    font-size: 0.875rem !important;
}

.os-pagination-controls {
    gap: 6px !important;
}

.os-pagination-btn {
    min-width: 34px !important;
    height: 34px !important;
    border-radius: 6px !important;
    font-weight: 600 !important;
    border-color: #cbd5e1 !important;
    color: #475569 !important;
    background-color: #ffffff !important;
    transition: all 0.15s ease-in-out !important;
}

.os-pagination-btn:hover:not(:disabled) {
    background-color: #e2e8f0 !important;
    border-color: #94a3b8 !important;
    color: #0f172a !important;
}

.os-pagination-btn.active {
    background-color: var(--os-primary) !important;
    border-color: var(--os-primary) !important;
    color: #ffffff !important;
}
</style>
