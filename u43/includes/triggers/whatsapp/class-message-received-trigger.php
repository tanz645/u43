<?php
/**
 * WhatsApp Message Received Trigger
 *
 * @package U43
 */

namespace U43\Triggers\WhatsApp;

use U43\Triggers\Trigger_Base;

class Message_Received_Trigger extends Trigger_Base {
    
    /**
     * Register the trigger
     * This trigger is activated by the webhook handler when a message is received
     */
    public function register() {
        // Trigger is registered and fired by the webhook handler
        // No WordPress hooks needed - webhook handler calls trigger directly
    }
    
    /**
     * Get output schema
     *
     * @return array
     */
    public function get_output_schema() {
        return [
            'message_id' => 'string',
            'from' => 'string',
            'from_name' => 'string',
            'to' => 'string',
            'phone_number_id' => 'string',
            'message_text' => 'string',
            'message_type' => 'string',
            'timestamp' => 'integer',
            'media_url' => 'string',
            'media_type' => 'string',
            'interactive_type' => 'string',
            'button_id' => 'string',
            'button_title' => 'string',
        ];
    }
}

