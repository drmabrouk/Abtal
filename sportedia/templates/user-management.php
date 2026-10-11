<?php
if (!defined('ABSPATH')) exit;

$search    = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$role_filter = isset($_GET['role_filter']) ? sanitize_text_field($_GET['role_filter']) : '';
$branch_filter = isset($_GET['branch_filter']) ? intval($_GET['branch_filter']) : 0;

$usersList         = Sportedia_User_Manager::get_users($search, $role_filter, $branch_filter);
$branchesList      = Sportedia_Branch_Manager::get_branches();
$nationalitiesList = Sportedia_User_Manager::get_nationalities();
$system_logo       = Sportedia_Settings_Manager::get_setting('system_logo', '');
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

                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="sp-badge <?php echo $u['status'] === 'active' ? 'sp-badge-active' : 'sp-badge-inactive'; ?>">
                        <?php echo esc_html(ucfirst($u['status'])); ?>
                    </span>

                    <?php
                        $clean_phone = preg_replace('/[^0-9]/', '', $u['phone']);
                        $wa_text = rawurlencode("Hello " . $u['name'] . ",\n\nHere is your Sportedia Official Employee ID Card:\nID: " . $u['employee_id'] . "\nRole: " . $u['role'] . "\nStatus: " . ucfirst($u['status']) . "\n\nInstructions: Present your barcode at the attendance kiosk for staff check-in/check-out. Keep strictly confidential.");
                        $wa_link = !empty($clean_phone) ? ("https://api.whatsapp.com/send?phone=" . $clean_phone . "&text=" . $wa_text) : '#';
                    ?>
                    <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='viewEmpCard(<?php echo json_encode($u); ?>)'>ID Card</button>
                    <a href="<?php echo esc_url($wa_link); ?>" target="_blank" class="sp-btn sp-btn-secondary sp-btn-sm" style="color: #16a34a; border-color: #16a34a;" title="Share ID Card via WhatsApp">WhatsApp</a>
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

