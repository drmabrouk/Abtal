<?php
if (!defined('ABSPATH')) exit;

class Abtal_Subscription_Manager {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function get_whatsapp_url($phone, $message) {
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($clean_phone)) return '#';
        $encoded_msg = urlencode($message);
        return 'https://wa.me/' . $clean_phone . '?text=' . $encoded_msg;
    }

    public function __construct() {
        add_action('wp_ajax_abtal_save_subscription', array($this, 'ajax_save_subscription'));
        add_action('wp_ajax_abtal_delete_subscription', array($this, 'ajax_delete_subscription'));
    }

    public function ajax_save_subscription() {
        check_ajax_referer('abtal_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'abtal_subscriptions';

        $id               = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $subscriber_name  = sanitize_text_field($_POST['subscriber_name']);
        $phone            = sanitize_text_field($_POST['phone']);
        $email            = sanitize_email($_POST['email']);
        $plan_name        = sanitize_text_field($_POST['plan_name']);
        $sessions_total   = isset($_POST['sessions_total']) ? intval($_POST['sessions_total']) : 12;
        $start_date       = sanitize_text_field($_POST['start_date']);
        $end_date         = sanitize_text_field($_POST['end_date']);
        $price            = floatval($_POST['price']);
        $status           = sanitize_text_field($_POST['status']);

        if (empty($subscriber_name) || empty($phone) || empty($plan_name)) {
            wp_send_json_error('Subscriber Name, Phone Number, and Plan Name are required.');
        }

        // Link/create subscriber user account
        $existing_user = get_user_by('email', $email);
        if ($existing_user) {
            $user_id = $existing_user->ID;
        } else {
            $username = 'sub_' . preg_replace('/[^0-9]/', '', $phone);
            if (empty($username) || username_exists($username)) {
                $username = 'sub_' . rand(10000, 99999);
            }
            if (empty($email)) {
                $email = $username . '@abtal.online';
            }
            $user_id = wp_create_user($username, wp_generate_password(12, true), $email);
            if (is_wp_error($user_id)) {
                $user_id = get_current_user_id();
            } else {
                wp_update_user(array('ID' => $user_id, 'display_name' => $subscriber_name));
            }
        }

        if ($id > 0) {
            $wpdb->update($table, array(
                'subscriber_name' => $subscriber_name,
                'phone'           => $phone,
                'email'           => $email,
                'plan_name'       => $plan_name,
                'sessions_total'  => $sessions_total,
                'start_date'      => $start_date,
                'end_date'        => $end_date,
                'price'           => $price,
                'status'          => $status,
            ), array('id' => $id));

            wp_send_json_success('Subscription updated successfully.');
        } else {
            $invoice_number = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
            $subscriber_id  = 'SUB-' . sprintf('%04d', rand(1, 9999));

            $wpdb->insert($table, array(
                'invoice_number'  => $invoice_number,
                'user_id'         => $user_id,
                'subscriber_id'   => $subscriber_id,
                'subscriber_name' => $subscriber_name,
                'phone'           => $phone,
                'email'           => $email,
                'plan_name'       => $plan_name,
                'sessions_total'  => $sessions_total,
                'sessions_used'   => 0,
                'start_date'      => $start_date,
                'end_date'        => $end_date,
                'price'           => $price,
                'status'          => $status,
            ));

            $new_sub_id = $wpdb->insert_id;

            wp_send_json_success(array(
                'message'        => 'Subscription created successfully.',
                'sub_id'         => $new_sub_id,
                'subscriber_id'  => $subscriber_id,
                'invoice_number' => $invoice_number
            ));
        }
    }

    public function ajax_delete_subscription() {
        check_ajax_referer('abtal_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'abtal_subscriptions';
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($id > 0) {
            $wpdb->delete($table, array('id' => $id), array('%d'));
            wp_send_json_success('Subscription record deleted.');
        }

        wp_send_json_error('Invalid subscription record ID.');
    }

    public static function get_subscriptions($search = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'abtal_subscriptions';

        if (!empty($search)) {
            $s = '%' . $wpdb->esc_like(trim($search)) . '%';
            $sql = $wpdb->prepare("SELECT * FROM $table WHERE subscriber_name LIKE %s OR subscriber_id LIKE %s OR phone LIKE %s OR invoice_number LIKE %s ORDER BY id DESC", $s, $s, $s, $s);
        } else {
            $sql = "SELECT * FROM $table ORDER BY id DESC";
        }

        return $wpdb->get_results($sql, ARRAY_A);
    }
}
