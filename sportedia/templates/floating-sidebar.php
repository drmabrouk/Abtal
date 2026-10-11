<?php
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id      = $current_user->ID;
$current_page = isset($_GET['module']) ? sanitize_text_field($_GET['module']) : 'dashboard';
$app_url      = get_permalink(get_option('sportedia_page_id'));

$user_role_obj  = !empty($current_user->roles) ? get_role(reset($current_user->roles)) : null;
$user_role_name = $user_role_obj ? $user_role_obj->name : 'Sportedia User';

$branding_name = Sportedia_Settings_Manager::get_setting('site_name', 'Sportedia');
$branding_logo = Sportedia_Settings_Manager::get_setting('system_logo', '');

// Fetch user profile fields for modal
$user_avatar      = get_user_meta($user_id, 'sportedia_avatar', true);
$user_nationality = get_user_meta($user_id, 'sportedia_nationality', true);
$user_gender      = get_user_meta($user_id, 'sportedia_gender', true);
$user_address     = get_user_meta($user_id, 'sportedia_address', true);
$user_emirate     = get_user_meta($user_id, 'sportedia_emirate', true);
if (empty($user_emirate)) $user_emirate = 'Dubai';

$emirates_list      = array('Abu Dhabi', 'Dubai', 'Sharjah', 'Ajman', 'Umm Al Quwain', 'Ras Al Khaimah', 'Fujairah');
$nationalities_list = Sportedia_User_Manager::get_nationalities();

$nav_items = array(
    'dashboard' => array(
        'label' => 'Dashboard',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>',
        'cap'   => 'sportedia_access'
    ),
    'subscriptions' => array(
        'label' => 'Subscriptions',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><path d="M16 21V5a2 2 0 00-2-2H10a2 2 0 00-2 2v16"/></svg>',
        'cap'   => 'sportedia_manage_subscriptions'
    ),
    'programs' => array(
        'label' => 'Program Management',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
        'cap'   => 'sportedia_manage_programs'
    ),
    'coaches' => array(
        'label' => 'Coach Management',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>',
        'cap'   => 'sportedia_manage_programs'
    ),
    'tournaments' => array(
        'label' => 'Tournament Management',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 9H4.5a2.5 2.5 0 010-5H6"/><path d="M18 9h1.5a2.5 2.5 0 000-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.45 1-1 1H7.5"/><path d="M14 14.66V17c0 .55.45 1 1 1h1.5"/><path d="M18 2H6v7a6 6 0 0012 0V2z"/></svg>',
        'cap'   => 'sportedia_access'
    ),
    'branches' => array(
        'label' => 'Branch Management',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 21h18M3 7v14M21 7v14M6 21V11m4 10V11m4 10V11m4 10V11M12 3L2 7h20L12 3z"/></svg>',
        'cap'   => 'sportedia_manage_branches'
    ),
    'users' => array(
        'label' => 'System Users',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>',
        'cap'   => 'sportedia_manage_users'
    ),
    'attendance' => array(
        'label' => 'Attendance Log',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
        'cap'   => 'sportedia_manage_attendance'
    ),
    'reports' => array(
        'label' => 'Daily Reports',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        'cap'   => 'sportedia_view_reports'
    ),
    'settings' => array(
        'label' => 'System Settings',
        'icon'  => '<svg class="sp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>',
        'cap'   => 'sportedia_manage_settings'
    )
);
?>

<button id="spMobileToggle" class="sp-mobile-toggle">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
</button>

<aside class="sp-sidebar-floating">
    <div class="sp-sidebar-brand">
        <?php if (!empty($branding_logo)) : ?>
            <img src="<?php echo esc_url($branding_logo); ?>" style="width: 38px; height: 38px; border-radius: var(--sp-radius); object-fit: cover;">
        <?php else : ?>
            <div class="sp-brand-logo"><?php echo esc_html(strtoupper(substr($branding_name, 0, 1))); ?></div>
        <?php endif; ?>
        <div>
            <div class="sp-brand-name"><?php echo esc_html($branding_name); ?></div>
            <div style="font-size: 10px; color: var(--sp-text-muted); font-weight: 500; margin-top: 1px; letter-spacing: 0.2px;">Sports Management System</div>
        </div>
    </div>

    <nav class="sp-sidebar-nav">
        <?php foreach ($nav_items as $key => $item) : ?>
            <?php if (current_user_can($item['cap']) || Sportedia_Roles::is_sys_admin()) : ?>
                <a href="<?php echo esc_url(add_query_arg('module', $key, $app_url)); ?>"
                   class="sp-nav-item <?php echo $current_page === $key ? 'active' : ''; ?>">
                    <?php echo $item['icon']; ?>
                    <span><?php echo esc_html($item['label']); ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <!-- Merged Interactive User Profile Box with Logout Icon -->
    <div class="sp-sidebar-footer">
        <div class="sp-user-badge" style="cursor: pointer; position: relative; padding-right: 36px; transition: background 0.2s;" onclick="spOpenModal('spMyProfileModal')" title="Click to Edit Profile">
            <?php if (!empty($user_avatar)) : ?>
                <img src="<?php echo esc_url($user_avatar); ?>" style="width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 1px solid var(--sp-border-color); flex-shrink: 0;">
            <?php else : ?>
                <div class="sp-user-avatar">
                    <?php echo esc_html(strtoupper(substr($current_user->display_name, 0, 1))); ?>
                </div>
            <?php endif; ?>

            <div class="sp-user-info">
                <span class="sp-user-name"><?php echo esc_html($current_user->display_name); ?></span>
                <span class="sp-user-role"><?php echo esc_html($user_role_name); ?></span>
            </div>

            <!-- Integrated Logout Icon inside Profile Box -->
            <button type="button"
                    onclick="event.stopPropagation(); window.location.href='<?php echo esc_url(wp_logout_url($app_url)); ?>';"
                    title="Logout"
                    style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent; border: none; cursor: pointer; color: var(--sp-text-muted); padding: 4px; border-radius: 6px;"
                    onmouseover="this.style.color='#dc2626'"
                    onmouseout="this.style.color='var(--sp-text-muted)'">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </button>
        </div>
    </div>
