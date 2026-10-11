<?php
if (!defined('ABSPATH')) exit;

/**
 * Sportedia WordPress Whitelabeling & Fingerprint Shielding Class
 */
class Sportedia_Whitelabel {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->init_shielding();
    }

    private function init_shielding() {
        // 1. Remove WP Version Meta Tag
        remove_action('wp_head', 'wp_generator');
        add_filter('the_generator', '__return_empty_string');

        // 2. Remove RSD and Windows Live Writer Discovery Links
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');

        // 3. Remove WP REST API Header Link & oEmbed Links
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
        remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
        remove_action('template_redirect', 'rest_output_link_header', 11);

        // 4. Disable XML-RPC Completely
        add_filter('xmlrpc_enabled', '__return_false');
        add_filter('wp_headers', array($this, 'remove_x_pingback'));

        // 5. Remove Asset Version Query Strings (`?ver=x.y.z`)
        add_filter('style_loader_src', array($this, 'remove_asset_version_query'), 9999);
        add_filter('script_loader_src', array($this, 'remove_asset_version_query'), 9999);

        // 6. Clean WP Login Footer Text
        add_filter('login_headertext', array($this, 'whitelabel_login_title'));
        add_filter('login_headerurl', array($this, 'whitelabel_login_url'));
    }

    public function remove_x_pingback($headers) {
        unset($headers['X-Pingback']);
        return $headers;
    }

    public function remove_asset_version_query($src) {
        if (strpos($src, 'ver=')) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }

    public function whitelabel_login_title() {
        return 'Sportedia Online Management';
    }

    public function whitelabel_login_url() {
        $page_id = get_option('sportedia_page_id');
        return $page_id ? get_permalink($page_id) : home_url('/sportedia/');
    }
}
