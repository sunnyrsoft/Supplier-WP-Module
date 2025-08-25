jQuery(document).ready(function($) {
    var currentTab = 'suppliers';
    var suppliersLoaded = false;
    var categoriesLoaded = false;
    var suppliersContent = '';
    var categoriesContent = '';
    
    // Tab functionality
    $('.spm-tab').on('click', function() {
        var tab = $(this).data('tab');
        
        if (tab === currentTab) return;
        
        // Update active tab
        $('.spm-tab').removeClass('active');
        $(this).addClass('active');
        
        // Show loading state
        $('.spm-tab-pane').removeClass('active');
        $('#' + tab + '-tab').addClass('active').html('<div class="loading">Loading content...</div>');
        
        currentTab = tab;
        
        // Load content based on tab
        if (tab === 'suppliers') {
            loadSuppliersTab();
        } else if (tab === 'categories') {
            loadCategoriesTab();
        }
    });
    
    function loadSuppliersTab() {
        if (!suppliersLoaded) {
            // Load suppliers via AJAX for the first time
            $.ajax({
                url: spm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_suppliers_list',
                    page: 1,
                    nonce: spm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        suppliersContent = response.data.content;
                        $('#suppliers-tab').html(suppliersContent);
                        suppliersLoaded = true;
                        initAccordion();
                    }
                },
                error: function() {
                    $('#suppliers-tab').html('<p>Error loading suppliers. Please try again.</p>');
                }
            });
        } else {
            // Already loaded, use cached content
            $('#suppliers-tab').html(suppliersContent);
            initAccordion();
        }
    }
    
    function loadCategoriesTab() {
        if (!categoriesLoaded) {
            // Load categories via AJAX for the first time
            $.ajax({
                url: spm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_categories_list',
                    page: 1,
                    nonce: spm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        categoriesContent = response.data.content;
                        $('#categories-tab').html(categoriesContent);
                        categoriesLoaded = true;
                        initCategoryAccordion();
                        initAccordion();
                    }
                },
                error: function() {
                    $('#categories-tab').html('<p>Error loading categories. Please try again.</p>');
                }
            });
        } else {
            // Already loaded, use cached content
            $('#categories-tab').html(categoriesContent);
            initCategoryAccordion();
            initAccordion();
        }
    }
    
    function initAccordion() {
        // Remove any existing click handlers to prevent duplication
        $('.spm-supplier-header').off('click.accordion');
        
        // Initialize accordion for newly loaded content
        $('.spm-supplier-header').on('click.accordion', function(e) {
            e.stopPropagation();
            
            var $item = $(this).closest('.spm-supplier-item');
            var $content = $item.find('.spm-supplier-content');
            var $toggle = $item.find('.spm-accordion-toggle');
            var isOpening = !$item.hasClass('active');
            
            // If we're opening this item, close others first
            if (isOpening) {
                // Close all other items smoothly
                $('.spm-supplier-item').not($item).each(function() {
                    var $otherItem = $(this);
                    var $otherContent = $otherItem.find('.spm-supplier-content');
                    var $otherToggle = $otherItem.find('.spm-accordion-toggle');
                    
                    if ($otherItem.hasClass('active')) {
                        $otherContent.stop(true, true).slideUp(300, function() {
                            $otherItem.removeClass('active');
                            $otherToggle.text('+');
                        });
                    }
                });
            }
            
            // Toggle current item with smooth animation
            if (isOpening) {
                // Opening
                $content.stop(true, true).slideDown(300, function() {
                    $item.addClass('active');
                    $toggle.text('−');
                });
            } else {
                // Closing
                $content.stop(true, true).slideUp(300, function() {
                    $item.removeClass('active');
                    $toggle.text('+');
                });
            }
        });
    }
    
    function initCategoryAccordion() {
        // Remove any existing click handlers to prevent duplication
        $('.spm-category-header').off('click.category');
        
        // Initialize category accordion for newly loaded content
        $('.spm-category-header').on('click.category', function(e) {
            e.stopPropagation();
            
            var $item = $(this).closest('.spm-category-item');
            var $content = $item.find('.spm-category-content');
            var $toggle = $item.find('.spm-category-toggle');
            var isOpening = !$item.hasClass('active');
            
            // Toggle current category
            if (isOpening) {
                // Opening
                $content.stop(true, true).slideDown(300, function() {
                    $item.addClass('active');
                    $toggle.text('▲');
                });
            } else {
                // Closing
                $content.stop(true, true).slideUp(300, function() {
                    $item.removeClass('active');
                    $toggle.text('▼');
                });
            }
        });
    }
    
    // Prevent event propagation for elements inside headers
    $(document).on('click', '.spm-supplier-header *, .spm-category-header *', function(e) {
        e.stopPropagation();
    });
    
    // Load more functionality for suppliers
    $(document).on('click', '.spm-load-more', function() {
        var $button = $(this);
        var page = $button.data('page');
        var maxPages = $button.data('max-pages');
        
        $button.prop('disabled', true).text('Loading...');
        
        $.ajax({
            url: spm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'load_more_suppliers',
                page: page,
                max_pages: maxPages,
                nonce: spm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Append new content
                    var $newContent = $(response.data.content);
                    var $newItems = $newContent.find('.spm-supplier-item');
                    
                    // Append the new items
                    $button.closest('.spm-load-more-container').before($newItems);
                    
                    // Update cached content
                    suppliersContent = $('#suppliers-tab').html();
                    
                    // Re-initialize accordion for new items
                    initAccordion();
                    
                    // Update load more button
                    if (response.data.has_more) {
                        $button.data('page', response.data.page + 1);
                        $button.prop('disabled', false).text('Load More Suppliers');
                    } else {
                        // Remove the load more button if no more pages
                        $button.closest('.spm-load-more-container').remove();
                    }
                }
            },
            error: function() {
                $button.prop('disabled', false).text('Load More Suppliers');
                alert('Error loading more suppliers. Please try again.');
            }
        });
    });
    
    // Load more functionality for categories
    $(document).on('click', '.spm-load-more-categories', function() {
        var $button = $(this);
        var page = $button.data('page');
        var maxPages = $button.data('max-pages');
        
        $button.prop('disabled', true).text('Loading...');
        
        $.ajax({
            url: spm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'load_more_categories',
                page: page,
                max_pages: maxPages,
                nonce: spm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Append new content
                    var $newContent = $(response.data.content);
                    var $newItems = $newContent.find('.spm-category-item');
                    
                    // Append the new items
                    $button.closest('.spm-load-more-categories-container').before($newItems);
                    
                    // Update cached content
                    categoriesContent = $('#categories-tab').html();
                    
                    // Re-initialize accordion for new items
                    initCategoryAccordion();
                    initAccordion();
                    
                    // Update load more button
                    if (response.data.has_more) {
                        $button.data('page', response.data.page + 1);
                        $button.prop('disabled', false).text('Load More Categories');
                    } else {
                        // Remove the load more button if no more pages
                        $button.closest('.spm-load-more-categories-container').remove();
                    }
                }
            },
            error: function() {
                $button.prop('disabled', false).text('Load More Categories');
                alert('Error loading more categories. Please try again.');
            }
        });
    });
    
    // Initial load
    loadSuppliersTab();





