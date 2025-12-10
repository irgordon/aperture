<?php
namespace AperturePro\Utils;

class Logger {
    public static function log($type, $message, $context = []) {
        global $wpdb;
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';

        $wpdb->insert("{$wpdb->prefix}ap_logs", [
            'type' => $type,
            'message' => $message,
            'context_json' => json_encode($context),
            'user_id' => get_current_user_id(),
            'ip_address' => $ip
        ]);

        // Trigger hook for external integrations
        do_action('ap_log_event', $type, $message, $context, $ip);
    }

    public static function get_logs($limit = 50, $type = '') {
        global $wpdb;
        $query = "SELECT * FROM {$wpdb->prefix}ap_logs";
        if (!empty($type)) {
            $query .= $wpdb->prepare(" WHERE type = %s", $type);
        }
        $query .= " ORDER BY created_at DESC LIMIT %d";

        return $wpdb->get_results($wpdb->prepare($query, $limit));
    }
}
