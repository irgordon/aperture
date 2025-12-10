<?php
namespace AperturePro\Admin\Pages;

class Billing {
    public static function render() {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';

        // Handle Contract Editor
        if ($action == 'edit_contract') {
            self::render_contract_editor();
            return;
        }

        // Handle Invoice Editor (Placeholder/Basic Form)
        if ($action == 'new_invoice') {
            self::render_invoice_editor();
            return;
        }

        require_once plugin_dir_path(__DIR__) . 'Tables/Billing_Tables.php';
        $invoices_table = new \AperturePro\Admin\Tables\Invoices_Table();
        $contracts_table = new \AperturePro\Admin\Tables\Contracts_Table();
        $invoices_table->prepare_items();
        $contracts_table->prepare_items();
        ?>
        <div class="wrap">
            <h1>Billing & Contracts</h1>

            <h2>Invoices <a href="<?php echo admin_url('admin.php?page=aperture-billing&action=new_invoice'); ?>" class="page-title-action">Add New</a></h2>
            <?php $invoices_table->display(); ?>

            <br><hr><br>

            <h2>Contracts <a href="<?php echo admin_url('admin.php?page=aperture-billing&action=edit_contract'); ?>" class="page-title-action">Add New</a></h2>
            <?php $contracts_table->display(); ?>
        </div>
        <?php
    }

    private static function render_contract_editor() {
        global $wpdb;
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $content = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('save_contract')) {
            $content = wp_kses_post($_POST['contract_content']);
            if ($id) {
                $wpdb->update("{$wpdb->prefix}ap_contracts", ['content' => $content], ['id' => $id]);
            } else {
                $wpdb->insert("{$wpdb->prefix}ap_contracts", ['content' => $content, 'status' => 'draft']);
                $id = $wpdb->insert_id;
            }
            echo '<div class="notice notice-success"><p>Contract Saved.</p></div>';
        } elseif ($id) {
            $contract = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ap_contracts WHERE id = $id");
            if($contract) $content = $contract->content;
        }

        ?>
        <div class="wrap">
            <h1><?php echo $id ? 'Edit Contract' : 'New Contract'; ?></h1>
            <form method="post">
                <?php wp_nonce_field('save_contract'); ?>
                <?php wp_editor($content, 'contract_content'); ?>
                <br>
                <?php submit_button('Save Contract'); ?>
                <a href="?page=aperture-billing">Back</a>
            </form>
        </div>
        <?php
    }

    private static function render_invoice_editor() {
        // Basic Invoice Form for completeness
        global $wpdb;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('save_invoice')) {
            $amount = floatval($_POST['amount']);
            $wpdb->insert("{$wpdb->prefix}ap_invoices", [
                'amount' => $amount,
                'total_amount' => $amount,
                'status' => 'unpaid',
                'invoice_number' => 'INV-' . time(),
                'due_date' => sanitize_text_field($_POST['due_date'])
            ]);
            echo '<div class="notice notice-success"><p>Invoice Created.</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>New Invoice</h1>
            <form method="post">
                <?php wp_nonce_field('save_invoice'); ?>
                <table class="form-table">
                    <tr><th>Amount ($)</th><td><input type="number" step="0.01" name="amount" required></td></tr>
                    <tr><th>Due Date</th><td><input type="date" name="due_date" required></td></tr>
                </table>
                <?php submit_button('Create Invoice'); ?>
                <a href="?page=aperture-billing">Back</a>
            </form>
        </div>
        <?php
    }
}
