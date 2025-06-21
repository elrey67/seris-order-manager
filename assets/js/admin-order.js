jQuery(document).ready(function($) {
    $('#custom_shipping').on('change', function() {
        $('#shipping_company_field').toggle($(this).val() === 'third_party_shipping');
    });
});


jQuery(document).ready(function($) {
    // Debug output
    console.log('Admin order JS loaded');
    console.log('seris_vars:', seris_vars); // Verify the data is available
    
    // Print Receipt
    $(document).on('click', '.seris-receipt-btn', function(e) {
        e.preventDefault();
        if (typeof seris_vars !== 'undefined') {
            window.open(seris_vars.receiptUrl, '_blank');
        } else {
            console.error('seris_vars is not defined');
        }
    });
    
    // Print Packing Slip
    $(document).on('click', '.seris-packing-slip-btn', function(e) {
        e.preventDefault();
        if (typeof seris_vars !== 'undefined') {
            window.open(seris_vars.packingSlipUrl, '_blank');
        } else {
            console.error('seris_vars is not defined');
        }
    });
});

// Add this to your admin-order.js for debugging:
jQuery(document).ready(function($) {
    console.log('Admin order JS loaded'); // Check if this appears in browser console
    
    $('.scf-receipt-btn, .scf-packing-slip-btn').on('click', function() {
        console.log('Button clicked:', this); // Verify click events are registered
    });
});