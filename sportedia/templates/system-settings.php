<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$site_name          = Sportedia_Settings_Manager::get_setting('site_name', 'Sportedia');
$system_desc        = Sportedia_Settings_Manager::get_setting('system_desc', 'Complete Online Sports Management System');
$system_logo        = Sportedia_Settings_Manager::get_setting('system_logo', '');
$system_icon        = Sportedia_Settings_Manager::get_setting('system_icon', '');
$support_email      = Sportedia_Settings_Manager::get_setting('support_email', 'support@sportedia.online');

$timezone           = Sportedia_Settings_Manager::get_setting('timezone', 'Asia/Dubai');
$currency           = Sportedia_Settings_Manager::get_setting('currency', 'AED');
$default_vat        = Sportedia_Settings_Manager::get_setting('default_vat_rate', '5');

$default_user_role  = Sportedia_Settings_Manager::get_setting('default_user_role', 'sportedia_customer');
$grace_days         = Sportedia_Settings_Manager::get_setting('subscription_grace_days', '3');
$enable_renewal     = Sportedia_Settings_Manager::get_setting('enable_renewal_reminders', 'yes');

$branch_hours       = Sportedia_Settings_Manager::get_setting('branch_operating_hours', '06:00 - 23:00');
$max_branches       = Sportedia_Settings_Manager::get_setting('max_branches_per_mgr', '5');

$default_capacity   = Sportedia_Settings_Manager::get_setting('default_program_capacity', '20');
$allow_overbook     = Sportedia_Settings_Manager::get_setting('allow_overbooking', 'no');

$default_att_status = Sportedia_Settings_Manager::get_setting('default_attendance_status', 'present');
$allow_past_att     = Sportedia_Settings_Manager::get_setting('allow_past_attendance', 'yes');
$lateness_allowance = Sportedia_Settings_Manager::get_setting('monthly_lateness_allowance', '30');
$checkout_threshold = Sportedia_Settings_Manager::get_setting('checkout_threshold_minutes', '30');

$active_tab         = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'branding';

$app_url = get_permalink(get_option('sportedia_page_id'));

$tabs = array(
    'branding'       => 'General & Branding',
    'membership_num' => 'Membership Numbering',
    'invoice_num'    => 'Invoice Numbering',
    'roles'          => 'Roles & Permissions',
    'branches'       => 'Branches',
    'financial'      => 'Financial / VAT',
    'attendance'     => 'Attendance',
    'backup'         => 'Backup & Restore',
    'reset'          => 'Data Reset',
    'activity_log'   => 'Activity Log'
);

$timezones_list = array(
    'Asia/Dubai'     => 'Dubai / UAE (GST +04:00)',
    'Asia/Abu_Dhabi' => 'Abu Dhabi (GST +04:00)',
    'UTC'            => 'UTC Standard Time',
    'Europe/London'  => 'London / UK (GMT)',
    'America/New_York' => 'New York / Eastern Time'
);
?>

<div class="sp-page-header" style="margin-bottom: 20px;">
    <h1 class="sp-page-title">Centralized System Settings</h1>
    <p class="sp-page-subtitle">Centralized system administration, membership numbering, roles, backup, data reset, and audit trail.</p>
</div>

