<?php
/**
 * Barcode Generator Class
 * 
 * @package Seris_Order_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use Picqer\Barcode\BarcodeGeneratorPNG;

class Serisvri_Barcode_Generator {
    /**
     * Generate barcode with order status
     *
     * @param int $order_id
     * @return string Base64 encoded barcode image
     * @throws Exception
     */
    public function generate_with_status($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception(esc_html__('Order not found.', 'seris-order-manager'));
        }

        $status_codes = [
            'pending'    => 1,
            'processing' => 2,
            'on-hold'    => 3,
            'completed'  => 4,
            'shipped'    => 5,
            'refunded'   => 6,
            'failed'     => 7,
            'cancelled'  => 8
        ];
        
        $status_code = $status_codes[$order->get_status()] ?? 0;
        $barcode_data = $order_id . '-' . $status_code;

        if (!class_exists('Picqer\Barcode\BarcodeGeneratorPNG')) {
            throw new Exception(esc_html__('Barcode library not found.', 'seris-order-manager'));
        }

        $generator = new BarcodeGeneratorPNG();
        $barcode_image = $generator->getBarcode(
            $barcode_data,
            $generator::TYPE_CODE_128,
            3,  // Width factor
            60  // Height
        );

        return 'data:image/png;base64,' . base64_encode($barcode_image);
    }

    /**
     * Generate simple barcode
     *
     * @param string $data
     * @param int $widthFactor
     * @param int $height
     * @return string Base64 encoded barcode image
     * @throws Exception
     */
    public function generate($data, $widthFactor = 2, $height = 30) {
        if (!class_exists('Picqer\Barcode\BarcodeGeneratorPNG')) {
            throw new Exception(esc_html__('Barcode library not found.', 'seris-order-manager'));
        }

        $generator = new BarcodeGeneratorPNG();
        $barcode_image = $generator->getBarcode(
            $data,
            $generator::TYPE_CODE_128,
            $widthFactor,
            $height
        );

        return 'data:image/png;base64,' . base64_encode($barcode_image);
    }

    /**
     * Generate barcode for tracking number
     *
     * @param string $tracking_number
     * @return string Base64 encoded barcode image
     * @throws Exception
     */
    public function generate_tracking_barcode($tracking_number) {
        return $this->generate($tracking_number, 2, 40);
    }
}