<!-- Dedicated Employee Digital Mobile ID Business Card Modal -->
<div id="spEmpCardModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 440px; padding: 0; background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e5e7eb; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);" id="spPrintableEmpCardModal">
        <div class="sp-modal-header sp-no-print" style="padding: 12px 16px; margin: 0; background: var(--sp-bg-main);">
            <h3 class="sp-modal-title">Employee Digital ID Business Card</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spEmpCardModal')">&times;</button>
        </div>

        <div id="spEmpCardSheet">
            <!-- Header Section -->
            <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; padding: 20px; display: flex; justify-content: space-between; align-items: center; position: relative;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div>
                        <?php if (!empty($system_logo)) : ?>
                            <img src="<?php echo esc_url($system_logo); ?>" style="max-height: 38px; max-width: 100px; object-fit: contain;">
                        <?php else : ?>
                            <div style="width: 36px; height: 36px; background: #ffffff; color: #000000; font-weight: 800; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px;">S</div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <strong style="font-size: 16px; display: block; letter-spacing: -0.3px;">Sportedia</strong>
                        <span style="font-size: 10px; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px;">Employee Credentials Card</span>
                    </div>
                </div>
                <span id="emp_card_role_badge" style="font-size: 10px; background: rgba(255,255,255,0.15); padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.2);">Staff</span>
            </div>

            <!-- Profile & Main Info Section -->
            <div style="padding: 20px; font-family: 'Google Sans Flex', -apple-system, sans-serif;">
                <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 18px;">
                    <div style="width: 64px; height: 64px; border-radius: 50%; overflow: hidden; border: 2px solid #0f172a; background: #f1f5f9; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                        <span id="emp_card_avatar_initial" style="font-size: 24px; font-weight: 700; color: #334155;">E</span>
                        <img id="emp_card_avatar_img" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                    </div>
                    <div style="flex: 1;">
                        <span id="emp_card_status_badge" class="sp-badge sp-badge-active" style="display: inline-block; margin-bottom: 4px; font-size: 10px; padding: 2px 8px;">Active Staff</span>
                        <h3 id="emp_card_name" style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;">Employee Name</h3>
                        <span id="emp_card_id" style="font-size: 12px; font-family: monospace; color: #64748b; display: block;">ID: EMP001</span>
                    </div>
                </div>

                <!-- Details Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: #f8fafc; padding: 14px; border-radius: 12px; margin-bottom: 16px; border: 1px solid #e2e8f0; font-size: 12px;">
                    <div>
                        <span style="font-size: 10px; color: #64748b; display: block; text-transform: uppercase;">Official Role</span>
                        <strong id="emp_card_role_text" style="font-size: 12px; color: #0f172a; display: block;">Staff Officer</strong>
                    </div>
                    <div>
                        <span style="font-size: 10px; color: #64748b; display: block; text-transform: uppercase;">Mobile Contact</span>
                        <span id="emp_card_phone" style="font-size: 11px; font-weight: 600;">+971 50 000 0000</span>
                    </div>
                    <div>
                        <span style="font-size: 10px; color: #64748b; display: block; text-transform: uppercase;">Standard Shift</span>
                        <span id="emp_card_shift" style="font-size: 11px; font-family: monospace; font-weight: 600;">09:00 - 17:00</span>
                    </div>
                    <div>
                        <span style="font-size: 10px; color: #64748b; display: block; text-transform: uppercase;">Email</span>
                        <span id="emp_card_email" style="font-size: 11px; font-weight: 500; word-break: break-all;">staff@sportedia.online</span>
                    </div>
                </div>

                <!-- Barcode Area -->
                <div style="text-align: center; background: #ffffff; padding: 14px; border-radius: 12px; border: 1px dashed #cbd5e1; margin-bottom: 14px;">
                    <div id="emp_card_barcode_lines" style="display: flex; justify-content: center; gap: 2px; height: 42px; margin-bottom: 6px;"></div>
                    <span id="emp_card_barcode_text" style="font-family: monospace; font-size: 13px; font-weight: 700; letter-spacing: 2px; color: #0f172a;">*EMP001*</span>
                </div>

                <!-- Context-Aware Instructions Footer -->
                <div style="background: #f1f5f9; border-left: 3px solid #0f172a; padding: 10px 12px; border-radius: 4px; font-size: 11px; color: #475569; line-height: 1.4;">
                    <strong>Staff Instructions:</strong> Present this barcode ID at any Sportedia Kiosk terminal for automated check-in & check-out. Credentials are non-transferable and strictly confidential.
                </div>
            </div>
        </div>

        <!-- Footer Actions -->
        <div style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; gap: 8px; justify-content: flex-end;" class="sp-no-print">
            <button type="button" class="sp-btn sp-btn-secondary sp-btn-sm" onclick="downloadEmployeeCardPNG()">Download PNG</button>
            <button type="button" class="sp-btn sp-btn-secondary sp-btn-sm" onclick="window.print()">Print Card</button>
            <a id="emp_card_whatsapp_link" href="#" target="_blank" class="sp-btn sp-btn-primary sp-btn-sm" style="background-color: #16a34a; border-color: #16a34a; display: inline-flex; align-items: center; gap: 4px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l.399.635-1.157 4.225 4.323-1.135.578.342z"/></svg>
                Share via WhatsApp
            </a>
            <button type="button" class="sp-btn sp-btn-secondary sp-btn-sm" onclick="spCloseModal('spEmpCardModal')">Close</button>
        </div>
    </div>
