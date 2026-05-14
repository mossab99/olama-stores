<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap os-wrap" id="os-add-items-page">
    <h1 class="os-page-title">
        <span class="dashicons dashicons-plus"></span>
        <?php esc_html_e( 'Add Items to Store', 'olama-stores' ); ?>
    </h1>

    <div class="os-card" style="max-width: 600px; margin-top: 20px;">
        <form id="os-add-items-form">
            <div class="os-form-row">
                <label><?php esc_html_e( 'Select Item', 'olama-stores' ); ?> *</label>
                <div class="os-input-group">
                    <input type="text" class="os-modal-item-search" data-target="#os-selected-item" placeholder="<?php esc_attr_e( 'Search by name, SKU or barcode…', 'olama-stores' ); ?>" autocomplete="off">
                    <select id="os-selected-item" required></select>
                </div>
            </div>

            <div id="os-item-details-preview" style="display:none; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #3498db;">
                <h3 style="margin-top:0;" id="preview-name"></h3>
                <p style="margin-bottom:5;"><strong id="preview-sku"></strong> | <span id="preview-category"></span></p>
                <p style="margin-bottom:0; color: #27ae60;"><span class="dashicons dashicons-chart-bar" style="font-size:16px; width:16px; height:16px; vertical-align:middle;"></span> <?php esc_html_e( 'Current Stock:', 'olama-stores' ); ?> <strong id="preview-stock"></strong></p>
            </div>

            <div class="os-form-row">
                <label><?php esc_html_e( 'Warehouse', 'olama-stores' ); ?> *</label>
                <select id="os-warehouse-id" required>
                    <option value=""><?php esc_html_e( 'Select Warehouse', 'olama-stores' ); ?></option>
                </select>
            </div>

            <div class="os-form-row">
                <label><?php esc_html_e( 'Item Count (Quantity)', 'olama-stores' ); ?> *</label>
                <input type="number" id="os-item-count" min="1" value="1" required style="font-size: 1.2em; font-weight: bold;">
            </div>

            <div class="os-form-row">
                <label><?php esc_html_e( 'Notes', 'olama-stores' ); ?></label>
                <textarea id="os-notes" placeholder="<?php esc_attr_e( 'Optional notes about this addition...', 'olama-stores' ); ?>"></textarea>
            </div>

            <div class="os-form-actions">
                <button type="submit" class="button button-primary button-large" id="os-btn-save-addition" style="width: 100%; height: 45px; font-size: 1.1em;">
                    <span class="dashicons dashicons-yes" style="vertical-align: middle; margin-top: -2px;"></span>
                    <?php esc_html_e( 'Add to Store', 'olama-stores' ); ?>
                </button>
            </div>
        </form>
    </div>

    <div id="os-recent-additions" style="margin-top: 40px;">
        <h3><?php esc_html_e( 'Recent Additions', 'olama-stores' ); ?></h3>
        <div id="os-additions-table-wrap">
            <span class="os-loading"><?php esc_html_e( 'Loading recent additions…', 'olama-stores' ); ?></span>
        </div>
    </div>
</div>

