<?php
if (!defined('ABSPATH')) exit;

$member_identifier = '';
if (isset($_GET['sportedia_member_access'])) {
    $member_identifier = sanitize_text_field($_GET['sportedia_member_access']);
} else if (isset($_GET['id'])) {
    $member_identifier = sanitize_text_field($_GET['id']);
} else if (isset($_GET['member_id'])) {
    $member_identifier = sanitize_text_field($_GET['member_id']);
}

// Search member user in WP DB
$users = array();
if (!empty($member_identifier)) {
    $users = get_users(array(
        'meta_key'   => 'sportedia_employee_id',
        'meta_value' => $member_identifier,
        'number'     => 1,
    ));

    if (empty($users)) {
        $u_obj = get_user_by('login', $member_identifier);
        if (!$u_obj) $u_obj = get_user_by('email', $member_identifier);
        if (!$u_obj && is_numeric($member_identifier)) $u_obj = get_user_by('id', intval($member_identifier));
        if ($u_obj) $users = array($u_obj);
    }
}

$member = !empty($users) ? $users[0] : null;

// Fetch Subscription & Attendance Data if member found
global $wpdb;
$subs_table = $wpdb->prefix . 'sportedia_subscriptions';
$att_table  = $wpdb->prefix . 'sportedia_attendance';

$sub          = null;
$att_logs     = array();
$total_count  = 12;
$used_count   = 0;
$rem_count    = 12;
$status_label = 'No Active Plan';

if ($member) {
    $m_id = $member->ID;
    $emp_id = get_user_meta($m_id, 'sportedia_employee_id', true);
    if (empty($emp_id)) $emp_id = 'MEM-' . $m_id;

    $sub = $wpdb->get_row($wpdb->prepare("SELECT * FROM $subs_table WHERE user_id = %d ORDER BY id DESC LIMIT 1", $m_id), ARRAY_A);

    if ($sub) {
        $total_count = intval($sub['sessions_count']) > 0 ? intval($sub['sessions_count']) : 12;
        $used_count  = intval($sub['sessions_used']);
        $rem_count   = max(0, $total_count - $used_count);
        $status_label= ucfirst($sub['status']);
    }

    // Fetch actual attendance logs
    $att_logs = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $att_table WHERE user_id = %d ORDER BY check_in_time ASC, id ASC",
        $m_id
    ), ARRAY_A);
}

$avatar_url = $member ? get_user_meta($member->ID, 'sportedia_avatar', true) : '';
if (empty($avatar_url)) {
    $avatar_url = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($member ? $member->user_email : ''))) . '?s=120&d=mp';
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Portal — Sportedia</title>
    <?php wp_head(); ?>
    <style>
        body.sp-member-body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            font-family: 'Google Sans Flex', -apple-system, sans-serif;
            color: #0f172a;
            min-height: 100vh;
        }
        .sp-portal-container {
            max-width: 600px;
            margin: 24px auto;
            padding: 0 16px;
        }
        .sp-portal-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
            padding: 24px;
            margin-bottom: 20px;
        }
        .sp-portal-header {
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .sp-avatar-wrapper {
            position: relative;
            cursor: pointer;
        }
        .sp-portal-avatar {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #0284c7;
            transition: opacity 0.2s ease;
        }
        .sp-avatar-wrapper:hover .sp-portal-avatar {
            opacity: 0.8;
        }
        .sp-avatar-upload-badge {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #0284c7;
            color: #fff;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .sp-portal-name {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px 0;
        }
        .sp-portal-id {
            font-family: monospace;
            font-size: 13px;
            color: #64748b;
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 4px;
            display: inline-block;
        }
        .sp-session-circles-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
            margin: 20px 0;
        }
        .sp-circle-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            border: none;
        }
        .sp-circle-btn:hover {
            transform: scale(1.08);
        }
        .sp-circle-black {
            background-color: #0f172a;
            color: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.2);
        }
        .sp-circle-green {
            background-color: #10b981;
            color: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);
        }
        .sp-circle-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            font-size: 12px;
            color: #64748b;
            margin-top: 12px;
        }
        .sp-legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .sp-legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
    </style>
