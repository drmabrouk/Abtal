<?php
if (!defined('ABSPATH')) exit;

$report_date   = isset($_GET['report_date']) ? sanitize_text_field($_GET['report_date']) : date('Y-m-d');
$branch_filter = isset($_GET['branch_filter']) ? intval($_GET['branch_filter']) : 0;

$reportData   = Sportedia_Reports_Manager::get_daily_report_data($report_date, $branch_filter);
$branchesList = Sportedia_Branch_Manager::get_branches();
$usersList    = Sportedia_User_Manager::get_users();
$app_url      = get_permalink(get_option('sportedia_page_id'));
$export_nonce = wp_create_nonce('sportedia_nonce');

$base_export_args = '&nonce=' . $export_nonce . '&branch_filter=' . $branch_filter;
?>

<div class="sp-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
    <div>
        <h1 class="sp-page-title">Daily Reports & Data Operations</h1>
        <p class="sp-page-subtitle">Analyze daily attendance, new registrations, operational performance, and manage CSV export/import.</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sportedia_export_csv&export_type=users' . $base_export_args)); ?>" class="sp-btn sp-btn-secondary sp-btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export Users CSV
        </a>
        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sportedia_export_csv&export_type=subscriptions' . $base_export_args)); ?>" class="sp-btn sp-btn-secondary sp-btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export Subscriptions CSV
        </a>
        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sportedia_export_csv&export_type=branches' . $base_export_args)); ?>" class="sp-btn sp-btn-secondary sp-btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export Branches CSV
        </a>
        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sportedia_export_csv&export_type=attendance' . $base_export_args)); ?>" class="sp-btn sp-btn-secondary sp-btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export Attendance CSV
        </a>
        <button class="sp-btn sp-btn-primary sp-btn-sm" onclick="openPayrollModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            Employee Payroll Slip
        </button>
        <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick="openImportModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Import Users CSV
        </button>
    </div>
</div>

