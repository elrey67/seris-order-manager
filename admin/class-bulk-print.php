<?php
/**
 * Handles bulk printing functionality for receipts and packing slips
 */

    if (!defined('ABSPATH')) {
        exit; // Exit if accessed directly
    }
    
class Serisvri_Bulk_Print {
    

    /**
     * Initialize bulk print functionality
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_bulk_print_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_post_serisvri_generate_bulk_pdf', array($this, 'handle_bulk_pdf_generation'));
    }

    /**
     * Add bulk print page to WooCommerce menu
     */
    public function add_bulk_print_page() {
        add_submenu_page(
            'woocommerce',
            __('Bulk Print Orders', 'seris-order-manager'),
            __('Bulk Print Orders', 'seris-order-manager'),
            'manage_woocommerce',
            'serisvri-bulk-print-orders',
            array($this, 'render_bulk_print_page')
        );
    }

    /**
     * Enqueue required scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ('woocommerce_page_serisvri-bulk-print-orders' !== $hook) {
            return;
        }
        
        // Enqueue scripts
        wp_enqueue_script(
            'serisvri-bulk-print',
            SERISVRI_SLIPS_URL . 'assets/js/bulk-print.js',
            array('jquery'),
            SERISVRI_SLIPS_VERSION,
            true
        );
        
        // Localize script with required data
        wp_localize_script(
            'serisvri-bulk-print',
            'serisvriBulkPrintVars',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('serisvri_bulk_print_nonce'),
                'selectAllText' => __('Select All', 'seris-order-manager'),
                'deselectAllText' => __('Deselect All', 'seris-order-manager')
            )
        );
        
        // Enqueue styles
        wp_enqueue_style(
            'serisvri-bulk-print',
            SERISVRI_SLIPS_URL . 'assets/css/bulk-print.css',
            array(),
            SERISVRI_SLIPS_VERSION
        );
    }

    /**
     * Render the bulk print page
     */
    public function render_bulk_print_page() {
        // Get completed orders
        $orders = wc_get_orders(array(
            'status' => 'completed',
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        // Include the view template
        include SERISVRI_SLIPS_PATH . 'admin/views/bulk-print-page.php';
    }

    /**
     * Handle bulk PDF generation
     */
    public function handle_bulk_pdf_generation() {
        try {
            // Verify nonce with proper unslashing and sanitization
            if (!isset($_POST['serisvri_bulk_pdf_nonce']) || 
                !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['serisvri_bulk_pdf_nonce'])), 'serisvri_bulk_pdf_action')) {
                throw new Exception(__('Security check failed', 'seris-order-manager'));
            }
            
            // Check capabilities
            if (!current_user_can('manage_woocommerce')) {
                throw new Exception(__('Permission denied', 'seris-order-manager'));
            }
            
            // Get and validate order IDs with proper sanitization
            $order_ids = isset($_POST['order_ids']) ? array_map('absint', (array) wp_unslash($_POST['order_ids'])) : array();
            if (empty($order_ids)) {
                throw new Exception(__('No orders selected', 'seris-order-manager'));
            }
            
            // Get and validate document type with proper unslashing and sanitization
            $pdf_type = isset($_POST['pdf_type']) ? sanitize_text_field(wp_unslash($_POST['pdf_type'])) : 'receipt';
            if (!in_array($pdf_type, array('receipt', 'packing_slip'), true)) {
                throw new Exception(__('Invalid document type', 'seris-order-manager'));
            }
            
            // Get PDF generator instance
            $pdf_generator = Serisvri_Core::get_instance()->get_pdf_generator();
            if (!$pdf_generator || !method_exists($pdf_generator, 'generate_bulk_pdf')) {
                throw new Exception(__('PDF generator not available', 'seris-order-manager'));
            }
            
            // Generate the PDF
            $pdf_generator->generate_bulk_pdf($order_ids, $pdf_type);
            
        } catch (Exception $e) {
            // Log the error (commented out for production)
            // error_log('Bulk PDF Generation Error: ' . $e->getMessage());
            
            // Display error message
            wp_die(
                esc_html($e->getMessage()),
                esc_html__('Bulk Print Error', 'seris-order-manager'),
                array('response' => 403)
            );
        }
    }
}