</div>

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
function downloadEmployeeCardPNG() {
    var canvas = document.createElement('canvas');
    canvas.width = 800;
    canvas.height = 1000;
    var ctx = canvas.getContext('2d');

    // Background
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, 800, 1000);

    // Header Banner
    var grad = ctx.createLinearGradient(0, 0, 800, 180);
    grad.addColorStop(0, '#0f172a');
    grad.addColorStop(1, '#1e293b');
    ctx.fillStyle = grad;
    ctx.fillRect(0, 0, 800, 180);

    // Header Text
    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 32px -apple-system, sans-serif';
    ctx.fillText('Sportedia', 40, 75);
    ctx.font = '16px -apple-system, sans-serif';
    ctx.fillStyle = '#94a3b8';
    ctx.fillText('EMPLOYEE CREDENTIALS CARD', 40, 115);

    // Role Badge
    var rBadge = jQuery('#emp_card_role_badge').text() || 'Staff';
    ctx.fillStyle = 'rgba(255,255,255,0.15)';
    ctx.fillRect(580, 50, 180, 40);
    ctx.fillStyle = '#ffffff';
    ctx.font = '14px -apple-system, sans-serif';
    ctx.fillText(rBadge, 600, 75);

    // Employee Info Section
    var eName  = jQuery('#emp_card_name').text() || 'Employee Name';
    var eId    = jQuery('#emp_card_id').text() || 'EMP001';
    var status = jQuery('#emp_card_status_badge').text() || 'Active Staff';

    ctx.fillStyle = '#0f172a';
    ctx.font = 'bold 32px -apple-system, sans-serif';
    ctx.fillText(eName, 40, 260);

    ctx.fillStyle = '#64748b';
    ctx.font = 'bold 20px monospace';
    ctx.fillText(eId, 40, 295);

    ctx.fillStyle = '#1e293b';
    ctx.fillRect(40, 315, 180, 32);
    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 14px -apple-system, sans-serif';
    ctx.fillText(status, 55, 336);

    // Details Grid Box
    ctx.fillStyle = '#f8fafc';
    ctx.strokeStyle = '#e2e8f0';
    ctx.lineWidth = 2;
    ctx.fillRect(40, 370, 720, 260);
    ctx.strokeRect(40, 370, 720, 260);

    var role  = jQuery('#emp_card_role_text').text();
    var phone = jQuery('#emp_card_phone').text();
    var shift = jQuery('#emp_card_shift').text();
    var email = jQuery('#emp_card_email').text();

    ctx.fillStyle = '#64748b';
    ctx.font = 'bold 14px -apple-system, sans-serif';
    ctx.fillText('OFFICIAL ROLE', 70, 415);
    ctx.fillStyle = '#0f172a';
    ctx.font = 'bold 20px -apple-system, sans-serif';
    ctx.fillText(role, 70, 445);

    ctx.fillStyle = '#64748b';
    ctx.font = 'bold 14px -apple-system, sans-serif';
    ctx.fillText('MOBILE CONTACT', 420, 415);
    ctx.fillStyle = '#0f172a';
    ctx.font = 'bold 20px -apple-system, sans-serif';
    ctx.fillText(phone, 420, 445);

    ctx.fillStyle = '#64748b';
    ctx.font = 'bold 14px -apple-system, sans-serif';
    ctx.fillText('STANDARD SHIFT', 70, 525);
    ctx.fillStyle = '#0f172a';
    ctx.font = 'bold 18px monospace';
    ctx.fillText(shift, 70, 555);

    ctx.fillStyle = '#64748b';
    ctx.font = 'bold 14px -apple-system, sans-serif';
    ctx.fillText('EMAIL ADDRESS', 420, 525);
    ctx.fillStyle = '#0f172a';
    ctx.font = 'bold 18px -apple-system, sans-serif';
    ctx.fillText(email, 420, 555);

    // Barcode Container Box
    ctx.fillStyle = '#ffffff';
    ctx.strokeStyle = '#cbd5e1';
    ctx.fillRect(40, 660, 720, 160);
    ctx.strokeRect(40, 660, 720, 160);

    // Render Barcode
    var cleanId = eId.replace('ID: ', '');
    ctx.fillStyle = '#0f172a';
    var hash = 0;
    for (var i = 0; i < cleanId.length; i++) hash = cleanId.charCodeAt(i) + ((hash << 5) - hash);
    var x = 120;
    for (var j = 0; j < 60; j++) {
        var w = (Math.abs(hash + j * 7) % 5) + 2;
        ctx.fillRect(x, 680, w, 80);
        x += w + 4;
    }

    ctx.fillStyle = '#0f172a';
    ctx.font = 'bold 22px monospace';
    ctx.fillText('*' + cleanId + '*', 320, 795);

    // Instructions Box
    ctx.fillStyle = '#f1f5f9';
    ctx.fillRect(40, 840, 720, 100);
    ctx.fillStyle = '#0f172a';
    ctx.fillRect(40, 840, 10, 100);

    ctx.fillStyle = '#475569';
    ctx.font = '15px -apple-system, sans-serif';
    ctx.fillText('Staff Instructions: Present this barcode ID at any Sportedia Kiosk terminal for', 65, 875);
    ctx.fillText('automated check-in & check-out. Credentials are strictly confidential.', 65, 905);

    // Trigger Download
    var link = document.createElement('a');
    link.download = 'Sportedia_Employee_Card_' + cleanId + '.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
}

