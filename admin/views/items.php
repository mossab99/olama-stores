<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap os-wrap" id="os-items-page">
    <h1 class="os-page-title">
        <span class="dashicons dashicons-products"></span>
        <?php esc_html_e( 'Item Registry', 'olama-stores' ); ?>
        <?php if ( OS_Roles::can( 'os_manage_items' ) ): ?>
            <a href="#" id="os-btn-add-item" class="page-title-action"><?php esc_html_e( 'Add Item', 'olama-stores' ); ?></a>
            <a href="#" id="os-btn-add-custom" class="page-title-action" style="background-color: #e74c3c; border-color: #c0392b; color: #fff;"><?php esc_html_e( 'Add School Custom', 'olama-stores' ); ?></a>
            <a href="#" id="os-btn-add-books" class="page-title-action" style="background-color: #27ae60; border-color: #219150; color: #fff;"><?php esc_html_e( 'Add Grade Books', 'olama-stores' ); ?></a>
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


</div>
<script>
(function($){
    var currentPage = 1, perPage = 20;
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
        var params = '?academic_year_id=' + (olamaStores.activeYearId || '') + '&per_page=' + perPage + '&page=' + currentPage;
        if(search)     params += '&search=' + encodeURIComponent(search);
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
        var html = '<table class="wp-list-table widefat striped"><thead><tr>'
            + '<th>SKU</th><th><?php esc_html_e("Name","olama-stores");?></th>'
            + '<th><?php esc_html_e("Category","olama-stores");?></th>'
            + '<th><?php esc_html_e("Unit","olama-stores");?></th>'
            + '<th><?php esc_html_e("Price","olama-stores");?></th>'
            + '<th><?php esc_html_e("Provider","olama-stores");?></th>'
            + '<th><?php esc_html_e("Min Stock","olama-stores");?></th>'
            + '<th><?php esc_html_e("Actions","olama-stores");?></th>'
            + '</tr></thead><tbody>';
        items.forEach(function(item){
            html += '<tr id="os-item-row-'+item.id+'">'
                + '<td><code>'+item.sku+'</code></td>'
                + '<td><strong>'+item.name+'</strong>'+(item.name_ar?'<br><small dir="rtl">'+item.name_ar+'</small>':'')+'</td>'
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
        html += '<div id="os-items-pagination" class="tablenav-pages"></div>';
        $('#os-items-table-wrap').html(html);
    }

    function renderPagination(total, totalPages){
        if(totalPages <= 1) return;
        var html = '<span class="displaying-num">' + total + ' <?php esc_html_e("items","olama-stores");?></span>';
        html += '<span class="pagination-links">';
        if(currentPage > 1) {
            html += '<a class="prev-page button" href="#" data-page="'+(currentPage-1)+'"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>';
        }
        html += '<span class="paging-input"><span class="total-pages">'+currentPage+'</span> of <span class="total-pages">'+totalPages+'</span></span>';
        if(currentPage < totalPages) {
            html += '<a class="next-page button" href="#" data-page="'+(parseInt(currentPage)+1)+'"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>';
        }
        html += '</span>';
        $('#os-items-pagination').html(html);
    }

    $(document).on('click', '#os-items-pagination a', function(e){
        e.preventDefault();
        loadItems($('#os-search-items').val(), $('#os-filter-category').val(), $(this).data('page'));
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



    // Close modal
    $('.os-modal-close, .os-modal').on('click', function(e){
        if(e.target===this || $(e.target).hasClass('os-modal-close')) $(this).closest('.os-modal').hide();
    });

    // Export
    $('#os-btn-export-items').on('click', function(){
        window.location = olamaStores.apiRoot + '/reports/export/stock?_wpnonce=' + olamaStores.nonce;
    });
})(jQuery);
</script>
