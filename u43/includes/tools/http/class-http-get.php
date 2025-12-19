<?php
/**
 * HTTP GET Request Tool
 *
 * @package U43
 */

namespace U43\Tools\HTTP;

use U43\Tools\Tool_Base;

class HTTP_Get extends Tool_Base {
    
    /**
     * Execute the tool
     *
     * @param array $inputs Input parameters (already resolved by executor)
     * @param array $context Execution context (for accessing previous node outputs)
     * @return array
     * @throws \Exception
     */
    public function execute($inputs, $context = []) {
        // Get URL
        $url = isset($inputs['url']) ? trim($inputs['url']) : '';
        if (empty($url)) {
            throw new \Exception('URL is required');
        }
        
        // Resolve variables in URL
        $url = $this->resolve_template($url, $context);
        
        // Get URL parameters and replace in URL
        $url_params = isset($inputs['url_params']) && is_array($inputs['url_params']) ? $inputs['url_params'] : [];
        // Filter out internal meta data
        unset($url_params['__pairs_meta__']);
        // Resolve variables in URL params
        $url_params = $this->resolve_variables_in_array($url_params, $context);
        $url = $this->replace_url_params($url, $url_params);
        
        // Get query parameters
        $query_params = isset($inputs['query_params']) && is_array($inputs['query_params']) ? $inputs['query_params'] : [];
        // Filter out internal meta data
        unset($query_params['__pairs_meta__']);
        // Resolve variables in query params
        $query_params = $this->resolve_variables_in_array($query_params, $context);
        
        // Add query parameters to URL
        if (!empty($query_params)) {
            $parsed_url = parse_url($url);
            $existing_query = [];
            if (isset($parsed_url['query'])) {
                parse_str($parsed_url['query'], $existing_query);
            }
            $merged_query = array_merge($existing_query, $query_params);
            $query_string = http_build_query($merged_query);
            
            // Rebuild URL with query string
            if (isset($parsed_url['scheme']) && isset($parsed_url['host'])) {
                $url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
                if (isset($parsed_url['port'])) {
                    $url .= ':' . $parsed_url['port'];
                }
                if (isset($parsed_url['path'])) {
                    $url .= $parsed_url['path'];
                }
                if (!empty($query_string)) {
                    $url .= '?' . $query_string;
                }
                if (isset($parsed_url['fragment'])) {
                    $url .= '#' . $parsed_url['fragment'];
                }
            } else {
                // If URL doesn't have scheme/host, append query string
                $separator = strpos($url, '?') !== false ? '&' : '?';
                $url .= $separator . $query_string;
            }
        }
        
        // Get headers
        $headers = isset($inputs['headers']) && is_array($inputs['headers']) ? $inputs['headers'] : [];
        // Filter out internal meta data and empty pair markers
        unset($headers['__pairs_meta__']);
        $headers = array_filter($headers, function($key) {
            return strpos($key, '__empty_') !== 0;
        }, ARRAY_FILTER_USE_KEY);
        // Resolve variables in headers
        $headers = $this->resolve_variables_in_array($headers, $context);
        
        // Get output format
        $output_format = isset($inputs['output_format']) ? $inputs['output_format'] : 'json';
        
        // Store request details for logging
        $request_details = [
            'method' => 'GET',
            'url' => $url,
            'headers' => $headers,
            'query_params' => $query_params,
            'url_params' => $url_params,
        ];
        
        // Make HTTP GET request
        $response = $this->make_request('GET', $url, $headers);
        
        // Handle output format
        if ($output_format === 'void') {
            return [
                'request' => $request_details,
            ];
        }
        
        // Parse response
        $status_code = $response['status_code'];
        $response_headers = $response['headers'];
        $raw_response = $response['body'];
        
        // Try to parse JSON response
        $body = null;
        if (!empty($raw_response)) {
            $decoded = json_decode($raw_response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $body = $decoded;
            } else {
                // If not JSON, return as string
                $body = $raw_response;
            }
        }
        
        // Apply output schema if provided
        if (isset($inputs['output_schema']) && is_array($inputs['output_schema']) && !empty($inputs['output_schema'])) {
            $body = $this->apply_schema($body, $inputs['output_schema']);
        }
        
        return [
            'request' => $request_details,
            'status_code' => $status_code,
            'headers' => $response_headers,
            'body' => $body,
            'raw_response' => $raw_response,
            'success' => $status_code >= 200 && $status_code < 300,
        ];
    }
    
