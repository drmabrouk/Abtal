<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Auth {

    public function __construct() {
        add_action('init', array($this, 'handle_login_submission'));
        add_action('init', array($this, 'handle_member_access_submission'));
        add_action('wp_logout', array($this, 'handle_logout_redirect'));
        add_filter('authenticate', array($this, 'authenticate_employee_id_or_email'), 20, 3);

        // Forgot password & OTP verification hooks
        add_action('wp_ajax_nopriv_sportedia_forgot_password_request', array($this, 'ajax_forgot_password_request'));
        add_action('wp_ajax_sportedia_forgot_password_request', array($this, 'ajax_forgot_password_request'));

        add_action('wp_ajax_nopriv_sportedia_verify_otp', array($this, 'ajax_verify_otp'));
        add_action('wp_ajax_sportedia_verify_otp', array($this, 'ajax_verify_otp'));

        add_action('wp_ajax_nopriv_sportedia_reset_password', array($this, 'ajax_reset_password'));
        add_action('wp_ajax_sportedia_reset_password', array($this, 'ajax_reset_password'));
    }

    public function ajax_forgot_password_request() {
        check_ajax_referer('sportedia_auth_nonce', 'nonce');

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        if (empty($email) || !is_email($email)) {
            wp_send_json_error('Please enter a valid email address.');
        }

        $user = get_user_by('email', $email);

        // Neutral response to avoid user enumeration if user not found or is a customer
        if (!$user) {
            wp_send_json_success('If your email is registered in our system, a 6-digit verification code has been sent.');
        }

        // Rate limiting check: max 1 request per 60 seconds
        $last_sent = get_user_meta($user->ID, 'sportedia_otp_last_sent', true);
        if ($last_sent && (time() - intval($last_sent)) < 60) {
            wp_send_json_error('Please wait 60 seconds before requesting another code.');
        }

        // Generate cryptographically secure 6-digit OTP
        $otp = sprintf('%06d', wp_rand(100000, 999999));
        $hashed_otp = wp_hash_password($otp);
        $expires = time() + (15 * 60); // 15 minutes validity

        update_user_meta($user->ID, 'sportedia_otp_hash', $hashed_otp);
        update_user_meta($user->ID, 'sportedia_otp_expires', $expires);
        update_user_meta($user->ID, 'sportedia_otp_attempts', 0);
        update_user_meta($user->ID, 'sportedia_otp_last_sent', time());

        // Send Sportedia branded HTML Email
        $site_name = Sportedia_Settings_Manager::get_setting('site_name', 'Sportedia');
        $subject = sprintf('%s – Staff Password Reset Verification Code', $site_name);

        $message = "
        <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;'>
            <div style='text-align: center; margin-bottom: 20px;'>
                <div style='display: inline-block; width: 44px; height: 44px; background: #0f172a; color: #ffffff; border-radius: 10px; font-size: 22px; font-weight: bold; line-height: 44px;'>S</div>
                <h2 style='margin: 10px 0 2px 0; color: #0f172a;'>$site_name</h2>
                <p style='margin: 0; font-size: 13px; color: #64748b;'>Sports Management System</p>
            </div>
            <p style='font-size: 14px; color: #334155;'>Hello " . esc_html($user->display_name) . ",</p>
            <p style='font-size: 14px; color: #334155;'>We received a password reset request for your staff account. Use the verification code below to proceed:</p>
            <div style='background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px; text-align: center; margin: 20px 0;'>
                <span style='font-size: 28px; font-weight: bold; letter-spacing: 6px; color: #0f172a; font-family: monospace;'>$otp</span>
            </div>
            <p style='font-size: 12px; color: #64748b;'>This code is valid for 15 minutes and can only be used once. If you did not request a password reset, please ignore this message.</p>
            <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
            <p style='font-size: 11px; color: #94a3b8; text-align: center;'>Sportedia Sports Management System &copy; " . date('Y') . "</p>
        </div>
        ";

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($email, $subject, $message, $headers);

        Sportedia_DB::log_activity('password_reset_otp_sent', 'Generated password reset OTP for staff email ' . $email, $user->ID);

        wp_send_json_success('If your email is registered in our system, a 6-digit verification code has been sent.');
    }

    public function ajax_verify_otp() {
        check_ajax_referer('sportedia_auth_nonce', 'nonce');

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $otp   = isset($_POST['otp']) ? sanitize_text_field($_POST['otp']) : '';

        if (empty($email) || empty($otp) || strlen($otp) !== 6) {
            wp_send_json_error('Invalid verification code format.');
        }

        $user = get_user_by('email', $email);
        if (!$user) {
            wp_send_json_error('Invalid email or verification code.');
        }

        $attempts = intval(get_user_meta($user->ID, 'sportedia_otp_attempts', true));
        if ($attempts >= 5) {
            wp_send_json_error('Too many failed attempts. Please request a new verification code.');
        }

        $expires = intval(get_user_meta($user->ID, 'sportedia_otp_expires', true));
        if (time() > $expires) {
            wp_send_json_error('Verification code has expired. Please request a new code.');
        }

        $hashed_otp = get_user_meta($user->ID, 'sportedia_otp_hash', true);
        if (!wp_check_password($otp, $hashed_otp)) {
            update_user_meta($user->ID, 'sportedia_otp_attempts', $attempts + 1);
            wp_send_json_error('Incorrect verification code. Please check your email.');
        }

        // Invalidate single-use OTP
        delete_user_meta($user->ID, 'sportedia_otp_hash');
        delete_user_meta($user->ID, 'sportedia_otp_expires');

        // Generate one-time reset token valid for 10 minutes
        $reset_token = wp_generate_password(32, false);
        update_user_meta($user->ID, 'sportedia_reset_token', wp_hash_password($reset_token));
        update_user_meta($user->ID, 'sportedia_reset_token_expires', time() + (10 * 60));

        wp_send_json_success(array(
            'message'     => 'Verification successful.',
            'reset_token' => $reset_token
        ));
    }

    public function ajax_reset_password() {
        check_ajax_referer('sportedia_auth_nonce', 'nonce');

        $email       = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $reset_token = isset($_POST['reset_token']) ? sanitize_text_field($_POST['reset_token']) : '';
        $password    = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($email) || empty($reset_token) || empty($password)) {
            wp_send_json_error('Required fields missing.');
        }

        if (strlen($password) < 6) {
            wp_send_json_error('Password must be at least 6 characters long.');
        }

        $user = get_user_by('email', $email);
        if (!$user) {
            wp_send_json_error('Invalid password reset request.');
        }

        $token_expires = intval(get_user_meta($user->ID, 'sportedia_reset_token_expires', true));
        if (time() > $token_expires) {
            wp_send_json_error('Password reset session has expired. Please restart the process.');
        }

        $hashed_token = get_user_meta($user->ID, 'sportedia_reset_token', true);
        if (!wp_check_password($reset_token, $hashed_token)) {
            wp_send_json_error('Invalid or expired password reset token.');
        }

        // Save new password securely
        wp_set_password($password, $user->ID);

        // Invalidate reset tokens and sessions
        delete_user_meta($user->ID, 'sportedia_reset_token');
        delete_user_meta($user->ID, 'sportedia_reset_token_expires');

        Sportedia_DB::log_activity('password_reset_success', 'Successfully reset staff password for ' . $user->display_name, $user->ID);

        wp_send_json_success('Your password has been updated successfully.');
    }

    public function authenticate_employee_id_or_email($user, $username, $password) {
        if (empty($username) || empty($password)) {
            return $user;
        }

        // 1. Try finding user by Employee ID custom meta
        $users = get_users(array(
            'meta_key'   => 'sportedia_employee_id',
            'meta_value' => $username,
            'number'     => 1,
        ));

        if (!empty($users)) {
            $matched_user = $users[0];
            if (wp_check_password($password, $matched_user->user_pass, $matched_user->ID)) {
                return $matched_user;
            }
        }

        // 2. Try finding user by email
        if (is_email($username)) {
            $user_by_email = get_user_by('email', $username);
            if ($user_by_email && wp_check_password($password, $user_by_email->user_pass, $user_by_email->ID)) {
                return $user_by_email;
            }
        }

        return $user;
    }

    public function handle_login_submission() {
        if (isset($_POST['sportedia_login_submit'])) {
            if (!isset($_POST['sportedia_login_nonce']) || !wp_verify_nonce($_POST['sportedia_login_nonce'], 'sportedia_login_action')) {
                wp_die('Security verification failed.');
            }

            $login_identifier = sanitize_text_field($_POST['log']);
            $password         = $_POST['pwd'];

            $creds = array(
                'user_login'    => $login_identifier,
                'user_password' => $password,
                'remember'      => true,
            );

            $user = wp_signon($creds, false);

            if (is_wp_error($user)) {
                $page_id = get_option('sportedia_page_id');
                $redirect_url = add_query_arg('login_error', urlencode($user->get_error_message()), get_permalink($page_id));
                wp_safe_redirect($redirect_url);
                exit;
            } else {
                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID, true);
                $page_id = get_option('sportedia_page_id');
                wp_safe_redirect(get_permalink($page_id));
                exit;
            }
        }
    }

    public function handle_member_access_submission() {
        if (isset($_POST['sportedia_member_access_submit'])) {
            if (!isset($_POST['sportedia_member_nonce']) || !wp_verify_nonce($_POST['sportedia_member_nonce'], 'sportedia_member_action')) {
                wp_die('Security verification failed.');
            }

            $member_id_input = sanitize_text_field($_POST['member_id_input']);
            $member_dob      = isset($_POST['member_dob']) ? sanitize_text_field($_POST['member_dob']) : '';

            $barcode = trim($member_id_input, " *\t\n\r\0\x0B");

            $users = get_users(array(
                'meta_key'   => 'sportedia_employee_id',
                'meta_value' => $barcode,
                'number'     => 1,
            ));

            if (empty($users)) {
                $u_obj = get_user_by('login', $barcode);
                if (!$u_obj) $u_obj = get_user_by('email', $barcode);
                if (!$u_obj && is_numeric($barcode)) $u_obj = get_user_by('id', intval($barcode));
                if ($u_obj) $users = array($u_obj);
            }

            $page_id = get_option('sportedia_page_id');

            if (empty($users)) {
                $redirect_url = add_query_arg('login_error', urlencode('Member ID or Barcode not recognized.'), get_permalink($page_id));
                wp_safe_redirect($redirect_url);
                exit;
            }

            $member = $users[0];
            $emp_id = get_user_meta($member->ID, 'sportedia_employee_id', true);
            if (empty($emp_id)) $emp_id = 'MEM-' . $member->ID;

            // Verify DOB if provided
            if (!empty($member_dob)) {
                $saved_dob = get_user_meta($member->ID, 'sportedia_dob', true);
                if (!empty($saved_dob) && $saved_dob !== $member_dob) {
                    $redirect_url = add_query_arg('login_error', urlencode('Date of birth does not match member records.'), get_permalink($page_id));
                    wp_safe_redirect($redirect_url);
                    exit;
                }
            }

            // Redirect directly to Member Portal
            $portal_page_id = get_option('sportedia_member_page_id');
            $portal_url = $portal_page_id ? get_permalink($portal_page_id) : home_url('/sportedia-member/');
            $redirect_url = add_query_arg('sportedia_member_access', urlencode($emp_id), $portal_url);

            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    public function handle_logout_redirect() {
        $page_id = get_option('sportedia_page_id');
        $redirect_url = $page_id ? get_permalink($page_id) : home_url('/sportedia/');
        wp_safe_redirect($redirect_url);
        exit;
    }
}