</head>
<body class="sp-member-body">

<div class="sp-portal-container">
    <div style="text-align: center; margin-bottom: 20px;">
        <h1 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0;">SPORTEDIA ACADEMY</h1>
        <p style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">Official Member Portal & Session Tracker</p>
    </div>

    <?php if ($member && $sub) : ?>
        <!-- Member Profile Header Card -->
        <div class="sp-portal-card">
            <div class="sp-portal-header">
                <div class="sp-avatar-wrapper" onclick="document.getElementById('member_avatar_file').click();" title="Click to upload profile photo">
                    <img src="<?php echo esc_url($avatar_url); ?>" id="member_avatar_img" alt="<?php echo esc_attr($member->display_name); ?>" class="sp-portal-avatar">
                    <div class="sp-avatar-upload-badge">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    </div>
                </div>
                <input type="file" id="member_avatar_file" style="display: none;" accept="image/jpeg,image/png,image/webp" onchange="uploadMemberPhoto(<?php echo $member->ID; ?>)">

                <div>
                    <h2 class="sp-portal-name"><?php echo esc_html($member->display_name); ?></h2>
                    <span class="sp-portal-id"><?php echo esc_html(get_user_meta($member->ID, 'sportedia_employee_id', true) ? get_user_meta($member->ID, 'sportedia_employee_id', true) : 'MEM-' . $member->ID); ?></span>
                    <div style="margin-top: 6px; font-size: 12px; color: #64748b;">
                        Sport: <strong><?php echo esc_html($sub['plan_name']); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Program Details Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px; background: #f8fafc; padding: 14px; border-radius: 8px; margin-bottom: 16px;">
                <div>
                    <span style="font-size: 11px; color: #64748b; display: block;">PROGRAM PLAN</span>
                    <strong><?php echo esc_html($sub['plan_name']); ?></strong>
                </div>
                <div>
                    <span style="font-size: 11px; color: #64748b; display: block;">STATUS</span>
                    <span class="sp-badge <?php echo $sub['status'] === 'active' ? 'sp-badge-active' : 'sp-badge-inactive'; ?>"><?php echo esc_html(ucfirst($sub['status'])); ?></span>
                </div>
                <div>
                    <span style="font-size: 11px; color: #64748b; display: block;">START DATE</span>
                    <span><?php echo esc_html($sub['start_date']); ?></span>
                </div>
                <div>
                    <span style="font-size: 11px; color: #64748b; display: block;">EXPIRY DATE</span>
                    <span><?php echo esc_html($sub['end_date']); ?></span>
                </div>
            </div>

            <!-- Session Balance Counters -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; text-align: center; margin-bottom: 20px;">
                <div style="background: #f1f5f9; padding: 10px; border-radius: 8px;">
                    <span style="font-size: 10px; color: #64748b; display: block;">TOTAL</span>
                    <strong style="font-size: 16px;"><?php echo $total_count; ?></strong>
                </div>
                <div style="background: #f1f5f9; padding: 10px; border-radius: 8px;">
                    <span style="font-size: 10px; color: #0f172a; display: block; font-weight: 700;">COMPLETED</span>
                    <strong style="font-size: 16px; color: #0f172a;"><?php echo $used_count; ?></strong>
                </div>
                <div style="background: #dcfce7; padding: 10px; border-radius: 8px;">
                    <span style="font-size: 10px; color: #16a34a; display: block; font-weight: 700;">REMAINING</span>
                    <strong style="font-size: 16px; color: #16a34a;"><?php echo $rem_count; ?></strong>
                </div>
            </div>

            <!-- VISUAL SESSION CIRCLES (BLACK = COMPLETED, GREEN = REMAINING) -->
            <h3 style="font-size: 14px; font-weight: 700; margin: 0 0 10px 0; color: #0f172a; text-align: center;">Session Progress Tracking</h3>
            <div class="sp-session-circles-grid">
                <?php
                for ($i = 1; $i <= $total_count; $i++) {
                    $is_completed = ($i <= $used_count);
                    $att_record   = isset($att_logs[$i - 1]) ? $att_logs[$i - 1] : null;

                    if ($is_completed) {
                        $log_date = $att_record ? date('Y-m-d', strtotime($att_record['check_in_time'])) : $sub['start_date'];
                        $log_time = $att_record ? date('H:i:s', strtotime($att_record['check_in_time'])) : 'Completed';
                        echo '<button type="button" class="sp-circle-btn sp-circle-black" onclick=\'showSessionLogModal(' . $i . ', "' . esc_js($log_date) . '", "' . esc_js($log_time) . '", "' . esc_js($sub['plan_name']) . '")\'>' . $i . '</button>';
                    } else {
                        echo '<button type="button" class="sp-circle-btn sp-circle-green" onclick=\'alert("Session #' . $i . ' is AVAILABLE.")\'>' . $i . '</button>';
                    }
                }
                ?>
            </div>

            <div class="sp-circle-legend">
                <div class="sp-legend-item">
                    <div class="sp-legend-dot" style="background: #0f172a;"></div>
                    <span>Black = Completed Session</span>
                </div>
                <div class="sp-legend-item">
                    <div class="sp-legend-dot" style="background: #10b981;"></div>
                    <span>Green = Available Session</span>
                </div>
            </div>
        </div>
    <?php else : ?>
        <div class="sp-portal-card" style="text-align: center; padding: 40px;">
            <h3 style="margin-top: 0; color: #991b1b;">Member Access / Barcode Not Recognized</h3>
            <p style="font-size: 13px; color: #64748b;">Please verify your Member ID or scan a valid Sportedia membership card barcode.</p>
            <a href="<?php echo esc_url(get_permalink(get_option('sportedia_page_id'))); ?>" class="sp-btn sp-btn-primary" style="display: inline-block; margin-top: 14px;">Return to Access Screen</a>
        </div>
    <?php endif; ?>
