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

    public static function cancel($id) {
        global $wpdb;
        return $wpdb->update("{$wpdb->prefix}ap_job_queue", ['status' => 'cancelled'], ['id' => $id]);
    }

    public static function retry($id) {
        global $wpdb;
        return $wpdb->update("{$wpdb->prefix}ap_job_queue",
            ['status' => 'pending', 'attempts' => 0, 'last_attempt' => null],
            ['id' => $id]
        );
    }

    public static function get_stats() {
        global $wpdb;
        return $wpdb->get_results("SELECT status, COUNT(*) as count FROM {$wpdb->prefix}ap_job_queue GROUP BY status", ARRAY_A);
    }

    public static function get_jobs($status = '', $limit = 20) {
        global $wpdb;
        $query = "SELECT * FROM {$wpdb->prefix}ap_job_queue";
        if ($status) $query .= $wpdb->prepare(" WHERE status = %s", $status);
        $query .= " ORDER BY created_at DESC LIMIT %d";
        return $wpdb->get_results($wpdb->prepare($query, $limit));
    }

    public static function process() {
        global $wpdb;

        // Find jobs that are pending OR failed (for manual retry trigger logic if needed, but primarily pending)
        // We use FOR UPDATE SKIP LOCKED if DB supports it, but for WP simple logic:
        // Update status to 'processing' atomically first.

        // 1. Get candidate IDs
        $candidates = $wpdb->get_results("SELECT id, attempts, last_attempt FROM {$wpdb->prefix}ap_job_queue WHERE status = 'pending' LIMIT 5");

        foreach ($candidates as $candidate) {
            // Backoff Check
            if ($candidate->attempts > 0) {
                $wait_time = pow(2, $candidate->attempts) * 60;
                if (strtotime($candidate->last_attempt) + $wait_time > time()) {
                    continue;
                }
            }

            // Atomic Lock: Try to update status to 'processing' where status is still 'pending'
            // This prevents race conditions
            $locked = $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}ap_job_queue SET status = 'processing', last_attempt = %s WHERE id = %d AND status = 'pending'",
                current_time('mysql'),
                $candidate->id
            ));

            if (!$locked) continue; // Another process grabbed it

            // 2. Fetch full job data
            $job = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_job_queue WHERE id = %d", $candidate->id));
            if (!$job) continue;

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
                $status = $attempts >= 5 ? 'failed' : 'pending';
                $wpdb->update("{$wpdb->prefix}ap_job_queue",
                    ['status' => $status, 'attempts' => $attempts], // last_attempt already set on lock, but update implies new attempt finished
                    ['id' => $job->id]
                );
            }
        }
    }

    private static function build_zip($lead_id) {
        global $wpdb;
        $images = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_gallery_images WHERE lead_id = %d AND status != 'rejected'", $lead_id));

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

        $wpdb->update("{$wpdb->prefix}ap_leads",
            ['zip_path' => $zip_path, 'is_zip_ready' => 1],
            ['id' => $lead_id]
        );

        $lead = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_leads WHERE id = %d", $lead_id));
        $contact = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_contacts WHERE id = %d", $lead->contact_id));

        if ($contact) {
            TemplateMailer::send('zip_ready', $contact->email, [
                'client_name' => $contact->first_name,
                'download_link' => content_url("/uploads/aperture_deliveries/" . $zip_name)
            ]);
        }

        return true;
    }
}
