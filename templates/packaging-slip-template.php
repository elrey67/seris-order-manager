<?php
/**
 * Packing Slip Template
 * 
 * @param WC_Order $order
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Extract variables for easier access in template
$serisvri_company_name = esc_html(Serisvri_Core::get_instance()->get_settings()->get_company_name());
$serisvri_company_logo_url = esc_url(Serisvri_Core::get_instance()->get_settings()->get_company_logo());
$serisvri_company_address = nl2br(esc_html(Serisvri_Core::get_instance()->get_settings()->get_company_address()));

// Order details
$serisvri_customer_name = esc_html($order->get_formatted_billing_full_name());
$serisvri_customer_email = esc_html($order->get_billing_email());
$serisvri_customer_phone = esc_html($order->get_billing_phone());
$serisvri_customer_address = $order->get_formatted_shipping_address() ?: $order->get_formatted_billing_address();

// Shipping information - FIXED: Use proper getter methods
$serisvri_shipping_methods = $order->get_shipping_methods();
$serisvri_shipping_method_names = array();

foreach ($serisvri_shipping_methods as $shipping_item) {
    if (is_a($shipping_item, 'WC_Order_Item_Shipping')) {
        // Use the proper getter method instead of direct property access
        $method_title = $shipping_item->get_method_title();
        if (!empty($method_title)) {
            $serisvri_shipping_method_names[] = esc_html($method_title);
        } else {
            // Fallback to get_name() if method_title is empty
            $serisvri_shipping_method_names[] = esc_html($shipping_item->get_name());
        }
    }
}

$serisvri_shipping_method = implode(', ', $serisvri_shipping_method_names);

// Custom shipping meta
$serisvri_shipping_company = esc_html(get_post_meta($order->get_id(), '_shipping_company', true));

// Barcode and QR code
$serisvri_barcode_generator = Serisvri_Core::get_instance()->get_barcode_generator();
$serisvri_tracking_barcode = $serisvri_barcode_generator->generate_with_status($order->get_id());

// QR Code Generation (raw data first)
$serisvri_qrUrl = add_query_arg('order_id', $order_id, home_url('/'));
$serisvri_qrCode = new Endroid\QrCode\QrCode($serisvri_qrUrl);
$serisvri_writer = new Endroid\QrCode\Writer\PngWriter();
$serisvri_qrCodeImage = $serisvri_writer->write($serisvri_qrCode)->getString();

// Get attachment ID from logo URL if it's a media library image
$serisvri_company_logo_id = attachment_url_to_postid($serisvri_company_logo_url);

// Ensure the packing slip stylesheet is properly enqueued
wp_enqueue_style(
    'seris-order-manager-packing-slip',
    SERISVRI_SLIPS_URL . '/assets/css/packing-slip.css',
    array(),
    SERISVRI_SLIPS_VERSION
);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo esc_html__('Packing Slip', 'seris-order-manager'); ?></title>
    <meta charset="UTF-8">
    <?php wp_print_styles('seris-order-manager-packing-slip'); ?>
</head>
<body>
    <div class="packaging-slip">
        <div class="packaging-header">
            <div class="company-logo-container">
                <?php if ($serisvri_company_logo_url) : ?>
                    <?php if ($serisvri_company_logo_id) : ?>
                        <?php echo wp_get_attachment_image(
                            $serisvri_company_logo_id,
                            'medium',
                            false,
                            array(
                                'alt' => esc_attr__('Company Logo', 'seris-order-manager'),
                                'class' => 'company-logo'
                            )
                        ); ?>
                    <?php else : ?>
                        <img src="<?php echo esc_url($serisvri_company_logo_url); ?>" 
                             alt="<?php echo esc_attr__('Company Logo', 'seris-order-manager'); ?>"
                             class="company-logo">
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div class="company-info">
                <p><?php echo esc_html($serisvri_company_name); ?></p>
                <p><?php echo wp_kses_post($serisvri_company_address); ?></p>
            </div>
            
            <hr class="header-separator">
        </div>

        <div class="package-details" style="margin:0px 0; padding:0;">
            <h3 style="margin:0px 0; padding:0;"><?php echo esc_html__('Package Details', 'seris-order-manager'); ?></h3>
            <p style="margin:0px 0; padding:0;"><strong><?php echo esc_html__('Name:', 'seris-order-manager'); ?></strong> <?php echo esc_html($serisvri_customer_name); ?></p>
            <p style="margin:0px 0; padding:0;"><strong><?php echo esc_html__('Email:', 'seris-order-manager'); ?></strong> <?php echo esc_html($serisvri_customer_email); ?></p>
            <p style="margin:0px 0; padding:0;"><strong><?php echo esc_html__('Address:', 'seris-order-manager'); ?></strong> <?php echo wp_kses_post($serisvri_customer_address); ?></p>
            <p style="margin:0px 0; padding:0;"><strong><?php echo esc_html__('Phone:', 'seris-order-manager'); ?></strong> <?php echo esc_html($serisvri_customer_phone); ?></p>
            
            <h3 style="margin:0px 0; padding:0;"><?php echo esc_html__('Shipping Details', 'seris-order-manager'); ?></h3>
            <p style="margin:0px 0; padding:0;"><strong><?php echo esc_html__('Method:', 'seris-order-manager'); ?></strong> <?php echo esc_html($serisvri_shipping_method); ?></p>
            <p style="margin:0px 0; padding:0;"><strong><?php echo esc_html__('Company:', 'seris-order-manager'); ?></strong> 
                <?php echo esc_html($serisvri_shipping_company ?: esc_html__('Self Shipping', 'seris-order-manager')); ?>
            </p>
        </div>

        <div class="qr-code-container">
            <?php 
            // Dynamically generated QR code - cannot use wp_get_attachment_image
            // This is a data URI image generated on the fly
            ?>
            <img src="<?php echo esc_url('data:image/png;base64,' . base64_encode($serisvri_qrCodeImage)); ?>"
                 alt="<?php echo esc_attr__('QR Code', 'seris-order-manager'); ?>" 
                 class="qr-code-image"
                 style="width:80px; height:80px;">
        </div>

        <h3 style="text-align: center;"><?php echo esc_html__('Tracking Barcode', 'seris-order-manager'); ?></h3>
        <?php if ($serisvri_tracking_barcode) : ?>
            <div class="barcode-container">
                <?php 
                // Dynamically generated barcode - cannot use wp_get_attachment_image
                // This is a generated image URL from our barcode generator
                ?>
                <img src="<?php echo esc_url($serisvri_tracking_barcode); ?>" 
                     alt="<?php echo esc_attr__('Tracking Barcode', 'seris-order-manager'); ?>"
                     class="barcode-image">
            </div>
        <?php endif; ?>
    </div>
</body>
</html>