// Search functionality
$(document).on('click', '.spm-load-more-search', function() {
    var $button = $(this);
    var keyword = $button.data('keyword');
    var page = $button.data('page');
    var maxPages = $button.data('max-pages');
    
    $button.prop('disabled', true).text('Loading...');
    
    $.ajax({
        url: spm_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'search_suppliers',
            keyword: keyword,
            page: page,
            max_pages: maxPages,
            nonce: spm_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                // Append new content
                var $newContent = $(response.data.content);
                var $newItems = $newContent.find('.spm-supplier-item');
                
                // Append the new items
                $button.closest('.spm-load-more-container').before($newItems);
                
                // Re-initialize accordion for new items
                initAccordion();
                
                // Update load more button
                if (response.data.has_more) {
                    $button.data('page', response.data.page + 1);
                    $button.prop('disabled', false).text('Load More Results');
                } else {
                    // Remove the load more button if no more pages
                    $button.closest('.spm-load-more-container').remove();
                }
            }
        },
        error: function() {
            $button.prop('disabled', false).text('Load More Results');
            alert('Error loading more results. Please try again.');
        }
    });
});

// Live search functionality (optional)
$('.spm-search-input').on('keyup', function(e) {
    if (e.key === 'Enter') {
        $(this).closest('form').submit();
    }
});



    
});