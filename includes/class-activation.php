<?php
class SPM_Activation {
    
    public static function activate() {
        // Register CPTs first
        $supplier_category_cpt = new SupplierCategoryCPT();
        $supplier_cpt = new SupplierCPT();
        $product_cpt = new ProductCPT();
        
        $supplier_category_cpt->register_cpt();
        $supplier_cpt->register_cpt();
        $product_cpt->register_cpt();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Register activation hook
register_activation_hook(__FILE__, array('SPM_Activation', 'activate'));