<?php
/**
 * WooCommerce Product Deleted Trigger
 *
 * @package U43
 */

namespace U43\Triggers\WooCommerce;

use U43\Triggers\Trigger_Base;

class Product_Deleted_Trigger extends Trigger_Base {
    
    /**
     * Register the trigger
     */
    public function register() {
        // Hook is registered in Core class via before_delete_post and woocommerce_delete_product hooks
    }
    
    /**
     * Get output schema
     *
     * @return array
     */
    public function get_output_schema() {
        return [
            'product_id' => 'integer',
            'product_name' => 'string',
            'product_sku' => 'string',
            'product_price' => 'string',
            'product_type' => 'string',
            'product_status' => 'string',
            'product_url' => 'string',
            'product_author' => 'integer',
            'product_created_date' => 'string',
            'product_modified_date' => 'string',
            'deleted_date' => 'string',
        ];
    }
}




