<?php
if (!defined('ABSPATH')) exit;

class Abtal_Employee_Manager {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function generate_employee_id($branch_prefix = 'BR1') {
        global $wpdb;
        $table = $wpdb->prefix . 'abtal_employees';

        $prefix = strtoupper(trim($branch_prefix));
        if (empty($prefix) || strlen($prefix) !== 3) {
            $prefix = 'BR1';
        }

        $max_seq = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(seq_num) FROM $table WHERE branch_prefix = %s",
            $prefix
        ));

        $next_seq = intval($max_seq) + 1;
        $formatted_id = sprintf('%s-%03d', $prefix, $next_seq);

        return array(
            'employee_id'   => $formatted_id,
            'branch_prefix' => $prefix,
            'seq_num'       => $next_seq
        );
    }

    /**
     * Code 39 Barcode Generator for standard scannability
     */
    public static function generate_barcode_svg($code_text) {
        $text = strtoupper(trim($code_text));
        if (empty($text)) $text = 'BR1-001';

        $code39_patterns = array(
            '0' => '101001101101', '1' => '110100101011', '2' => '101100101011', '3' => '110110010101',
            '4' => '101001101011', '5' => '110100110101', '6' => '101100110101', '7' => '101001011011',
            '8' => '110100101101', '9' => '101100101101', 'A' => '110101001011', 'B' => '101101001011',
            'C' => '110110100101', 'D' => '101011001011', 'E' => '110101100101', 'F' => '101101100101',
            'G' => '101010011011', 'H' => '110101001101', 'I' => '101101001101', 'J' => '101011001101',
            'K' => '110101010011', 'L' => '101101010011', 'M' => '110110101001', 'N' => '101011010011',
            'O' => '110101101001', 'P' => '101101101001', 'Q' => '101010110011', 'R' => '110101011001',
            'S' => '101101011001', 'T' => '101011011001', 'U' => '110010101011', 'V' => '100110101011',
            'W' => '110011010101', 'X' => '100101101011', 'Y' => '110010110101', 'Z' => '100110110101',
            '-' => '100101011011', '.' => '110010101101', ' ' => '100110101101', '*' => '100101101101'
        );

        $formatted = '*' . $text . '*';
        $pattern = '';

        for ($i = 0; $i < strlen($formatted); $i++) {
            $char = $formatted[$i];
            if (isset($code39_patterns[$char])) {
                $pattern .= $code39_patterns[$char] . '0';
            }
        }

        $bar_width = 2;
        $height = 50;
        $total_width = strlen($pattern) * $bar_width + 20;

        $svg_bars = '';
        for ($j = 0; $j < strlen($pattern); $j++) {
            if ($pattern[$j] === '1') {
                $x = 10 + ($j * $bar_width);
                $svg_bars .= sprintf('<rect x="%d" y="10" width="%d" height="%d" fill="#000000" />', $x, $bar_width, $height);
            }
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d 80" width="100%%" height="60"><g>%s</g><text x="50%%" y="74" text-anchor="middle" font-family="monospace" font-size="12" font-weight="bold" fill="#000">%s</text></svg>',
            $total_width,
            $svg_bars,
            esc_html($text)
        );
    }

    public function __construct() {
        add_action('wp_ajax_abtal_save_employee', array($this, 'ajax_save_employee'));
        add_action('wp_ajax_abtal_delete_employee', array($this, 'ajax_delete_employee'));
    }

    public function ajax_save_employee() {
        check_ajax_referer('abtal_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'abtal_employees';

        $id            = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $user_id       = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $display_name  = sanitize_text_field($_POST['display_name']);
        $email         = sanitize_email($_POST['email']);
        $phone         = sanitize_text_field($_POST['phone']);
        $branch_prefix = strtoupper(sanitize_text_field($_POST['branch_prefix']));
        $job_title     = sanitize_text_field($_POST['job_title']);
        $department    = sanitize_text_field($_POST['department']);
        $salary        = floatval($_POST['salary']);
        $status        = sanitize_text_field($_POST['status']);
        $password      = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($display_name) || empty($email)) {
            wp_send_json_error('Employee name and email are required.');
        }

        if (strlen($branch_prefix) !== 3) {
            $branch_prefix = 'BR1';
        }

        if ($user_id > 0) {
            $user_data = array('ID' => $user_id, 'display_name' => $display_name, 'user_email' => $email);
            if (!empty($password)) $user_data['user_pass'] = $password;
            $updated = wp_update_user($user_data);
            if (is_wp_error($updated)) {
                wp_send_json_error($updated->get_error_message());
            }
        } else {
            $existing_user = get_user_by('email', $email);
            if ($existing_user) {
                $user_id = $existing_user->ID;
            } else {
                if (empty($password)) $password = wp_generate_password(12, true);
                $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $display_name));
                if (empty($username) || username_exists($username)) {
                    $username = 'emp_' . rand(1000, 9999);
                }
                $user_id = wp_create_user($username, $password, $email);
                if (is_wp_error($user_id)) {
                    wp_send_json_error('Failed to create user: ' . $user_id->get_error_message());
                }
                $u = new WP_User($user_id);
                $u->set_role('subscriber');
                wp_update_user(array('ID' => $user_id, 'display_name' => $display_name));
            }
        }

        if ($id > 0) {
            $wpdb->update($table, array(
                'job_title'  => $job_title,
                'department' => $department,
                'phone'      => $phone,
                'salary'     => $salary,
                'status'     => $status,
            ), array('id' => $id));

            wp_send_json_success('Employee record updated successfully.');
        } else {
            $emp_data = self::generate_employee_id($branch_prefix);
            $employee_id = $emp_data['employee_id'];
            $seq_num     = $emp_data['seq_num'];
            $barcode     = $employee_id;

            $wpdb->insert($table, array(
                'user_id'       => $user_id,
                'employee_id'   => $employee_id,
                'branch_prefix' => $branch_prefix,
                'seq_num'       => $seq_num,
                'job_title'     => $job_title,
                'department'    => $department,
                'phone'         => $phone,
                'barcode'       => $barcode,
                'salary'        => $salary,
                'status'        => $status,
            ));

            update_user_meta($user_id, 'abtal_employee_id', $employee_id);
            update_user_meta($user_id, 'abtal_phone', $phone);

            wp_send_json_success(array(
                'message'     => 'Employee created and user linked successfully.',
                'employee_id' => $employee_id,
                'user_id'     => $user_id
            ));
        }
    }

    public function ajax_delete_employee() {
        check_ajax_referer('abtal_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'abtal_employees';
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($id > 0) {
            $wpdb->delete($table, array('id' => $id), array('%d'));
            wp_send_json_success('Employee record deleted.');
        }

        wp_send_json_error('Invalid employee record ID.');
    }

    public static function get_employees($search = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'abtal_employees';

        if (!empty($search)) {
            $s = '%' . $wpdb->esc_like(trim($search)) . '%';
            $sql = $wpdb->prepare("SELECT e.*, u.display_name, u.user_email FROM $table e LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID WHERE e.employee_id LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s OR e.phone LIKE %s ORDER BY e.id DESC", $s, $s, $s, $s);
        } else {
            $sql = "SELECT e.*, u.display_name, u.user_email FROM $table e LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID ORDER BY e.id DESC";
        }

        return $wpdb->get_results($sql, ARRAY_A);
    }
}
