jQuery(document).ready(function($) {

    // Function to update preview size class
    function updatePaperClass() {
        var paperSize = $('#paper_size').val();
        $('#preview').removeClass('paper-a4 paper-2_25_inch paper-4x6').addClass('paper-' + paperSize);
    }

    // Bind change event to the paper size dropdown
    $('#paper_size').change(updatePaperClass);

    // Initialize the paper size on page load
    updatePaperClass();

    // Shipping company toggle
    function toggleShippingCompanyInput() {
        if ($('#custom_shipping').val() === 'third_party_shipping') {
            $('#shipping_company_field').show();
        } else {
            $('#shipping_company_field').hide();
            $('#shipping_company').val('');
        }
    }

    // Initialize and bind events
    toggleShippingCompanyInput();
    $('#custom_shipping').on('change', toggleShippingCompanyInput);
});