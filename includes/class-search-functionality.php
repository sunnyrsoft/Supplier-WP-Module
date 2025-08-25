<?php
class SearchFunctionality {
    
    public function __construct() {
        add_shortcode('supplier_search_form', array($this, 'search_form_shortcode'));
        add_shortcode('supplier_search_results', array($this, 'search_results_shortcode'));
        add_action('wp_ajax_search_suppliers', array($this, 'ajax_search_suppliers'));
        add_action('wp_ajax_nopriv_search_suppliers', array($this, 'ajax_search_suppliers'));
        add_action('template_redirect', array($this, 'handle_search_redirect'));
    }
    
    public function search_form_shortcode($atts) {
        ob_start();
        ?>
        <div class="spm-search-form">
            <form method="get" action="<?php echo esc_url(home_url('/')); ?>" class="spm-search-form-inner">
                <input type="hidden" name="spm_search" value="1">
                <div class="spm-search-input-group">
                    <input type="text" name="search_keyword" class="spm-search-input" 
                           placeholder="<?php esc_attr_e('Search suppliers, products, categories...', 'supplier-product-manager'); ?>"
                           value="<?php echo isset($_GET['search_keyword']) ? esc_attr($_GET['search_keyword']) : ''; ?>">
                    <button type="submit" class="spm-search-button">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Search', 'supplier-product-manager'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function search_results_shortcode($atts) {
        ob_start();
        
        $keyword = isset($_GET['search_keyword']) ? sanitize_text_field($_GET['search_keyword']) : '';
        $page = isset($_GET['spm_page']) ? intval($_GET['spm_page']) : 1;
        
        if (empty($keyword)) {
            echo '<div class="spm-search-results">';
            echo '<h2>' . __('Search Suppliers', 'supplier-product-manager') . '</h2>';
            echo '<p>' . __('Please enter a search term to find suppliers.', 'supplier-product-manager') . '</p>';
            echo '</div>';
            return ob_get_clean();
        }
        
        $results = $this->perform_search($keyword, $page);
        ?>
        
        <div class="spm-search-results">
            <h2><?php printf(__('Search Results for "%s"', 'supplier-product-manager'), esc_html($keyword)); ?></h2>
            
            <?php if (!empty($results['category_results'])) : ?>
                <div class="spm-search-category-results">
                    <h3><?php printf(__('%d categories found', 'supplier-product-manager'), count($results['category_results'])); ?></h3>
                    <div class="spm-categories-grid">
                        <?php foreach ($results['category_results'] as $category) : ?>
                            <div class="spm-category-card">
                                <h4><?php echo esc_html($category->post_title); ?></h4>
                                <?php if ($category_logo = get_post_meta($category->ID, '_category_logo', true)) : ?>
                                    <img src="<?php echo esc_url($category_logo); ?>" alt="<?php echo esc_attr($category->post_title); ?>">
                                <?php endif; ?>
                                <a href="<?php echo get_permalink($category->ID); ?>" class="spm-view-category">
                                    <?php _e('View Category', 'supplier-product-manager'); ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="spm-search-supplier-results">
                <h3><?php printf(__('%d suppliers have products relevant to your search.', 'supplier-product-manager'), $results['total_suppliers']); ?></h3>
                
                <?php if ($results['suppliers']) : ?>
                    <div class="spm-suppliers-accordion">
                        <?php foreach ($results['suppliers'] as $supplier) : ?>
                            <?php 
                            // Use the existing display method from FrontendDisplay
                            $frontend_display = new FrontendDisplay();
                            $frontend_display->display_supplier_item($supplier->ID);
                            ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($results['total_pages'] > 1) : ?>
                        <div class="spm-search-pagination">
                            <?php
                            $base_url = add_query_arg(array(
                                'search_keyword' => $keyword,
                                'spm_search' => 1
                            ), home_url('/'));
                            
                            echo paginate_links(array(
                                'base' => $base_url . '%_%',
                                'format' => '&spm_page=%#%',
                                'current' => $page,
                                'total' => $results['total_pages'],
                                'prev_text' => __('&laquo; Previous', 'supplier-product-manager'),
                                'next_text' => __('Next &raquo;', 'supplier-product-manager'),
                            ));
                            ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else : ?>
                    <p class="spm-no-results"><?php _e('No suppliers found matching your search criteria.', 'supplier-product-manager'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php
        return ob_get_clean();
    }
    
    private function perform_search($keyword, $page = 1, $per_page = 10) {
        $results = array(
            'suppliers' => array(),
            'category_results' => array(),
            'total_suppliers' => 0,
            'total_pages' => 0
        );
        
        // Search in categories
        $category_args = array(
            'post_type' => 'supplier_category',
            'posts_per_page' => 5,
            's' => $keyword,
            'post_status' => 'publish'
        );
        
        $results['category_results'] = get_posts($category_args);
        
        // Search in suppliers, products, and content
        $supplier_args = array(
            'post_type' => 'supplier',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_supplier_unique_id',
                    'value' => $keyword,
                    'compare' => 'LIKE'
                )
            ),
            'tax_query' => array(
                array(
                    'taxonomy' => 'supplier_category',
                    'field' => 'name',
                    'terms' => $keyword,
                    'operator' => 'LIKE'
                )
            )
        );
        
