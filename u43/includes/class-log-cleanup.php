<?php
/**
 * Log Cleanup Class
 * Handles automatic deletion of old workflow execution logs
 *
 * @package U43
 */

namespace U43;

use U43\Config\Settings_Manager;

class Log_Cleanup {
    
    const CRON_HOOK = 'u43_cleanup_old_logs';
    const TRANSIENT_KEY = 'u43_cleanup_running';
    
    /**
     * Initialize the log cleanup system
     */
    public static function init() {
        add_filter('cron_schedules', [__CLASS__, 'add_cron_schedules']);
        add_action('init', [__CLASS__, 'schedule_cleanup']);
        add_action(self::CRON_HOOK, [__CLASS__, 'cleanup_old_logs']);
        add_action('admin_init', [__CLASS__, 'check_and_run_overdue_cleanup']);
    }
    
    /**
     * Check if cleanup is overdue and run it if on executions page
     */
    public static function check_and_run_overdue_cleanup() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'u43-executions') {
            return;
        }
        
        if (!self::is_enabled()) {
            return;
        }
        
        $next_run = wp_next_scheduled(self::CRON_HOOK);
        if ($next_run && $next_run < time()) {
            if (get_transient(self::TRANSIENT_KEY)) {
                return;
            }
            
            set_transient(self::TRANSIENT_KEY, true, 60);
            self::cleanup_old_logs();
            self::schedule_cleanup();
        }
    }
    
    /**
     * Add custom cron schedules
     */
    public static function add_cron_schedules($schedules) {
        $schedules['u43_log_cleanup_minute'] = ['interval' => 60, 'display' => __('Every Minute', 'u43')];
        $schedules['u43_log_cleanup_hour'] = ['interval' => 3600, 'display' => __('Every Hour', 'u43')];
        $schedules['u43_log_cleanup_day'] = ['interval' => 86400, 'display' => __('Every Day', 'u43')];
        return $schedules;
    }
    
    /**
     * Schedule the cleanup event based on current settings
     */
    public static function schedule_cleanup() {
        if (!self::is_enabled()) {
            $timestamp = wp_next_scheduled(self::CRON_HOOK);
            if ($timestamp) {
                wp_unschedule_event($timestamp, self::CRON_HOOK);
            }
            return;
        }
        
        $settings = self::get_settings();
        $schedule_interval = 'u43_log_cleanup_' . $settings['unit'];
        $next_scheduled = wp_next_scheduled(self::CRON_HOOK);
        
        if (!$next_scheduled) {
            wp_schedule_event(time(), $schedule_interval, self::CRON_HOOK);
        } elseif (wp_get_schedule(self::CRON_HOOK) !== $schedule_interval) {
            wp_unschedule_event($next_scheduled, self::CRON_HOOK);
            wp_schedule_event(time(), $schedule_interval, self::CRON_HOOK);
        }
    }
    
    /**
     * Cleanup old execution logs
     * Only deletes completed executions (not running ones) based on completed_at time
     * Processes in batches with limits to prevent timeouts on large volumes
     *
     * @param bool $manual Whether this is a manual cleanup (allows processing more logs)
     * @return int Number of logs deleted
     */
    public static function cleanup_old_logs($manual = false) {
        if (!self::is_enabled()) {
            return 0;
        }
        
        $settings = self::get_settings();
        if ($settings['duration'] <= 0) {
            return 0;
        }
        
        $seconds = self::duration_to_seconds($settings['duration'], $settings['unit']);
        if ($seconds <= 0) {
            return 0;
        }
        
        // Set execution time limit for cleanup
        $max_execution_time = $manual ? 60 : 30; // More time for manual cleanup
        $start_time = time();
        
        // Limit how many logs we process per run to prevent timeouts
        // Manual cleanup can process more since user initiated it
        $max_logs_per_run = $manual ? 5000 : 1000;
        
        global $wpdb;
        $executions_table = $wpdb->prefix . 'u43_executions';
        $node_logs_table = $wpdb->prefix . 'u43_node_logs';
        
        // Get IDs of completed executions to delete (with limit, oldest first)
        $all_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$executions_table} 
             WHERE status != 'running' 
             AND (
                 (completed_at IS NOT NULL AND completed_at < DATE_SUB(NOW(), INTERVAL %d SECOND))
                 OR 
                 (completed_at IS NULL AND started_at < DATE_SUB(NOW(), INTERVAL %d SECOND))
             )
             ORDER BY COALESCE(completed_at, started_at) ASC
             LIMIT %d",
            $seconds,
            $seconds,
            $max_logs_per_run
        ));
        
        if (empty($all_ids)) {
            return 0;
        }
        
        $total_deleted = 0;
        $batch_size = 100;
        $batches = array_chunk($all_ids, $batch_size);
        
        foreach ($batches as $batch_ids) {
            // Check if we're running out of time
            if ((time() - $start_time) >= $max_execution_time) {
                break;
            }
            
            $ids_placeholder = implode(',', array_map('intval', $batch_ids));
            
            // Delete node logs for this batch
            $wpdb->query("DELETE FROM {$node_logs_table} WHERE execution_id IN ({$ids_placeholder})");
            
            // Delete executions for this batch
            $batch_deleted = $wpdb->query("DELETE FROM {$executions_table} WHERE id IN ({$ids_placeholder})");
            
            if ($batch_deleted !== false && $batch_deleted > 0) {
                $total_deleted += $batch_deleted;
            } elseif ($batch_deleted === false) {
                error_log('U43: Error deleting execution logs - ' . $wpdb->last_error);
                break; // Stop on error
            }
        }
        
        return $total_deleted;
    }
    
    /**
     * Manually trigger cleanup
     * For manual cleanup, we can process more logs (up to 5000) since user initiated it
     */
    public static function manual_cleanup() {
        if (!self::is_enabled()) {
            return [
                'success' => false,
                'message' => __('Log retention is not enabled.', 'u43'),
            ];
        }
        
        $before_count = self::get_logs_to_delete_count();
        delete_transient(self::TRANSIENT_KEY);
        
        // For manual cleanup, allow processing more logs
        $deleted = self::cleanup_old_logs(true);
        $after_count = self::get_logs_to_delete_count();
        
        if ($deleted > 0) {
            $message = sprintf(__('Log cleanup completed successfully. Deleted %d log(s).', 'u43'), $deleted);
            if ($after_count > 0) {
                $message .= ' ' . sprintf(__('%d log(s) still remain and will be cleaned up in the next scheduled run.', 'u43'), $after_count);
            }
        } elseif ($after_count > 0) {
            $message = sprintf(__('Log cleanup attempted but %d log(s) still remain. They will be cleaned up in batches during scheduled runs.', 'u43'), $after_count);
        } else {
            $message = __('Log cleanup completed. No logs needed cleanup.', 'u43');
        }
        
        return ['success' => true, 'message' => $message, 'deleted' => $deleted];
    }
    
    /**
     * Get count of logs that would be deleted
     */
    public static function get_logs_to_delete_count() {
        if (!self::is_enabled()) {
            return 0;
        }
        
        $settings = self::get_settings();
        $seconds = self::duration_to_seconds($settings['duration'], $settings['unit']);
        
        if ($seconds <= 0) {
            return 0;
        }
        
        global $wpdb;
        $executions_table = $wpdb->prefix . 'u43_executions';
        
        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$executions_table} 
             WHERE status != 'running' 
             AND (
                 (completed_at IS NOT NULL AND completed_at < DATE_SUB(NOW(), INTERVAL %d SECOND))
                 OR 
                 (completed_at IS NULL AND started_at < DATE_SUB(NOW(), INTERVAL %d SECOND))
             )",
            $seconds,
            $seconds
        )));
    }
    
    /**
     * Get debug information about cleanup status
     */
    public static function get_debug_info() {
        $settings = self::get_settings();
        $info = [
            'enabled' => $settings['enabled'],
            'duration' => $settings['duration'],
            'unit' => $settings['unit'],
            'next_scheduled' => wp_next_scheduled(self::CRON_HOOK),
            'current_time' => current_time('mysql'),
            'current_timestamp' => current_time('timestamp'),
        ];
        
        if ($settings['enabled'] && $settings['duration'] > 0) {
            global $wpdb;
            $executions_table = $wpdb->prefix . 'u43_executions';
            $seconds = self::duration_to_seconds($settings['duration'], $settings['unit']);
            
            $info['cutoff_seconds'] = $seconds;
            $info['total_executions'] = intval($wpdb->get_var("SELECT COUNT(*) FROM {$executions_table}"));
            $info['logs_to_delete'] = self::get_logs_to_delete_count();
            
            // Get oldest completed execution
            $oldest = $wpdb->get_row(
                "SELECT id, status, started_at, completed_at, 
                        TIMESTAMPDIFF(SECOND, COALESCE(completed_at, started_at), NOW()) as age_seconds 
                 FROM {$executions_table} 
                 WHERE status != 'running'
                 ORDER BY COALESCE(completed_at, started_at) ASC 
                 LIMIT 1"
            );
            
            if ($oldest) {
                $info['oldest_execution'] = [
                    'id' => $oldest->id,
                    'status' => $oldest->status,
                    'started_at' => $oldest->started_at,
                    'completed_at' => $oldest->completed_at,
                    'age_seconds' => $oldest->age_seconds,
                ];
            }
            
            $info['running_executions'] = intval($wpdb->get_var("SELECT COUNT(*) FROM {$executions_table} WHERE status = 'running'"));
            $info['completed_executions'] = intval($wpdb->get_var("SELECT COUNT(*) FROM {$executions_table} WHERE status != 'running'"));
        }
        
        return $info;
    }
    
    /**
     * Get retention settings
     */
    public static function get_settings() {
        return [
            'enabled' => Settings_Manager::get('u43_log_retention_enabled', false),
            'duration' => intval(Settings_Manager::get('u43_log_retention_duration', 7)),
            'unit' => Settings_Manager::get('u43_log_retention_unit', 'day'),
        ];
    }
    
    /**
     * Update retention settings
     */
    public static function update_settings($settings) {
        $enabled = isset($settings['enabled']) ? (bool) $settings['enabled'] : false;
        $duration = max(1, intval($settings['duration'] ?? 7));
        $unit = in_array($settings['unit'] ?? 'day', ['minute', 'hour', 'day']) ? $settings['unit'] : 'day';
        
        Settings_Manager::set('u43_log_retention_enabled', $enabled, 'bool');
        Settings_Manager::set('u43_log_retention_duration', $duration, 'int');
        Settings_Manager::set('u43_log_retention_unit', $unit, 'string');
        
        self::schedule_cleanup();
        return true;
    }
    
    /**
     * Check if cleanup is enabled
     */
    private static function is_enabled() {
        return Settings_Manager::get('u43_log_retention_enabled', false);
    }
    
    /**
     * Convert duration and unit to seconds
     */
    private static function duration_to_seconds($duration, $unit) {
        $multipliers = [
            'minute' => 60,
            'hour' => 3600,
            'day' => 86400,
        ];
        
        return isset($multipliers[$unit]) ? $duration * $multipliers[$unit] : 0;
    }
}
