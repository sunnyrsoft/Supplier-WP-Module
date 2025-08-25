jQuery(document).ready(function($) {
    // Media uploader
    $('.spm-upload-button').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var target = button.data('target');
        var frame = wp.media({
            title: 'Select or Upload Media',
            button: {
                text: 'Use this media'
            },
            multiple: false
        });
        
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#' + target).val(attachment.url);
        });
        
        frame.open();
    });
    
    // Address management
    $('.button-add-address').on('click', function() {
        var addressesContainer = $(this).closest('.spm-addresses');
        var index = addressesContainer.find('.spm-address').length;
        var newAddress = addressesContainer.find('.spm-address:first').clone();
        
        newAddress.attr('data-index', index);
        newAddress.find('textarea').val('');
        newAddress.find('label').text('Address #' + (index + 1));
        newAddress.find('.button-remove-address').show();
        
        addressesContainer.find('.spm-address:last').after(newAddress);
    });
    
    $(document).on('click', '.button-remove-address', function() {
        if ($('.spm-address').length > 1) {
            $(this).closest('.spm-address').remove();
            // Renumber remaining addresses
            $('.spm-address').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('label').text('Address #' + (index + 1));
            });
        }
    });
});