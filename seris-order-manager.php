<?php
/*
Plugin Name: Seris Order Manager
Plugin URI:  
Description: Elevate your WooCommerce store's order processing with professional receipts and packing slips
Version:     1.0.0
Author:      Faveren Caleb
License:     GPL2
Text Domain: seris-order-manager
Domain Path: /languages
Requires at least: 5.6
Requires PHP: 7.2
Requires Plugins: woocommerce
*/

// Prevent direct access
defined('ABSPATH') || exit;

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'serisvri_woocommerce_missing_notice');
    return;
}

function serisvri_woocommerce_missing_notice() {
    echo '<div class="notice notice-error"><p>';
    printf(
        /* translators: %s: WooCommerce download link */
        esc_html__('Seris Order Manager requires WooCommerce to be installed and active. You can download %s here.', 'seris-order-manager'),
        '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>'
    );
    echo '</p></div>';
}

// Define plugin constants with conditional checks to prevent duplicates
if (!defined('SERISVRI_SLIPS_VERSION')) {
    define('SERISVRI_SLIPS_VERSION', '1.0.0');
}

if (!defined('SERISVRI_PLUGIN_FILE')) {
    define('SERISVRI_PLUGIN_FILE', __FILE__);
}

if (!defined('SERISVRI_SLIPS_PATH')) {
    define('SERISVRI_SLIPS_PATH', plugin_dir_path(__FILE__));
}

if (!defined('SERISVRI_SLIPS_URL')) {
    define('SERISVRI_SLIPS_URL', plugin_dir_url(__FILE__));
}

if (!defined('SERISVRI_SLIPS_INCLUDES_DIR')) {
    define('SERISVRI_SLIPS_INCLUDES_DIR', SERISVRI_SLIPS_PATH . 'includes/');
}

if (!defined('SERISVRI_SLIPS_ADMIN_DIR')) {
    define('SERISVRI_SLIPS_ADMIN_DIR', SERISVRI_SLIPS_PATH . 'admin/');
}

if (!defined('SERISVRI_SLIPS_ASSETS_DIR')) {
    define('SERISVRI_SLIPS_ASSETS_DIR', SERISVRI_SLIPS_PATH . 'assets/');
}

if (!defined('SERISVRI_SLIPS_ASSETS_URL')) {
    define('SERISVRI_SLIPS_ASSETS_URL', SERISVRI_SLIPS_URL . 'assets/');
}

// Check for required dependencies
if (!file_exists(SERISVRI_SLIPS_PATH . 'vendor/autoload.php')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        esc_html_e('Seris Order Manager Error: Please run "composer install" in the plugin directory.', 'seris-order-manager');
        echo '</p></div>';
    });
    return;
}

// Load Composer autoloader
require_once SERISVRI_SLIPS_PATH . 'vendor/autoload.php';

// Include core files
require_once SERISVRI_SLIPS_INCLUDES_DIR . 'class-core.php';
require_once SERISVRI_SLIPS_INCLUDES_DIR . 'class-pdf-generator.php';
require_once SERISVRI_SLIPS_INCLUDES_DIR . 'class-barcode-generator.php';
require_once SERISVRI_SLIPS_INCLUDES_DIR . 'class-qrcode-generator.php';
require_once SERISVRI_SLIPS_INCLUDES_DIR . 'class-order-handler.php';
require_once SERISVRI_SLIPS_INCLUDES_DIR . 'class-settings.php';
require_once SERISVRI_SLIPS_INCLUDES_DIR . 'helpers.php';

// Include admin files only in admin area
if (is_admin()) {
    require_once SERISVRI_SLIPS_ADMIN_DIR . 'class-admin.php';
    require_once SERISVRI_SLIPS_ADMIN_DIR . 'class-bulk-print.php';
}

/**
 * Register and enqueue all plugin styles and scripts
 */
function serisvri_register_assets() {
    // Register admin styles
    wp_register_style(
        'serisvri-admin',
        SERISVRI_SLIPS_ASSETS_URL . 'css/admin.css',
        array(),
        SERISVRI_SLIPS_VERSION
    );
    
    // Register iframe styles
    wp_register_style(
        'serisvri-iframe',
        SERISVRI_SLIPS_ASSETS_URL . 'css/iframe.css',
        array(),
        SERISVRI_SLIPS_VERSION
    );
    
    // Register packing slip styles
    wp_register_style(
        'serisvri-packing-slip',
        SERISVRI_SLIPS_ASSETS_URL . 'css/packing-slip.css',
        array(),
        SERISVRI_SLIPS_VERSION
    );
}
add_action('init', 'serisvri_register_assets');

