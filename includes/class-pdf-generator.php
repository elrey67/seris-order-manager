<?php
class Serisvri_PDF_Generator {
    public function generate_receipt($order_id, $output = true, $full_document = true) {
        // Enqueue receipt styles
        wp_enqueue_style('seris-order-manager-receipt');
        
        // Get order data
        $order = wc_get_order($order_id);
        if (!$order) return '';
        
        // Get settings
        $settings = Serisvri_Core::get_instance()->get_settings();
        
        // Load receipt template
        ob_start();
        include SERIS_ORDER_MANAGER_PATH . '/templates/receipt-template.php';
        $html = ob_get_clean();

        if ($output) {
            $this->output_pdf($html, "receipt-{$order_id}.pdf");
        }

        return $html;
    }

    public function generate_packing_slip($order_id, $output = true) {
        // Enqueue packing slip styles
        wp_enqueue_style('seris-order-manager-packing-slip');
        
        // Get order data
        $order = wc_get_order($order_id);
        if (!$order) return '';
        
        // Load packing slip template
        ob_start();
        include SERIS_ORDER_MANAGER_PATH . '/templates/packaging-slip-template.php';
        $html = ob_get_clean();

        if ($output) {
            $this->output_pdf($html, "packing-slip-{$order_id}.pdf");
        }

        return $html;
    }

    private function output_pdf($html, $filename) {
        $dompdf = new Dompdf\Dompdf();
        $options = new Dompdf\Options();
        
        // PDF configuration
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultEncoding', 'UTF-8');
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf->setOptions($options);
        
        // Get settings for paper size
        $settings = Serisvri_Core::get_instance()->get_settings();
        $dompdf->setPaper($settings->get_paper_size(), 'portrait');
        
        $dompdf->loadHtml($html);
        $dompdf->render();
        
        $dompdf->stream($filename, ['Attachment' => 0]);
        exit;
    }
    
    /**
     * Generate bulk PDF for multiple orders
     */
    public function generate_bulk_pdf($order_ids, $document_type = 'receipt') {
        if (!class_exists('Dompdf\Dompdf')) {
            throw new Exception('DOMPDF library not found');
        }

        $html = '';
        $first_order = true;
        
        foreach ($order_ids as $order_id) {
            if (!$first_order) {
                // Add page break between documents
                $html .= '<div style="page-break-before: always;"></div>';
            }
            
            if ($document_type === 'receipt') {
                $html .= $this->generate_receipt($order_id, false, false);
            } else {
                $html .= $this->generate_packing_slip($order_id, false);
            }
            
            $first_order = false;
        }

        $dompdf = new Dompdf\Dompdf();
        $options = new Dompdf\Options();
        
        // PDF configuration
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultEncoding', 'UTF-8');
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf->setOptions($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = ($document_type === 'receipt') ? 'bulk-receipts.pdf' : 'bulk-packing-slips.pdf';
        $dompdf->stream($filename, ['Attachment' => 0]);
        exit;
    }
}