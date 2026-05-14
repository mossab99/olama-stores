<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap os-wrap" id="os-dashboard">
    <h1 class="os-page-title">
        <span class="dashicons dashicons-performance"></span>
        <?php esc_html_e( 'Warehouse Intelligence', 'olama-stores' ); ?>
        <span class="os-year-badge"><?php echo esc_html( os_get_active_year_name() ); ?></span>
    </h1>

    <!-- KPI Grid -->
    <div class="os-kpi-grid">
        <div class="os-kpi-card os-kpi-loading" id="os-kpi-total-items">
            <span class="os-kpi-icon dashicons dashicons-products"></span>
            <span class="os-kpi-value">0</span>
            <span class="os-kpi-label"><?php esc_html_e( 'Total Items', 'olama-stores' ); ?></span>
        </div>
        <div class="os-kpi-card os-kpi-loading os-kpi-warning" id="os-kpi-low-stock">
            <span class="os-kpi-icon dashicons dashicons-warning"></span>
            <span class="os-kpi-value">0</span>
            <span class="os-kpi-label"><?php esc_html_e( 'Low Stock', 'olama-stores' ); ?></span>
        </div>
        <div class="os-kpi-card os-kpi-loading os-kpi-success" id="os-kpi-active-assignments">
            <span class="os-kpi-icon dashicons dashicons-admin-users"></span>
            <span class="os-kpi-value">0</span>
            <span class="os-kpi-label"><?php esc_html_e( 'Active Assignments', 'olama-stores' ); ?></span>
        </div>
        <div class="os-kpi-card os-kpi-loading os-kpi-danger" id="os-kpi-pending-returns">
            <span class="os-kpi-icon dashicons dashicons-undo"></span>
            <span class="os-kpi-value">0</span>
            <span class="os-kpi-label"><?php esc_html_e( 'Overdue Returns', 'olama-stores' ); ?></span>
        </div>
    </div>

    <div class="os-dashboard-bottom">
        <div class="os-section-card">
            <div class="os-section-header">
                <h2><span class="dashicons dashicons-external"></span> <?php esc_html_e( 'Quick Operations', 'olama-stores' ); ?></h2>
            </div>
            <div class="os-quick-actions">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=olama-stores-items' ) ); ?>" class="button button-primary">
                    <span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add Item', 'olama-stores' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=olama-stores-stock' ) ); ?>" class="button">
                    <span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Receive Stock', 'olama-stores' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=olama-stores-assignments' ) ); ?>" class="button">
                    <span class="dashicons dashicons-assign"></span> <?php esc_html_e( 'Issue Items', 'olama-stores' ); ?>
                </a>
            </div>
        </div>

        <div class="os-section-card">
            <div class="os-section-header">
                <h2><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Recent Inventory Activity', 'olama-stores' ); ?></h2>
            </div>
            <div id="os-recent-movements-wrap">
                <span class="os-loading"><?php esc_html_e( 'Retrieving activity log…', 'olama-stores' ); ?></span>
            </div>
        </div>
    </div>
</div>
<script>
(function($){
    wp.apiFetch({ path: '/olama-stores/v1/reports/dashboard' }).then(function(data){
        $('#os-kpi-total-items .os-kpi-value').text(data.total_skus);
        $('#os-kpi-low-stock .os-kpi-value').text(data.low_stock_count);
        $('#os-kpi-active-assignments .os-kpi-value').text(data.active_assignments);
        $('#os-kpi-total-items, #os-kpi-low-stock, #os-kpi-active-assignments').removeClass('os-kpi-loading');

        var html = '<table class="wp-list-table widefat"><thead><tr>'
            + '<th><?php esc_html_e("Date","olama-stores");?></th>'
            + '<th><?php esc_html_e("Item","olama-stores");?></th>'
            + '<th><?php esc_html_e("Type","olama-stores");?></th>'
            + '<th><?php esc_html_e("Qty","olama-stores");?></th>'
            + '<th><?php esc_html_e("Warehouse","olama-stores");?></th>'
            + '<th><?php esc_html_e("By","olama-stores");?></th>'
            + '</tr></thead><tbody>';
        if(!data.recent_movements.length){
            html += '<tr><td colspan="6"><?php esc_html_e("No recent activity found.","olama-stores");?></td></tr>';
        } else {
            data.recent_movements.forEach(function(m){
                html += '<tr><td>' + m.performed_at + '</td><td>' + (m.item_name||'') + ' <small>'+m.sku+'</small></td>'
                     + '<td><span class="os-badge os-badge-'+m.movement_type+'">' + m.movement_type.replace(/_/g,' ') + '</span></td>'
                     + '<td>' + m.quantity + '</td><td>' + (m.warehouse_name||'') + '</td><td>' + (m.performed_by_name||'') + '</td></tr>';
            });
        }
        html += '</tbody></table>';
        $('#os-recent-movements-wrap').html(html);
    }).catch(function(e){ $('#os-recent-movements-wrap').html('<p class="os-error">Error loading data.</p>'); });
})(jQuery);
</script>