/**
 * Enqueue admin assets
 */
function serisvri_enqueue_admin_assets($hook) {
    if ('toplevel_page_serisvri-settings' === $hook) {
        wp_enqueue_style('serisvri-admin');
        wp_enqueue_media();
    }
}
add_action('admin_enqueue_scripts', 'serisvri_enqueue_admin_assets');

/**
 * Initialize the plugin
 */
function serisvri_init_plugin() {
    // Load text domain for translations
    load_plugin_textdomain(
        'seris-order-manager',
        false,
        dirname(plugin_basename(SERISVRI_PLUGIN_FILE)) . '/languages'
    );

    try {
        $plugin = Serisvri_Core::get_instance();
        $plugin->run();
    } catch (Exception $e) {
        if (is_admin()) {
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>';
                esc_html_e('Seris Order Manager Error: ', 'seris-order-manager');
                echo esc_html($e->getMessage());
                echo '</p></div>';
            });
        }
    }
}
add_action('plugins_loaded', 'serisvri_init_plugin');

/**
 * AJAX handler for bulk PDF generation
 */
add_action('wp_ajax_serisvri_generate_bulk_pdf', 'serisvri_handle_generate_bulk_pdf');
function serisvri_handle_generate_bulk_pdf() {
    try {
        // Verify nonce and check document_type exists
        if (!isset($_POST['_wpnonce'], $_POST['document_type']) || 
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'print_' . sanitize_text_field(wp_unslash($_POST['document_type'])))) {
            throw new Exception(esc_html__('Security check failed.', 'seris-order-manager'));
        }

        // Check permissions
        if (!current_user_can('edit_shop_orders')) {
            throw new Exception(esc_html__('Permission denied.', 'seris-order-manager'));
        }

        // Get order IDs
        $order_ids = isset($_POST['order_ids']) ? array_map('absint', $_POST['order_ids']) : array();
        if (empty($order_ids)) {
            throw new Exception(esc_html__('No orders selected.', 'seris-order-manager'));
        }

        $document_type = sanitize_text_field(wp_unslash($_POST['document_type']));
        $pdf_url = serisvri_generate_bulk_pdf_document($order_ids, $document_type);
        
        wp_send_json_success(array(
            'url' => esc_url($pdf_url),
            'message' => esc_html__('PDF generated successfully.', 'seris-order-manager')
        ));
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => esc_html($e->getMessage())
        ));
    }
}

/**
 * Generate bulk PDF document
 */
function serisvri_generate_bulk_pdf_document($order_ids, $document_type) {
    $upload_dir = wp_upload_dir();
    $filename = sanitize_file_name($document_type . '_bulk_' . time() . '.pdf');
    $filepath = trailingslashit($upload_dir['path']) . $filename;
    
    // Get the core plugin instance
    $serisvri_core = Serisvri_Core::get_instance();
    
    // Generate PDF content based on document type
    $html_content = '';
    foreach ($order_ids as $order_id) {
        if ($document_type === 'receipt') {
            $html_content .= $serisvri_core->get_pdf_generator()->generate_receipt_html($order_id);
        } else {
            $html_content .= $serisvri_core->get_pdf_generator()->generate_packing_slip_html($order_id);
        }
    }
    
    // Generate PDF
    $dompdf = $serisvri_core->get_pdf_generator()->get_dompdf_instance();
    $dompdf->loadHtml($html_content);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Save the PDF file
    file_put_contents($filepath, $dompdf->output());
    
    return trailingslashit($upload_dir['url']) . $filename;
}

/**
 * AJAX handler to get a fresh nonce
 */
add_action('wp_ajax_serisvri_get_fresh_nonce', 'serisvri_get_fresh_nonce');
function serisvri_get_fresh_nonce() {
    check_ajax_referer('serisvri_ajax_nonce', 'security');
    
    wp_send_json_success(array(
        'nonce' => wp_create_nonce('serisvri_preview_nonce')
    ));
}

