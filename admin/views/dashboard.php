<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap os-wrap" id="os-dashboard">
    <h1 class="os-page-title">
        <div class="os-title-left">
            <span class="dashicons dashicons-performance"></span>
            <?php esc_html_e( 'Warehouse Intelligence', 'olama-stores' ); ?>
            <span class="os-year-badge"><?php echo esc_html( os_get_active_year_name() ); ?></span>
        </div>
        <?php if ( OS_Roles::can( 'os_run_inventory_count' ) ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=olama-stores-stock' ) ); ?>" class="page-title-action">
                <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Inventory Count', 'olama-stores' ); ?>
            </a>
        <?php endif; ?>
    </h1>

    <!-- REC-13: Expanded 5-card KPI Grid (REC-09 adds inventory value) -->
    <div class="os-kpi-grid os-kpi-grid-5">
        <div class="os-kpi-card os-kpi-loading" id="os-kpi-total-items" title="<?php esc_attr_e( 'View all items', 'olama-stores' ); ?>">
            <div class="os-kpi-header">
                <span class="os-kpi-icon dashicons dashicons-products"></span>
                <span class="os-kpi-label"><?php esc_html_e( 'Total SKUs', 'olama-stores' ); ?></span>
            </div>
            <a class="os-kpi-value" href="<?php echo esc_url( admin_url( 'admin.php?page=olama-stores-items' ) ); ?>">—</a>
        </div>
        <div class="os-kpi-card os-kpi-loading os-kpi-warning" id="os-kpi-low-stock" title="<?php esc_attr_e( 'View low stock items', 'olama-stores' ); ?>">
            <div class="os-kpi-header">
                <span class="os-kpi-icon dashicons dashicons-warning"></span>
                <span class="os-kpi-label"><?php esc_html_e( 'Low stock', 'olama-stores' ); ?></span>
            </div>
            <a class="os-kpi-value" href="<?php echo esc_url( admin_url( 'admin.php?page=olama-stores-stock&filter=low_stock' ) ); ?>">—</a>
        </div>
        <div class="os-kpi-card os-kpi-loading os-kpi-success" id="os-kpi-active-assignments" title="<?php esc_attr_e( 'View active custody records', 'olama-stores' ); ?>">
            <div class="os-kpi-header">
                <span class="os-kpi-icon dashicons dashicons-admin-users"></span>
                <span class="os-kpi-label"><?php esc_html_e( 'Active assignments', 'olama-stores' ); ?></span>
            </div>
            <a class="os-kpi-value" href="<?php echo esc_url( admin_url( 'admin.php?page=olama-stores-assignments&status=active' ) ); ?>">—</a>
        </div>
        <div class="os-kpi-card os-kpi-loading os-kpi-danger" id="os-kpi-pending-returns" title="<?php esc_attr_e( 'View overdue returns', 'olama-stores' ); ?>">
            <div class="os-kpi-header">
                <span class="os-kpi-icon dashicons dashicons-undo"></span>
                <span class="os-kpi-label"><?php esc_html_e( 'Overdue returns', 'olama-stores' ); ?></span>
            </div>
            <a class="os-kpi-value" href="<?php echo esc_url( admin_url( 'admin.php?page=olama-stores-assignments&status=active' ) ); ?>">—</a>
        </div>
        <!-- REC-09: Inventory value KPI -->
        <div class="os-kpi-card os-kpi-loading os-kpi-info" id="os-kpi-inventory-value" title="<?php esc_attr_e( 'Total stock value', 'olama-stores' ); ?>">
            <div class="os-kpi-header">
                <span class="os-kpi-icon dashicons dashicons-money-alt"></span>
                <span class="os-kpi-label"><?php esc_html_e( 'Inventory value', 'olama-stores' ); ?></span>
            </div>
            <span class="os-kpi-value" style="cursor:default;">—</span>
        </div>
    </div>

    <!-- Stacked dashboard bottom layout -->
    <div class="os-dashboard-bottom">

        <!-- Quick Operations -->
        <div class="os-section-card" style="margin-bottom:20px;">
            <div class="os-section-header">
                <h2><span class="dashicons dashicons-menu-alt3"></span> <?php esc_html_e( 'Quick Operations', 'olama-stores' ); ?></h2>
            </div>
            <div class="os-quick-actions">
                <?php if ( OS_Roles::can( 'os_manage_items' ) ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=olama-stores-items' ) ); ?>" class="button">
                    <span class="dashicons dashicons-plus"></span> <span><?php esc_html_e( 'Add Item', 'olama-stores' ); ?></span>
                </a>
                <?php endif; ?>
                <?php if ( OS_Roles::can( 'os_receive_stock' ) ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=olama-stores-stock' ) ); ?>" class="button">
                    <span class="dashicons dashicons-download"></span> <span><?php esc_html_e( 'Receive Shipment', 'olama-stores' ); ?></span>
                </a>
                <?php endif; ?>
                <?php if ( OS_Roles::can( 'os_process_assignments' ) ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=olama-stores-assignments' ) ); ?>" class="button">
                    <span class="dashicons dashicons-admin-users"></span> <span><?php esc_html_e( 'Issue Items', 'olama-stores' ); ?></span>
                </a>
                <?php endif; ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=olama-stores-reports' ) ); ?>" class="button">
                    <span class="dashicons dashicons-chart-area"></span> <span><?php esc_html_e( 'Reports', 'olama-stores' ); ?></span>
                </a>
            </div>
        </div>

        <!-- Recent Inventory Activity -->
        <div class="os-section-card" style="margin-bottom:20px;">
            <div class="os-section-header">
                <h2><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Recent Inventory Activity', 'olama-stores' ); ?></h2>
            </div>
            <div id="os-recent-movements-wrap">
                <span class="os-loading"><?php esc_html_e( 'Retrieving activity log…', 'olama-stores' ); ?></span>
            </div>
        </div>

        <!-- Top Items by Value -->
        <div class="os-section-card">
            <div class="os-section-header">
                <h2><i class="ti ti-trophy" aria-hidden="true"></i> <?php esc_html_e( 'Top Items by Value', 'olama-stores' ); ?></h2>
            </div>
            <div id="os-top-items-wrap">
                <span class="os-loading"><?php esc_html_e( 'Loading…', 'olama-stores' ); ?></span>
            </div>
        </div>

    </div>
</div>
<script>
(function($){
    // REC-15: Human-readable movement type labels
    var OS_MOVEMENT_LABELS = {
        purchase_receipt:  '<?php esc_html_e( 'Stock Receipt', 'olama-stores' ); ?>',
        opening_balance:   '<?php esc_html_e( 'Opening Balance', 'olama-stores' ); ?>',
        adjustment_add:    '<?php esc_html_e( 'Adjustment (+)', 'olama-stores' ); ?>',
        adjustment_sub:    '<?php esc_html_e( 'Adjustment (−)', 'olama-stores' ); ?>',
        issue_employee:    '<?php esc_html_e( 'Issued to Employee', 'olama-stores' ); ?>',
        return_employee:   '<?php esc_html_e( 'Returned by Employee', 'olama-stores' ); ?>',
        issue_student:     '<?php esc_html_e( 'Issued to Student', 'olama-stores' ); ?>',
        reverse_student:   '<?php esc_html_e( 'Withdrawal Reversed', 'olama-stores' ); ?>',
        transfer_out:      '<?php esc_html_e( 'Transfer Out', 'olama-stores' ); ?>',
        transfer_in:       '<?php esc_html_e( 'Transfer In', 'olama-stores' ); ?>',
        inventory_count:   '<?php esc_html_e( 'Inventory Count', 'olama-stores' ); ?>',
        damage_loss:       '<?php esc_html_e( 'Damage / Loss', 'olama-stores' ); ?>',
    };
    function movementLabel(type) {
        return OS_MOVEMENT_LABELS[type] || type.replace(/_/g, ' ');
    }

    // ── Load dashboard KPIs (REC-01, REC-02, REC-09) ─────────────────────────
    wp.apiFetch({ path: '/olama-stores/v1/reports/dashboard' }).then(function(data){
        $('#os-kpi-total-items .os-kpi-value').text(data.total_skus);
        $('#os-kpi-low-stock .os-kpi-value').text(data.low_stock_count);
        $('#os-kpi-active-assignments .os-kpi-value').text(data.active_assignments);
        $('#os-kpi-pending-returns .os-kpi-value').text(data.pending_returns || 0);

        // REC-09: Inventory value — format as currency
        var val = parseFloat(data.inventory_value || 0);
        var formatted = val > 0
            ? val.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })
            : '—';
        $('#os-kpi-inventory-value .os-kpi-value').text(formatted);

        // Remove loading state from all 5 cards
        $('#os-kpi-total-items, #os-kpi-low-stock, #os-kpi-active-assignments, #os-kpi-pending-returns, #os-kpi-inventory-value')
            .removeClass('os-kpi-loading');

        // ── Recent Movements table ────────────────────────────────────────────
        var html = '<table class="wp-list-table widefat"><thead><tr>'
            + '<th><?php esc_html_e("Date","olama-stores");?></th>'
            + '<th><?php esc_html_e("Item","olama-stores");?></th>'
            + '<th><?php esc_html_e("Type","olama-stores");?></th>'
            + '<th><?php esc_html_e("Qty","olama-stores");?></th>'
            + '<th><?php esc_html_e("By","olama-stores");?></th>'
            + '</tr></thead><tbody>';
        if (!data.recent_movements || !data.recent_movements.length) {
            html += '<tr><td colspan="5"><?php esc_html_e("No recent activity found.","olama-stores");?></td></tr>';
        } else {
            data.recent_movements.forEach(function(m){
                html += '<tr><td>' + m.performed_at + '</td>'
                     + '<td><div class="os-activity-item-wrap">' + (m.item_name||'') + ' <small><code>' + (m.sku||'') + '</code></small></div></td>'
                     + '<td><span class="os-badge os-badge-' + m.movement_type + '">' + movementLabel(m.movement_type) + '</span></td>'
                     + '<td>' + m.quantity + '</td>'
                     + '<td>' + (m.performed_by_name||'') + '</td></tr>';
            });
        }
        html += '</tbody></table>';
        $('#os-recent-movements-wrap').html(html);

    }).catch(function(){ $('#os-recent-movements-wrap').html('<p class="os-error"><?php esc_html_e("Error loading data.","olama-stores");?></p>'); });

    // ── REC-13: Top Items by Value ────────────────────────────────────────────
    wp.apiFetch({ path: '/olama-stores/v1/reports/dashboard/top-items' }).then(function(rows){
        if (!rows || !rows.length) {
            $('#os-top-items-wrap').html('<p><?php esc_html_e("No items with cost data yet.","olama-stores");?></p>');
            return;
        }
        var html = '<table class="wp-list-table widefat"><thead><tr>'
            + '<th><?php esc_html_e("Item","olama-stores");?></th>'
            + '<th style="text-align:right;"><?php esc_html_e("On Hand","olama-stores");?></th>'
            + '<th style="text-align:right;"><?php esc_html_e("Value","olama-stores");?></th>'
            + '</tr></thead><tbody>';
        rows.forEach(function(r, i){
            var barPct = rows[0].total_value > 0 ? Math.round((r.total_value / rows[0].total_value) * 100) : 0;
            var val = parseFloat(r.total_value || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            html += '<tr><td>'
                + '<div style="display:flex;align-items:center;gap:8px;width:100%;overflow:hidden;box-sizing:border-box;">'
                + '<span style="width:24px;height:24px;border-radius:50%;background:hsl('+(200+i*20)+',70%,52%);display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.7em;font-weight:600;flex-shrink:0;">'+(i+1)+'</span>'
                + '<div style="display:flex;align-items:baseline;gap:6px;overflow:hidden;flex:1;min-width:0;white-space:nowrap;">'
                + '<strong style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500;">' + r.name + '</strong>'
                + '<span style="font-size:10px;color:#64748b;font-family:monospace;flex-shrink:0;">' + r.sku + '</span>'
                + '</div>'
                + '</div>'
                + '<div style="margin-top:4px;height:4px;border-radius:2px;background:#e2e8f0;overflow:hidden;margin-left:32px;">'
                + '<div style="height:100%;width:'+barPct+'%;background:hsl('+(200+i*20)+',70%,52%);border-radius:2px;transition:width .4s;"></div>'
                + '</div></td>'
                + '<td style="text-align:right;vertical-align:top;">' + parseInt(r.total_qty) + '</td>'
                + '<td style="text-align:right;vertical-align:top;font-weight:600;">' + val + '</td></tr>';
        });
        html += '</tbody></table>';
        $('#os-top-items-wrap').html(html);
    }).catch(function(){
        $('#os-top-items-wrap').html('<p><?php esc_html_e("Could not load top items.","olama-stores");?></p>');
    });

})(jQuery);
</script>
<style>
/* REC-13: 5-column KPI grid */
.os-kpi-grid-5 { grid-template-columns: repeat(5, 1fr) !important; }
@media (max-width: 1200px) { .os-kpi-grid-5 { grid-template-columns: repeat(3, 1fr) !important; } }
@media (max-width: 700px)  { .os-kpi-grid-5 { grid-template-columns: 1fr 1fr !important; } }

/* REC-09: Inventory value KPI card — teal/info accent */
#os-kpi-inventory-value { --os-kpi-accent: #0891b2; }

/* Stacked bottom layout */
.os-dashboard-bottom {
    display: flex;
    flex-direction: column;
    gap: 20px;
    width: 100%;
}
</style>
