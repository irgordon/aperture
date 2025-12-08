<?php
namespace AperturePro\Database;

class Installer {
    public static function install() {
        self::create_tables();
        self::seed_email_templates();
        self::create_default_pages();
        self::update_db_version();
        
        $upload = wp_upload_dir();
        $dirs = ['aperture_contracts', 'aperture_proofs', 'aperture_temp', 'aperture_imports'];
        foreach($dirs as $d) {
            $path = $upload['basedir'] . '/' . $d;
            if(!file_exists($path)) mkdir($path, 0755, true);
        }
    }

    private static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = [];

        // Note: dbDelta requires 2 spaces after PRIMARY KEY
        
        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_contacts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(50) DEFAULT '',
            address text DEFAULT '',
            verification_token varchar(100),
            is_verified boolean DEFAULT 0,
            password_hash varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY email (email)
        ) $charset;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_leads (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            contact_id mediumint(9) NOT NULL,
            project_hash varchar(64),
            status varchar(50) DEFAULT 'new',
            source varchar(100) DEFAULT '',
            project_value decimal(10,2) DEFAULT '0.00',
            notes longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY contact_id (contact_id),
            KEY project_hash (project_hash)
        ) $charset;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_tasks (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            lead_id mediumint(9),
            description varchar(255) NOT NULL,
            is_completed boolean DEFAULT 0,
            due_date date,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY lead_id (lead_id)
        ) $charset;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_invoices (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            lead_id mediumint(9) NOT NULL,
            stripe_intent_id varchar(255) DEFAULT '',
            invoice_number varchar(50) NOT NULL,
            items_json longtext,
            amount decimal(10,2) NOT NULL,
            total_amount decimal(10,2) NOT NULL,
            currency varchar(3) DEFAULT 'USD',
            status varchar(20) DEFAULT 'unpaid',
            due_date date,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY lead_id (lead_id)
        ) $charset;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_contracts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            lead_id mediumint(9) NOT NULL,
            content longtext NOT NULL,
            client_signature longtext,
            admin_signature longtext,
            status varchar(20) DEFAULT 'draft',
            pdf_path varchar(255) DEFAULT '',
            signed_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY lead_id (lead_id)
        ) $charset;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_gallery_images (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            album_id mediumint(9) NOT NULL,
            lead_id mediumint(9),
            file_name varchar(255) NOT NULL,
            file_path varchar(255) NOT NULL,
            public_url varchar(255) NOT NULL,
            proof_id varchar(20),
            serial_number varchar(50),
            is_selected boolean DEFAULT 0,
            is_downloadable boolean DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY album_id (album_id)
        ) $charset;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_email_templates (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            slug varchar(50),
            name varchar(100),
            subject varchar(255),
            body longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) $charset;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_questionnaires (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            schema_json longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_responses (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            questionnaire_id mediumint(9) NOT NULL,
            answers_json longtext NOT NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_events (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            lead_id mediumint(9) NOT NULL,
            google_event_id varchar(255) DEFAULT '',
            start_time datetime NOT NULL,
            end_time datetime NOT NULL,
            location varchar(255) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY lead_id (lead_id)
        ) $charset;";

        foreach ( $sql as $query ) {
            dbDelta( $query );
        }
    }

    private static function seed_email_templates() {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_email_templates';
        if ( $wpdb->get_var("SELECT COUNT(*) FROM $table") == 0 ) {
            $wpdb->insert($table, ['slug'=>'new_inquiry', 'name'=>'New Inquiry', 'subject'=>'We received your inquiry!', 'body'=>"Dear {client_name},\n\nThank you for reaching out! We'll be in touch shortly."]);
            $wpdb->insert($table, ['slug'=>'photos_ready', 'name'=>'Photos Ready', 'subject'=>'Your Gallery is Ready', 'body'=>"Hi {client_name},\n\nView your photos here: {portal_link}"]);
            $wpdb->insert($table, ['slug'=>'invoice_reminder', 'name'=>'Invoice Reminder', 'subject'=>'Invoice Due', 'body'=>"Hi {client_name},\n\nPlease pay your invoice: {portal_link}"]);
        }
    }

    private static function create_default_pages() {
        $pages = [
            ['slug' => 'client-portal', 'title' => 'Client Portal', 'content' => '[aperture_client_portal]'],
            ['slug' => 'contact-us', 'title' => 'Contact Us', 'content' => '[aperture_contact_form]']
        ];
        foreach ($pages as $p) {
            if (!get_page_by_path($p['slug'])) {
                wp_insert_post(['post_title'=>$p['title'], 'post_name'=>$p['slug'], 'post_content'=>$p['content'], 'post_status'=>'publish', 'post_type'=>'page']);
            }
        }
    }

    private static function update_db_version() { update_option('aperture_pro_db_version', '2.0.0'); }
}
