<?php
/**
 * Core Plugin Class
 * 
 * @package Seris_Order_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class Serisvri_Core {
    private static $instance = null;
    private $pdf_generator;
    private $barcode_generator;
    private $qrcode_generator;
    private $order_handler;
    private $settings;
    private $admin;
    private $bulk_print;

    private function __construct() {
        $this->verify_dependencies();
        $this->load_dependencies();
        $this->initialize_components();
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function verify_dependencies() {
        $required = [
            'Dompdf\Dompdf',
            'Picqer\Barcode\BarcodeGeneratorPNG',
            'Endroid\QrCode\QrCode'
        ];

        foreach ($required as $class) {
            if (!class_exists($class)) {
                throw new Exception(
                    sprintf(
                        /* translators: %s: Name of the required class that was not found */
                        esc_html__('Required class %s not found. Please run composer install.', 'seris-order-manager'),
                        esc_html($class)
                    )
                );
            }
        }
    }

    private function load_dependencies() {
        require_once SERISVRI_SLIPS_INCLUDES_DIR . 'class-pdf-generator.php';
        require_once SERISVRI_SLIPS_INCLUDES_DIR . 'class-barcode-generator.php';
        require_once SERISVRI_SLIPS_INCLUDES_DIR . 'class-qrcode-generator.php';
        require_once SERISVRI_SLIPS_INCLUDES_DIR . 'class-order-handler.php';
        require_once SERISVRI_SLIPS_INCLUDES_DIR . 'class-settings.php';
        
        if (is_admin()) {
            require_once SERISVRI_SLIPS_ADMIN_DIR . 'class-admin.php';
            require_once SERISVRI_SLIPS_ADMIN_DIR . 'class-bulk-print.php';
        }
    }

    private function initialize_components() {
        $this->pdf_generator = new Serisvri_PDF_Generator();
        $this->barcode_generator = new Serisvri_Barcode_Generator();
        $this->qrcode_generator = new Serisvri_QRCode_Generator();
        $this->order_handler = new Serisvri_Order_Handler();
        $this->settings = new Serisvri_Settings();
        
        if (is_admin()) {
            $this->admin = new Serisvri_Admin($this->settings);
            $this->bulk_print = new Serisvri_Bulk_Print();
        }
    }

    public function run() {
        register_activation_hook(SERISVRI_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(SERISVRI_PLUGIN_FILE, [$this, 'deactivate']);

        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('admin_enqueue_scripts', [$this, 'register_assets']);
    }

    public function activate() {
        $this->verify_dependencies();
        $this->settings->init_defaults();
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function init() {
        load_plugin_textdomain(
            'seris-order-manager',
            false,
            dirname(plugin_basename(SERISVRI_PLUGIN_FILE)) . '/languages'
        );

        $this->order_handler->init();
        $this->settings->init();
        
        if (is_admin()) {
            $this->admin->init();
            $this->bulk_print->init();
        }
    }

    public function register_assets() {
        // Register styles
        wp_register_style(
            'seris-order-manager-receipt',
            SERISVRI_SLIPS_ASSETS_DIR. 'css/receipt.css',
            [],
            SERISVRI_SLIPS_VERSION
        );
        
        wp_register_style(
            'seris-order-manager-packing-slip',
            SERISVRI_SLIPS_ASSETS_DIR. 'css/packing-slip.css',
            [],
            SERISVRI_SLIPS_VERSION
        );
        
        wp_register_style(
            'seris-order-manager-admin',
            SERISVRI_SLIPS_ASSETS_DIR. 'css/admin.css',
            [],
            SERISVRI_SLIPS_VERSION
        );
        
        wp_register_style(
            'seris-order-manager-bulk-print',
            SERISVRI_SLIPS_ASSETS_DIR. 'css/bulk-print.css',
            [],
            SERISVRI_SLIPS_VERSION
        );

        // Register scripts
        wp_register_script(
            'seris-order-manager-admin',
            SERISVRI_SLIPS_ASSETS_DIR. 'js/admin.js',
            ['jquery'],
            SERISVRI_SLIPS_VERSION,
            true
        );
        
        wp_register_script(
            'seris-order-manager-bulk-print',
            SERISVRI_SLIPS_ASSETS_DIR. 'js/bulk-print.js',
            ['jquery'],
            SERISVRI_SLIPS_VERSION,
            true
        );
        
        wp_localize_script('seris-order-manager-admin', 'serisvri_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('serisvri_nonce')
        ]);
    }

    // Getters for components
    public function get_pdf_generator() {
        return $this->pdf_generator;
    }

    public function get_barcode_generator() {
        return $this->barcode_generator;
    }

    public function get_qrcode_generator() {
        return $this->qrcode_generator;
    }

    public function get_order_handler() {
        return $this->order_handler;
    }

    public function get_settings() {
        return $this->settings;
    }
}