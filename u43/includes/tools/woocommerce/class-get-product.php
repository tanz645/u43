<?php
/**
 * Get WooCommerce Product Tool
 *
 * @package U43
 */

namespace U43\Tools\WooCommerce;

use U43\Tools\Tool_Base;

class Get_Product extends Tool_Base {
    
    /**
     * Execute the tool
     *
     * @param array $inputs Input parameters (already resolved by executor)
     * @param array $context Execution context (for accessing previous node outputs)
     * @return array
     * @throws \Exception
     */
    public function execute($inputs, $context = []) {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            throw new \Exception('WooCommerce is not active. Please install and activate WooCommerce.');
        }
        
        if (!function_exists('wc_get_product')) {
            throw new \Exception('WooCommerce functions are not available.');
        }
        
        // Get product_id from inputs
        // The executor already resolves variables in inputs, so product_id should be resolved
        $product_id = isset($inputs['product_id']) ? absint($inputs['product_id']) : 0;
        
        // If product_id_variable was provided, it should already be resolved to product_id
        // But as a fallback, check if it's still a variable string
        if (!$product_id && !empty($inputs['product_id_variable'])) {
            $variable_value = $this->resolve_variable($inputs['product_id_variable'], $context);
            if ($variable_value !== null) {
                $product_id = absint($variable_value);
            }
        }
        
        // Fallback: Try to find product_id in context (from trigger or previous nodes)
        if (!$product_id) {
            // Check trigger_data
            if (isset($context['trigger_data']['product_id'])) {
                $product_id = absint($context['trigger_data']['product_id']);
            }
            // Check previous node outputs (search all node outputs for product_id)
            else {
                foreach ($context as $key => $value) {
                    if (is_array($value) && isset($value['product_id'])) {
                        $product_id = absint($value['product_id']);
                        break;
                    }
                }
            }
        }
        
        if (!$product_id) {
            throw new \Exception('Product ID is required. Please provide a product_id or product_id_variable.');
        }
        
        // Get WooCommerce product
        $product = wc_get_product($product_id);
        
        if (!$product) {
            throw new \Exception("Product with ID {$product_id} not found.");
        }
        
