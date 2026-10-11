<?php
if (!defined('ABSPATH')) exit;

$error_msg = isset($_GET['login_error']) ? sanitize_text_field(urldecode($_GET['login_error'])) : '';
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sportedia – Access System</title>
    <?php wp_head(); ?>
    <style>
        body.sp-login-body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            font-family: 'Google Sans Flex', -apple-system, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .sp-login-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            padding: 36px;
            margin: 20px;
        }
        .sp-login-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .sp-login-logo {
            width: 48px;
            height: 48px;
            background: #0f172a;
            color: #ffffff;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 10px;
        }
        .sp-login-title {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 4px 0;
            letter-spacing: -0.5px;
        }
        .sp-login-subtitle {
            font-size: 13px;
            color: #64748b;
            margin: 0;
        }
        .sp-alert-error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px 14px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .sp-login-tabs {
            display: flex;
            background: #f1f5f9;
            border-radius: 8px;
            padding: 4px;
            margin-bottom: 24px;
        }
        .sp-tab-btn {
            flex: 1;
            padding: 10px;
            border: none;
            background: transparent;
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
        }
        .sp-tab-btn.active {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .sp-input-wrapper {
            position: relative;
        }
        .sp-input-icon-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
        }
        .sp-input-icon-btn:hover {
            color: #0f172a;
        }
        .sp-legal-info-text {
            font-size: 11px;
            color: #94a3b8;
            line-height: 1.4;
            margin-top: 16px;
            text-align: center;
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
        }
        #camera_scanner_container {
            display: none;
            margin-bottom: 16px;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            text-align: center;
        }
        #camera_video {
            width: 100%;
            max-height: 220px;
            object-fit: cover;
        }
    </style>
</head>
<body class="sp-login-body">

