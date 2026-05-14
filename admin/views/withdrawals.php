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
            <div class="os-form-row">
                <label><?php esc_html_e( 'Item', 'olama-stores' ); ?></label>
                <div class="os-input-group">
                    <input type="text" class="os-modal-item-search" data-target="#os-wd-item" placeholder="<?php esc_attr_e( 'Search items...', 'olama-stores' ); ?>">
                    <select id="os-wd-item" required></select>
                </div>
            </div>
            <div class="os-form-row">
                <label><?php esc_html_e( 'Quantity', 'olama-stores' ); ?></label>
                <input type="number" id="os-wd-qty" min="1" value="1">
                <small id="os-wd-stock-avail"></small>
            </div>
            <div class="os-form-row">
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

        var html = '<table class="wp-list-table widefat striped"><thead><tr>'
            + '<th><?php esc_html_e("Date","olama-stores");?></th>'
            + '<th><?php esc_html_e("Student","olama-stores");?></th>'
            + '<th><?php esc_html_e("Item","olama-stores");?></th>'
            + '<th><?php esc_html_e("Quantity","olama-stores");?></th>'
            + '<th><?php esc_html_e("Warehouse","olama-stores");?></th>'
            + '<th><?php esc_html_e("Status","olama-stores");?></th>'
            + '</tr></thead><tbody>';

        withdrawals.forEach(function(w) {
            var date = new Date(w.assigned_date).toLocaleDateString();
            html += '<tr>'
                + '<td>' + date + '</td>'
                + '<td>' + w.student_name + '</td>'
                + '<td><strong>' + w.item_name + '</strong><br><small>' + w.sku + '</small></td>'
                + '<td>' + w.quantity_assigned + '</td>'
                + '<td>' + w.warehouse_name + '</td>'
                + '<td><span class="os-badge os-badge-' + w.status + '">' + w.status + '</span></td>'
                + '</tr>';
        });
        html += '</tbody></table>';
        $('#os-family-withdrawals-list').html(html);
    }

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
        
        window.osSearchItems('', $('#os-wd-item'));
        $('#os-withdraw-modal').find('.os-modal-item-search').val('');
        
        $('#os-withdraw-modal').show();
    });

    // Stock check helper
    $('#os-wd-item, #os-wd-warehouse').on('change', function(){
        var i = $('#os-wd-item').val(), w = $('#os-wd-warehouse').val();
        if(!i || !w){ $('#os-wd-stock-avail').text(''); return; }
        wp.apiFetch({ path: '/olama-stores/v1/stock?item_id='+i+'&warehouse_id='+w }).then(function(stock){
            var qty = stock.length ? stock[0].quantity_available : 0;
            $('#os-wd-stock-avail').text('<?php esc_html_e("Available:","olama-stores");?> ' + qty).css('color', qty > 0 ? 'green' : 'red');
        });
    });

    // Confirm
    $('#os-btn-confirm-wd').on('click', function(){
        var payload = {
            assignee_type: 'student',
            assignee_id: $('#os-wd-student-uid').val(),
            item_id: parseInt($('#os-wd-item').val()),
            warehouse_id: parseInt($('#os-wd-warehouse').val()),
            quantity_assigned: parseInt($('#os-wd-qty').val()),
            assigned_date: new Date().toISOString().split('T')[0],
            notes: $('#os-wd-notes').val(),
            academic_year_id: olamaStores.activeYearId
        };
        wp.apiFetch({ path: '/olama-stores/v1/assignments', method: 'POST', data: payload }).then(function(){
            alert('<?php esc_html_e("Withdrawal successful!","olama-stores");?>');
            $('#os-withdraw-modal').hide();
            // Refresh lookup to show new withdrawal
            $('#os-btn-family-lookup').click();
        }).catch(function(e){ alert(e.message); });
    });
})(jQuery);
</script>
