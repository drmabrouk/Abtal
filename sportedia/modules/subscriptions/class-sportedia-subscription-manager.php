<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Subscription_Manager {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_sportedia_save_subscription', array($this, 'ajax_save_subscription'));
        add_action('wp_ajax_sportedia_delete_subscription', array($this, 'ajax_delete_subscription'));
        add_action('wp_ajax_sportedia_verify_member_session', array($this, 'ajax_verify_member_session'));
        add_action('wp_ajax_nopriv_sportedia_verify_member_session', array($this, 'ajax_verify_member_session'));
        add_action('wp_ajax_sportedia_search_subscriptions', array($this, 'ajax_search_subscriptions'));
    }

    public function ajax_search_subscriptions() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        $search        = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $branch_filter = isset($_POST['branch_filter']) ? intval($_POST['branch_filter']) : 0;
        $status_filter = isset($_POST['status_filter']) ? sanitize_text_field($_POST['status_filter']) : '';

        $subscriptions = self::get_subscriptions($search, $branch_filter, $status_filter);

        ob_start();
        if (!empty($subscriptions)) :
            foreach ($subscriptions as $s) : ?>
                <div class="sp-card" style="margin-bottom: 0; padding: 16px 20px; border-radius: var(--sp-radius); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                    <div style="flex: 2; min-width: 220px;">
                        <strong style="font-size: 15px; color: var(--sp-text-main); display: block;"><?php echo esc_html($s['member_name']); ?></strong>
                        <span style="font-size: 11px; color: var(--sp-text-muted); font-family: monospace;">ID: <?php echo esc_html($s['employee_id']); ?></span>
                    </div>

                    <div style="flex: 2; min-width: 200px;">
                        <span style="font-size: 10px; color: var(--sp-text-muted); display: block; text-transform: uppercase;">Plan & Type</span>
                        <strong style="font-size: 13px; color: #000;"><?php echo esc_html($s['plan_name']); ?></strong>
                        <span style="font-size: 11px; color: var(--sp-text-muted); display: block;"><?php echo esc_html(ucfirst($s['subscription_type'])); ?> | <?php echo esc_html($s['branch_name']); ?></span>
                    </div>

                    <div style="flex: 2; min-width: 180px;">
                        <span style="font-size: 10px; color: var(--sp-text-muted); display: block; text-transform: uppercase;">Validity Period</span>
                        <span style="font-size: 12px; font-family: monospace; color: var(--sp-text-main);"><?php echo esc_html($s['start_date'] . ' &rarr; ' . $s['end_date']); ?></span>
                    </div>

                    <div style="flex: 1; min-width: 130px;">
                        <span style="font-size: 10px; color: var(--sp-text-muted); display: block; text-transform: uppercase;">Price</span>
                        <strong style="font-size: 14px; color: #000;"><?php echo esc_html(Sportedia_Finance::format_price($s['price'])); ?></strong>
                    </div>

                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="sp-badge <?php echo $s['status'] === 'active' ? 'sp-badge-active' : 'sp-badge-inactive'; ?>">
                            <?php echo esc_html(ucfirst($s['status'])); ?>
                        </span>

                        <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='viewA5Invoice(<?php echo json_encode($s); ?>)' title="Print Invoice">Invoice</button>
                        <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='viewA6Card(<?php echo json_encode($s); ?>)' title="Membership Card">Card</button>
                        <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='editSub(<?php echo json_encode($s); ?>)'>Edit</button>
                        <button class="sp-btn sp-btn-secondary sp-btn-sm" style="color:#dc2626;" onclick="deleteSub(<?php echo $s['id']; ?>)">Delete</button>
                    </div>
                </div>
            <?php endforeach;
        else : ?>
            <div class="sp-card" style="text-align: center; color: var(--sp-text-muted); padding: 48px;">
                <h3>No subscriptions found</h3>
                <p>No subscription records match your search criteria.</p>
            </div>
        <?php endif;
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html, 'count' => count($subscriptions)));
    }

    public static function auto_update_expired_subscriptions() {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_subscriptions';
        $today = date('Y-m-d');
        $wpdb->query($wpdb->prepare(
            "UPDATE $table SET status = 'expired' WHERE status = 'active' AND end_date < %s",
            $today
        ));
    }

    public static function get_subscriptions($search = '', $branch_id = 0, $status = '') {
        global $wpdb;
        self::auto_update_expired_subscriptions();
        $table = $wpdb->prefix . 'sportedia_subscriptions';

        $where = array('1=1');
        $params = array();

        if ($branch_id > 0) {
            $where[] = '(s.branch_id = %d OR s.branch_id = 0)';
            $params[] = $branch_id;
        }

        if (!empty($status)) {
            $where[] = 's.status = %s';
            $params[] = $status;
        }

        $where_sql = implode(' AND ', $where);

        if (!empty($params)) {
            $sql = $wpdb->prepare("SELECT s.* FROM $table s WHERE $where_sql ORDER BY s.id DESC", $params);
        } else {
            $sql = "SELECT s.* FROM $table s WHERE $where_sql ORDER BY s.id DESC";
        }

        $results = $wpdb->get_results($sql, ARRAY_A);
        $subscriptions = array();

        foreach ($results as $row) {
            $user = get_userdata($row['user_id']);
            $user_name  = $user ? $user->display_name : 'Unknown Member';
            $emp_id     = get_user_meta($row['user_id'], 'sportedia_employee_id', true);
            $user_phone = get_user_meta($row['user_id'], 'sportedia_phone', true);
            $user_dob   = get_user_meta($row['user_id'], 'sportedia_dob', true);

            $coach_user = !empty($row['coach_id']) ? get_userdata($row['coach_id']) : null;
            $coach_name = $coach_user ? $coach_user->display_name : 'Unassigned Coach';

            $branch_name = 'All Branches';
            if ($row['branch_id'] > 0) {
                $b = $wpdb->get_row($wpdb->prepare("SELECT branch_name FROM {$wpdb->prefix}sportedia_branches WHERE id = %d", $row['branch_id']));
                if ($b) $branch_name = $b->branch_name;
            }

            if (!empty($search)) {
                $s = strtolower(trim($search));
                $match = (stripos(strtolower($user_name), $s) !== false) ||
                         (stripos(strtolower($row['plan_name']), $s) !== false) ||
                         (stripos(strtolower($emp_id), $s) !== false) ||
                         (stripos(strtolower($user_phone), $s) !== false) ||
                         (!empty($row['invoice_number']) && stripos(strtolower($row['invoice_number']), $s) !== false);

                if (!$match) {
                    continue;
                }
            }

            $row['member_name']  = $user_name;
            $row['employee_id']  = $emp_id;
            $row['member_phone'] = $user_phone;
            $row['member_dob']   = $user_dob ? $user_dob : 'N/A';
            $row['coach_name']   = $coach_name;
            $row['branch_name']  = $branch_name;
            $subscriptions[]     = $row;
        }

        return $subscriptions;
    }

    public function ajax_save_subscription() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_subscriptions') && !Sportedia_Roles::is_sys_admin()) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_subscriptions';

        $sub_id         = isset($_POST['sub_id']) ? intval($_POST['sub_id']) : 0;
        $is_renewal     = isset($_POST['is_renewal']) && $_POST['is_renewal'] === '1';
        $user_id        = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $branch_id      = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        $program_id     = isset($_POST['program_id']) ? intval($_POST['program_id']) : 0;
        $coach_id       = isset($_POST['coach_id']) ? intval($_POST['coach_id']) : 0;
        $plan_name      = sanitize_text_field($_POST['plan_name']);
        $sub_type       = sanitize_text_field($_POST['subscription_type']);
        $start_date     = sanitize_text_field($_POST['start_date']);
        $end_date       = sanitize_text_field($_POST['end_date']);
        $price          = floatval($_POST['price']);
        $notes          = sanitize_textarea_field($_POST['notes']);
        $status         = sanitize_text_field($_POST['status']);

        // New member inputs
        $member_name    = sanitize_text_field($_POST['member_name']);
        $member_phone   = sanitize_text_field($_POST['member_phone']);
        $member_dob     = sanitize_text_field($_POST['member_dob']);
        $member_id      = sanitize_text_field($_POST['member_id']);
        $member_email   = sanitize_email($_POST['member_email']);
        $member_pass    = isset($_POST['member_password']) ? $_POST['member_password'] : '';

        if ($sub_id === 0) {
            if ($is_renewal) {
                if ($user_id <= 0) {
                    wp_send_json_error('Select or find an existing member for subscription renewal.');
                }
                $existing_user = get_userdata($user_id);
                $member_name = $existing_user ? $existing_user->display_name : 'Member';
                $member_phone = get_user_meta($user_id, 'sportedia_phone', true);
                $member_id = get_user_meta($user_id, 'sportedia_employee_id', true);
            } else {
                // New Member Creation
                if (empty($member_name) || empty($member_phone)) {
                    wp_send_json_error('Member full name and mobile number are required.');
                }

                if (empty($member_id)) {
                    $member_id = Sportedia_User_Manager::get_next_member_id('MEM-');
                }

                // Create WP User account so member appears in System Users
                $username = strtolower(str_replace(' ', '', $member_id));
                if (username_exists($username)) {
                    $username = 'sp_' . $username . '_' . rand(10, 99);
                }

                if (empty($member_email)) {
                    $member_email = $username . '@sportedia.online';
                }

                if (empty($member_pass)) {
                    $member_pass = 'Sp#' . rand(100000, 999999);
                }

                $new_user_id = wp_create_user($username, $member_pass, $member_email);
                if (is_wp_error($new_user_id)) {
                    wp_send_json_error('Failed to create member account: ' . $new_user_id->get_error_message());
                }

                $user_id = $new_user_id;
                $u = new WP_User($user_id);
                $u->set_role('sportedia_customer');
                wp_update_user(array('ID' => $user_id, 'display_name' => $member_name));

                update_user_meta($user_id, 'sportedia_employee_id', $member_id);
                update_user_meta($user_id, 'sportedia_phone', $member_phone);
                if (!empty($member_dob)) update_user_meta($user_id, 'sportedia_dob', $member_dob);
                update_user_meta($user_id, 'sportedia_status', 'active');

                if ($branch_id > 0) {
                    Sportedia_User_Manager::set_user_branches($user_id, array($branch_id));
                }
            }
        }

        if ($user_id <= 0 || empty($plan_name) || empty($start_date) || empty($end_date)) {
            wp_send_json_error('Please fill in all required subscription fields.');
        }

        $sessions_count = 12;
        if ($program_id > 0) {
            $prog_row = $wpdb->get_row($wpdb->prepare("SELECT sessions_count, coach_id FROM {$wpdb->prefix}sportedia_programs WHERE id = %d", $program_id));
            if ($prog_row) {
                if (intval($prog_row->sessions_count) > 0) $sessions_count = intval($prog_row->sessions_count);
                if ($coach_id <= 0) $coach_id = intval($prog_row->coach_id);
            }
        }
        if (isset($_POST['sessions_count']) && intval($_POST['sessions_count']) > 0) {
            $sessions_count = intval($_POST['sessions_count']);
        }

        $invoice_number = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);

        $coach_user = $coach_id > 0 ? get_userdata($coach_id) : null;
        $coach_name = $coach_user ? $coach_user->display_name : 'Assigned Coach';

        $branch_obj = $branch_id > 0 ? $wpdb->get_row($wpdb->prepare("SELECT branch_name FROM {$wpdb->prefix}sportedia_branches WHERE id = %d", $branch_id)) : null;
        $branch_name = $branch_obj ? $branch_obj->branch_name : 'All Branches';

        $data = array(
            'invoice_number'    => $invoice_number,
            'user_id'           => $user_id,
            'branch_id'         => $branch_id,
            'program_id'        => $program_id,
            'coach_id'          => $coach_id,
            'plan_name'         => $plan_name,
            'subscription_type' => $sub_type,
            'sessions_count'    => $sessions_count,
            'start_date'        => $start_date,
            'end_date'          => $end_date,
            'price'             => $price,
            'notes'             => $notes,
            'status'            => $status,
        );

        $format = array('%s', '%d', '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%f', '%s', '%s');

        if ($sub_id > 0) {
            unset($data['invoice_number']);
            unset($format[0]);
            $wpdb->update($table, $data, array('id' => $sub_id), array_values($format), array('%d'));
            $res_invoice = $wpdb->get_var($wpdb->prepare("SELECT invoice_number FROM $table WHERE id = %d", $sub_id));
            if (!empty($res_invoice)) $invoice_number = $res_invoice;
        } else {
            $wpdb->insert($table, $data, $format);
            $sub_id = $wpdb->insert_id;
        }

        $fin_calc = Sportedia_Finance::calculate_vat($price);

        wp_send_json_success(array(
            'message'        => 'Subscription saved successfully.',
            'sub_id'         => $sub_id,
            'invoice_number' => $invoice_number,
            'user_id'        => $user_id,
            'member_name'    => $member_name,
            'member_phone'   => $member_phone,
            'member_dob'     => !empty($member_dob) ? $member_dob : 'N/A',
            'member_id'      => $member_id,
            'branch_name'    => $branch_name,
            'coach_name'     => $coach_name,
            'plan_name'      => $plan_name,
            'sessions_count' => $sessions_count,
            'start_date'     => $start_date,
            'end_date'       => $end_date,
            'base_price'     => Sportedia_Finance::format_price($fin_calc['base']),
            'vat'            => Sportedia_Finance::format_price($fin_calc['vat']),
            'total'          => Sportedia_Finance::format_price($fin_calc['total']),
        ));
    }

    public function ajax_verify_member_session() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        $current_u = wp_get_current_user();
        $allowed_roles = array(
            'sportedia_sys_admin',
            'sportedia_general_mgr',
            'sportedia_facility_mgr',
            'sportedia_ops_mgr',
            'sportedia_booking_mgr',
            'sportedia_coach',
            'administrator'
        );
        $user_roles = (array) $current_u->roles;
        $has_perm = false;
        foreach ($user_roles as $r) {
            if (in_array($r, $allowed_roles, true) || current_user_can('manage_options')) {
                $has_perm = true;
                break;
            }
        }
        if (!$has_perm) {
            wp_send_json_error('Access Denied. Session verification is restricted to authorized Sportedia staff, managers, and coaches.');
        }

        $raw_barcode = isset($_POST['member_barcode']) ? sanitize_text_field($_POST['member_barcode']) : '';
        $barcode = trim($raw_barcode, " *\t\n\r\0\x0B");
        $force_scan = isset($_POST['force']) && $_POST['force'] === '1';
        $req_sub_id = isset($_POST['subscription_id']) ? intval($_POST['subscription_id']) : 0;

        if (empty($barcode)) {
            wp_send_json_error('Please scan or enter a valid Member ID / Barcode.');
        }

        // Search user by sportedia_employee_id, sportedia_phone, login, email, or ID
        $users = get_users(array(
            'meta_key'   => 'sportedia_employee_id',
            'meta_value' => $barcode,
            'number'     => 1,
        ));

        if (empty($users)) {
            $users = get_users(array(
                'meta_key'   => 'sportedia_phone',
                'meta_value' => $barcode,
                'number'     => 1,
            ));
        }

        if (empty($users)) {
            $user_obj = get_user_by('login', $barcode);
            if (!$user_obj) $user_obj = get_user_by('email', $barcode);
            if (!$user_obj && is_numeric($barcode)) $user_obj = get_user_by('id', intval($barcode));
            if ($user_obj) $users = array($user_obj);
        }

        if (empty($users)) {
            wp_send_json_error('No member account found for Member ID / Barcode: ' . $barcode);
        }

        $member = $users[0];
        $member_id = $member->ID;
        $emp_id = get_user_meta($member_id, 'sportedia_employee_id', true);
        if (empty($emp_id)) $emp_id = 'MEM-' . $member_id;

        global $wpdb;
        $subs_table = $wpdb->prefix . 'sportedia_subscriptions';
        $att_table  = $wpdb->prefix . 'sportedia_attendance';

        self::auto_update_expired_subscriptions();

        $today = date('Y-m-d');

        // Check if specific sub_id requested or fetch active sub
        if ($req_sub_id > 0) {
            $sub = $wpdb->get_row($wpdb->prepare("SELECT * FROM $subs_table WHERE id = %d AND user_id = %d", $req_sub_id, $member_id), ARRAY_A);
        } else {
            $sub = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $subs_table WHERE user_id = %d AND status = 'active' AND start_date <= %s AND end_date >= %s ORDER BY id DESC LIMIT 1",
                $member_id,
                $today,
                $today
            ), ARRAY_A);
        }

        if (!$sub) {
            wp_send_json_error('No active subscription found or plan expired for member: ' . $member->display_name);
        }

        $total_sessions = intval($sub['sessions_count']);
        if ($total_sessions <= 0) $total_sessions = 12;

        $used_sessions  = intval($sub['sessions_used']);
        $remaining      = $total_sessions - $used_sessions;

        if ($remaining <= 0) {
            wp_send_json_error('No sessions remaining for member: ' . $member->display_name);
        }

        // Duplicate same-day scan check
        if (!$force_scan) {
            $same_day_scan = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $att_table WHERE user_id = %d AND program_id = %d AND attendance_date = %s",
                $member_id,
                intval($sub['program_id']),
                $today
            ));

            if ($same_day_scan) {
                wp_send_json_error(array(
                    'type'    => 'duplicate_scan_confirm',
                    'message' => 'Attendance for ' . $member->display_name . ' (' . $sub['plan_name'] . ') has already been recorded today. Record another session deduction?'
                ));
            }
        }

        // Deduct 1 session
        $new_used = $used_sessions + 1;
        $new_remaining = $total_sessions - $new_used;

        $update_data = array('sessions_used' => $new_used);
        if ($new_remaining <= 0) {
            $update_data['status'] = 'completed';
        }

        $wpdb->update($subs_table, $update_data, array('id' => $sub['id']));

        $now_time = date('Y-m-d H:i:s');

        // Record attendance entry
        $wpdb->insert($att_table, array(
            'branch_id'                => intval($sub['branch_id']),
            'program_id'               => intval($sub['program_id']),
            'user_id'                  => $member_id,
            'user_type'                => 'customer',
            'attendance_date'          => $today,
            'check_in_time'            => $now_time,
            'status'                   => 'present',
            'checked_in_by'            => get_current_user_id()
        ));

        $coach_user = !empty($sub['coach_id']) ? get_userdata($sub['coach_id']) : null;
        $coach_name = $coach_user ? $coach_user->display_name : 'Assigned Coach';

        Sportedia_DB::log_activity('member_session_verified', 'Deducted 1 session for member ' . $member->display_name . ' (' . $emp_id . '). Remaining: ' . $new_remaining, $member_id);

        wp_send_json_success(array(
            'message'            => 'Member session verified and deducted successfully.',
            'member_name'        => $member->display_name,
            'member_id'          => $emp_id,
            'subscription_id'    => $sub['id'],
            'invoice_number'     => !empty($sub['invoice_number']) ? $sub['invoice_number'] : ('SUB-' . $sub['id']),
            'plan_name'          => $sub['plan_name'],
            'sport'              => $sub['plan_name'],
            'coach_name'         => $coach_name,
            'attendance_date'    => $today,
            'attendance_time'    => date('H:i:s', strtotime($now_time)),
            'sessions_count'     => $total_sessions,
            'sessions_used'      => $new_used,
            'sessions_remaining' => $new_remaining
        ));
    }

    public function ajax_delete_subscription() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_subscriptions') && !Sportedia_Roles::is_sys_admin()) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_subscriptions';
        $sub_id = isset($_POST['sub_id']) ? intval($_POST['sub_id']) : 0;

        if ($sub_id > 0) {
            $wpdb->delete($table, array('id' => $sub_id), array('%d'));
            wp_send_json_success('Subscription deleted successfully.');
        }

        wp_send_json_error('Invalid subscription ID.');
    }
}