</div>

<!-- SESSION DETAILS TOOLTIP MODAL -->
<div id="spSessionDetailModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 400px; text-align: center;">
        <div class="sp-modal-header">
            <h3 class="sp-modal-title" id="sess_modal_title">Session Details</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spSessionDetailModal')">&times;</button>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; font-size: 13px; text-align: left; margin-bottom: 16px;">
            <div style="margin-bottom: 8px;"><strong>Session Date:</strong> <span id="sess_det_date">-</span></div>
            <div style="margin-bottom: 8px;"><strong>Exact Time:</strong> <span id="sess_det_time" style="font-family: monospace;">-</span></div>
            <div style="margin-bottom: 8px;"><strong>Program:</strong> <span id="sess_det_program">-</span></div>
            <div><strong>Status:</strong> <span class="sp-badge sp-badge-active">Verified & Completed</span></div>
        </div>

        <button type="button" class="sp-btn sp-btn-secondary" style="width: 100%;" onclick="spCloseModal('spSessionDetailModal')">Close</button>
    </div>
</div>

<script>
function showSessionLogModal(num, date, time, program) {
    jQuery('#sess_modal_title').text('Session #' + num + ' Completed Record');
    jQuery('#sess_det_date').text(date);
    jQuery('#sess_det_time').text(time);
    jQuery('#sess_det_program').text(program);
    spOpenModal('spSessionDetailModal');
}

function uploadMemberPhoto(userId) {
    var fileInput = document.getElementById('member_avatar_file');
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) return;

    var file = fileInput.files[0];
    if (file.size > 2 * 1024 * 1024) {
        alert('File size exceeds 2 MB limit.');
        return;
    }

    var formData = new FormData();
    formData.append('action', 'sportedia_upload_avatar');
    formData.append('nonce', sportedia_vars.nonce);
    formData.append('user_id', userId);
    formData.append('avatar_file', file);

    jQuery.ajax({
        url: sportedia_vars.ajax_url,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(res) {
            if (res.success && res.data.avatar_url) {
                jQuery('#member_avatar_img').attr('src', res.data.avatar_url);
                alert('Profile photo updated successfully!');
            } else {
                alert(res.data || 'Failed to upload photo.');
            }
        }
    });
}
</script>

<?php wp_footer(); ?>
</body>
</html>
