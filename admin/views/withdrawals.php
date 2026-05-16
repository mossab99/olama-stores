<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap os-wrap" id="os-withdrawals-page">
    <h1 class="os-page-title">
        <span class="dashicons dashicons-cart"></span>
        <?php esc_html_e( 'Student Withdrawals', 'olama-stores' ); ?>
        <span class="os-year-badge"><?php echo esc_html( os_get_active_year_name() ); ?></span>
    </h1>

    <!-- Family Lookup Section -->
    <div class="os-section-card" style="margin-bottom: 2rem;">
        <div class="os-section-header">
            <h2><span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Find Family', 'olama-stores' ); ?></h2>
        </div>
        <div class="os-filters">
            <input type="text" id="os-family-search" class="os-filter-input" style="min-width: 300px;" placeholder="<?php esc_attr_e( 'Enter Family Number / Name...', 'olama-stores' ); ?>">
            <button type="button" id="os-btn-family-lookup" class="button button-primary"><?php esc_html_e( 'Retrieve Family Students', 'olama-stores' ); ?></button>
        </div>
    </div>

    <!-- Family Results Container -->
    <div id="os-family-results-wrap" style="display:none;">
        <div class="os-section-card">
            <div class="os-section-header">
                <h2><span class="dashicons dashicons-groups"></span> <?php esc_html_e( 'Family Members:', 'olama-stores' ); ?> <span id="os-active-family-id" style="color:var(--os-primary);"></span></h2>
            </div>
            <div id="os-family-students-list"></div>
        </div>
        
        <div class="os-section-card" style="margin-top: 2rem; display:none;" id="os-family-withdrawals-wrap">
            <div class="os-section-header">
                <h2><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Withdrawals History', 'olama-stores' ); ?></h2>
            </div>
            <div id="os-family-withdrawals-list"></div>
        </div>
    </div>

    <!-- Batch Assignment Modal (hidden) -->
    <div id="os-withdraw-modal" class="os-modal" style="display:none;">
        <div class="os-modal-content">
            <h2 id="os-withdraw-modal-title"><?php esc_html_e( 'Withdraw Items', 'olama-stores' ); ?></h2>
            
            <div class="os-form-row">
                <label><?php esc_html_e( 'Student', 'olama-stores' ); ?></label>
                <input type="text" id="os-wd-student-name" readonly>
                <input type="hidden" id="os-wd-student-uid">
            </div>
            
            <div class="os-form-row">
                <label><?php esc_html_e( 'Warehouse', 'olama-stores' ); ?></label>
                <select id="os-wd-warehouse" required></select>
            </div>

            <div style="margin-top: 24px; width: 100%;">
                <div class="os-items-header">
                    <div style="width: 80px; flex: 0 0 80px;"><?php esc_html_e( 'Qty', 'olama-stores' ); ?></div>
                    <div style="flex: 1; padding-left: 12px;"><?php esc_html_e( 'Item (Search & Select)', 'olama-stores' ); ?></div>
                </div>
                <div class="os-withdrawal-items">
                    <?php for ( $i = 1; $i <= 4; $i++ ) : ?>
                    <div class="os-item-row">
                        <div style="display: flex; gap: 12px; align-items: center; width: 100%;">
                            <input type="number" id="os-wd-qty-<?php echo $i; ?>" class="os-wd-qty-input" min="1" value="1" style="width: 80px !important; flex: 0 0 80px;">
                            <div class="os-input-group" style="flex: 1; min-width: 0;">
                                <input type="text" class="os-modal-item-search" 
                                       data-target="#os-wd-item-<?php echo $i; ?>" 
                                       placeholder="<?php esc_attr_e( 'Search items...', 'olama-stores' ); ?>">
                                <select id="os-wd-item-<?php echo $i; ?>" class="os-wd-item-select"></select>
                            </div>
                        </div>
                        <div class="os-row-stock-avail"></div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>


            <div class="os-form-row" style="margin-top: 24px;">
                <label><?php esc_html_e( 'Notes', 'olama-stores' ); ?></label>
                <textarea id="os-wd-notes" placeholder="<?php esc_attr_e( 'e.g. Uniform distribution', 'olama-stores' ); ?>"></textarea>
            </div>

            <div class="os-form-actions">
                <button type="button" class="button button-primary" id="os-btn-confirm-wd"><?php esc_html_e( 'Confirm Withdrawal', 'olama-stores' ); ?></button>
                <button type="button" class="button os-modal-close"><?php esc_html_e( 'Cancel', 'olama-stores' ); ?></button>
            </div>
        </div>
    </div>

