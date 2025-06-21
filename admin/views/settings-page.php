<?php
/**
 * Admin Settings Page View
 * 
 * @package Seris_Order_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Company Information', 'seris-order-manager'); ?></h1>
    <form method="post" action="options.php">
        <?php settings_fields('serisvri-settings-group'); ?>
        <?php do_settings_sections('serisvri-settings-group'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Preferred paper size', 'seris-order-manager'); ?></th>
                <td>
                    <select name="serisvri_paper_size" id="serisvri_paper_size" class="regular-text">
                        <option value="a4" <?php selected(get_option('serisvri_paper_size'), 'a4'); ?>><?php esc_html_e('Large A4 (210mm × 297mm)', 'seris-order-manager'); ?></option>
                        <option value="2_25_inch" <?php selected(get_option('serisvri_paper_size'), '2_25_inch'); ?>><?php esc_html_e('2 1/4 inch (57mm × any)', 'seris-order-manager'); ?></option>
                        <option value="2_5_inch" <?php selected(get_option('serisvri_paper_size'), '2_5_inch'); ?>><?php esc_html_e('2 1/2 inch (77mm × any)', 'seris-order-manager'); ?></option>
                        <option value="custom" <?php selected(get_option('serisvri_paper_size'), 'custom'); ?>><?php esc_html_e('Custom Size', 'seris-order-manager'); ?></option>
                    </select>
                    
                    <div id="serisvri-custom-size-fields" style="<?php echo (get_option('serisvri_paper_size') !== 'custom') ? 'display:none;' : ''; ?> margin-top:10px;">
                        <div>
                            <label for="serisvri_custom_paper_width" style="display:inline-block;width:120px;">
                                <?php esc_html_e('Width (mm):', 'seris-order-manager'); ?>
                            </label>
                            <input type="number" id="serisvri_custom_paper_width" name="serisvri_custom_paper_width" 
                                   value="<?php echo esc_attr(get_option('serisvri_custom_paper_width', '210')); ?>" style="width:80px;">
                        </div>
                        <div style="margin-top:5px;">
                            <label for="serisvri_custom_paper_height" style="display:inline-block;width:120px;">
                                <?php esc_html_e('Height (mm):', 'seris-order-manager'); ?>
                            </label>
                            <input type="number" id="serisvri_custom_paper_height" name="serisvri_custom_paper_height" 
                                   value="<?php echo esc_attr(get_option('serisvri_custom_paper_height', '297')); ?>" style="width:80px;">
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>

    <h2><?php esc_html_e('Preview', 'seris-order-manager'); ?></h2>
    <div id="serisvri-preview-container">
        <div id="serisvri-preview" class="paper-<?php echo esc_attr(get_option('serisvri_paper_size', 'a4')); ?>">
            <iframe id="serisvri-preview-iframe" src="<?php echo esc_url(admin_url('admin-ajax.php?action=serisvri_preview_iframe&paper_size=' . get_option('serisvri_paper_size', 'a4'))); ?>"></iframe>
        </div>
    </div>
</div>