</aside>

<!-- Interactive My Profile Editor Modal -->
<div id="spMyProfileModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 600px;">
        <div class="sp-modal-header">
            <h3 class="sp-modal-title">My Account Profile Settings</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spMyProfileModal')">&times;</button>
        </div>

        <form id="spMyProfileForm" enctype="multipart/form-data">
            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="text" id="prof_name" name="display_name" class="sp-floating-input" value="<?php echo esc_attr($current_user->display_name); ?>" required>
                    <label for="prof_name" class="sp-floating-label">Full Name *</label>
                </div>

                <div class="sp-form-group">
                    <input type="email" id="prof_email" name="email" class="sp-floating-input" value="<?php echo esc_attr($current_user->user_email); ?>" required>
                    <label for="prof_email" class="sp-floating-label">Email Address *</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="password" id="prof_password" name="password" class="sp-floating-input" placeholder=" ">
                    <label for="prof_password" class="sp-floating-label">Password (leave blank to keep unchanged)</label>
                </div>

                <div class="sp-form-group">
                    <select id="prof_nationality" name="nationality" class="sp-floating-select">
                        <option value="">Select Nationality</option>
                        <?php foreach ($nationalities_list as $nat) : ?>
                            <option value="<?php echo esc_attr($nat); ?>" <?php selected($user_nationality, $nat); ?>><?php echo esc_html($nat); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="prof_nationality" class="sp-floating-label">Nationality</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <select id="prof_gender" name="gender" class="sp-floating-select">
                        <option value="Male" <?php selected($user_gender, 'Male'); ?>>Male</option>
                        <option value="Female" <?php selected($user_gender, 'Female'); ?>>Female</option>
                        <option value="Other" <?php selected($user_gender, 'Other'); ?>>Other</option>
                    </select>
                    <label for="prof_gender" class="sp-floating-label">Gender</label>
                </div>

                <div class="sp-form-group">
                    <select id="prof_emirate" name="emirate" class="sp-floating-select">
                        <?php foreach ($emirates_list as $em) : ?>
                            <option value="<?php echo esc_attr($em); ?>" <?php selected($user_emirate, $em); ?>><?php echo esc_html($em); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="prof_emirate" class="sp-floating-label">Emirate (UAE)</label>
                </div>
            </div>

            <div class="sp-form-group">
                <input type="text" value="United Arab Emirates" class="sp-floating-input" disabled readonly style="background: #f3f4f6;">
                <label class="sp-floating-label">Country (Fixed)</label>
            </div>

            <div class="sp-form-group">
                <textarea id="prof_address" name="address" class="sp-floating-input" style="height: 60px;" placeholder=" "><?php echo esc_textarea($user_address); ?></textarea>
                <label for="prof_address" class="sp-floating-label">Physical Address</label>
            </div>

            <div style="margin-bottom: 20px; border: 1px dashed var(--sp-border-color); padding: 12px; border-radius: var(--sp-radius);">
                <label style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 6px;">Profile Photo (Max 2 MB)</label>
                <input type="file" id="prof_avatar" name="avatar_file" accept="image/*" style="font-size: 12px;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spMyProfileModal')">Cancel</button>
                <button type="submit" class="sp-btn sp-btn-primary">Save Profile Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery('#spMyProfileForm').on('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    formData.append('action', 'sportedia_update_my_profile');
    formData.append('nonce', sportedia_vars.nonce);

    jQuery.ajax({
        url: sportedia_vars.ajax_url,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(res) {
            if (res.success) {
                alert(res.data);
                location.reload();
            } else {
                alert(res.data || 'Failed to update profile.');
            }
        }
    });
});
</script>
