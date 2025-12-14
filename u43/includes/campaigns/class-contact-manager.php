<?php
/**
 * Contact Manager
 *
 * @package U43
 */

namespace U43\Campaigns;

class Contact_Manager {
    
    private $wpdb;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Format phone number - preserve + sign at the beginning
     *
     * @param string $phone Phone number
     * @return string Formatted phone number
     */
    private function format_phone_number($phone) {
        $phone = trim($phone);
        // Remove all non-digit characters except + at the start
        if (strpos($phone, '+') === 0) {
            // Keep + at the beginning, remove all other non-digits
            return '+' . preg_replace('/[^\d]/', '', substr($phone, 1));
        } else {
            // No + at start, remove all non-digits
            return preg_replace('/[^\d]/', '', $phone);
        }
    }
    
    /**
     * Get all contacts
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_contacts($args = []) {
        $defaults = [
            'folder_id' => '',
            'tag_id' => '',
            'search' => '',
            'per_page' => 50,
            'page' => 1,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $table = $this->wpdb->prefix . 'u43_campaign_contacts';
        $contact_tags_table = $this->wpdb->prefix . 'u43_campaign_contact_tags';
        
        $where = '1=1';
        $joins = '';
        
        if (!empty($args['folder_id'])) {
            $where .= $this->wpdb->prepare(' AND folder_id = %d', $args['folder_id']);
        }
        
        if (!empty($args['tag_id'])) {
            $joins .= " INNER JOIN $contact_tags_table ct ON c.id = ct.contact_id";
            $where .= $this->wpdb->prepare(' AND ct.tag_id = %d', $args['tag_id']);
        }
        
        if (!empty($args['search'])) {
            $search = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $where .= $this->wpdb->prepare(' AND (name LIKE %s OR phone LIKE %s)', $search, $search);
        }
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $query = "SELECT c.* FROM $table c $joins WHERE $where ORDER BY c.$orderby LIMIT %d OFFSET %d";
        $contacts = $this->wpdb->get_results($this->wpdb->prepare($query, $args['per_page'], $offset));
        
        // Get tags for each contact
        foreach ($contacts as $contact) {
            $contact->tags = $this->get_contact_tags($contact->id);
        }
        
        $total = $this->wpdb->get_var("SELECT COUNT(DISTINCT c.id) FROM $table c $joins WHERE $where");
        
        return [
            'items' => $contacts,
            'total' => (int) $total,
            'pages' => ceil($total / $args['per_page'])
        ];
    }
    
    /**
     * Get contact by ID
     *
     * @param int $contact_id Contact ID
     * @return object|null
     */
    public function get_contact($contact_id) {
        $table = $this->wpdb->prefix . 'u43_campaign_contacts';
        $contact = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM $table WHERE id = %d", $contact_id));
        
        if ($contact) {
            $contact->tags = $this->get_contact_tags($contact_id);
        }
        
        return $contact;
    }
    
