<?php
/**
 * Order Handler Class
 * 
 * @package Seris_Order_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class Serisvri_Order_Handler {
    
    /**
     * Initialize order handler
     */
    public function init() {
        add_action('admin_notices', array($this, 'serisvri_customization_notice'));
        // Register custom order status
        add_action('init', array($this, 'register_shipped_order_status'), 20);
        
        // Add custom order status to WooCommerce
        add_filter('wc_order_statuses', array($this, 'add_shipped_to_order_statuses'), 20);
        
        // Include shipped status in all queries
        add_filter('woocommerce_order_data_store_cpt_get_orders_query', array($this, 'include_shipped_in_all_queries'), 20, 2);
        
        // Force include in admin queries
        add_action('pre_get_posts', array($this, 'include_shipped_in_admin_queries'), 20);
        
        // Include in My Account
        add_filter('woocommerce_my_account_my_orders_query', array($this, 'include_shipped_in_my_account'), 20);
        
        // Ensure shipped status appears in admin counts
        add_filter('views_edit-shop_order', array($this, 'include_shipped_in_admin_counts'), 20);
        
        // Add order action buttons
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'add_order_buttons'));
        
        // Add custom shipping options
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'add_custom_shipping_option'));
        
        // Save custom shipping options
        add_action('woocommerce_process_shop_order_meta', array($this, 'save_custom_shipping_option'));
        
        // Add delivery confirmation in My Account orders list
        add_action('woocommerce_account_orders_actions', array($this, 'add_confirm_delivery_button'), 10, 2);
        
        // Add delivery confirmation to order view page
        add_action('woocommerce_order_details_after_order_table', array($this, 'add_confirm_delivery_button_to_order_view'), 5);
        
        // Handle delivery confirmation
        add_action('wp', array($this, 'handle_delivery_confirmation'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Localize scripts
        add_action('admin_enqueue_scripts', array($this, 'localize_admin_scripts'));
        
        // Add print actions
        add_action('admin_action_print_receipt', array($this, 'handle_print_receipt'));
        add_action('admin_action_print_packing_slip', array($this, 'handle_print_packing_slip'));
        
        // Handle mark as shipped action
        add_action('admin_action_mark_as_shipped', array($this, 'handle_mark_as_shipped'));
        
        // Handle bulk print action
        add_action('admin_action_bulk_print', array($this, 'handle_bulk_print'));
        
        // Add settings sections
        add_filter('woocommerce_get_sections_serisvri', array($this, 'add_customization_settings_section'));
        add_filter('woocommerce_get_settings_serisvri', array($this, 'add_customization_settings'), 10, 2);
        
        // Enqueue frontend styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'));
    }

    /**
     * Enqueue frontend styles
     */
    public function enqueue_frontend_styles() {
        if (is_account_page()) {
            wp_enqueue_style(
                'serisvri-delivery-confirmation',
                SERISVRI_SLIPS_URL . 'assets/css/delivery-confirmation.css',
                array(),
                SERISVRI_SLIPS_VERSION
            );
        }
    }

    /**
     * Add customization settings section
     */
    public function add_customization_settings_section($sections) {
        $sections['customization'] = __('Customization', 'seris-order-manager');
        return $sections;
    }

    /**
     * Add customization settings
     */
    public function add_customization_settings($settings, $current_section) {
        if ($current_section === 'customization') {
            $settings = array(
                array(
                    'title' => __('CSS Customization Guide', 'seris-order-manager'),
                    'type'  => 'title',
                    'desc'  => $this->get_css_guide_html(),
                    'id'    => 'serisvri_css_guide'
                ),
                array(
                    'type' => 'sectionend',
                    'id'   => 'serisvri_css_guide_end'
                )
            );
        }
        return $settings;
    }

    /**
     * Get CSS guide HTML
     */
    private function get_css_guide_html() {
        ob_start();
        ?>
        <div class="serisvri-css-guide">
            <h3><?php esc_html_e('How to Customize the Delivery Confirmation Section', 'seris-order-manager'); ?></h3>
            
            <p><?php esc_html_e('To customize the appearance of the delivery confirmation section, copy the CSS below and add it to:', 'seris-order-manager'); ?></p>
            
            <ul>
                <li><?php esc_html_e('Your theme\'s Customizer (Appearance → Customize → Additional CSS)', 'seris-order-manager'); ?></li>
                <li><?php esc_html_e('Elementor\'s Custom CSS (if using Elementor)', 'seris-order-manager'); ?></li>
                <li><?php esc_html_e('Any custom CSS plugin', 'seris-order-manager'); ?></li>
            </ul>
            
            <div class="css-code-container">
                <h4><?php esc_html_e('CSS Selectors for Customization:', 'seris-order-manager'); ?></h4>
                <pre><code><?php echo esc_html($this->get_css_template_with_comments()); ?></code></pre>
                
                <div class="css-copy-actions">
                    <button class="button button-primary serisvri-copy-css" data-clipboard-target=".css-code-container pre">
                        <?php esc_html_e('Copy CSS to Clipboard', 'seris-order-manager'); ?>
                    </button>
                    <span class="serisvri-copy-success" style="display:none; color:#46b450; margin-left:10px;">
                        <?php esc_html_e('CSS copied!', 'seris-order-manager'); ?>
                    </span>
                </div>
            </div>
            
            <h4><?php esc_html_e('Important Notes:', 'seris-order-manager'); ?></h4>
            <ul>
                <li><?php esc_html_e('Use !important sparingly and only when necessary to override other styles', 'seris-order-manager'); ?></li>
                <li><?php esc_html_e('The plugin includes responsive styles by default (mobile-friendly)', 'seris-order-manager'); ?></li>
                <li><?php esc_html_e('For advanced customizations, you may need to inspect elements with browser developer tools', 'seris-order-manager'); ?></li>
            </ul>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // ClipboardJS for copying CSS
            if (typeof ClipboardJS !== 'undefined') {
                new ClipboardJS('.serisvri-copy-css').on('success', function() {
                    $('.serisvri-copy-success').fadeIn().delay(2000).fadeOut();
                });
            }
        });
        </script>
        
        <style>
        .css-code-container {
            background: #f5f5f5;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 15px 0;
            position: relative;
        }
        .css-code-container pre {
            white-space: pre-wrap;
            margin: 0;
            padding: 0;
            background: transparent;
            border: none;
        }
        .css-copy-actions {
            margin-top: 10px;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Get CSS template with detailed comments
     */
    private function get_css_template_with_comments() {
        return '/* ==============================================
   SERIS Delivery Confirmation Styles
   Copy this CSS to your theme or Elementor custom CSS
   to customize the appearance.
   
   All elements have !important tags to ensure they
   override theme styles by default. Remove these if
   you want your theme styles to take precedence.
============================================== */

/* Main container for the delivery confirmation section */
#serisvri-delivery-confirmation-section {
    /* Layout & Box Model */
    margin: 2em 0 !important;
    padding: 1.5em !important;
    
    /* Visual Styles */
    background: #f8f9fa !important;
    border: 1px solid #e0e0e0 !important;
    border-radius: 4px !important;
    
    /* Typography */
    font-family: inherit !important;
}

/* Flex container for content and button */
#serisvri-delivery-notice-container {
    /* Flex Layout */
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    gap: 1.5em !important;
    
    /* Box Model */
    width: 100% !important;
}