<div class="sp-login-card">
    <div class="sp-login-header">
        <div class="sp-login-logo">S</div>
        <h1 class="sp-login-title">Sportedia</h1>
        <p class="sp-login-subtitle">System Login & Member Access Portal</p>
    </div>

    <?php if (!empty($error_msg)) : ?>
        <div class="sp-alert-error">
            <?php echo esc_html($error_msg); ?>
        </div>
    <?php endif; ?>

    <!-- LOGIN MODE TABS -->
    <div class="sp-login-tabs">
        <button type="button" class="sp-tab-btn active" id="tab_staff_btn" onclick="switchLoginTab('staff')">
            Staff Sign In
        </button>
        <button type="button" class="sp-tab-btn" id="tab_member_btn" onclick="switchLoginTab('member')">
            Member Access
        </button>
    </div>

    <!-- TAB 1: STAFF LOGIN -->
    <div id="tab_staff_content">
        <form method="post" action="">
            <?php wp_nonce_field('sportedia_login_action', 'sportedia_login_nonce'); ?>

            <div class="sp-form-group">
                <input type="text" name="log" id="user_login" class="sp-floating-input" placeholder=" " required autofocus>
                <label for="user_login" class="sp-floating-label">Employee ID or Email</label>
            </div>

            <div class="sp-form-group sp-input-wrapper">
                <input type="password" name="pwd" id="user_pass" class="sp-floating-input" placeholder=" " required style="padding-right: 42px;">
                <label for="user_pass" class="sp-floating-label">Password</label>
                <button type="button" class="sp-input-icon-btn" onclick="togglePasswordVisibility('user_pass', this)" title="Toggle password visibility">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: -8px; margin-bottom: 16px;">
                <button type="button" onclick="showForgotPasswordModal()" style="background: none; border: none; color: #0284c7; font-size: 12px; font-weight: 600; cursor: pointer; padding: 0;">
                    Forgot Password?
                </button>
            </div>

            <button type="submit" name="sportedia_login_submit" class="sp-btn sp-btn-primary" style="width: 100%; padding: 12px;">
                Sign In to Sportedia
            </button>

            <!-- INFORMATIONAL PARAGRAPH FOR STAFF LOGIN -->
            <p class="sp-legal-info-text">
                Sportedia operates in accordance with applicable policies, terms and conditions, and includes appropriate protection of user rights and system data.
            </p>
        </form>
    </div>

    <!-- FORGOT PASSWORD MODAL CONTAINER -->
    <div id="spForgotModal" class="sp-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 9999; padding: 16px;">
        <div class="sp-modal-content" style="max-width: 420px; width: 100%; background: #ffffff; border-radius: 12px; padding: 28px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); position: relative;">
            <button type="button" class="sp-modal-close" onclick="closeForgotPasswordModal()" style="position: absolute; right: 16px; top: 16px; background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>

            <!-- STEP 1: EMAIL REQUEST -->
            <div id="fp_step_1">
                <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 6px 0;">Reset Staff Password</h3>
                <p style="font-size: 12px; color: #64748b; margin: 0 0 20px 0;">Enter your registered staff email address to receive a 6-digit verification code.</p>

                <div id="fp_msg_1" style="display: none; margin-bottom: 14px; font-size: 12px; padding: 10px 12px; border-radius: 6px;"></div>

                <div class="sp-form-group">
                    <input type="email" id="fp_email" class="sp-floating-input" placeholder=" " required>
                    <label for="fp_email" class="sp-floating-label">Registered Staff Email *</label>
                </div>

                <button type="button" id="fp_btn_1" onclick="submitForgotEmail()" class="sp-btn sp-btn-primary" style="width: 100%; padding: 12px;">
                    Send 6-Digit Code
                </button>
            </div>

            <!-- STEP 2: 6-DIGIT OTP VERIFICATION -->
            <div id="fp_step_2" style="display: none;">
                <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 6px 0;">Enter Verification Code</h3>
                <p style="font-size: 12px; color: #64748b; margin: 0 0 20px 0;">We sent a 6-digit security code to your email. Enter code below.</p>

                <div id="fp_msg_2" style="display: none; margin-bottom: 14px; font-size: 12px; padding: 10px 12px; border-radius: 6px;"></div>

                <div class="sp-form-group">
                    <input type="text" id="fp_otp" maxlength="6" class="sp-floating-input" placeholder=" " style="letter-spacing: 6px; font-size: 20px; font-weight: 800; text-align: center; font-family: monospace;" required>
                    <label for="fp_otp" class="sp-floating-label" style="text-align: center; width: 100%;">6-Digit Code *</label>
                </div>

                <button type="button" id="fp_btn_2" onclick="submitVerifyOTP()" class="sp-btn sp-btn-primary" style="width: 100%; padding: 12px; margin-bottom: 10px;">
                    Verify Code
                </button>
                <div style="text-align: center;">
                    <button type="button" onclick="submitForgotEmail()" style="background: none; border: none; color: #0284c7; font-size: 11px; font-weight: 600; cursor: pointer;">Resend Code</button>
                </div>
            </div>

            <!-- STEP 3: NEW PASSWORD -->
            <div id="fp_step_3" style="display: none;">
                <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 6px 0;">Set New Password</h3>
                <p style="font-size: 12px; color: #64748b; margin: 0 0 20px 0;">Create a strong new password for your Sportedia account.</p>

                <div id="fp_msg_3" style="display: none; margin-bottom: 14px; font-size: 12px; padding: 10px 12px; border-radius: 6px;"></div>

                <div class="sp-form-group sp-input-wrapper">
                    <input type="password" id="fp_new_pass" class="sp-floating-input" placeholder=" " required style="padding-right: 42px;">
                    <label for="fp_new_pass" class="sp-floating-label">New Password *</label>
                    <button type="button" class="sp-input-icon-btn" onclick="togglePasswordVisibility('fp_new_pass', this)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>

                <div class="sp-form-group sp-input-wrapper">
                    <input type="password" id="fp_confirm_pass" class="sp-floating-input" placeholder=" " required style="padding-right: 42px;">
                    <label for="fp_confirm_pass" class="sp-floating-label">Confirm New Password *</label>
                    <button type="button" class="sp-input-icon-btn" onclick="togglePasswordVisibility('fp_confirm_pass', this)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>

                <button type="button" id="fp_btn_3" onclick="submitResetPassword()" class="sp-btn sp-btn-primary" style="width: 100%; padding: 12px;">
                    Save New Password
                </button>
            </div>
        </div>
    </div>

    <!-- TAB 2: MEMBER PORTAL ACCESS -->
    <div id="tab_member_content" style="display: none;">
        <!-- Camera Scanner Stream View -->
        <div id="camera_scanner_container">
            <video id="camera_video" autoplay playsinline></video>
            <button type="button" onclick="stopCameraScanner()" class="sp-btn sp-btn-secondary sp-btn-sm" style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.6); color: #fff; border: none;">
                Close Camera
            </button>
        </div>

        <form method="post" action="" id="spMemberAccessForm">
            <?php wp_nonce_field('sportedia_member_action', 'sportedia_member_nonce'); ?>

            <div class="sp-form-group sp-input-wrapper">
                <input type="text" name="member_id_input" id="member_id_input" class="sp-floating-input" placeholder=" " required style="padding-right: 42px;">
                <label for="member_id_input" class="sp-floating-label">Scan Barcode / Member ID *</label>
                <button type="button" class="sp-input-icon-btn" onclick="startCameraScanner()" title="Open camera barcode scanner">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                </button>
            </div>

            <div class="sp-form-group">
                <input type="date" name="member_dob" id="member_dob" class="sp-floating-input" placeholder=" ">
                <label for="member_dob" class="sp-floating-label">Date of Birth (Optional Verification)</label>
            </div>

            <button type="submit" name="sportedia_member_access_submit" class="sp-btn sp-btn-primary" style="width: 100%; padding: 12px; margin-top: 8px; background-color: #10b981; border-color: #10b981;">
                Access Member Portal
            </button>

            <!-- INFORMATIONAL NOTE FOR MEMBER ACCESS -->
            <p class="sp-legal-info-text">
                Scan your official Sportedia membership card barcode or enter your Member ID to view your active subscription balances, training schedule, and session history securely.
            </p>
        </form>
    </div>
