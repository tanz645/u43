<?php
/**
 * Base Registry Class
 *
 * @package U43
 */

namespace U43\Registry;

abstract class Registry_Base {
    
    protected $items = [];
    
    /**
     * Register an item
     *
     * @param string $id Item ID
     * @param mixed $item Item data
     */
    public function register($id, $item) {
        $this->items[$id] = $item;
    }
    
    /**
     * Get an item by ID
     *
     * @param string $id Item ID
     * @return mixed|null
     */
    public function get($id) {
        return $this->items[$id] ?? null;
    }
    
    /**
     * Get all items
     *
     * @return array
     */
    public function get_all() {
        return $this->items;
    }
    
    /**
     * Check if item exists
     *
     * @param string $id Item ID
     * @return bool
     */
    public function has($id) {
        return isset($this->items[$id]);
    }
    
    /**
     * Unregister an item
     *
     * @param string $id Item ID
     */
    public function unregister($id) {
        unset($this->items[$id]);
    }
}

