<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap os-wrap" id="os-items-page">
    <h1 class="os-page-title">
        <span class="dashicons dashicons-products"></span>
        <?php esc_html_e( 'Item Registry', 'olama-stores' ); ?>
        <?php if ( OS_Roles::can( 'os_manage_items' ) ): ?>
            <a href="#" id="os-btn-add-item" class="page-title-action"><?php esc_html_e( 'Add Item', 'olama-stores' ); ?></a>
            <a href="#" id="os-btn-add-custom" class="page-title-action" style="background-color: #e74c3c; border-color: #c0392b; color: #fff;"><?php esc_html_e( 'Add School Custom', 'olama-stores' ); ?></a>
            <a href="#" id="os-btn-add-books" class="page-title-action" style="background-color: #27ae60; border-color: #219150; color: #fff;"><?php esc_html_e( 'Add Grade Books', 'olama-stores' ); ?></a>
            <a href="#" id="os-btn-copy-provider" class="page-title-action" style="background-color: #8e44ad; border-color: #7d3c98; color: #fff;">
                <span class="dashicons dashicons-controls-repeat" style="font-size:14px;width:14px;height:14px;vertical-align:middle;margin-right:3px;"></span>
                <?php esc_html_e( 'Copy Provider Items', 'olama-stores' ); ?>
            </a>
            <a href="#" id="os-btn-delete-provider" class="page-title-action" style="background-color: #c0392b; border-color: #96281b; color: #fff;">
                <span class="dashicons dashicons-trash" style="font-size:14px;width:14px;height:14px;vertical-align:middle;margin-right:3px;"></span>
                <?php esc_html_e( 'Delete Provider Items', 'olama-stores' ); ?>
            </a>
        <?php endif; ?>
    </h1>

    <div class="os-filters tablenav top">
        <input type="text" id="os-search-items" class="os-filter-input" placeholder="<?php esc_attr_e( 'Search by name, SKU or barcode…', 'olama-stores' ); ?>">
        <select id="os-filter-category">
            <option value=""><?php esc_html_e( 'All Categories', 'olama-stores' ); ?></option>
        </select>
        <button class="button" id="os-btn-export-items"><?php esc_html_e( 'Export Excel', 'olama-stores' ); ?></button>
    </div>

    <div id="os-items-table-wrap">
        <span class="os-loading"><?php esc_html_e( 'Loading items…', 'olama-stores' ); ?></span>
    </div>

    <!-- Add / Edit Item Modal -->
    <div id="os-item-modal" class="os-modal" style="display:none;">
        <div class="os-modal-content">
            <h2 id="os-item-modal-title"><?php esc_html_e( 'Add Item', 'olama-stores' ); ?></h2>
            <form id="os-item-form">
                <input type="hidden" id="os-item-id" value="">
                <div class="os-form-row">
                    <label><?php esc_html_e( 'SKU', 'olama-stores' ); ?></label>
                    <input type="text" id="os-item-sku" placeholder="<?php esc_attr_e( 'Auto-generated if blank', 'olama-stores' ); ?>">
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Name (English)', 'olama-stores' ); ?> *</label>
                    <input type="text" id="os-item-name" required>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Name (Arabic)', 'olama-stores' ); ?></label>
                    <input type="text" id="os-item-name-ar" dir="rtl">
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Category', 'olama-stores' ); ?> *</label>
                    <select id="os-item-category" required></select>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Unit', 'olama-stores' ); ?> *</label>
                    <select id="os-item-unit" required></select>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Barcode', 'olama-stores' ); ?></label>
                    <input type="text" id="os-item-barcode">
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Min Stock Level', 'olama-stores' ); ?></label>
                    <input type="number" id="os-item-min-stock" value="0" min="0">
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Unit Price', 'olama-stores' ); ?></label>
                    <input type="number" id="os-item-price" step="0.01" value="0.00">
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Provider', 'olama-stores' ); ?></label>
                    <select id="os-item-provider">
                        <option value=""><?php esc_html_e( 'No Provider', 'olama-stores' ); ?></option>
                    </select>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Description', 'olama-stores' ); ?></label>
                    <textarea id="os-item-description"></textarea>
                </div>
                <div class="os-form-actions">
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Item', 'olama-stores' ); ?></button>
                    <button type="button" class="button os-modal-close"><?php esc_html_e( 'Cancel', 'olama-stores' ); ?></button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add School Custom Modal -->
    <div id="os-custom-modal" class="os-modal" style="display:none;">
        <div class="os-modal-content">
            <h2 id="os-custom-modal-title"><?php esc_html_e( 'Add School Custom Item', 'olama-stores' ); ?></h2>
            <form id="os-custom-form">
                <input type="hidden" id="os-custom-id" value="">
                <div class="os-form-row">
                    <label><?php esc_html_e( 'SKU', 'olama-stores' ); ?></label>
                    <input type="text" id="os-custom-sku" placeholder="<?php esc_attr_e( 'Auto-generated if blank', 'olama-stores' ); ?>">
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Name', 'olama-stores' ); ?> *</label>
                    <input type="text" id="os-custom-name" readonly>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Custom Model', 'olama-stores' ); ?></label>
                    <select id="os-custom-model">
                        <option value=""><?php esc_html_e( 'Select Model', 'olama-stores' ); ?></option>
                    </select>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Color', 'olama-stores' ); ?></label>
                    <select id="os-custom-color">
                        <option value=""><?php esc_html_e( 'Select Color', 'olama-stores' ); ?></option>
                    </select>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Size', 'olama-stores' ); ?></label>
                    <select id="os-custom-size">
                        <option value=""><?php esc_html_e( 'Select Size', 'olama-stores' ); ?></option>
                    </select>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Fabric', 'olama-stores' ); ?></label>
                    <select id="os-custom-fabric">
                        <option value=""><?php esc_html_e( 'Select Fabric', 'olama-stores' ); ?></option>
                    </select>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Unit Price', 'olama-stores' ); ?></label>
                    <input type="number" id="os-custom-price" step="0.01" value="0.00">
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Provider', 'olama-stores' ); ?></label>
                    <select id="os-custom-provider">
                        <option value=""><?php esc_html_e( 'No Provider', 'olama-stores' ); ?></option>
                    </select>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Category', 'olama-stores' ); ?> *</label>
                    <select id="os-custom-category" required></select>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Unit', 'olama-stores' ); ?> *</label>
                    <select id="os-custom-unit" required></select>
                </div>
                <div class="os-form-actions">
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Custom Item', 'olama-stores' ); ?></button>
                    <button type="button" class="button os-modal-close"><?php esc_html_e( 'Cancel', 'olama-stores' ); ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Grade Books Modal -->
    <div id="os-books-modal" class="os-modal" style="display:none;">
        <div class="os-modal-content" style="max-width: 600px;">
            <h2 id="os-books-modal-title"><?php esc_html_e( 'Add Grade Books', 'olama-stores' ); ?></h2>
            <form id="os-books-form">
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Grade', 'olama-stores' ); ?> *</label>
                    <select id="os-books-grade" required>
                        <option value=""><?php esc_html_e( 'Select Grade', 'olama-stores' ); ?></option>
                    </select>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Subject', 'olama-stores' ); ?> *</label>
                    <select id="os-books-subject" required>
                        <option value=""><?php esc_html_e( 'Select Subject', 'olama-stores' ); ?></option>
                    </select>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Provider', 'olama-stores' ); ?></label>
                    <select id="os-books-provider">
                        <option value=""><?php esc_html_e( 'No Provider', 'olama-stores' ); ?></option>
                    </select>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Category', 'olama-stores' ); ?> *</label>
                    <select id="os-books-category" required></select>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Unit Price (Total Package)', 'olama-stores' ); ?></label>
                    <input type="number" id="os-books-price" step="0.01" value="0.00">
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Books in Package', 'olama-stores' ); ?></label>
                    <div id="os-books-list">
                        <div class="os-book-item os-flex" style="margin-bottom: 5px;">
                            <input type="text" class="os-book-name" placeholder="<?php esc_attr_e( 'Book Name (e.g. Student Book)', 'olama-stores' ); ?>" style="flex: 1;">
                            <button type="button" class="button os-btn-remove-book" style="margin-left: 5px;">&times;</button>
                        </div>
                    </div>
                    <button type="button" class="button" id="os-btn-add-book-row" style="margin-top: 5px;">+ <?php esc_html_e( 'Add Another Book', 'olama-stores' ); ?></button>
                </div>
                <div class="os-form-actions">
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Grade Books', 'olama-stores' ); ?></button>
                    <button type="button" class="button os-modal-close"><?php esc_html_e( 'Cancel', 'olama-stores' ); ?></button>
                </div>
            </form>
        </div>
    </div>


    <!-- Copy Provider Items Modal -->
    <div id="os-copy-provider-modal" class="os-modal" style="display:none;">
        <div class="os-modal-content" style="max-width:520px;">
            <h2>
                <span class="dashicons dashicons-controls-repeat" style="color:#8e44ad;font-size:24px;vertical-align:middle;margin-right:8px;"></span>
                <?php esc_html_e( 'Copy Provider Items', 'olama-stores' ); ?>
            </h2>

            <div style="background:#f0ebf8;border-left:4px solid #8e44ad;padding:12px 16px;border-radius:6px;margin-bottom:20px;color:#4a235a;font-size:0.9em;">
                <?php esc_html_e( 'This will duplicate items from the source provider and assign them to the target provider. You can filter by model or copy all items. New SKUs will be auto-generated. Original items are not modified.', 'olama-stores' ); ?>
            </div>

            <div class="os-form-row">
                <label style="font-weight:600;"><?php esc_html_e( 'Copy FROM (Source Provider)', 'olama-stores' ); ?> *</label>
                <select id="os-copy-from-provider" required style="border-color:#8e44ad;">
                    <option value=""><?php esc_html_e( 'Select source provider…', 'olama-stores' ); ?></option>
                </select>
            </div>

            <div class="os-form-row">
                <label style="font-weight:600;"><?php esc_html_e( 'Filter by Model (School Custom)', 'olama-stores' ); ?></label>
                <select id="os-copy-model-filter" style="border-color:#8e44ad;">
                    <option value=""><?php esc_html_e( '— All Items (no model filter) —', 'olama-stores' ); ?></option>
                </select>
                <p style="margin:4px 0 0;font-size:0.82em;color:#6c3483;"><?php esc_html_e( 'Leave as "All Items" to copy every item from the selected provider.', 'olama-stores' ); ?></p>
            </div>

            <div id="os-copy-provider-preview" style="display:none;margin:4px 0 16px;padding:10px 14px;background:#fdfbff;border:1px solid #d5b8f0;border-radius:6px;font-size:0.9em;color:#4a235a;">
                <span class="dashicons dashicons-info" style="vertical-align:middle;margin-right:4px;"></span>
                <span id="os-copy-preview-text"></span>
            </div>

            <div class="os-form-row">
                <label style="font-weight:600;"><?php esc_html_e( 'Copy TO (Target Provider)', 'olama-stores' ); ?> *</label>
                <select id="os-copy-to-provider" required style="border-color:#8e44ad;">
                    <option value=""><?php esc_html_e( 'Select target provider…', 'olama-stores' ); ?></option>
                </select>
            </div>

            <div class="os-form-actions" style="margin-top:24px;">
                <button type="button" class="button button-primary" id="os-copy-provider-submit"
                    style="background-color:#8e44ad;border-color:#7d3c98;height:40px;font-size:1em;font-weight:600;padding:0 20px;">
                    <span class="dashicons dashicons-controls-repeat" style="font-size:16px;width:16px;height:16px;vertical-align:middle;margin-right:4px;"></span>
                    <?php esc_html_e( 'Copy Items', 'olama-stores' ); ?>
                </button>
                <button type="button" class="button os-modal-close"><?php esc_html_e( 'Cancel', 'olama-stores' ); ?></button>
            </div>
        </div>
    </div>

    <!-- Delete Provider Items Modal -->
    <div id="os-delete-provider-modal" class="os-modal" style="display:none;">
        <div class="os-modal-content" style="max-width:520px;">
            <h2>
                <span class="dashicons dashicons-trash" style="color:#c0392b;font-size:24px;vertical-align:middle;margin-right:8px;"></span>
                <?php esc_html_e( 'Delete Provider Items', 'olama-stores' ); ?>
            </h2>

            <div style="background:#fdf2f0;border-left:4px solid #c0392b;padding:12px 16px;border-radius:6px;margin-bottom:20px;color:#7b241c;font-size:0.9em;">
                <strong><?php esc_html_e( 'Warning:', 'olama-stores' ); ?></strong>
                <?php esc_html_e( 'This will permanently deactivate (soft-delete) items from the selected provider. Items with stock > 0 will be skipped and reported. This action cannot be undone.', 'olama-stores' ); ?>
            </div>

            <div class="os-form-row">
                <label style="font-weight:600;"><?php esc_html_e( 'Provider', 'olama-stores' ); ?> *</label>
                <select id="os-del-provider" required style="border-color:#c0392b;">
                    <option value=""><?php esc_html_e( 'Select provider…', 'olama-stores' ); ?></option>
                </select>
            </div>

            <div class="os-form-row">
                <label style="font-weight:600;"><?php esc_html_e( 'Filter by Model (School Custom)', 'olama-stores' ); ?></label>
                <select id="os-del-model-filter" style="border-color:#c0392b;">
                    <option value=""><?php esc_html_e( '— All Items (no model filter) —', 'olama-stores' ); ?></option>
                </select>
                <p style="margin:4px 0 0;font-size:0.82em;color:#96281b;"><?php esc_html_e( 'Leave as "All Items" to target every item from the selected provider.', 'olama-stores' ); ?></p>
            </div>

            <div id="os-del-provider-preview" style="display:none;margin:4px 0 16px;padding:10px 14px;background:#fff8f7;border:1px solid #e6b0aa;border-radius:6px;font-size:0.9em;color:#7b241c;">
                <span class="dashicons dashicons-info" style="vertical-align:middle;margin-right:4px;"></span>
                <span id="os-del-preview-text"></span>
            </div>

            <div class="os-form-actions" style="margin-top:24px;">
                <button type="button" class="button" id="os-delete-provider-submit"
                    style="background-color:#c0392b;border-color:#96281b;color:#fff;height:40px;font-size:1em;font-weight:600;padding:0 20px;">
                    <span class="dashicons dashicons-trash" style="font-size:16px;width:16px;height:16px;vertical-align:middle;margin-right:4px;"></span>
                    <?php esc_html_e( 'Delete Items', 'olama-stores' ); ?>
                </button>
                <button type="button" class="button os-modal-close"><?php esc_html_e( 'Cancel', 'olama-stores' ); ?></button>
            </div>
        </div>
    </div>

