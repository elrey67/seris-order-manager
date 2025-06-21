<?php
/**
 * Receipt Template
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Verify order exists
$serisvri_order = wc_get_order($order_id);
if (!$serisvri_order) {
    wp_die('Invalid order');
}

// Get company info
$serisvri_company_name = esc_html(get_option('serisvri_company_name'));
$serisvri_company_address = nl2br(esc_html(get_option('serisvri_company_address')));
$serisvri_company_logo_url = esc_url(get_option('serisvri_company_logo'));
$serisvri_company_logo_id = attachment_url_to_postid($serisvri_company_logo_url); // Get attachment ID if available

// Order details
$serisvri_order_date = $serisvri_order->get_date_created();
$serisvri_formatted_date = $serisvri_order_date->date('F j, Y');
$serisvri_formatted_time = $serisvri_order_date->date('g:i A');

// Paper size 
$serisvri_paper_size = get_option('serisvri_paper_size', 'a4');
$serisvri_dimensions = [
    'a4' => ['width' => '205mm', 'height' => '297mm', 'margin' => '5mm'],
    '2_25_inch' => ['width' => '57mm', 'height' => 'auto', 'margin' => '2mm'],
    '2_5_inch' => ['width' => '77mm', 'height' => 'auto', 'margin' => '2mm'],
    'custom' => [
        'width' => max(10, get_option('serisvri_custom_paper_width', 210)) . 'mm',
        'height' => max(10, get_option('serisvri_custom_paper_height', 297)) . 'mm',
        'margin' => '5mm'
    ]
];
$serisvri_size = $serisvri_dimensions[$serisvri_paper_size] ?? $serisvri_dimensions['a4'];

// Determine font size based on paper width
$serisvri_width_mm = (float) str_replace('mm', '', $serisvri_size['width']);
$serisvri_sub_item_font_size = $serisvri_width_mm < 100 ? '14px' : '16px';

// QR Code Generation (raw data first)
$serisvri_qrUrl = add_query_arg('order_id', $order_id, home_url('/'));
$serisvri_qrCode = new Endroid\QrCode\QrCode($serisvri_qrUrl);
$serisvri_writer = new Endroid\QrCode\Writer\PngWriter();
$serisvri_qrCodeImage = $serisvri_writer->write($serisvri_qrCode)->getString();

$serisvri_currency = $serisvri_order->get_currency();

// Enqueue the receipt stylesheet
wp_enqueue_style(
    'seris-order-manager-receipt',
    SERISVRI_SLIPS_URL . '/assets/css/receipt.css',
    array(),
    SERISVRI_SLIPS_VERSION
);

// Output the HTML
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <title><?php esc_html_e('Order Receipt', 'seris-order-manager'); ?></title>
    <?php wp_print_styles('seris-order-manager-receipt'); ?>
</head>
<body>
    <div class="receipt-container" style="width: <?php echo esc_attr($serisvri_size['width']); ?>;">
        <div class="company-header">
            <?php if ($serisvri_company_logo_url) : ?>
                <?php if ($serisvri_company_logo_id) : ?>
                    <?php echo wp_get_attachment_image(
                        $serisvri_company_logo_id,
                        'medium',
                        false,
                        [
                            'class' => 'company-logo',
                            'alt' => esc_attr__('Company Logo', 'seris-order-manager')
                        ]
                    ); ?>
                <?php else : ?>
                    <img src="<?php echo esc_url($serisvri_company_logo_url); ?>" 
                         class="company-logo" 
                         alt="<?php esc_attr_e('Company Logo', 'seris-order-manager'); ?>">
                <?php endif; ?>
            <?php endif; ?>
            <h3><?php echo esc_html($serisvri_company_name); ?></h3>
            <div class="company-address"><?php echo wp_kses_post($serisvri_company_address); ?></div>
        </div>
        
        <p><strong><?php esc_html_e('Date:', 'seris-order-manager'); ?></strong> <?php echo esc_html($serisvri_formatted_date); ?></p>
        <p><strong><?php esc_html_e('Time:', 'seris-order-manager'); ?></strong> <?php echo esc_html($serisvri_formatted_time); ?></p>
        <h4><?php 
            /* translators: %s: Order ID number */
            echo esc_html(sprintf(__('Order Receipt #%s', 'seris-order-manager'), $serisvri_order->get_id())); 
        ?></h4>
        <hr style="border: 1px solid #000;">
        
        <table>
            <thead>
                <tr>
                    <th style="border-bottom: 2px solid #000;"><?php esc_html_e('Item', 'seris-order-manager'); ?></th>
                    <th style="border-bottom: 2px solid #000;"><?php esc_html_e('Qty', 'seris-order-manager'); ?></th>
                    <th style="border-bottom: 2px solid #000;"><?php esc_html_e('Price', 'seris-order-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($serisvri_order->get_items() as $serisvri_item) : ?>
                <tr>
                    <td style="font-size: <?php echo esc_attr($serisvri_sub_item_font_size); ?>;"><?php echo esc_html($serisvri_item->get_name()); ?></td>
                    <td style="font-size: <?php echo esc_attr($serisvri_sub_item_font_size); ?>;"><?php echo esc_html($serisvri_item->get_quantity()); ?></td>
                    <td style="font-size: <?php echo esc_attr($serisvri_sub_item_font_size); ?>;"><?php echo wp_kses_post(wc_price($serisvri_item->get_total(), ['currency' => $serisvri_currency])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <!-- Subtotal -->
                <tr>
                    <td colspan="2" style="text-align: right;">
                        <strong style="font-size: <?php echo esc_attr($serisvri_sub_item_font_size); ?>;"><?php esc_html_e('Subtotal:', 'seris-order-manager'); ?></strong>
                    </td>
                    <td class="price-cell" style="font-size: <?php echo esc_attr($serisvri_sub_item_font_size); ?>;">
                        <?php echo wp_kses_post(wc_price($serisvri_order->get_subtotal(), ['currency' => $serisvri_currency])); ?>
                    </td>
                </tr>

                <!-- Shipping -->
                <?php if ($serisvri_order->get_shipping_total()) : ?>
                <tr>
                    <td colspan="2" style="text-align: right;">
                        <strong style="font-size: <?php echo esc_attr($serisvri_sub_item_font_size); ?>;"><?php esc_html_e('Shipping:', 'seris-order-manager'); ?></strong>
                    </td>
                    <td class="price-cell" style="font-size: <?php echo esc_attr($serisvri_sub_item_font_size); ?>;">
                        <?php echo wp_kses_post(wc_price($serisvri_order->get_shipping_total(), ['currency' => $serisvri_currency])); ?>
                    </td>
                </tr>
                <?php endif; ?>

                <!-- Taxes -->
                <?php 
                $serisvri_tax_totals = $serisvri_order->get_tax_totals();
                if (!empty($serisvri_tax_totals)) : ?>
                    <?php foreach ($serisvri_tax_totals as $serisvri_code => $serisvri_tax) : ?>
                    <tr>
                        <td colspan="2" style="text-align: right;">
                            <strong style="font-size: <?php echo esc_attr($serisvri_sub_item_font_size); ?>;"><?php echo esc_html($serisvri_tax->label); ?>:</strong>
                        </td>
                        <td class="price-cell" style="font-size: <?php echo esc_attr($serisvri_sub_item_font_size); ?>;">
                            <?php echo wp_kses_post(wc_price($serisvri_tax->amount, ['currency' => $serisvri_currency])); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Total -->
                <tr class="total-row">
                    <td colspan="2" style="text-align: right;">
                        <strong style="font-size: <?php echo esc_attr($serisvri_sub_item_font_size); ?>;"><?php esc_html_e('Total:', 'seris-order-manager'); ?></strong>
                    </td>
                    <td class="price-cell" style="font-size: <?php echo esc_attr($serisvri_sub_item_font_size); ?>;">
                        <?php echo wp_kses_post(wc_price($serisvri_order->get_total(), ['currency' => $serisvri_currency])); ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Footer Section -->
        <div style="margin-top: 10px;" class="below-sub">
            <p><strong><?php esc_html_e('Payment Method:', 'seris-order-manager'); ?></strong> <?php echo esc_html($serisvri_order->get_payment_method_title()); ?></p>
            <div style="text-align: center; margin-top: 15px;">
                <?php 
                // QR code is dynamically generated - must use direct img tag
                ?>
                <img src="<?php echo esc_url('data:image/png;base64,' . base64_encode($serisvri_qrCodeImage)); ?>"
                     alt="<?php esc_attr_e('QR Code', 'seris-order-manager'); ?>" 
                     style="width:80px; height:80px;">
            </div>
            <?php if ($serisvri_custom_message = get_option('serisvri_custom_message')) : ?>
                <p style="text-align: center; margin-top: 10px;"><?php echo nl2br(esc_html($serisvri_custom_message)); ?></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>