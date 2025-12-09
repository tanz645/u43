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
     * @param string $variable Variable expression (e.g., {{trigger_data.product_id}})
     * @param array $context Execution context
     * @return mixed|null
     */
    private function resolve_variable($variable, $context) {
        // Handle template syntax {{variable}} or {{node_id.field}}
        if (preg_match_all('/\{\{([^}]+)\}\}/', $variable, $matches)) {
            foreach ($matches[1] as $match) {
                $path = trim($match);
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
}

