<?php
/**
 * Delete Comment Tool
 *
 * @package U43
 */

namespace U43\Tools\Built_In\WordPress;

use U43\Tools\Tool_Base;

class Delete_Comment extends Tool_Base {
    
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
        
        // Check permissions
        if (!current_user_can('moderate_comments')) {
            throw new \Exception('Insufficient permissions to moderate comments');
        }
        
        // Delete comment
        $result = wp_delete_comment($comment_id, true);
        
        if ($result === false) {
            throw new \Exception("Failed to delete comment ID {$comment_id}");
        }
        
        return [
            'success' => true,
            'comment_id' => $comment_id,
            'status' => 'deleted',
        ];
    }
}

