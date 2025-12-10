<?php
namespace AperturePro\Admin\Pages;

class Settings {
    public static function render() {
        if (isset($_POST['aperture_settings_nonce']) && wp_verify_nonce($_POST['aperture_settings_nonce'], 'save_settings')) {
            update_option('aperture_company_name', sanitize_text_field($_POST['company_name']));
            update_option('aperture_stripe_secret_key', sanitize_text_field($_POST['stripe_key']));
            update_option('aperture_stripe_public_key', sanitize_text_field($_POST['stripe_pub_key']));
            update_option('aperture_logo_url', esc_url_raw($_POST['logo_url']));
            echo '<div class="notice notice-success"><p>Settings Saved.</p></div>';
        }

        $company_name = get_option('aperture_company_name');
        $stripe_key = get_option('aperture_stripe_secret_key');
        $stripe_pub_key = get_option('aperture_stripe_public_key');
        $logo_url = get_option('aperture_logo_url');
        ?>
        <div class="wrap">
            <h1>Settings</h1>
            <form method="post" action="">
                <?php wp_nonce_field('save_settings', 'aperture_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="company_name">Company Name</label></th>
                        <td><input type="text" name="company_name" id="company_name" value="<?php echo esc_attr($company_name); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="logo_url">Logo URL</label></th>
                        <td><input type="url" name="logo_url" id="logo_url" value="<?php echo esc_attr($logo_url); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="stripe_key">Stripe Secret Key</label></th>
                        <td><input type="password" name="stripe_key" id="stripe_key" value="<?php echo esc_attr($stripe_key); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="stripe_pub_key">Stripe Publishable Key</label></th>
                        <td><input type="text" name="stripe_pub_key" id="stripe_pub_key" value="<?php echo esc_attr($stripe_pub_key); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <hr>
            <h2>Export Data</h2>
            <p>Download a CSV of all your data.</p>
            <a href="<?php echo esc_url(rest_url('aperture/v1/export/all')); ?>" class="button button-secondary" target="_blank">Export All Data</a>
        </div>
        <?php
    }
}