<script>
(function($){
    var items = [];

    // Load initial data
    Promise.all([
        wp.apiFetch({ path: '/olama-stores/v1/warehouses' })
    ]).then(function(results){
        var whs = results[0];
        var whOpts = '<option value=""><?php esc_html_e("Select Warehouse","olama-stores");?></option>';
        whs.forEach(function(w){ whOpts += '<option value="'+w.id+'">'+w.name+'</option>'; });
        $('#os-warehouse-id').html(whOpts);
    });

    $('#os-selected-item').on('os:search-results', function(e, results){
        items = results;
        if(items.length === 1) {
            $(this).val(items[0].id).trigger('change');
        }
    });


    $('#os-selected-item').on('change', function(){
        var id = $(this).val();
        if(!id) { $('#os-item-details-preview').hide(); return; }
        var item = items ? items.find(function(i){ return i.id == id; }) : null;
        if(item) {
            $('#preview-name').text(item.name);
            $('#preview-sku').text(item.sku);
            $('#preview-category').text(item.category_name || 'No Category');
            $('#preview-stock').text((item.stock_count || 0) + ' ' + (item.unit_symbol || ''));
            $('#os-item-details-preview').fadeIn();
        }
    });

    // Form submit
    $('#os-add-items-form').on('submit', function(e){
        e.preventDefault();
        var btn = $('#os-btn-save-addition');
        btn.prop('disabled', true).text('<?php esc_html_e("Saving...","olama-stores");?>');

        var payload = {
            item_id: parseInt($('#os-selected-item').val()),
            warehouse_id: parseInt($('#os-warehouse-id').val()),
            quantity: parseInt($('#os-item-count').val()),
            movement_type: 'opening_balance', // Default for "Add Items"
            notes: $('#os-notes').val(),
            academic_year_id: olamaStores.activeYearId
        };

        wp.apiFetch({ path: '/olama-stores/v1/stock/receive', method: 'POST', data: payload }).then(function(){
            // Success
            alert('<?php esc_html_e("Items added successfully!","olama-stores");?>');
            $('#os-add-items-form')[0].reset();
            $('#os-selected-item').html('<option value=""><?php esc_html_e("Search and select an item...","olama-stores");?></option>');
            $('#os-item-details-preview').hide();
            loadRecentMovements();
        }).catch(function(e){
            alert(e.message || 'Error adding items');
        }).finally(function(){
            btn.prop('disabled', false).html('<span class="dashicons dashicons-yes" style="vertical-align: middle; margin-top: -2px;"></span> <?php esc_html_e("Add to Store","olama-stores");?>');
        });
    });

    function loadRecentMovements(){
        // Fetch movements for "opening_balance" or recent receipts
        wp.apiFetch({ path: '/olama-stores/v1/stock/movements?limit=10' }).then(function(movements){
            if(!movements.length){ $('#os-additions-table-wrap').html('<p><?php esc_html_e("No recent additions.","olama-stores");?></p>'); return; }
            var html = '<table class="wp-list-table widefat striped"><thead><tr>'
                + '<th><?php esc_html_e("Date","olama-stores");?></th>'
                + '<th><?php esc_html_e("Item","olama-stores");?></th>'
                + '<th><?php esc_html_e("Warehouse","olama-stores");?></th>'
                + '<th><?php esc_html_e("Quantity","olama-stores");?></th>'
                + '<th><?php esc_html_e("Performed By","olama-stores");?></th>';
            if (olamaStores.caps.is_admin) {
                html += '<th style="width:50px;"><?php esc_html_e("Actions","olama-stores");?></th>';
            }
            html += '</tr></thead><tbody>';
            movements.forEach(function(m){
                var date = new Date(m.performed_at).toLocaleDateString();
                html += '<tr>'
                    + '<td>'+date+'</td>'
                    + '<td><strong>'+m.item_name+'</strong><br><small>'+m.sku+'</small></td>'
                    + '<td>'+m.warehouse_name+'</td>'
                    + '<td><span class="os-badge os-badge-success">+ '+m.quantity+'</span></td>'
                    + '<td>'+(m.performed_by_name || 'System')+'</td>';
                if (olamaStores.caps.is_admin) {
                    html += '<td><button type="button" class="button button-small button-link-delete os-delete-addition" data-id="'+m.id+'"><span class="dashicons dashicons-trash"></span></button></td>';
                }
                html += '</tr>';
            });
            html += '</tbody></table>';
            $('#os-additions-table-wrap').html(html);
        });
    }

    $(document).on('click', '.os-delete-addition', function(){
        if (!confirm('<?php esc_html_e("Are you sure you want to delete this addition? This will reverse the stock change.","olama-stores");?>')) return;
        var id = $(this).data('id');
        wp.apiFetch({ path: '/olama-stores/v1/stock/movements/' + id, method: 'DELETE' }).then(function(){
            loadRecentMovements();
        }).catch(function(e){ alert(e.message); });
    });

    loadRecentMovements();

})(jQuery);
</script>

<style>
.os-card { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
.os-form-row { margin-bottom: 25px; }
.os-form-row label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50; }
.os-form-row input[type="text"], .os-form-row input[type="number"], .os-form-row select, .os-form-row textarea {
    width: 100%; padding: 12px; border: 2px solid #eaeff2; border-radius: 8px; transition: border-color 0.2s;
}
.os-form-row input:focus, .os-form-row select:focus, .os-form-row textarea:focus { border-color: #3498db; outline: none; }
.os-badge { padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.9em; }
.os-badge-success { background: #e6fffa; color: #047481; }
</style>