/* Content wrapper (title + message) */
#serisvri-delivery-content-wrapper {
    /* Flex child properties */
    flex: 1 1 auto !important;
}

/* Delivery confirmation title */
#serisvri-delivery-confirmation-title {
    /* Typography */
    font-size: 1.25em !important;
    font-weight: 600 !important;
    color: #2c3338 !important;
    margin: 0 0 0.5em 0 !important;
}

/* Delivery confirmation message */
#serisvri-delivery-confirmation-message {
    /* Typography */
    font-size: 0.9375em !important;
    line-height: 1.5 !important;
    color: #50575e !important;
    margin: 0 !important;
}

/* Confirmation button */
#serisvri-delivery-confirmation-button {
    /* Layout & Box Model */
    display: inline-block !important;
    padding: 0.75em 1.5em !important;
    margin: 0 !important;
    
    /* Typography */
    font-size: 0.9375em !important;
    font-weight: 600 !important;
    text-decoration: none !important;
    text-align: center !important;
    white-space: nowrap !important;
    
    /* Visual Styles */
    background-color: #2271b1 !important;
    color: #fff !important;
    border: 1px solid #2271b1 !important;
    border-radius: 3px !important;
    cursor: pointer !important;
    
    /* Transitions */
    transition: all 0.2s ease !important;
}

/* Button hover state */
#serisvri-delivery-confirmation-button:hover {
    background-color: #135e96 !important;
    border-color: #135e96 !important;
}

