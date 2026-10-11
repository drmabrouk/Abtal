<?php
if (!defined('ABSPATH')) exit;

$att_date      = isset($_GET['att_date']) ? sanitize_text_field($_GET['att_date']) : date('Y-m-d');
$branch_filter = isset($_GET['branch_filter']) ? intval($_GET['branch_filter']) : 0;
$program_filter= isset($_GET['program_filter']) ? intval($_GET['program_filter']) : 0;

$attendanceRecords = Sportedia_Attendance_Manager::get_attendance($att_date, $branch_filter, $program_filter);
$branchesList      = Sportedia_Branch_Manager::get_branches();
$programsList      = Sportedia_Program_Manager::get_programs();
$usersList         = Sportedia_User_Manager::get_users();

$kiosk_page_id = get_option('sportedia_kiosk_page_id');
$kiosk_url = $kiosk_page_id ? get_permalink($kiosk_page_id) : home_url('/sportedia-kiosk/');
?>

<div class="sp-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 class="sp-page-title">Attendance & Absence Management</h1>
        <p class="sp-page-subtitle">Track daily check-ins for members, employees, and coaches across branches and programs.</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <button type="button" class="sp-btn sp-btn-secondary" onclick="exportAttendanceToExcel()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Export to Excel
        </button>
        <a href="<?php echo esc_url($kiosk_url); ?>" target="_blank" class="sp-btn sp-btn-secondary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 12h10"/><path d="M12 7v10"/></svg>
            Launch Attendance Kiosk
        </a>
        <button class="sp-btn sp-btn-primary" onclick="openAttModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Record Attendance
        </button>
    </div>
</div>