function renderEmpBarcodeLines(containerId, text) {
    var $box = jQuery('#' + containerId).empty();
    var hash = 0;
    for (var i = 0; i < text.length; i++) hash = text.charCodeAt(i) + ((hash << 5) - hash);
    for (var j = 0; j < 32; j++) {
        var w = (Math.abs(hash + j * 7) % 4) + 1;
        $box.append(jQuery('<div>').css({ background: '#0f172a', width: w + 'px', height: '100%', borderRadius: '1px' }));
    }
}

function viewEmpCard(u) {
    var name     = u.name || 'Employee Name';
    var empId    = u.employee_id || 'EMP001';
    var role     = u.role || 'Staff Officer';
    var phone    = u.phone || 'N/A';
    var email    = u.email || 'staff@sportedia.online';
    var status   = u.status || 'active';
    var avatar   = u.avatar_url || '';
    var shift    = (u.work_schedule && u.work_schedule.start && u.work_schedule.end) ? (u.work_schedule.start + ' - ' + u.work_schedule.end) : '09:00 - 17:00';

    jQuery('#emp_card_name').text(name);
    jQuery('#emp_card_id').text('ID: ' + empId);
    jQuery('#emp_card_role_text').text(role);
    jQuery('#emp_card_role_badge').text(role.length > 15 ? role.substring(0, 15) + '...' : role);
    jQuery('#emp_card_phone').text(phone);
    jQuery('#emp_card_email').text(email);
    jQuery('#emp_card_shift').text(shift);

    if (avatar) {
        jQuery('#emp_card_avatar_img').attr('src', avatar).show();
        jQuery('#emp_card_avatar_initial').hide();
    } else {
        jQuery('#emp_card_avatar_img').hide();
        jQuery('#emp_card_avatar_initial').text(name.charAt(0).toUpperCase()).show();
    }

    jQuery('#emp_card_status_badge').text(status.toUpperCase() + ' STAFF')
        .attr('class', 'sp-badge ' + (status === 'active' ? 'sp-badge-active' : 'sp-badge-inactive'));

    jQuery('#emp_card_barcode_text').text('*' + empId + '*');
    renderEmpBarcodeLines('emp_card_barcode_lines', empId);

    var cleanPhone = phone.replace(/[^0-9]/g, '');
    var waText = encodeURIComponent("Hello " + name + ",\n\nHere is your Sportedia Official Employee Credentials ID Card:\nID: " + empId + "\nRole: " + role + "\nShift: " + shift + "\nStatus: " + status + "\n\nInstructions: Present this barcode at any Sportedia Kiosk for check-in/out. Credentials are non-transferable and strictly confidential.");
    jQuery('#emp_card_whatsapp_link').attr('href', 'https://api.whatsapp.com/send?phone=' + cleanPhone + '&text=' + waText);

    spOpenModal('spEmpCardModal');
}

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
