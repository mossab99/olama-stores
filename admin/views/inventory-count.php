<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap os-wrap" id="os-inventory-count-page">
    <h1 class="os-page-title">
        <span class="dashicons dashicons-clipboard"></span>
        <?php esc_html_e( 'Physical Inventory Count', 'olama-stores' ); ?>
        <span class="os-year-badge"><?php echo esc_html( os_get_active_year_name() ); ?></span>
    </h1>

    <p class="description"><?php esc_html_e( 'Load the current system stock quantities, enter your physically counted quantities, then post the count. Any differences will generate adjustment movements automatically.', 'olama-stores' ); ?></p>

    <div class="os-filters tablenav top" style="display:flex; gap:12px; align-items:center; margin-bottom:20px;">
        <label style="font-weight:600;"><?php esc_html_e( 'Warehouse', 'olama-stores' ); ?>:</label>
        <select id="os-ic-warehouse" style="min-width:200px;">
            <option value=""><?php esc_html_e( 'All Warehouses', 'olama-stores' ); ?></option>
        </select>
        <button class="button button-primary" id="os-ic-load">
            <span class="dashicons dashicons-update" style="margin-top:3px;"></span>
            <?php esc_html_e( 'Load Items', 'olama-stores' ); ?>
        </button>
        <span id="os-ic-status" style="color:#666;"></span>
    </div>

    <div id="os-ic-table-wrap" style="display:none;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <strong id="os-ic-count-info"></strong>
            <div style="display:flex; gap:8px;">
                <button class="button" id="os-ic-fill-zeros"><?php esc_html_e( 'Set all blank → 0', 'olama-stores' ); ?></button>
                <button class="button button-primary" id="os-ic-post-count">
                    <span class="dashicons dashicons-yes-alt" style="margin-top:3px;"></span>
                    <?php esc_html_e( 'Post Count', 'olama-stores' ); ?>
                </button>
            </div>
        </div>

        <table class="wp-list-table widefat striped" id="os-ic-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Item', 'olama-stores' ); ?></th>
                    <th><?php esc_html_e( 'SKU', 'olama-stores' ); ?></th>
                    <th><?php esc_html_e( 'Warehouse', 'olama-stores' ); ?></th>
                    <th style="text-align:center;"><?php esc_html_e( 'System Qty', 'olama-stores' ); ?></th>
                    <th style="text-align:center;"><?php esc_html_e( 'Counted Qty', 'olama-stores' ); ?></th>
                    <th style="text-align:center;"><?php esc_html_e( 'Variance', 'olama-stores' ); ?></th>
                </tr>
            </thead>
            <tbody id="os-ic-body">
            </tbody>
        </table>

        <div style="margin-top:16px;">
            <label><strong><?php esc_html_e( 'Count Notes (applied to all adjustments):', 'olama-stores' ); ?></strong></label>
            <textarea id="os-ic-notes" style="width:100%; margin-top:6px;" rows="2" placeholder="<?php esc_attr_e( 'e.g. End of year physical count — June 2025', 'olama-stores' ); ?>"></textarea>
        </div>
        <div style="margin-top:12px; display:flex; gap:8px; justify-content:flex-end;">
            <button class="button button-primary button-hero" id="os-ic-post-count-footer">
                <span class="dashicons dashicons-yes-alt" style="margin-top:5px;"></span>
                <?php esc_html_e( 'Post Physical Count', 'olama-stores' ); ?>
            </button>
        </div>
    </div>

    <!-- Progress overlay -->
    <div id="os-ic-posting-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,.5); z-index:9999; display:none; align-items:center; justify-content:center;">
        <div style="background:#fff; padding:40px; border-radius:8px; text-align:center; min-width:300px;">
            <span class="dashicons dashicons-update os-spin" style="font-size:40px; color:var(--os-primary);"></span>
            <p style="margin-top:16px; font-size:1.1em;" id="os-ic-posting-msg"><?php esc_html_e( 'Posting count…', 'olama-stores' ); ?></p>
        </div>
    </div>
</div>

