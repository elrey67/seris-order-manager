<?php
/**
 * QR Code Generator Class
 * 
 * @package Seris_Order_Manager
 */
if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('Serisvri_QRCode_Generator')) {
    class Serisvri_QRCode_Generator {
        
        /**
         * Generate QR code for an order
         *
         * @param int $order_id Order ID
         * @return string Base64 encoded image data URI or empty string on failure
         */
        public function generate($order_id) {
            try {
                // Check if required classes exist
                if (!class_exists('Endroid\QrCode\QrCode') || 
                    !class_exists('Endroid\QrCode\Writer\PngWriter')) {
                    return ''; // Silently fail if library not available
                }
                $order = wc_get_order($order_id);
                if (!$order) {
                    return ''; // Silently fail if order not found
                }
                $qrUrl = add_query_arg('order_id', $order_id, home_url('/'));
                $qrCode = new \Endroid\QrCode\QrCode($qrUrl);
                $qrCode->setSize(300);
                $qrCode->setMargin(10);
                $qrCode->setEncoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'));
                $qrCode->setErrorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::High);
                $writer = new \Endroid\QrCode\Writer\PngWriter();
                $result = $writer->write($qrCode);
                
                $qrString = $result->getString();
                if (empty($qrString)) {
                    return ''; // Silently fail if empty QR code
                }
                return 'data:image/png;base64,' . base64_encode($qrString);
            } catch (Exception $e) {
                return '';
            }
        }
    }
}