<?php
/**
 * Approve Comment Tool
 *
 * @package U43
 */

namespace U43\Tools\Built_In\WordPress;

use U43\Tools\Tool_Base;

class Approve_Comment extends Tool_Base {
    
    /**
     * Execute the tool
     *
     * @param array $inputs Input parameters
     * @return array
     * @throws \Exception
     */
    public function execute($inputs) {
        $comment_id = $inputs['comment_id'] ?? 0;
        
        // Ensure comment_id is an integer
        $comment_id = absint($comment_id);
        
        if (!$comment_id) {
            throw new \Exception('Comment ID is required');
        }
        
        // Verify comment exists
        $comment = get_comment($comment_id);
        if (!$comment) {
            throw new \Exception("Comment ID {$comment_id} does not exist");
        }
        
        // Check current comment status
        $current_status = wp_get_comment_status($comment_id);
        
        // If already approved, return success
        if ($current_status === 'approved') {
            return [
                'success' => true,
                'comment_id' => $comment_id,
                'status' => 'approved',
                'message' => 'Comment was already approved',
            ];
        }
        
        // Check permissions
        if (!current_user_can('moderate_comments')) {
            throw new \Exception('Insufficient permissions to moderate comments');
        }
        
        // Approve comment
        $result = wp_set_comment_status($comment_id, 'approve');
        
        if ($result === false) {
            // Get more details about why it failed
            $current_status = wp_get_comment_status($comment_id);
            $error_details = "Current status: " . ($current_status ? $current_status : 'unknown');
            
            // Check if comment was actually updated despite false return
            $updated_comment = get_comment($comment_id);
            if ($updated_comment && $updated_comment->comment_approved == '1') {
                // Comment was actually approved, wp_set_comment_status might have returned false due to hooks
                return [
                    'success' => true,
                    'comment_id' => $comment_id,
                    'status' => 'approved',
                    'message' => 'Comment approved (status change detected)',
                ];
            }
            
            throw new \Exception("Failed to approve comment ID {$comment_id}. {$error_details}");
        }
        
        return [
            'success' => true,
            'comment_id' => $comment_id,
            'status' => 'approved',
        ];
    }
}

