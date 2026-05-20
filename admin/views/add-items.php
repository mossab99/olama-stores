<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap os-wrap" id="os-add-items-page">
    <h1 class="os-page-title">
        <span class="dashicons dashicons-plus"></span>
        <?php esc_html_e( 'Add Items to Store', 'olama-stores' ); ?>
    </h1>

    <!-- Tabs Navigation -->
    <div class="os-tabs-nav">
        <a href="#single" class="os-tab-link active" data-tab="single">
            <span class="dashicons dashicons-insert"></span>
            <?php esc_html_e( 'Single Addition', 'olama-stores' ); ?>
        </a>
        <a href="#bulk" class="os-tab-link" data-tab="bulk">
            <span class="dashicons dashicons-database-import"></span>
            <?php esc_html_e( 'Bulk Upload (CSV)', 'olama-stores' ); ?>
        </a>
    </div>

    <!-- Tab 1: Single Addition -->
    <div id="os-tab-single" class="os-tab-content active">
        <div class="os-card">
            <form id="os-add-items-form">
                <div class="os-form-row full-width-field">
                    <label><?php esc_html_e( 'Select Item', 'olama-stores' ); ?> *</label>
                    <div class="os-input-group">
                        <input type="text" class="os-modal-item-search" data-target="#os-selected-item" placeholder="<?php esc_attr_e( 'Search by name, SKU or barcode…', 'olama-stores' ); ?>" autocomplete="off">
                        <select id="os-selected-item" required></select>
                    </div>
                </div>

                <div id="os-item-details-preview" style="display:none; margin-bottom: 25px; padding: 20px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #3498db; box-shadow: inset 0 1px 3px rgba(0,0,0,0.02);">
                    <h3 style="margin-top:0; color:#2c3e50; font-size:1.25em;" id="preview-name"></h3>
                    <p style="margin-bottom:10px; font-size:0.95em;"><strong id="preview-sku" style="background:#eef2f7; padding:3px 8px; border-radius:4px; font-family:monospace; color:#34495e;"></strong> <span style="color:#cbd5e1; margin:0 5px;">|</span> <span id="preview-category" style="color:#7f8c8d; font-weight:500;"></span></p>
                    <p style="margin-bottom:0; color: #27ae60; font-size:1.15em; font-weight:600;"><span class="dashicons dashicons-chart-bar" style="font-size:18px; width:18px; height:18px; vertical-align:middle; margin-right:4px;"></span> <?php esc_html_e( 'Current Stock:', 'olama-stores' ); ?> <strong id="preview-stock"></strong></p>
                </div>

                <div class="os-form-row full-width-field">
                    <label><?php esc_html_e( 'Warehouse', 'olama-stores' ); ?> *</label>
                    <select id="os-warehouse-id" required style="border-color: #cbd5e1; height: 42px;">
                        <option value=""><?php esc_html_e( 'Select Warehouse', 'olama-stores' ); ?></option>
                    </select>
                </div>

                <div class="os-form-row full-width-field">
                    <label><?php esc_html_e( 'Item Count (Quantity)', 'olama-stores' ); ?> *</label>
                    <input type="number" id="os-item-count" min="1" value="1" required style="font-size: 1.2em; font-weight: bold; border-color: #cbd5e1; height: 42px;">
                </div>

                <div class="os-form-row full-width-field">
                    <label><?php esc_html_e( 'Notes', 'olama-stores' ); ?></label>
                    <textarea id="os-notes" placeholder="<?php esc_attr_e( 'Optional notes about this addition...', 'olama-stores' ); ?>" style="min-height:90px; border-color: #cbd5e1;"></textarea>
                </div>

                <div class="os-form-actions" style="margin-top: 25px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                    <button type="submit" class="button button-primary button-large" id="os-btn-save-addition" style="width: 100%; height: 48px; font-size: 1.1em; border-radius: 8px; font-weight: 600;">
                        <span class="dashicons dashicons-yes" style="vertical-align: middle; margin-top: -2px;"></span>
                        <?php esc_html_e( 'Add to Store', 'olama-stores' ); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tab 2: Bulk Upload (CSV) -->
    <div id="os-tab-bulk" class="os-tab-content" style="display:none;">
        <div class="os-card">
            <div class="os-grid-2col">
                <!-- Left: File Upload & Configuration -->
                <div class="os-form-column">
                    <div class="os-form-row full-width-field">
                        <label><?php esc_html_e( '1. Select Default Warehouse', 'olama-stores' ); ?> *</label>
                        <select id="os-bulk-default-warehouse" style="border-color: #cbd5e1; height: 42px;">
                            <option value=""><?php esc_html_e( 'Select Default Warehouse', 'olama-stores' ); ?></option>
                        </select>
                        <p class="description" style="margin-top:5px; color:#64748b;"><?php esc_html_e( 'Used if the Warehouse column is empty or invalid in the CSV.', 'olama-stores' ); ?></p>
                    </div>

                    <div class="os-csv-dropzone" id="os-csv-dropzone">
                        <span class="dashicons dashicons-cloud-upload" style="font-size: 48px; width: 48px; height: 48px; color: #3498db; margin-bottom: 12px; display: inline-block;"></span>
                        <h3><?php esc_html_e( 'Drag & Drop CSV File Here', 'olama-stores' ); ?></h3>
                        <p><?php esc_html_e( 'or click to browse your computer', 'olama-stores' ); ?></p>
                        <input type="file" id="os-csv-file-input" accept=".csv" style="display: none;">
                    </div>
                    
                    <div id="os-csv-file-info" style="display: none; margin-top: 15px; padding: 15px; background: #f1f5f9; border-radius: 8px; font-weight: 600; border-left: 4px solid #3498db;">
                        <span class="dashicons dashicons-media-spreadsheet" style="vertical-align: middle; margin-right: 6px; color: #3498db;"></span>
                        <span id="os-csv-file-name" style="color: #334155;"></span>
                        <button type="button" class="button button-small button-link-delete" id="os-btn-clear-csv" style="float: right; margin-top: -2px;"><?php esc_html_e( 'Remove', 'olama-stores' ); ?></button>
                    </div>
                </div>

                <!-- Right: Instructions & Template -->
                <div class="os-form-column" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 25px;">
                    <h3 style="margin-top: 0; color: #2c3e50; font-size:1.15em;"><span class="dashicons dashicons-info" style="vertical-align: middle; margin-right: 6px; color:#3498db;"></span><?php esc_html_e( 'CSV Instructions', 'olama-stores' ); ?></h3>
                    <p style="font-size: 0.95em; line-height: 1.5; color: #475569; margin-bottom: 15px;">
                        <?php esc_html_e( 'Please format your CSV spreadsheet with these column headers:', 'olama-stores' ); ?>
                    </p>
                    <ul style="list-style-type: disc; margin-left: 20px; font-size: 0.9em; color: #475569; line-height: 1.6;">
                        <li><strong>SKU</strong> <?php esc_html_e( '(Required): The unique item code or barcode.', 'olama-stores' ); ?></li>
                        <li><strong>Quantity</strong> <?php esc_html_e( '(Required): Number of items to add.', 'olama-stores' ); ?></li>
                        <li><strong>Notes</strong> <?php esc_html_e( '(Optional): Audit note about the addition.', 'olama-stores' ); ?></li>
                        <li><strong>Warehouse</strong> <?php esc_html_e( '(Optional): The warehouse ID or Name. If blank, the Default Warehouse will be used.', 'olama-stores' ); ?></li>
                    </ul>
                    <p style="margin-top: 25px; margin-bottom: 0;">
                        <button type="button" class="button" id="os-btn-download-template" style="width: 100%; border-color: #3498db; color: #3498db; height: 42px; font-weight: 600;">
                            <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 6px;"></span>
                            <?php esc_html_e( 'Download CSV Template', 'olama-stores' ); ?>
                        </button>
                    </p>
                </div>
            </div>

            <!-- Validation Preview -->
            <div id="os-bulk-preview-wrap" style="display: none; margin-top: 35px; border-top: 1px solid #e2e8f0; padding-top: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                    <h3 style="margin: 0; color: #2c3e50; font-size:1.25em;"><?php esc_html_e( 'Import Preview & Validation', 'olama-stores' ); ?></h3>
                    <div id="os-bulk-summary-badges"></div>
                </div>
                
                <div style="max-height: 450px; overflow-y: auto; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);">
                    <table class="wp-list-table widefat striped" style="border: none; margin: 0;">
                        <thead>
                            <tr>
                                <th style="width: 60px; text-align: center; background: #f1f5f9; color: #475569 !important;">#</th>
                                <th style="background: #f1f5f9; color: #475569 !important;"><?php esc_html_e( 'SKU / Barcode', 'olama-stores' ); ?></th>
                                <th style="background: #f1f5f9; color: #475569 !important;"><?php esc_html_e( 'Resolved Item', 'olama-stores' ); ?></th>
                                <th style="background: #f1f5f9; color: #475569 !important;"><?php esc_html_e( 'Warehouse', 'olama-stores' ); ?></th>
                                <th style="width: 100px; background: #f1f5f9; color: #475569 !important;"><?php esc_html_e( 'Quantity', 'olama-stores' ); ?></th>
                                <th style="background: #f1f5f9; color: #475569 !important;"><?php esc_html_e( 'Notes', 'olama-stores' ); ?></th>
                                <th style="background: #f1f5f9; color: #475569 !important;"><?php esc_html_e( 'Status', 'olama-stores' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="os-bulk-preview-tbody"></tbody>
                    </table>
                </div>

                <div class="os-form-actions" style="margin-top: 25px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                    <button type="button" class="button button-primary button-large" id="os-btn-confirm-import" style="height: 45px; font-size: 1.1em; padding: 0 30px; font-weight: 600;">
                        <span class="dashicons dashicons-upload" style="vertical-align: middle; margin-right: 6px;"></span>
                        <?php esc_html_e( 'Import Items', 'olama-stores' ); ?>
                    </button>
                    <button type="button" class="button button-large" id="os-btn-cancel-import" style="height: 45px; font-size: 1.1em;"><?php esc_html_e( 'Cancel', 'olama-stores' ); ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Additions Table -->
    <div id="os-recent-additions" style="margin-top: 40px;">
        <h3 style="color: var(--os-primary); font-size: 1.35rem; font-weight: 600; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
            <span class="dashicons dashicons-backup" style="font-size: 1.5rem; width: 1.5rem; height: 1.5rem;"></span>
            <?php esc_html_e( 'Recent Additions', 'olama-stores' ); ?>
        </h3>
        <div id="os-additions-table-wrap">
            <span class="os-loading"><?php esc_html_e( 'Loading recent additions…', 'olama-stores' ); ?></span>
        </div>
    </div>
</div>

<script>
(function($){
    var items = [];
    var parsedRows = [];

    // Load initial data
    Promise.all([
        wp.apiFetch({ path: '/olama-stores/v1/warehouses' })
    ]).then(function(results){
        var whs = results[0];
        var whOpts = '<option value=""><?php esc_html_e("Select Warehouse","olama-stores");?></option>';
        whs.forEach(function(w){ whOpts += '<option value="'+w.id+'">'+w.name+'</option>'; });
        
        // Populate single and bulk warehouse selects
        $('#os-warehouse-id').html(whOpts);
        $('#os-bulk-default-warehouse').html('<option value=""><?php esc_html_e("Select Default Warehouse","olama-stores");?></option>' + whs.map(function(w){ return '<option value="'+w.id+'">'+w.name+'</option>'; }).join(''));
    });

    // Tab Switcher
    $('.os-tab-link').on('click', function(e) {
        e.preventDefault();
        var tabId = $(this).data('tab');
        
        $('.os-tab-link').removeClass('active');
        $(this).addClass('active');
        
        $('.os-tab-content').hide();
        $('#os-tab-' + tabId).fadeIn(200);
    });

    // Single: Item search results Hook
    $('#os-selected-item').on('os:search-results', function(e, results){
        items = results;
        if(items.length === 1) {
            $(this).val(items[0].id).trigger('change');
        }
    });

    // Single: Item changed preview
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

    // Single: Form submit
    $('#os-add-items-form').on('submit', function(e){
        e.preventDefault();
        var btn = $('#os-btn-save-addition');
        btn.prop('disabled', true).text('<?php esc_html_e("Saving...","olama-stores");?>');

        var payload = {
            item_id: parseInt($('#os-selected-item').val()),
            warehouse_id: parseInt($('#os-warehouse-id').val()),
            quantity: parseInt($('#os-item-count').val()),
            movement_type: 'opening_balance',
            notes: $('#os-notes').val(),
            academic_year_id: olamaStores.activeYearId
        };

        wp.apiFetch({ path: '/olama-stores/v1/stock/receive', method: 'POST', data: payload }).then(function(){
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

    // ── Bulk Upload Handler ───────────────────────────────────────────────
    var dropzone = $('#os-csv-dropzone');
    var fileInput = $('#os-csv-file-input');

    dropzone.on('click', function() {
        fileInput.trigger('click');
    });

    dropzone.on('dragover', function(e) {
        e.preventDefault();
        dropzone.addClass('dragover');
    });

    dropzone.on('dragleave', function(e) {
        e.preventDefault();
        dropzone.removeClass('dragover');
    });

    dropzone.on('drop', function(e) {
        e.preventDefault();
        dropzone.removeClass('dragover');
        var files = e.originalEvent.dataTransfer.files;
        if (files.length) {
            handleFile(files[0]);
        }
    });

    fileInput.on('change', function() {
        var files = this.files;
        if (files.length) {
            handleFile(files[0]);
        }
    });

    $('#os-bulk-default-warehouse').on('change', function() {
        if (parsedRows.length > 0) {
            renderPreview();
        }
    });

    function handleFile(file) {
        if (!file.name.endsWith('.csv')) {
            alert('<?php esc_html_e("Please select a valid CSV file.","olama-stores");?>');
            return;
        }

        $('#os-csv-file-name').text(file.name + ' (' + (file.size / 1024).toFixed(2) + ' KB)');
        $('#os-csv-file-info').show();
        dropzone.hide();

        var reader = new FileReader();
        reader.onload = function(e) {
            var text = e.target.result;
            processCSVData(text);
        };
        reader.readAsText(file);
    }

    function processCSVData(text) {
        var lines = parseCSV(text);
        if (lines.length < 2) {
            alert('<?php esc_html_e("The CSV file is empty or invalid.","olama-stores");?>');
            resetCSV();
            return;
        }

        // Identify headers
        var headers = lines[0].map(function(h) { return h.trim().toLowerCase(); });
        var skuIdx = headers.indexOf('sku');
        if (skuIdx === -1) skuIdx = headers.indexOf('sku / barcode');
        if (skuIdx === -1) skuIdx = headers.indexOf('barcode');
        if (skuIdx === -1) skuIdx = 0;

        var qtyIdx = headers.indexOf('quantity');
        if (qtyIdx === -1) qtyIdx = headers.indexOf('qty');
        if (qtyIdx === -1) qtyIdx = headers.indexOf('count');
        if (qtyIdx === -1) qtyIdx = 1;

        var notesIdx = headers.indexOf('notes');
        if (notesIdx === -1) notesIdx = headers.indexOf('note');
        if (notesIdx === -1) notesIdx = 2;

        var whIdx = headers.indexOf('warehouse');
        if (whIdx === -1) whIdx = headers.indexOf('warehouse name');
        if (whIdx === -1) whIdx = headers.indexOf('wh');
        if (whIdx === -1) whIdx = 3;

        var startRow = 1;
        var headerVal = lines[0][qtyIdx];
        if (headerVal && !isNaN(parseInt(headerVal.trim()))) {
            startRow = 0;
        }

        var dataRows = [];
        for (var i = startRow; i < lines.length; i++) {
            var line = lines[i];
            if (line.length < 2) continue;
            var sku = line[skuIdx] ? line[skuIdx].trim() : '';
            var qtyStr = line[qtyIdx] ? line[qtyIdx].trim() : '';
            var notes = line[notesIdx] ? line[notesIdx].trim() : '';
            var wh = line[whIdx] ? line[whIdx].trim() : '';

            if (!sku && !qtyStr) continue;

            dataRows.push({
                sku: sku,
                quantity: parseInt(qtyStr) || 0,
                notes: notes,
                warehouse: wh
            });
        }

        if (dataRows.length === 0) {
            alert('<?php esc_html_e("No data rows found in CSV.","olama-stores");?>');
            resetCSV();
            return;
        }

        // Show loading preview
        $('#os-bulk-preview-wrap').show();
        $('#os-bulk-preview-tbody').html('<tr><td colspan="7" class="os-loading"><?php esc_html_e("Validating rows with server…","olama-stores");?></td></tr>');
        $('#os-bulk-summary-badges').html('');
        $('#os-btn-confirm-import').prop('disabled', true);

        // Call validate API
        wp.apiFetch({
            path: '/olama-stores/v1/stock/bulk-validate',
            method: 'POST',
            data: { rows: dataRows }
        }).then(function(resolvedRows) {
            parsedRows = resolvedRows;
            renderPreview();
        }).catch(function(err) {
            alert(err.message || 'Error validating CSV');
            resetCSV();
        });
    }

    function renderPreview() {
        var defaultWhId = $('#os-bulk-default-warehouse').val();
        var defaultWhName = $('#os-bulk-default-warehouse option:selected').text();
        
        var validCount = 0;
        var invalidCount = 0;
        var html = '';

        parsedRows.forEach(function(row, idx) {
            var rowNum = idx + 1;
            
            var warehouseLabel = '';
            var hasWarehouse = false;
            
            if (row.wh_id) {
                warehouseLabel = row.wh_name;
                hasWarehouse = true;
            } else if (defaultWhId) {
                warehouseLabel = defaultWhName + ' <small style="color:#64748b; font-style:italic;">(' + '<?php esc_html_e("Default","olama-stores");?>' + ')</small>';
                hasWarehouse = true;
            } else {
                warehouseLabel = '<span class="os-error"><?php esc_html_e("No warehouse specified","olama-stores");?></span>';
                hasWarehouse = false;
            }

            var statusHtml = '';
            var rowValid = row.valid && hasWarehouse;
            var errorMsg = row.error;

            if (!hasWarehouse && row.valid) {
                errorMsg = '<?php esc_html_e("Warehouse not specified. Select a default warehouse or add it in CSV.","olama-stores");?>';
            }

            if (rowValid) {
                validCount++;
                statusHtml = '<span class="os-badge os-badge-success" style="background:#dcfce7; color:#15803d;"><span class="dashicons dashicons-yes" style="font-size:14px; width:14px; height:14px; vertical-align:middle; margin-top:-2px;"></span> ' + '<?php esc_html_e("Ready","olama-stores");?>' + '</span>';
            } else {
                invalidCount++;
                statusHtml = '<span class="os-badge os-badge-danger" style="background:#fee2e2; color:#b91c1c;"><span class="dashicons dashicons-warning" style="font-size:14px; width:14px; height:14px; vertical-align:middle; margin-top:-2px;"></span> ' + '<?php esc_html_e("Error","olama-stores");?>' + '</span>'
                    + '<br><small class="os-error" style="display:block; margin-top:4px;">' + errorMsg + '</small>';
            }

            html += '<tr>'
                + '<td style="text-align:center;">' + rowNum + '</td>'
                + '<td><strong>' + (row.sku || '-') + '</strong></td>'
                + '<td>' + (row.item_name ? row.item_name + (row.unit_symbol ? ' (' + row.unit_symbol + ')' : '') : '<span class="os-error">-</span>') + '</td>'
                + '<td>' + warehouseLabel + '</td>'
                + '<td><strong>' + row.quantity + '</strong></td>'
                + '<td><span style="font-size:0.9em; color:#64748b;">' + (row.notes || '-') + '</span></td>'
                + '<td>' + statusHtml + '</td>'
                + '</tr>';
        });

        $('#os-bulk-preview-tbody').html(html);

        var summaryHtml = '<span class="os-badge os-badge-success" style="font-size: 1em; padding: 6px 12px; margin-right: 8px;">' + validCount + ' ' + '<?php esc_html_e("Valid","olama-stores");?>' + '</span>';
        if (invalidCount > 0) {
            summaryHtml += '<span class="os-badge os-badge-danger" style="font-size: 1em; padding: 6px 12px;">' + invalidCount + ' ' + '<?php esc_html_e("Errors","olama-stores");?>' + '</span>';
        }
        $('#os-bulk-summary-badges').html(summaryHtml);

        if (validCount > 0) {
            $('#os-btn-confirm-import').prop('disabled', false).html('<span class="dashicons dashicons-upload" style="vertical-align: middle; margin-right: 6px;"></span> ' + '<?php esc_html_e("Import","olama-stores");?>' + ' ' + validCount + ' ' + '<?php esc_html_e("Items","olama-stores");?>');
        } else {
            $('#os-btn-confirm-import').prop('disabled', true).html('<span class="dashicons dashicons-upload" style="vertical-align: middle; margin-right: 6px;"></span> ' + '<?php esc_html_e("Import Items","olama-stores");?>');
        }
    }

    $('#os-btn-confirm-import').on('click', function() {
        var defaultWhId = $('#os-bulk-default-warehouse').val();
        var validRows = parsedRows.filter(function(row) {
            return row.valid && (row.wh_id || defaultWhId);
        });

        if (validRows.length === 0) {
            alert('<?php esc_html_e("No valid rows to import.","olama-stores");?>');
            return;
        }

        // Make sure wh_id falls back to default if empty
        validRows.forEach(function(row) {
            if (!row.wh_id) {
                row.wh_id = parseInt(defaultWhId);
            }
        });

        var btn = $(this);
        btn.prop('disabled', true).text('<?php esc_html_e("Importing...","olama-stores");?>');

        wp.apiFetch({
            path: '/olama-stores/v1/stock/bulk-receive',
            method: 'POST',
            data: {
                rows: validRows,
                default_warehouse_id: parseInt(defaultWhId),
                academic_year_id: olamaStores.activeYearId
            }
        }).then(function(res) {
            alert(res.count + ' ' + '<?php esc_html_e("items imported successfully!","olama-stores");?>');
            resetCSV();
            loadRecentMovements();
            $('.os-tab-link[data-tab="single"]').trigger('click');
        }).catch(function(err) {
            alert(err.message || 'Error during bulk import');
        }).finally(function() {
            btn.prop('disabled', false);
        });
    });

    function resetCSV() {
        parsedRows = [];
        fileInput.val('');
        $('#os-csv-file-name').text('');
        $('#os-csv-file-info').hide();
        $('#os-bulk-preview-wrap').hide();
        $('#os-bulk-preview-tbody').html('');
        $('#os-bulk-summary-badges').html('');
        dropzone.show();
    }

    $('#os-btn-clear-csv, #os-btn-cancel-import').on('click', function() {
        resetCSV();
    });

    $('#os-btn-download-template').on('click', function() {
        var csvContent = "data:text/csv;charset=utf-8," 
            + "SKU,Quantity,Notes,Warehouse\n"
            + "SKU-00001,10,Opening stock,\n"
            + "SKU-00002,25,New purchase,Al-Rayy\n";
        var encodedUri = encodeURI(csvContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "store_bulk_additions_template.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    function parseCSV(text) {
        var lines = [];
        var row = [""];
        var inQuotes = false;
        var separator = ',';

        var firstLine = text.split('\n')[0];
        if (firstLine.indexOf(';') !== -1 && (firstLine.indexOf(',') === -1 || firstLine.indexOf(';') < firstLine.indexOf(','))) {
            separator = ';';
        }

        for (var i = 0; i < text.length; i++) {
            var c = text[i];
            var next = text[i + 1];

            if (c === '"') {
                if (inQuotes && next === '"') {
                    row[row.length - 1] += '"';
                    i++;
                } else {
                    inQuotes = !inQuotes;
                }
            } else if (c === separator && !inQuotes) {
                row.push('');
            } else if ((c === '\r' || c === '\n') && !inQuotes) {
                if (c === '\r' && next === '\n') {
                    i++;
                }
                lines.push(row);
                row = [''];
            } else {
                row[row.length - 1] += c;
            }
        }
        if (row.length > 1 || row[0] !== '') {
            lines.push(row);
        }
        return lines;
    }

    // ── Recent movements section ──────────────────────────────────────────
    function loadRecentMovements(){
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
/* Grid layout rules */
.os-grid-2col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}
@media (max-width: 900px) {
    .os-grid-2col {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}
.full-width-field {
    display: block !important;
}
.full-width-field label {
    display: block !important;
    margin-bottom: 8px !important;
    font-weight: 600;
    color: #2c3e50;
    float: none !important;
    width: auto !important;
}
.os-form-column {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}

/* Tabs styles */
.os-tabs-nav {
    display: flex;
    gap: 8px;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 25px;
    margin-top: 15px;
}
.os-tab-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    text-decoration: none;
    color: #64748b;
    font-weight: 600;
    font-size: 0.95rem;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease-in-out;
}
.os-tab-link:hover {
    color: #3b82f6;
    border-bottom-color: #cbd5e1;
}
.os-tab-link.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
}
.os-tab-link .dashicons {
    font-size: 1.2rem;
    width: 1.2rem;
    height: 1.2rem;
}

/* CSV Drag & dropzone */
.os-csv-dropzone {
    border: 3px dashed #cbd5e1;
    border-radius: 12px;
    padding: 35px 20px;
    text-align: center;
    background: #f8fafc;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    margin-top: 15px;
}
.os-csv-dropzone:hover, .os-csv-dropzone.dragover {
    border-color: #3b82f6;
    background: #f0f7ff;
}
.os-csv-dropzone h3 {
    margin: 10px 0 5px 0;
    font-size: 1.15em;
    color: #1e293b;
    font-weight: 600;
}
.os-csv-dropzone p {
    margin: 0;
    color: #64748b;
    font-size: 0.9em;
}

/* Card aesthetics */
.os-card { 
    background: #fff; 
    padding: 30px; 
    border-radius: 12px; 
    box-shadow: 0 4px 20px rgba(0,0,0,0.03); 
    border: 1px solid #e2e8f0;
}
.os-form-row { margin-bottom: 25px; }
.os-form-row label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50; }
.os-form-row input[type="text"], .os-form-row input[type="number"], .os-form-row select, .os-form-row textarea {
    width: 100%; padding: 12px; border: 2px solid #eaeff2; border-radius: 8px; transition: border-color 0.2s; box-sizing: border-box;
}
.os-form-row input:focus, .os-form-row select:focus, .os-form-row textarea:focus { border-color: #3498db; outline: none; }
.os-badge { padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.9em; display: inline-block; }
.os-badge-success { background: #e6fffa; color: #047481; }

.description {
    font-style: italic;
    font-size: 0.85em;
}
</style>
