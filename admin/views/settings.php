<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap os-wrap" id="os-settings-page">
    <h1 class="os-page-title">
        <span class="dashicons dashicons-admin-settings"></span>
        <?php esc_html_e( 'Olama Stores Settings', 'olama-stores' ); ?>
    </h1>

        <a href="#tab-general"    class="nav-tab nav-tab-active" data-tab="general"><?php esc_html_e( 'General', 'olama-stores' ); ?></a>
        <a href="#tab-warehouses" class="nav-tab" data-tab="warehouses"><?php esc_html_e( 'Warehouses', 'olama-stores' ); ?></a>
        <a href="#tab-categories" class="nav-tab" data-tab="categories"><?php esc_html_e( 'Categories', 'olama-stores' ); ?></a>
        <a href="#tab-units" class="nav-tab" data-tab="units"><?php esc_html_e( 'Units', 'olama-stores' ); ?></a>
        <a href="#tab-providers" class="nav-tab" data-tab="providers"><?php esc_html_e( 'Providers', 'olama-stores' ); ?></a>
        <a href="#tab-custom-models" class="nav-tab" data-tab="custom-models"><?php esc_html_e( 'Custom Models', 'olama-stores' ); ?></a>
        <a href="#tab-fabrics" class="nav-tab" data-tab="fabrics"><?php esc_html_e( 'Fabrics', 'olama-stores' ); ?></a>
        <a href="#tab-colors" class="nav-tab" data-tab="colors"><?php esc_html_e( 'Colors', 'olama-stores' ); ?></a>
        <a href="#tab-sizes" class="nav-tab" data-tab="sizes"><?php esc_html_e( 'Sizes', 'olama-stores' ); ?></a>
        <a href="#tab-integration" class="nav-tab" data-tab="integration"><?php esc_html_e( 'School Integration', 'olama-stores' ); ?></a>
    </div>

    <!-- General Tab -->
    <div id="tab-general" class="os-tab-content">
        <form method="post" action="options.php">
            <?php settings_fields( 'os_settings' ); ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'SKU Prefix', 'olama-stores' ); ?></th>
                    <td><input type="text" name="os_sku_prefix" value="<?php echo esc_attr( get_option( 'os_sku_prefix', 'SKU' ) ); ?>"></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Low Stock Email Alerts', 'olama-stores' ); ?></th>
                    <td><label><input type="checkbox" name="os_low_stock_email" value="1" <?php checked( get_option( 'os_low_stock_email' ), '1' ); ?>> <?php esc_html_e( 'Send daily email when items fall below minimum stock', 'olama-stores' ); ?></label></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php
        // Register settings (simple inline approach)
        add_action( 'admin_init', function () {
            register_setting( 'os_settings', 'os_sku_prefix', array( 'sanitize_callback' => 'sanitize_text_field' ) );
            register_setting( 'os_settings', 'os_low_stock_email', array( 'sanitize_callback' => 'absint' ) );
        } );
        ?>
    </div>

    <!-- Warehouses Tab -->
    <div id="tab-warehouses" class="os-tab-content" style="display:none;">
        <h2><?php esc_html_e( 'Warehouses', 'olama-stores' ); ?></h2>
        <button type="button" class="button button-primary" id="os-btn-add-warehouse"><?php esc_html_e( '+ Add Warehouse', 'olama-stores' ); ?></button>
        <div id="os-warehouses-list" style="margin-top:16px;">
            <span class="os-loading"><?php esc_html_e( 'Loading…', 'olama-stores' ); ?></span>
        </div>

        <div id="os-warehouse-modal" class="os-modal" style="display:none;">
            <div class="os-modal-content">
                <h2 id="os-wh-modal-title"><?php esc_html_e( 'Add Warehouse', 'olama-stores' ); ?></h2>
                <input type="hidden" id="os-wh-id" value="">
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Name', 'olama-stores' ); ?></label>
                    <input type="text" id="os-wh-name">
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Arabic Name', 'olama-stores' ); ?></label>
                    <input type="text" id="os-wh-name-ar" dir="rtl">
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Location', 'olama-stores' ); ?></label>
                    <input type="text" id="os-wh-location">
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Warehouse Type', 'olama-stores' ); ?></label>
                    <select id="os-wh-type">
                        <option value="items"><?php esc_html_e( 'General Items', 'olama-stores' ); ?></option>
                        <option value="custom"><?php esc_html_e( 'School Custom', 'olama-stores' ); ?></option>
                        <option value="books"><?php esc_html_e( 'Books', 'olama-stores' ); ?></option>
                    </select>
                </div>
                <div class="os-form-actions">
                    <button type="button" class="button button-primary" id="os-wh-save"><?php esc_html_e( 'Save', 'olama-stores' ); ?></button>
                    <button type="button" class="button os-modal-close"><?php esc_html_e( 'Cancel', 'olama-stores' ); ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Tab (Global) -->
    <div id="tab-categories" class="os-tab-content" style="display:none;">
        <h2><?php esc_html_e( 'Categories', 'olama-stores' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Unified categories used for both Inventory and Academic Evaluations.', 'olama-stores' ); ?></p>
        <div id="os-categories-manager">
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input type="text" id="os-new-cat-name" placeholder="<?php esc_attr_e('Category Name', 'olama-stores');?>" style="flex: 1;">
                <button type="button" class="button button-primary" id="os-btn-add-cat"><?php esc_html_e('Add Category', 'olama-stores');?></button>
            </div>
            <div id="os-categories-list">
                <span class="os-loading"><?php esc_html_e( 'Loading…', 'olama-stores' ); ?></span>
            </div>
        </div>
    </div>

    <!-- Units Tab (Global) -->
    <div id="tab-units" class="os-tab-content" style="display:none;">
        <h2><?php esc_html_e( 'Units', 'olama-stores' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Unified units used for both Measurement and Curriculum Units.', 'olama-stores' ); ?></p>
        <div id="os-units-manager">
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input type="text" id="os-new-unit-name" placeholder="<?php esc_attr_e('Unit Name (e.g. Piece)', 'olama-stores');?>" style="flex: 2;">
                <input type="text" id="os-new-unit-symbol" placeholder="<?php esc_attr_e('Symbol (e.g. pc)', 'olama-stores');?>" style="flex: 1;">
                <button type="button" class="button button-primary" id="os-btn-add-unit"><?php esc_html_e('Add Unit', 'olama-stores');?></button>
            </div>
            <div id="os-units-list">
                <span class="os-loading"><?php esc_html_e( 'Loading…', 'olama-stores' ); ?></span>
            </div>
        </div>
    </div>

    <!-- Providers Tab -->
    <div id="tab-providers" class="os-tab-content" style="display:none;">
        <h2><?php esc_html_e( 'Providers', 'olama-stores' ); ?></h2>
        <button type="button" class="button button-primary" id="os-btn-add-provider"><?php esc_html_e( '+ Add Provider', 'olama-stores' ); ?></button>
        <div id="os-providers-list" style="margin-top:16px;">
            <span class="os-loading"><?php esc_html_e( 'Loading…', 'olama-stores' ); ?></span>
        </div>

        <div id="os-provider-modal" class="os-modal" style="display:none;">
            <div class="os-modal-content">
                <h2 id="os-provider-modal-title"><?php esc_html_e( 'Add Provider', 'olama-stores' ); ?></h2>
                <input type="hidden" id="os-provider-id" value="">
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Company Name', 'olama-stores' ); ?> *</label>
                    <input type="text" id="os-provider-name" required>
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Mobile Contact', 'olama-stores' ); ?></label>
                    <input type="text" id="os-provider-mobile">
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Location', 'olama-stores' ); ?></label>
                    <input type="text" id="os-provider-location">
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Contact Person', 'olama-stores' ); ?></label>
                    <input type="text" id="os-provider-person">
                </div>
                <div class="os-form-row">
                    <label><?php esc_html_e( 'Active Status', 'olama-stores' ); ?></label>
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="os-provider-active" style="width: auto !important; height: auto !important; margin: 0 !important;"> 
                        <span><?php esc_html_e( 'Active Provider (Used for cost calculations)', 'olama-stores' ); ?></span>
                    </label>
                </div>
                <div class="os-form-actions">
                    <button type="button" class="button button-primary" id="os-provider-save"><?php esc_html_e( 'Save', 'olama-stores' ); ?></button>
                    <button type="button" class="button os-modal-close"><?php esc_html_e( 'Cancel', 'olama-stores' ); ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Integration Tab -->
    <div id="tab-integration" class="os-tab-content" style="display:none;">
        <h2><?php esc_html_e( 'Olama School Integration', 'olama-stores' ); ?></h2>
        <?php if ( class_exists( 'Olama_School_DB' ) ): ?>
            <div class="notice notice-success inline"><p>
                <strong><?php esc_html_e( 'Connected', 'olama-stores' ); ?></strong>
                — <?php printf( esc_html__( 'Olama School System %s is active.', 'olama-stores' ), esc_html( OLAMA_SCHOOL_VERSION ) ); ?>
            </p></div>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Active Academic Year', 'olama-stores' ); ?></th>
                    <td><strong><?php echo esc_html( os_get_active_year_name() ); ?></strong>
                        (ID: <?php echo esc_html( os_get_active_year_id() ); ?>)
                        <br><small><?php esc_html_e( 'Olama Stores uses this year ID as the default academic_year_id for all new records.', 'olama-stores' ); ?></small>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Employee Source', 'olama-stores' ); ?></th>
                    <td><?php esc_html_e( 'Olama_School_Teacher::get_teachers() — WordPress users with school staff roles.', 'olama-stores' ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Student Source', 'olama-stores' ); ?></th>
                    <td><?php
                        $count = class_exists( 'Olama_School_Student' ) ? count( Olama_School_Student::get_students( array( 'academic_year_id' => os_get_active_year_id() ) ) ) : 0;
                        printf( esc_html__( 'Olama_School_Student::get_students() — %d students enrolled in current year.', 'olama-stores' ), $count );
                    ?></td>
                </tr>
            </table>
        <?php else: ?>
            <div class="notice notice-warning inline"><p>
                <?php esc_html_e( 'Olama School System is not active. Employee and student data will not be available for assignments.', 'olama-stores' ); ?>
            </p></div>
        <?php endif; ?>
    </div>

    <!-- Custom Models Tab -->
    <div id="tab-custom-models" class="os-tab-content" style="display:none;">
        <h2><?php esc_html_e( 'Custom Models', 'olama-stores' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Manage custom models that can be assigned to school items.', 'olama-stores' ); ?></p>
        <div id="os-custom-models-manager">
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input type="text" id="os-new-model-name" placeholder="<?php esc_attr_e('Model Name', 'olama-stores');?>" style="flex: 1;">
                <button type="button" class="button button-primary" id="os-btn-add-model"><?php esc_html_e('Add Model', 'olama-stores');?></button>
            </div>
            <div id="os-custom-models-list">
                <span class="os-loading"><?php esc_html_e( 'Loading…', 'olama-stores' ); ?></span>
            </div>
        </div>
    </div>

    <!-- Fabrics Tab -->
    <div id="tab-fabrics" class="os-tab-content" style="display:none;">
        <h2><?php esc_html_e( 'Fabrics', 'olama-stores' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Manage fabric types that can be assigned to school items.', 'olama-stores' ); ?></p>
        <div id="os-fabrics-manager">
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input type="text" id="os-new-fabric-name" placeholder="<?php esc_attr_e('Fabric Name', 'olama-stores');?>" style="flex: 1;">
                <button type="button" class="button button-primary" id="os-btn-add-fabric"><?php esc_html_e('Add Fabric', 'olama-stores');?></button>
            </div>
            <div id="os-fabrics-list">
                <span class="os-loading"><?php esc_html_e( 'Loading…', 'olama-stores' ); ?></span>
            </div>
        </div>
    </div>

    <!-- Colors Tab -->
    <div id="tab-colors" class="os-tab-content" style="display:none;">
        <h2><?php esc_html_e( 'Colors', 'olama-stores' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Manage colors that can be assigned to school items.', 'olama-stores' ); ?></p>
        <div id="os-colors-manager">
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input type="text" id="os-new-color-name" placeholder="<?php esc_attr_e('Color Name', 'olama-stores');?>" style="flex: 1;">
                <button type="button" class="button button-primary" id="os-btn-add-color"><?php esc_html_e('Add Color', 'olama-stores');?></button>
            </div>
            <div id="os-colors-list">
                <span class="os-loading"><?php esc_html_e( 'Loading…', 'olama-stores' ); ?></span>
            </div>
        </div>
    </div>

    <!-- Sizes Tab -->
    <div id="tab-sizes" class="os-tab-content" style="display:none;">
        <h2><?php esc_html_e( 'Sizes', 'olama-stores' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Manage sizes that can be assigned to school items.', 'olama-stores' ); ?></p>
        <div id="os-sizes-manager">
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input type="text" id="os-new-size-name" placeholder="<?php esc_attr_e('Size Name/Number', 'olama-stores');?>" style="flex: 1;">
                <button type="button" class="button button-primary" id="os-btn-add-size"><?php esc_html_e('Add Size', 'olama-stores');?></button>
            </div>
            <div id="os-sizes-list">
                <span class="os-loading"><?php esc_html_e( 'Loading…', 'olama-stores' ); ?></span>
            </div>
        </div>
    </div>
</div>

<script>
(function($){
    // Tab switching
    $('.nav-tab').on('click', function(e){
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.os-tab-content').hide();
        $('#tab-'+$(this).data('tab')).show();
        if($(this).data('tab')==='warehouses') loadWarehouses();
        if($(this).data('tab')==='categories') loadCategories();
        if($(this).data('tab')==='units') loadUnits();
        if($(this).data('tab')==='providers') loadProviders();
        if($(this).data('tab')==='custom-models') loadCustomModels();
        if($(this).data('tab')==='fabrics') loadFabrics();
        if($(this).data('tab')==='colors') loadColors();
        if($(this).data('tab')==='sizes') loadSizes();
    });
    function loadWarehouses(){
        wp.apiFetch({ path:'/olama-stores/v1/warehouses' }).then(function(rows){
            var html='<table class="wp-list-table widefat"><thead><tr><th>ID</th><th><?php esc_html_e("Name","olama-stores");?></th><th><?php esc_html_e("Location","olama-stores");?></th><th><?php esc_html_e("Type","olama-stores");?></th><th><?php esc_html_e("Status","olama-stores");?></th><th><?php esc_html_e("Actions","olama-stores");?></th></tr></thead><tbody>';
            rows.forEach(function(w){ 
                var typeLabel = w.type.charAt(0).toUpperCase() + w.type.slice(1);
                html+='<tr><td>'+w.id+'</td><td>'+w.name+'</td><td>'+(w.location||'—')+'</td><td><span class="os-badge os-badge-'+w.type+'">'+typeLabel+'</span></td><td>'+(w.is_active?'Active':'Inactive')+'</td>'
                    + '<td><button type="button" class="button button-small os-edit-wh" data-id="'+w.id+'"><span class="dashicons dashicons-edit"></span></button></td></tr>'; 
            });
            $('#os-warehouses-list').html(html+'</tbody></table>');
        });
    }

    function loadCategories(){
        wp.apiFetch({ path:'/olama-stores/v1/categories' }).then(function(rows){
            var html='<table class="wp-list-table widefat"><thead><tr><th>ID</th><th><?php esc_html_e("Name","olama-stores");?></th><th style="width: 100px;"><?php esc_html_e("Actions","olama-stores");?></th></tr></thead><tbody>';
            rows.forEach(function(c){ 
                html+='<tr data-id="'+c.id+'"><td>'+c.id+'</td>'
                    + '<td><span class="view-mode">'+c.name+'</span><input type="text" class="edit-mode os-input" value="'+c.name+'" style="display:none; width: 100%;"></td>'
                    + '<td>'
                    + '<button type="button" class="button button-small os-edit-row"><span class="dashicons dashicons-edit"></span></button> '
                    + '<button type="button" class="button button-small os-save-row" style="display:none;"><span class="dashicons dashicons-yes"></span></button> '
                    + '<button type="button" class="button button-small button-link-delete os-delete-row"><span class="dashicons dashicons-trash"></span></button>'
                    + '</td></tr>'; 
            });
            $('#os-categories-list').html(html+'</tbody></table>');
        });
    }

    function loadUnits(){
        wp.apiFetch({ path:'/olama-stores/v1/units' }).then(function(rows){
            var html='<table class="wp-list-table widefat"><thead><tr><th>ID</th><th><?php esc_html_e("Name","olama-stores");?></th><th><?php esc_html_e("Symbol","olama-stores");?></th><th style="width: 100px;"><?php esc_html_e("Actions","olama-stores");?></th></tr></thead><tbody>';
            rows.forEach(function(u){ 
                html+='<tr data-id="'+u.id+'"><td>'+u.id+'</td>'
                    + '<td><span class="view-mode">'+u.name+'</span><input type="text" class="edit-mode-name os-input" value="'+u.name+'" style="display:none; width: 100%;"></td>'
                    + '<td><span class="view-mode">'+u.symbol+'</span><input type="text" class="edit-mode-symbol os-input" value="'+u.symbol+'" style="display:none; width: 100%;"></td>'
                    + '<td>'
                    + '<button type="button" class="button button-small os-edit-row"><span class="dashicons dashicons-edit"></span></button> '
                    + '<button type="button" class="button button-small os-save-row" style="display:none;"><span class="dashicons dashicons-yes"></span></button> '
                    + '<button type="button" class="button button-small button-link-delete os-delete-row"><span class="dashicons dashicons-trash"></span></button>'
                    + '</td></tr>'; 
            });
            $('#os-units-list').html(html+'</tbody></table>');
        });
    }

    function loadCustomModels(){
        wp.apiFetch({ path:'/olama-stores/v1/custom-models' }).then(function(rows){
            var html='<table class="wp-list-table widefat"><thead><tr><th>ID</th><th><?php esc_html_e("Name","olama-stores");?></th><th style="width: 100px;"><?php esc_html_e("Actions","olama-stores");?></th></tr></thead><tbody>';
            rows.forEach(function(m){ 
                html+='<tr data-id="'+m.id+'"><td>'+m.id+'</td>'
                    + '<td><span class="view-mode">'+m.name+'</span><input type="text" class="edit-mode-name os-input" value="'+m.name+'" style="display:none; width: 100%;"></td>'
                    + '<td>'
                    + '<button type="button" class="button button-small os-edit-row"><span class="dashicons dashicons-edit"></span></button> '
                    + '<button type="button" class="button button-small os-save-row" style="display:none;"><span class="dashicons dashicons-yes"></span></button> '
                    + '<button type="button" class="button button-small button-link-delete os-delete-row"><span class="dashicons dashicons-trash"></span></button>'
                    + '</td></tr>'; 
            });
            $('#os-custom-models-list').html(html+'</tbody></table>');
        });
    }

    function loadFabrics(){
        wp.apiFetch({ path:'/olama-stores/v1/fabrics' }).then(function(rows){
            var html='<table class="wp-list-table widefat"><thead><tr><th>ID</th><th><?php esc_html_e("Name","olama-stores");?></th><th style="width: 100px;"><?php esc_html_e("Actions","olama-stores");?></th></tr></thead><tbody>';
            rows.forEach(function(f){ 
                html+='<tr data-id="'+f.id+'"><td>'+f.id+'</td>'
                    + '<td><span class="view-mode">'+f.name+'</span><input type="text" class="edit-mode-name os-input" value="'+f.name+'" style="display:none; width: 100%;"></td>'
                    + '<td>'
                    + '<button type="button" class="button button-small os-edit-row"><span class="dashicons dashicons-edit"></span></button> '
                    + '<button type="button" class="button button-small os-save-row" style="display:none;"><span class="dashicons dashicons-yes"></span></button> '
                    + '<button type="button" class="button button-small button-link-delete os-delete-row"><span class="dashicons dashicons-trash"></span></button>'
                    + '</td></tr>'; 
            });
            $('#os-fabrics-list').html(html+'</tbody></table>');
        });
    }

    function loadColors(){
        wp.apiFetch({ path:'/olama-stores/v1/colors' }).then(function(rows){
            var html='<table class="wp-list-table widefat"><thead><tr><th>ID</th><th><?php esc_html_e("Name","olama-stores");?></th><th style="width: 100px;"><?php esc_html_e("Actions","olama-stores");?></th></tr></thead><tbody>';
            rows.forEach(function(c){ 
                html+='<tr data-id="'+c.id+'"><td>'+c.id+'</td>'
                    + '<td><span class="view-mode">'+c.name+'</span><input type="text" class="edit-mode-name os-input" value="'+c.name+'" style="display:none; width: 100%;"></td>'
                    + '<td>'
                    + '<button type="button" class="button button-small os-edit-row"><span class="dashicons dashicons-edit"></span></button> '
                    + '<button type="button" class="button button-small os-save-row" style="display:none;"><span class="dashicons dashicons-yes"></span></button> '
                    + '<button type="button" class="button button-small button-link-delete os-delete-row"><span class="dashicons dashicons-trash"></span></button>'
                    + '</td></tr>'; 
            });
            $('#os-colors-list').html(html+'</tbody></table>');
        });
    }

    function loadSizes(){
        wp.apiFetch({ path:'/olama-stores/v1/sizes' }).then(function(rows){
            var html='<table class="wp-list-table widefat"><thead><tr><th>ID</th><th><?php esc_html_e("Name","olama-stores");?></th><th style="width: 100px;"><?php esc_html_e("Actions","olama-stores");?></th></tr></thead><tbody>';
            rows.forEach(function(s){ 
                html+='<tr data-id="'+s.id+'"><td>'+s.id+'</td>'
                    + '<td><span class="view-mode">'+s.name+'</span><input type="text" class="edit-mode-name os-input" value="'+s.name+'" style="display:none; width: 100%;"></td>'
                    + '<td>'
                    + '<button type="button" class="button button-small os-edit-row"><span class="dashicons dashicons-edit"></span></button> '
                    + '<button type="button" class="button button-small os-save-row" style="display:none;"><span class="dashicons dashicons-yes"></span></button> '
                    + '<button type="button" class="button button-small button-link-delete os-delete-row"><span class="dashicons dashicons-trash"></span></button>'
                    + '</td></tr>'; 
            });
            $('#os-sizes-list').html(html+'</tbody></table>');
        });
    }

    $('#os-btn-add-cat').on('click', function(){
        var name = $('#os-new-cat-name').val();
        if(!name) return;
        wp.apiFetch({ path:'/olama-stores/v1/categories', method:'POST', data:{ name: name } }).then(function(){
            $('#os-new-cat-name').val('');
            loadCategories();
        });
    });

    $('#os-btn-add-unit').on('click', function(){
        var name = $('#os-new-unit-name').val();
        var symbol = $('#os-new-unit-symbol').val();
        if(!name || !symbol) return;
        wp.apiFetch({ path:'/olama-stores/v1/units', method:'POST', data:{ name: name, symbol: symbol } }).then(function(){
            $('#os-new-unit-name').val(''); $('#os-new-unit-symbol').val('');
            loadUnits();
        });
    });

    $('#os-btn-add-model').on('click', function(){
        var name = $('#os-new-model-name').val();
        if(!name) return;
        wp.apiFetch({ path:'/olama-stores/v1/custom-models', method:'POST', data:{ name: name } }).then(function(){
            $('#os-new-model-name').val('');
            loadCustomModels();
        });
    });

    $('#os-btn-add-fabric').on('click', function(){
        var name = $('#os-new-fabric-name').val();
        if(!name) return;
        wp.apiFetch({ path:'/olama-stores/v1/fabrics', method:'POST', data:{ name: name } }).then(function(){
            $('#os-new-fabric-name').val('');
            loadFabrics();
        });
    });

    $('#os-btn-add-color').on('click', function(){
        var name = $('#os-new-color-name').val();
        if(!name) return;
        wp.apiFetch({ path:'/olama-stores/v1/colors', method:'POST', data:{ name: name } }).then(function(){
            $('#os-new-color-name').val('');
            loadColors();
        });
    });

    $('#os-btn-add-size').on('click', function(){
        var name = $('#os-new-size-name').val();
        if(!name) return;
        wp.apiFetch({ path:'/olama-stores/v1/sizes', method:'POST', data:{ name: name } }).then(function(){
            $('#os-new-size-name').val('');
            loadSizes();
        });
    });

    $(document).on('click', '.os-edit-row', function(){
        var row = $(this).closest('tr');
        row.find('.view-mode').hide();
        row.find('.edit-mode, .edit-mode-name, .edit-mode-symbol').show();
        $(this).hide();
        row.find('.os-save-row').show();
    });

    $(document).on('click', '.os-save-row', function(){
        var row = $(this).closest('tr');
        var id = row.data('id');
        var tab = row.closest('.os-tab-content').attr('id');
        var payload = {};

        if (tab === 'tab-categories') {
            payload.name = row.find('.edit-mode').val();
            wp.apiFetch({ path: '/olama-stores/v1/categories/' + id, method: 'PUT', data: payload }).then(function(){
                loadCategories();
            });
        } else if (tab === 'tab-custom-models') {
            payload.name = row.find('.edit-mode-name').val();
            wp.apiFetch({ path: '/olama-stores/v1/custom-models/' + id, method: 'PUT', data: payload }).then(function(){
                loadCustomModels();
            });
        } else if (tab === 'tab-fabrics') {
            payload.name = row.find('.edit-mode-name').val();
            wp.apiFetch({ path: '/olama-stores/v1/fabrics/' + id, method: 'PUT', data: payload }).then(function(){
                loadFabrics();
            });
        } else if (tab === 'tab-colors') {
            payload.name = row.find('.edit-mode-name').val();
            wp.apiFetch({ path: '/olama-stores/v1/colors/' + id, method: 'PUT', data: payload }).then(function(){
                loadColors();
            });
        } else if (tab === 'tab-sizes') {
            payload.name = row.find('.edit-mode-name').val();
            wp.apiFetch({ path: '/olama-stores/v1/sizes/' + id, method: 'PUT', data: payload }).then(function(){
                loadSizes();
            });
        } else {
            payload.name = row.find('.edit-mode-name').val();
            payload.symbol = row.find('.edit-mode-symbol').val();
            wp.apiFetch({ path: '/olama-stores/v1/units/' + id, method: 'PUT', data: payload }).then(function(){
                loadUnits();
            });
        }
    });

    $(document).on('click', '.os-delete-row', function(){
        if (!confirm('<?php esc_html_e("Are you sure you want to delete this?","olama-stores");?>')) return;
        var row = $(this).closest('tr');
        var id = row.data('id');
        var tab = row.closest('.os-tab-content').attr('id');
        var path = '';
        if (tab === 'tab-categories') path = '/olama-stores/v1/categories/';
        else if (tab === 'tab-custom-models') path = '/olama-stores/v1/custom-models/';
        else if (tab === 'tab-fabrics') path = '/olama-stores/v1/fabrics/';
        else if (tab === 'tab-colors') path = '/olama-stores/v1/colors/';
        else if (tab === 'tab-sizes') path = '/olama-stores/v1/sizes/';
        else path = '/olama-stores/v1/units/';

        wp.apiFetch({ path: path + id, method: 'DELETE' }).then(function(){
            if (tab === 'tab-categories') loadCategories(); 
            else if (tab === 'tab-custom-models') loadCustomModels();
            else if (tab === 'tab-fabrics') loadFabrics();
            else if (tab === 'tab-colors') loadColors();
            else if (tab === 'tab-sizes') loadSizes();
            else loadUnits();
        });
    });

    $(document).on('click', '.remove-row', function(){
        $(this).closest('.row').remove();
    });

    $('#os-btn-add-warehouse').on('click', function(){ 
        $('#os-wh-id').val('');
        $('#os-wh-modal-title').text('<?php esc_html_e("Add Warehouse","olama-stores");?>');
        $('#os-wh-name').val(''); $('#os-wh-name-ar').val(''); $('#os-wh-location').val(''); $('#os-wh-type').val('items');
        $('#os-warehouse-modal').show(); 
    });

    $(document).on('click', '.os-edit-wh', function(){
        var id = $(this).data('id');
        wp.apiFetch({ path:'/olama-stores/v1/warehouses' }).then(function(rows){
            var w = rows.find(function(x){ return x.id == id; });
            if(w){
                $('#os-wh-id').val(w.id);
                $('#os-wh-modal-title').text('<?php esc_html_e("Edit Warehouse","olama-stores");?>');
                $('#os-wh-name').val(w.name);
                $('#os-wh-name-ar').val(w.name_ar);
                $('#os-wh-location').val(w.location);
                $('#os-wh-type').val(w.type || 'items');
                $('#os-warehouse-modal').show();
            }
        });
    });

    $('#os-wh-save').on('click', function(){
        var id = $('#os-wh-id').val();
        var payload = {
            name: $('#os-wh-name').val(),
            name_ar: $('#os-wh-name-ar').val(),
            location: $('#os-wh-location').val(),
            type: $('#os-wh-type').val()
        };
        var method = id ? 'PUT' : 'POST';
        var path = id ? '/olama-stores/v1/warehouses/' + id : '/olama-stores/v1/warehouses';

        wp.apiFetch({ path: path, method: method, data: payload }).then(function(){ 
            $('#os-warehouse-modal').hide(); 
            loadWarehouses(); 
        }).catch(function(e){ alert(e.message); });
    });

    // Providers Logic
    function loadProviders(){
        wp.apiFetch({ path:'/olama-stores/v1/providers' }).then(function(rows){
            var html='<table class="wp-list-table widefat"><thead><tr><th><?php esc_html_e("Company","olama-stores");?></th><th><?php esc_html_e("Mobile","olama-stores");?></th><th><?php esc_html_e("Location","olama-stores");?></th><th><?php esc_html_e("Contact Person","olama-stores");?></th><th><?php esc_html_e("Status","olama-stores");?></th><th style="width:100px;"><?php esc_html_e("Actions","olama-stores");?></th></tr></thead><tbody>';
            rows.forEach(function(p){ 
                html+='<tr><td>'+p.company_name+'</td><td>'+(p.mobile_contact||'—')+'</td><td>'+(p.location||'—')+'</td><td>'+(p.contact_person||'—')+'</td>'
                    + '<td>'+(p.is_active == 1 ? '<strong>Active</strong>' : 'Inactive')+'</td>'
                    + '<td><button type="button" class="button button-small os-edit-provider" data-id="'+p.id+'"><span class="dashicons dashicons-edit"></span></button> '
                    + '<button type="button" class="button button-small button-link-delete os-delete-provider" data-id="'+p.id+'"><span class="dashicons dashicons-trash"></span></button></td></tr>'; 
            });
            $('#os-providers-list').html(html+'</tbody></table>');
        });
    }

    $('#os-btn-add-provider').on('click', function(){ 
        $('#os-provider-id').val('');
        $('#os-provider-modal-title').text('<?php esc_html_e("Add Provider","olama-stores");?>');
        $('#os-provider-name').val(''); $('#os-provider-mobile').val(''); $('#os-provider-location').val(''); $('#os-provider-person').val('');
        $('#os-provider-active').prop('checked', false);
        $('#os-provider-modal').show(); 
    });

    $(document).on('click', '.os-edit-provider', function(){
        var id = $(this).data('id');
        wp.apiFetch({ path: '/olama-stores/v1/providers' }).then(function(rows){
            var p = rows.find(function(x){ return x.id == id; });
            if(p){
                $('#os-provider-id').val(p.id);
                $('#os-provider-modal-title').text('<?php esc_html_e("Edit Provider","olama-stores");?>');
                $('#os-provider-name').val(p.company_name);
                $('#os-provider-mobile').val(p.mobile_contact);
                $('#os-provider-location').val(p.location);
                $('#os-provider-person').val(p.contact_person);
                $('#os-provider-active').prop('checked', p.is_active == 1);
                $('#os-provider-modal').show();
            }
        });
    });

    $('#os-provider-save').on('click', function(){
        var id = $('#os-provider-id').val();
        var payload = {
            company_name: $('#os-provider-name').val(),
            mobile_contact: $('#os-provider-mobile').val(),
            location: $('#os-provider-location').val(),
            contact_person: $('#os-provider-person').val(),
            is_active: $('#os-provider-active').is(':checked') ? 1 : 0
        };
        var method = id ? 'PUT' : 'POST';
        var path = id ? '/olama-stores/v1/providers/' + id : '/olama-stores/v1/providers';
        
        if (!payload.company_name) { alert('<?php esc_html_e("Company Name is required","olama-stores");?>'); return; }

        wp.apiFetch({ path: path, method: method, data: payload }).then(function(){
            $('#os-provider-modal').hide();
            loadProviders();
        }).catch(function(e){ 
            console.error('Provider Save Error:', e);
            alert(e.message || '<?php esc_html_e("Error saving provider","olama-stores");?>'); 
        });
    });

    $(document).on('click', '.os-delete-provider', function(){
        if(!confirm('<?php esc_html_e("Delete this provider?","olama-stores");?>')) return;
        wp.apiFetch({ path: '/olama-stores/v1/providers/' + $(this).data('id'), method: 'DELETE' }).then(function(){
            loadProviders();
        }).catch(function(e){ alert(e.message); });
    });

    // Close modals
    $('.os-modal-close, .os-modal').on('click', function(e){
        if(e.target===this || $(e.target).hasClass('os-modal-close')) $(this).closest('.os-modal').hide();
    });
})(jQuery);
</script>
