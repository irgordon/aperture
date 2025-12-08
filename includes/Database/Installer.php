<?php
namespace AperturePro\Database;

/**
 * Handles database creation and default setup on plugin activation.
 */
class Installer {

    /**
     * Run the installer.
     * Hooked to register_activation_hook in the main file.
     */
    public static function install() {
        self::create_tables();
        self::create_default_pages();
        self::update_db_version();
    }

    /**
     * Create or update custom tables using dbDelta.
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // 1. Contacts Table (Clients)
        $table_contacts = $wpdb->prefix . 'ap_contacts';
        $sql_contacts = "CREATE TABLE $table_contacts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(50) DEFAULT '',
            address text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY email (email),
            KEY user_id (user_id)
        ) $charset_collate;";

        // 2. Leads Table (The Pipeline)
        $table_leads = $wpdb->prefix . 'ap_leads';
        $sql_leads = "CREATE TABLE $table_leads (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            contact_id mediumint(9) NOT NULL,
            status varchar(50) DEFAULT 'new',
            source varchar(100) DEFAULT '',
            project_value decimal(10,2) DEFAULT '0.00',
            event_date datetime DEFAULT NULL,
            notes longtext DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY contact_id (contact_id),
            KEY status (status)
        ) $charset_collate;";

        // 3. Invoices Table (Financials)
        $table_invoices = $wpdb->prefix . 'ap_invoices';
        $sql_invoices = "CREATE TABLE $table_invoices (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            lead_id mediumint(9) NOT NULL,
            stripe_intent_id varchar(255) DEFAULT '',
            invoice_number varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(3) DEFAULT 'USD',
            status varchar(20) DEFAULT 'unpaid',
            due_date date DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY lead_id (lead_id),
            KEY stripe_intent_id (stripe_intent_id)
        ) $charset_collate;";

        // 4. Events Table (Calendar Sync)
        $table_events = $wpdb->prefix . 'ap_events';
        $sql_events = "CREATE TABLE $table_events (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            lead_id mediumint(9) NOT NULL,
            google_event_id varchar(255) DEFAULT '',
            start_time datetime NOT NULL,
            end_time datetime NOT NULL,
            location varchar(255) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY lead_id (lead_id)
        ) $charset_collate;";

        // 5. Gallery Images (Proofing System - bypassing Media Library)
        $table_gallery = $wpdb->prefix . 'ap_gallery_images';
        $sql_gallery = "CREATE TABLE $table_gallery (
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

        // 6. Questionnaires (Form Definitions)
        $table_quests = $wpdb->prefix . 'ap_questionnaires';
        $sql_quests = "CREATE TABLE $table_quests (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            schema_json longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // 7. Questionnaire Responses (Client Answers)
        $table_responses = $wpdb->prefix . 'ap_responses';
        $sql_responses = "CREATE TABLE $table_responses (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            questionnaire_id mediumint(9) NOT NULL,
            lead_id mediumint(9) DEFAULT NULL,
            answers_json longtext NOT NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Execute dbDelta
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        
        dbDelta( $sql_contacts );
        dbDelta( $sql_leads );
        dbDelta( $sql_invoices );
        dbDelta( $sql_events );
        dbDelta( $sql_gallery );
        dbDelta( $sql_quests );
        dbDelta( $sql_responses );
    }

    /**
     * Automatically create the frontend pages with embedded shortcodes.
     */
    private static function create_default_pages() {
        $pages = [
            [
                'slug'    => 'client-portal',
                'title'   => 'Client Portal',
                'content' => '[aperture_client_portal]'
            ],
            [
                'slug'    => 'contact-us',
                'title'   => 'Contact Us',
                'content' => '[aperture_contact_form]'
            ]
        ];

        foreach ( $pages as $page ) {
            $existing_page = get_page_by_path( $page['slug'] );

            if ( ! $existing_page ) {
                wp_insert_post([
                    'post_title'     => $page['title'],
                    'post_name'      => $page['slug'],
                    'post_content'   => $page['content'],
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'comment_status' => 'closed',
                    'ping_status'    => 'closed'
                ]);
            }
        }
    }

    /**
     * Store DB version for future updates.
     */
    private static function update_db_version() {
        update_option( 'aperture_pro_db_version', '1.0.0' );
    }
}
