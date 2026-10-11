<?php
if (!defined('ABSPATH')) exit;

class Abtal_Import_Export {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_abtal_export_csv', array($this, 'handle_export_csv'));
        add_action('wp_ajax_abtal_import_csv', array($this, 'handle_import_csv'));
        add_action('wp_ajax_abtal_dispatch_card_whatsapp', array($this, 'ajax_dispatch_card_whatsapp'));
    }

    public function handle_export_csv() {
        check_ajax_referer('abtal_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized export request.');
        }

        $type = isset($_GET['export_type']) ? sanitize_text_field($_GET['export_type']) : 'employees';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=abtal_' . $type . '_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM

        if ($type === 'subscriptions') {
            fputcsv($output, array('Subscriber ID', 'Subscriber Name', 'Phone', 'Email', 'Plan Name', 'Sessions Used', 'Sessions Total', 'Start Date', 'End Date', 'Price (AED)', 'Status', 'Digital Card Link'));
            $subs = Abtal_Subscription_Manager::get_subscriptions();
            foreach ($subs as $s) {
                $card_url = add_query_arg(array('abtal_card' => $s['subscriber_id']), site_url('/'));
                fputcsv($output, array(
                    $s['subscriber_id'],
                    $s['subscriber_name'],
                    $s['phone'],
                    $s['email'],
                    $s['plan_name'],
                    $s['sessions_used'],
                    $s['sessions_total'],
                    $s['start_date'],
                    $s['end_date'],
                    $s['price'],
                    $s['status'],
                    $card_url
                ));
            }
        } else {
            fputcsv($output, array('Employee ID', 'Branch Prefix', 'Name', 'Email', 'Phone', 'Job Title', 'Department', 'Salary', 'Status', 'Digital Card Link'));
            $employees = Abtal_Employee_Manager::get_employees();
            foreach ($employees as $e) {
                $card_url = add_query_arg(array('abtal_card' => $e['employee_id']), site_url('/'));
                fputcsv($output, array(
                    $e['employee_id'],
                    $e['branch_prefix'],
                    $e['display_name'],
                    $e['user_email'],
                    $e['phone'],
                    $e['job_title'],
                    $e['department'],
                    $e['salary'],
                    $e['status'],
                    $card_url
                ));
            }
        }

        fclose($output);
        exit;
    }

    public function handle_import_csv() {
        check_ajax_referer('abtal_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized import request.');
        }

        if (empty($_FILES['csv_file']['tmp_name'])) {
            wp_send_json_error('No CSV file uploaded.');
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) {
            wp_send_json_error('Unable to read uploaded file.');
        }

        $header = fgetcsv($handle);
        $imported = 0;
        $skipped  = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 2) {
                $name  = sanitize_text_field($row[0]);
                $phone = sanitize_text_field($row[1]);
                $email = isset($row[2]) ? sanitize_email($row[2]) : '';
                $plan  = isset($row[3]) ? sanitize_text_field($row[3]) : 'Standard Plan';

                if (empty($name) || empty($phone)) {
                    $skipped++;
                    continue;
                }

                $_POST['subscriber_name'] = $name;
                $_POST['phone']           = $phone;
                $_POST['email']           = $email;
                $_POST['plan_name']       = $plan;
                $_POST['sessions_total']  = 12;
                $_POST['start_date']      = date('Y-m-d');
                $_POST['end_date']        = date('Y-m-d', strtotime('+30 days'));
                $_POST['price']           = 0.00;
                $_POST['status']          = 'active';

                global $wpdb;
                $table = $wpdb->prefix . 'abtal_subscriptions';
                $subscriber_id = 'SUB-' . sprintf('%04d', rand(1, 9999));

                $wpdb->insert($table, array(
                    'invoice_number'  => 'INV-' . date('Ymd') . '-' . rand(1000, 9999),
                    'user_id'         => get_current_user_id(),
                    'subscriber_id'   => $subscriber_id,
                    'subscriber_name' => $name,
                    'phone'           => $phone,
                    'email'           => $email,
                    'plan_name'       => $plan,
                    'sessions_total'  => 12,
                    'sessions_used'   => 0,
                    'start_date'      => date('Y-m-d'),
                    'end_date'        => date('Y-m-d', strtotime('+30 days')),
                    'price'           => 0.00,
                    'status'          => 'active',
                ));

                $imported++;
            }
        }

        fclose($handle);

        wp_send_json_success(sprintf('CSV Import Complete: %d imported successfully, %d skipped.', $imported, $skipped));
    }

    public function ajax_dispatch_card_whatsapp() {
        check_ajax_referer('abtal_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized.');
        }

        $card_id = sanitize_text_field($_POST['card_id']);
        $phone   = sanitize_text_field($_POST['phone']);

        if (empty($card_id) || empty($phone)) {
            wp_send_json_error('Card ID and Phone Number are required for dispatch.');
        }

        $card_url = add_query_arg(array('abtal_card' => $card_id), site_url('/'));
        $msg      = "Hello, here is your official Abtal Electronic Digital ID Card: " . $card_url;
        $wa_url   = Abtal_Subscription_Manager::get_whatsapp_url($phone, $msg);

        wp_send_json_success(array(
            'message'      => 'Card dispatch link generated.',
            'whatsapp_url' => $wa_url,
            'card_url'     => $card_url
        ));
    }
}
