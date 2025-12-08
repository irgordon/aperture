<?php
namespace AperturePro\Database;

class Installer {
    public static function install() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = [
            "CREATE TABLE {$wpdb->prefix}ap_contacts (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                first_name varchar(100), last_name varchar(100),
                email varchar(100), phone varchar(50), address text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id), KEY email (email)
            ) $charset;",
            
            "CREATE TABLE {$wpdb->prefix}ap_leads (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                contact_id mediumint(9), status varchar(50) DEFAULT 'new',
                project_value decimal(10,2), notes longtext,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id), KEY contact_id (contact_id)
            ) $charset;",

            "CREATE TABLE {$wpdb->prefix}ap_invoices (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                lead_id mediumint(9), stripe_intent_id varchar(255),
                invoice_number varchar(50), amount decimal(10,2),
                status varchar(20) DEFAULT 'unpaid', due_date date,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset;",

            "CREATE TABLE {$wpdb->prefix}ap_gallery_images (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                album_id mediumint(9), file_name varchar(255),
                file_path varchar(255), public_url varchar(255),
                serial_number varchar(50), is_selected boolean DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id), KEY album_id (album_id)
            ) $charset;",

            "CREATE TABLE {$wpdb->prefix}ap_questionnaires (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                title varchar(255), schema_json longtext,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset;",

            "CREATE TABLE {$wpdb->prefix}ap_responses (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                questionnaire_id mediumint(9), answers_json longtext,
                submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset;"
        ];

        foreach ( $sql as $query ) dbDelta( $query );
    }
}
