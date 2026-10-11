<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Settings_Manager {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_sportedia_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_sportedia_reset_transactional_data', array($this, 'ajax_reset_transactional_data'));
    }

    public function ajax_reset_transactional_data() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('manage_options') && !Sportedia_Roles::is_sys_admin()) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $subs_table = $wpdb->prefix . 'sportedia_subscriptions';
        $att_table  = $wpdb->prefix . 'sportedia_attendance';

        $wpdb->query("TRUNCATE TABLE $subs_table");
        $wpdb->query("TRUNCATE TABLE $att_table");

        Sportedia_DB::log_activity('reset_transactional_data', 'Administrator performed reset of subscriptions and attendance transactional records.');

        wp_send_json_success('Subscriptions and attendance transactional records reset successfully.');
    }

    public static function get_setting($key, $default = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_settings';
        $val = $wpdb->get_var($wpdb->prepare("SELECT setting_value FROM $table WHERE setting_key = %s", $key));
        return $val !== null ? $val : $default;
    }

    public static function update_setting($key, $value) {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_settings';
        $wpdb->replace($table, array(
            'setting_key'   => sanitize_key($key),
            'setting_value' => sanitize_text_field($value)
        ), array('%s', '%s'));
    }

    public function ajax_save_settings() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_settings') && !Sportedia_Roles::is_sys_admin()) {
            wp_send_json_error('Unauthorized.');
        }

        if (isset($_POST['settings']) && is_array($_POST['settings'])) {
            foreach ($_POST['settings'] as $key => $val) {
                self::update_setting($key, $val);
                if ($key === 'next_member_sequence') {
                    update_option('sportedia_next_member_sequence', sanitize_text_field($val));
                    Sportedia_DB::log_activity('reset_member_sequence', 'Administrator updated next member starting sequence to ' . sanitize_text_field($val));
                }
            }
        }

        // Handle System Logo Upload
        if (!empty($_FILES['system_logo_file']['tmp_name'])) {
            if ($_FILES['system_logo_file']['size'] > 3 * 1024 * 1024) {
                wp_send_json_error('System logo photo exceeds 3 MB size limit.');
            }
            $file_type = wp_check_filetype($_FILES['system_logo_file']['name']);
            $allowed_types = array('jpg', 'jpeg', 'png', 'webp', 'svg');
            if (!in_array(strtolower($file_type['ext']), $allowed_types, true)) {
                wp_send_json_error('Invalid image file format for logo. Allowed: JPG, PNG, WEBP, SVG.');
            }
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_id = media_handle_upload('system_logo_file', 0);
            if (!is_wp_error($attachment_id)) {
                $url = wp_get_attachment_url($attachment_id);
                self::update_setting('system_logo', $url);
            } else {
                wp_send_json_error($attachment_id->get_error_message());
            }
        }

        wp_send_json_success('System settings saved successfully.');
    }
}