</div>

<script>
function switchLoginTab(mode) {
    if (mode === 'member') {
        jQuery('#tab_staff_btn').removeClass('active');
        jQuery('#tab_member_btn').addClass('active');
        jQuery('#tab_staff_content').hide();
        jQuery('#tab_member_content').show();
        jQuery('#member_id_input').focus();
    } else {
        jQuery('#tab_member_btn').removeClass('active');
        jQuery('#tab_staff_btn').addClass('active');
        jQuery('#tab_member_content').hide();
        jQuery('#tab_staff_content').show();
        jQuery('#user_login').focus();
    }
}

function togglePasswordVisibility(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.45 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    }
}

var cameraStream = null;
var scannerInterval = null;

function startCameraScanner() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Camera access is not supported by your browser.');
        return;
    }

    jQuery('#camera_scanner_container').show();
    var video = document.getElementById('camera_video');

    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(function(stream) {
            cameraStream = stream;
            video.srcObject = stream;

            if ('BarcodeDetector' in window) {
                var detector = new BarcodeDetector({ formats: ['code_128', 'qr_code', 'code_39', 'ean_13'] });
                scannerInterval = setInterval(function() {
                    detector.detect(video).then(function(barcodes) {
                        if (barcodes.length > 0) {
                            var code = barcodes[0].rawValue;
                            stopCameraScanner();
                            jQuery('#member_id_input').val(code);
                            jQuery('#spMemberAccessForm').submit();
                        }
                    }).catch(function(e) {});
                }, 400);
            }
        })
        .catch(function(err) {
            alert('Unable to access camera: ' + err.message);
            stopCameraScanner();
        });
}

function stopCameraScanner() {
    if (scannerInterval) clearInterval(scannerInterval);
    if (cameraStream) {
        cameraStream.getTracks().forEach(function(track) { track.stop(); });
        cameraStream = null;
    }
    jQuery('#camera_scanner_container').hide();
}

// FORGOT PASSWORD MODAL HANDLERS
var activeResetToken = '';

function showForgotPasswordModal() {
    jQuery('#spForgotModal').css('display', 'flex');
    jQuery('#fp_step_1').show();
    jQuery('#fp_step_2, #fp_step_3').hide();
    jQuery('#fp_email').focus();
}

function closeForgotPasswordModal() {
    jQuery('#spForgotModal').hide();
}

