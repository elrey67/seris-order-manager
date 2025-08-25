jQuery(document).ready(function($) {
    // Handle logo upload
    $('#serisvri-upload-logo').on('click', function(e) {
        e.preventDefault();
        
        var fileFrame = wp.media({
            title: serisvri_vars.title,
            button: {
                text: serisvri_vars.button
            },
            multiple: false
        });
        
        fileFrame.on('select', function() {
            var attachment = fileFrame.state().get('selection').first().toJSON();
            $('#serisvri_company_logo').val(attachment.url);
            
            // Update or create the preview
            var $preview = $('#serisvri-logo-preview');
            if ($preview.length) {
                $preview.find('img').attr('src', attachment.url);
            } else {
                $('#serisvri_company_logo').after(
                    '<div id="serisvri-logo-preview" style="margin-top:10px;">' +
                    '<img src="' + attachment.url + '" style="max-height:100px;">' +
                    '</div>'
                );
            }
        });
        
        fileFrame.open();
    });
    
    // Toggle custom paper size fields
    $('#serisvri_paper_size').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#serisvri-custom-paper-size-fields').show();
        } else {
            $('#serisvri-custom-paper-size-fields').hide();
        }
    });
    
    // Update preview when form is submitted
    $('#serisvri-settings-form').on('submit', function(e) {
        // Let the form submit normally first
        setTimeout(function() {
            // After settings are saved, update the iframe
            var iframe = $('#serisvri-preview-iframe');
            iframe.attr('src', iframe.attr('src').split('?')[0] + '?' + Math.random());
        }, 500);
    });
});