<?php
if (!defined('ABSPATH')) exit;

$search    = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$role_filter = isset($_GET['role_filter']) ? sanitize_text_field($_GET['role_filter']) : '';
$branch_filter = isset($_GET['branch_filter']) ? intval($_GET['branch_filter']) : 0;

$usersList         = Sportedia_User_Manager::get_users($search, $role_filter, $branch_filter);
$branchesList      = Sportedia_Branch_Manager::get_branches();
$nationalitiesList = Sportedia_User_Manager::get_nationalities();
?>

<div class="sp-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
    <div>
        <h1 class="sp-page-title">System User Management</h1>
        <p class="sp-page-subtitle">Manage system users, roles, employee IDs, and multi-branch access.</p>
    </div>
    <button class="sp-btn sp-btn-primary" onclick="openUserModal()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add New User
    </button>
</div>

<div class="sp-card" style="padding: 16px; margin-bottom: 20px;">
    <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap;">
        <input type="hidden" name="module" value="users">

        <div style="flex: 1; min-width: 200px;">
            <input type="text" name="search" class="sp-floating-input" placeholder="Search by name, email, employee ID..." value="<?php echo esc_attr($search); ?>">
        </div>

        <div style="width: 200px;">
            <select name="role_filter" class="sp-floating-select">
                <option value="">All Roles</option>
                <option value="sportedia_sys_admin" <?php selected($role_filter, 'sportedia_sys_admin'); ?>>System Administrator</option>
                <option value="sportedia_facility_mgr" <?php selected($role_filter, 'sportedia_facility_mgr'); ?>>Facility Manager</option>
                <option value="sportedia_ops_mgr" <?php selected($role_filter, 'sportedia_ops_mgr'); ?>>Operations Manager</option>
                <option value="sportedia_booking_mgr" <?php selected($role_filter, 'sportedia_booking_mgr'); ?>>Booking & Reception Manager</option>
                <option value="sportedia_coach" <?php selected($role_filter, 'sportedia_coach'); ?>>Coach / Trainer</option>
                <option value="sportedia_customer" <?php selected($role_filter, 'sportedia_customer'); ?>>Customer / Member</option>
                <option value="sportedia_finance_mgr" <?php selected($role_filter, 'sportedia_finance_mgr'); ?>>Finance & Accounts Manager</option>
            </select>
        </div>

        <div style="width: 200px;">
            <select name="branch_filter" class="sp-floating-select">
                <option value="0">All Branches</option>
                <?php foreach ($branchesList as $b) : ?>
                    <option value="<?php echo esc_attr($b['id']); ?>" <?php selected($branch_filter, $b['id']); ?>><?php echo esc_html($b['branch_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="sp-btn sp-btn-primary">Filter</button>
    </form>
</div>

<!-- Stacked Full-Width User Rows (Sorted Newest Registered First) -->
<?php if (!empty($usersList)) : ?>
    <div style="display: flex; flex-direction: column; gap: 12px;" id="users_container">
        <?php foreach ($usersList as $u) : ?>
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
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <div class="sp-card" style="text-align: center; color: var(--sp-text-muted); padding: 48px;">
        <h3>No system users found</h3>
        <p>No users match your query parameters.</p>
    </div>
<?php endif; ?>

<!-- Modal Dialog for User Create/Edit -->
<div id="spUserModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 600px;">
        <div class="sp-modal-header">
            <h3 id="spUserModalTitle" class="sp-modal-title">Add System User</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spUserModal')">&times;</button>
        </div>

        <form id="spUserForm" enctype="multipart/form-data">
            <input type="hidden" id="sp_user_id" name="user_id" value="0">

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="text" id="sp_employee_id" name="employee_id" class="sp-floating-input" placeholder=" " required>
                    <label for="sp_employee_id" class="sp-floating-label">Member / Employee ID *</label>
                </div>

                <div class="sp-form-group">
                    <input type="text" id="sp_phone" name="phone" class="sp-floating-input" placeholder=" ">
                    <label for="sp_phone" class="sp-floating-label">Mobile Phone Number</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="text" id="sp_display_name" name="display_name" class="sp-floating-input" placeholder=" " required>
                    <label for="sp_display_name" class="sp-floating-label">Full Name *</label>
                </div>

                <div class="sp-form-group">
                    <input type="email" id="sp_email" name="email" class="sp-floating-input" placeholder=" " required>
                    <label for="sp_email" class="sp-floating-label">Email Address *</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <select id="sp_nationality" name="nationality" class="sp-floating-select">
                        <option value="">Select Nationality</option>
                        <?php foreach ($nationalitiesList as $nat) : ?>
                            <option value="<?php echo esc_attr($nat); ?>"><?php echo esc_html($nat); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="sp_nationality" class="sp-floating-label">Nationality</label>
                </div>

                <div class="sp-form-group">
                    <select id="sp_gender" name="gender" class="sp-floating-select">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                    <label for="sp_gender" class="sp-floating-label">Gender</label>
                </div>
            </div>

            <!-- Profile Photo Upload -->
            <div style="margin-bottom: 20px; border: 1px dashed var(--sp-border-color); padding: 12px; border-radius: var(--sp-radius);">
                <label style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 6px;">Profile Photo (Max size: 2 MB)</label>
                <input type="file" id="sp_avatar_file" name="avatar_file" accept="image/*" style="font-size: 12px;">
            </div>

            <!-- Health Metrics -->
            <div class="sp-grid-3">
                <div class="sp-form-group">
                    <input type="text" id="sp_height" name="height" class="sp-floating-input" placeholder=" ">
                    <label for="sp_height" class="sp-floating-label">Height (cm)</label>
                </div>

                <div class="sp-form-group">
                    <input type="text" id="sp_weight" name="weight" class="sp-floating-input" placeholder=" ">
                    <label for="sp_weight" class="sp-floating-label">Weight (kg)</label>
                </div>

                <div class="sp-form-group">
                    <select id="sp_health_status" name="health_status" class="sp-floating-select">
                        <option value="Fit & Healthy">Fit & Healthy</option>
                        <option value="Good">Good</option>
                        <option value="Under Observation">Under Observation</option>
                        <option value="Medical Exception">Medical Exception</option>
                    </select>
                    <label for="sp_health_status" class="sp-floating-label">Health Status</label>
                </div>
            </div>

            <div class="sp-form-group">
                <textarea id="sp_medical_notes" name="medical_notes" class="sp-floating-input" style="height: 60px;" placeholder=" "></textarea>
                <label for="sp_medical_notes" class="sp-floating-label">Health Problems / Medical Notes</label>
            </div>

            <!-- Employee Salary & Payroll Configuration -->
            <div style="margin-bottom: 20px; border: 1px solid var(--sp-border-color); padding: 12px; border-radius: var(--sp-radius);">
                <label style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 8px;">Employee Salary & Payroll Configuration</label>
                <div class="sp-grid-3">
                    <div>
                        <label style="font-size: 11px; font-weight: 600; display: block; margin-bottom: 4px;">Base Salary (AED)</label>
                        <input type="number" step="0.01" id="sp_base_salary" name="base_salary" class="sp-floating-input" value="0.00">
                    </div>
                    <div>
                        <label style="font-size: 11px; font-weight: 600; display: block; margin-bottom: 4px;">Pay Type</label>
                        <select id="sp_pay_type" name="pay_type" class="sp-floating-select">
                            <option value="monthly">Monthly Fixed</option>
                            <option value="hourly">Hourly Rate</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 11px; font-weight: 600; display: block; margin-bottom: 4px;">Hourly Rate (AED)</label>
                        <input type="number" step="0.01" id="sp_hourly_rate" name="hourly_rate" class="sp-floating-input" value="0.00">
                    </div>
                </div>
            </div>

            <!-- Employee Work Schedule Configuration -->
            <div style="margin-bottom: 20px; border: 1px solid var(--sp-border-color); padding: 12px; border-radius: var(--sp-radius);">
                <label style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 8px;">Employee Work Schedule</label>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px;">
                    <?php
                    $days_list = array('Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday');
                    foreach ($days_list as $day) :
                    ?>
                        <label style="font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
                            <input type="checkbox" name="work_days[]" class="sp-work-day-cb" value="<?php echo esc_attr($day); ?>" checked>
                            <?php echo esc_html(substr($day, 0, 3)); ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="sp-grid-2">
                    <div>
                        <label style="font-size: 11px; font-weight: 600; display: block; margin-bottom: 4px;">Shift Start Time</label>
                        <input type="time" id="sp_shift_start" name="shift_start" class="sp-floating-input" value="09:00">
                    </div>
                    <div>
                        <label style="font-size: 11px; font-weight: 600; display: block; margin-bottom: 4px;">Shift End Time</label>
                        <input type="time" id="sp_shift_end" name="shift_end" class="sp-floating-input" value="17:00">
                    </div>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <select id="sp_role" name="role" class="sp-floating-select" required>
                        <option value="sportedia_sys_admin">System Administrator</option>
                        <option value="sportedia_general_mgr">General Manager</option>
                        <option value="sportedia_facility_mgr">Facility Manager</option>
                        <option value="sportedia_ops_mgr">Operations Manager</option>
                        <option value="sportedia_booking_mgr">Booking & Reception Manager</option>
                        <option value="sportedia_coach">Coach / Trainer</option>
                        <option value="sportedia_customer">Customer / Member</option>
                        <option value="sportedia_finance_mgr">Finance & Accounts Manager</option>
                    </select>
                    <label for="sp_role" class="sp-floating-label">User Role</label>
                </div>

                <div class="sp-form-group">
                    <select id="sp_status" name="status" class="sp-floating-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <label for="sp_status" class="sp-floating-label">Account Status</label>
                </div>
            </div>

            <div class="sp-form-group">
                <input type="password" id="sp_password" name="password" class="sp-floating-input" placeholder=" ">
                <label for="sp_password" class="sp-floating-label">Password (leave blank to keep current)</label>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="font-size: 13px; font-weight: 600; color: var(--sp-text-main); display: block; margin-bottom: 8px;">Assigned Branches (Multi-branch support)</label>
                <div style="max-height: 120px; overflow-y: auto; border: 1px solid var(--sp-border-color); padding: 10px; border-radius: var(--sp-radius);">
                    <?php foreach ($branchesList as $b) : ?>
                        <label style="display: block; font-size: 13px; margin-bottom: 4px;">
                            <input type="checkbox" name="branch_ids[]" class="sp-branch-checkbox" value="<?php echo esc_attr($b['id']); ?>">
                            <?php echo esc_html($b['branch_name']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--sp-border-color); padding-top: 16px;">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spUserModal')">Cancel</button>
                <button type="submit" class="sp-btn sp-btn-primary">Save User</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUserModal() {
    jQuery('#spUserModalTitle').text('Add System User');
    jQuery('#sp_user_id').val('0');
    jQuery('#spUserForm')[0].reset();
    jQuery('.sp-branch-checkbox').prop('checked', false);
    jQuery('.sp-work-day-cb').prop('checked', true);
    jQuery('#sp_shift_start').val('09:00');
    jQuery('#sp_shift_end').val('17:00');
    spOpenModal('spUserModal');
}

function editUser(u) {
    jQuery('#spUserModalTitle').text('Edit System User');
    jQuery('#sp_user_id').val(u.id);
    jQuery('#sp_employee_id').val(u.employee_id);
    jQuery('#sp_phone').val(u.phone || '');
    jQuery('#sp_display_name').val(u.name);
    jQuery('#sp_email').val(u.email);
    jQuery('#sp_nationality').val(u.nationality || '');
    jQuery('#sp_gender').val(u.gender || 'Male');
    jQuery('#sp_height').val(u.height || '');
    jQuery('#sp_weight').val(u.weight || '');
    jQuery('#sp_health_status').val(u.health_status || 'Fit & Healthy');
    jQuery('#sp_medical_notes').val(u.medical_notes || '');
    jQuery('#sp_base_salary').val(u.base_salary || '0.00');
    jQuery('#sp_pay_type').val(u.pay_type || 'monthly');
    jQuery('#sp_hourly_rate').val(u.hourly_rate || '0.00');
    jQuery('#sp_role').val(u.role_key);
    jQuery('#sp_status').val(u.status);
    jQuery('#sp_password').val('');

    jQuery('.sp-branch-checkbox').prop('checked', false);
    if (u.branches && u.branches.length > 0) {
        u.branches.forEach(function(bId) {
            jQuery('.sp-branch-checkbox[value="' + bId + '"]').prop('checked', true);
        });
    }

    jQuery('.sp-work-day-cb').prop('checked', false);
    if (u.work_schedule && u.work_schedule.days) {
        u.work_schedule.days.forEach(function(d) {
            jQuery('.sp-work-day-cb[value="' + d + '"]').prop('checked', true);
        });
        jQuery('#sp_shift_start').val(u.work_schedule.start || '09:00');
        jQuery('#sp_shift_end').val(u.work_schedule.end || '17:00');
    }

    spOpenModal('spUserModal');
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? The linked WordPress account will also be permanently deleted.')) {
        jQuery.post(sportedia_vars.ajax_url, {
            action: 'sportedia_delete_user',
            nonce: sportedia_vars.nonce,
            user_id: userId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || 'Failed to delete user.');
            }
        });
    }
}

jQuery('#spUserForm').on('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    formData.append('action', 'sportedia_save_user');
    formData.append('nonce', sportedia_vars.nonce);

    jQuery.ajax({
        url: sportedia_vars.ajax_url,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || 'Error saving user.');
            }
        }
    });
});
</script>