<div class="sp-card" style="padding: 16px; margin-bottom: 20px;">
    <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <input type="hidden" name="module" value="reports">

        <div style="width: 200px;">
            <input type="date" name="report_date" class="sp-floating-input" value="<?php echo esc_attr($report_date); ?>">
        </div>

        <div style="width: 220px;">
            <select name="branch_filter" class="sp-floating-select">
                <option value="0">All Branches</option>
                <?php foreach ($branchesList as $b) : ?>
                    <option value="<?php echo esc_attr($b['id']); ?>" <?php selected($branch_filter, $b['id']); ?>><?php echo esc_html($b['branch_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="sp-btn sp-btn-primary">Generate Filtered Report</button>
    </form>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px;">
    <div class="sp-card" style="margin-bottom:0; padding: 18px;">
        <div style="font-size: 13px; color: var(--sp-text-muted); font-weight: 500;">Present Today</div>
        <div style="font-size: 32px; font-weight: 700; margin-top: 8px; color: #166534;"><?php echo esc_html($reportData['present_count']); ?></div>
    </div>

    <div class="sp-card" style="margin-bottom:0; padding: 18px;">
        <div style="font-size: 13px; color: var(--sp-text-muted); font-weight: 500;">Absent / Late Today</div>
        <div style="font-size: 32px; font-weight: 700; margin-top: 8px; color: #991b1b;"><?php echo esc_html($reportData['absent_count'] + $reportData['late_count']); ?></div>
    </div>

    <div class="sp-card" style="margin-bottom:0; padding: 18px;">
        <div style="font-size: 13px; color: var(--sp-text-muted); font-weight: 500;">New Subscriptions</div>
        <div style="font-size: 32px; font-weight: 700; margin-top: 8px; color: var(--sp-text-main);"><?php echo esc_html($reportData['new_subs']); ?></div>
    </div>

    <div class="sp-card" style="margin-bottom:0; padding: 18px;">
        <div style="font-size: 13px; color: var(--sp-text-muted); font-weight: 500;">Day Revenue</div>
        <div style="font-size: 32px; font-weight: 700; margin-top: 8px; color: var(--sp-text-main);"><?php echo esc_html(Sportedia_Finance::format_price($reportData['revenue'])); ?></div>
    </div>
</div>

<div class="sp-card">
    <h3 style="margin-top:0;">Daily Operational Summary for <?php echo esc_html($report_date); ?></h3>
    <p style="color: var(--sp-text-muted); font-size: 13px; margin: 0;">Summary report data generated from active branches and member attendance tracking records.</p>
</div>

<!-- Employee Payroll Slip Generator Modal -->
<div id="spPayrollModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 520px;">
        <div class="sp-modal-header">
            <h3 class="sp-modal-title">Generate Employee Payroll & Attendance Slip</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spPayrollModal')">&times;</button>
        </div>

        <form id="spPayrollForm">
            <div class="sp-form-group">
                <select id="pay_user_id" name="user_id" class="sp-floating-select" required>
                    <option value="">Select Employee / Staff Member</option>
                    <?php foreach ($usersList as $u) : ?>
                        <option value="<?php echo esc_attr($u['id']); ?>"><?php echo esc_html($u['name'] . ' (ID: ' . $u['employee_id'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="pay_user_id" class="sp-floating-label">Employee *</label>
            </div>

            <div class="sp-form-group">
                <input type="month" id="pay_month_year" name="month_year" class="sp-floating-input" value="<?php echo esc_attr(date('Y-m')); ?>" required>
                <label for="pay_month_year" class="sp-floating-label">Payroll Month *</label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--sp-border-color); padding-top: 16px;">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spPayrollModal')">Cancel</button>
                <button type="submit" class="sp-btn sp-btn-primary">Generate Slip</button>
            </div>
        </form>
    </div>
</div>

<!-- Printable Employee Payroll Slip Modal -->
<div id="spPayrollReportModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 500px; padding: 0; border-radius: var(--sp-radius); overflow: hidden;" id="spPrintableInvoice">
        <div class="sp-modal-header sp-no-print" style="padding: 12px 16px; margin: 0; background: var(--sp-bg-main);">
            <h3 class="sp-modal-title">Employee Attendance & Payroll Slip</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spPayrollReportModal')">&times;</button>
        </div>

        <div style="padding: 24px; background: #ffffff;">
            <div style="border-bottom: 2px solid #000; padding-bottom: 12px; margin-bottom: 16px; display: flex; justify-content: space-between;">
                <div>
                    <strong style="font-size: 18px;">Sportedia Online</strong>
                    <span id="pr_month" style="font-size: 12px; color: var(--sp-text-muted); display: block;">2026-03</span>
                </div>
                <div style="text-align: right;">
                    <strong style="font-size: 13px;">Payroll Statement</strong>
                    <span style="display: block; font-size: 11px; color: var(--sp-text-muted);"><?php echo esc_html(date('Y-m-d')); ?></span>
                </div>
            </div>

            <div style="font-size: 13px; margin-bottom: 16px; background: #f8f9fa; border: 1px solid #e5e7eb; padding: 12px; border-radius: 8px;">
                <div><strong>Employee:</strong> <span id="pr_emp_name">John Doe</span> (<span id="pr_emp_id">EMP-001</span>)</div>
                <div><strong>Base Salary / Pay:</strong> <span id="pr_base_sal">AED 0.00</span></div>
                <div><strong>Scheduled Work:</strong> <span id="pr_sched">22 days (176 hrs)</span></div>
                <div><strong>Actual Present:</strong> <span id="pr_actual">22 days (176 hrs)</span></div>
            </div>

            <table style="width: 100%; font-size: 12px; border-collapse: collapse; margin-bottom: 16px; background: #f8f9fa; border: 1px solid #e5e7eb;">
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 8px 10px;">Total Lateness (Mins)</td>
                    <td style="text-align: right; padding: 8px 10px;" id="pr_late_mins">0 mins</td>
                </tr>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 8px 10px;">Unexcused Absences</td>
                    <td style="text-align: right; padding: 8px 10px;" id="pr_absent_days">0 days</td>
                </tr>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 8px 10px;">Total Deductions</td>
                    <td style="text-align: right; padding: 8px 10px; color: #991b1b; font-weight: 700;" id="pr_deductions">AED 0.00</td>
                </tr>
                <tr style="font-weight: 700; font-size: 14px; background: #ffffff;">
                    <td style="padding: 10px; border-top: 2px solid #000;">Net Payable Amount</td>
                    <td style="text-align: right; padding: 10px; border-top: 2px solid #000; color: #166534;" id="pr_net_pay">AED 0.00</td>
                </tr>
            </table>

            <div style="margin-bottom: 16px; font-size: 12px; border-left: 3px solid #000000; padding-left: 10px;">
                <strong>Calculation & Deduction Reasons:</strong>
                <div id="pr_reasons" style="margin-top: 4px; color: var(--sp-text-muted);"></div>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 8px; padding: 12px 20px; background: var(--sp-bg-main); border-top: 1px solid var(--sp-border-color);" class="sp-no-print">
            <button type="button" class="sp-btn sp-btn-secondary sp-btn-sm" onclick="window.print()">Print Payroll Slip</button>
            <button type="button" class="sp-btn sp-btn-secondary sp-btn-sm" onclick="spCloseModal('spPayrollReportModal')">Close</button>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="spImportModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 480px;">
        <div class="sp-modal-header">
            <h3 class="sp-modal-title">Import Users CSV</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spImportModal')">&times;</button>
        </div>

        <form id="spImportForm" enctype="multipart/form-data">
            <div style="margin-bottom: 20px;">
                <label style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 8px;">Select CSV File</label>
                <input type="file" name="csv_file" accept=".csv" required style="width: 100%; border: 1px dashed var(--sp-border-color); padding: 20px; border-radius: var(--sp-radius);">
                <p style="font-size: 11px; color: var(--sp-text-muted); margin-top: 6px;">CSV columns format: Employee ID, Full Name, Email, Role Key</p>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--sp-border-color); padding-top: 16px;">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spImportModal')">Cancel</button>
                <button type="submit" class="sp-btn sp-btn-primary">Upload & Import</button>
            </div>
        </form>
    </div>
</div>

<script>
function openImportModal() {
    spOpenModal('spImportModal');
}

function openPayrollModal() {
    spOpenModal('spPayrollModal');
}

jQuery('#spPayrollForm').on('submit', function(e) {
    e.preventDefault();
    var userId = jQuery('#pay_user_id').val();
    var monthYear = jQuery('#pay_month_year').val();

    jQuery.post(sportedia_vars.ajax_url, {
        action: 'sportedia_get_payroll_report',
        nonce: sportedia_vars.nonce,
        user_id: userId,
        month_year: monthYear
    }, function(res) {
        if (res.success) {
            var d = res.data;
            spCloseModal('spPayrollModal');

            jQuery('#pr_emp_name').text(d.employee_name);
            jQuery('#pr_emp_id').text(d.employee_id);
            jQuery('#pr_month').text(d.month_year);
            jQuery('#pr_base_sal').text(d.base_salary);
            jQuery('#pr_sched').text(d.scheduled_days + ' days (' + d.scheduled_hours + ' hrs)');
            jQuery('#pr_actual').text(d.actual_present_days + ' days (' + d.actual_working_hours + ' hrs)');
            jQuery('#pr_late_mins').text(d.total_lateness_mins + ' mins');
            jQuery('#pr_absent_days').text(d.absent_days + ' days');
            jQuery('#pr_deductions').text(d.total_deductions);
            jQuery('#pr_net_pay').text(d.net_payable_amount);

            var reasonsHtml = '';
            d.deduction_reasons.forEach(function(r) {
                reasonsHtml += '<div>• ' + r + '</div>';
            });
            jQuery('#pr_reasons').html(reasonsHtml);

            spOpenModal('spPayrollReportModal');
        } else {
            alert(res.data || 'Failed to generate payroll slip.');
        }
    });
});

jQuery('#spImportForm').on('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    formData.append('action', 'sportedia_import_csv');
    formData.append('nonce', sportedia_vars.nonce);

    jQuery.ajax({
        url: sportedia_vars.ajax_url,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            } else {
                alert(response.data || 'Import failed.');
            }
        }
    });
});
</script>
