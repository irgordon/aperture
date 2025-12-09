<?php
namespace AperturePro\Database;

class Installer {

    /**
     * Main installation method.
     * Runs table creation, seeds data, creates pages, and sets up folders.
     */
    public static function install() {
        self::create_tables();
        self::seed_email_templates();
        self::create_default_pages();
        self::update_db_version();
        
        // Create necessary upload directories for secure storage
        $upload = wp_upload_dir();
        $dirs = ['aperture_contracts', 'aperture_proofs', 'aperture_temp', 'aperture_imports'];
        
        foreach ( $dirs as $d ) {
            $path = $upload['basedir'] . '/' . $d;
            if ( ! file_exists( $path ) ) {
                // Suppress warnings to prevent activation output errors
                @mkdir( $path, 0755, true );
            }
        }
    }

    /**
     * Defines and creates all custom database tables.
     * Uses dbDelta to safely update existing tables without losing data.
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = [];

        // 1. Contacts (CRM Address Book)
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
        ) $charset_collate;";

        // 2. Leads (Projects & Opportunities)
        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_leads (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            contact_id mediumint(9) NOT NULL,
            project_hash varchar(64) UNIQUE, -- Secure URL access
            title varchar(255), -- Project Name
            status varchar(50) DEFAULT 'new',
            source varchar(100) DEFAULT '',
            project_value decimal(10,2) DEFAULT '0.00',
            notes longtext DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY contact_id (contact_id),
            KEY project_hash (project_hash)
        ) $charset_collate;";

        // 3. Tasks (Project Sub-tasks)
        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_tasks (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            lead_id mediumint(9) DEFAULT NULL,
            description varchar(255) NOT NULL,
            is_completed boolean DEFAULT 0,
            due_date date DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY lead_id (lead_id)
        ) $charset_collate;";

        // 4. Invoices (Financials with Itemization)
        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_invoices (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            lead_id mediumint(9) NOT NULL,
            stripe_intent_id varchar(255) DEFAULT '',
            invoice_number varchar(50) NOT NULL,
            items_json longtext, -- Stores line items, tax rates, fees as JSON
            amount decimal(10,2) NOT NULL, -- The subtotal
            total_amount decimal(10,2) NOT NULL, -- The final total with tax/fees
            currency varchar(3) DEFAULT 'USD',
            status varchar(20) DEFAULT 'unpaid',
            due_date date DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY lead_id (lead_id)
        ) $charset_collate;";

        // 5. Contracts (Digital Signatures)
        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_contracts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            lead_id mediumint(9) NOT NULL,
            content longtext NOT NULL, -- The HTML body of the contract
            client_signature longtext, -- Base64 PNG data
            admin_signature longtext, -- Base64 PNG data
            status varchar(20) DEFAULT 'draft',
            pdf_path varchar(255) DEFAULT '',
            signed_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY lead_id (lead_id)
        ) $charset_collate;";

        // 6. Gallery Images (Proofing & Delivery)
        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_gallery_images (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            album_id mediumint(9) NOT NULL,
            lead_id mediumint(9) DEFAULT NULL,
            file_name varchar(255) NOT NULL,
            file_path varchar(255) NOT NULL,
            public_url varchar(255) NOT NULL,
            proof_id varchar(20), -- User facing serial (e.g. #145)
            serial_number varchar(50) DEFAULT '', -- Internal serial
            is_selected boolean DEFAULT 0, -- Client selection
            is_downloadable boolean DEFAULT 0, -- False=Proof, True=Final
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY album_id (album_id)
        ) $charset_collate;";

        // 7. Questionnaires (Form Builder)
        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_questionnaires (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            schema_json longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // 8. Questionnaire Responses
        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_responses (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            questionnaire_id mediumint(9) NOT NULL,
            lead_id mediumint(9) DEFAULT NULL,
            answers_json longtext NOT NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // 9. Email Templates (Canned Responses)
        $sql[] = "CREATE TABLE {$wpdb->prefix}ap_email_templates (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            slug varchar(50) NOT NULL UNIQUE,
            name varchar(100) NOT NULL,
            subject varchar(255) NOT NULL,
            body longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // 10. Calendar Events (Google Sync)
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
        ) $charset_collate;";

        // Execute changes
        foreach ( $sql as $query ) {
            dbDelta( $query );
        }
    }

    /**
     * Seeds the database with default email templates if none exist.
     */
    private static function seed_email_templates() {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_email_templates';
        
        // Only run if table is empty
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
                'body' => "Hi {client_name},\n\nThis is a friendly reminder that you have an outstanding invoice.\n\nAmount: {amount}\nDue Date: {due_date}\n\nYou can view and pay your invoice here: {portal_link}\n\nThank you,\n{company_name}"
            ],
            [
                'slug' => 'photos_ready',
                'name' => 'Photos Ready',
                'subject' => 'Your Photos are Ready!',
                'body' => "Hi {client_name},\n\nGreat news! Your photos are ready for review.\n\nPlease log in to your client portal to view your gallery and make your proofing selections: {portal_link}\n\nWe can't wait to hear what you think!\n\nBest,\n{company_name}"
            ],
            [
                'slug' => 'contract_signature',
                'name' => 'Contract Signature Request',
                'subject' => 'Action Required: Please sign your contract',
                'body' => "Dear {client_name},\n\nPlease review and sign your photography agreement here: {portal_link}\n\nOnce signed, we can confirm your booking date.\n\nRegards,\n{company_name}"
            ],
            [
                'slug' => 'booking_confirmed',
                'name' => 'Booking Confirmation',
                'subject' => 'Confirmed: Your Session',
                'body' => "Dear {client_name},\n\nYour photoshoot is officially booked. We are looking forward to capturing these moments for you.\n\nRegards,\n{company_name}"
            ]
        ];

        foreach( $templates as $t ) {
            $wpdb->insert( $table, $t );
        }
    }

    /**
     * Ensures critical frontend pages exist with the correct shortcodes.
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
            if ( ! get_page_by_path( $page['slug'] ) ) {
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
     * Stores the current DB version to handle future migrations.
     */
    private static function update_db_version() {
        update_option( 'aperture_pro_db_version', '2.0.0' );
    }
}
