<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Template_Loader {

    public function __construct() {
        add_filter('template_include', array($this, 'load_sportedia_template'), 99);
        add_shortcode('sportedia_app', array($this, 'shortcode_app'));
        add_shortcode('sportedia_kiosk', array($this, 'shortcode_kiosk'));
        add_shortcode('sportedia_member', array($this, 'shortcode_member'));
        add_shortcode('sportedia_tournament', array($this, 'shortcode_tournament'));
    }

    public static function is_dashboard_page() {
        global $post;
        $page_id = get_option('sportedia_page_id');
        if (is_page($page_id) || is_page('sportedia')) {
            return true;
        }
        if ($post && (has_shortcode($post->post_content, 'sportedia_app') || strpos($post->post_content, '<!-- sportedia_app -->') !== false)) {
            return true;
        }
        return false;
    }

    public static function is_kiosk_page() {
        global $post;
        $kiosk_id  = get_option('sportedia_kiosk_page_id');
        $verify_id = get_option('sportedia_verify_page_id');
        $scan_id   = get_option('sportedia_scan_page_id');

        if (is_page($kiosk_id) || is_page($verify_id) || is_page($scan_id) || is_page('sportedia-kiosk') || is_page('sportedia-verify') || is_page('sportedia-scan')) {
            return true;
        }
        if ($post && (has_shortcode($post->post_content, 'sportedia_kiosk') || strpos($post->post_content, '<!-- sportedia_kiosk -->') !== false || strpos($post->post_content, '<!-- sportedia_verify -->') !== false)) {
            return true;
        }
        return false;
    }

    public static function is_member_portal_page() {
        global $post;
        $mem_id = get_option('sportedia_member_page_id');

        if (isset($_GET['sportedia_member_access'])) {
            return true;
        }
        if (is_page($mem_id) || is_page('sportedia-member')) {
            return true;
        }
        if ($post && (has_shortcode($post->post_content, 'sportedia_member') || strpos($post->post_content, '<!-- sportedia_member -->') !== false)) {
            return true;
        }
        return false;
    }

    public static function is_tournament_page() {
        global $post;
        $tourn_id = get_option('sportedia_tournament_page_id');

        if (is_page($tourn_id) || is_page('sportedia-tournament')) {
            return true;
        }
        if ($post && (has_shortcode($post->post_content, 'sportedia_tournament') || strpos($post->post_content, '<!-- sportedia_tournament -->') !== false)) {
            return true;
        }
        return false;
    }

    public static function is_sportedia_page() {
        return self::is_dashboard_page() || self::is_kiosk_page() || self::is_member_portal_page() || self::is_tournament_page();
    }

    public function load_sportedia_template($template) {
        if (self::is_tournament_page()) {
            return SPORTEDIA_PLUGIN_DIR . 'templates/tournament-public.php';
        }

        if (self::is_member_portal_page()) {
            return SPORTEDIA_PLUGIN_DIR . 'templates/member-portal.php';
        }

        if (self::is_kiosk_page()) {
            if (!is_user_logged_in()) {
                return SPORTEDIA_PLUGIN_DIR . 'templates/login-form.php';
            }
            return SPORTEDIA_PLUGIN_DIR . 'templates/verification-system.php';
        }

        if (self::is_dashboard_page()) {
            if (!is_user_logged_in()) {
                return SPORTEDIA_PLUGIN_DIR . 'templates/login-form.php';
            }
            return SPORTEDIA_PLUGIN_DIR . 'templates/app-layout.php';
        }

        return $template;
    }

    public function shortcode_member() {
        ob_start();
        include SPORTEDIA_PLUGIN_DIR . 'templates/member-portal.php';
        return ob_get_clean();
    }

    public function shortcode_app() {
        if (!is_user_logged_in()) {
            ob_start();
            include SPORTEDIA_PLUGIN_DIR . 'templates/login-form.php';
            return ob_get_clean();
        }
        ob_start();
        include SPORTEDIA_PLUGIN_DIR . 'templates/app-layout.php';
        return ob_get_clean();
    }

    public function shortcode_kiosk() {
        if (!is_user_logged_in()) {
            ob_start();
            include SPORTEDIA_PLUGIN_DIR . 'templates/login-form.php';
            return ob_get_clean();
        }
        ob_start();
        include SPORTEDIA_PLUGIN_DIR . 'templates/verification-system.php';
        return ob_get_clean();
    }

    public function shortcode_tournament() {
        ob_start();
        include SPORTEDIA_PLUGIN_DIR . 'templates/tournament-public.php';
        return ob_get_clean();
    }
}
