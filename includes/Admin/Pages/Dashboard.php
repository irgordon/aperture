<?php
namespace AperturePro\Admin\Pages;

class Dashboard {
    public static function render() {
        global $wpdb;
        $leads_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ap_leads");
        $invoices_due = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ap_invoices WHERE status = 'unpaid'");
        $revenue = $wpdb->get_var("SELECT SUM(amount_paid) FROM {$wpdb->prefix}ap_invoices");

        ?>
        <div class="wrap">
            <h1>AperturePro Dashboard</h1>
            <div class="ap-dashboard-widgets" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                <div class="card">
                    <h2>Active Projects</h2>
                    <p style="font-size: 2em; margin: 10px 0;"><?php echo intval($leads_count); ?></p>
                </div>
                <div class="card">
                    <h2>Invoices Due</h2>
                    <p style="font-size: 2em; margin: 10px 0; color: #d63638;"><?php echo intval($invoices_due); ?></p>
                </div>
                <div class="card">
                    <h2>Total Revenue</h2>
                    <p style="font-size: 2em; margin: 10px 0; color: #00a32a;"><?php echo '$' . number_format((float)$revenue, 2); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
}
