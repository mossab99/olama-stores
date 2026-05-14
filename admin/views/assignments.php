<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap os-wrap" id="os-assignments-page">
    <h1 class="os-page-title">
        <span class="dashicons dashicons-admin-users"></span>
        <?php esc_html_e( 'Employee Custody', 'olama-stores' ); ?>
        <?php if ( OS_Roles::can( 'os_process_assignments' ) ): ?>
            <a href="#" id="os-btn-new-assignment" class="page-title-action"><?php esc_html_e( 'Issue Custody', 'olama-stores' ); ?></a>
        <?php endif; ?>
    </h1>

    <div class="os-filters tablenav top">
        <select id="os-filter-status">
            <option value=""><?php esc_html_e( 'All Statuses', 'olama-stores' ); ?></option>
            <option value="active"><?php esc_html_e( 'Active (Held)', 'olama-stores' ); ?></option>
            <option value="fully_returned"><?php esc_html_e( 'Returned', 'olama-stores' ); ?></option>
            <option value="lost"><?php esc_html_e( 'Lost / Damaged', 'olama-stores' ); ?></option>
        </select>
        <button class="button" id="os-btn-export-assignments"><?php esc_html_e( 'Export Excel', 'olama-stores' ); ?></button>
    </div>

    <div id="os-assignments-table-wrap">
        <span class="os-loading"><?php esc_html_e( 'Loading…', 'olama-stores' ); ?></span>
    </div>

    <!-- Issue Items Modal (2-step) -->
    <div id="os-assignment-modal" class="os-modal" style="display:none;">
        <div class="os-modal-content">
            <h2><?php esc_html_e( 'Issue Items', 'olama-stores' ); ?></h2>
            <div id="os-assignment-step1">
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Employee', 'olama-stores' ); ?></label>
                    <select id="os-assign-person"></select>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Item', 'olama-stores' ); ?></label>
                    <div class="os-input-group">
                        <input type="text" class="os-modal-item-search" data-target="#os-assign-item" placeholder="<?php esc_attr_e( 'Search items...', 'olama-stores' ); ?>">
                        <select id="os-assign-item"></select>
                    </div>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Warehouse', 'olama-stores' ); ?></label>
                    <select id="os-assign-warehouse"></select>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Quantity', 'olama-stores' ); ?></label>
                    <input type="number" id="os-assign-qty" min="1" value="1">
                    <small id="os-assign-available"></small>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Assigned Date', 'olama-stores' ); ?></label>
                    <input type="date" id="os-assign-date">
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Expected Return Date', 'olama-stores' ); ?></label>
                    <input type="date" id="os-assign-return-date">
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Notes', 'olama-stores' ); ?></label>
                    <textarea id="os-assign-notes"></textarea>
                </div>
                <div class="os-form-actions">
                    <button type="button" class="button button-primary" id="os-assign-preview"><?php esc_html_e( 'Preview →', 'olama-stores' ); ?></button>
                    <button type="button" class="button os-modal-close"><?php esc_html_e( 'Cancel', 'olama-stores' ); ?></button>
                </div>
            </div>

            <!-- Step 2: confirm preview -->
            <div id="os-assignment-step2" style="display:none;">
                <div id="os-assignment-preview-content"></div>
                <div class="os-form-actions">
                    <button type="button" class="button button-primary" id="os-assign-confirm"><?php esc_html_e( 'Confirm & Issue', 'olama-stores' ); ?></button>
                    <button type="button" class="button" id="os-assign-back"><?php esc_html_e( '← Back', 'olama-stores' ); ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Return Modal -->
    <div id="os-return-modal" class="os-modal" style="display:none;">
        <div class="os-modal-content">
            <h2><?php esc_html_e( 'Process Return', 'olama-stores' ); ?></h2>
            <input type="hidden" id="os-return-assignment-id">
            <div class="os-form-row">
                <label><?php esc_html_e( 'Quantity to Return', 'olama-stores' ); ?></label>
                <input type="number" id="os-return-qty" min="1" value="1">
                <small id="os-return-max"></small>
            </div>
            <div class="os-form-row">
                <label><?php esc_html_e( 'Condition', 'olama-stores' ); ?></label>
                <select id="os-return-condition">
                    <option value="good"><?php esc_html_e( 'Good', 'olama-stores' ); ?></option>
                    <option value="damaged"><?php esc_html_e( 'Damaged', 'olama-stores' ); ?></option>
                    <option value="lost"><?php esc_html_e( 'Lost', 'olama-stores' ); ?></option>
                </select>
            </div>
            <div class="os-form-row">
                <label><?php esc_html_e( 'Return Date', 'olama-stores' ); ?></label>
                <input type="date" id="os-return-date">
            </div>
            <div class="os-form-row">
                <label><?php esc_html_e( 'Notes', 'olama-stores' ); ?></label>
                <textarea id="os-return-notes"></textarea>
            </div>
            <div class="os-form-actions">
                <button type="button" class="button button-primary" id="os-return-submit"><?php esc_html_e( 'Record Return', 'olama-stores' ); ?></button>
                <button type="button" class="button os-modal-close"><?php esc_html_e( 'Cancel', 'olama-stores' ); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
