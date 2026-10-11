<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Activator {

    public static function activate() {
        // Safe database migration without dropping existing tables
        Sportedia_DB::create_tables();

        // Safe frontend page generation without duplicate pages
        Sportedia_Page_Generator::generate_pages();

        // Initialize Roles
        Sportedia_Roles::init();

        // Flush rewrite rules safely
        flush_rewrite_rules();
    }

    public static function deactivate() {
        // Preserve all user data, tables, and settings upon deactivation
        flush_rewrite_rules();
    }
}
