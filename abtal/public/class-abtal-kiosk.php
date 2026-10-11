<?php
if (!defined('ABSPATH')) exit;

class Abtal_Kiosk {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_shortcode('abtal_kiosk', array($this, 'render_kiosk_shortcode'));
        add_action('wp_ajax_abtal_kiosk_scan', array($this, 'ajax_kiosk_scan'));
        add_action('wp_ajax_nopriv_abtal_kiosk_scan', array($this, 'ajax_kiosk_scan'));
    }

    public function render_kiosk_shortcode() {
        ob_start();
        include ABTAL_PLUGIN_DIR . 'public/views/kiosk-template.php';
        return ob_get_clean();
    }

    public function ajax_kiosk_scan() {
        check_ajax_referer('abtal_nonce', 'nonce');

        $raw_barcode = isset($_POST['barcode']) ? sanitize_text_field($_POST['barcode']) : '';
        $barcode = trim($raw_barcode, " *\t\n\r\0\x0B");

        if (empty($barcode)) {
            wp_send_json_error('Please scan or enter a valid Barcode or ID.');
        }

        global $wpdb;

        // 1. Check if barcode matches Employee
        $emp = $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, u.display_name FROM {$wpdb->prefix}abtal_employees e LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID WHERE e.employee_id = %s OR e.barcode = %s",
            $barcode, $barcode
        ), ARRAY_A);

        if ($emp) {
            $att_table = $wpdb->prefix . 'abtal_attendance';
            $today = date('Y-m-d');
            $now   = date('Y-m-d H:i:s');

            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $att_table WHERE employee_id = %s AND attendance_date = %s ORDER BY id DESC LIMIT 1",
                $emp['employee_id'], $today
            ), ARRAY_A);

            if ($existing && empty($existing['check_out_time'])) {
                // Check-out
                $check_in_ts = strtotime($existing['check_in_time']);
                $work_duration = max(0, floor((time() - $check_in_ts) / 60));

                $wpdb->update($att_table, array(
                    'check_out_time'           => $now,
                    'working_duration_minutes' => $work_duration
                ), array('id' => $existing['id']));

                wp_send_json_success(array(
                    'type'         => 'employee_checkout',
                    'message'      => 'Check-Out Recorded: Goodbye ' . $emp['display_name'] . '!',
                    'name'         => $emp['display_name'],
                    'employee_id'  => $emp['employee_id'],
                    'time'         => date('H:i:s'),
                    'duration'     => $work_duration . ' mins'
                ));
            } else {
                // Check-in
                $wpdb->insert($att_table, array(
                    'user_id'                  => $emp['user_id'],
                    'employee_id'              => $emp['employee_id'],
                    'attendance_date'          => $today,
                    'check_in_time'            => $now,
                    'lateness_minutes'         => 0,
                    'working_duration_minutes' => 0,
                    'status'                   => 'present'
                ));

                wp_send_json_success(array(
                    'type'        => 'employee_checkin',
                    'message'     => 'Check-In Recorded: Welcome ' . $emp['display_name'] . '!',
                    'name'        => $emp['display_name'],
                    'employee_id' => $emp['employee_id'],
                    'time'        => date('H:i:s')
                ));
            }
        }

        // 2. Check if barcode matches Subscriber
        $sub = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}abtal_subscriptions WHERE subscriber_id = %s OR phone = %s",
            $barcode, $barcode
        ), ARRAY_A);

        if ($sub) {
            $total = intval($sub['sessions_total']);
            $used  = intval($sub['sessions_used']);
            $rem   = $total - $used;

            if ($rem <= 0) {
                wp_send_json_error('Subscription Expired or Sessions Fully Used for: ' . $sub['subscriber_name']);
            }

            $new_used = $used + 1;
            $new_rem  = $total - $new_used;

            $update_data = array('sessions_used' => $new_used);
            if ($new_rem <= 0) {
                $update_data['status'] = 'expired';
            }

            $wpdb->update($wpdb->prefix . 'abtal_subscriptions', $update_data, array('id' => $sub['id']));

            wp_send_json_success(array(
                'type'               => 'subscriber_verify',
                'message'            => 'Subscriber ID Verified & Session Deducted!',
                'name'               => $sub['subscriber_name'],
                'subscriber_id'      => $sub['subscriber_id'],
                'plan_name'          => $sub['plan_name'],
                'sessions_used'      => $new_used,
                'sessions_total'     => $total,
                'sessions_remaining' => $new_rem
            ));
        }

        wp_send_json_error('Invalid Barcode / ID: No employee or active subscriber record found for barcode: ' . $barcode);
    }
}
