<?php
namespace AperturePro\Admin\Pages;

class Settings {
    public static function render() {
        $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general';
        ?>
        <div class="wrap">
            <h1>Settings</h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=aperture-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">General</a>
                <a href="?page=aperture-settings&tab=branding" class="nav-tab <?php echo $active_tab == 'branding' ? 'nav-tab-active' : ''; ?>">Branding</a>
                <a href="?page=aperture-settings&tab=templates" class="nav-tab <?php echo $active_tab == 'templates' ? 'nav-tab-active' : ''; ?>">Templates</a>
                <a href="?page=aperture-settings&tab=automation" class="nav-tab <?php echo $active_tab == 'automation' ? 'nav-tab-active' : ''; ?>">Automation</a>
                <a href="?page=aperture-settings&tab=logs" class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?>">Logs</a>
                <a href="?page=aperture-settings&tab=system" class="nav-tab <?php echo $active_tab == 'system' ? 'nav-tab-active' : ''; ?>">System</a>
            </h2>

            <form method="post" action="">
                <?php wp_nonce_field('save_settings', 'aperture_settings_nonce'); ?>

                <?php if($active_tab == 'general'): ?>
                    <?php self::render_general(); ?>
                <?php elseif($active_tab == 'branding'): ?>
                    <?php self::render_branding(); ?>
                <?php elseif($active_tab == 'templates'): ?>
                    <?php self::render_templates(); ?>
                <?php elseif($active_tab == 'automation'): ?>
                    <?php self::render_automation(); ?>
                <?php elseif($active_tab == 'logs'): ?>
                    <?php self::render_logs(); ?>
                <?php elseif($active_tab == 'system'): ?>
                    <?php self::render_system(); ?>
                <?php endif; ?>

                <?php if($active_tab == 'general' || $active_tab == 'branding') submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private static function render_general() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['company_name'])) {
            check_admin_referer('save_settings', 'aperture_settings_nonce');

            update_option('aperture_company_name', sanitize_text_field($_POST['company_name']));
            update_option('aperture_logo_url', esc_url_raw($_POST['logo_url']));
            update_option('aperture_stripe_secret_key', sanitize_text_field($_POST['stripe_key']));
            update_option('aperture_stripe_public_key', sanitize_text_field($_POST['stripe_pub_key']));
            echo '<div class="notice notice-success"><p>Settings Saved.</p></div>';
        }
        $company_name = get_option('aperture_company_name');
        $logo_url = get_option('aperture_logo_url');
        $stripe_key = get_option('aperture_stripe_secret_key');
        $stripe_pub_key = get_option('aperture_stripe_public_key');
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="company_name">Company Name</label></th>
                <td><input type="text" name="company_name" value="<?php echo esc_attr($company_name); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="logo_url">Logo URL</label></th>
                <td><input type="url" name="logo_url" value="<?php echo esc_attr($logo_url); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="stripe_key">Stripe Secret Key</label></th>
                <td><input type="password" name="stripe_key" value="<?php echo esc_attr($stripe_key); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="stripe_pub_key">Stripe Publishable Key</label></th>
                <td><input type="text" name="stripe_pub_key" value="<?php echo esc_attr($stripe_pub_key); ?>" class="regular-text"></td>
            </tr>
        </table>
        <br><a href="<?php echo esc_url(rest_url('aperture/v1/export/all')); ?>" class="button button-secondary" target="_blank">Export All Data</a>
        <?php
    }

    private static function render_branding() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['brand_primary'])) {
            check_admin_referer('save_settings', 'aperture_settings_nonce');

            update_option('aperture_brand_primary', sanitize_hex_color($_POST['brand_primary']));
            update_option('aperture_brand_secondary', sanitize_hex_color($_POST['brand_secondary']));
            update_option('aperture_brand_bg', sanitize_hex_color($_POST['brand_bg']));
            echo '<div class="notice notice-success"><p>Branding Saved.</p></div>';
        }
        $p = get_option('aperture_brand_primary', '#14b8a6');
        $s = get_option('aperture_brand_secondary', '#0f766e');
        $b = get_option('aperture_brand_bg', '#f8fafc');
        ?>
        <table class="form-table">
            <tr><th>Primary Color</th><td><input type="color" name="brand_primary" value="<?php echo esc_attr($p); ?>"></td></tr>
            <tr><th>Secondary Color</th><td><input type="color" name="brand_secondary" value="<?php echo esc_attr($s); ?>"></td></tr>
            <tr><th>Background Color</th><td><input type="color" name="brand_bg" value="<?php echo esc_attr($b); ?>"></td></tr>
        </table>
        <?php
    }

    private static function render_templates() {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($action === 'edit' && $id) {
            self::render_template_editor($id);
        } else {
            self::render_template_list();
        }
    }

    private static function render_template_list() {
        $templates = \AperturePro\Utils\TemplateManager::get_templates();
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>Name</th><th>Subject</th><th>Actions</th></tr></thead><tbody>';
        foreach ($templates as $t) {
            $edit_link = admin_url('admin.php?page=aperture-settings&tab=templates&action=edit&id=' . $t->id);
            echo "<tr><td>" . esc_html($t->name) . "</td><td>" . esc_html($t->subject) . "</td>";
            echo "<td><a href='$edit_link' class='button'>Edit</a></td></tr>";
        }
        echo '</tbody></table>';
    }

    private static function render_template_editor($id) {
        $template = \AperturePro\Utils\TemplateManager::get_template($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['template_subject'])) {
            check_admin_referer('save_settings', 'aperture_settings_nonce');

            if (isset($_POST['rollback_version'])) {
                 \AperturePro\Utils\TemplateManager::rollback(intval($_POST['rollback_version']));
                 echo '<div class="notice notice-success"><p>Rolled back successfully.</p></div>';
                 $template = \AperturePro\Utils\TemplateManager::get_template($id); // Reload
            } else {
                 \AperturePro\Utils\TemplateManager::update_template($id, sanitize_text_field($_POST['template_subject']), wp_kses_post($_POST['template_body']));
                 echo '<div class="notice notice-success"><p>Template Updated.</p></div>';
                 $template = \AperturePro\Utils\TemplateManager::get_template($id); // Reload
            }
        }

        $versions = \AperturePro\Utils\TemplateManager::get_versions($id);
        ?>
        <h3>Edit Template: <?php echo esc_html($template->name); ?></h3>
        <p><a href="?page=aperture-settings&tab=templates">Back to list</a></p>

        <div style="display: flex; gap: 20px;">
            <div style="flex: 2;">
                <table class="form-table">
                    <tr><th>Subject</th><td><input type="text" name="template_subject" value="<?php echo esc_attr($template->subject); ?>" class="regular-text" style="width: 100%;"></td></tr>
                    <tr><th>Body</th><td><textarea name="template_body" rows="15" style="width: 100%;"><?php echo esc_textarea($template->body); ?></textarea>
                    <p class="description">Available vars: {client_name}, {portal_link}, {invoice_number}, etc.</p></td></tr>
                </table>
                <?php submit_button('Save Changes'); ?>
            </div>

            <div style="flex: 1; background: #fff; padding: 15px; border: 1px solid #ccd0d4;">
                <h4>Version History</h4>
                <?php if (empty($versions)): ?>
                    <p>No previous versions.</p>
                <?php else: ?>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach($versions as $v): ?>
                        <li style="border-bottom: 1px solid #eee; padding: 8px 0;">
                            <strong><?php echo $v->created_at; ?></strong><br>
                            <span style="color: #666; font-size: 12px;"><?php echo esc_html(substr($v->subject, 0, 30)); ?>...</span>
                            <br>
                            <button type="submit" name="rollback_version" value="<?php echo $v->id; ?>" class="button-link" onclick="return confirm('Revert to this version?');">Rollback</button>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private static function render_automation() {
        ?>
        <h3>Automated Email Triggers</h3>
        <p>The following events trigger automated emails:</p>
        <ul style="list-style: disc; margin-left: 20px;">
            <li><strong>Payment Failed</strong>: Sends an apology/retry link to client.</li>
            <li><strong>Gate Locked</strong>: Notifies client of pending items when they try to access restricted gallery.</li>
            <li><strong>Invoice Reminder</strong>: Sends reminder for due invoices.</li>
            <li><strong>Gallery Expiry</strong>: Warns client before gallery access ends.</li>
            <li><strong>Zip Ready</strong>: Notifies client when full download is built.</li>
        </ul>
        <?php
    }

    private static function render_logs() {
        $filter = isset($_GET['log_type']) ? sanitize_text_field($_GET['log_type']) : '';
        $logs = \AperturePro\Utils\Logger::get_logs(50, $filter);

        ?>
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="log_type_filter" onchange="window.location.href='?page=aperture-settings&tab=logs&log_type='+this.value">
                    <option value="">All Types</option>
                    <option value="error" <?php selected($filter, 'error'); ?>>Errors</option>
                    <option value="email_sent" <?php selected($filter, 'email_sent'); ?>>Emails</option>
                    <option value="gate_locked" <?php selected($filter, 'gate_locked'); ?>>Access Denied</option>
                </select>
                <!-- Export CSV Button (Simple Link) -->
                <a href="<?php echo esc_url(rest_url('aperture/v1/export/logs')); ?>" class="button" target="_blank">Export CSV</a>
            </div>
        </div>

        <table class="widefat fixed striped">
            <thead><tr><th>Date</th><th>Type</th><th>Message</th><th>User</th><th>IP</th><th>Context</th></tr></thead>
            <tbody>
                <?php if(empty($logs)): ?>
                    <tr><td colspan="6">No logs found.</td></tr>
                <?php else: ?>
                    <?php foreach($logs as $log): ?>
                    <tr>
                        <td><?php echo $log->created_at; ?></td>
                        <td><?php echo esc_html($log->type); ?></td>
                        <td><?php echo esc_html($log->message); ?></td>
                        <td><?php echo $log->user_id ? get_userdata($log->user_id)->user_login : '-'; ?></td>
                        <td><?php echo esc_html($log->ip_address); ?></td>
                        <td><pre style="white-space: pre-wrap; font-size: 10px;"><?php echo esc_html($log->context_json); ?></pre></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    private static function render_system() {
        // Handle Actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['system_action'])) {
            check_admin_referer('save_settings', 'aperture_settings_nonce');
            $action = $_POST['system_action'];

            if ($action === 'retry_job') {
                \AperturePro\Utils\Queue::retry(intval($_POST['job_id']));
                echo '<div class="notice notice-success"><p>Job requeued.</p></div>';
            } elseif ($action === 'cancel_job') {
                \AperturePro\Utils\Queue::cancel(intval($_POST['job_id']));
                echo '<div class="notice notice-warning"><p>Job cancelled.</p></div>';
            } elseif ($action === 'restore_settings') {
                // Simplified Restore: Accept JSON paste
                $json = stripslashes($_POST['restore_json']);
                $data = json_decode($json, true);
                if ($data) {
                    if(isset($data['options'])) {
                        foreach($data['options'] as $key => $val) update_option($key, $val);
                    }
                    echo '<div class="notice notice-success"><p>Settings restored.</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>Invalid JSON.</p></div>';
                }
            }
        }

        $jobs = \AperturePro\Utils\Queue::get_jobs('', 20);
        $stats = \AperturePro\Utils\Queue::get_stats();

        // Prepare Backup Data (Options only for now)
        $backup_data = [
            'options' => [
                'aperture_company_name' => get_option('aperture_company_name'),
                'aperture_brand_primary' => get_option('aperture_brand_primary'),
                // ... others
            ]
        ];
        $backup_json = json_encode($backup_data, JSON_PRETTY_PRINT);
        ?>

        <h3>Job Queue</h3>
        <div style="display:flex; gap:10px; margin-bottom:20px;">
            <?php foreach($stats as $s): ?>
                <div class="card">
                    <strong><?php echo ucfirst($s['status']); ?></strong>: <?php echo $s['count']; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <table class="widefat fixed striped">
            <thead><tr><th>ID</th><th>Type</th><th>Status</th><th>Attempts</th><th>Last Attempt</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if(empty($jobs)): ?>
                    <tr><td colspan="6">Queue is empty.</td></tr>
                <?php else: ?>
                    <?php foreach($jobs as $job): ?>
                    <tr>
                        <td><?php echo $job->id; ?></td>
                        <td><?php echo esc_html($job->type); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $job->status; ?>"><?php echo $job->status; ?></span>
                        </td>
                        <td><?php echo $job->attempts; ?></td>
                        <td><?php echo $job->last_attempt; ?></td>
                        <td>
                            <?php if($job->status === 'failed' || $job->status === 'cancelled'): ?>
                                <button type="submit" name="system_action" value="retry_job" class="button-small">Retry</button>
                                <input type="hidden" name="job_id" value="<?php echo $job->id; ?>">
                            <?php elseif($job->status === 'pending'): ?>
                                <button type="submit" name="system_action" value="cancel_job" class="button-small">Cancel</button>
                                <input type="hidden" name="job_id" value="<?php echo $job->id; ?>">
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <hr>
        <h3>Backup & Restore</h3>
        <div style="display:flex; gap:20px;">
            <div style="flex:1;">
                <h4>Backup Configuration</h4>
                <p>Copy this JSON to save your settings.</p>
                <textarea class="large-text code" rows="5" readonly><?php echo esc_textarea($backup_json); ?></textarea>
            </div>
            <div style="flex:1;">
                <h4>Restore Configuration</h4>
                <p>Paste JSON here to restore settings.</p>
                <textarea name="restore_json" class="large-text code" rows="5"></textarea>
                <button type="submit" name="system_action" value="restore_settings" class="button button-primary" onclick="return confirm('Overwrite settings?');">Restore</button>
            </div>
        </div>
        <?php
    }
}