function showFpMessage(stepNum, msg, isError) {
    var $box = jQuery('#fp_msg_' + stepNum);
    $box.removeClass('sp-alert-error').css({
        background: isError ? '#fef2f2' : '#dcfce7',
        border: isError ? '1px solid #fecaca' : '1px solid #bbf7d0',
        color: isError ? '#991b1b' : '#166534'
    }).text(msg).show();
}

function submitForgotEmail() {
    var email = jQuery('#fp_email').val();
    if (!email) {
        showFpMessage(1, 'Please enter your registered staff email address.', true);
        return;
    }

    jQuery('#fp_btn_1').prop('disabled', true).text('Sending Code...');

    jQuery.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
        action: 'sportedia_forgot_password_request',
        email: email,
        nonce: '<?php echo wp_create_nonce('sportedia_auth_nonce'); ?>'
    }, function(res) {
        jQuery('#fp_btn_1').prop('disabled', false).text('Send 6-Digit Code');
        if (res.success) {
            jQuery('#fp_step_1').hide();
            jQuery('#fp_step_2').show();
            showFpMessage(2, res.data || 'Verification code sent to your email.', false);
            jQuery('#fp_otp').focus();
        } else {
            showFpMessage(1, res.data || 'Failed to request reset code.', true);
        }
    }).fail(function() {
        jQuery('#fp_btn_1').prop('disabled', false).text('Send 6-Digit Code');
        showFpMessage(1, 'Server communication error. Please try again.', true);
    });
}

function submitVerifyOTP() {
    var email = jQuery('#fp_email').val();
    var otp = jQuery('#fp_otp').val();
    if (!otp || otp.length !== 6) {
        showFpMessage(2, 'Please enter the 6-digit code sent to your email.', true);
        return;
    }

    jQuery('#fp_btn_2').prop('disabled', true).text('Verifying...');

    jQuery.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
        action: 'sportedia_verify_otp',
        email: email,
        otp: otp,
        nonce: '<?php echo wp_create_nonce('sportedia_auth_nonce'); ?>'
    }, function(res) {
        jQuery('#fp_btn_2').prop('disabled', false).text('Verify Code');
        if (res.success && res.data && res.data.reset_token) {
            activeResetToken = res.data.reset_token;
            jQuery('#fp_step_2').hide();
            jQuery('#fp_step_3').show();
            showFpMessage(3, 'Code verified! Enter your new password below.', false);
            jQuery('#fp_new_pass').focus();
        } else {
            showFpMessage(2, res.data || 'Invalid verification code.', true);
        }
    }).fail(function() {
        jQuery('#fp_btn_2').prop('disabled', false).text('Verify Code');
        showFpMessage(2, 'Server communication error. Please try again.', true);
    });
}

function submitResetPassword() {
    var email = jQuery('#fp_email').val();
    var p1 = jQuery('#fp_new_pass').val();
    var p2 = jQuery('#fp_confirm_pass').val();

    if (!p1 || p1.length < 6) {
        showFpMessage(3, 'Password must be at least 6 characters long.', true);
        return;
    }
    if (p1 !== p2) {
        showFpMessage(3, 'Passwords do not match.', true);
        return;
    }

    jQuery('#fp_btn_3').prop('disabled', true).text('Updating Password...');

    jQuery.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
        action: 'sportedia_reset_password',
        email: email,
        reset_token: activeResetToken,
        password: p1,
        nonce: '<?php echo wp_create_nonce('sportedia_auth_nonce'); ?>'
    }, function(res) {
        jQuery('#fp_btn_3').prop('disabled', false).text('Save New Password');
        if (res.success) {
            alert(res.data || 'Password updated successfully! Please sign in with your new password.');
            closeForgotPasswordModal();
            jQuery('#user_pass').focus();
        } else {
            showFpMessage(3, res.data || 'Failed to update password.', true);
        }
    }).fail(function() {
        jQuery('#fp_btn_3').prop('disabled', false).text('Save New Password');
        showFpMessage(3, 'Server communication error. Please try again.', true);
    });
}
</script>

<?php wp_footer(); ?>
</body>
</html>
