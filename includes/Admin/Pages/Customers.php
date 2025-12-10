<?php
namespace AperturePro\Admin\Pages;

class Customers {
    public static function render() {
        require_once plugin_dir_path(__DIR__) . 'Tables/Customers_Table.php';
        $table = new \AperturePro\Admin\Tables\Customers_Table();
        $table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Customers</h1>
            <a href="#" class="page-title-action">Add New</a>
            <hr class="wp-header-end">
            <form method="post">
                <?php $table->display(); ?>
            </form>
        </div>
        <?php
    }
}
