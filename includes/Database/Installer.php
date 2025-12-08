<?php
namespace AperturePro\Database;

class Installer {

    public static function install() {
        self::create_tables();
        self::seed_email_templates(); // NEW: Seed defaults
        self::create_default_pages();
        self::update_db_version();
    }

    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = [];

        // 1. Contacts
        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_contacts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(50) DEFAULT '',
            address text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY email (email)
        ) $charset_collate;";

        // 2. Leads
        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_leads (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            contact_id mediumint(9) NOT NULL,
            status varchar(50) DEFAULT 'new',
            source varchar(100) DEFAULT '',
            project_value decimal(10,2) DEFAULT '0.00',
            notes longtext DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY contact_id (contact_id)
        ) $charset_collate;";

        // 3. Invoices
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

        // 4. Events
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

        // 5. Gallery Images
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

        // 6. Questionnaires
        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_questionnaires (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            schema_json longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // 7. Questionnaire Responses
        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_responses (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            questionnaire_id mediumint(9) NOT NULL,
            answers_json longtext NOT NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // 8. NEW: Email Templates
        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_email_templates (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            slug varchar(50) NOT NULL UNIQUE,
            name varchar(100) NOT NULL,
            subject varchar(255) NOT NULL,
            body longtext NOT NULL,
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
            [
                'slug' => 'new_inquiry',
                'name' => 'New Inquiry Response',
                'subject' => 'We received your inquiry!',
                'body' => "Dear {client_name},\n\nThank you for reaching out to {company_name}! We have received your inquiry regarding a photoshoot. One of our team members will review your details and follow up with you shortly.\n\nBest,\nThe {company_name} Team"
            ],
            [
                'slug' => 'invoice_reminder',
                'name' => 'Invoice Reminder',
                'subject' => 'Reminder: Invoice #{invoice_number} is due',
                'body' => "Hi {client_name},\n\nThis is a friendly reminder that you have an outstanding invoice.\n\nAmount: {amount}\n\nYou can view and pay your invoice here: {portal_link}\n\nThank you,\n{company_name}"
            ],
            [
                'slug' => 'photos_ready',
                'name' => 'Photos Ready',
                'subject' => 'Your Photos are Ready!',
                'body' => "Hi {client_name},\n\nGreat news! Your photos are ready for review.\n\nPlease log in to your client portal to view your gallery and make your proofing selections: {portal_link}\n\nWe can't wait to hear what you think!\n\nBest,\n{company_name}"
            ],
            [
                'slug' => 'booking_confirmed',
                'name' => 'Booking Confirmation',
                'subject' => 'Confirmed: Your Session',
                'body' => "Dear {client_name},\n\nYour photoshoot is officially booked. We are looking forward to capturing these moments for you.\n\nRegards,\n{company_name}"
            ]
        ];

        foreach($templates as $t) $wpdb->insert($table, $t);
    }

    private static function create_default_pages() {
        $pages = [
            ['slug' => 'client-portal', 'title' => 'Client Portal', 'content' => '[aperture_client_portal]'],
            ['slug' => 'contact-us', 'title' => 'Contact Us', 'content' => '[aperture_contact_form]']
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

    private static function update_db_version() { update_option( 'aperture_pro_db_version', '1.0.0' ); }
}