<script>
(function($){
    var icItems = []; // loaded count rows

    // Load warehouses
    wp.apiFetch({ path: '/olama-stores/v1/warehouses' }).then(function(whs){
        var opts = '<option value=""><?php esc_html_e( "All Warehouses", "olama-stores" ); ?></option>';
        whs.forEach(function(w){ opts += '<option value="'+w.id+'">'+w.name+'</option>'; });
        $('#os-ic-warehouse').html(opts);
    });

    // Load items for count
    $('#os-ic-load').on('click', function(){
        var whId = $('#os-ic-warehouse').val();
        var path = '/olama-stores/v1/reports/inventory-count' + (whId ? '?warehouse_id='+whId : '');
        $('#os-ic-status').text('<?php esc_html_e( "Loading…", "olama-stores" ); ?>');
        $('#os-ic-table-wrap').hide();
        wp.apiFetch({ path: path }).then(function(rows){
            icItems = rows;
            $('#os-ic-count-info').text(rows.length + ' <?php esc_html_e( "items to count", "olama-stores" ); ?>');
            var html = '';
            rows.forEach(function(r, idx){
                html += '<tr id="os-ic-row-'+idx+'">'
                    + '<td><strong>' + r.name + '</strong></td>'
                    + '<td><code>' + r.sku + '</code></td>'
                    + '<td>' + r.warehouse_name + '</td>'
                    + '<td style="text-align:center;">' + r.system_qty + '</td>'
                    + '<td style="text-align:center;"><input type="number" class="os-ic-counted" data-idx="'+idx+'" data-system="'+r.system_qty+'" min="0" style="width:80px; text-align:center;" placeholder="—"></td>'
                    + '<td class="os-ic-variance" id="os-ic-var-'+idx+'" style="text-align:center; font-weight:600;">—</td>'
                    + '</tr>';
            });
            $('#os-ic-body').html(html);
            $('#os-ic-table-wrap').show();
            $('#os-ic-status').text('');
        }).catch(function(e){
            $('#os-ic-status').text('<?php esc_html_e( "Error loading items.", "olama-stores" ); ?>');
        });
    });

    // Live variance calculation
    $(document).on('input', '.os-ic-counted', function(){
        var $inp  = $(this);
        var idx   = $inp.data('idx');
        var sys   = parseInt($inp.data('system')) || 0;
        var cnt   = $inp.val() !== '' ? parseInt($inp.val()) : null;
        var $cell = $('#os-ic-var-' + idx);

        if (cnt === null || isNaN(cnt)) {
            $cell.text('—').css('color','');
            return;
        }
        var diff = cnt - sys;
        $cell.text((diff >= 0 ? '+' : '') + diff).css('color', diff === 0 ? '#22c55e' : (diff < 0 ? '#d63638' : '#f59e0b'));
    });

    // Fill all blank counted inputs with 0
    $('#os-ic-fill-zeros').on('click', function(){
        $('.os-ic-counted').each(function(){
            if ($(this).val() === '') {
                $(this).val(0).trigger('input');
            }
        });
    });

    // Post count — generates adjustment movements for any variance ≠ 0
    function postCount() {
        var notes = $('#os-ic-notes').val() || '<?php esc_js( esc_html__( 'Physical inventory count', 'olama-stores' ) ); ?>';
        var adjustments = [];

        $('.os-ic-counted').each(function(){
            var $inp  = $(this);
            var idx   = parseInt($inp.data('idx'));
            var sys   = parseInt($inp.data('system')) || 0;
            var cnt   = $inp.val() !== '' ? parseInt($inp.val()) : null;

            if (cnt === null || isNaN(cnt) || cnt === sys) return; // skip unchanged / blank

            var row = icItems[idx];
            adjustments.push({
                item_id:      row.item_id,
                warehouse_id: row.warehouse_id,
                quantity:     cnt - sys, // signed delta
                notes:        notes,
                academic_year_id: olamaStores.activeYearId
            });
        });

        if (!adjustments.length) {
            alert('<?php esc_html_e( "No variances to post. All counted quantities match system quantities.", "olama-stores" ); ?>');
            return;
        }

        if (!confirm('<?php esc_html_e( "Post count? This will create adjustment movements for all variances. This cannot be undone.", "olama-stores" ); ?>')) return;

        $('#os-ic-posting-overlay').css('display','flex');
        var posted = 0;
        var errors = 0;

        function postNext(i) {
            if (i >= adjustments.length) {
                $('#os-ic-posting-overlay').hide();
                var msg = '<?php esc_html_e( "Count posted!", "olama-stores" ); ?> ' + posted + ' <?php esc_html_e( "adjustments applied.", "olama-stores" ); ?>';
                if (errors) msg += ' ' + errors + ' <?php esc_html_e( "errors.", "olama-stores" ); ?>';
                alert(msg);
                $('#os-ic-load').click(); // reload
                return;
            }
            $('#os-ic-posting-msg').text('<?php esc_html_e( "Posting", "olama-stores" ); ?> ' + (i+1) + ' / ' + adjustments.length + '…');
            var adj = adjustments[i];
            wp.apiFetch({ path: '/olama-stores/v1/stock/adjust', method: 'POST', data: adj }).then(function(){
                posted++;
            }).catch(function(){ errors++; }).then(function(){ postNext(i+1); });
        }
        postNext(0);
    }

    $('#os-ic-post-count, #os-ic-post-count-footer').on('click', postCount);

})(jQuery);
</script>

<style>
.os-spin { animation: os-spin-kf .8s linear infinite; display:inline-block; }
@keyframes os-spin-kf { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
#os-ic-table td, #os-ic-table th { padding: 10px 12px !important; }
</style>