        // Get product image
        $image_id = $product->get_image_id();
        $image_url = '';
        if ($image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'full');
        }
        
        // Get product dimensions
        $dimensions = [
            'length' => $product->get_length() ?: '',
            'width' => $product->get_width() ?: '',
            'height' => $product->get_height() ?: '',
        ];
        
        // Get product categories
        $categories = [];
        $category_ids = $product->get_category_ids();
        if (!empty($category_ids)) {
            foreach ($category_ids as $cat_id) {
                $term = get_term($cat_id, 'product_cat');
                if ($term && !is_wp_error($term)) {
                    $categories[] = [
                        'id' => $cat_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                    ];
                }
            }
        }
        
        // Get product tags
        $tags = [];
        $tag_ids = $product->get_tag_ids();
        if (!empty($tag_ids)) {
            foreach ($tag_ids as $tag_id) {
                $term = get_term($tag_id, 'product_tag');
                if ($term && !is_wp_error($term)) {
                    $tags[] = [
                        'id' => $tag_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                    ];
                }
            }
        }
        
        // Get post object for dates
        $post = get_post($product_id);
        
        // Prepare output
        return [
            'product_id' => $product_id,
            'product_name' => $product->get_name(),
            'product_sku' => $product->get_sku() ?: '',
            'product_price' => $product->get_price() ?: '0',
            'product_regular_price' => $product->get_regular_price() ?: '0',
            'product_sale_price' => $product->get_sale_price() ?: '0',
            'product_type' => $product->get_type(),
            'product_status' => $product->get_status(),
            'product_url' => $product->get_permalink(),
            'product_image_url' => $image_url,
            'product_description' => $product->get_description() ?: '',
            'product_short_description' => $product->get_short_description() ?: '',
            'product_stock_status' => $product->get_stock_status(),
            'product_stock_quantity' => $product->get_stock_quantity() ?: 0,
            'product_manage_stock' => $product->get_manage_stock(),
            'product_weight' => $product->get_weight() ?: '',
            'product_dimensions' => $dimensions,
            'product_categories' => $categories,
            'product_tags' => $tags,
            'product_author' => $post ? (int)$post->post_author : 0,
            'product_created_date' => $post ? $post->post_date : '',
            'product_modified_date' => $post ? $post->post_modified : '',
        ];
    }
    
    /**
     * Resolve variable from context
     *
     * @param string $variable Variable expression (e.g., {{trigger_data.product_id}}, {{parents.action.btn1}})
     * @param array $context Execution context
     * @return mixed|null
     */
    private function resolve_variable($variable, $context) {
        // Handle template syntax {{variable}} or {{node_id.field}} or {{parents.type.field}}
        if (preg_match_all('/\{\{([^}]+)\}\}/', $variable, $matches)) {
            foreach ($matches[1] as $match) {
                $path = trim($match);
                
                // Check for combined parent variable pattern: parents.<type>.<field>
                if (preg_match('/^parents\.([a-zA-Z0-9_]+)\.(.+)$/', $path, $parent_match)) {
                    $parent_type = $parent_match[1];
                    $field_path = $parent_match[2];
                    
                    // Find all nodes of the specified type that have been executed
                    $node_types = $context['_node_types'] ?? [];
                    $matching_nodes = [];
                    
                    foreach ($node_types as $node_id => $node_type) {
                        if ($node_type === $parent_type && isset($context[$node_id])) {
                            $matching_nodes[] = $node_id;
                        }
                    }
                    
                    // If we have matching nodes, try to resolve the field from the first one
                    if (!empty($matching_nodes)) {
                        // Try nodes in reverse order (most recently executed first)
                        $matching_nodes = array_reverse($matching_nodes);
                        foreach ($matching_nodes as $node_id) {
                            $node_output = $context[$node_id];
                            if (is_array($node_output)) {
                                // Resolve field path within the node output
                                $field_value = $this->resolve_field_path($field_path, $node_output);
                                if ($field_value !== null) {
                                    return $field_value;
                                }
                            }
                        }
                    }
                    
                    // No matching parent node found
                    return null;
                }
                
                // Standard variable path resolution
                $parts = explode('.', $path);
                $value = $context;
                
                foreach ($parts as $part) {
                    if (is_array($value) && isset($value[$part])) {
                        $value = $value[$part];
                    } else {
                        return null;
                    }
                }
                
                return $value;
            }
        }
        
        // Direct variable access
        if (isset($context[$variable])) {
            return $context[$variable];
        }
        
        return null;
    }
    
    /**
     * Resolve a field path within a data structure (supports dots and array brackets)
     * Helper function for resolving nested fields in parent node outputs
     *
     * @param string $field_path Field path (e.g., "btn1", "response", "results[0].status")
     * @param mixed $data Data structure to search in
     * @return mixed Resolved value or null
     */
    private function resolve_field_path($field_path, $data) {
        if (!is_array($data)) {
            return null;
        }
        
        $remaining_path = trim($field_path);
        $var_value = $data;
        
        while (!empty($remaining_path)) {
            // Check for array bracket pattern: key[index]
            if (preg_match('/^([a-zA-Z0-9_]+)\[(\d+)\](.*)$/', $remaining_path, $array_match)) {
                $key = $array_match[1];
                $index = (int)$array_match[2];
                $remaining_path = ltrim($array_match[3], '.');
                
                if (is_array($var_value) && isset($var_value[$key]) && is_array($var_value[$key])) {
                    if (isset($var_value[$key][$index])) {
                        $var_value = $var_value[$key][$index];
                    } else {
                        return null;
                    }
                } else {
                    return null;
                }
            } 
            // Check for simple key access
            elseif (preg_match('/^([a-zA-Z0-9_]+)(.*)$/', $remaining_path, $key_match)) {
                $key = $key_match[1];
                $remaining_path = ltrim($key_match[2], '.');
                
                if (is_array($var_value) && isset($var_value[$key])) {
                    $var_value = $var_value[$key];
                } else {
                    return null;
                }
            } else {
                return null;
            }
        }
        
        return $var_value;
    }
}