/* Button active/focus state */
#serisvri-delivery-confirmation-button:active,
#serisvri-delivery-confirmation-button:focus {
    background-color: #0c4b7a !important;
    border-color: #0c4b7a !important;
    outline: none !important;
    box-shadow: 0 0 0 1px #fff, 0 0 0 3px #2271b1 !important;
}

/* Mobile responsive styles */
@media (max-width: 600px) {
    #serisvri-delivery-notice-container {
        /* Stack elements vertically on small screens */
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 1em !important;
    }
    
    #serisvri-delivery-confirmation-button {
        /* Make button full width */
        width: 100% !important;
    }
}';
    }

    /**
     * Show customization notice in admin
     */
    public function serisvri_customization_notice() {
        $screen = get_current_screen();
        if ($screen->id === 'woocommerce_page_wc-settings') {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<h3>' . esc_html__('Customize Delivery Confirmation Styles', 'seris-order-manager') . '</h3>';
            echo '<p>' . esc_html__('You can customize the appearance of the delivery confirmation section by copying the CSS from:', 'seris-order-manager') . '</p>';
            echo '<p><strong>' . esc_html__('WooCommerce → Settings → Seris → Customization', 'seris-order-manager') . '</strong></p>';
            echo '<p>' . esc_html__('Paste the CSS in your theme customizer, Elementor, or any custom CSS plugin.', 'seris-order-manager') . '</p>';
            echo '</div>';
        }
    }

    /**
     * Register shipped order status
     */
    public function register_shipped_order_status() {
        register_post_status('wc-shipped', array(
            'label'                     => _x('Shipped', 'Order status', 'seris-order-manager'),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            /* translators: %s: count of shipped orders */
            'label_count'               => _n_noop(
                'Shipped <span class="count">(%s)</span>', 
                'Shipped <span class="count">(%s)</span>', 
                'seris-order-manager'
            ),
            'protected'                 => false,
            'internal'                  => false,
            'publicly_queryable'        => true,
            'show_in_rest'              => true,
            'capability_type'           => 'shop_order'
        ));
    }

    /**
     * Add shipped status to WooCommerce order statuses
     */
    public function add_shipped_to_order_statuses($order_statuses) {
        $order_statuses['wc-shipped'] = _x('Shipped', 'Order status', 'seris-order-manager');
        return $order_statuses;
    }

    /**
     * Include shipped status in all order queries
     */
    public function include_shipped_in_all_queries($query, $query_vars) {
        if (!empty($query_vars['status'])) {
            if (is_array($query_vars['status'])) {
                if (!in_array('shipped', $query_vars['status']) && !in_array('wc-shipped', $query_vars['status'])) {
                    $query_vars['status'][] = 'wc-shipped';
                }
            } elseif ($query_vars['status'] === 'shipped' || $query_vars['status'] === 'wc-shipped') {
                $query['post_status'] = 'wc-shipped';
            }
        }
        return $query;
    }

    /**
     * Include shipped status in admin queries with nonce verification
     */
    public function include_shipped_in_admin_queries($query) {
        global $pagenow, $post_type;
        
        if ($pagenow === 'edit.php' && $post_type === 'shop_order' && !isset($_GET['post_status'])) {
            // Verify nonce for admin requests
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'serisvri_admin_query')) {
                return $query;
            }
            
            $query->query_vars['post_status'] = array_merge(
                (array)$query->query_vars['post_status'] ?? array(),
                array('wc-shipped')
            );
        }
        return $query;
    }

    /**
     * Include shipped status in My Account orders
     */
    public function include_shipped_in_my_account($query_args) {
        if (isset($query_args['status'])) {
            if (is_array($query_args['status'])) {
                if (!in_array('shipped', $query_args['status']) && !in_array('wc-shipped', $query_args['status'])) {
                    $query_args['status'][] = 'shipped';
                }
            } elseif (is_string($query_args['status'])) {
                if ($query_args['status'] !== 'shipped' && $query_args['status'] !== 'wc-shipped') {
                    $query_args['status'] = array($query_args['status'], 'shipped');
                }
            }
        }
        return $query_args;
    }

    /**
     * Include shipped status in admin counts
     */
    public function include_shipped_in_admin_counts($views) {
        global $wp_post_statuses;
        
        if (isset($wp_post_statuses['wc-shipped'])) {
            $wp_post_statuses['wc-shipped']->show_in_admin_all_list = true;
            $wp_post_statuses['wc-shipped']->show_in_admin_status_list = true;
        }
        
        return $views;
    }

    /**
     * Add order action buttons
     */
    public function add_order_buttons($order) {
        if (!is_a($order, 'WC_Order')) {
            return;
        }
        
        $order_id = $order->get_id();
        
        $receipt_url = wp_nonce_url(
            admin_url('admin.php?action=print_receipt&order_id=' . $order_id),
            'serisvri_print_receipt_' . $order_id
        );
        
        $packing_url = wp_nonce_url(
            admin_url('admin.php?action=print_packing_slip&order_id=' . $order_id),
            'serisvri_print_packing_slip_' . $order_id
        );
        ?>
        <div class="serisvri-order-actions">
            <a href="<?php echo esc_url($receipt_url); ?>" 
               class="button" 
               target="_blank" style="margin-top:20px;">
                <span class="dashicons dashicons-printer" style="margin-top:5px;"></span> 
                <?php esc_html_e('Print Receipt', 'seris-order-manager'); ?>
            </a>
            <a href="<?php echo esc_url($packing_url); ?>" 
               class="button" 
               target="_blank" style="margin-top:20px;" >
                <span class="dashicons dashicons-car" style="margin-top:5px;"></span> 
                <?php esc_html_e('Print Packing Slip', 'seris-order-manager'); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Add custom shipping option
     */
    public function add_custom_shipping_option($order) {
        $custom_shipping = get_post_meta($order->get_id(), '_custom_shipping', true);
        $shipping_company = get_post_meta($order->get_id(), '_shipping_company', true);
        ?>
        <div class="form-field form-field-wide">
            <label for="custom_shipping"><?php esc_html_e('Shipping Method', 'seris-order-manager'); ?></label>
            <select id="custom_shipping" name="custom_shipping" class="wc-enhanced-select">
                <option value="self_shipping" <?php selected($custom_shipping, 'self_shipping'); ?>>
                    <?php esc_html_e('Self Shipping', 'seris-order-manager'); ?>
                </option>
                <option value="third_party_shipping" <?php selected($custom_shipping, 'third_party_shipping'); ?>>
                    <?php esc_html_e('Third Party Shipping', 'seris-order-manager'); ?>
                </option>
            </select>
        </div>
        <div class="form-field form-field-wide" id="shipping_company_field" 
             style="<?php echo $custom_shipping === 'third_party_shipping' ? '' : 'display:none;'; ?>">
            <label for="shipping_company"><?php esc_html_e('Shipping Company', 'seris-order-manager'); ?></label>
            <input type="text" id="shipping_company" name="shipping_company" 
                   value="<?php echo esc_attr($shipping_company); ?>" class="widefat" />
            <?php wp_nonce_field('serisvri_shipping_action', 'serisvri_shipping_nonce'); ?>
        </div>
        <?php
    }

    /**
     * Save custom shipping option with security checks
     */
    public function save_custom_shipping_option($order_id) {
        // Verify nonce
        if (!isset($_POST['serisvri_shipping_nonce']) || 
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['serisvri_shipping_nonce'])), 'serisvri_shipping_action')) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_shop_order', $order_id)) {
            return;
        }

        // Save custom shipping
        if (isset($_POST['custom_shipping'])) {
            $allowed_values = ['self_shipping', 'third_party_shipping'];
            $custom_shipping = sanitize_text_field(wp_unslash($_POST['custom_shipping']));
            
            if (in_array($custom_shipping, $allowed_values, true)) {
                update_post_meta($order_id, '_custom_shipping', $custom_shipping);
            }
        }

        // Save shipping company
        if (isset($_POST['shipping_company'])) {
            $shipping_company = sanitize_text_field(wp_unslash($_POST['shipping_company']));
            update_post_meta($order_id, '_shipping_company', $shipping_company);
        }
    }

    /**
     * Add delivery confirmation button
     */
    public function add_confirm_delivery_button($actions, $order) {
        if (!is_user_logged_in() || get_current_user_id() !== $order->get_user_id()) {
            return $actions;
        }
        
        if ($order->get_status() === 'completed') {
            $actions['confirm_delivery'] = array(
                'url'  => wp_nonce_url(
                    add_query_arg('confirm_delivery', $order->get_id(), wc_get_account_endpoint_url('orders')), 
                    'woocommerce-confirm-delivery'
                ),
                'name' => esc_html__('Confirm Delivery', 'seris-order-manager'),
            );
        }
        
        return $actions;
    }

    /**
     * Add delivery confirmation button on order view
     */