<!-- Streamlined Settings Navigation Tabs -->
<div style="display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--sp-border-color); padding-bottom: 12px; flex-wrap: wrap;">
    <?php foreach ($tabs as $key => $label) : ?>
        <a href="<?php echo esc_url(add_query_arg(array('module' => 'settings', 'tab' => $key), $app_url)); ?>"
           class="sp-btn <?php echo $active_tab === $key ? 'sp-btn-primary' : 'sp-btn-secondary'; ?> sp-btn-sm">
            <?php echo esc_html($label); ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="sp-card" style="max-width: 780px;">
    <form id="spSettingsForm">
        <?php if ($active_tab === 'branding') : ?>
            <h3 style="margin-top:0;">System Branding & General Info</h3>

            <div class="sp-form-group">
                <input type="text" id="setting_site_name" name="settings[site_name]" class="sp-floating-input" value="<?php echo esc_attr($site_name); ?>" required>
                <label for="setting_site_name" class="sp-floating-label">System Name *</label>
            </div>

            <div class="sp-form-group">
                <input type="text" id="setting_system_desc" name="settings[system_desc]" class="sp-floating-input" value="<?php echo esc_attr($system_desc); ?>">
                <label for="setting_system_desc" class="sp-floating-label">System Description</label>
            </div>

            <div class="sp-form-group">
                <input type="email" id="setting_support_email" name="settings[support_email]" class="sp-floating-input" value="<?php echo esc_attr($support_email); ?>" required>
                <label for="setting_support_email" class="sp-floating-label">Support Email Address *</label>
            </div>

        <?php elseif ($active_tab === 'membership_num') :
            $next_seq_val = get_option('sportedia_next_member_sequence', '701');
        ?>
            <h3 style="margin-top:0;">Membership Numbering & Serial Allocation</h3>
            <p style="font-size: 12px; color: var(--sp-text-muted); margin-bottom: 16px;">New Member IDs auto-generate as <strong>YEAR + Sequence Number</strong> (e.g., <?php echo date('Y'); ?>701). Sequence numbers never restart upon deletion.</p>

            <div class="sp-form-group">
                <input type="number" id="setting_next_seq" name="settings[next_member_sequence]" class="sp-floating-input" value="<?php echo esc_attr($next_seq_val); ?>" required>
                <label for="setting_next_seq" class="sp-floating-label">Next Membership Starting Sequence (e.g., 701)</label>
            </div>

        <?php elseif ($active_tab === 'invoice_num') :
            $next_inv_val = get_option('sportedia_next_invoice_sequence', '1001');
        ?>
            <h3 style="margin-top:0;">Invoice Numbering Sequence</h3>

            <div class="sp-form-group">
                <input type="number" id="setting_next_inv" name="settings[next_invoice_sequence]" class="sp-floating-input" value="<?php echo esc_attr($next_inv_val); ?>" required>
                <label for="setting_next_inv" class="sp-floating-label">Next Invoice Starting Number (e.g., 1001)</label>
            </div>

        <?php elseif ($active_tab === 'roles') : ?>
            <h3 style="margin-top:0;">Roles & Permissions Overview</h3>
            <p style="font-size: 12px; color: var(--sp-text-muted); margin-bottom: 16px;">Sportedia 10-Role Hierarchy: System Administrator, General Manager, Administrative Manager, Sports Facility Manager, Sports Supervisor, Registration Officer, Coach, HR Officer, Accounts & Finance, Member.</p>

            <div class="sp-form-group">
                <select id="setting_default_role" name="settings[default_user_role]" class="sp-floating-select">
                    <option value="sportedia_customer" <?php selected($default_user_role, 'sportedia_customer'); ?>>Member</option>
                    <option value="sportedia_coach" <?php selected($default_user_role, 'sportedia_coach'); ?>>Coach</option>
                </select>
                <label for="setting_default_role" class="sp-floating-label">Default New Account Role</label>
            </div>

        <?php elseif ($active_tab === 'financial') : ?>
            <h3 style="margin-top:0;">Financial & UAE VAT Defaults</h3>

            <div class="sp-form-group">
                <select id="setting_currency" name="settings[currency]" class="sp-floating-select">
                    <option value="AED" <?php selected($currency, 'AED'); ?>>AED (United Arab Emirates Dirham)</option>
                </select>
                <label for="setting_currency" class="sp-floating-label">Currency</label>
            </div>

            <div class="sp-form-group">
                <input type="number" id="setting_vat" name="settings[default_vat_rate]" class="sp-floating-input" value="<?php echo esc_attr($default_vat); ?>">
                <label for="setting_vat" class="sp-floating-label">Standard UAE VAT Rate (%)</label>
            </div>

        <?php elseif ($active_tab === 'backup') : ?>
            <h3 style="margin-top:0;">Backup & Restore System</h3>
            <p style="font-size: 12px; color: var(--sp-text-muted); margin-bottom: 16px;">Export full JSON system backup containing members, subscriptions, attendance, invoices, and settings.</p>

            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <button type="button" class="sp-btn sp-btn-primary" onclick="exportFullSystemBackup()">
                    Download Full JSON System Backup
                </button>
            </div>

        <?php elseif ($active_tab === 'reset') : ?>
            <h3 style="margin-top:0; color: #dc2626;">Controlled System Reset & Data Management</h3>
            <p style="font-size: 12px; color: var(--sp-text-muted); margin-bottom: 16px;">Perform controlled reset operations independently. Every reset requires explicit confirmation and is recorded in the activity log.</p>

            <div style="display: flex; flex-direction: column; gap: 14px;">
                <div style="background: #fff; border: 1px solid var(--sp-border-color); padding: 14px; border-radius: 8px;">
                    <strong>Reset Membership Numbering Sequence</strong>
                    <p style="font-size: 11px; color: var(--sp-text-muted); margin: 2px 0 8px 0;">Resets the next new membership sequence counter back to 701 without deleting members.</p>
                    <button type="button" class="sp-btn sp-btn-secondary sp-btn-sm" style="color: #dc2626;" onclick="resetMemberSequenceAdmin()">Reset Membership Sequence Only</button>
                </div>

                <div style="background: #fff; border: 1px solid var(--sp-border-color); padding: 14px; border-radius: 8px;">
                    <strong>Reset Transactional Data (Subscriptions & Attendance)</strong>
                    <p style="font-size: 11px; color: var(--sp-text-muted); margin: 2px 0 8px 0;">Deletes subscription records and attendance logs while preserving user accounts and branches.</p>
                    <button type="button" class="sp-btn sp-btn-secondary sp-btn-sm" style="color: #dc2626;" onclick="resetTransactionalDataAdmin()">Reset Subscriptions & Attendance Only</button>
                </div>
            </div>

        <?php elseif ($active_tab === 'activity_log') :
            $log_table = $wpdb->prefix . 'sportedia_activity_log';
            $logs = $wpdb->get_results("SELECT * FROM $log_table ORDER BY id DESC LIMIT 50", ARRAY_A);
        ?>
            <h3 style="margin-top:0;">Searchable Activity & Audit Log</h3>

            <div class="sp-table-wrapper" style="max-height: 360px; overflow-y: auto;">
                <table class="sp-table">
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>User ID</th>
                            <th>Action</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)) : ?>
                            <?php foreach ($logs as $l) : ?>
                                <tr>
                                    <td style="font-family: monospace; font-size: 11px;"><?php echo esc_html($l['created_at']); ?></td>
                                    <td><?php echo esc_html($l['user_id']); ?></td>
                                    <td><code><?php echo esc_html($l['action']); ?></code></td>
                                    <td><?php echo esc_html($l['details']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="4" style="text-align:center; padding: 20px; color: var(--sp-text-muted);">No activity records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php else : ?>
            <h3 style="margin-top:0;">System Settings</h3>
            <p style="font-size: 13px; color: var(--sp-text-muted);">Select a settings tab above to manage system configuration options.</p>
        <?php endif; ?>

        <?php if (in_array($active_tab, array('branding', 'membership_num', 'invoice_num', 'roles', 'financial', 'branches', 'attendance'), true)) : ?>
            <div style="border-top: 1px solid var(--sp-border-color); padding-top: 16px; margin-top: 16px;">
                <button type="submit" class="sp-btn sp-btn-primary">Save Settings</button>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
jQuery('#spSettingsForm').on('submit', function(e) {
    e.preventDefault();
    var formData = jQuery(this).serialize() + '&action=sportedia_save_settings&nonce=' + sportedia_vars.nonce;
    jQuery.post(sportedia_vars.ajax_url, formData, function(response) {
        if (response.success) {
            alert(response.data);
            location.reload();
        } else {
            alert(response.data || 'Failed to save settings.');
        }
    });
});

function resetMemberSequenceAdmin() {
    if (confirm('RESET MEMBERSHIP SEQUENCE: Set next Member ID sequence to 701? (Existing members will remain unchanged).')) {
        jQuery.post(sportedia_vars.ajax_url, {
            action: 'sportedia_save_settings',
            nonce: sportedia_vars.nonce,
            settings: { next_member_sequence: '701' }
        }, function(res) {
            if (res.success) {
                alert('Membership sequence reset to 701.');
                location.reload();
            }
        });
    }
}

function resetTransactionalDataAdmin() {
    if (confirm('WARNING: Are you sure you want to reset subscriptions and attendance transactional records? This cannot be undone.')) {
        jQuery.post(sportedia_vars.ajax_url, {
            action: 'sportedia_reset_transactional_data',
            nonce: sportedia_vars.nonce
        }, function(res) {
            if (res.success) {
                alert(res.data || 'Transactional data reset completed.');
                location.reload();
            } else {
                alert(res.data || 'Failed to reset data.');
            }
        });
    }
}

function exportFullSystemBackup() {
    window.location.href = sportedia_vars.ajax_url + '?action=sportedia_export_json_backup&nonce=' + sportedia_vars.nonce;
}
</script>
