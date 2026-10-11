<?php
if (!defined('ABSPATH')) exit;

class Sportedia_User_Manager {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function get_next_member_id($prefix = '') {
        global $wpdb;
        $current_year = date('Y');
        $configured_seq = get_option('sportedia_next_member_sequence', 700);

        $meta_values = $wpdb->get_col("SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'sportedia_employee_id'");

        $max_seq = intval($configured_seq) > 0 ? intval($configured_seq) - 1 : 700;

        if (!empty($meta_values)) {
            foreach ($meta_values as $val) {
                preg_match_all('/\d+/', $val, $matches);
                if (!empty($matches[0])) {
                    foreach ($matches[0] as $num_str) {
                        $num = intval($num_str);
                        if (strlen($num_str) >= 7 && substr($num_str, 0, 4) === $current_year) {
                            $seq = intval(substr($num_str, 4));
                            if ($seq > $max_seq) {
                                $max_seq = $seq;
                            }
                        } else if ($num > $max_seq && $num < 2000000) {
                            $max_seq = $num;
                        }
                    }
                }
            }
        }

        $next_seq = $max_seq + 1;
        return $current_year . $next_seq;
    }

    public static function get_nationalities() {
        return array(
            'Emirati', 'Egyptian', 'Saudi', 'Jordanian', 'Lebanese', 'Syrian',
            'Indian', 'Pakistani', 'Filipino', 'British', 'American', 'Canadian',
            'Australian', 'French', 'German', 'Italian', 'Spanish', 'Russian',
            'Chinese', 'Japanese', 'South Korean', 'Moroccan', 'Sudanese',
            'Omani', 'Kuwaiti', 'Qatari', 'Bahraini', 'Iraqi', 'Yemeni',
            'Algerian', 'Tunisian', 'Palestine', 'Turkish', 'South African',
            'Nigerian', 'Kenyan', 'Ghanaian', 'Iranian', 'Afghan', 'Bangladeshi',
            'Sri Lankan', 'Nepalese', 'Indonesian', 'Malaysian', 'Vietnamese',
            'Brazilian', 'Argentine', 'Mexican', 'Colombian', 'Dutch',
            'Swedish', 'Swiss', 'Polish', 'Greek', 'Ukrainian'
        );
    }

    public function __construct() {
        add_action('wp_ajax_sportedia_save_user', array($this, 'ajax_save_user'));
        add_action('wp_ajax_sportedia_delete_user', array($this, 'ajax_delete_user'));
        add_action('wp_ajax_sportedia_upload_avatar', array($this, 'ajax_upload_avatar'));
        add_action('wp_ajax_sportedia_update_my_profile', array($this, 'ajax_update_my_profile'));
        add_action('wp_ajax_sportedia_search_users', array($this, 'ajax_search_users'));
    }

