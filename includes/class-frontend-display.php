<?php
class FrontendDisplay {
    
    public function __construct() {
        add_shortcode('supplier_directory', array($this, 'supplier_directory_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_load_more_suppliers', array($this, 'load_more_suppliers'));
        add_action('wp_ajax_nopriv_load_more_suppliers', array($this, 'load_more_suppliers'));
        add_action('wp_ajax_get_suppliers_list', array($this, 'get_suppliers_list'));
        add_action('wp_ajax_nopriv_get_suppliers_list', array($this, 'get_suppliers_list'));
        add_action('wp_ajax_load_more_categories', array($this, 'load_more_categories'));
        add_action('wp_ajax_nopriv_load_more_categories', array($this, 'load_more_categories'));
        add_action('wp_ajax_get_categories_list', array($this, 'get_categories_list'));
        add_action('wp_ajax_nopriv_get_categories_list', array($this, 'get_categories_list'));
    }
    
    public function enqueue_frontend_scripts() {
        // Only enqueue on pages that use the shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'supplier_directory')) {
            wp_enqueue_style('spm-frontend', SPM_PLUGIN_URL . 'assets/css/frontend.css', array(), SPM_VERSION);
            wp_enqueue_script('spm-frontend', SPM_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), SPM_VERSION, true);
            
            wp_localize_script('spm-frontend', 'spm_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('spm_nonce')
            ));
        }
    }
    
    public function supplier_directory_shortcode($atts) {
        ob_start();
        ?>
        <div class="spm-supplier-directory">
            <div class="spm-tabs">
                <button class="spm-tab active" data-tab="suppliers">Sort By Suppliers name</button>
                <button class="spm-tab" data-tab="categories">Sort By Category name</button>
            </div>
            
            <div class="spm-tab-content">
                <div class="spm-tab-pane active" id="suppliers-tab">
                    <div class="spm-suppliers-list-container">
                        <?php $this->display_suppliers_list(); ?>
                    </div>
                </div>
                
                <div class="spm-tab-pane" id="categories-tab">
                    <div class="spm-categories-list-container">
                        <?php $this->display_categories_list(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function display_suppliers_list($page = 1) {
        $args = array(
            'post_type' => 'supplier',
            'posts_per_page' => 10,
            'paged' => $page,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish'
        );
        
        $suppliers_query = new WP_Query($args);
        
        if ($suppliers_query->have_posts()) {
            echo '<div class="spm-suppliers-accordion">';
            while ($suppliers_query->have_posts()) {
                $suppliers_query->the_post();
                $this->display_supplier_item(get_the_ID());
            }
            echo '</div>';
            
            // Load more button - ONLY show if there are more than 2 items AND more pages
            $total_items = $suppliers_query->found_posts;
            if ($total_items > 2 && $suppliers_query->max_num_pages > $page) {
                echo '<div class="spm-load-more-container">';
                echo '<button class="spm-load-more" data-page="' . ($page + 1) . '" data-max-pages="' . $suppliers_query->max_num_pages . '">Load More Suppliers</button>';
                echo '</div>';
            }
            
            wp_reset_postdata();
        } else {
            echo '<p>No suppliers found.</p>';
        }
    }
    
    private function display_categories_list($page = 1) {
        $categories_per_page = 10;
        
        // Get all supplier categories
        $categories = get_posts(array(
            'post_type' => 'supplier_category',
            'posts_per_page' => $categories_per_page,
            'paged' => $page,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish'
        ));
        
        if ($categories) {
            echo '<div class="spm-categories-accordion">';
            foreach ($categories as $category) {
                $this->display_category_item($category);
            }
            echo '</div>';
            
            // Check if there are more categories
            $total_categories = wp_count_posts('supplier_category')->publish;
            $total_pages = ceil($total_categories / $categories_per_page);
            
            // Load more button - ONLY show if there are more than 10 categories AND more pages
            if ($total_categories > 10 && $page < $total_pages) {
                echo '<div class="spm-load-more-categories-container">';
                echo '<button class="spm-load-more-categories" data-page="' . ($page + 1) . '" data-max-pages="' . $total_pages . '">Load More Categories</button>';
                echo '</div>';
            }
        } else {
            echo '<p>No categories found.</p>';
        }
    }
    
    private function display_category_item($category) {
        $category_id = $category->ID;
        $category_name = $category->post_title;
        $category_logo = get_post_meta($category_id, '_category_logo', true);
        $sub_category = get_post_meta($category_id, '_sub_category', true);
        
        // Get suppliers for this category
        $suppliers = get_posts(array(
            'post_type' => 'supplier',
            'posts_per_page' => -1,
            'meta_key' => '_supplier_category',
            'meta_value' => $category_id,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        ?>
        
        <div class="spm-category-item">
            <div class="spm-category-header">
                <h3 class="spm-category-title"><?php echo esc_html($category_name); ?></h3>
                <?php if ($sub_category) : ?>
                    <span class="spm-sub-category">(<?php echo esc_html($sub_category); ?>)</span>
                <?php endif; ?>
                <span class="spm-category-toggle">▼</span>
            </div>
            
            <div class="spm-category-content">
                <?php if ($category_logo) : ?>
                    <div class="spm-category-logo">
                        <img src="<?php echo esc_url($category_logo); ?>" alt="<?php echo esc_attr($category_name); ?>">
                    </div>
                <?php endif; ?>
                
                <?php if ($suppliers) : ?>
                    <div class="spm-category-suppliers">
                        <?php foreach ($suppliers as $supplier) : ?>
                            <?php $this->display_supplier_item($supplier->ID, true); ?>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="spm-no-suppliers">No suppliers found in this category.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    private function display_supplier_item($supplier_id, $in_category = false) {
        $supplier_name = get_the_title($supplier_id);
        $supplier_logo = get_post_meta($supplier_id, '_supplier_logo', true);
        $supplier_unique_id = get_post_meta($supplier_id, '_supplier_unique_id', true);
        $supplier_desc = get_the_content($supplier_id);
        $website = get_post_meta($supplier_id, '_website', true);
        $addresses = get_post_meta($supplier_id, '_supplier_addresses', true);
        $category_id = get_post_meta($supplier_id, '_supplier_category', true);
        $category_name = $category_id ? get_the_title($category_id) : 'No Category';
        
        // Get products for this supplier
        $products = get_posts(array(
            'post_type' => 'product',
            'meta_key' => '_product_supplier',
            'meta_value' => $supplier_id,
            'posts_per_page' => -1
        ));
        ?>
        
        <div class="spm-supplier-item <?php echo $in_category ? 'in-category' : ''; ?>">
            <div class="spm-supplier-header">
                <div class="spm-supplier-basic-info">
                    <h3 class="spm-supplier-title"><?php echo esc_html($supplier_name); ?></h3>
                    <div class="spm-supplier-meta">
                        <?php if ($supplier_unique_id) : ?>
                            <span class="spm-supplier-id">ID: <?php echo esc_html($supplier_unique_id); ?></span>
                        <?php endif; ?>
                        <?php if (!$in_category) : ?>
                            <span class="spm-supplier-category"><?php echo esc_html($category_name); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="spm-accordion-toggle">+</span>
            </div>
            
            <div class="spm-supplier-content">
                <div class="spm-supplier-details">
                    <div class="spm-supplier-left">
                        <?php if ($supplier_logo) : ?>
                            <div class="spm-supplier-logo">
                                <img src="<?php echo esc_url($supplier_logo); ?>" alt="<?php echo esc_attr($supplier_name); ?>">
                            </div>
                        <?php endif; ?>
                        
                        <div class="spm-supplier-info">
                            <?php if ($supplier_unique_id) : ?>
                                <p><strong>Supplier ID:</strong> <?php echo esc_html($supplier_unique_id); ?></p>
                            <?php endif; ?>
                            <p><strong>Category:</strong> <?php echo esc_html($category_name); ?></p>
                            <div class="spm-supplier-description">
                                <strong>Description:</strong>
                                <?php echo wp_kses_post(wpautop($supplier_desc)); ?>
                            </div>
                            <?php if ($website) : ?>
                                <p><strong>Website:</strong> <a href="<?php echo esc_url($website); ?>" target="_blank"><?php echo esc_url($website); ?></a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="spm-supplier-right">
                        <h4>Addresses:</h4>
                        <?php if ($addresses && is_array($addresses)) : ?>
                            <?php foreach ($addresses as $index => $address) : ?>
                                <?php if (!empty($address)) : ?>
                                    <div class="spm-address">
                                        <strong>Address <?php echo $index + 1; ?>:</strong>
                                        <p><?php echo esc_html($address); ?></p>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p>No addresses available.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($products) : ?>
                    <div class="spm-supplier-products">
                        <h4>Products:</h4>
                        <div class="spm-products-grid">
                            <?php foreach ($products as $product) : 
                                $product_logo = get_post_meta($product->ID, '_product_logo', true);
                                $product_price = get_post_meta($product->ID, '_product_price', true);
                            ?>
                                <div class="spm-product-item">
                                    <?php if ($product_logo) : ?>
                                        <div class="spm-product-logo">
                                            <img src="<?php echo esc_url($product_logo); ?>" alt="<?php echo esc_attr($product->post_title); ?>">
                                        </div>
                                    <?php endif; ?>
                                    <h5><?php echo esc_html($product->post_title); ?></h5>
                                    <?php if ($product_price) : ?>
                                        <p class="spm-product-price">$<?php echo number_format($product_price, 2); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    public function load_more_suppliers() {
        check_ajax_referer('spm_nonce', 'nonce');
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $max_pages = isset($_POST['max_pages']) ? intval($_POST['max_pages']) : 0;
        
        ob_start();
        $this->display_suppliers_list($page);
        $content = ob_get_clean();
        
        $has_more = $page < $max_pages;
        
        wp_send_json_success(array(
            'content' => $content,
            'page' => $page,
            'has_more' => $has_more
        ));
    }
    
    public function load_more_categories() {
        check_ajax_referer('spm_nonce', 'nonce');
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $max_pages = isset($_POST['max_pages']) ? intval($_POST['max_pages']) : 0;
        
        ob_start();
        $this->display_categories_list($page);
        $content = ob_get_clean();
        
        $has_more = $page < $max_pages;
        
        wp_send_json_success(array(
            'content' => $content,
            'page' => $page,
            'has_more' => $has_more
        ));
    }
    
    public function get_suppliers_list() {
        check_ajax_referer('spm_nonce', 'nonce');
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        
        ob_start();
        $this->display_suppliers_list($page);
        $content = ob_get_clean();
        
        wp_send_json_success(array(
            'content' => $content
        ));
    }
    
    public function get_categories_list() {
        check_ajax_referer('spm_nonce', 'nonce');
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        
        ob_start();
        $this->display_categories_list($page);
        $content = ob_get_clean();
        
        wp_send_json_success(array(
            'content' => $content
        ));
    }
}