</div>
<script>
(function($){
    var currentPage = 1, perPage = 20;
    var currentOrderby = 'name', currentOrder = 'ASC';
    var categories = [], units = [];

    // Load categories & units
    Promise.all([
        wp.apiFetch({ path: '/olama-stores/v1/categories' }),
        wp.apiFetch({ path: '/olama-stores/v1/units' })
    ]).then(function(results){
        categories = results[0]; units = results[1];
        var catOpts = '<option value=""><?php esc_html_e("All","olama-stores");?></option>';
        categories.forEach(function(c){ catOpts += '<option value="'+c.id+'">'+c.name+'</option>'; });
        $('#os-filter-category, #os-item-category').html(catOpts);

        var catOptsFull = '<option value=""></option>';
        categories.forEach(function(c){ catOptsFull += '<option value="'+c.id+'">'+c.name+'</option>'; });
        
        var provPromise = wp.apiFetch({ path: '/olama-stores/v1/providers' });

        Promise.all([provPromise]).then(function(values){
            var providers = values[0];
            var provOpts = '<option value=""><?php esc_html_e("No Provider","olama-stores");?></option>';
            providers.forEach(function(p){ provOpts += '<option value="'+p.id+'">'+p.company_name+'</option>'; });
            $('#os-item-provider, #os-custom-provider, #os-books-provider').html(provOpts);
        });

        wp.apiFetch({ path: '/olama-stores/v1/custom-models' }).then(function(models){
            var html = '<option value=""><?php esc_html_e("Select Model","olama-stores");?></option>';
            models.forEach(function(m){ html += '<option value="'+m.id+'">'+m.name+'</option>'; });
            $('#os-custom-model').html(html);
        });

        wp.apiFetch({ path: '/olama-stores/v1/fabrics' }).then(function(fabrics){
            var html = '<option value=""><?php esc_html_e("Select Fabric","olama-stores");?></option>';
            fabrics.forEach(function(f){ html += '<option value="'+f.name+'">'+f.name+'</option>'; });
            $('#os-custom-fabric').html(html);
        });

        wp.apiFetch({ path: '/olama-stores/v1/colors' }).then(function(colors){
            var html = '<option value=""><?php esc_html_e("Select Color","olama-stores");?></option>';
            colors.forEach(function(c){ html += '<option value="'+c.name+'">'+c.name+'</option>'; });
            $('#os-custom-color').html(html);
        });

        wp.apiFetch({ path: '/olama-stores/v1/sizes' }).then(function(sizes){
            var html = '<option value=""><?php esc_html_e("Select Size","olama-stores");?></option>';
            sizes.forEach(function(s){ html += '<option value="'+s.name+'">'+s.name+'</option>'; });
            $('#os-custom-size').html(html);
        });

        var unitOpts = '<option value=""></option>';
        units.forEach(function(u){ unitOpts += '<option value="'+u.id+'">'+u.name+' ('+u.symbol+')</option>'; });
        $('#os-item-unit, #os-custom-unit').html(unitOpts);
        $('#os-custom-category, #os-books-category').html(catOptsFull);

        loadItems();
    });

    function loadItems(search, categoryId, page){
        currentPage = page || 1;
        var params = '?academic_year_id=' + (olamaStores.activeYearId || '')
            + '&per_page=' + perPage
            + '&page='     + currentPage
            + '&orderby='  + encodeURIComponent(currentOrderby)
            + '&order='    + encodeURIComponent(currentOrder);
        if(search)     params += '&search='      + encodeURIComponent(search);
        if(categoryId) params += '&category_id=' + categoryId;
        
        $('#os-items-table-wrap').html('<span class="os-loading"><?php esc_html_e("Loading…","olama-stores");?></span>');
        
        // Use jQuery ajax to get headers if needed, but apiFetch with full response is better
        // Actually wp.apiFetch returns only the body by default. To get headers, we use { parse: false }
        wp.apiFetch({ path: '/olama-stores/v1/items' + params, parse: false }).then(function(response){
            var total = response.headers.get('X-WP-Total');
            var totalPages = response.headers.get('X-WP-TotalPages');
            response.json().then(function(items){
                if (!Array.isArray(items)) {
                    console.error("API returned non-array:", items);
                    if (items.message) {
                        $('#os-items-table-wrap').html('<div class="notice notice-error"><p>' + items.message + '</p></div>');
                    } else {
                        $('#os-items-table-wrap').html('<p><?php esc_html_e("Error loading items.","olama-stores");?></p>');
                    }
                    return;
                }
                renderItems(items);
                renderPagination(total, totalPages);
            }).catch(function(e){
                console.error("JSON parse error:", e);
                $('#os-items-table-wrap').html('<p>Error parsing response.</p>');
            });
        }).catch(function(e){
            console.error("Fetch error:", e);
            $('#os-items-table-wrap').html('<p>Network error.</p>');
        });
    }

    function renderItems(items){
        if(!items.length){ $('#os-items-table-wrap').html('<p><?php esc_html_e("No items found.","olama-stores");?></p>'); return; }

        var getHdrCls = function(col) {
            var cls = 'sortable';
            if (currentOrderby === col) cls += ' sorted ' + currentOrder.toLowerCase();
            return cls;
        };

        var html = '<table class="wp-list-table widefat striped"><thead><tr>'
            + '<th class="'+getHdrCls('sku')+'" data-orderby="sku"><?php esc_html_e("SKU","olama-stores");?></th>'
            + '<th class="'+getHdrCls('name')+'" data-orderby="name"><?php esc_html_e("Name","olama-stores");?></th>'
            + '<th class="'+getHdrCls('category_name')+'" data-orderby="category_name"><?php esc_html_e("Category","olama-stores");?></th>'
            + '<th class="'+getHdrCls('unit_name')+'" data-orderby="unit_name"><?php esc_html_e("Unit","olama-stores");?></th>'
            + '<th class="'+getHdrCls('unit_price')+'" data-orderby="unit_price"><?php esc_html_e("Price","olama-stores");?></th>'
            + '<th class="'+getHdrCls('provider_name')+'" data-orderby="provider_name"><?php esc_html_e("Provider","olama-stores");?></th>'
            + '<th class="'+getHdrCls('min_stock_level')+'" data-orderby="min_stock_level"><?php esc_html_e("Min Stock","olama-stores");?></th>'
            + '<th><?php esc_html_e("Actions","olama-stores");?></th>'
            + '</tr></thead><tbody>';
        items.forEach(function(item){
            html += '<tr id="os-item-row-'+item.id+'">'
                + '<td><code>'+item.sku+'</code></td>'
                + '<td><strong>'+item.name+'</strong>'+(item.name_ar?'<br><small dir="rtl">'+item.name_ar+'</small>':'')+
                  '</td>'
                + '<td>'+(item.category_name||'—')+'</td>'
                + '<td>'+(item.unit_symbol?item.unit_name+' ('+item.unit_symbol+')':item.unit_name||'—')+'</td>'
                + '<td>'+(parseFloat(item.unit_price).toFixed(2))+'</td>'
                + '<td>'+(item.provider_name||'—')+'</td>'
                + '<td>'+item.min_stock_level+'</td>'
                + '<td>';
            if(olamaStores.caps.manage_items){
                html += '<a href="#" class="os-edit-item button button-small" data-id="'+item.id+'"><?php esc_html_e("Edit","olama-stores");?></a> '
                     +  '<a href="#" class="os-duplicate-item button button-small" data-id="'+item.id+'"><?php esc_html_e("Duplicate","olama-stores");?></a> '
                     +  '<a href="#" class="os-delete-item button button-small button-link-delete" data-id="'+item.id+'"><?php esc_html_e("Delete","olama-stores");?></a>';
            }
            html += '</td></tr>';
        });
        html += '</tbody></table>';
        html += '<div id="os-items-pagination"></div>';
        $('#os-items-table-wrap').html(html);
    }

    function renderPagination(total, totalPages){
        total      = parseInt(total)      || 0;
        totalPages = parseInt(totalPages) || 1;

        var startEntry = (currentPage - 1) * perPage + 1;
        var endEntry   = Math.min(currentPage * perPage, total);
        if (total === 0) { startEntry = 0; endEntry = 0; }

        var html = '<div class="os-pagination-container">';

        // Left: per-page selector
        html += '<div class="os-per-page-select-wrap">'
            + '<span><?php esc_html_e("Show","olama-stores");?></span>'
            + '<select id="os-items-per-page-select">'
            + '<option value="10"'+(perPage===10?' selected':'')+'>10</option>'
            + '<option value="20"'+(perPage===20?' selected':'')+'>20</option>'
            + '<option value="50"'+(perPage===50?' selected':'')+'>50</option>'
            + '<option value="100"'+(perPage===100?' selected':'')+'>100</option>'
            + '</select>'
            + '<span><?php esc_html_e("entries","olama-stores");?></span>'
            + '</div>';

        // Centre: summary
        html += '<div class="os-pagination-info">'
            + '<?php esc_html_e("Showing","olama-stores");?> ' + startEntry
            + ' <?php esc_html_e("to","olama-stores");?> '     + endEntry
            + ' <?php esc_html_e("of","olama-stores");?> '     + total
            + ' <?php esc_html_e("entries","olama-stores");?>'
            + '</div>';

        // Right: page buttons
        html += '<div class="os-pagination-controls">';

        var prevDisabled = currentPage === 1 ? 'disabled' : '';
        html += '<button type="button" class="os-pagination-btn os-items-pag-btn" id="os-items-pag-prev" '+prevDisabled+'>&laquo;</button>';

        // 5-page sliding window
        var maxButtons = 5;
        var startPage  = 1;
        var endPage    = totalPages;

        if (totalPages > maxButtons) {
            var half = Math.floor(maxButtons / 2);
            startPage = currentPage - half;
            endPage   = currentPage + half;
            if (startPage < 1)           { endPage = maxButtons;             startPage = 1; }
            else if (endPage > totalPages){ startPage = totalPages - maxButtons + 1; endPage = totalPages; }
        }

        if (startPage > 1) {
            html += '<button type="button" class="os-pagination-btn os-items-pag-btn" data-page="1">1</button>';
            if (startPage > 2) { html += '<span style="padding:0 4px;color:var(--os-text-muted)">...</span>'; }
        }

        for (var p = startPage; p <= endPage; p++) {
            var actCls = currentPage === p ? ' active' : '';
            html += '<button type="button" class="os-pagination-btn os-items-pag-btn'+actCls+'" data-page="'+p+'">'+p+'</button>';
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) { html += '<span style="padding:0 4px;color:var(--os-text-muted)">...</span>'; }
            html += '<button type="button" class="os-pagination-btn os-items-pag-btn" data-page="'+totalPages+'">'+totalPages+'</button>';
        }

        var nextDisabled = currentPage >= totalPages ? 'disabled' : '';
        html += '<button type="button" class="os-pagination-btn os-items-pag-btn" id="os-items-pag-next" '+nextDisabled+'>&raquo;</button>';

        html += '</div></div>'; // close controls + container
        $('#os-items-pagination').html(html);
    }

    // Numbered page click
    $(document).on('click', '#os-items-table-wrap .os-items-pag-btn[data-page]', function(){
        loadItems($('#os-search-items').val(), $('#os-filter-category').val(), parseInt($(this).data('page')));
    });

    // Prev / Next clicks
    $(document).on('click', '#os-items-table-wrap #os-items-pag-prev', function(){
        if (currentPage > 1) loadItems($('#os-search-items').val(), $('#os-filter-category').val(), currentPage - 1);
    });
    $(document).on('click', '#os-items-table-wrap #os-items-pag-next', function(){
        loadItems($('#os-search-items').val(), $('#os-filter-category').val(), currentPage + 1);
    });

    // Per-page change
    $(document).on('change', '#os-items-table-wrap #os-items-per-page-select', function(){
        perPage = parseInt($(this).val());
        loadItems($('#os-search-items').val(), $('#os-filter-category').val(), 1);
    });

    // Sort by column header click
    $(document).on('click', '#os-items-table-wrap th.sortable', function() {
        var col = $(this).data('orderby');
        if (currentOrderby === col) {
            currentOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
        } else {
            currentOrderby = col;
            currentOrder = 'ASC';
        }
        loadItems($('#os-search-items').val(), $('#os-filter-category').val(), 1);
    });

    // Filters
    var searchTimer;
    $('#os-search-items').on('input', function(){ clearTimeout(searchTimer); var s=$(this).val(); searchTimer=setTimeout(function(){ loadItems(s,$('#os-filter-category').val(), 1); },400); });
    $('#os-filter-category').on('change', function(){ loadItems($('#os-search-items').val(),$(this).val(), 1); });

    // Open modal for add
    $('#os-btn-add-item').on('click', function(e){ e.preventDefault(); openModal(null); });

    // Open modal for edit
    $(document).on('click', '.os-edit-item', function(e){
        e.preventDefault();
        wp.apiFetch({ path: '/olama-stores/v1/items/' + $(this).data('id') }).then(function(item){ 
            if (item.specifications && item.specifications.model_id) {
                openCustomModal(item);
            } else {
                openModal(item); 
            }
        });
    });

    // Duplicate item
    $(document).on('click', '.os-duplicate-item', function(e){
        e.preventDefault();
        var originalId = $(this).data('id');
        $(this).text('...'); // Loading state
        wp.apiFetch({ path: '/olama-stores/v1/items/' + originalId }).then(function(item){ 
            var payload = {
                sku: '',
                name: item.name + ' - <?php esc_html_e("Copy","olama-stores");?>',
                name_ar: item.name_ar ? item.name_ar + ' - <?php esc_html_e("Copy","olama-stores");?>' : '',
                category_id: item.category_id,
                unit_id: item.unit_id,
                barcode: '',
                unit_price: item.unit_price,
                provider_id: item.provider_id,
                min_stock_level: item.min_stock_level,
                description: item.description,
                specifications: item.specifications,
                academic_year_id: olamaStores.activeYearId
            };
            wp.apiFetch({ path: '/olama-stores/v1/items', method: 'POST', data: payload }).then(function(){
                loadItems($('#os-search-items').val(), $('#os-filter-category').val(), currentPage);
            }).catch(function(err){
                alert(err.message || 'Error duplicating item');
                loadItems($('#os-search-items').val(), $('#os-filter-category').val(), currentPage);
            });
        });
    });

    // Delete
    $(document).on('click', '.os-delete-item', function(e){
        e.preventDefault();
        if(!confirm('<?php esc_html_e("Delete this item?","olama-stores");?>')) return;
        var id=$(this).data('id');
        wp.apiFetch({ path:'/olama-stores/v1/items/'+id, method:'DELETE' }).then(function(){ $('#os-item-row-'+id).remove(); });
    });

    function openModal(item){
        $('#os-item-id').val(item?item.id:'');
        $('#os-item-modal-title').text(item && item.id ? '<?php esc_html_e("Edit Item","olama-stores");?>' : '<?php esc_html_e("Add Item","olama-stores");?>');
        $('#os-item-sku').val(item?item.sku:'');
        $('#os-item-name').val(item?item.name:'');
        $('#os-item-name-ar').val(item?item.name_ar:'');
        $('#os-item-category').val(item?item.category_id:'');
        $('#os-item-unit').val(item?item.unit_id:'');
        $('#os-item-barcode').val(item?item.barcode:'');
        $('#os-item-price').val(item?item.unit_price:0);
        $('#os-item-provider').val(item && item.provider_id ? item.provider_id : '');
        $('#os-item-min-stock').val(item?item.min_stock_level:0);
        $('#os-item-description').val(item?item.description:'');
        $('#os-item-modal').show();
    }

    // Custom Item Modal
    $('#os-btn-add-custom').on('click', function(e){
        e.preventDefault();
        openCustomModal(null);
    });

    function openCustomModal(item){
        $('#os-custom-id').val(item ? item.id : '');
        $('#os-custom-modal-title').text(item && item.id ? '<?php esc_html_e("Edit School Custom Item","olama-stores");?>' : '<?php esc_html_e("Add School Custom Item","olama-stores");?>');
        $('#os-custom-sku').val(item ? item.sku : '');
        $('#os-custom-name').val(item ? item.name : '');
        $('#os-custom-price').val(item ? item.unit_price : 0);
        
        if (item && item.specifications) {
            $('#os-custom-model').val(item.specifications.model_id || '');
            $('#os-custom-color').val(item.specifications.color || '');
            $('#os-custom-size').val(item.specifications.size || '');
            $('#os-custom-fabric').val(item.specifications.fabric || '');
        } else {
            $('#os-custom-model, #os-custom-color, #os-custom-size, #os-custom-fabric').val('');
        }
        
        $('#os-custom-provider').val(item ? item.provider_id : '');
        $('#os-custom-category').val(item ? item.category_id : '');
        $('#os-custom-unit').val(item ? item.unit_id : '');
        
        generateCustomName();
        $('#os-custom-modal').show();
    }

    function generateCustomName(){
        var model = $('#os-custom-model option:selected').text();
        if($('#os-custom-model').val() === '') model = '';
        
        var size = $('#os-custom-size').val();
        var color = $('#os-custom-color option:selected').text();
        var fabric = $('#os-custom-fabric option:selected').text();
        var provider = $('#os-custom-provider option:selected').text();
        if($('#os-custom-provider').val() === '') provider = '';

        var parts = [];
        if(model) parts.push(model);
        if(size)  parts.push(size);
        if(color) parts.push(color);
        if(fabric) parts.push(fabric);
        if(provider && provider !== '<?php esc_html_e("No Provider","olama-stores");?>') parts.push(provider);

        $('#os-custom-name').val(parts.join(' - '));
    }

    $(document).on('change', '#os-custom-model, #os-custom-size, #os-custom-color, #os-custom-fabric, #os-custom-provider', function(){
        generateCustomName();
    });

    $('#os-custom-form').on('submit', function(e){
        e.preventDefault();
        var id = $('#os-custom-id').val();
        var payload = {
            sku: $('#os-custom-sku').val(),
            name: $('#os-custom-name').val(),
            category_id: $('#os-custom-category').val(),
            unit_id: $('#os-custom-unit').val(),
            unit_price: $('#os-custom-price').val(),
            provider_id: $('#os-custom-provider').val(),
            specifications: {
                model_id: $('#os-custom-model').val(),
                color: $('#os-custom-color').val(),
                size: $('#os-custom-size').val(),
                fabric: $('#os-custom-fabric').val()
            },
            academic_year_id: olamaStores.activeYearId
        };
        var method = id ? 'PUT' : 'POST';
        var path   = id ? '/olama-stores/v1/items/' + id : '/olama-stores/v1/items';
        wp.apiFetch({ path: path, method: method, data: payload }).then(function(){
            $('#os-custom-modal').hide();
            loadItems($('#os-search-items').val(), $('#os-filter-category').val(), id ? currentPage : 1);
        }).catch(function(e){ alert(e.message||'Error saving item'); });
    });

    // Grade Books Modal
    $('#os-btn-add-books').on('click', function(e){
        e.preventDefault();
        $('#os-books-form')[0].reset();
        $('#os-books-list').html('<div class="os-book-item os-flex" style="margin-bottom: 5px;"><input type="text" class="os-book-name" placeholder="Book Name" style="flex: 1;"><button type="button" class="button os-btn-remove-book" style="margin-left: 5px;">&times;</button></div>');
        
        // Load grades if not loaded
        wp.apiFetch({ path: '/olama-stores/v1/grades' }).then(function(grades){
            var html = '<option value="">Select Grade</option>';
            grades.forEach(function(g){ html += '<option value="'+g.id+'">'+g.grade_name+'</option>'; });
            $('#os-books-grade').html(html);
            $('#os-books-modal').show();
        });
    });

    $('#os-books-grade').on('change', function(){
        var gradeId = $(this).val();
        if(!gradeId) { $('#os-books-subject').html('<option value="">Select Subject</option>'); return; }
        wp.apiFetch({ path: '/olama-stores/v1/subjects/' + gradeId }).then(function(subjects){
            var html = '<option value="">Select Subject</option>';
            subjects.forEach(function(s){ html += '<option value="'+s.id+'">'+s.subject_name+'</option>'; });
            $('#os-books-subject').html(html);
        });
    });

    $('#os-btn-add-book-row').on('click', function(){
        $('#os-books-list').append('<div class="os-book-item os-flex" style="margin-bottom: 5px;"><input type="text" class="os-book-name" placeholder="Book Name" style="flex: 1;"><button type="button" class="button os-btn-remove-book" style="margin-left: 5px;">&times;</button></div>');
    });

    $(document).on('click', '.os-btn-remove-book', function(){
        if($('#os-books-list .os-book-item').length > 1) $(this).closest('.os-book-item').remove();
    });

    $('#os-books-form').on('submit', function(e){
        e.preventDefault();
        var books = [];
        $('#os-books-list .os-book-name').each(function(){
            if($(this).val()) books.push($(this).val());
        });
        
        var gradeName = $('#os-books-grade option:selected').text();
        var subjectName = $('#os-books-subject option:selected').text();

        var payload = {
            name: gradeName + ' - ' + subjectName + ' (Books Package)',
            category_id: $('#os-books-category').val(),
            unit_price: $('#os-books-price').val(),
            provider_id: $('#os-books-provider').val(),
            specifications: {
                type: 'grade_books',
                grade_id: $('#os-books-grade').val(),
                subject_id: $('#os-books-subject').val(),
                books: books
            },
            academic_year_id: olamaStores.activeYearId
        };
        wp.apiFetch({ path: '/olama-stores/v1/items', method: 'POST', data: payload }).then(function(){
            $('#os-books-modal').hide();
            loadItems($('#os-search-items').val(), $('#os-filter-category').val(), 1);
        }).catch(function(e){ alert(e.message||'Error saving books'); });
    });

    // Form submit
    $('#os-item-form').on('submit', function(e){
        e.preventDefault();
        var id = $('#os-item-id').val();
        var payload = {
            sku: $('#os-item-sku').val(),
            name: $('#os-item-name').val(),
            name_ar: $('#os-item-name-ar').val(),
            category_id: $('#os-item-category').val(),
            unit_id: $('#os-item-unit').val(),
            barcode: $('#os-item-barcode').val(),
            unit_price: $('#os-item-price').val(),
            provider_id: $('#os-item-provider').val(),
            min_stock_level: $('#os-item-min-stock').val(),
            description: $('#os-item-description').val(),
            academic_year_id: olamaStores.activeYearId  // Correction #1: sends INT
        };
        var method = id ? 'PUT' : 'POST';
        var path   = id ? '/olama-stores/v1/items/'+id : '/olama-stores/v1/items';
        wp.apiFetch({ path: path, method: method, data: payload }).then(function(){
            $('#os-item-modal').hide();
            loadItems($('#os-search-items').val(), $('#os-filter-category').val(), currentPage);
        }).catch(function(e){ alert(e.message||'<?php esc_html_e("Error saving item","olama-stores");?>'); });
    });



    // ── Copy Provider Items ───────────────────────────────────────────────────

    // Open copy modal and populate provider + model dropdowns
    $('#os-btn-copy-provider').on('click', function(e){
        e.preventDefault();
        $('#os-copy-from-provider').val('');
        $('#os-copy-to-provider').val('');
        $('#os-copy-model-filter').val('');
        $('#os-copy-provider-preview').hide();
        $('#os-copy-preview-text').text('');

        Promise.all([
            wp.apiFetch({ path: '/olama-stores/v1/providers' }),
            wp.apiFetch({ path: '/olama-stores/v1/custom-models' })
        ]).then(function(results){
            var providers = results[0];
            var models    = results[1];

            var opts = '<option value=""><?php esc_html_e( 'Select source provider…', 'olama-stores' ); ?></option>';
            providers.forEach(function(p){ opts += '<option value="'+p.id+'">'+p.company_name+'</option>'; });
            $('#os-copy-from-provider').html(opts);

            var optsTo = '<option value=""><?php esc_html_e( 'Select target provider…', 'olama-stores' ); ?></option>';
            providers.forEach(function(p){ optsTo += '<option value="'+p.id+'">'+p.company_name+'</option>'; });
            $('#os-copy-to-provider').html(optsTo);

            var modelOpts = '<option value=""><?php esc_html_e( '— All Items (no model filter) —', 'olama-stores' ); ?></option>';
            models.forEach(function(m){ modelOpts += '<option value="'+m.id+'">'+m.name+'</option>'; });
            $('#os-copy-model-filter').html(modelOpts);

            $('#os-copy-provider-modal').show();
        });
    });

    // Helper: refresh the preview count based on current from-provider + model filter
    function refreshCopyPreview() {
        var fromId  = $('#os-copy-from-provider').val();
        var modelId = $('#os-copy-model-filter').val();
        if (!fromId) {
            $('#os-copy-provider-preview').hide();
            return;
        }
        $('#os-copy-preview-text').text('<?php esc_html_e( 'Loading…', 'olama-stores' ); ?>');
        $('#os-copy-provider-preview').show();

        var path = '/olama-stores/v1/items?provider_id_exact=' + fromId + '&per_page=1&page=1';
        if (modelId) { path += '&model_id=' + modelId; }

        wp.apiFetch({ path: path, parse: false })
            .then(function(response){
                var total    = parseInt(response.headers.get('X-WP-Total')) || 0;
                var fromName = $('#os-copy-from-provider option:selected').text();
                var modelName= modelId ? (' — ' + $('#os-copy-model-filter option:selected').text()) : '';
                $('#os-copy-preview-text').text(
                    total + ' <?php esc_html_e( 'item(s) will be copied from', 'olama-stores' ); ?> ' + fromName + modelName
                );
            }).catch(function(){
                $('#os-copy-preview-text').text('<?php esc_html_e( 'Could not load preview.', 'olama-stores' ); ?>');
            });
    }

    // Re-run preview whenever source provider OR model filter changes
    $(document).on('change', '#os-copy-from-provider, #os-copy-model-filter', function(){
        refreshCopyPreview();
    });

    // Submit copy
    $('#os-copy-provider-submit').on('click', function(){
        var fromId  = $('#os-copy-from-provider').val();
        var toId    = $('#os-copy-to-provider').val();
        var modelId = $('#os-copy-model-filter').val();
        var modelName = modelId ? $('#os-copy-model-filter option:selected').text() : '';

        if (!fromId || !toId) {
            alert('<?php esc_html_e( 'Please select both a source and a target provider.', 'olama-stores' ); ?>');
            return;
        }
        if (fromId === toId) {
            alert('<?php esc_html_e( 'Source and target providers must be different.', 'olama-stores' ); ?>');
            return;
        }

        var scope = modelName
            ? '<?php esc_html_e( 'model', 'olama-stores' ); ?> "' + modelName + '"'
            : '<?php esc_html_e( 'all items', 'olama-stores' ); ?>';
        if (!confirm('<?php esc_html_e( 'This will copy', 'olama-stores' ); ?> ' + scope + ' <?php esc_html_e( 'from the source provider to the target. Continue?', 'olama-stores' ); ?>')) {
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php esc_html_e( 'Copying…', 'olama-stores' ); ?>');

        var payload = { from_provider_id: parseInt(fromId), to_provider_id: parseInt(toId) };
        if (modelId) { payload.model_id = parseInt(modelId); }

        wp.apiFetch({
            path:   '/olama-stores/v1/items/copy-provider',
            method: 'POST',
            data:   payload
        }).then(function(result){
            $('#os-copy-provider-modal').hide();
            var msg = result.copied + ' <?php esc_html_e( 'item(s) copied successfully.', 'olama-stores' ); ?>';
            if (result.errors && result.errors.length) {
                msg += '\n<?php esc_html_e( 'Errors:', 'olama-stores' ); ?> ' + result.errors.join(', ');
            }
            alert(msg);
            loadItems($('#os-search-items').val(), $('#os-filter-category').val(), 1);
        }).catch(function(err){
            alert(err.message || '<?php esc_html_e( 'Error copying items.', 'olama-stores' ); ?>');
        }).finally(function(){
            $btn.prop('disabled', false).html(
                '<span class="dashicons dashicons-controls-repeat" style="font-size:16px;width:16px;height:16px;vertical-align:middle;margin-right:4px;"></span><?php esc_html_e( 'Copy Items', 'olama-stores' ); ?>'
            );
        });
    });

    // ── Delete Provider Items ─────────────────────────────────────────────────

    // Open delete modal and populate provider + model dropdowns
    $('#os-btn-delete-provider').on('click', function(e){
        e.preventDefault();
        $('#os-del-provider').val('');
        $('#os-del-model-filter').val('');
        $('#os-del-provider-preview').hide();
        $('#os-del-preview-text').text('');

        Promise.all([
            wp.apiFetch({ path: '/olama-stores/v1/providers' }),
            wp.apiFetch({ path: '/olama-stores/v1/custom-models' })
        ]).then(function(results){
            var providers = results[0];
            var models    = results[1];

            var opts = '<option value=""><?php esc_html_e( 'Select provider\u2026', 'olama-stores' ); ?></option>';
            providers.forEach(function(p){ opts += '<option value="'+p.id+'">'+p.company_name+'</option>'; });
            $('#os-del-provider').html(opts);

            var modelOpts = '<option value=""><?php esc_html_e( '\u2014 All Items (no model filter) \u2014', 'olama-stores' ); ?></option>';
            models.forEach(function(m){ modelOpts += '<option value="'+m.id+'">'+m.name+'</option>'; });
            $('#os-del-model-filter').html(modelOpts);

            $('#os-delete-provider-modal').show();
        });
    });

    // Helper: refresh the delete preview count
    function refreshDeletePreview() {
        var providerId = $('#os-del-provider').val();
        var modelId    = $('#os-del-model-filter').val();
        if (!providerId) {
            $('#os-del-provider-preview').hide();
            return;
        }
        $('#os-del-preview-text').text('<?php esc_html_e( 'Loading\u2026', 'olama-stores' ); ?>');
        $('#os-del-provider-preview').show();

        var path = '/olama-stores/v1/items?provider_id_exact=' + providerId + '&per_page=1&page=1';
        if (modelId) { path += '&model_id=' + modelId; }

        wp.apiFetch({ path: path, parse: false })
            .then(function(response){
                var total      = parseInt(response.headers.get('X-WP-Total')) || 0;
                var modelName  = modelId ? (' \u2014 ' + $('#os-del-model-filter option:selected').text()) : '';
                var provName   = $('#os-del-provider option:selected').text();
                $('#os-del-preview-text').html(
                    '<strong>' + total + '</strong> <?php esc_html_e( 'item(s) found for', 'olama-stores' ); ?> ' + provName + modelName
                    + '<br><small style="color:#96281b;"><?php esc_html_e( 'Items with stock > 0 will be skipped automatically.', 'olama-stores' ); ?></small>'
                );
            }).catch(function(){
                $('#os-del-preview-text').text('<?php esc_html_e( 'Could not load preview.', 'olama-stores' ); ?>');
            });
    }

    $(document).on('change', '#os-del-provider, #os-del-model-filter', function(){
        refreshDeletePreview();
    });

    // Submit delete
    $('#os-delete-provider-submit').on('click', function(){
        var providerId = $('#os-del-provider').val();
        var modelId    = $('#os-del-model-filter').val();
        var modelName  = modelId ? $('#os-del-model-filter option:selected').text() : '';
        var provName   = $('#os-del-provider option:selected').text();

        if (!providerId) {
            alert('<?php esc_html_e( 'Please select a provider.', 'olama-stores' ); ?>');
            return;
        }

        var scope = modelName
            ? '<?php esc_html_e( 'model', 'olama-stores' ); ?> "' + modelName + '" <?php esc_html_e( 'from', 'olama-stores' ); ?> ' + provName
            : '<?php esc_html_e( 'ALL items from', 'olama-stores' ); ?> ' + provName;

        // Double confirm for destructive action
        if (!confirm('<?php esc_html_e( 'Are you sure you want to delete', 'olama-stores' ); ?> ' + scope + '?\n\n<?php esc_html_e( 'Items with stock > 0 will be skipped. This cannot be undone.', 'olama-stores' ); ?>')) {
            return;
        }
        if (!confirm('<?php esc_html_e( 'FINAL CONFIRMATION: Proceed with deletion?', 'olama-stores' ); ?>')) {
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php esc_html_e( 'Deleting\u2026', 'olama-stores' ); ?>');

        var payload = { provider_id: parseInt(providerId) };
        if (modelId) { payload.model_id = parseInt(modelId); }

        wp.apiFetch({
            path:   '/olama-stores/v1/items/delete-by-provider',
            method: 'POST',
            data:   payload
        }).then(function(result){
            $('#os-delete-provider-modal').hide();
            var msg = result.deleted + ' <?php esc_html_e( 'item(s) deleted successfully.', 'olama-stores' ); ?>';
            if (result.skipped && result.skipped.length) {
                msg += '\n\n<?php esc_html_e( 'Skipped (stock > 0):', 'olama-stores' ); ?>\n' + result.skipped.join('\n');
            }
            alert(msg);
            loadItems($('#os-search-items').val(), $('#os-filter-category').val(), 1);
        }).catch(function(err){
            alert(err.message || '<?php esc_html_e( 'Error deleting items.', 'olama-stores' ); ?>');
        }).finally(function(){
            $btn.prop('disabled', false).html(
                '<span class="dashicons dashicons-trash" style="font-size:16px;width:16px;height:16px;vertical-align:middle;margin-right:4px;"></span><?php esc_html_e( 'Delete Items', 'olama-stores' ); ?>'
            );
        });
    });

    // Close modal
    $('.os-modal-close, .os-modal').on('click', function(e){
        if(e.target===this || $(e.target).hasClass('os-modal-close')) $(this).closest('.os-modal').hide();
    });


    // Export — uses dedicated items endpoint with current filters, no per-page limit
    $('#os-btn-export-items').on('click', function(){
        var search     = encodeURIComponent( $('#os-search-items').val() );
        var categoryId = encodeURIComponent( $('#os-filter-category').val() );
        var url = olamaStores.apiRoot + '/reports/export/items?_wpnonce=' + olamaStores.nonce;
        if ( search )     url += '&search='      + search;
        if ( categoryId ) url += '&category_id=' + categoryId;
        window.location = url;
    });
})(jQuery);
</script>

<style>
/* Enhanced items table headers */
#os-items-page .os-wrap table.wp-list-table thead th,
#os-items-table-wrap table.wp-list-table thead th {
    font-size: 0.92rem !important;
    text-transform: none !important;
    font-weight: 600 !important;
    letter-spacing: 0.01em !important;
    padding: 14px 12px !important;
    color: #ffffff !important;
    background: var(--os-primary) !important;
}