        // Add search in title and content
        if (!empty($keyword)) {
            $supplier_args['s'] = $keyword;
        }
        
        $supplier_query = new WP_Query($supplier_args);
        $results['suppliers'] = $supplier_query->posts;
        $results['total_suppliers'] = $supplier_query->found_posts;
        $results['total_pages'] = $supplier_query->max_num_pages;
        
        // If no suppliers found, search in products and get their suppliers
        if (empty($results['suppliers'])) {
            $product_args = array(
                'post_type' => 'product',
                'posts_per_page' => -1,
                's' => $keyword,
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => '_product_price',
                        'value' => $keyword,
                        'compare' => 'LIKE'
                    )
                )
            );
            
            $products = get_posts($product_args);
            $supplier_ids = array();
            
            foreach ($products as $product) {
                $supplier_id = get_post_meta($product->ID, '_product_supplier', true);
                if ($supplier_id && !in_array($supplier_id, $supplier_ids)) {
                    $supplier_ids[] = $supplier_id;
                }
            }
            
            if (!empty($supplier_ids)) {
                $supplier_args = array(
                    'post_type' => 'supplier',
                    'posts_per_page' => $per_page,
                    'paged' => $page,
                    'post__in' => $supplier_ids,
                    'post_status' => 'publish',
                    'orderby' => 'title',
                    'order' => 'ASC'
                );
                
                $supplier_query = new WP_Query($supplier_args);
                $results['suppliers'] = $supplier_query->posts;
                $results['total_suppliers'] = $supplier_query->found_posts;
                $results['total_pages'] = $supplier_query->max_num_pages;
            }
        }
        
        return $results;
    }
    
    public function ajax_search_suppliers() {
        check_ajax_referer('spm_nonce', 'nonce');
        
        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        
        if (empty($keyword)) {
            wp_send_json_error(array('message' => __('Please enter a search term.', 'supplier-product-manager')));
        }
        
        $results = $this->perform_search($keyword, $page);
        
        ob_start();
        
        if ($results['suppliers']) {
            echo '<div class="spm-suppliers-accordion">';
            foreach ($results['suppliers'] as $supplier) {
                $frontend_display = new FrontendDisplay();
                $frontend_display->display_supplier_item($supplier->ID);
            }
            echo '</div>';
            
            if ($results['total_pages'] > $page) {
                echo '<div class="spm-load-more-container">';
                echo '<button class="spm-load-more-search" data-keyword="' . esc_attr($keyword) . '" data-page="' . ($page + 1) . '" data-max-pages="' . $results['total_pages'] . '">' . __('Load More Results', 'supplier-product-manager') . '</button>';
                echo '</div>';
            }
        } else {
            echo '<p class="spm-no-results">' . __('No results found.', 'supplier-product-manager') . '</p>';
        }
        
        $content = ob_get_clean();
        
        wp_send_json_success(array(
            'content' => $content,
            'total' => $results['total_suppliers'],
            'page' => $page,
            'has_more' => $results['total_pages'] > $page
        ));
    }
    
    public function handle_search_redirect() {
        if (isset($_GET['spm_search']) && $_GET['spm_search'] == 1 && !empty($_GET['search_keyword'])) {
            // Create search results page if it doesn't exist
            $this->create_search_results_page();
            
            // Redirect to search results page
            $search_page = get_page_by_path('supplier-search-results');
            if ($search_page) {
                wp_redirect(add_query_arg(array(
                    'search_keyword' => $_GET['search_keyword'],
                    'spm_page' => isset($_GET['spm_page']) ? intval($_GET['spm_page']) : 1
                ), get_permalink($search_page->ID)));
                exit;
            }
        }
    }
    
    private function create_search_results_page() {
        $page = get_page_by_path('supplier-search-results');
        
        if (!$page) {
            $page_data = array(
                'post_title' => 'Supplier Search Results',
                'post_name' => 'supplier-search-results',
                'post_content' => '[supplier_search_results]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => 1
            );
            
            wp_insert_post($page_data);
        }
    }
}