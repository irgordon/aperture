<?php
namespace AperturePro\Admin\Tables;

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Customers_Table extends \WP_List_Table {
    function get_columns(){
        return [
            'cb' => '<input type="checkbox" />',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'created_at' => 'Date Added'
        ];
    }

    function prepare_items() {
        global $wpdb;
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = ['created_at' => ['created_at', false]];
        $this->_column_headers = [$columns, $hidden, $sortable];

        $data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_contacts ORDER BY created_at DESC", ARRAY_A);
        $this->items = $data;
    }

    function column_default($item, $column_name){
        return $item[$column_name];
    }

    function column_cb($item) {
        return sprintf('<input type="checkbox" name="customer[]" value="%s" />', $item['id']);
    }
}
