jQuery(document).ready(function($) {
    // Select/Deselect all checkboxes
    $('#select-all-orders').on('click', function() {
        $('input[name="order_ids[]"]').prop('checked', this.checked);
    });

    // Bulk Receipts
    $('#bulk-print-receipts').on('click', function(e) {
        e.preventDefault();
        
        if (typeof scf_vars === 'undefined') {
            console.error('scf_vars is not defined');
            return;
        }

        var orders = $('input[name="order_ids[]"]:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (orders.length === 0) {
            alert('Please select at least one order.');
            return false;
        }
        
        // Submit form to generate bulk PDF
        $('form').attr('action', scf_vars.siteUrl + '?action=generate_bulk_pdf&pdf_type=receipt');
        $('form').submit();
    });

    // Bulk Packing Slips
    $('#bulk-print-slips').on('click', function(e) {
        e.preventDefault();
        
        if (typeof scf_vars === 'undefined') {
            console.error('scf_vars is not defined');
            return;
        }

        var orders = $('input[name="order_ids[]"]:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (orders.length === 0) {
            alert('Please select at least one order.');
            return false;
        }
        
        // Submit form to generate bulk PDF
        $('form').attr('action', scf_vars.siteUrl + '?action=generate_bulk_pdf&pdf_type=packing_slip');
        $('form').submit();
    });
});

