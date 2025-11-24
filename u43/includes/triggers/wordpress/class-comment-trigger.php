<?php
/**
 * WordPress Comment Trigger
 *
 * @package U43
 */

namespace U43\Triggers\WordPress;

use U43\Triggers\Trigger_Base;

class Comment_Trigger extends Trigger_Base {
    
    /**
     * Register the trigger
     */
    public function register() {
        // Already registered in Core class via comment_post hook
    }
    
    /**
     * Get output schema
     *
     * @return array
     */
    public function get_output_schema() {
        return [
            'comment_id' => 'integer',
            'post_id' => 'integer',
            'author' => 'string',
            'content' => 'string',
            'email' => 'string',
        ];
    }
}

