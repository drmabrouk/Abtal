<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Import_Export {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_sportedia_export_csv', array($this, 'handle_export_csv'));
        add_action('wp_ajax_sportedia_import_csv', array($this, 'handle_import_csv'));
        add_action('wp_ajax_sportedia_export_json_backup', array($this, 'handle_export_json_backup'));
    }

    public function handle_export_json_backup() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('manage_options') && !Sportedia_Roles::is_sys_admin()) {
            wp_die('Unauthorized.');
        }

        @set_time_limit(300);

        global $wpdb;

        $data = array(
            'export_date'    => date('Y-m-d H:i:s'),
            'system'         => 'Sportedia Online Management',
            'users'          => Sportedia_User_Manager::get_users(),
            'branches'       => Sportedia_Branch_Manager::get_branches(),
            'programs'       => Sportedia_Program_Manager::get_programs(),
            'subscriptions'  => Sportedia_Subscription_Manager::get_subscriptions('', 0, ''),
            'attendance'     => Sportedia_Attendance_Manager::get_attendance('', 0, 0, 0),
            'sec_players'    => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sportedia_sec_players", ARRAY_A),
            'sec_coaches'    => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sportedia_sec_coaches", ARRAY_A),
            'sec_branches'   => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sportedia_sec_branches", ARRAY_A),
            'sec_attendance' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sportedia_sec_attendance", ARRAY_A),
            'tournaments'    => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sportedia_tournaments", ARRAY_A),
            'tournament_teams' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sportedia_tournament_teams", ARRAY_A),
            'tournament_players' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sportedia_tournament_players", ARRAY_A),
            'tournament_fixtures' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sportedia_tournament_fixtures", ARRAY_A),
        );

        Sportedia_DB::log_activity('json_backup_export', 'Exported full JSON system backup.');

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=sportedia_backup_' . date('Y-m-d_H-i') . '.json');

        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    public function handle_export_csv() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        $curr_u = wp_get_current_user();
        $admin_roles = array('sportedia_sys_admin', 'sportedia_general_mgr', 'sportedia_facility_mgr', 'sportedia_ops_mgr', 'sportedia_finance_mgr', 'administrator');
        $has_perm = false;
        foreach ((array)$curr_u->roles as $r) {
            if (in_array($r, $admin_roles, true) || current_user_can('sportedia_import_export') || current_user_can('manage_options')) {
                $has_perm = true;
                break;
            }
        }

        if (!$has_perm) {
            wp_die('Unauthorized. Export functionality is restricted to authorized administrative users.');
        }

        @set_time_limit(300);

        $type          = isset($_GET['export_type']) ? sanitize_text_field($_GET['export_type']) : 'users';
        $search        = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $branch_filter = isset($_GET['branch_filter']) ? intval($_GET['branch_filter']) : 0;
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
        $role_filter   = isset($_GET['role_filter']) ? sanitize_text_field($_GET['role_filter']) : '';

        Sportedia_DB::log_activity('csv_export', 'Exported ' . $type . ' CSV dataset with active filters.');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=sportedia_' . $type . '_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        // Add UTF-8 BOM for Microsoft Excel compatibility
        fputs($output, "\xEF\xBB\xBF");

        if ($type === 'branches') {
            fputcsv($output, array('Branch ID', 'Branch Name', 'Code', 'Phone', 'Email', 'Status'));
            $branches = Sportedia_Branch_Manager::get_branches($search);
            foreach ($branches as $b) {
                fputcsv($output, array($b['id'], $b['branch_name'], $b['code'], $b['phone'], $b['email'], $b['status']));
            }
        } else if ($type === 'subscriptions') {
            fputcsv($output, array('Sub ID', 'Invoice No', 'Member', 'Employee ID', 'Plan Name', 'Type', 'Start Date', 'End Date', 'Price', 'Status'));
            $subs = Sportedia_Subscription_Manager::get_subscriptions($search, $branch_filter, $status_filter);
            foreach ($subs as $s) {
                fputcsv($output, array(
                    $s['id'],
                    $s['invoice_number'],
                    $s['member_name'],
                    $s['employee_id'],
                    $s['plan_name'],
                    $s['subscription_type'],
                    $s['start_date'],
                    $s['end_date'],
                    $s['price'],
                    $s['status']
                ));
            }
        } else if ($type === 'attendance') {
            fputcsv($output, array('Record ID', 'Employee ID', 'Employee Name', 'Role', 'Branch', 'Date', 'Scheduled Start', 'Check-In Time', 'Scheduled End', 'Check-Out Time', 'Lateness (Mins)', 'Working Duration (Mins)', 'Status'));
            $att = Sportedia_Attendance_Manager::get_attendance($search, $branch_filter, 0);
            foreach ($att as $a) {
                fputcsv($output, array(
                    $a['id'],
                    $a['employee_id'],
                    $a['user_name'],
                    $a['role_label'],
                    $a['branch_name'],
                    $a['attendance_date'],
                    $a['scheduled_start'],
                    $a['check_in_time'],
                    $a['scheduled_end'],
                    $a['check_out_time'],
                    $a['lateness_minutes'],
                    $a['working_duration_minutes'],
                    $a['status']
                ));
            }
        } else {
            fputcsv($output, array('User ID', 'Employee ID', 'Name', 'Email', 'Phone', 'Role', 'Status'));
            $users = Sportedia_User_Manager::get_users($search, $role_filter, $branch_filter);
            foreach ($users as $u) {
                fputcsv($output, array($u['id'], $u['employee_id'], $u['name'], $u['email'], $u['phone'], $u['role'], $u['status']));
            }
        }

        fclose($output);
        exit;
    }

    public function handle_import_csv() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        $curr_u = wp_get_current_user();
        $admin_roles = array('sportedia_sys_admin', 'sportedia_general_mgr', 'sportedia_facility_mgr', 'administrator');
        $has_perm = false;
        foreach ((array)$curr_u->roles as $r) {
            if (in_array($r, $admin_roles, true) || current_user_can('sportedia_import_export') || current_user_can('manage_options')) {
                $has_perm = true;
                break;
            }
        }

        if (!$has_perm) {
            wp_send_json_error('Unauthorized. CSV Import is restricted to authorized administrative users.');
        }

        if (empty($_FILES['csv_file']['tmp_name'])) {
            wp_send_json_error('No CSV file uploaded.');
        }

        @set_time_limit(300);

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) {
            wp_send_json_error('Unable to open uploaded file.');
        }

        $header = fgetcsv($handle);
        $imported_count = 0;
        $skipped_count  = 0;
        $invalid_count  = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 3) {
                $employee_id  = sanitize_text_field($row[0]);
                $name         = sanitize_text_field($row[1]);
                $email        = sanitize_email($row[2]);
                $role         = isset($row[3]) ? sanitize_text_field($row[3]) : 'sportedia_customer';

                if (empty($name) || empty($email)) {
                    $invalid_count++;
                    continue;
                }

                if (!empty($employee_id)) {
                    $existing_emp = get_users(array(
                        'meta_key'   => 'sportedia_employee_id',
                        'meta_value' => $employee_id,
                        'number'     => 1,
                    ));
                    if (!empty($existing_emp)) {
                        $skipped_count++;
                        continue;
                    }
                }

                if (email_exists($email)) {
                    $skipped_count++;
                    continue;
                }

                $username = strtolower(str_replace(' ', '', $employee_id));
                if (empty($username) || username_exists($username)) {
                    $username = 'sp_' . rand(10000, 99999);
                }
                $random_pass = wp_generate_password(12, true);
                $user_id = wp_create_user($username, $random_pass, $email);

                if (!is_wp_error($user_id)) {
                    wp_update_user(array('ID' => $user_id, 'display_name' => $name));
                    $u = new WP_User($user_id);
                    $u->set_role(!empty($role) ? $role : 'sportedia_customer');
                    if (empty($employee_id)) {
                        $employee_id = Sportedia_User_Manager::get_next_member_id('MEM-');
                    }
                    update_user_meta($user_id, 'sportedia_employee_id', $employee_id);
                    update_user_meta($user_id, 'sportedia_status', 'active');
                    $imported_count++;
                } else {
                    $invalid_count++;
                }
            } else {
                $invalid_count++;
            }
        }

        fclose($handle);
        Sportedia_DB::log_activity('csv_import', 'Imported ' . $imported_count . ' users via CSV.');

        $msg = sprintf('Import Complete: %d imported successfully, %d skipped (duplicates), %d invalid rows.', $imported_count, $skipped_count, $invalid_count);
        wp_send_json_success($msg);
    }
}
