<?php
class SupplierCPT {
    
    public function __construct() {
        add_action('init', array($this, 'register_cpt'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_data'));
    }
    
    public function register_cpt() {
        $labels = array(
            'name' => __('Suppliers', 'supplier-product-manager'),
            'singular_name' => __('Supplier', 'supplier-product-manager'),
            'menu_name' => __('Suppliers', 'supplier-product-manager'),
            'name_admin_bar' => __('Supplier', 'supplier-product-manager'),
            'add_new' => __('Add New', 'supplier-product-manager'),
            'add_new_item' => __('Add New Supplier', 'supplier-product-manager'),
            'new_item' => __('New Supplier', 'supplier-product-manager'),
            'edit_item' => __('Edit Supplier', 'supplier-product-manager'),
            'view_item' => __('View Supplier', 'supplier-product-manager'),
            'all_items' => __('All Suppliers', 'supplier-product-manager'),
            'search_items' => __('Search Suppliers', 'supplier-product-manager'),
            'not_found' => __('No suppliers found.', 'supplier-product-manager'),
            'not_found_in_trash' => __('No suppliers found in Trash.', 'supplier-product-manager')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'supplier'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 21,
            'menu_icon' => 'dashicons-building',
            'supports' => array('title', 'editor', 'thumbnail'),
            'show_in_rest' => true
        );
        
        register_post_type('supplier', $args);
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'supplier_details',
            __('Supplier Details', 'supplier-product-manager'),
            array($this, 'render_meta_box'),
            'supplier',
            'normal',
            'high'
        );
        
        add_meta_box(
            'supplier_addresses',
            __('Supplier Addresses', 'supplier-product-manager'),
            array($this, 'render_addresses_meta_box'),
            'supplier',
            'normal',
            'high'
        );
    }
    
    public function render_meta_box($post) {
        wp_nonce_field('supplier_nonce', 'supplier_nonce');
        
        $supplier_id = get_post_meta($post->ID, '_supplier_unique_id', true);
        $supplier_logo = get_post_meta($post->ID, '_supplier_logo', true);
        $website = get_post_meta($post->ID, '_website', true);
        ?>
        <div class="spm-metabox">
            <div class="spm-field">
                <label for="supplier_unique_id"><?php _e('Supplier Unique ID', 'supplier-product-manager'); ?></label>
                <input type="text" name="supplier_unique_id" id="supplier_unique_id" value="<?php echo esc_attr($supplier_id); ?>" class="widefat">
            </div>
            
            <div class="spm-field">
                <label for="supplier_logo"><?php _e('Supplier Logo', 'supplier-product-manager'); ?></label>
                <input type="text" name="supplier_logo" id="supplier_logo" value="<?php echo esc_attr($supplier_logo); ?>" class="widefat">
                <button type="button" class="button spm-upload-button" data-target="supplier_logo"><?php _e('Upload Logo', 'supplier-product-manager'); ?></button>
            </div>
            
            <div class="spm-field">
                <label for="website"><?php _e('Website', 'supplier-product-manager'); ?></label>
                <input type="url" name="website" id="website" value="<?php echo esc_attr($website); ?>" class="widefat" placeholder="https://">
            </div>
            
            <div class="spm-field">
                <label for="supplier_category"><?php _e('Supplier Category', 'supplier-product-manager'); ?></label>
                <?php
                $categories = get_posts(array(
                    'post_type' => 'supplier_category',
                    'numberposts' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC'
                ));
                
                $selected_category = get_post_meta($post->ID, '_supplier_category', true);
                ?>
                <select name="supplier_category" id="supplier_category" class="widefat">
                    <option value=""><?php _e('Select Category', 'supplier-product-manager'); ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category->ID; ?>" <?php selected($selected_category, $category->ID); ?>>
                            <?php echo esc_html($category->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php
    }
    
    public function render_addresses_meta_box($post) {
        $addresses = get_post_meta($post->ID, '_supplier_addresses', true);
        $addresses = is_array($addresses) ? $addresses : array('');
        ?>
        <div class="spm-addresses">
            <?php foreach ($addresses as $index => $address): ?>
                <div class="spm-address" data-index="<?php echo $index; ?>">
                    <label><?php _e('Address', 'supplier-product-manager'); ?> #<?php echo $index + 1; ?></label>
                    <textarea name="supplier_addresses[]" class="widefat" rows="3" placeholder="<?php _e('Enter full address', 'supplier-product-manager'); ?>"><?php echo esc_textarea($address); ?></textarea>
                    <?php if ($index > 0): ?>
                        <button type="button" class="button button-remove-address"><?php _e('Remove', 'supplier-product-manager'); ?></button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <button type="button" class="button button-primary button-add-address"><?php _e('Add Another Address', 'supplier-product-manager'); ?></button>
        </div>
        <?php
    }
    
    public function save_meta_data($post_id) {
        // Check if our nonce is set.
        if (!isset($_POST['supplier_nonce'])) {
            return;
        }
        
        // Verify that the nonce is valid.
        if (!wp_verify_nonce($_POST['supplier_nonce'], 'supplier_nonce')) {
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
        
        // Save basic fields
        if (isset($_POST['supplier_unique_id'])) {
            update_post_meta($post_id, '_supplier_unique_id', sanitize_text_field($_POST['supplier_unique_id']));
        }
        
        if (isset($_POST['supplier_logo'])) {
            update_post_meta($post_id, '_supplier_logo', sanitize_text_field($_POST['supplier_logo']));
        }
        
        if (isset($_POST['website'])) {
            update_post_meta($post_id, '_website', esc_url_raw($_POST['website']));
        }
        
        if (isset($_POST['supplier_category'])) {
            update_post_meta($post_id, '_supplier_category', absint($_POST['supplier_category']));
        }
        
        // Save addresses
        if (isset($_POST['supplier_addresses'])) {
            $addresses = array_map('sanitize_textarea_field', $_POST['supplier_addresses']);
            $addresses = array_filter($addresses); // Remove empty addresses
            update_post_meta($post_id, '_supplier_addresses', $addresses);
        }
    }
}