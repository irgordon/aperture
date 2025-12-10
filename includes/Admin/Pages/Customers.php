<?php
namespace AperturePro\Admin\Pages;

class Customers {
    public static function render() {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($action === 'new' || ($action === 'edit' && $id)) {
            self::render_form($id);
        } else {
            self::render_list();
        }
    }

    private static function render_list() {
        require_once plugin_dir_path(__DIR__) . 'Tables/Customers_Table.php';
        $table = new \AperturePro\Admin\Tables\Customers_Table();
        $table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Customers</h1>
            <a href="<?php echo admin_url('admin.php?page=aperture-customers&action=new'); ?>" class="page-title-action">Add New</a>
            <hr class="wp-header-end">
            <form method="post">
                <?php $table->display(); ?>
            </form>
        </div>
        <?php
    }

    private static function render_form($id) {
        global $wpdb;
        $customer = null;
        if ($id) {
            $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_contacts WHERE id = %d", $id));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('save_customer')) {
            $data = [
                'first_name' => sanitize_text_field($_POST['first_name']),
                'last_name' => sanitize_text_field($_POST['last_name']),
                'email' => sanitize_email($_POST['email']),
                'phone' => sanitize_text_field($_POST['phone']),
                'address' => sanitize_textarea_field($_POST['address'])
            ];

            if ($id) {
                $wpdb->update("{$wpdb->prefix}ap_contacts", $data, ['id' => $id]);
                echo '<div class="notice notice-success"><p>Customer updated.</p></div>';
            } else {
                $wpdb->insert("{$wpdb->prefix}ap_contacts", $data);
                $id = $wpdb->insert_id;
                echo '<div class="notice notice-success"><p>Customer added.</p></div>';
                $customer = (object) $data; // Populate for display
            }
        }

        // Populate fields
        $fname = $customer ? $customer->first_name : '';
        $lname = $customer ? $customer->last_name : '';
        $email = $customer ? $customer->email : '';
        $phone = $customer ? $customer->phone : '';
        $addr  = $customer ? $customer->address : '';

        ?>
        <div class="wrap">
            <h1><?php echo $id ? 'Edit Customer' : 'Add New Customer'; ?></h1>
            <form method="post">
                <?php wp_nonce_field('save_customer'); ?>
                <table class="form-table">
                    <tr><th>First Name</th><td><input type="text" name="first_name" value="<?php echo esc_attr($fname); ?>" class="regular-text" required></td></tr>
                    <tr><th>Last Name</th><td><input type="text" name="last_name" value="<?php echo esc_attr($lname); ?>" class="regular-text" required></td></tr>
                    <tr><th>Email</th><td><input type="email" name="email" value="<?php echo esc_attr($email); ?>" class="regular-text" required></td></tr>
                    <tr><th>Phone</th><td><input type="text" name="phone" value="<?php echo esc_attr($phone); ?>" class="regular-text"></td></tr>
                    <tr><th>Address</th><td><textarea name="address" rows="3" class="large-text"><?php echo esc_textarea($addr); ?></textarea></td></tr>
                </table>
                <?php submit_button($id ? 'Update Customer' : 'Add Customer'); ?>
                <a href="?page=aperture-customers">Cancel</a>
            </form>
        </div>
        <?php
    }
}