/**
 * Render preview iframe
 */
add_action('wp_ajax_serisvri_render_preview', 'serisvri_render_preview_iframe');
function serisvri_render_preview_iframe() {
    // Verify nonce first with proper sanitization
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'serisvri_preview_action')) {
        wp_die(esc_html__('Security check failed', 'seris-order-manager'));
    }

    // Initialize all variables with safe defaults
    $safe_params = [
        'paper_size' => 'a4',
        'custom_width' => 210,
        'custom_height' => 297,
        'company_name' => '',
        'company_address' => '',
        'company_logo' => '',
        'custom_message' => ''
    ];

    // Process and sanitize each parameter individually
    $safe_params['paper_size'] = isset($_GET['paper_size']) ? sanitize_key(wp_unslash($_GET['paper_size'])) : 'a4';
    $safe_params['custom_width'] = isset($_GET['custom_width']) ? absint(wp_unslash($_GET['custom_width'])) : 210;
    $safe_params['custom_height'] = isset($_GET['custom_height']) ? absint(wp_unslash($_GET['custom_height'])) : 297;

    // Sanitize text fields with proper unslashing
    if (isset($_GET['company_name'])) {
        $safe_params['company_name'] = sanitize_text_field(wp_unslash($_GET['company_name']));
    }

    if (isset($_GET['company_address'])) {
        $safe_params['company_address'] = sanitize_textarea_field(wp_unslash($_GET['company_address']));
    }

    if (isset($_GET['company_logo'])) {
        $safe_params['company_logo'] = esc_url_raw(wp_unslash($_GET['company_logo']));
        // Try to get attachment ID if this is a media library image
        $company_logo_id = attachment_url_to_postid($safe_params['company_logo']);
    }

    if (isset($_GET['custom_message'])) {
        $safe_params['custom_message'] = sanitize_textarea_field(wp_unslash($_GET['custom_message']));
    }

    // Set appropriate content type
    header('Content-Type: text/html; charset=utf-8');
    
    // Enqueue iframe style
    wp_enqueue_style('serisvri-iframe');
    
    $dimensions = [
        'a4' => ['width' => '210mm', 'height' => '297mm', 'margin' => '5mm'],
        '2_25_inch' => ['width' => '57mm', 'height' => '150mm', 'margin' => '2mm'],
        '2_5_inch' => ['width' => '77mm', 'height' => '150mm', 'margin' => '2mm'],
        'custom' => [
            'width' => max(10, $safe_params['custom_width']) . 'mm',
            'height' => max(10, $safe_params['custom_height']) . 'mm',
            'margin' => '5mm'
        ]
    ];
    
    $size = $dimensions[$safe_params['paper_size']] ?? $dimensions['a4'];
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <?php wp_print_styles('serisvri-iframe'); ?>
    </head>
    <body>
        <div class="paper-content" style="width: <?php echo esc_attr($size['width']); ?>; min-height: <?php echo esc_attr($size['height']); ?>; padding: <?php echo esc_attr($size['margin']); ?>;">
            <div class="company-header">
                <?php if (!empty($safe_params['company_logo'])) : ?>
                    <?php if (!empty($company_logo_id)) : ?>
                        <?php echo wp_get_attachment_image(
                            $company_logo_id,
                            'medium',
                            false,
                            [
                                'alt' => esc_attr__('Company Logo', 'seris-order-manager'),
                                'class' => 'company-logo'
                            ]
                        ); ?>
                    <?php else : ?>
                        <img src="<?php echo esc_url($safe_params['company_logo']); ?>" 
                             alt="<?php esc_attr_e('Company Logo', 'seris-order-manager'); ?>"
                             class="company-logo">
                    <?php endif; ?>
                <?php endif; ?>
                <h3><?php echo nl2br(esc_html($safe_params['company_name'])); ?></h3>
                <p class="company-address"><?php echo nl2br(esc_html($safe_params['company_address'])); ?></p>
                <hr>
            </div>
            <div class="preview-content">
                <p><?php esc_html_e('This is a preview of how your receipt will look when printed.', 'seris-order-manager'); ?></p>
                <?php if ($safe_params['custom_message']) : ?>
                    <p class="custom-message" style="text-align:center;"><?php echo nl2br(esc_html($safe_params['custom_message'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}