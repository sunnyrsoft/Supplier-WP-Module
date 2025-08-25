<?php
class SupplierCategoryCPT {
    
    public function __construct() {
        add_action('init', array($this, 'register_cpt'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_data'));
    }
    
    public function register_cpt() {
        $labels = array(
            'name' => __('Supplier Categories', 'supplier-product-manager'),
            'singular_name' => __('Supplier Category', 'supplier-product-manager'),
            'menu_name' => __('Supplier Categories', 'supplier-product-manager'),
            'name_admin_bar' => __('Supplier Category', 'supplier-product-manager'),
            'add_new' => __('Add New', 'supplier-product-manager'),
            'add_new_item' => __('Add New Supplier Category', 'supplier-product-manager'),
            'new_item' => __('New Supplier Category', 'supplier-product-manager'),
            'edit_item' => __('Edit Supplier Category', 'supplier-product-manager'),
            'view_item' => __('View Supplier Category', 'supplier-product-manager'),
            'all_items' => __('All Supplier Categories', 'supplier-product-manager'),
            'search_items' => __('Search Supplier Categories', 'supplier-product-manager'),
            'not_found' => __('No supplier categories found.', 'supplier-product-manager'),
            'not_found_in_trash' => __('No supplier categories found in Trash.', 'supplier-product-manager')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'supplier-category'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-category',
            'supports' => array('title', 'editor', 'thumbnail'),
            'show_in_rest' => true
        );
        
        register_post_type('supplier_category', $args);
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'supplier_category_details',
            __('Category Details', 'supplier-product-manager'),
            array($this, 'render_meta_box'),
            'supplier_category',
            'normal',
            'high'
        );
    }
    
    public function render_meta_box($post) {
        wp_nonce_field('supplier_category_nonce', 'supplier_category_nonce');
        
        $category_logo = get_post_meta($post->ID, '_category_logo', true);
        $sub_category = get_post_meta($post->ID, '_sub_category', true);
        ?>
        <div class="spm-metabox">
            <div class="spm-field">
                <label for="category_logo"><?php _e('Category Logo', 'supplier-product-manager'); ?></label>
                <input type="text" name="category_logo" id="category_logo" value="<?php echo esc_attr($category_logo); ?>" class="widefat">
                <button type="button" class="button spm-upload-button" data-target="category_logo"><?php _e('Upload Logo', 'supplier-product-manager'); ?></button>
            </div>
            
            <div class="spm-field">
                <label for="sub_category"><?php _e('Sub Category', 'supplier-product-manager'); ?></label>
                <input type="text" name="sub_category" id="sub_category" value="<?php echo esc_attr($sub_category); ?>" class="widefat">
                <p class="description"><?php _e('Enter sub-category name if applicable', 'supplier-product-manager'); ?></p>
            </div>
        </div>
        <?php
    }
    
    public function save_meta_data($post_id) {
        // Check if our nonce is set.
        if (!isset($_POST['supplier_category_nonce'])) {
            return;
        }
        
        // Verify that the nonce is valid.
        if (!wp_verify_nonce($_POST['supplier_category_nonce'], 'supplier_category_nonce')) {
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
        
        // Sanitize and save the data
        if (isset($_POST['category_logo'])) {
            update_post_meta($post_id, '_category_logo', sanitize_text_field($_POST['category_logo']));
        }
        
        if (isset($_POST['sub_category'])) {
            update_post_meta($post_id, '_sub_category', sanitize_text_field($_POST['sub_category']));
        }
    }
}