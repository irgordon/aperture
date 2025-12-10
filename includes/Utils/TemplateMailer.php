<?php
namespace AperturePro\Utils;

class TemplateMailer {
    public static function send( $slug, $recipient_email, $data = [] ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_email_templates';
        
        $template = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE slug = %s", $slug ) );
        if ( ! $template ) {
            Logger::log('mail_error', "Template not found: $slug");
            return false;
        }

        $defaults = [
            '{company_name}' => get_option('aperture_company_name', 'AperturePro'),
            '{portal_link}'  => site_url('/client-portal'),
        ];
        
        $merge_vars = array_merge( $defaults, $data );
        $subject = str_replace( array_keys($merge_vars), array_values($merge_vars), $template->subject );
        $body    = str_replace( array_keys($merge_vars), array_values($merge_vars), $template->body );

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('aperture_company_name') . ' <' . get_option('admin_email') . '>'
        ];

        $html_body = "<div style='font-family: sans-serif; padding: 20px; line-height: 1.5; color: #333;'>" . nl2br($body) . "</div>";

        $result = wp_mail( $recipient_email, $subject, $html_body, $headers );

        if (!$result) {
            Logger::log('mail_failed', "Failed to send $slug to $recipient_email");
            // Optionally push to queue if not already being processed from queue
            // But be careful of infinite loops if this called FROM Queue::process
            return false;
        }

        return true;
    }
}
