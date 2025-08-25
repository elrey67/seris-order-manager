<?php
/**
 * Admin Settings Class
 * 
 * @package Seris_Order_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Serisvri_Admin {
    private $settings;

    public function __construct() {
        $this->settings = new Serisvri_Settings();
        $this->init();
    }

    /**
     * Initialize admin functionality
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_serisvri_get_fresh_nonce', array($this, 'ajax_get_fresh_nonce'));
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_menu_page(
            esc_html__('Seris Order Manager Settings', 'seris-order-manager'),
            esc_html__('Seris Manager', 'seris-order-manager'),
            'manage_options',
            'serisvri-settings',
            array($this, 'render_settings_page'),
            'data:image/svg+xml;base64,' . base64_encode('
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_9_1224)">
<path d="M11.3298 5.40596C11.356 5.11738 11.4452 4.77952 11.5975 4.39239C11.7491 3.97327 11.9494 3.58517 12.1983 3.22811C12.4785 2.83841 12.8077 2.49574 13.1859 2.20009C13.5634 1.87246 13.9911 1.65583 14.4691 1.55023C15.489 1.33773 16.3381 1.3847 17.0164 1.69116C17.6947 1.99762 18.214 2.35528 18.5742 2.76413C18.7061 2.95353 18.7563 3.06454 18.725 3.09718C18.6623 3.16245 18.55 3.1487 18.3881 3.05593C18.1935 2.9318 17.8714 2.82624 17.4215 2.73925C17.0037 2.65161 16.5394 2.64492 16.0288 2.71918C15.5496 2.7608 15.0726 2.9144 14.5978 3.17997C14.155 3.44491 13.7954 3.8682 13.519 4.44986C13.274 4.99889 13.1093 5.56231 13.0248 6.14012C12.9404 6.71792 12.8873 7.2631 12.8656 7.77564C12.8752 8.25554 12.8996 8.67114 12.9386 9.02242C12.9777 9.37371 12.9985 9.61334 13.0011 9.74132C13.0721 10.092 13.1291 10.5389 13.172 11.0822C13.2462 11.5928 13.2888 12.12 13.2997 12.6639C13.3107 13.2078 13.3056 13.752 13.2845 14.2965C13.2628 14.8091 13.2078 15.2583 13.1195 15.6441C13.0894 15.7408 13.0446 15.9017 12.9851 16.1269C12.9576 16.3515 12.9119 16.4645 12.8479 16.4657C12.6879 16.4689 12.6057 16.3586 12.6012 16.1346C12.5967 15.9107 12.5935 15.7507 12.5916 15.6547C12.5116 14.8562 12.3835 14.0586 12.2075 13.2619C12.0309 12.4333 11.8539 11.5887 11.6766 10.7281C11.5313 9.86682 11.418 9.00492 11.3367 8.14238C11.2547 7.24784 11.2524 6.3357 11.3298 5.40596ZM9.84073 14.9417C9.85989 14.3012 9.77793 13.4067 9.59486 12.2581C9.44314 11.0769 9.36787 9.71818 9.36906 8.18185C9.385 7.38137 9.44862 6.56393 9.55991 5.72953C9.67056 4.86313 9.95749 4.00921 10.4207 3.16775C10.9146 2.26166 11.5413 1.59295 12.3008 1.16163C13.0917 0.697679 13.7754 0.475922 14.3519 0.496363C14.4479 0.494437 14.5439 0.492512 14.6399 0.490586C14.7358 0.488661 14.7841 0.503695 14.7848 0.535689C14.7861 0.599677 14.7707 0.631991 14.7387 0.632633C14.7061 0.601281 14.5944 0.619524 14.4038 0.687362C13.8311 0.858882 13.3246 1.1411 12.8844 1.534C12.4436 1.89492 12.068 2.31854 11.7577 2.80486C11.4474 3.29118 11.2014 3.79221 11.0197 4.30796C10.838 4.82371 10.7353 5.28986 10.7117 5.70642C10.555 7.46992 10.6018 9.00529 10.8521 10.3125C11.0332 11.3651 11.1834 12.4663 11.3024 13.6162C11.4209 14.734 11.3938 15.7748 11.221 16.7384C11.0797 17.6694 10.7595 18.46 10.2604 19.1102C9.79271 19.7277 9.0476 20.0787 8.02509 20.1632C7.92911 20.1651 7.80114 20.1677 7.64117 20.1709C7.51255 20.1415 7.4476 20.0948 7.44632 20.0308C7.44504 19.9668 7.58676 19.8519 7.87149 19.6862C8.69562 19.2856 9.22828 18.7148 9.46947 17.9738C9.71066 17.2328 9.83441 16.2221 9.84073 14.9417ZM4.86736 20.7067C5.32939 21.4015 6.04383 21.9153 7.0107 22.248C8.00892 22.548 9.17604 22.4926 10.5121 22.0817C11.1801 21.8763 11.7652 21.5285 12.2675 21.0383C12.7698 20.5481 13.1751 20.0119 13.4835 19.4296C13.8232 18.8147 14.0666 18.1856 14.2138 17.5426C14.3929 16.8988 14.493 16.3047 14.5141 15.7602C14.5281 14.8637 14.4785 13.9845 14.3652 13.1226C14.2838 12.2601 14.1712 11.4302 14.0272 10.6329C13.9145 9.80301 13.8025 9.00509 13.6911 8.23917C13.6117 7.47261 13.5966 6.72076 13.6458 5.98363C13.6938 5.18251 13.9055 4.57014 14.2811 4.14652C14.6567 3.7229 15.1155 3.45765 15.6574 3.35075C16.2307 3.21123 16.8233 3.23135 17.435 3.41111C18.0467 3.59088 18.6127 3.88359 19.1329 4.28923C19.2622 4.35065 19.3118 4.42968 19.2817 4.5263C19.283 4.59029 19.2513 4.60692 19.1867 4.57621C19.1221 4.5455 19.0575 4.51479 18.9928 4.48408C17.8014 4.12391 16.8713 4.03054 16.2026 4.20399C15.566 4.37679 15.2256 4.95974 15.1815 5.95282C15.1406 7.10587 15.2479 8.464 15.5033 10.0272C15.7901 11.5577 15.9204 13.2675 15.8943 15.1564C15.9391 15.7956 15.8739 16.5331 15.6986 17.3687C15.5553 18.2038 15.2837 19.0254 14.8839 19.8336C14.5153 20.6091 14.0012 21.3076 13.3416 21.9289C12.7139 22.5497 11.9538 22.949 11.0611 23.1269C9.37062 23.4169 8.00897 23.3482 6.97619 22.9208C5.9754 22.4928 5.24175 21.8194 4.77523 20.9005C4.70739 20.7099 4.67315 20.5985 4.67251 20.5665L4.7205 20.5656C4.7205 20.5656 4.76945 20.6126 4.86736 20.7067ZM6.97893 20.6643C7.59002 20.8121 8.32715 20.8613 9.19034 20.812C9.47764 20.7742 9.82796 20.6872 10.2413 20.5508C10.6547 20.4145 10.878 20.378 10.9113 20.4414C11.0098 20.5674 10.917 20.7293 10.6329 20.9271C10.3488 21.1248 9.98445 21.3081 9.53975 21.4771C9.09441 21.6141 8.63242 21.7193 8.1538 21.793C7.67454 21.8346 7.27366 21.7946 6.95116 21.6731C6.33815 21.4293 5.90078 21.166 5.63905 20.8832C5.34469 20.5691 5.08104 20.1903 4.8481 19.7468L4.74827 19.5568C4.68172 19.4301 4.66411 19.3505 4.69547 19.3178C4.72682 19.2852 4.77481 19.2842 4.83944 19.3149C4.87207 19.3463 4.88871 19.378 4.88935 19.41C5.28034 19.7542 5.60573 20.0197 5.86553 20.2065C6.15668 20.3607 6.52781 20.5133 6.97893 20.6643Z" fill="black"/>
</g>
<defs>
<clipPath id="clip0_9_1224">
<rect width="24" height="24" fill="currentColor"/>
</clipPath>
</defs>
</svg>
'),
            56
        );
    }

    /**
     * Register settings and sections
     */
    public function register_settings() {
        $this->settings->init();

        // Add settings section
        add_settings_section(
            'serisvri_general_section',
            esc_html__('General Settings', 'seris-order-manager'),
            array($this, 'render_section_header'),
            'serisvri-settings'
        );

        // Register settings with static sanitization callbacks
        register_setting('serisvri-settings-group', 'serisvri_company_name', 'sanitize_text_field');
        register_setting('serisvri-settings-group', 'serisvri_company_logo', 'esc_url_raw');
        register_setting('serisvri-settings-group', 'serisvri_company_address', 'Serisvri_Admin::sanitize_textarea');
        register_setting('serisvri-settings-group', 'serisvri_paper_size', 'Serisvri_Admin::sanitize_paper_size');
        register_setting('serisvri-settings-group', 'serisvri_custom_paper_width', 'Serisvri_Admin::sanitize_paper_dimension');
        register_setting('serisvri-settings-group', 'serisvri_custom_paper_height', 'Serisvri_Admin::sanitize_paper_dimension');
        register_setting('serisvri-settings-group', 'serisvri_custom_message', 'Serisvri_Admin::sanitize_textarea');

        // Add settings fields
        $this->add_settings_field('serisvri_company_name', __('Company Name', 'seris-order-manager'), 'render_company_name_field');
        $this->add_settings_field('serisvri_company_logo', __('Company Logo', 'seris-order-manager'), 'render_company_logo_field');
        $this->add_settings_field('serisvri_company_address', __('Company Address', 'seris-order-manager'), 'render_company_address_field');
        $this->add_settings_field('serisvri_paper_size', __('Receipt Paper Size', 'seris-order-manager'), 'render_paper_size_field');
        $this->add_settings_field('serisvri_custom_message', __('Custom Thank You Message', 'seris-order-manager'), 'render_custom_message_field');
    }

    /**
     * Helper method to add settings fields
     */
    private function add_settings_field($id, $title, $callback) {
        add_settings_field(
            $id,
            esc_html($title),
            array($this, $callback),
            'serisvri-settings',
            'serisvri_general_section'
        );
    }

    /**
     * Custom sanitization for textarea fields
     */
    public static function sanitize_textarea($input) {
        return wp_kses_post(wp_unslash($input));
    }

    /**
     * Sanitize paper size option
     */
    public static function sanitize_paper_size($input) {
        $valid_sizes = array('a4', '2_25_inch', '2_5_inch', 'custom');
        return in_array($input, $valid_sizes, true) ? $input : 'a4';
    }

    /**
     * Sanitize paper dimensions
     */
    public static function sanitize_paper_dimension($input) {
        $value = absint($input);
        return ($value >= 10 && $value <= 1000) ? $value : 210; // Default to 210mm if invalid
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ('toplevel_page_serisvri-settings' !== $hook) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style('serisvri-admin', SERISVRI_SLIPS_URL . 'assets/css/admin.css', array(), SERISVRI_SLIPS_VERSION);
        
        // Correct the path to admin.js
        wp_enqueue_script(
            'serisvri-admin', 
            SERISVRI_SLIPS_URL . '/assets/js/admin.js', 
            array('jquery'), 
            SERISVRI_SLIPS_VERSION, 
            true
        );
        
        wp_localize_script('serisvri-admin', 'serisvri_vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'preview_nonce' => wp_create_nonce('serisvri_preview_iframe'),
            'ajax_nonce' => wp_create_nonce('serisvri_ajax_nonce'),
            'title' => __('Select Logo', 'seris-order-manager'),
            'button' => __('Use this logo', 'seris-order-manager'),
            'paper_size' => $this->settings->get_paper_size()
        ));
    }
    

    /**
     * AJAX handler to get fresh nonce
     */
    public function ajax_get_fresh_nonce() {
        check_ajax_referer('serisvri_ajax_nonce', 'security');
        
        wp_send_json_success(array(
            'nonce' => wp_create_nonce('serisvri_preview_iframe')
        ));
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Generate iframe URL with current settings
        $iframe_url = $this->get_preview_url();

        // Get the order preview image attachment ID if available
        $order_preview_image_id = attachment_url_to_postid(SERISVRI_SLIPS_URL . '/assets/images/order.png');
        
        settings_errors('serisvri_messages'); ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post" id="serisvri-settings-form">
                <?php
                settings_fields('serisvri-settings-group');
                do_settings_sections('serisvri-settings');
                submit_button(esc_html__('Save Settings', 'seris-order-manager'));
                ?>
            </form>

            <div class="serisvri-preview-section">
                <h2><?php esc_html_e('Preview', 'seris-order-manager'); ?></h2>
                <div id="serisvri-preview-container" class="paper-<?php echo esc_attr($this->settings->get_paper_size()); ?>">
                    <iframe id="serisvri-preview-iframe" 
                        src="<?php echo esc_url($iframe_url); ?>"
                        style="width:100%; height:500px; border:1px solid #ddd;"></iframe>
                </div>
            </div>

            <div class="serisvri-css-info-section" style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px;">
                <h2><?php esc_html_e('Custom CSS for Delivery Confirmation', 'seris-order-manager'); ?></h2>
                
                <div style="background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 4px; padding: 20px;">
                    <p><?php esc_html_e('Use the CSS below to customize the appearance of the delivery confirmation section on the order view page.', 'seris-order-manager'); ?></p>
                    <p><?php esc_html_e('Copy and paste this CSS into:', 'seris-order-manager'); ?></p>
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <li><?php esc_html_e('Elementor Custom CSS', 'seris-order-manager'); ?></li>
                        <li><?php esc_html_e('Theme Customizer (Appearance > Customize > Additional CSS)', 'seris-order-manager'); ?></li>
                        <li><?php esc_html_e('Any custom CSS plugin', 'seris-order-manager'); ?></li>
                    </ul>
                    
                    <div style="margin: 20px 0;">
                        <pre style="background: #fff; padding: 15px; border-radius: 4px; border: 1px solid #ddd; overflow-x: auto;"><code>/* ==============================================
   SERIS Delivery Confirmation - Customizable CSS
   Add these styles to:
   - Theme Customizer (Appearance > Customize > Additional CSS)
   - Elementor Custom CSS
   - Any custom CSS plugin
============================================== */

/* Main container */
#serisvri-delivery-container {
    /* Layout */
    margin: 20px 0;
    padding: 15px;
    
    /* Borders & Background */
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    
    /* Text Alignment */
    text-align: center;
}

