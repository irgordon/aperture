<?php
namespace AperturePro\Utils;

class Logger {
    public static function log($type, $message, $context = []) {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}ap_logs", [
            'type' => $type,
            'message' => $message,
            'context_json' => json_encode($context),
            'user_id' => get_current_user_id()
        ]);
    }

    public static function get_logs($limit = 50) {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_logs ORDER BY created_at DESC LIMIT $limit");
    }
}
