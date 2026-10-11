<?php
if (!defined('ABSPATH')) exit;

class Abtal_Activator {

    public static function activate() {
        Abtal_DB::create_tables();
        self::generate_kiosk_page();
        flush_rewrite_rules();
    }

    public static function generate_kiosk_page() {
        $kiosk_page = get_page_by_path('sportedia-kiosk');
        if (!$kiosk_page) {
            $kiosk_data = array(
                'post_title'     => 'Kiosk Scanner Interface',
                'post_name'      => 'sportedia-kiosk',
                'post_content'   => '[abtal_kiosk]',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed'
            );
            wp_insert_post($kiosk_data);
        } else if ($kiosk_page->post_status !== 'publish') {
            wp_update_post(array('ID' => $kiosk_page->ID, 'post_status' => 'publish'));
        }
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }
}
