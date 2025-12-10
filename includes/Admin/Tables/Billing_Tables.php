<?php
namespace AperturePro\Admin\Tables;

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Invoices_Table extends \WP_List_Table {
    function get_columns(){
        return ['invoice_number'=>'Number', 'amount'=>'Amount', 'status'=>'Status', 'due_date'=>'Due Date'];
    }
    function prepare_items() {
        global $wpdb;
        $this->_column_headers = [$this->get_columns(), [], []];
        $this->items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_invoices ORDER BY created_at DESC", ARRAY_A);
    }
    function column_default($item, $col){ return $item[$col]; }
}

class Contracts_Table extends \WP_List_Table {
    function get_columns(){
        return ['id'=>'ID', 'status'=>'Status', 'created_at'=>'Date Created', 'actions'=>'Actions'];
    }
    function prepare_items() {
        global $wpdb;
        $this->_column_headers = [$this->get_columns(), [], []];
        $this->items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_contracts ORDER BY created_at DESC", ARRAY_A);
    }
    function column_default($item, $col){ return $item[$col]; }
    function column_actions($item) {
        $edit_link = add_query_arg(['page'=>'aperture-billing', 'action'=>'edit_contract', 'id'=>$item['id']], admin_url('admin.php'));
        return "<a href='$edit_link'>Edit</a>";
    }
}
