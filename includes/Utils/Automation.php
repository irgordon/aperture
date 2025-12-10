<?php
namespace AperturePro\Utils;

class Automation {
    public static function trigger($event, $data = []) {
        global $wpdb;

        // Log the event
        Logger::log('automation_trigger', "Event triggered: $event", $data);

        // Map events to email templates
        $template_map = [
            'payment_failed' => 'payment_failed',
            'gate_locked' => 'gate_locked',
            'invoice_due' => 'invoice_reminder'
        ];

        if (isset($template_map[$event])) {
            $slug = $template_map[$event];

            // Check if automation is enabled for this event (could be added to settings later)
            // For now, assume always on for core critical emails

            if (isset($data['email'])) {
                $email = $data['email'];
                TemplateMailer::send($slug, $email, $data);
                Logger::log('email_sent', "Sent email '$slug' to $email");
            }
        }
    }
}
