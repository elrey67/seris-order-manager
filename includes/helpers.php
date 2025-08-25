<?php
/**
 * Helper Functions
 * 
 * @package Seris_Order_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Get allowed HTML for output
 */
function serisvri_allowed_html() {
    $allowed = wp_kses_allowed_html('post');
    
    // Add specific allowed elements
    $allowed['style'] = array(
        'type' => true,
    );
    
    // Add table styling attributes
    $allowed['table'] = array_merge($allowed['table'], array(
        'style' => true,
        'class' => true,
        'id' => true
    ));
    
    $allowed['tr'] = array_merge($allowed['tr'], array(
        'style' => true,
        'class' => true
    ));
    
    $allowed['td'] = array_merge($allowed['td'], array(
        'style' => true,
        'class' => true,
        'colspan' => true,
        'rowspan' => true
    ));
    
    $allowed['th'] = array_merge($allowed['th'], array(
        'style' => true,
        'class' => true
    ));
    
    // Add font-face support
    $allowed['@font-face'] = array(
        'font-family' => true,
        'src' => true,
        'font-weight' => true,
        'font-style' => true
    );

    return $allowed;
}

/**
 * Allow additional CSS properties
 */
add_filter('safe_style_css', function($styles) {
    $new_styles = array(
        'border-collapse',
        'border-spacing',
        'font-family',
        'src',
        'font-weight',
        'font-style',
        'text-align',
        'vertical-align'
    );
    
    return array_merge($styles, $new_styles);
});

/**
 * Add data protocol allowance
 */
add_filter('kses_allowed_protocols', function($protocols) {
    $protocols[] = 'data';
    return $protocols;
});

/**
 * Format currency amount with proper HTML
 */
function serisvri_format_currency_amount($amount, $symbol, $position) {
    $formatted = number_format((float)$amount, 2);
    
    $patterns = [
        'left' => '%1$s%2$s',
        'right' => '%2$s%1$s',
        'left_space' => '%1$s %2$s',
        'right_space' => '%2$s %1$s'
    ];
    
    $pattern = $patterns[$position] ?? '%1$s%2$s';
    $html = sprintf($pattern, $symbol, $formatted);
    
    return wp_kses($html, [
        'span' => [
            'class' => true,
            'style' => true
        ]
    ]);
}

/**
 * Get formatted order date
 */
function serisvri_get_formatted_order_date($order, $format = 'F j, Y') {
    if (!is_a($order, 'WC_Order')) {
        return '';
    }
    
    $date = $order->get_date_created();
    return $date ? $date->date($format) : '';
}

/**
 * Get order items formatted for display
 */
function serisvri_get_order_items_formatted($order) {
    if (!is_a($order, 'WC_Order')) {
        return array();
    }
    
    $items = array();
    foreach ($order->get_items() as $item) {
        $items[] = array(
            'name' => $item->get_name(),
            'quantity' => $item->get_quantity(),
            'total' => $item->get_total(),
            'product' => $item->get_product()
        );
    }
    
    return $items;
}