public function add_confirm_delivery_button_to_order_view($order) {
    if (!is_user_logged_in() || get_current_user_id() !== $order->get_user_id()) {
        return;
    }
    
    if ($order->get_status() === 'completed') {
        $confirm_url = wp_nonce_url(
            add_query_arg(
                'confirm_delivery',
                $order->get_id(),
                wc_get_endpoint_url('view-order', $order->get_id(), wc_get_page_permalink('myaccount'))
            ),
            'woocommerce-confirm-delivery'
        );
        
        echo '<div id="serisvri-delivery-container">';
        echo '<h3 id="serisvri-delivery-title">' . esc_html__('Package Received?', 'seris-order-manager') . '</h3>';
        echo '<p id="serisvri-delivery-message">' . esc_html__('Please confirm when you have received your package.', 'seris-order-manager') . '</p>';
        echo '<a href="' . esc_url($confirm_url) . '" id="serisvri-delivery-button" class="button">';
        echo esc_html__('Confirm Delivery', 'seris-order-manager');
        echo '</a>';
        echo '</div>';
    }
}

    /**
     * Handle delivery confirmation
     */
    public function handle_delivery_confirmation() {
        if (!isset($_GET['confirm_delivery'], $_GET['_wpnonce'])) {
            return;
        }

        try {
            $order_id = absint($_GET['confirm_delivery']);
            $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));

            if (!wp_verify_nonce($nonce, 'woocommerce-confirm-delivery')) {
                throw new Exception(__('Security check failed.', 'seris-order-manager'));
            }

            $order = wc_get_order($order_id);
            
            if (!$order) {
                throw new Exception(__('Order not found.', 'seris-order-manager'));
            }

            if (get_current_user_id() !== $order->get_user_id()) {
                throw new Exception(__('Permission denied.', 'seris-order-manager'));
            }

            if ('completed' !== $order->get_status()) {
                throw new Exception(__('Order not ready for confirmation.', 'seris-order-manager'));
            }

            $order->update_status('shipped', __('Delivery confirmed by customer.', 'seris-order-manager'));
            $order->add_order_note(__('Customer confirmed delivery.', 'seris-order-manager'));
            
            wc_add_notice(
                esc_html__('Thank you for confirming your delivery!', 'seris-order-manager'), 
                'success'
            );
            
            wp_safe_redirect(wc_get_endpoint_url('view-order', $order_id, wc_get_page_permalink('myaccount')));
            exit;

        } catch (Exception $e) {
            wc_add_notice(esc_html($e->getMessage()), 'error');
            wp_safe_redirect(wc_get_page_permalink('myaccount'));
            exit;
        }
    }

    /**
     * Handle print receipt action
     */
    public function handle_print_receipt() {
        try {
            if (!isset($_GET['order_id'], $_GET['_wpnonce'])) {
                throw new Exception(__('Missing parameters.', 'seris-order-manager'));
            }

            $order_id = absint($_GET['order_id']);
            $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));

            if (!wp_verify_nonce($nonce, 'serisvri_print_receipt_' . $order_id)) {
                throw new Exception(__('Security check failed.', 'seris-order-manager'));
            }

            if (!current_user_can('edit_shop_orders')) {
                throw new Exception(__('Permission denied.', 'seris-order-manager'));
            }

            $pdf_generator = Serisvri_Core::get_instance()->get_pdf_generator();
            $pdf_generator->generate_receipt($order_id);
            exit;

        } catch (Exception $e) {
            wp_die(
                esc_html($e->getMessage()),
                esc_html__('Print Error', 'seris-order-manager'),
                ['response' => 403]
            );
        }
    }

    /**
     * Handle print packing slip action
     */
    public function handle_print_packing_slip() {
        try {
            if (!isset($_GET['order_id'], $_GET['_wpnonce'])) {
                throw new Exception(__('Missing parameters.', 'seris-order-manager'));
            }

            $order_id = absint($_GET['order_id']);
            $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));

            if (!wp_verify_nonce($nonce, 'serisvri_print_packing_slip_' . $order_id)) {
                throw new Exception(__('Security check failed.', 'seris-order-manager'));
            }

            if (!current_user_can('edit_shop_orders')) {
                throw new Exception(__('Permission denied.', 'seris-order-manager'));
            }

            $pdf_generator = Serisvri_Core::get_instance()->get_pdf_generator();
            $pdf_generator->generate_packing_slip($order_id);
            exit;

        } catch (Exception $e) {
            wp_die(
                esc_html($e->getMessage()),
                esc_html__('Print Error', 'seris-order-manager'),
                ['response' => 403]
            );
        }
    }

    /**
     * Handle bulk print action
     */
    public function handle_bulk_print() {
        try {
            // Verify nonce
            if (!isset($_POST['_wpnonce']) || 
                !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'serisvri_bulk_print')) {
                throw new Exception(__('Security check failed.', 'seris-order-manager'));
            }

            // Check permissions
            if (!current_user_can('edit_shop_orders')) {
                throw new Exception(__('Permission denied.', 'seris-order-manager'));
            }

            // Validate input
            if (!isset($_POST['order_ids']) || empty($_POST['order_ids'])) {
                throw new Exception(__('No orders selected.', 'seris-order-manager'));
            }

            // Sanitize order IDs
            $order_ids = array_map('absint', $_POST['order_ids']);
            
            // Sanitize and validate PDF type
            $pdf_type = isset($_POST['pdf_type']) ? sanitize_text_field(wp_unslash($_POST['pdf_type'])) : 'receipt';
            $allowed_types = ['receipt', 'packing_slip'];
            if (!in_array($pdf_type, $allowed_types, true)) {
                $pdf_type = 'receipt';
            }
            
            // Generate PDF
            $pdf_generator = Serisvri_Core::get_instance()->get_pdf_generator();
            $pdf_generator->generate_bulk_pdf($order_ids, $pdf_type);
            
        } catch (Exception $e) {
            wp_die(
                sprintf(
                    /* translators: %s: error message */
                    esc_html__('Error: %s', 'seris-order-manager'),
                    esc_html($e->getMessage())
                ),
                esc_html__('Print Error', 'seris-order-manager'),
                ['response' => 403]
            );
        }
    }

    /**
     * Handle mark as shipped action
     */
    public function handle_mark_as_shipped() {
        try {
            if (!isset($_GET['order_id'], $_GET['_wpnonce'])) {
                throw new Exception(__('Missing parameters.', 'seris-order-manager'));
            }

            $order_id = absint($_GET['order_id']);
            $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));

            if (!wp_verify_nonce($nonce, 'serisvri_mark_as_shipped_' . $order_id)) {
                throw new Exception(__('Security check failed.', 'seris-order-manager'));
            }

            if (!current_user_can('edit_shop_orders')) {
                throw new Exception(__('Permission denied.', 'seris-order-manager'));
            }

            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_status('shipped');
                wp_safe_redirect(admin_url('post.php?post=' . $order_id . '&action=edit'));
                exit;
            }

            throw new Exception(__('Order not found.', 'seris-order-manager'));

        } catch (Exception $e) {
            wp_die(
                esc_html($e->getMessage()),
                esc_html__('Error', 'seris-order-manager'),
                ['response' => 403]
            );
        }
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        
        global $post;
        if ($post && 'shop_order' === $post->post_type) {
            // Fix the CSS path - remove the extra slash
            wp_enqueue_style(
                'serisvri-admin-order-css',
                SERISVRI_SLIPS_URL . 'assets/css/admin-order.css', // Removed the extra slash
                array(),
                SERISVRI_SLIPS_VERSION
            );
            
            wp_enqueue_script(
                'serisvri-order-admin',
                SERISVRI_SLIPS_URL . 'assets/js/admin-order.js',
                array('jquery'),
                SERISVRI_SLIPS_VERSION,
                true
            );
        }
    }
    
    /**
     * Localize admin scripts
     */
    public function localize_admin_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        
        global $post;
        if ($post && 'shop_order' === $post->post_type) {
            wp_localize_script(
                'serisvri-order-admin', 
                'serisvri_vars', 
                [
                    'siteUrl' => admin_url('admin.php'),
                    'receiptUrl' => wp_nonce_url(
                        admin_url('admin.php?action=print_receipt&order_id=' . $post->ID),
                        'serisvri_print_receipt_' . $post->ID
                    ),
                    'packingSlipUrl' => wp_nonce_url(
                        admin_url('admin.php?action=print_packing_slip&order_id=' . $post->ID),
                        'serisvri_print_packing_slip_' . $post->ID
                    ),
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'ajax_nonce' => wp_create_nonce('serisvri_order_admin_nonce')
                ]
            );
        }
    }
}