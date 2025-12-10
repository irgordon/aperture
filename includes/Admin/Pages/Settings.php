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
                <a href="?page=aperture-settings&tab=automation" class="nav-tab <?php echo $active_tab == 'automation' ? 'nav-tab-active' : ''; ?>">Automation</a>
                <a href="?page=aperture-settings&tab=logs" class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?>">Logs</a>
            </h2>

            <form method="post" action="">
                <?php wp_nonce_field('save_settings', 'aperture_settings_nonce'); ?>

                <?php if($active_tab == 'general'): ?>
                    <?php self::render_general(); ?>
                <?php elseif($active_tab == 'branding'): ?>
                    <?php self::render_branding(); ?>
                <?php elseif($active_tab == 'automation'): ?>
                    <?php self::render_automation(); ?>
                <?php elseif($active_tab == 'logs'): ?>
                    <?php self::render_logs(); ?>
                <?php endif; ?>

                <?php if($active_tab != 'logs' && $active_tab != 'automation') submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private static function render_general() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['company_name'])) {
            check_admin_referer('save_settings', 'aperture_settings_nonce'); // CSRF Protection

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
            check_admin_referer('save_settings', 'aperture_settings_nonce'); // CSRF Protection

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

    private static function render_automation() {
        ?>
        <h3>Automated Email Triggers</h3>
        <p>The following events trigger automated emails:</p>
        <ul style="list-style: disc; margin-left: 20px;">
            <li><strong>Payment Failed</strong>: Sends an apology/retry link to client.</li>
            <li><strong>Gate Locked</strong>: Notifies client of pending items when they try to access restricted gallery.</li>
            <li><strong>Invoice Reminder</strong>: Sends reminder for due invoices.</li>
        </ul>
        <p><em>(Advanced configuration coming in next update)</em></p>
        <?php
    }

    private static function render_logs() {
        $logs = \AperturePro\Utils\Logger::get_logs(20);
        ?>
        <table class="widefat fixed striped">
            <thead><tr><th>Date</th><th>Type</th><th>Message</th><th>Context</th></tr></thead>
            <tbody>
                <?php if(empty($logs)): ?>
                    <tr><td colspan="4">No logs found.</td></tr>
                <?php else: ?>
                    <?php foreach($logs as $log): ?>
                    <tr>
                        <td><?php echo $log->created_at; ?></td>
                        <td><?php echo esc_html($log->type); ?></td>
                        <td><?php echo esc_html($log->message); ?></td>
                        <td><pre style="white-space: pre-wrap; font-size: 10px;"><?php echo esc_html($log->context_json); ?></pre></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
}
