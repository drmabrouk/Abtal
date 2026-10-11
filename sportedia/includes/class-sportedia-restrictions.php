<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Restrictions {

    public function __construct() {
        add_action('after_setup_theme', array($this, 'remove_admin_bar'));
        add_action('admin_init', array($this, 'restrict_admin_access'));
    }

    public function remove_admin_bar() {
        if (is_user_logged_in() && !Sportedia_Roles::is_sys_admin()) {
            show_admin_bar(false);
        }
    }

    public function restrict_admin_access() {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        if (is_user_logged_in() && !Sportedia_Roles::is_sys_admin()) {
            $page_id = get_option('sportedia_page_id');
            $redirect_url = $page_id ? get_permalink($page_id) : home_url('/sportedia/');
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
}
