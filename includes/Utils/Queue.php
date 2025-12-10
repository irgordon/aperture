<?php
namespace AperturePro\Utils;

class Queue {
    public static function push($type, $payload) {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}ap_job_queue", [
            'type' => $type,
            'payload_json' => json_encode($payload),
            'status' => 'pending'
        ]);
    }

    public static function process() {
        global $wpdb;
        $jobs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_job_queue WHERE status = 'pending' LIMIT 5");

        foreach ($jobs as $job) {
            $payload = json_decode($job->payload_json, true);
            $success = false;

            try {
                switch ($job->type) {
                    case 'send_email':
                        $success = TemplateMailer::send($payload['slug'], $payload['to'], $payload['data']);
                        break;
                    case 'build_zip':
                        $success = self::build_zip($payload['lead_id']);
                        break;
                }
            } catch (\Exception $e) {
                Logger::log('queue_error', $e->getMessage(), ['job_id' => $job->id]);
            }

            if ($success) {
                $wpdb->update("{$wpdb->prefix}ap_job_queue", ['status' => 'completed'], ['id' => $job->id]);
            } else {
                $attempts = $job->attempts + 1;
                $status = $attempts >= 3 ? 'failed' : 'pending';
                $wpdb->update("{$wpdb->prefix}ap_job_queue",
                    ['status' => $status, 'attempts' => $attempts, 'last_attempt' => current_time('mysql')],
                    ['id' => $job->id]
                );
            }
        }
    }

    private static function build_zip($lead_id) {
        global $wpdb;
        $images = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_gallery_images WHERE lead_id = %d AND status != 'rejected'", $lead_id)); // Include pending/approved

        if (empty($images)) return false;

        $upload_dir = wp_upload_dir();
        $zip_dir = $upload_dir['basedir'] . '/aperture_deliveries';
        if (!file_exists($zip_dir)) mkdir($zip_dir, 0755, true);

        $zip_name = "project_{$lead_id}_" . md5(time()) . ".zip";
        $zip_path = $zip_dir . '/' . $zip_name;

        $zip = new \ZipArchive();
        if ($zip->open($zip_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            return false;
        }

        foreach ($images as $img) {
            if (file_exists($img->file_path)) {
                $zip->addFile($img->file_path, $img->file_name);
            }
        }
        $zip->close();

        // Update lead
        $wpdb->update("{$wpdb->prefix}ap_leads",
            ['zip_path' => $zip_path, 'is_zip_ready' => 1],
            ['id' => $lead_id]
        );

        // Notify Client
        $lead = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_leads WHERE id = %d", $lead_id));
        $contact = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_contacts WHERE id = %d", $lead->contact_id));

        if ($contact) {
            TemplateMailer::send('zip_ready', $contact->email, [
                'client_name' => $contact->first_name,
                'download_link' => content_url("/uploads/aperture_deliveries/" . $zip_name) // Simple link, ideally guarded route
            ]);
        }

        return true;
    }
}
