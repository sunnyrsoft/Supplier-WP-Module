<?php
/**
 * Plugin Name: Supplier Product Manager
 * Plugin URI: https://yourwebsite.com
 * Description: Manage suppliers, categories, and products with custom fields
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: supplier-product-manager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SPM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SPM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPM_VERSION', '1.0.0');

// Include required files
require_once SPM_PLUGIN_PATH . 'includes/class-supplier-category-cpt.php';
require_once SPM_PLUGIN_PATH . 'includes/class-supplier-cpt.php';
require_once SPM_PLUGIN_PATH . 'includes/class-product-cpt.php';
require_once SPM_PLUGIN_PATH . 'includes/class-admin-interface.php';
require_once SPM_PLUGIN_PATH . 'includes/class-frontend-display.php';
require_once SPM_PLUGIN_PATH . 'includes/class-search-functionality.php'; // Add this line

// Initialize the plugin
class SupplierProductManager {
    
    private static $instance = null;
    private $supplier_category_cpt;
    private $supplier_cpt;
    private $product_cpt;
    private $admin_interface;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init_plugin'), 0); // Priority 0 to run early
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
public function init_plugin() {
    // Initialize CPT classes
    $this->supplier_category_cpt = new SupplierCategoryCPT();
    $this->supplier_cpt = new SupplierCPT();
    $this->product_cpt = new ProductCPT();
    
    // Initialize admin interface
    $this->admin_interface = new AdminInterface();
    
    // Initialize frontend display
    $this->frontend_display = new FrontendDisplay();
    
    // Initialize search functionality
    $this->search_functionality = new SearchFunctionality(); // Add this line
}
    
    public function enqueue_admin_scripts($hook) {
        if (in_array($hook, array('post.php', 'post-new.php'))) {
            $screen = get_current_screen();
            if ($screen && in_array($screen->post_type, array('supplier_category', 'supplier', 'product'))) {
                wp_enqueue_media();
                wp_enqueue_script('spm-admin', SPM_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SPM_VERSION, true);
                wp_enqueue_style('spm-admin', SPM_PLUGIN_URL . 'assets/css/admin.css', array(), SPM_VERSION);
            }
        }
    }
    
    public static function activate_plugin() {
        // Flush rewrite rules on activation
        flush_rewrite_rules();
    }
    
    public static function deactivate_plugin() {
        // Flush rewrite rules on deactivation
        flush_rewrite_rules();
    }
}

// Initialize the plugin
SupplierProductManager::get_instance();

// Activation hook
register_activation_hook(__FILE__, array('SupplierProductManager', 'activate_plugin'));

// Deactivation hook
register_deactivation_hook(__FILE__, array('SupplierProductManager', 'deactivate_plugin'));

// Flush rewrite rules after plugin activation
add_action('activated_plugin', function($plugin) {
    if ($plugin === plugin_basename(__FILE__)) {
        flush_rewrite_rules();
    }
});