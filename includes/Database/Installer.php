<?php
namespace AperturePro\Database;

class Installer {
    public static function install() {
        self::create_tables();
        self::seed_email_templates();
        self::create_default_pages();
        self::update_db_version();
        
        $upload = wp_upload_dir();
        $dirs = ['aperture_contracts', 'aperture_proofs', 'aperture_temp', 'aperture_imports', 'aperture_deliveries'];
        foreach($dirs as $d) {
            $path = $upload['basedir'] . '/' . $d;
            if(!file_exists($path)) @mkdir($path, 0755, true);
        }
    }

    private static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = "
        CREATE TABLE {$wpdb->prefix}ap_contacts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED,
            first_name varchar(100),
            last_name varchar(100),
            email varchar(100),
            phone varchar(50),
            address text,
            verification_token varchar(100),
            is_verified boolean DEFAULT 0,
            password_hash varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY email (email)
        ) $charset;
        CREATE TABLE {$wpdb->prefix}ap_leads (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            contact_id mediumint(9),
            project_hash varchar(64) UNIQUE,
            title varchar(255),
            status varchar(50) DEFAULT 'new',
            stage varchar(50) DEFAULT 'inquiry',
            source varchar(100),
            project_value decimal(10,2),
            notes longtext,
            event_date date,
            gallery_expiry datetime,
            delivery_notes text,
            zip_path varchar(255),
            is_zip_ready boolean DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset;
        CREATE TABLE {$wpdb->prefix}ap_tasks (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            lead_id mediumint(9),
            description varchar(255),
            is_completed boolean DEFAULT 0,
            due_date date,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset;
        CREATE TABLE {$wpdb->prefix}ap_invoices (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            lead_id mediumint(9),
            stripe_intent_id varchar(255),
            invoice_number varchar(50),
            items_json longtext,
            amount decimal(10,2),
            total_amount decimal(10,2),
            amount_paid decimal(10,2) DEFAULT '0.00',
            status varchar(20) DEFAULT 'unpaid',
            due_date date,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset;
        CREATE TABLE {$wpdb->prefix}ap_contracts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            lead_id mediumint(9),
            content longtext,
            client_signature longtext,
            admin_signature longtext,
            status varchar(20) DEFAULT 'draft',
            pdf_path varchar(255),
            signed_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset;
        CREATE TABLE {$wpdb->prefix}ap_packages (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255),
            description text,
            price decimal(10,2),
            deliverables_json longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset;
        CREATE TABLE {$wpdb->prefix}ap_gallery_images (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            album_id mediumint(9),
            lead_id mediumint(9),
            file_name varchar(255),
            file_path varchar(255),
            public_url varchar(255),
            proof_id varchar(20),
            serial_number varchar(50),
            is_selected boolean DEFAULT 0,
            status varchar(20) DEFAULT 'pending',
            is_downloadable boolean DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset;
        CREATE TABLE {$wpdb->prefix}ap_email_templates (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            slug varchar(50) UNIQUE,
            name varchar(100),
            subject varchar(255),
            body longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset;
        CREATE TABLE {$wpdb->prefix}ap_email_template_versions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            template_id mediumint(9),
            subject varchar(255),
            body longtext,
            created_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset;
        CREATE TABLE {$wpdb->prefix}ap_job_queue (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            type varchar(50),
            payload_json longtext,
            status varchar(20) DEFAULT 'pending',
            attempts int DEFAULT 0,
            last_attempt datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset;
        CREATE TABLE {$wpdb->prefix}ap_questionnaires (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255),
            schema_json longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset;
        CREATE TABLE {$wpdb->prefix}ap_responses (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            questionnaire_id mediumint(9),
            answers_json longtext,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset;
        CREATE TABLE {$wpdb->prefix}ap_logs (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            type varchar(50),
            message text,
            context_json longtext,
            user_id bigint(20) UNSIGNED,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset;
        ";

        dbDelta($sql);
    }

    private static function seed_email_templates() {
        global $wpdb;
        $table = $wpdb->prefix . 'ap_email_templates';

        $templates = [
            ['slug'=>'new_inquiry', 'name'=>'New Inquiry', 'subject'=>'We received your inquiry!', 'body'=>"Dear {client_name},\n\nThank you for reaching out! We'll be in touch shortly."],
            ['slug'=>'photos_ready', 'name'=>'Photos Ready', 'subject'=>'Your Gallery is Ready', 'body'=>"Hi {client_name},\n\nView your photos here: {portal_link}"],
            ['slug'=>'invoice_reminder', 'name'=>'Invoice Reminder', 'subject'=>'Invoice Due', 'body'=>"Hi {client_name},\n\nPlease pay your invoice: {portal_link}"],
            ['slug'=>'payment_failed', 'name'=>'Payment Failed', 'subject'=>'Issue with your payment', 'body'=>"Hi {client_name},\n\nWe were unable to process your payment for Invoice #{invoice_number}. Please try again: {portal_link}"],
            ['slug'=>'gate_locked', 'name'=>'Action Required', 'subject'=>'Access Restricted', 'body'=>"Hi {client_name},\n\nTo view your gallery, please complete the following items: {pending_items}."],
            ['slug'=>'gallery_expiry', 'name'=>'Gallery Expiring Soon', 'subject'=>'Download your photos!', 'body'=>"Hi {client_name},\n\nYour gallery will expire on {expiry_date}. Please download your photos soon: {portal_link}"],
            ['slug'=>'zip_ready', 'name'=>'Download Ready', 'subject'=>'Your photos are ready for download', 'body'=>"Hi {client_name},\n\nYour download is ready. Click here: {download_link}"]
        ];

        foreach($templates as $tpl) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE slug = %s", $tpl['slug']));
            if (!$exists) {
                $wpdb->insert($table, $tpl);
            }
        }

        $pkg_table = $wpdb->prefix . 'ap_packages';
        if ($wpdb->get_var("SELECT COUNT(*) FROM $pkg_table") == 0) {
            $wpdb->insert($pkg_table, ['name'=>'Wedding Essentials', 'description'=>'8 hours', 'price'=>2500.00, 'deliverables_json'=>json_encode(['8 Hours', 'Gallery'])]);
        }
    }

    private static function create_default_pages() {
        // Check option first to avoid creating duplicates if page was renamed
        $portal_page_id = get_option('aperture_portal_page_id');
        if (!$portal_page_id || !get_post($portal_page_id)) {
            $existing = get_page_by_path('client-portal');
            if ($existing) {
                 update_option('aperture_portal_page_id', $existing->ID);
            } else {
                $id = wp_insert_post(['post_title'=>'Client Portal', 'post_name'=>'client-portal', 'post_content'=>'[aperture_client_portal]', 'post_status'=>'publish', 'post_type'=>'page']);
                if ($id) update_option('aperture_portal_page_id', $id);
            }
        }

        $contact_page_id = get_option('aperture_contact_page_id');
        if (!$contact_page_id || !get_post($contact_page_id)) {
            $existing = get_page_by_path('contact-us');
            if ($existing) {
                 update_option('aperture_contact_page_id', $existing->ID);
            } else {
                $id = wp_insert_post(['post_title'=>'Contact Us', 'post_name'=>'contact-us', 'post_content'=>'[aperture_contact_form]', 'post_status'=>'publish', 'post_type'=>'page']);
                if ($id) update_option('aperture_contact_page_id', $id);
            }
        }
    }

    private static function update_db_version() { update_option('aperture_pro_db_version', '2.3.0'); }
}
