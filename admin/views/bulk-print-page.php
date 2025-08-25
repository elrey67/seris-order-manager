<?php
/**
 * Bulk Print Orders Page
 *
 * This file renders the bulk print orders page in the admin area.
 *
 * @package Seris_Order_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>

<div class="wrap">
    <h1><?php esc_html_e('Bulk Print Orders', 'seris-order-manager'); ?></h1>
    
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('serisvri_bulk_pdf_action', 'serisvri_bulk_pdf_nonce'); ?>
        <input type="hidden" name="action" value="serisvri_generate_bulk_pdf">
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="check-column"><input type="checkbox" id="serisvri-select-all-checkbox"></th>
                    <th><?php esc_html_e('Order', 'seris-order-manager'); ?></th>
                    <th><?php esc_html_e('Customer', 'seris-order-manager'); ?></th>
                    <th><?php esc_html_e('Date', 'seris-order-manager'); ?></th>
                    <th><?php esc_html_e('Total', 'seris-order-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order) : ?>
                <tr>
                    <th scope="row" class="check-column">
                        <input type="checkbox" name="order_ids[]" value="<?php echo esc_attr($order->get_id()); ?>">
                    </th>
                    <td>
                        <a href="<?php echo esc_url(get_edit_post_link($order->get_id())); ?>">
                            #<?php echo esc_html($order->get_id()); ?>
                        </a>
                    </td>
                    <td><?php echo esc_html($order->get_formatted_billing_full_name()); ?></td>
                    <td><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></td>
                    <td><?php echo wp_kses_post($order->get_formatted_order_total()); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="tablenav bottom">
            <div class="alignleft actions">
                <button type="submit" name="pdf_type" value="receipt" class="button-primary">
                    <?php esc_html_e('Generate Receipts', 'seris-order-manager'); ?>
                </button>
                <button type="submit" name="pdf_type" value="packing_slip" class="button-primary" style="margin-left: 10px;">
                    <?php esc_html_e('Generate Packing Slips', 'seris-order-manager'); ?>
                </button>
            </div>
        </div>
    </form>
</div>