    /**
     * Create contact
     *
     * @param array $data Contact data
     * @return int|false Contact ID or false on failure
     */
    public function create_contact($data) {
        try {
            $table = $this->wpdb->prefix . 'u43_campaign_contacts';
            
            // Phone is required
            if (empty($data['phone'])) {
                error_log('U43: Contact creation failed - phone is required');
                return false;
            }
            
            $defaults = [
                'name' => '',
                'phone' => '',
                'folder_id' => null,
                'notes' => '',
                'created_by' => get_current_user_id()
            ];
            
            $data = wp_parse_args($data, $defaults);
            
            // Format phone number - preserve + sign at the beginning
            $phone = $this->format_phone_number($data['phone']);
            
            if (empty($phone) || (strpos($phone, '+') === 0 && strlen($phone) < 2)) {
                error_log('U43: Contact creation failed - invalid phone number');
                return false;
            }
            
            // Check if contact with this phone already exists
            $existing = $this->wpdb->get_var($this->wpdb->prepare("SELECT id FROM {$table} WHERE phone = %s", $phone));
            if ($existing) {
                error_log('U43: Contact creation failed - phone number already exists: ' . $phone);
                // Store error in wpdb for REST API to retrieve
                $this->wpdb->last_error = 'A contact with this phone number already exists.';
                return false;
            }
            
            // If name is empty, use phone number as name
            $name = !empty($data['name']) ? sanitize_text_field($data['name']) : $phone;
            
            // Prepare insert data
            $insert_data = [
                'name' => $name,
                'phone' => $phone,
                'notes' => sanitize_textarea_field($data['notes']),
                'created_by' => absint($data['created_by'])
            ];
            
            // Add folder_id if provided (don't include if null or empty)
            if (!empty($data['folder_id']) && $data['folder_id'] !== null) {
                $insert_data['folder_id'] = absint($data['folder_id']);
            }
            
            $result = $this->wpdb->insert($table, $insert_data);
            
            if ($result === false) {
                $error = $this->wpdb->last_error;
                error_log('U43: Failed to create contact - ' . ($error ?: 'Unknown database error'));
                error_log('U43: Insert data: ' . print_r($insert_data, true));
                return false;
            }
            
            $contact_id = $this->wpdb->insert_id;
            
            // Add tags if provided
            if (!empty($data['tags']) && is_array($data['tags'])) {
                $this->set_contact_tags($contact_id, $data['tags']);
            }
            
            return $contact_id;
        } catch (\Exception $e) {
            error_log('U43: Exception in create_contact - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update contact
     *
     * @param int $contact_id Contact ID
     * @param array $data Contact data
     * @return bool
     */
    public function update_contact($contact_id, $data) {
        try {
            $table = $this->wpdb->prefix . 'u43_campaign_contacts';
            
            // Check if contact exists
            $contact = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $contact_id));
            if (!$contact) {
                error_log('U43: Contact update failed - contact not found: ' . $contact_id);
                return false;
            }
            
            // Format phone number if provided - preserve + sign at the beginning
            if (isset($data['phone'])) {
                $phone = $this->format_phone_number($data['phone']);
                
                // Check if phone number is being changed and if it already exists
                if ($phone !== $contact->phone) {
                    $existing = $this->wpdb->get_var($this->wpdb->prepare("SELECT id FROM {$table} WHERE phone = %s AND id != %d", $phone, $contact_id));
                    if ($existing) {
                        error_log('U43: Contact update failed - phone number already exists: ' . $phone);
                        $this->wpdb->last_error = 'A contact with this phone number already exists.';
                        return false;
                    }
                }
                
                $data['phone'] = $phone;
            }
            
            // Prepare update data
            $update_data = [];
            
            if (isset($data['name'])) {
                $update_data['name'] = sanitize_text_field($data['name']);
            }
            if (isset($data['phone'])) {
                $update_data['phone'] = $data['phone'];
            }
            if (isset($data['folder_id'])) {
                $update_data['folder_id'] = !empty($data['folder_id']) ? absint($data['folder_id']) : null;
            }
            if (isset($data['notes'])) {
                $update_data['notes'] = sanitize_textarea_field($data['notes']);
            }
            
            if (empty($update_data)) {
                // No data to update, but still update tags if provided
                if (isset($data['tags']) && is_array($data['tags'])) {
                    $this->set_contact_tags($contact_id, $data['tags']);
                }
                return true;
            }
            
            $result = $this->wpdb->update(
                $table,
                $update_data,
                ['id' => $contact_id],
                null,
                ['%d']
            );
            
            if ($result === false) {
                $error = $this->wpdb->last_error;
                error_log('U43: Failed to update contact - ' . ($error ?: 'Unknown database error'));
                return false;
            }
            
            // Update tags if provided
            if (isset($data['tags']) && is_array($data['tags'])) {
                $this->set_contact_tags($contact_id, $data['tags']);
            }
            
            return true;
        } catch (\Exception $e) {
            error_log('U43: Exception in update_contact - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete contact
     *
     * @param int $contact_id Contact ID
     * @return bool
     */
    public function delete_contact($contact_id) {
        try {
            $table = $this->wpdb->prefix . 'u43_campaign_contacts';
            
            // Check if contact exists
            $contact = $this->wpdb->get_row($this->wpdb->prepare("SELECT id FROM {$table} WHERE id = %d", $contact_id));
            if (!$contact) {
                error_log('U43: Contact deletion failed - contact not found: ' . $contact_id);
                return false;
            }
            
            $result = $this->wpdb->delete($table, ['id' => $contact_id], ['%d']);
            
            if ($result === false) {
                $error = $this->wpdb->last_error;
                error_log('U43: Failed to delete contact - ' . ($error ?: 'Unknown database error'));
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            error_log('U43: Exception in delete_contact - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Import contacts from CSV
     *
     * @param string $file_path CSV file path
     * @param array $options Import options
     * @return array Results
     */
    public function import_contacts_from_csv($file_path, $options = []) {
        $defaults = [
            'skip_first_row' => true,
            'name_column' => 0,
            'phone_column' => 1,
            'tags_column' => 2,
            'folder_id' => null
        ];
        
        $options = wp_parse_args($options, $defaults);
        
        $results = [
            'imported' => 0,
            'skipped' => 0,
            'errors' => []
        ];
        
        if (!file_exists($file_path)) {
            $results['errors'][] = 'File not found';
            return $results;
        }
        
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            $results['errors'][] = 'Could not open file';
            return $results;
        }
        
        $row_number = 0;
        
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            
            // Skip first row if it's headers
            if ($options['skip_first_row'] && $row_number === 1) {
                continue;
            }
            
            // Validate required columns
            if (!isset($row[$options['name_column']]) || !isset($row[$options['phone_column']])) {
                $results['skipped']++;
                $results['errors'][] = "Row $row_number: Missing required columns";
                continue;
            }
            
            $name = trim($row[$options['name_column']]);
            $phone = trim($row[$options['phone_column']]);
            $tags = isset($row[$options['tags_column']]) ? trim($row[$options['tags_column']]) : '';
            
            if (empty($name) || empty($phone)) {
                $results['skipped']++;
                continue;
            }
            
            // Parse tags (comma-separated)
            $tag_names = [];
            if (!empty($tags)) {
                $tag_names = array_map('trim', explode(',', $tags));
            }
            
            // Create or get tag IDs
            $tag_ids = [];
            foreach ($tag_names as $tag_name) {
                if (!empty($tag_name)) {
                    $tag_id = $this->get_or_create_tag($tag_name);
                    if ($tag_id) {
                        $tag_ids[] = $tag_id;
                    }
                }
            }
            
            // Check if contact already exists
            $existing = $this->get_contact_by_phone($phone);
            
            if ($existing) {
                // Update existing contact
                $this->update_contact($existing->id, [
                    'name' => $name,
                    'folder_id' => $options['folder_id'],
                    'tags' => $tag_ids
                ]);
            } else {
                // Create new contact
                $this->create_contact([
                    'name' => $name,
                    'phone' => $phone,
                    'folder_id' => $options['folder_id'],
                    'tags' => $tag_ids
                ]);
            }
            
            $results['imported']++;
        }
        
        fclose($handle);
        
        return $results;
    }
    
    /**
     * Get contact by phone number
     *
     * @param string $phone Phone number
     * @return object|null
     */
    public function get_contact_by_phone($phone) {
        $table = $this->wpdb->prefix . 'u43_campaign_contacts';
        // Format phone number - preserve + sign at the beginning
        $phone = $this->format_phone_number($phone);
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM $table WHERE phone = %s", $phone));
    }
    
    /**
     * Get contact tags
     *
     * @param int $contact_id Contact ID
     * @return array
     */
    public function get_contact_tags($contact_id) {
        $tags_table = $this->wpdb->prefix . 'u43_campaign_tags';
        $contact_tags_table = $this->wpdb->prefix . 'u43_campaign_contact_tags';
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT t.* FROM $tags_table t
            INNER JOIN $contact_tags_table ct ON t.id = ct.tag_id
            WHERE ct.contact_id = %d",
            $contact_id
        ));
    }
    
    /**
     * Set contact tags
     *
     * @param int $contact_id Contact ID
     * @param array $tag_ids Tag IDs
     * @return bool
     */
    public function set_contact_tags($contact_id, $tag_ids) {
        $table = $this->wpdb->prefix . 'u43_campaign_contact_tags';
        
        // Delete existing tags
        $this->wpdb->delete($table, ['contact_id' => $contact_id]);
        
        // Insert new tags
        foreach ($tag_ids as $tag_id) {
            $this->wpdb->insert($table, [
                'contact_id' => $contact_id,
                'tag_id' => $tag_id
            ]);
        }
        
        return true;
    }
    
    /**
     * Get all tags
     *
     * @return array
     */
    public function get_tags() {
        $table = $this->wpdb->prefix . 'u43_campaign_tags';
        return $this->wpdb->get_results("SELECT * FROM $table ORDER BY name");
    }
    
    /**
     * Get or create tag
     *
     * @param string $tag_name Tag name
     * @param string $color Tag color (optional)
     * @return int|false Tag ID or false
     */
    public function get_or_create_tag($tag_name, $color = '#0073aa') {
        $table = $this->wpdb->prefix . 'u43_campaign_tags';
        
        $tag = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM $table WHERE name = %s", $tag_name));
        
        if ($tag) {
            return $tag->id;
        }
        
        // Create new tag
        $result = $this->wpdb->insert($table, [
            'name' => $tag_name,
            'color' => $color
        ]);
        
        if ($result) {
            return $this->wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Create tag
     *
     * @param array $data Tag data
     * @return int|false Tag ID or false
     */
    public function create_tag($data) {
        try {
            $table = $this->wpdb->prefix . 'u43_campaign_tags';
            
            // Name is required
            if (empty($data['name'])) {
                error_log('U43: Tag creation failed - name is required');
                return false;
            }
            
            $defaults = [
                'name' => '',
                'color' => '#0073aa'
            ];
            
            $data = wp_parse_args($data, $defaults);
            
            // Sanitize name
            $data['name'] = sanitize_text_field($data['name']);
            // Sanitize color (hex color format)
            $color = isset($data['color']) ? $data['color'] : '#0073aa';
            if (!preg_match('/^#[a-f0-9]{6}$/i', $color)) {
                $color = '#0073aa';
            }
            $data['color'] = $color;
            
            // Check if tag already exists
            $existing = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$table} WHERE name = %s", $data['name']));
            if ($existing) {
                return $existing->id;
            }
            
            $result = $this->wpdb->insert($table, [
                'name' => $data['name'],
                'color' => $data['color']
            ]);
            
            if ($result === false) {
                $error = $this->wpdb->last_error;
                error_log('U43: Failed to create tag - ' . ($error ?: 'Unknown database error'));
                return false;
            }
            
            return $this->wpdb->insert_id;
        } catch (\Exception $e) {
            error_log('U43: Exception in create_tag - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all folders
     *
     * @return array
     */
    public function get_folders() {
        $table = $this->wpdb->prefix . 'u43_campaign_folders';
        return $this->wpdb->get_results("SELECT * FROM $table ORDER BY name");
    }
    
    /**
     * Create folder
     *
     * @param array $data Folder data
     * @return int|false Folder ID or false
     */
    public function create_folder($data) {
        try {
            $table = $this->wpdb->prefix . 'u43_campaign_folders';
            
            // Name is required
            if (empty($data['name'])) {
                error_log('U43: Folder creation failed - name is required');
                return false;
            }
            
            $defaults = [
                'name' => '',
                'description' => '',
                'created_by' => get_current_user_id()
            ];
            
            $data = wp_parse_args($data, $defaults);
            
            // Sanitize data
            $data['name'] = sanitize_text_field($data['name']);
            $data['description'] = sanitize_textarea_field($data['description']);
            $data['created_by'] = absint($data['created_by']);
            
            $result = $this->wpdb->insert($table, [
                'name' => $data['name'],
                'description' => $data['description'],
                'created_by' => $data['created_by']
            ]);
            
            if ($result === false) {
                $error = $this->wpdb->last_error;
                error_log('U43: Failed to create folder - ' . ($error ?: 'Unknown database error'));
                return false;
            }
            
            return $this->wpdb->insert_id;
        } catch (\Exception $e) {
            error_log('U43: Exception in create_folder - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update folder
     *
     * @param int $folder_id Folder ID
     * @param array $data Folder data
     * @return bool
     */
    public function update_folder($folder_id, $data) {
        $table = $this->wpdb->prefix . 'u43_campaign_folders';
        return $this->wpdb->update($table, $data, ['id' => $folder_id]) !== false;
    }
    
    /**
     * Delete folder
     *
     * @param int $folder_id Folder ID
     * @return bool
     */
    public function delete_folder($folder_id) {
        $table = $this->wpdb->prefix . 'u43_campaign_folders';
        return $this->wpdb->delete($table, ['id' => $folder_id]) !== false;
    }
}