/* Footer pagination bar */
#os-items-table-wrap .os-pagination-container {
    background: #f1f5f9 !important;
    border: 1px solid #cbd5e1 !important;
    border-top: none !important;
    margin-top: 0 !important;
    border-radius: 0 0 var(--os-radius) var(--os-radius) !important;
    padding: 14px 20px !important;
}

/* Per-page dropdown */
#os-items-table-wrap .os-per-page-select-wrap select {
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

#os-items-table-wrap .os-per-page-select-wrap,
#os-items-table-wrap .os-pagination-info {
    font-weight: 500 !important;
    color: #334155 !important;
    font-size: 0.875rem !important;
}

#os-items-table-wrap .os-pagination-controls {
    gap: 6px !important;
}

#os-items-table-wrap .os-pagination-btn {
    min-width: 34px !important;
    height: 34px !important;
    border-radius: 6px !important;
    font-weight: 600 !important;
    border-color: #cbd5e1 !important;
    color: #475569 !important;
    background-color: #ffffff !important;
    transition: all 0.15s ease-in-out !important;
}

#os-items-table-wrap .os-pagination-btn:hover:not(:disabled) {
    background-color: #e2e8f0 !important;
    border-color: #94a3b8 !important;
    color: #0f172a !important;
}

#os-items-table-wrap .os-pagination-btn.active {
    background-color: var(--os-primary) !important;
    border-color: var(--os-primary) !important;
    color: #ffffff !important;
}
</style>
