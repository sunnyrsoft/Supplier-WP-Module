<?php
class ProductCPT {
    
    public function __construct() {
        add_action('init', array($this, 'register_cpt'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_data'));
    }
    
    public function register_cpt() {
        $labels = array(
            'name' => __('Products', 'supplier-product-manager'),
            'singular_name' => __('Product', 'supplier-product-manager'),
            'menu_name' => __('Products', 'supplier-product-manager'),
            'name_admin_bar' => __('Product', 'supplier-product-manager'),
            'add_new' => __('Add New', 'supplier-product-manager'),
            'add_new_item' => __('Add New Product', 'supplier-product-manager'),
            'new_item' => __('New Product', 'supplier-product-manager'),
            'edit_item' => __('Edit Product', 'supplier-product-manager'),
            'view_item' => __('View Product', 'supplier-product-manager'),
            'all_items' => __('All Products', 'supplier-product-manager'),
            'search_items' => __('Search Products', 'supplier-product-manager'),
            'not_found' => __('No products found.', 'supplier-product-manager'),
            'not_found_in_trash' => __('No products found in Trash.', 'supplier-product-manager')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'product'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 22,
            'menu_icon' => 'dashicons-cart',
            'supports' => array('title', 'editor', 'thumbnail'),
            'show_in_rest' => true
        );
        
        register_post_type('product', $args);
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'product_details',
            __('Product Details', 'supplier-product-manager'),
            array($this, 'render_meta_box'),
            'product',
            'normal',
            'high'
        );
    }
    
    public function render_meta_box($post) {
        wp_nonce_field('product_nonce', 'product_nonce');
        
        $product_logo = get_post_meta($post->ID, '_product_logo', true);
        $product_price = get_post_meta($post->ID, '_product_price', true);
        ?>
        <div class="spm-metabox">
            <div class="spm-field">
                <label for="product_logo"><?php _e('Product Logo/Image', 'supplier-product-manager'); ?></label>
                <input type="text" name="product_logo" id="product_logo" value="<?php echo esc_attr($product_logo); ?>" class="widefat">
                <button type="button" class="button spm-upload-button" data-target="product_logo"><?php _e('Upload Image', 'supplier-product-manager'); ?></button>
            </div>
            
            <div class="spm-field">
                <label for="product_price"><?php _e('Product Price', 'supplier-product-manager'); ?></label>
                <input type="number" name="product_price" id="product_price" value="<?php echo esc_attr($product_price); ?>" class="widefat" step="0.01" min="0" placeholder="0.00">
            </div>
            
            <div class="spm-field">
                <label for="product_supplier"><?php _e('Supplier', 'supplier-product-manager'); ?></label>
                <?php
                $suppliers = get_posts(array(
                    'post_type' => 'supplier',
                    'numberposts' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC'
                ));
                
                $selected_supplier = get_post_meta($post->ID, '_product_supplier', true);
                ?>
                <select name="product_supplier" id="product_supplier" class="widefat">
                    <option value=""><?php _e('Select Supplier', 'supplier-product-manager'); ?></option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?php echo $supplier->ID; ?>" <?php selected($selected_supplier, $supplier->ID); ?>>
                            <?php echo esc_html($supplier->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php
    }
    
    public function save_meta_data($post_id) {
        // Check if our nonce is set.
        if (!isset($_POST['product_nonce'])) {
            return;
        }
        
        // Verify that the nonce is valid.
        if (!wp_verify_nonce($_POST['product_nonce'], 'product_nonce')) {
            return;
        }
        
        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the user's permissions.
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save product logo
        if (isset($_POST['product_logo'])) {
            update_post_meta($post_id, '_product_logo', sanitize_text_field($_POST['product_logo']));
        }
        
        // Save product price
        if (isset($_POST['product_price'])) {
            $price = floatval($_POST['product_price']);
            update_post_meta($post_id, '_product_price', number_format($price, 2, '.', ''));
        }
        
        // Save product supplier
        if (isset($_POST['product_supplier'])) {
            update_post_meta($post_id, '_product_supplier', absint($_POST['product_supplier']));
        }
    }
}