    public function ajax_search_users() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_users') && !Sportedia_Roles::is_sys_admin()) {
            wp_send_json_error('Unauthorized');
        }

        $search    = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $role      = isset($_POST['role_filter']) ? sanitize_text_field($_POST['role_filter']) : '';
        $branch_id = isset($_POST['branch_filter']) ? intval($_POST['branch_filter']) : 0;

        $usersList = self::get_users($search, $role, $branch_id);

        ob_start();
        if (!empty($usersList)) :
            foreach ($usersList as $u) : ?>
                <div class="sp-card" style="margin-bottom: 0; padding: 16px 20px; border-radius: var(--sp-radius); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                    <div style="display: flex; align-items: center; gap: 14px; flex: 2; min-width: 240px;">
                        <?php if (!empty($u['avatar_url'])) : ?>
                            <img src="<?php echo esc_url($u['avatar_url']); ?>" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 1px solid var(--sp-border-color);">
                        <?php else : ?>
                            <div class="sp-user-avatar" style="width: 44px; height: 44px; font-size: 16px; font-weight: 700;"><?php echo esc_html(strtoupper(substr($u['name'], 0, 1))); ?></div>
                        <?php endif; ?>
                        <div>
                            <strong style="font-size: 16px; color: var(--sp-text-main); display: block;"><?php echo esc_html($u['name']); ?></strong>
                            <span style="font-size: 11px; color: var(--sp-text-muted); font-family: monospace;">ID: <?php echo esc_html($u['employee_id']); ?> | <?php echo esc_html($u['email']); ?></span>
                        </div>
                    </div>

                    <div style="flex: 1; min-width: 160px; font-size: 12px;">
                        <span style="font-size: 10px; color: var(--sp-text-muted); display: block; text-transform: uppercase;">Role</span>
                        <strong><?php echo esc_html($u['role']); ?></strong>
                    </div>

                    <div style="flex: 1; min-width: 140px; font-size: 12px;">
                        <span style="font-size: 10px; color: var(--sp-text-muted); display: block; text-transform: uppercase;">Nationality / Phone</span>
                        <span><?php echo esc_html($u['nationality'] ? $u['nationality'] : 'N/A'); ?> | <?php echo esc_html($u['phone'] ? $u['phone'] : 'N/A'); ?></span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="sp-badge <?php echo $u['status'] === 'active' ? 'sp-badge-active' : 'sp-badge-inactive'; ?>">
                            <?php echo esc_html(ucfirst($u['status'])); ?>
                        </span>

                        <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='editUser(<?php echo json_encode($u); ?>)'>Edit Profile</button>
                        <button class="sp-btn sp-btn-secondary sp-btn-sm" style="color:#dc2626;" onclick="deleteUser(<?php echo $u['id']; ?>)">Delete</button>
                    </div>
                </div>
            <?php endforeach;
        else : ?>
            <div class="sp-card" style="text-align: center; color: var(--sp-text-muted); padding: 48px;">
                <h3>No system users found</h3>
                <p>No user accounts match your search query.</p>
            </div>
        <?php endif;
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html, 'count' => count($usersList)));
    }

    public static function get_users($search = '', $role = '', $branch_id = 0) {
        global $wpdb;

        $args = array(
            'number'  => 150,
            'orderby' => 'registered',
            'order'   => 'DESC'
        );

        if (!empty($role)) {
            $args['role'] = $role;
        }

        $wp_users = get_users($args);
        $result = array();

        foreach ($wp_users as $u) {
            $user_id = $u->ID;

            // Get Employee ID
            $emp_id = get_user_meta($user_id, 'sportedia_employee_id', true);
            if (empty($emp_id)) {
                $emp_id = 'EMP-' . str_pad($user_id, 4, '0', STR_PAD_LEFT);
            }

            // Search filtering across Name, Email, Username, and Employee ID
            if (!empty($search)) {
                $s = strtolower(trim($search));
                $match = (stripos(strtolower($u->display_name), $s) !== false) ||
                         (stripos(strtolower($u->user_email), $s) !== false) ||
                         (stripos(strtolower($u->user_login), $s) !== false) ||
                         (stripos(strtolower($emp_id), $s) !== false);

                if (!$match) {
                    continue;
                }
            }

            // Get Status
            $status = get_user_meta($user_id, 'sportedia_status', true);
            if (empty($status)) $status = 'active';

            // Get Assigned Branches
            $assigned_branches = self::get_user_branches($user_id);

            // Filter by branch if specified
            if ($branch_id > 0 && !in_array($branch_id, $assigned_branches, true)) {
                continue;
            }

            $user_role_names = array_map(function($r) {
                $role_obj = get_role($r);
                return $role_obj ? $role_obj->name : $r;
            }, (array) $u->roles);

            $schedule = get_user_meta($user_id, 'sportedia_work_schedule', true);
            if (empty($schedule) || !is_array($schedule)) {
                $schedule = array('days' => array('Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'), 'start' => '09:00', 'end' => '17:00');
            }

            $result[] = array(
                'id'            => $user_id,
                'employee_id'   => $emp_id,
                'username'      => $u->user_login,
                'name'          => $u->display_name,
                'email'         => $u->user_email,
                'phone'         => get_user_meta($user_id, 'sportedia_phone', true),
                'nationality'   => get_user_meta($user_id, 'sportedia_nationality', true),
                'gender'        => get_user_meta($user_id, 'sportedia_gender', true),
                'address'       => get_user_meta($user_id, 'sportedia_address', true),
                'emirate'       => get_user_meta($user_id, 'sportedia_emirate', true),
                'height'        => get_user_meta($user_id, 'sportedia_height', true),
                'weight'        => get_user_meta($user_id, 'sportedia_weight', true),
                'health_status' => get_user_meta($user_id, 'sportedia_health_status', true),
                'medical_notes' => get_user_meta($user_id, 'sportedia_medical_notes', true),
                'avatar_url'    => get_user_meta($user_id, 'sportedia_avatar', true),
                'work_schedule' => $schedule,
                'base_salary'   => get_user_meta($user_id, 'sportedia_base_salary', true),
                'pay_type'      => get_user_meta($user_id, 'sportedia_pay_type', true),
                'hourly_rate'   => get_user_meta($user_id, 'sportedia_hourly_rate', true),
                'role'          => implode(', ', $user_role_names),
                'role_key'      => !empty($u->roles) ? reset($u->roles) : '',
                'status'        => $status,
                'branches'      => $assigned_branches
            );
        }

        return $result;
    }

    public static function get_user_branches($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_user_branches';
        $results = $wpdb->get_col($wpdb->prepare("SELECT branch_id FROM $table WHERE user_id = %d", $user_id));
        return array_map('intval', $results);
    }

    public static function set_user_branches($user_id, $branch_ids = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_user_branches';
        $wpdb->delete($table, array('user_id' => $user_id), array('%d'));

        if (!empty($branch_ids) && is_array($branch_ids)) {
            foreach ($branch_ids as $b_id) {
                $wpdb->insert($table, array(
                    'user_id'   => $user_id,
                    'branch_id' => intval($b_id)
                ), array('%d', '%d'));
            }
        }
    }

    public function ajax_save_user() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_users') && !Sportedia_Roles::is_sys_admin()) {
            wp_send_json_error('Unauthorized');
        }

        $user_id     = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $employee_id = sanitize_text_field($_POST['employee_id']);
        $display_name= sanitize_text_field($_POST['display_name']);
        $email       = sanitize_email($_POST['email']);
        $role        = sanitize_text_field($_POST['role']);
        $status      = sanitize_text_field($_POST['status']);
        $password    = isset($_POST['password']) ? $_POST['password'] : '';
        $branch_ids  = isset($_POST['branch_ids']) && is_array($_POST['branch_ids']) ? array_map('intval', $_POST['branch_ids']) : array();

        if (empty($display_name) || empty($email) || empty($role)) {
            wp_send_json_error('Required fields missing.');
        }

        if ($user_id > 0) {
            // Update User
            $user_data = array(
                'ID'           => $user_id,
                'display_name' => $display_name,
                'user_email'   => $email,
            );
            if (!empty($password)) {
                $user_data['user_pass'] = $password;
            }
            $updated = wp_update_user($user_data);
            if (is_wp_error($updated)) {
                wp_send_json_error($updated->get_error_message());
            }

            $u = new WP_User($user_id);
            $u->set_role($role);
        } else {
            // Create New User
            if (empty($employee_id)) {
                wp_send_json_error('Employee ID is required.');
            }
            if (empty($password)) {
                wp_send_json_error('Password is required for new users.');
            }

            $username = strtolower(str_replace(' ', '', $employee_id));
            if (username_exists($username)) {
                $username = 'sp_' . $username;
            }

            $user_id = wp_create_user($username, $password, $email);
            if (is_wp_error($user_id)) {
                wp_send_json_error($user_id->get_error_message());
            }

            $u = new WP_User($user_id);
            $u->set_role($role);
            wp_update_user(array('ID' => $user_id, 'display_name' => $display_name));
        }

        update_user_meta($user_id, 'sportedia_employee_id', $employee_id);
        update_user_meta($user_id, 'sportedia_status', $status);
        if (isset($_POST['phone'])) update_user_meta($user_id, 'sportedia_phone', sanitize_text_field($_POST['phone']));
        if (isset($_POST['nationality'])) update_user_meta($user_id, 'sportedia_nationality', sanitize_text_field($_POST['nationality']));
        if (isset($_POST['gender'])) update_user_meta($user_id, 'sportedia_gender', sanitize_text_field($_POST['gender']));
        if (isset($_POST['address'])) update_user_meta($user_id, 'sportedia_address', sanitize_textarea_field($_POST['address']));
        if (isset($_POST['emirate'])) update_user_meta($user_id, 'sportedia_emirate', sanitize_text_field($_POST['emirate']));
        if (isset($_POST['height'])) update_user_meta($user_id, 'sportedia_height', sanitize_text_field($_POST['height']));
        if (isset($_POST['weight'])) update_user_meta($user_id, 'sportedia_weight', sanitize_text_field($_POST['weight']));
        if (isset($_POST['health_status'])) update_user_meta($user_id, 'sportedia_health_status', sanitize_text_field($_POST['health_status']));
        if (isset($_POST['medical_notes'])) update_user_meta($user_id, 'sportedia_medical_notes', sanitize_textarea_field($_POST['medical_notes']));

        // Salary configuration
        if (isset($_POST['base_salary'])) update_user_meta($user_id, 'sportedia_base_salary', floatval($_POST['base_salary']));
        if (isset($_POST['pay_type'])) update_user_meta($user_id, 'sportedia_pay_type', sanitize_text_field($_POST['pay_type']));
        if (isset($_POST['hourly_rate'])) update_user_meta($user_id, 'sportedia_hourly_rate', floatval($_POST['hourly_rate']));

        // Save Work Schedule
        $work_days   = isset($_POST['work_days']) && is_array($_POST['work_days']) ? array_map('sanitize_text_field', $_POST['work_days']) : array('Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday');
        $shift_start = isset($_POST['shift_start']) ? sanitize_text_field($_POST['shift_start']) : '09:00';
        $shift_end   = isset($_POST['shift_end']) ? sanitize_text_field($_POST['shift_end']) : '17:00';

        update_user_meta($user_id, 'sportedia_work_schedule', array(
            'days'  => $work_days,
            'start' => $shift_start,
            'end'   => $shift_end
        ));

        // Avatar Image Upload
        if (!empty($_FILES['avatar_file']['tmp_name'])) {
            if ($_FILES['avatar_file']['size'] > 2 * 1024 * 1024) {
                wp_send_json_error('Profile photo exceeds 2 MB size limit.');
            }
            $file_type = wp_check_filetype($_FILES['avatar_file']['name']);
            $allowed_types = array('jpg', 'jpeg', 'png', 'webp');
            if (!in_array(strtolower($file_type['ext']), $allowed_types, true)) {
                wp_send_json_error('Invalid image file format. Allowed formats: JPG, PNG, WEBP.');
            }
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_id = media_handle_upload('avatar_file', 0);
            if (!is_wp_error($attachment_id)) {
                $url = wp_get_attachment_url($attachment_id);
                update_user_meta($user_id, 'sportedia_avatar', $url);
            }
        }

        self::set_user_branches($user_id, $branch_ids);

        wp_send_json_success('User saved successfully.');
    }

    public function ajax_update_my_profile() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in.');
        }

        $user_id     = get_current_user_id();
        $name        = sanitize_text_field($_POST['display_name']);
        $email       = sanitize_email($_POST['email']);
        $password    = isset($_POST['password']) ? $_POST['password'] : '';
        $nationality = sanitize_text_field($_POST['nationality']);
        $gender      = sanitize_text_field($_POST['gender']);
        $address     = sanitize_textarea_field($_POST['address']);
        $emirate     = sanitize_text_field($_POST['emirate']);

        if (empty($name) || empty($email)) {
            wp_send_json_error('Name and email are required.');
        }

        $user_data = array(
            'ID'           => $user_id,
            'display_name' => $name,
            'user_email'   => $email
        );

        if (!empty($password)) {
            $user_data['user_pass'] = $password;
        }

        $updated = wp_update_user($user_data);
        if (is_wp_error($updated)) {
            wp_send_json_error($updated->get_error_message());
        }

        update_user_meta($user_id, 'sportedia_nationality', $nationality);
        update_user_meta($user_id, 'sportedia_gender', $gender);
        update_user_meta($user_id, 'sportedia_address', $address);
        update_user_meta($user_id, 'sportedia_emirate', $emirate);
        update_user_meta($user_id, 'sportedia_country', 'United Arab Emirates');

        if (!empty($_FILES['avatar_file']['tmp_name'])) {
            if ($_FILES['avatar_file']['size'] > 2 * 1024 * 1024) {
                wp_send_json_error('Profile photo exceeds 2 MB size limit.');
            }
            $file_type = wp_check_filetype($_FILES['avatar_file']['name']);
            $allowed_types = array('jpg', 'jpeg', 'png', 'webp');
            if (!in_array(strtolower($file_type['ext']), $allowed_types, true)) {
                wp_send_json_error('Invalid image file format. Allowed formats: JPG, PNG, WEBP.');
            }
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_id = media_handle_upload('avatar_file', 0);
            if (!is_wp_error($attachment_id)) {
                $url = wp_get_attachment_url($attachment_id);
                update_user_meta($user_id, 'sportedia_avatar', $url);
            }
        }

        wp_send_json_success('Profile updated successfully.');
    }

    public function ajax_upload_avatar() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : get_current_user_id();

        if ($user_id !== get_current_user_id() && !current_user_can('sportedia_manage_users') && !Sportedia_Roles::is_sys_admin()) {
            wp_send_json_error('Unauthorized');
        }

        if (empty($_FILES['avatar_file']['tmp_name'])) {
            wp_send_json_error('No image file selected.');
        }

        if ($_FILES['avatar_file']['size'] > 2 * 1024 * 1024) {
            wp_send_json_error('Profile photo exceeds 2 MB size limit.');
        }

        $file_type = wp_check_filetype($_FILES['avatar_file']['name']);
        $allowed_types = array('jpg', 'jpeg', 'png', 'webp');
        if (!in_array(strtolower($file_type['ext']), $allowed_types, true)) {
            wp_send_json_error('Invalid image file format. Allowed formats: JPG, PNG, WEBP.');
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $attachment_id = media_handle_upload('avatar_file', 0);
        if (is_wp_error($attachment_id)) {
            wp_send_json_error($attachment_id->get_error_message());
        }

        $url = wp_get_attachment_url($attachment_id);
        update_user_meta($user_id, 'sportedia_avatar', $url);

        wp_send_json_success(array('message' => 'Profile photo updated.', 'avatar_url' => $url));
    }

    public function ajax_delete_user() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_users') && !Sportedia_Roles::is_sys_admin()) {
            wp_send_json_error('Unauthorized');
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

        if ($user_id === get_current_user_id()) {
            wp_send_json_error('Cannot delete yourself.');
        }

        require_once(ABSPATH . 'wp-admin/includes/user.php');

        // Remove branch associations
        self::set_user_branches($user_id, array());

        // Clean delete WP User to prevent orphaned accounts
        $deleted = wp_delete_user($user_id);

        if ($deleted) {
            wp_send_json_success('User permanently deleted.');
        } else {
            wp_send_json_error('Failed to delete user.');
        }
    }
}