<div class="sp-card" style="padding: 16px;">
    <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap;">
        <input type="hidden" name="module" value="attendance">

        <div style="width: 180px;">
            <input type="date" name="att_date" class="sp-floating-input" value="<?php echo esc_attr($att_date); ?>">
        </div>

        <div style="width: 180px;">
            <select name="branch_filter" class="sp-floating-select">
                <option value="0">All Branches</option>
                <?php foreach ($branchesList as $b) : ?>
                    <option value="<?php echo esc_attr($b['id']); ?>" <?php selected($branch_filter, $b['id']); ?>><?php echo esc_html($b['branch_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="width: 180px;">
            <select name="program_filter" class="sp-floating-select">
                <option value="0">All Programs</option>
                <?php foreach ($programsList as $p) : ?>
                    <option value="<?php echo esc_attr($p['id']); ?>" <?php selected($program_filter, $p['id']); ?>><?php echo esc_html($p['program_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="sp-btn sp-btn-primary">Filter Records</button>
    </form>
</div>

<div class="sp-table-wrapper">
    <table class="sp-table">
        <thead>
            <tr>
                <th>Employee / Member</th>
                <th>Role & Branch</th>
                <th>Date</th>
                <th>Scheduled Shift</th>
                <th>Check-In / Check-Out</th>
                <th>Lateness</th>
                <th>Working Duration</th>
                <th>Status</th>
                <?php if (Sportedia_Roles::is_sys_admin() || Sportedia_Roles::is_general_mgr()) : ?>
                    <th style="text-align: right;">Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($attendanceRecords)) : ?>
                <?php foreach ($attendanceRecords as $att) : ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($att['user_name']); ?></strong><br>
                            <span style="font-size: 11px; color: var(--sp-text-muted);">ID: <?php echo esc_html($att['employee_id']); ?></span>
                        </td>
                        <td>
                            <span style="font-size: 13px; display: block;"><?php echo esc_html($att['role_label']); ?></span>
                            <span style="font-size: 11px; color: var(--sp-text-muted);"><?php echo esc_html($att['branch_name']); ?></span>
                        </td>
                        <td><?php echo esc_html($att['attendance_date']); ?></td>
                        <td>
                            <?php if (!empty($att['scheduled_start'])) : ?>
                                <span style="font-size: 12px; font-weight: 600;"><?php echo esc_html($att['scheduled_start'] . ' - ' . $att['scheduled_end']); ?></span>
                            <?php else : ?>
                                <span style="color: var(--sp-text-muted); font-size: 12px;">Standard Shift</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-size: 12px;">
                                <div><strong>In:</strong> <?php echo esc_html($att['check_in_time'] ? date('H:i', strtotime($att['check_in_time'])) : '-'); ?></div>
                                <div><strong>Out:</strong> <?php echo esc_html($att['check_out_time'] ? date('H:i', strtotime($att['check_out_time'])) : 'Active'); ?></div>
                            </div>
                        </td>
                        <td>
                            <?php if ($att['lateness_minutes'] > 0) : ?>
                                <span style="color: #991b1b; font-weight: 600; font-size: 12px;"><?php echo esc_html($att['lateness_minutes']); ?> mins</span>
                            <?php else : ?>
                                <span style="color: #166534; font-weight: 600; font-size: 12px;">On Time</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $dur = intval($att['working_duration_minutes']);
                            if ($dur > 0) {
                                $hrs = floor($dur / 60);
                                $mins = $dur % 60;
                                echo esc_html(($hrs > 0 ? $hrs . 'h ' : '') . $mins . 'm');
                            } else {
                                echo '<span style="color: var(--sp-text-muted); font-size: 12px;">In Progress</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <span class="sp-badge <?php echo $att['status'] === 'present' ? 'sp-badge-active' : 'sp-badge-inactive'; ?>">
                                <?php echo esc_html(ucfirst($att['status'])); ?>
                            </span>
                        </td>
                        <?php if (Sportedia_Roles::is_sys_admin() || Sportedia_Roles::is_general_mgr()) : ?>
                            <td style="text-align: right;">
                                <button class="sp-btn sp-btn-secondary sp-btn-sm" style="color:#dc2626;" onclick="deleteAtt(<?php echo $att['id']; ?>)">Delete</button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="9" style="text-align: center; color: var(--sp-text-muted); padding: 32px;">No employee attendance records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Attendance Modal -->
<div id="spAttModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 480px;">
        <div class="sp-modal-header">
            <h3 class="sp-modal-title">Record Attendance</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spAttModal')">&times;</button>
        </div>

        <form id="spAttForm">
            <div class="sp-form-group">
                <select id="sp_att_user_id" name="user_id" class="sp-floating-select" required>
                    <option value="">Select Member or Coach</option>
                    <?php foreach ($usersList as $u) : ?>
                        <option value="<?php echo esc_attr($u['id']); ?>"><?php echo esc_html($u['name'] . ' (' . $u['role'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="sp_att_user_id" class="sp-floating-label">User *</label>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <select id="sp_att_branch_id" name="branch_id" class="sp-floating-select">
                        <option value="0">Default Branch</option>
                        <?php foreach ($branchesList as $b) : ?>
                            <option value="<?php echo esc_attr($b['id']); ?>"><?php echo esc_html($b['branch_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="sp_att_branch_id" class="sp-floating-label">Branch</label>
                </div>

                <div class="sp-form-group">
                    <select id="sp_att_program_id" name="program_id" class="sp-floating-select">
                        <option value="0">General Check-in</option>
                        <?php foreach ($programsList as $p) : ?>
                            <option value="<?php echo esc_attr($p['id']); ?>"><?php echo esc_html($p['program_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="sp_att_program_id" class="sp-floating-label">Program</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="date" id="sp_att_date" name="attendance_date" class="sp-floating-input" value="<?php echo esc_attr(date('Y-m-d')); ?>" required>
                    <label for="sp_att_date" class="sp-floating-label">Date *</label>
                </div>

                <div class="sp-form-group">
                    <select id="sp_att_user_type" name="user_type" class="sp-floating-select">
                        <option value="customer">Customer</option>
                        <option value="coach">Coach / Trainer</option>
                    </select>
                    <label for="sp_att_user_type" class="sp-floating-label">User Type</label>
                </div>
            </div>

            <div class="sp-form-group">
                <select id="sp_att_status" name="status" class="sp-floating-select">
                    <option value="present">Present</option>
                    <option value="absent">Absent</option>
                    <option value="late">Late</option>
                </select>
                <label for="sp_att_status" class="sp-floating-label">Attendance Status</label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--sp-border-color); padding-top: 16px;">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spAttModal')">Cancel</button>
                <button type="submit" class="sp-btn sp-btn-primary">Save Check-in</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAttModal() {
    jQuery('#spAttForm')[0].reset();
    spOpenModal('spAttModal');
}

function deleteAtt(attId) {
    if (confirm('Are you sure you want to delete this attendance record?')) {
        jQuery.post(sportedia_vars.ajax_url, {
            action: 'sportedia_delete_attendance',
            nonce: sportedia_vars.nonce,
            att_id: attId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || 'Failed to delete record.');
            }
        });
    }
}

jQuery('#spAttForm').on('submit', function(e) {
    e.preventDefault();
    var formData = jQuery(this).serialize() + '&action=sportedia_record_attendance&nonce=' + sportedia_vars.nonce;
    jQuery.post(sportedia_vars.ajax_url, formData, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert(response.data || 'Error recording attendance.');
        }
    });
});

function exportAttendanceToExcel() {
    var table = document.querySelector('.sp-table');
    if (!table) return;

    var html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    html += '<head><meta charset="utf-8"/><style>table{border-collapse:collapse;} th,td{border:1px solid #000;padding:6px;}</style></head><body>';
    html += '<h2>Sportedia - Attendance Report (' + <?php echo json_encode($att_date); ?> + ')</h2>';
    html += table.outerHTML;
    html += '</body></html>';

    var blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'Sportedia_Attendance_' + <?php echo json_encode($att_date); ?> + '.xls';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