/* Title element */
#serisvri-delivery-title {
    /* Typography */
    font-size: 1.25em;
    font-weight: 600;
    color: #2c3338;
    
    /* Spacing */
    margin: 0 0 10px 0;
}

/* Message text */
#serisvri-delivery-message {
    /* Typography */
    font-size: 0.9375em;
    line-height: 1.5;
    color: #50575e;
    
    /* Spacing */
    margin: 0 0 15px 0;
}

/* Confirmation button */
#serisvri-delivery-button {
    /* Layout */
    display: inline-block;
    padding: 10px 20px;
    
    /* Typography */
    font-size: 0.9375em;
    font-weight: 600;
    text-decoration: none;
    
    /* Colors */
    background-color: #2271b1;
    color: #fff;
    border: 1px solid #2271b1;
    border-radius: 3px;
    
    /* Effects */
    transition: all 0.2s ease;
}

/* Button hover state */
#serisvri-delivery-button:hover {
    background-color: #135e96;
    border-color: #135e96;
    color: #fff;
}

/* Button active state */
#serisvri-delivery-button:active {
    background-color: #0c4b7a;
    border-color: #0c4b7a;
}

/* Responsive adjustments */
@media (max-width: 600px) {
    #serisvri-delivery-container {
        padding: 12px;
    }
    
    #serisvri-delivery-button {
        display: block;
        width: 100%;
    }
}</code></pre>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <h3><?php esc_html_e('Delivery Confirmation Section Preview', 'seris-order-manager'); ?></h3>
                        <?php if ($order_preview_image_id) : ?>
                            <?php echo wp_get_attachment_image($order_preview_image_id, 'full', false, array(
                                'style' => 'max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 4px;',
                                'alt' => esc_attr__('Delivery Confirmation Section Preview', 'seris-order-manager')
                            )); ?>
                        <?php else : ?>
                            <img src="<?php echo esc_url(SERISVRI_SLIPS_URL . '/assets/images/order.png'); ?>" alt="<?php esc_attr_e('Delivery Confirmation Section Preview', 'seris-order-manager'); ?>" style="max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 4px;">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Generate preview URL with current settings
     */
    private function get_preview_url() {
        return add_query_arg([
            'action' => 'serisvri_render_preview',
            'paper_size' => $this->settings->get_paper_size(),
            'custom_width' => $this->settings->get_custom_paper_width(),
            'custom_height' => $this->settings->get_custom_paper_height(),
            'company_name' => urlencode($this->settings->get_company_name()),
            'company_address' => urlencode($this->settings->get_company_address()),
            'company_logo' => urlencode($this->settings->get_company_logo()),
            'custom_message' => urlencode($this->settings->get_custom_message()),
            '_wpnonce' => wp_create_nonce('serisvri_preview_action')
        ], admin_url('admin-ajax.php'));
    }

    /**
     * Section header callback
     */
    public function render_section_header() {
        echo '<p>'.esc_html__('Configure your order management settings below.', 'seris-order-manager').'</p>';
    }

    /**
     * Company name field callback
     */
    public function render_company_name_field() {
        $value = $this->settings->get_company_name();
        echo '<input type="text" name="serisvri_company_name" value="'.esc_attr($value).'" class="regular-text">';
    }

    /**
     * Company logo field callback
     */
    public function render_company_logo_field() {
        $logo_url = $this->settings->get_company_logo();
        $logo_id = $logo_url ? attachment_url_to_postid($logo_url) : 0;
        
        // Add notice about PNG format for colored logos
        echo '<div class="notice notice-info inline" style="margin-bottom: 15px; padding: 8px 12px;">';
        echo '<p style="margin: 0;"><strong>'.esc_html__('Tip:', 'seris-order-manager').'</strong> ';
        echo esc_html__('For colored logos, please use PNG format to maintain colors. Other formats may appear in black and white on receipts.', 'seris-order-manager');
        echo '</p>';
        echo '</div>';
        
        echo '<div class="serisvri-upload-wrapper">';
        echo '<input type="text" id="serisvri_company_logo" name="serisvri_company_logo" value="'.esc_url($logo_url).'" class="regular-text">';
        echo '<button type="button" class="button" id="serisvri-upload-logo">'.esc_html__('Upload Logo', 'seris-order-manager').'</button>';
        
        if ($logo_url) {
            echo '<div id="serisvri-logo-preview" style="margin-top:10px;">';
            if ($logo_id) {
                echo wp_get_attachment_image($logo_id, 'medium', false, array(
                    'style' => 'max-height:100px;',
                    'alt' => esc_attr__('Company Logo Preview', 'seris-order-manager')
                ));
            } else {
                echo '<img src="'.esc_url($logo_url).'" style="max-height:100px;" alt="'.esc_attr__('Company Logo Preview', 'seris-order-manager').'">';
            }
            echo '</div>';
        }
        
        echo '</div>';
    }

    /**
     * Company address field callback
     */
    public function render_company_address_field() {
        $value = $this->settings->get_company_address();
        echo '<textarea name="serisvri_company_address" class="large-text" rows="5">'.esc_textarea($value).'</textarea>';
    }

    /**
     * Paper size field callback
     */
    public function render_paper_size_field() {
        $current_size = $this->settings->get_paper_size();
        $paper_sizes = $this->settings->get_available_paper_sizes();
        
        echo '<select name="serisvri_paper_size" id="serisvri_paper_size" class="regular-text">';
        foreach ($paper_sizes as $value => $label) {
            echo '<option value="'.esc_attr($value).'" '.selected($current_size, $value, false).'>';
            echo esc_html($label);
            echo '</option>';
        }
        echo '</select>';
        
        // Custom paper size fields
        echo '<div id="serisvri-custom-paper-size-fields" style="margin-top:10px;'.($current_size !== 'custom' ? 'display:none;' : '').'">';
        echo '<div style="margin-bottom:5px;">';
        echo '<label for="serisvri_custom_paper_width" style="display:inline-block;width:80px;">'.esc_html__('Width (mm):', 'seris-order-manager').'</label>';
        echo '<input type="number" id="serisvri_custom_paper_width" name="serisvri_custom_paper_width" value="'.esc_attr($this->settings->get_custom_paper_width()).'" style="width:80px;" min="10" max="1000">';
        echo '</div>';
        echo '<div>';
        echo '<label for="serisvri_custom_paper_height" style="display:inline-block;width:80px;">'.esc_html__('Height (mm):', 'seris-order-manager').'</label>';
        echo '<input type="number" id="serisvri_custom_paper_height" name="serisvri_custom_paper_height" value="'.esc_attr($this->settings->get_custom_paper_height()).'" style="width:80px;" min="10" max="1000">';
        echo '</div>';
        echo '<p class="description">'.esc_html__('Enter custom paper dimensions in millimeters.', 'seris-order-manager').'</p>';
        echo '</div>';
        
        echo '<p class="description">'.esc_html__('Select the paper size that will be used for printing receipts.', 'seris-order-manager').'</p>';
    }

    /**
     * Custom message field callback
     */
    public function render_custom_message_field() {
        $value = $this->settings->get_custom_message();
        echo '<textarea name="serisvri_custom_message" class="large-text" rows="5">'.esc_textarea($value).'</textarea>';
        echo '<p class="description">'.esc_html__('This message will appear at the bottom of receipts.', 'seris-order-manager').'</p>';
    }
}