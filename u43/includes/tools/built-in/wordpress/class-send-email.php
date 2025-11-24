<?php
/**
 * Send Email Tool
 *
 * @package U43
 */

namespace U43\Tools\Built_In\WordPress;

use U43\Tools\Tool_Base;

class Send_Email extends Tool_Base {
    
    /**
     * Execute the tool
     *
     * @param array $inputs Input parameters
     * @return array
     * @throws \Exception
     */
    public function execute($inputs) {
        $to = $inputs['to'] ?? '';
        $subject = $inputs['subject'] ?? '';
        $message = $inputs['message'] ?? '';
        
        if (empty($to) || empty($subject) || empty($message)) {
            throw new \Exception('To, subject, and message are required');
        }
        
        // Send email
        $result = wp_mail($to, $subject, $message);
        
        return [
            'success' => $result,
            'to' => $to,
            'subject' => $subject,
        ];
    }
}