    /**
     * Replace URL path parameters (e.g., /users/{id} -> /users/123)
     *
     * @param string $url URL with placeholders
     * @param array $params Parameters to replace
     * @return string URL with replaced parameters
     */
    private function replace_url_params($url, $params) {
        foreach ($params as $key => $value) {
            // Replace {key} or :key patterns
            $url = str_replace('{' . $key . '}', urlencode($value), $url);
            $url = str_replace(':' . $key, urlencode($value), $url);
        }
        return $url;
    }
    
    /**
     * Make HTTP request
     *
     * @param string $method HTTP method
     * @param string $url URL
     * @param array $headers Headers
     * @param string|null $body Request body
     * @return array Response data
     * @throws \Exception
     */
    private function make_request($method, $url, $headers = [], $body = null) {
        // Prepare headers array for wp_remote_request
        $wp_headers = [];
        foreach ($headers as $key => $value) {
            $wp_headers[$key] = $value;
        }
        
        // Prepare request arguments
        $args = [
            'method' => $method,
            'headers' => $wp_headers,
            'timeout' => 30,
            'sslverify' => true,
        ];
        
        if ($body !== null) {
            $args['body'] = $body;
        }
        
        // Make request using WordPress HTTP API
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new \Exception('HTTP request failed: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $response_headers = wp_remote_retrieve_headers($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Convert headers to array
        $headers_array = [];
        if ($response_headers instanceof \WP_HTTP_Requests_Response) {
            foreach ($response_headers as $key => $value) {
                $headers_array[$key] = $value;
            }
        } else {
            $headers_array = (array) $response_headers;
        }
        
        return [
            'status_code' => $status_code,
            'headers' => $headers_array,
            'body' => $response_body,
        ];
    }
    
    /**
     * Apply output schema to response body
     *
     * @param mixed $body Response body
     * @param array $schema Schema definition
     * @return mixed Transformed body
     */
    private function apply_schema($body, $schema) {
        if (!is_array($body) || !is_array($schema)) {
            return $body;
        }
        
        $result = [];
        foreach ($schema as $key => $type) {
            if (is_array($type)) {
                // Nested object
                if (isset($body[$key]) && is_array($body[$key])) {
                    $result[$key] = $this->apply_schema($body[$key], $type);
                } else {
                    $result[$key] = null;
                }
            } else {
                // Simple type
                if (isset($body[$key])) {
                    $value = $body[$key];
                    // Type conversion based on schema
                    switch ($type) {
                        case 'number':
                        case 'integer':
                            $result[$key] = is_numeric($value) ? (int) $value : 0;
                            break;
                        case 'float':
                            $result[$key] = is_numeric($value) ? (float) $value : 0.0;
                            break;
                        case 'boolean':
                            $result[$key] = (bool) $value;
                            break;
                        case 'string':
                            $result[$key] = (string) $value;
                            break;
                        case 'array':
                            $result[$key] = is_array($value) ? $value : [];
                            break;
                        case 'object':
                            $result[$key] = is_array($value) || is_object($value) ? (array) $value : [];
                            break;
                        default:
                            $result[$key] = $value;
                    }
                } else {
                    // Set default based on type
                    switch ($type) {
                        case 'number':
                        case 'integer':
                            $result[$key] = 0;
                            break;
                        case 'float':
                            $result[$key] = 0.0;
                            break;
                        case 'boolean':
                            $result[$key] = false;
                            break;
                        case 'string':
                            $result[$key] = '';
                            break;
                        case 'array':
                            $result[$key] = [];
                            break;
                        case 'object':
                            $result[$key] = [];
                            break;
                        default:
                            $result[$key] = null;
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Resolve template string with variables (supports {{variable}}, {{node_id.field}})
     *
     * @param string $template Template string
     * @param array $context Execution context
     * @return string Resolved string
     */
    private function resolve_template($template, $context) {
        if (empty($template) || !is_string($template)) {
            return $template;
        }
        
        // Find all {{variable}} patterns
        if (preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches)) {
            $resolved = $template;
            foreach ($matches[1] as $match) {
                $var_path = trim($match);
                $var_value = $this->resolve_variable_path($var_path, $context);
                
                // Convert to string for replacement
                if (is_array($var_value)) {
                    $var_value = json_encode($var_value);
                } elseif (is_object($var_value)) {
                    $var_value = json_encode($var_value);
                } elseif ($var_value === null) {
                    $var_value = '';
                } else {
                    $var_value = (string)$var_value;
                }
                
                $resolved = str_replace('{{' . $match . '}}', $var_value, $resolved);
            }
            return $resolved;
        }
        
        return $template;
    }
    
    /**
     * Resolve a variable path (supports dots and array brackets)
     * Examples: trigger_data.content, node_123.decision, node_123.results[0].status
     * Also supports: parents.agent.response, parents.action.btn1
     *
     * @param string $path Variable path
     * @param array $context Execution context
     * @return mixed Resolved value or null
     */
    private function resolve_variable_path($path, $context) {
        $remaining_path = trim($path);
        
        // Check for combined parent variable pattern: parents.<type>.<field>
        if (preg_match('/^parents\.([a-zA-Z0-9_]+)\.(.+)$/', $remaining_path, $parent_match)) {
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
                
                // First, try to find a node that has the field (for button message nodes, prioritize nodes with the button ID)
                foreach ($matching_nodes as $node_id) {
                    $node_output = $context[$node_id];
                    if (is_array($node_output)) {
                        // Check if this node has the field directly (for button IDs, check if the field exists as a key)
                        if (isset($node_output[$field_path])) {
                            return $node_output[$field_path];
                        }
                        // Otherwise, try to resolve field path within the node output
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
        $var_value = $context;
        $parts = explode('.', $remaining_path);
        
        foreach ($parts as $part) {
            // Handle array brackets like results[0]
            if (preg_match('/^(.+)\[(\d+)\]$/', $part, $matches)) {
                $key = $matches[1];
                $index = (int)$matches[2];
                
                if (is_array($var_value) && isset($var_value[$key]) && is_array($var_value[$key])) {
                    $var_value = isset($var_value[$key][$index]) ? $var_value[$key][$index] : null;
                } else {
                    return null;
                }
            } else {
                if (is_array($var_value) && isset($var_value[$part])) {
                    $var_value = $var_value[$part];
                } else {
                    return null;
                }
            }
            
            if ($var_value === null) {
                break;
            }
        }
        
        return $var_value;
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
    
    /**
     * Recursively resolve variables in an array/object
     *
     * @param mixed $data Data to resolve (array, object, or string)
     * @param array $context Execution context
     * @return mixed Resolved data
     */
    private function resolve_variables_in_array($data, $context) {
        if (is_array($data)) {
            $resolved = [];
            foreach ($data as $key => $value) {
                // Resolve key if it contains variables
                $resolved_key = is_string($key) ? $this->resolve_template($key, $context) : $key;
                // Recursively resolve value
                $resolved[$resolved_key] = $this->resolve_variables_in_array($value, $context);
            }
            return $resolved;
        } elseif (is_string($data)) {
            return $this->resolve_template($data, $context);
        } else {
            return $data;
        }
    }
}

