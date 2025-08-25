<?php
/**
 * Settings Handler Class
 * 
 * @package Seris_Order_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Serisvri_Settings {
    // Paper size constants
    const PAPER_SIZE_A4 = 'a4';
    const PAPER_SIZE_SMALL = '2_25_inch';
    const PAPER_SIZE_MEDIUM = '2_5_inch';
    const PAPER_SIZE_CUSTOM = 'custom';
    
    /**
     * Initialize settings
     */
    public function init() {
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Register plugin settings with proper sanitization
     */
    public function register_settings() {
        // Register settings with direct sanitization callbacks
        register_setting('serisvri-settings-group', 'serisvri_company_name', 'sanitize_text_field');
        register_setting('serisvri-settings-group', 'serisvri_company_logo', 'esc_url_raw');
        register_setting('serisvri-settings-group', 'serisvri_company_address', 'sanitize_textarea_field');
        register_setting('serisvri-settings-group', 'serisvri_paper_size', 'Serisvri_Settings::sanitize_paper_size');
        register_setting('serisvri-settings-group', 'serisvri_custom_paper_width', 'Serisvri_Settings::sanitize_paper_dimension');
        register_setting('serisvri-settings-group', 'serisvri_custom_paper_height', 'Serisvri_Settings::sanitize_paper_dimension');
        register_setting('serisvri-settings-group', 'serisvri_custom_message', 'sanitize_textarea_field');

        // Set default values if they don't exist
        $this->set_default_values();
    }

    /**
     * Set default values for all settings
     */
    protected function set_default_values() {
        $defaults = array(
            'serisvri_company_name' => '',
            'serisvri_company_logo' => '',
            'serisvri_company_address' => '',
            'serisvri_paper_size' => self::PAPER_SIZE_A4,
            'serisvri_custom_paper_width' => 210,
            'serisvri_custom_paper_height' => 297,
            'serisvri_custom_message' => ''
        );

        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                update_option($option, $value);
            }
        }
    }

    /**
     * Sanitize paper size selection
     * @param string $input
     * @return string
     */
    public static function sanitize_paper_size($input) {
        $available_sizes = array(
            self::PAPER_SIZE_A4,
            self::PAPER_SIZE_SMALL,
            self::PAPER_SIZE_MEDIUM,
            self::PAPER_SIZE_CUSTOM
        );
        return in_array($input, $available_sizes, true) ? $input : self::PAPER_SIZE_A4;
    }

    /**
     * Sanitize paper dimension (width/height)
     * @param mixed $input
     * @return int
     */
    public static function sanitize_paper_dimension($input) {
        $input = absint($input);
        return max(10, min($input, 1000)); // Limit between 10mm and 1000mm
    }

    /**
     * Get company name
     */
    public function get_company_name() {
        return get_option('serisvri_company_name', '');
    }

    /**
     * Get company logo URL
     */
    public function get_company_logo() {
        return get_option('serisvri_company_logo', '');
    }

    /**
     * Get company address
     */
    public function get_company_address() {
        return get_option('serisvri_company_address', '');
    }

    /**
     * Get paper size
     */
    public function get_paper_size() {
        return get_option('serisvri_paper_size', self::PAPER_SIZE_A4);
    }

    /**
     * Get custom paper width
     */
    public function get_custom_paper_width() {
        return get_option('serisvri_custom_paper_width', 210);
    }

    /**
     * Get custom paper height
     */
    public function get_custom_paper_height() {
        return get_option('serisvri_custom_paper_height', 297);
    }

    /**
     * Get custom message
     */
    public function get_custom_message() {
        return get_option('serisvri_custom_message', '');
    }
    
    /**
     * Get available paper sizes with labels
     */
    public function get_available_paper_sizes() {
        return [
            self::PAPER_SIZE_A4 => __('A4 (210 × 297 mm)', 'seris-order-manager'),
            self::PAPER_SIZE_SMALL => __('2 1/4 inch (57 mm width)', 'seris-order-manager'),
            self::PAPER_SIZE_MEDIUM => __('2 1/2 inch (77 mm width)', 'seris-order-manager'),
            self::PAPER_SIZE_CUSTOM => __('Custom Paper Size', 'seris-order-manager')
        ];
    }
    
    /**
     * Get paper dimensions and styling for a specific size
     */
    public function get_paper_dimensions($size = null) {
        $size = $size ?: $this->get_paper_size();
        
        $dimensions = [
            self::PAPER_SIZE_A4 => [
                'width' => '210mm',
                'height' => '297mm',
                'margin' => '10mm',
                'font_size' => '12px',
                'orientation' => 'portrait'
            ],
            self::PAPER_SIZE_SMALL => [
                'width' => '57mm',
                'height' => 'auto',
                'margin' => '2mm',
                'font_size' => '10px',
                'orientation' => 'portrait'
            ],
            self::PAPER_SIZE_MEDIUM => [
                'width' => '77mm',
                'height' => 'auto',
                'margin' => '2mm',
                'font_size' => '10px',
                'orientation' => 'portrait'
            ],
            self::PAPER_SIZE_CUSTOM => [
                'width' => max(10, $this->get_custom_paper_width()) . 'mm',
                'height' => max(10, $this->get_custom_paper_height()) . 'mm',
                'margin' => '5mm',
                'font_size' => '11px',
                'orientation' => 'portrait'
            ]
        ];
        
        return $dimensions[$size] ?? $dimensions[self::PAPER_SIZE_A4];
    }
    
    /**
     * Get paper size for dompdf configuration
     */
    public function get_paper_size_for_dompdf() {
        $size = $this->get_paper_size();
        $dimensions = $this->get_paper_dimensions($size);
        
        if ($size === self::PAPER_SIZE_CUSTOM) {
            return [
                $this->get_custom_paper_width() . 'mm',
                $this->get_custom_paper_height() . 'mm'
            ];
        }
        
        return [$dimensions['width'], $dimensions['height']];
    }
    
    /**
     * Get paper orientation for dompdf configuration
     */
    public function get_paper_orientation() {
        $dimensions = $this->get_paper_dimensions();
        return $dimensions['orientation'];
    }
}