</div>

<script>
(function($){
    var warehouses = [], items = [];

    // Preload meta
    wp.apiFetch({ path: '/olama-stores/v1/warehouses' }).then(function(res){
        warehouses = res;
        var whOpts = '<option value=""></option>';
        warehouses.forEach(function(w){ whOpts += '<option value="'+w.id+'">'+w.name+'</option>'; });
        $('#os-wd-warehouse').html(whOpts);
    });



    // Lookup
    $('#os-btn-family-lookup').on('click', function(){
        var search = $('#os-family-search').val();
        if(!search) return;
        
        $('#os-active-family-id').text(search);
        $('#os-family-results-wrap').show();
        $('#os-family-students-list').html('<p class="os-loading"><?php esc_html_e("Searching families...","olama-stores");?></p>');

        wp.apiFetch({ path: '/olama-stores/v1/students?search=' + encodeURIComponent(search) + '&academic_year_id=' + olamaStores.activeYearId }).then(function(students){
            if(!students.length){
                $('#os-family-students-list').html('<p><?php esc_html_e("No students found for this family.","olama-stores");?></p>');
                $('#os-family-withdrawals-wrap').hide();
                return;
            }
            renderStudents(students);
            fetchFamilyWithdrawals(students);
        });
    });

    function fetchFamilyWithdrawals(students) {
        $('#os-family-withdrawals-wrap').show();
        $('#os-family-withdrawals-list').html('<p class="os-loading"><?php esc_html_e("Loading history...","olama-stores");?></p>');
        
        var promises = students.map(function(s) {
            return wp.apiFetch({ path: '/olama-stores/v1/assignees/student/' + s.student_uid + '?academic_year_id=' + olamaStores.activeYearId });
        });

        Promise.all(promises).then(function(results) {
            var allWithdrawals = [];
            results.forEach(function(withdrawals, index) {
                var studentName = students[index].student_name;
                withdrawals.forEach(function(w) {
                    w.student_name = studentName; // Inject name for display
                    allWithdrawals.push(w);
                });
            });

            // Sort by date desc
            allWithdrawals.sort(function(a, b) {
                return new Date(b.created_at) - new Date(a.created_at);
            });

            renderWithdrawals(allWithdrawals);
        });
    }

    function renderWithdrawals(withdrawals) {
        if (!withdrawals.length) {
            $('#os-family-withdrawals-list').html('<p><?php esc_html_e("No withdrawals recorded for this family yet.","olama-stores");?></p>');
            return;
        }

        var html = '<table class="wp-list-table widefat striped">'
            + '<thead><tr>'
            + '<th><?php esc_html_e("Date","olama-stores");?></th>'
            + '<th><?php esc_html_e("Student","olama-stores");?></th>'
            + '<th><?php esc_html_e("Item","olama-stores");?></th>'
            + '<th style="text-align:center;"><?php esc_html_e("Qty","olama-stores");?></th>'
            + '<th><?php esc_html_e("Warehouse","olama-stores");?></th>'
            + '<th><?php esc_html_e("Status","olama-stores");?></th>'
            + '<th><?php esc_html_e("Action","olama-stores");?></th>'
            + '</tr></thead><tbody>';

        withdrawals.forEach(function(w) {
            var date      = new Date(w.assigned_date).toLocaleDateString();
            var isActive  = (w.status === 'active');
            var isReversed= (w.status === 'reversed');

            // Row styling: muted for reversed rows
            var rowStyle  = isReversed ? ' style="opacity:0.6;"' : '';

            // Transaction type pill
            var txType = isReversed
                ? '<span class="os-tx-pill os-tx-return">↩ <?php esc_html_e("Reversed","olama-stores");?></span>'
                : '<span class="os-tx-pill os-tx-issue">↓ <?php esc_html_e("Issued","olama-stores");?></span>';

            // Action column
            var actionCell = '';
            if (isActive) {
                actionCell = '<button class="button button-small os-btn-reverse" '
                    + 'data-id="' + w.id + '" '
                    + 'data-item="' + w.item_name + '" '
                    + 'data-qty="' + w.quantity_assigned + '">'
                    + '↩ <?php esc_html_e("Reverse","olama-stores");?>'
                    + '</button>';
            } else if (isReversed) {
                actionCell = '<span style="color:#6c757d; font-size:0.8rem;"><?php esc_html_e("Reversed","olama-stores");?></span>';
            }

            html += '<tr' + rowStyle + '>'
                + '<td>' + date + '<br><small style="color:#6c757d;">' + txType + '</small></td>'
                + '<td>' + w.student_name + '</td>'
                + '<td><strong>' + w.item_name + '</strong><br><small>' + w.sku + '</small></td>'
                + '<td style="text-align:center;">' + w.quantity_assigned + '</td>'
                + '<td>' + w.warehouse_name + '</td>'
                + '<td><span class="os-badge os-badge-' + w.status + '">' + w.status.replace('_',' ') + '</span></td>'
                + '<td>' + actionCell + '</td>'
                + '</tr>';
        });

        html += '</tbody></table>';
        $('#os-family-withdrawals-list').html(html);
    }

    // Handle Reverse button click
    $(document).on('click', '.os-btn-reverse', function() {
        var $btn = $(this);
        var id   = $btn.data('id');
        var item = $btn.data('item');
        var qty  = $btn.data('qty');

        var msg = '<?php esc_html_e("Reverse withdrawal of","olama-stores");?> ' + qty + 'x ' + item + '?\n'
                + '<?php esc_html_e("Stock will be restored to the warehouse.","olama-stores");?>';

        if (!confirm(msg)) return;

        $btn.prop('disabled', true).text('<?php esc_html_e("Processing...","olama-stores");?>');

        wp.apiFetch({
            path:   '/olama-stores/v1/assignments/' + id + '/reverse',
            method: 'POST',
            data:   { notes: '<?php esc_html_e("Reversed via Student Withdrawals admin panel.","olama-stores");?>' }
        }).then(function() {
            // Refresh family lookup to show updated table
            $('#os-btn-family-lookup').trigger('click');
        }).catch(function(e) {
            alert(e.message || '<?php esc_html_e("Reversal failed. Please try again.","olama-stores");?>');
            $btn.prop('disabled', false).text('↩ <?php esc_html_e("Reverse","olama-stores");?>');
        });
    });


    function renderStudents(students){
        var html = '<table class="wp-list-table widefat striped"><thead><tr>'
            + '<th><?php esc_html_e("Name","olama-stores");?></th>'
            + '<th><?php esc_html_e("Grade / Section","olama-stores");?></th>'
            + '<th><?php esc_html_e("Actions","olama-stores");?></th>'
            + '</tr></thead><tbody>';
        students.forEach(function(s){
            html += '<tr>'
                + '<td><strong>'+s.student_name+'</strong><br><small>UID: '+s.student_uid+'</small></td>'
                + '<td>'+(s.grade_name||'')+' — '+(s.section_name||'')+'</td>'
                + '<td><button class="button button-small os-btn-wd" data-uid="'+s.student_uid+'" data-name="'+s.student_name+'"><?php esc_html_e("Select Items","olama-stores");?></button></td>'
                + '</tr>';
        });
        html += '</tbody></table>';
        $('#os-family-students-list').html(html);
    }

    // Open Modal
    $(document).on('click', '.os-btn-wd', function(){
        var uid = $(this).data('uid'), name = $(this).data('name');
        $('#os-wd-student-uid').val(uid);
        $('#os-wd-student-name').val(name);
        
        // Reset rows
        $('.os-withdrawal-items .os-item-row').each(function(index){
            var $row = $(this);
            $row.find('.os-modal-item-search').val('');
            $row.find('.os-wd-item-select').empty();
            $row.find('.os-wd-qty-input').val(1);
            $row.find('.os-row-stock-avail').text('');
            
        });
        
        // Pre-load first 5 custom items into all dropdowns simultaneously
        window.osSearchItems('', $('.os-withdrawal-items .os-wd-item-select'), null, { per_page: 5, is_custom: 1 });

        // Reset warehouse to custom by default and lock it
        var customWh = warehouses.find(function(w){ return w.type === 'custom'; });
        if (customWh) {
            $('#os-wd-warehouse').val(customWh.id).prop('disabled', true);
        } else {
            $('#os-wd-warehouse').prop('disabled', false).val('');
        }
        
        $('#os-withdraw-modal').show();
    });

    // Stock check helper for multi-rows + Warehouse locking logic
    $(document).on('change', '.os-wd-item-select, #os-wd-warehouse', function(){
        var $row = $(this).closest('.os-item-row');
        
        // ── Warehouse Locking Logic ──────────────────────────────────────────
        var hasCustom = false, hasBooks = false, hasGeneral = false;
        $('.os-wd-item-select').each(function(){
            var val = $(this).val();
            if (!val) return;
            var $opt = $(this).find('option:selected');
            if ($opt.data('custom') == '1') hasCustom = true;
            else if ($opt.data('books') == '1') hasBooks = true;
            else hasGeneral = true;
        });

        var $whSelect = $('#os-wd-warehouse');
        var customWh = warehouses.find(function(w){ return w.type === 'custom'; });
        var booksWh  = warehouses.find(function(w){ return w.type === 'books'; });

        if (hasCustom && customWh) {
            $whSelect.val(customWh.id).prop('disabled', true);
        } else if (hasBooks && booksWh) {
            $whSelect.val(booksWh.id).prop('disabled', true);
        } else if (customWh) {
            // Default to custom and lock as requested
            $whSelect.val(customWh.id).prop('disabled', true);
        } else {
            $whSelect.prop('disabled', false);
        }
        // ─────────────────────────────────────────────────────────────────────

        if (!$row.length) {
            // Warehouse changed, refresh all active rows
            $('.os-item-row').each(function(){ checkRowStock($(this)); });
            return;
        }
        checkRowStock($row);
    });

    function checkRowStock($row) {
        var itemId = $row.find('.os-wd-item-select').val();
        var whId   = $('#os-wd-warehouse').val();
        var $avail = $row.find('.os-row-stock-avail');
        if (!itemId || !whId) { $avail.text('').removeClass('os-stock-none'); return; }
        
        wp.apiFetch({ path: '/olama-stores/v1/stock?item_id=' + itemId + '&warehouse_id=' + whId }).then(function(stock){
            if (!stock.length) {
                $avail.text('<?php esc_html_e("Available:","olama-stores");?> 0').addClass('os-stock-none');
                return;
            }
            var row = stock[0];
            var onHand   = parseInt(row.quantity_on_hand)   || 0;
            var reserved = parseInt(row.quantity_reserved)  || 0;
            var avail    = parseInt(row.quantity_available) || (onHand - reserved);
            avail = Math.max(0, avail);
            $avail
                .text('<?php esc_html_e("Available:","olama-stores");?> ' + avail)
                .toggleClass('os-stock-none', avail <= 0);
        }).catch(function(){
            $avail.text('').removeClass('os-stock-none');
        });
    }

    // Confirm
    $('#os-btn-confirm-wd').on('click', function(){
        var items = [];
        $('.os-item-row').each(function(){
            var $row = $(this);
            var itemId = parseInt($row.find('.os-wd-item-select').val());
            var qty = parseInt($row.find('.os-wd-qty-input').val());
            if (itemId > 0 && qty > 0) {
                items.push({ item_id: itemId, quantity: qty });
            }
        });

        if (!items.length) {
            alert('<?php esc_html_e("Please select at least one item.","olama-stores");?>');
            return;
        }

        var payload = {
            assignee_type: 'student',
            assignee_id: $('#os-wd-student-uid').val(),
            warehouse_id: parseInt($('#os-wd-warehouse').val()),
            items: items,
            assigned_date: new Date().toISOString().split('T')[0],
            notes: $('#os-wd-notes').val(),
            academic_year_id: olamaStores.activeYearId
        };

        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php esc_html_e("Processing...","olama-stores");?>');

        wp.apiFetch({ path: '/olama-stores/v1/assignments', method: 'POST', data: payload }).then(function(){
            alert('<?php esc_html_e("Withdrawal successful!","olama-stores");?>');
            $('#os-withdraw-modal').hide();
            $('#os-btn-family-lookup').click();
        }).catch(function(e){ 
            alert(e.message); 
        }).finally(function(){
            $btn.prop('disabled', false).text('<?php esc_html_e("Confirm Withdrawal","olama-stores");?>');
        });
    });
})(jQuery);

</script>
