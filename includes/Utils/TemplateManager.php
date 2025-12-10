<?php
namespace AperturePro\Utils;

class TemplateManager {
    public static function get_templates() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_email_templates");
    }

    public static function get_template($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_email_templates WHERE id = %d", $id));
    }

    public static function update_template($id, $subject, $body) {
        global $wpdb;
        $template = self::get_template($id);
        if (!$template) return false;

        // Save version
        $wpdb->insert("{$wpdb->prefix}ap_email_template_versions", [
            'template_id' => $id,
            'subject' => $template->subject,
            'body' => $template->body,
            'created_by' => get_current_user_id()
        ]);

        return $wpdb->update("{$wpdb->prefix}ap_email_templates",
            ['subject' => $subject, 'body' => $body],
            ['id' => $id]
        );
    }

    public static function get_versions($template_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ap_email_template_versions WHERE template_id = %d ORDER BY created_at DESC",
            $template_id
        ));
    }

    public static function rollback($version_id) {
        global $wpdb;
        $version = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_email_template_versions WHERE id = %d", $version_id));
        if (!$version) return false;

        // We do NOT save the current state as a version when rolling back,
        // OR we could. Let's stick to simple overwrite for rollback,
        // effectively making "Undo" possible by rolling back to the previous one again.
        // Actually, safer to save current as a version before rollback so we don't lose the "bad" edit (audit trail).
        $current = self::get_template($version->template_id);
        $wpdb->insert("{$wpdb->prefix}ap_email_template_versions", [
            'template_id' => $version->template_id,
            'subject' => $current->subject,
            'body' => $current->body,
            'created_by' => get_current_user_id()
        ]);

        return $wpdb->update("{$wpdb->prefix}ap_email_templates",
            ['subject' => $version->subject, 'body' => $version->body],
            ['id' => $version->template_id]
        );
    }
}
