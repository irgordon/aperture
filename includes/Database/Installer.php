<?php
namespace AperturePro\Database;

class Installer {

    public static function install() {
        self::create_tables();
        self::seed_email_templates();
        self::create_default_pages();
        self::update_db_version();
    }

    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = [];

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
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY email (email)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_leads (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            contact_id mediumint(9) NOT NULL,
            project_hash varchar(64) NOT NULL UNIQUE,
            status varchar(50) DEFAULT 'new',
            source varchar(100) DEFAULT '',
            project_value decimal(10,2) DEFAULT '0.00',
            notes longtext DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY contact_id (contact_id),
            KEY project_hash (project_hash)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_invoices (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            lead_id mediumint(9) NOT NULL,
            stripe_intent_id varchar(255) DEFAULT '',
            invoice_number varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(3) DEFAULT 'USD',
            status varchar(20) DEFAULT 'unpaid',
            due_date date DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_events (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            lead_id mediumint(9) NOT NULL,
            google_event_id varchar(255) DEFAULT '',
            start_time datetime NOT NULL,
            end_time datetime NOT NULL,
            location varchar(255) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_gallery_images (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            album_id mediumint(9) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_path varchar(255) NOT NULL,
            public_url varchar(255) NOT NULL,
            serial_number varchar(50) NOT NULL,
            is_selected boolean DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY album_id (album_id)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_questionnaires (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            schema_json longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_responses (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            questionnaire_id mediumint(9) NOT NULL,
            answers_json longtext NOT NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_email_templates (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            slug varchar(50) NOT NULL UNIQUE,
            name varchar(100) NOT NULL,
            subject varchar(255) NOT NULL,
            body longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_contracts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            lead_id mediumint(9) NOT NULL,
            content longtext NOT NULL,
            signature_data longtext,
            status varchar(20) DEFAULT 'draft',
            pdf_path varchar(255) DEFAULT '',
            signed_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        foreach ( $sql as $query ) dbDelta( $query );
    }

    private static function seed_email_templates() {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_email_templates';
        if ( $wpdb->get_var("SELECT COUNT(*) FROM $table") > 0 ) return;

        $templates = [
            ['slug' => 'new_inquiry', 'name' => 'New Inquiry Response', 'subject' => 'We received your inquiry!', 'body' => "Dear {client_name},\n\nThank you for reaching out to {company_name}! We have received your inquiry.\n\nBest,\nThe {company_name} Team"],
            ['slug' => 'invoice_reminder', 'name' => 'Invoice Reminder', 'subject' => 'Reminder: Invoice #{invoice_number} is due', 'body' => "Hi {client_name},\n\nReminder that you have an outstanding invoice of {amount}.\n\nPay here: {portal_link}\n\nThank you,\n{company_name}"],
            ['slug' => 'verify_email', 'name' => 'Verify Account', 'subject' => 'Activate your Client Portal', 'body' => "Hi {client_name},\n\nPlease click the link below to set your password and access your portal:\n\n{verify_link}\n\nThanks,\n{company_name}"],
            ['slug' => 'photos_ready', 'name' => 'Photos Ready', 'subject' => 'Your Photos are Ready!', 'body' => "Hi {client_name},\n\nYour gallery is ready for review: {portal_link}\n\nBest,\n{company_name}"]
        ];

        foreach($templates as $t) $wpdb->insert($table, $t);
    }

    private static function create_default_pages() {
        $pages = [
            ['slug' => 'client-portal', 'title' => 'Client Portal', 'content' => '[aperture_client_portal]'],
            ['slug' => 'contact-us', 'title' => 'Contact Us', 'content' => '[aperture_contact_form]'],
            ['slug' => 'verify-account', 'title' => 'Verify Account', 'content' => '[aperture_verify_account]']
        ];
        foreach ( $pages as $page ) {
            if ( ! get_page_by_path( $page['slug'] ) ) {
                wp_insert_post([
                    'post_title' => $page['title'], 'post_name' => $page['slug'], 
                    'post_content' => $page['content'], 'post_status' => 'publish', 'post_type' => 'page'
                ]);
            }
        }
    }

    private static function update_db_version() { update_option( 'aperture_pro_db_version', '1.1.0' ); }
}
