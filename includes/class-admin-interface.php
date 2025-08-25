<?php
class AdminInterface {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'setup_admin_columns'));
    }
    
    public function add_admin_menu() {
        // Main menu item
        add_menu_page(
            __('Supplier Product Manager', 'supplier-product-manager'),
            __('Supplier Manager', 'supplier-product-manager'),
            'manage_options',
            'supplier-product-manager',
            array($this, 'render_dashboard'),
            'dashicons-admin-generic',
            20
        );
        
        // Submenu items
        add_submenu_page(
            'supplier-product-manager',
            __('Dashboard', 'supplier-product-manager'),
            __('Dashboard', 'supplier-product-manager'),
            'manage_options',
            'supplier-product-manager',
            array($this, 'render_dashboard')
        );
        
        // Only add these if the post types are registered
        if (post_type_exists('supplier_category')) {
            add_submenu_page(
                'supplier-product-manager',
                __('All Categories', 'supplier-product-manager'),
                __('Categories', 'supplier-product-manager'),
                'manage_options',
                'edit.php?post_type=supplier_category'
            );
        }
        
        if (post_type_exists('supplier')) {
            add_submenu_page(
                'supplier-product-manager',
                __('All Suppliers', 'supplier-product-manager'),
                __('Suppliers', 'supplier-product-manager'),
                'manage_options',
                'edit.php?post_type=supplier'
            );
        }
        
        if (post_type_exists('product')) {
            add_submenu_page(
                'supplier-product-manager',
                __('All Products', 'supplier-product-manager'),
                __('Products', 'supplier-product-manager'),
                'manage_options',
                'edit.php?post_type=product'
            );
        }
    }
    
    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1><?php _e('Supplier Product Manager Dashboard', 'supplier-product-manager'); ?></h1>
            
            <div class="spm-dashboard-stats">
                <div class="spm-stat-card">
                    <h3><?php _e('Total Categories', 'supplier-product-manager'); ?></h3>
                    <p><?php 
                        if (post_type_exists('supplier_category')) {
                            echo wp_count_posts('supplier_category')->publish;
                        } else {
                            echo '0';
                        }
                    ?></p>
                </div>
                
                <div class="spm-stat-card">
                    <h3><?php _e('Total Suppliers', 'supplier-product-manager'); ?></h3>
                    <p><?php 
                        if (post_type_exists('supplier')) {
                            echo wp_count_posts('supplier')->publish;
                        } else {
                            echo '0';
                        }
                    ?></p>
                </div>
                
                <div class="spm-stat-card">
                    <h3><?php _e('Total Products', 'supplier-product-manager'); ?></h3>
                    <p><?php 
                        if (post_type_exists('product')) {
                            echo wp_count_posts('product')->publish;
                        } else {
                            echo '0';
                        }
                    ?></p>
                </div>
            </div>
            
            <div class="spm-quick-actions">
                <h2><?php _e('Quick Actions', 'supplier-product-manager'); ?></h2>
                <p>
                    <?php if (post_type_exists('supplier_category')): ?>
                    <a href="<?php echo admin_url('post-new.php?post_type=supplier_category'); ?>" class="button button-primary">
                        <?php _e('Add New Category', 'supplier-product-manager'); ?>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (post_type_exists('supplier')): ?>
                    <a href="<?php echo admin_url('post-new.php?post_type=supplier'); ?>" class="button button-primary">
                        <?php _e('Add New Supplier', 'supplier-product-manager'); ?>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (post_type_exists('product')): ?>
                    <a href="<?php echo admin_url('post-new.php?post_type=product'); ?>" class="button button-primary">
                        <?php _e('Add New Product', 'supplier-product-manager'); ?>
                    </a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <?php
    }
    
    public function setup_admin_columns() {
        // Supplier Category columns
        if (post_type_exists('supplier_category')) {
            add_filter('manage_supplier_category_posts_columns', array($this, 'supplier_category_columns'));
            add_action('manage_supplier_category_posts_custom_column', array($this, 'supplier_category_column_content'), 10, 2);
        }
        
        // Supplier columns
        if (post_type_exists('supplier')) {
            add_filter('manage_supplier_posts_columns', array($this, 'supplier_columns'));
            add_action('manage_supplier_posts_custom_column', array($this, 'supplier_column_content'), 10, 2);
        }
        
        // Product columns
        if (post_type_exists('product')) {
            add_filter('manage_product_posts_columns', array($this, 'product_columns'));
            add_action('manage_product_posts_custom_column', array($this, 'product_column_content'), 10, 2);
        }
    }
    
    public function supplier_category_columns($columns) {
        $new_columns = array(
            'cb' => $columns['cb'],
            'title' => __('Category Name', 'supplier-product-manager'),
            'logo' => __('Logo', 'supplier-product-manager'),
            'sub_category' => __('Sub Category', 'supplier-product-manager'),
            'date' => __('Date', 'supplier-product-manager')
        );
        return $new_columns;
    }
    
    public function supplier_category_column_content($column, $post_id) {
        switch ($column) {
            case 'logo':
                $logo = get_post_meta($post_id, '_category_logo', true);
                if ($logo) {
                    echo '<img src="' . esc_url($logo) . '" style="width: 50px; height: 50px; object-fit: cover;">';
                }
                break;
            case 'sub_category':
                echo esc_html(get_post_meta($post_id, '_sub_category', true));
                break;
        }
    }
    
    public function supplier_columns($columns) {
        $new_columns = array(
            'cb' => $columns['cb'],
            'title' => __('Supplier Name', 'supplier-product-manager'),
            'unique_id' => __('Unique ID', 'supplier-product-manager'),
            'logo' => __('Logo', 'supplier-product-manager'),
            'category' => __('Category', 'supplier-product-manager'),
            'date' => __('Date', 'supplier-product-manager')
        );
        return $new_columns;
    }
    
    public function supplier_column_content($column, $post_id) {
        switch ($column) {
            case 'unique_id':
                echo esc_html(get_post_meta($post_id, '_supplier_unique_id', true));
                break;
            case 'logo':
                $logo = get_post_meta($post_id, '_supplier_logo', true);
                if ($logo) {
                    echo '<img src="' . esc_url($logo) . '" style="width: 50px; height: 50px; object-fit: cover;">';
                }
                break;
            case 'category':
                $category_id = get_post_meta($post_id, '_supplier_category', true);
                if ($category_id) {
                    $category = get_post($category_id);
                    if ($category) {
                        echo esc_html($category->post_title);
                    }
                }
                break;
        }
    }
    
    public function product_columns($columns) {
        $new_columns = array(
            'cb' => $columns['cb'],
            'title' => __('Product Name', 'supplier-product-manager'),
            'logo' => __('Image', 'supplier-product-manager'),
            'price' => __('Price', 'supplier-product-manager'),
            'supplier' => __('Supplier', 'supplier-product-manager'),
            'date' => __('Date', 'supplier-product-manager')
        );
        return $new_columns;
    }
    
    public function product_column_content($column, $post_id) {
        switch ($column) {
            case 'logo':
                $logo = get_post_meta($post_id, '_product_logo', true);
                if ($logo) {
                    echo '<img src="' . esc_url($logo) . '" style="width: 50px; height: 50px; object-fit: cover;">';
                }
                break;
            case 'price':
                $price = get_post_meta($post_id, '_product_price', true);
                if ($price) {
                    echo '$' . number_format($price, 2);
                }
                break;
            case 'supplier':
                $supplier_id = get_post_meta($post_id, '_product_supplier', true);
                if ($supplier_id) {
                    $supplier = get_post($supplier_id);
                    if ($supplier) {
                        echo esc_html($supplier->post_title);
                    }
                }
                break;
        }
    }
}