(function($){
    var today = new Date().toISOString().split('T')[0];
    $('#os-assign-date, #os-return-date').val(today);

    function loadAssignments(){
        var status = $('#os-filter-status').val();
        var params = '?academic_year_id=' + (olamaStores.activeYearId||'') + '&assignee_type=employee';
        if(status) params += '&status='+status;
        $('#os-assignments-table-wrap').html('<span class="os-loading"><?php esc_html_e("Loading…","olama-stores");?></span>');
        wp.apiFetch({ path: '/olama-stores/v1/assignments'+params }).then(renderAssignments);
    }

    function renderAssignments(rows){
        if(!rows.length){ $('#os-assignments-table-wrap').html('<p><?php esc_html_e("No custody records found.","olama-stores");?></p>'); return; }
        var html = '<table class="wp-list-table widefat striped"><thead><tr>'
            + '<th>ID</th><th><?php esc_html_e("Employee","olama-stores");?></th>'
            + '<th><?php esc_html_e("Item","olama-stores");?></th><th><?php esc_html_e("Warehouse","olama-stores");?></th>'
            + '<th><?php esc_html_e("Qty","olama-stores");?></th>'
            + '<th><?php esc_html_e("Status","olama-stores");?></th><th><?php esc_html_e("Assigned Date","olama-stores");?></th>'
            + '<th><?php esc_html_e("Actions","olama-stores");?></th></tr></thead><tbody>';
        rows.forEach(function(r){
            html += '<tr id="os-assignment-row-'+r.id+'">'
                + '<td>'+r.id+'</td>'
                + '<td>'+r.assignee_id+'</td>'
                + '<td>'+(r.item_name||'')+'<br><small>'+r.sku+'</small></td>'
                + '<td>'+(r.warehouse_name||'')+'</td>'
                + '<td>'+r.quantity_assigned+'</td>'
                + '<td><span class="os-badge os-badge-'+r.status+'">'+r.status.replace(/_/g,' ')+'</span></td>'
                + '<td>'+r.assigned_date+'</td><td>';
            if(olamaStores.caps.manage_items && r.status !== 'fully_returned' && r.status !== 'lost'){
                html += '<button class="button button-small os-btn-return" data-id="'+r.id+'" data-max="'+(r.quantity_assigned-r.quantity_returned)+'"><?php esc_html_e("Record Return","olama-stores");?></button>';
            }
            html += '</td></tr>';
        });
        html += '</tbody></table>';
        $('#os-assignments-table-wrap').html(html);
    }

    $('#os-filter-status').on('change', loadAssignments);
    loadAssignments();

    // Item search for modals
    function searchItems(query, targetSelect){
        var params = '?per_page=50&academic_year_id=' + (olamaStores.activeYearId||'');
        if(query) params += '&search=' + encodeURIComponent(query);
        wp.apiFetch({ path: '/olama-stores/v1/items' + params }).then(function(items){
            var opts = '<option value=""></option>';
            items.forEach(function(i){ opts += '<option value="'+i.id+'">'+i.name+' ('+i.sku+')</option>'; });
            $(targetSelect).html(opts);
        });
    }

    var modalSearchTimer;
    $(document).on('input', '.os-modal-item-search', function(){
        var q = $(this).val(), target = $(this).data('target');
        clearTimeout(modalSearchTimer);
        modalSearchTimer = setTimeout(function(){ searchItems(q, target); }, 400);
    });

    // Open issue modal
    $('#os-btn-new-assignment').on('click', function(e){
        e.preventDefault();
        Promise.all([
            wp.apiFetch({ path:'/olama-stores/v1/employees' }),
            wp.apiFetch({ path:'/olama-stores/v1/warehouses' }),
        ]).then(function(r){
            populatePersonSelect(r[0]);
            var whOpts='<option value=""></option>';
            r[1].forEach(function(w){ whOpts+='<option value="'+w.id+'">'+w.name+'</option>'; });
            $('#os-assign-warehouse').html(whOpts);
            
            searchItems('', '#os-assign-item');
            $('#os-assignment-modal').find('.os-modal-item-search').val('');
            
            $('#os-assignment-step1').show();
            $('#os-assignment-step2').hide();
            $('#os-assignment-modal').show();
        });
    });

    function populatePersonSelect(people){
        var opts='<option value=""></option>';
        people.forEach(function(p){
            opts += '<option value="'+p.ID+'">'+p.display_name+'</option>';
        });
        $('#os-assign-person').html(opts);
    }

    // Preview step
    $('#os-assign-preview').on('click', function(){
        var previewHtml = '<table class="widefat"><tr><th><?php esc_html_e("Field","olama-stores");?></th><th><?php esc_html_e("Value","olama-stores");?></th></tr>'
            + '<tr><td><?php esc_html_e("Employee","olama-stores");?></td><td>'+$('#os-assign-person option:selected').text()+'</td></tr>'
            + '<tr><td><?php esc_html_e("Item","olama-stores");?></td><td>'+$('#os-assign-item option:selected').text()+'</td></tr>'
            + '<tr><td><?php esc_html_e("Warehouse","olama-stores");?></td><td>'+$('#os-assign-warehouse option:selected').text()+'</td></tr>'
            + '<tr><td><?php esc_html_e("Quantity","olama-stores");?></td><td>'+$('#os-assign-qty').val()+'</td></tr>'
            + '</table>';
        $('#os-assignment-preview-content').html(previewHtml);
        $('#os-assignment-step1').hide();
        $('#os-assignment-step2').show();
    });

    $('#os-assign-back').on('click', function(){ $('#os-assignment-step1').show(); $('#os-assignment-step2').hide(); });

    $('#os-assign-confirm').on('click', function(){
        var payload = {
            assignee_type:      'employee',
            assignee_id:        $('#os-assign-person').val(),
            item_id:            parseInt($('#os-assign-item').val()),
            warehouse_id:       parseInt($('#os-assign-warehouse').val()),
            quantity_assigned:  parseInt($('#os-assign-qty').val()),
            assigned_date:      $('#os-assign-date').val(),
            expected_return_date: $('#os-assign-return-date').val(),
            notes:              $('#os-assign-notes').val(),
            academic_year_id:   olamaStores.activeYearId
        };
        wp.apiFetch({ path:'/olama-stores/v1/assignments', method:'POST', data:payload }).then(function(){
            $('#os-assignment-modal').hide();
            loadAssignments();
        }).catch(function(e){ alert(e.message||'<?php esc_html_e("Error issuing items","olama-stores");?>'); });
    });

    // Return modal
    $(document).on('click', '.os-btn-return', function(){
        var id=$(this).data('id'), max=$(this).data('max');
        $('#os-return-assignment-id').val(id);
        $('#os-return-qty').val(1).attr('max',max);
        $('#os-return-max').text('<?php esc_html_e("Max:","olama-stores");?> '+max);
        $('#os-return-modal').show();
    });

    $('#os-return-submit').on('click', function(){
        var payload={
            assignment_id:    parseInt($('#os-return-assignment-id').val()),
            quantity:         parseInt($('#os-return-qty').val()),
            return_condition: $('#os-return-condition').val(),
            return_date:      $('#os-return-date').val(),
            notes:            $('#os-return-notes').val()
        };
        wp.apiFetch({ path:'/olama-stores/v1/assignments/returns', method:'POST', data:payload }).then(function(){
            $('#os-return-modal').hide();
            loadAssignments();
        }).catch(function(e){ alert(e.message||'<?php esc_html_e("Error recording return","olama-stores");?>'); });
    });

    // Close modals
    $('.os-modal-close, .os-modal').on('click', function(e){ if(e.target===this) $(this).closest('.os-modal').hide(); });

    // Export
    $('#os-btn-export-assignments').on('click', function(){
        var params='?academic_year_id='+(olamaStores.activeYearId||'') + '&assignee_type=employee';
        window.location = olamaStores.apiRoot + '/reports/export/assignments' + params + '&_wpnonce=' + olamaStores.nonce;
    });
})(jQuery);
</script>
