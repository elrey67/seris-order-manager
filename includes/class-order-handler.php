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
     * Add delivery confirmation button to order view
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
            
            echo '<div class="confirm-delivery-section">';
            echo '<h3>' . esc_html__('Package Received?', 'seris-order-manager') . '</h3>';
            echo '<p>' . esc_html__('Please confirm when you have received your package.', 'seris-order-manager') . '</p>';
            echo '<a href="' . esc_url($confirm_url) . '" class="button">';
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
            wp_enqueue_style(
                'serisvri-admin-order-css',
                SERISVRI_SLIPS_URL . 'css/admin-order.css',
                array(),
                SERISVRI_SLIPS_VERSION
            );
            
            wp_enqueue_script(
                'serisvri-order-admin',
                SERISVRI_SLIPS_URL . 'js/admin-order.js',
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