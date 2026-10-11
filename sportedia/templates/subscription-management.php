<?php
if (!defined('ABSPATH')) exit;

$search        = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$branch_filter = isset($_GET['branch_filter']) ? intval($_GET['branch_filter']) : 0;
$status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';

$subscriptions = Sportedia_Subscription_Manager::get_subscriptions($search, $branch_filter, $status_filter);
$branchesList  = Sportedia_Branch_Manager::get_branches();
$usersList     = Sportedia_User_Manager::get_users();
$programsList  = Sportedia_Program_Manager::get_programs();

$curr_u = wp_get_current_user();
$admin_roles = array('sportedia_sys_admin', 'sportedia_general_mgr', 'sportedia_facility_mgr', 'sportedia_ops_mgr', 'sportedia_finance_mgr', 'administrator');
$can_view_session_count = false;
foreach ((array)$curr_u->roles as $r) {
    if (in_array($r, $admin_roles, true) || current_user_can('manage_options')) {
        $can_view_session_count = true;
        break;
    }
}
?>

<?php $export_nonce = wp_create_nonce('sportedia_nonce'); ?>
<div class="sp-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
    <div>
        <h1 class="sp-page-title">Subscription Management</h1>
        <p class="sp-page-subtitle">Track customer memberships, subscription plans, start/end dates, and statuses.</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sportedia_export_csv&export_type=subscriptions&search=' . urlencode($search) . '&branch_filter=' . $branch_filter . '&status_filter=' . urlencode($status_filter) . '&nonce=' . $export_nonce)); ?>" class="sp-btn sp-btn-secondary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Export to Excel / CSV
        </a>
        <button class="sp-btn sp-btn-primary" onclick="openSubModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Subscription / Registration
        </button>
    </div>
</div>

<!-- Combined Filter Bar -->
<div class="sp-card" style="padding: 16px; margin-bottom: 20px;">
    <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <input type="hidden" name="module" value="subscriptions">

        <div style="flex: 2; min-width: 220px;">
            <input type="text" name="search" id="sp_filter_search" class="sp-floating-input" placeholder="Search plan, member name, ID or mobile..." value="<?php echo esc_attr($search); ?>">
        </div>

        <div style="flex: 1; min-width: 160px;">
            <select name="branch_filter" id="sp_filter_branch" class="sp-floating-select" style="min-width: 180px;">
                <option value="0">All Branches</option>
                <?php foreach ($branchesList as $b) : ?>
                    <option value="<?php echo esc_attr($b['id']); ?>" <?php selected($branch_filter, $b['id']); ?>><?php echo esc_html($b['branch_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="flex: 1; min-width: 160px;">
            <select name="status_filter" id="sp_filter_status" class="sp-floating-select" style="min-width: 160px;">
                <option value="">All Statuses</option>
                <option value="active" <?php selected($status_filter, 'active'); ?>>Active</option>
                <option value="expired" <?php selected($status_filter, 'expired'); ?>>Expired</option>
                <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>>Cancelled</option>
            </select>
        </div>

        <button type="submit" class="sp-btn sp-btn-primary">Filter</button>
        <?php if (!empty($search) || $branch_filter > 0 || !empty($status_filter)) : ?>
            <a href="?module=subscriptions" class="sp-btn sp-btn-secondary">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Unified Full-Width Extended Rows Layout (1 Row Per Subscription) -->
<?php if (!empty($subscriptions)) : ?>
    <div style="display: flex; flex-direction: column; gap: 12px;" id="subscriptions_container">
        <?php foreach ($subscriptions as $s) : ?>
            <div class="sp-card" style="margin-bottom: 0; padding: 16px 20px; border-radius: var(--sp-radius); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                <div style="flex: 2; min-width: 220px;">
                    <strong style="font-size: 15px; color: var(--sp-text-main); display: block;"><?php echo esc_html($s['member_name']); ?></strong>
                    <span style="font-size: 11px; color: var(--sp-text-muted); font-family: monospace;">ID: <?php echo esc_html($s['employee_id']); ?></span>
                </div>

                <div style="flex: 2; min-width: 200px;">
                    <span style="font-size: 10px; color: var(--sp-text-muted); display: block; text-transform: uppercase;">Plan & Type</span>
                    <strong style="font-size: 13px; color: #000;"><?php echo esc_html($s['plan_name']); ?></strong>
                    <span style="font-size: 11px; color: var(--sp-text-muted); display: block;"><?php echo esc_html(ucfirst($s['subscription_type'])); ?> | <?php echo esc_html($s['branch_name']); ?></span>
                </div>

                <div style="flex: 2; min-width: 180px;">
                    <span style="font-size: 10px; color: var(--sp-text-muted); display: block; text-transform: uppercase;">Validity Period</span>
                    <span style="font-size: 12px; font-family: monospace; color: var(--sp-text-main);"><?php echo esc_html($s['start_date'] . ' &rarr; ' . $s['end_date']); ?></span>
                </div>

                <div style="flex: 1; min-width: 130px;">
                    <span style="font-size: 10px; color: var(--sp-text-muted); display: block; text-transform: uppercase;">Price</span>
                    <strong style="font-size: 14px; color: #000;"><?php echo esc_html(Sportedia_Finance::format_price($s['price'])); ?></strong>
                </div>

                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="sp-badge <?php echo $s['status'] === 'active' ? 'sp-badge-active' : 'sp-badge-inactive'; ?>">
                        <?php echo esc_html(ucfirst($s['status'])); ?>
                    </span>

                    <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='viewA5Invoice(<?php echo json_encode($s); ?>)' title="Print Invoice">Invoice</button>
                    <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='viewA6Card(<?php echo json_encode($s); ?>)' title="Membership Card">Card</button>
                    <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='editSub(<?php echo json_encode($s); ?>)'>Edit</button>
                    <button class="sp-btn sp-btn-secondary sp-btn-sm" style="color:#dc2626;" onclick="deleteSub(<?php echo $s['id']; ?>)">Delete</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <div class="sp-card" style="text-align: center; color: var(--sp-text-muted); padding: 48px;">
        <h3>No subscriptions found</h3>
        <p>No subscription records match your filter criteria.</p>
    </div>
<?php endif; ?>

<!-- Multi-Step Subscription Registration Modal (Width: 768px) -->
<div id="spSubModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 768px;">
        <div class="sp-modal-header">
            <h3 id="spSubModalTitle" class="sp-modal-title">New Member Registration & Subscription</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spSubModal')">&times;</button>
        </div>

        <!-- VISIBLE MULTI-STEP PROGRESS INDICATOR -->
        <div style="display: flex; background: #f8fafc; border: 1px solid var(--sp-border-color); border-radius: 8px; padding: 6px; margin-bottom: 20px;" id="sub_step_progress_bar">
            <div class="sp-step-tab active" id="step_tab_1" onclick="goToSubStep(1)" style="flex:1; text-align:center; padding: 8px; font-size: 12px; font-weight: 700; border-radius: 6px; cursor: pointer; color: #0284c7; background: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                1. Member Information
            </div>
            <div class="sp-step-tab" id="step_tab_2" onclick="goToSubStep(2)" style="flex:1; text-align:center; padding: 8px; font-size: 12px; font-weight: 700; border-radius: 6px; cursor: pointer; color: var(--sp-text-muted);">
                2. Program & Dates
            </div>
            <div class="sp-step-tab" id="step_tab_3" onclick="goToSubStep(3)" style="flex:1; text-align:center; padding: 8px; font-size: 12px; font-weight: 700; border-radius: 6px; cursor: pointer; color: var(--sp-text-muted);">
                3. Payment & Invoice
            </div>
        </div>

        <form id="spSubForm" oninput="saveSubDraft()">
            <input type="hidden" id="sp_sub_id" name="sub_id" value="0">
            <input type="hidden" id="sp_is_renewal" name="is_renewal" value="0">

            <!-- STEP 1: MEMBER INFORMATION -->
            <div id="sub_step_1">
                <!-- Toggle Mode: New Member vs Renewal -->
                <div id="spSubModeContainer" style="display: flex; gap: 8px; margin-bottom: 20px; background: var(--sp-bg-main); padding: 4px; border-radius: var(--sp-radius);">
                    <button type="button" id="spModeNewBtn" class="sp-btn sp-btn-primary sp-btn-sm" style="flex: 1;" onclick="setSubMode('new')">New Member Registration</button>
                    <button type="button" id="spModeRenewalBtn" class="sp-btn sp-btn-secondary sp-btn-sm" style="flex: 1;" onclick="setSubMode('renewal')">Member Subscription Renewal</button>
                </div>

                <!-- Existing Member Selection (Renewal Mode Only) -->
                <div id="spRenewalMemberBox" style="display: none; margin-bottom: 20px;">
                    <div class="sp-form-group">
                        <select id="sp_sub_user_id" name="user_id" class="sp-floating-select" style="min-width: 100%;" onchange="onRenewalMemberSelect()">
                            <option value="">Search Existing Member (by Member ID, Full Name, Mobile...)</option>
                            <?php foreach ($usersList as $u) : ?>
                                <option value="<?php echo esc_attr($u['id']); ?>"
                                        data-name="<?php echo esc_attr($u['name']); ?>"
                                        data-empid="<?php echo esc_attr($u['employee_id']); ?>"
                                        data-phone="<?php echo esc_attr($u['phone']); ?>"
                                        data-email="<?php echo esc_attr($u['email']); ?>">
                                    <?php echo esc_html($u['name'] . ' (ID: ' . $u['employee_id'] . ' | Mobile: ' . ($u['phone'] ? $u['phone'] : 'N/A') . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="sp_sub_user_id" class="sp-floating-label">Select Member *</label>
                    </div>

                    <div id="spRenewalInfoCard" style="display: none; background: #f8f9fa; border: 1px solid var(--sp-border-color); border-radius: var(--sp-radius); padding: 14px; margin-top: -8px;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <strong id="ren_card_name" style="font-size: 14px;">Member Name</strong>
                                <span id="ren_card_empid" style="font-size: 11px; color: var(--sp-text-muted); font-family: monospace; display: block;">MEM-0000</span>
                            </div>
                            <span class="sp-badge sp-badge-active">Verified Member</span>
                        </div>
                    </div>
                </div>

                <!-- New Member Form Section -->
                <div id="spNewMemberBox" style="margin-bottom: 20px;">
                    <div class="sp-grid-2">
                        <div class="sp-form-group">
                            <input type="text" id="sp_member_name" name="member_name" class="sp-floating-input" placeholder=" ">
                            <label for="sp_member_name" class="sp-floating-label">Member Full Name *</label>
                        </div>

                        <div class="sp-form-group">
                            <input type="text" id="sp_member_phone" name="member_phone" class="sp-floating-input" placeholder=" ">
                            <label for="sp_member_phone" class="sp-floating-label">Mobile Number *</label>
                        </div>
                    </div>

                    <div class="sp-grid-2">
                        <div class="sp-form-group">
                            <input type="date" id="sp_member_dob" name="member_dob" class="sp-floating-input">
                            <label for="sp_member_dob" class="sp-floating-label">Date of Birth</label>
                        </div>

                        <div class="sp-form-group">
                            <input type="text" id="sp_member_id" name="member_id" class="sp-floating-input" placeholder=" ">
                            <label for="sp_member_id" class="sp-floating-label">Member ID (Auto-generated YYYYXXX if empty)</label>
                        </div>
                    </div>

                    <div class="sp-grid-2">
                        <div class="sp-form-group">
                            <input type="email" id="sp_member_email" name="member_email" class="sp-floating-input" placeholder=" ">
                            <label for="sp_member_email" class="sp-floating-label">Account Email (Optional)</label>
                        </div>

                        <div class="sp-form-group">
                            <input type="text" id="sp_member_password" name="member_password" class="sp-floating-input" placeholder=" ">
                            <label for="sp_member_password" class="sp-floating-label">Initial Login Password</label>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="sp-btn sp-btn-primary" onclick="goToSubStep(2)">Next: Program & Dates &rarr;</button>
                </div>
            </div>

            <!-- STEP 2: PROGRAM & DATES SELECTION -->
            <div id="sub_step_2" style="display: none;">
                <h4 style="margin: 0 0 14px 0; font-size: 14px;">Select Program & Duration</h4>

                <div class="sp-grid-2">
                    <div class="sp-form-group">
                        <select id="sp_sub_program_id" name="program_id" class="sp-floating-select" style="min-width: 100%;" onchange="onProgramChange()">
                            <option value="0" data-name="Custom Plan" data-duration="30" data-sessions="12">General / Custom Program</option>
                            <?php foreach ($programsList as $p) : ?>
                                <option value="<?php echo esc_attr($p['id']); ?>"
                                        data-name="<?php echo esc_attr($p['program_name']); ?>"
                                        data-category="<?php echo esc_attr($p['category'] ?? 'General'); ?>"
                                        data-duration="<?php echo esc_attr($p['duration_days'] ?? 30); ?>"
                                        data-sessions="<?php echo esc_attr($p['sessions_count'] ?? 12); ?>">
                                    <?php echo esc_html($p['program_name'] . ' (' . ($p['category'] ?? 'General') . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="sp_sub_program_id" class="sp-floating-label">Program *</label>
                    </div>

                    <div class="sp-form-group">
                        <input type="text" id="sp_plan_name" name="plan_name" class="sp-floating-input" placeholder=" " required>
                        <label for="sp_plan_name" class="sp-floating-label">Plan Name *</label>
                    </div>
                </div>

                <div class="sp-grid-2">
                    <div class="sp-form-group">
                        <select id="sp_sub_branch_id" name="branch_id" class="sp-floating-select" style="min-width: 100%;">
                            <option value="0">All Branches</option>
                            <?php foreach ($branchesList as $b) : ?>
                                <option value="<?php echo esc_attr($b['id']); ?>"><?php echo esc_html($b['branch_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="sp_sub_branch_id" class="sp-floating-label">Branch</label>
                    </div>

                    <div class="sp-form-group">
                        <select id="sp_subscription_type" name="subscription_type" class="sp-floating-select" style="min-width: 100%;">
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="annual">Annual</option>
                            <option value="session_pack">Session Pack</option>
                        </select>
                        <label for="sp_subscription_type" class="sp-floating-label">Subscription Type</label>
                    </div>
                </div>

                <div class="sp-grid-2">
                    <div class="sp-form-group">
                        <input type="date" id="sp_start_date" name="start_date" class="sp-floating-input" value="<?php echo esc_attr(date('Y-m-d')); ?>" required onchange="calculateEndDate()">
                        <label for="sp_start_date" class="sp-floating-label">Start Date *</label>
                    </div>

                    <div class="sp-form-group">
                        <input type="date" id="sp_end_date" name="end_date" class="sp-floating-input" required>
                        <label for="sp_end_date" class="sp-floating-label">End Date (Auto-calculated)</label>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; gap: 10px;">
                    <button type="button" class="sp-btn sp-btn-secondary" onclick="goToSubStep(1)">&larr; Back: Member Info</button>
                    <button type="button" class="sp-btn sp-btn-primary" onclick="goToSubStep(3)">Next: Payment & Review &rarr;</button>
                </div>
            </div>

            <!-- STEP 3: PAYMENT & REVIEW -->
            <div id="sub_step_3" style="display: none;">
                <h4 style="margin: 0 0 14px 0; font-size: 14px;">Pricing Fee & Summary Review</h4>

                <div class="sp-grid-2">
                    <div class="sp-form-group">
                        <input type="number" step="0.01" id="sp_sub_price" name="price" class="sp-floating-input" placeholder=" " value="0.00" oninput="updateVatPreview()">
                        <label for="sp_sub_price" class="sp-floating-label">Price / Fee (AED) *</label>
                    </div>

                    <div class="sp-form-group">
                        <select id="sp_sub_status" name="status" class="sp-floating-select" style="min-width: 100%;">
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <label for="sp_sub_status" class="sp-floating-label">Status</label>
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid var(--sp-border-color); border-radius: 8px; padding: 12px; margin-bottom: 16px; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="color: var(--sp-text-muted);">Base Amount:</span>
                        <strong id="sub_base_preview">AED 0.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="color: var(--sp-text-muted);">5% UAE VAT:</span>
                        <strong id="sub_vat_preview">AED 0.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--sp-border-color); padding-top: 6px;">
                        <strong>Total Amount Payable:</strong>
                        <strong id="sub_total_preview" style="color: var(--sp-primary-color); font-size: 15px;">AED 0.00</strong>
                    </div>
                </div>

                <div class="sp-form-group">
                    <textarea id="sp_sub_notes" name="notes" class="sp-floating-input" style="height: 50px;" placeholder=" "></textarea>
                    <label for="sp_sub_notes" class="sp-floating-label">Subscription Notes</label>
                </div>

                <div style="display: flex; justify-content: space-between; gap: 12px; border-top: 1px solid var(--sp-border-color); padding-top: 16px;">
                    <button type="button" class="sp-btn sp-btn-secondary" onclick="goToSubStep(2)">&larr; Back: Program & Dates</button>
                    <button type="submit" class="sp-btn sp-btn-primary">Confirm & Generate Invoice</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Dedicated A5 Invoice Printable Modal -->
<div id="spInvoiceModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 520px; padding: 0; overflow: hidden; border-radius: var(--sp-radius);" id="spPrintableInvoiceModal">
        <div class="sp-modal-header sp-no-print" style="padding: 16px 20px; margin: 0; background: var(--sp-bg-main);">
            <h3 class="sp-modal-title">Tax Invoice Preview (A5 Printable)</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spInvoiceModal')">&times;</button>
        </div>

        <div style="padding: 24px; background: #ffffff; color: #111827;" id="spA5InvoiceSheet">
            <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #000000; padding-bottom: 12px; margin-bottom: 16px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 28px; height: 28px; background: #000; color: #fff; border-radius: 6px; font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: 14px;">S</div>
                        <strong style="font-size: 18px; letter-spacing: -0.5px;">Sportedia Online</strong>
                    </div>
                    <span style="font-size: 11px; color: var(--sp-text-muted); display: block; margin-top: 2px;">UAE Official Tax Invoice (5% VAT)</span>
                </div>
                <div style="text-align: right;">
                    <h2 style="margin: 0; font-size: 18px; text-transform: uppercase;">TAX INVOICE</h2>
                    <span id="inv_no" style="font-size: 12px; font-family: monospace; font-weight: 700; display: block;">#INV-00000</span>
                    <span style="font-size: 11px; color: var(--sp-text-muted);"><?php echo esc_html(date('Y-m-d')); ?></span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f8f9fa; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb; font-size: 12px; margin-bottom: 16px;">
                <div>
                    <span style="font-size: 10px; color: var(--sp-text-muted); display: block; text-transform: uppercase;">Member Name</span>
                    <strong id="inv_member_name" style="font-size: 13px; color: #000;">John Doe</strong>
                </div>
                <div>
                    <span style="font-size: 10px; color: var(--sp-text-muted); display: block; text-transform: uppercase;">Member ID</span>
                    <strong id="inv_member_id" style="font-size: 13px; font-family: monospace;">MEM-1001</strong>
                </div>
                <div>
                    <span style="font-size: 10px; color: var(--sp-text-muted); display: block; text-transform: uppercase;">Mobile Number</span>
                    <span id="inv_member_phone">+971 50 000 0000</span>
                </div>
                <div>
                    <span style="font-size: 10px; color: var(--sp-text-muted); display: block; text-transform: uppercase;">Validity Period</span>
                    <span id="inv_dates" style="font-family: monospace;">2026-03-01 to 2026-03-31</span>
                </div>
            </div>

            <table style="width: 100%; font-size: 12px; border-collapse: collapse; margin-bottom: 16px;">
                <thead>
                    <tr style="background: #000; color: #fff;">
                        <th style="padding: 8px 10px; text-align: left;">Item / Subscription Plan</th>
                        <th style="padding: 8px 10px; text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 10px;" id="inv_plan_name">VIP Gold Membership</td>
                        <td style="padding: 10px; text-align: right;" id="inv_base_price">AED 0.00</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 8px 10px; color: var(--sp-text-muted);">5% UAE Value Added Tax (VAT)</td>
                        <td style="padding: 8px 10px; text-align: right; color: var(--sp-text-muted);" id="inv_vat">AED 0.00</td>
                    </tr>
                    <tr style="font-weight: 700; font-size: 14px; background: #f8f9fa;">
                        <td style="padding: 10px; border-top: 2px solid #000;">Total Payable Amount</td>
                        <td style="padding: 10px; text-align: right; border-top: 2px solid #000;" id="inv_total">AED 0.00</td>
                    </tr>
                </tbody>
            </table>

            <div style="font-size: 11px; color: var(--sp-text-muted); text-align: center; border-top: 1px dashed #e5e7eb; padding-top: 8px;">
                Thank you for choosing Sportedia Online Management.
            </div>
        </div>

        <div style="padding: 12px 20px; background: var(--sp-bg-main); border-top: 1px solid var(--sp-border-color); display: flex; gap: 8px; justify-content: flex-end;" class="sp-no-print">
            <button type="button" class="sp-btn sp-btn-secondary sp-btn-sm" onclick="window.print()">Print A5 Invoice</button>
            <button type="button" class="sp-btn sp-btn-secondary sp-btn-sm" onclick="openA6CardFromInvoice()">View Membership Card</button>
            <a id="inv_whatsapp_link" href="#" target="_blank" class="sp-btn sp-btn-primary sp-btn-sm" style="background-color: #16a34a; border-color: #16a34a;">Share WhatsApp</a>
            <button type="button" class="sp-btn sp-btn-secondary sp-btn-sm" onclick="spCloseModal('spInvoiceModal'); location.reload();">Done</button>
        </div>
    </div>
</div>

<!-- Dedicated A6 Membership Card Printable Modal -->
<div id="spCardModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 420px; padding: 0; background: #ffffff; border-radius: 12px; overflow: hidden; border: 2px solid #000000;" id="spPrintableCardModal">
        <div class="sp-modal-header sp-no-print" style="padding: 12px 16px; margin: 0; background: var(--sp-bg-main);">
            <h3 class="sp-modal-title">A6 Membership Card Preview</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spCardModal')">&times;</button>
        </div>

        <div id="spA6CardSheet">
            <div style="background: #000000; color: #ffffff; padding: 18px 20px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; background: #ffffff; color: #000000; font-weight: 800; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 16px;">S</div>
                    <div>
                        <strong style="font-size: 16px; display: block; letter-spacing: -0.5px;">Sportedia</strong>
                        <span style="font-size: 10px; opacity: 0.8; text-transform: uppercase;">Official Membership Card</span>
                    </div>
                </div>
                <span id="card_branch_badge" style="font-size: 10px; background: rgba(255,255,255,0.2); padding: 4px 8px; border-radius: 4px;">Main Branch</span>
            </div>

            <div style="padding: 20px; font-size: 13px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <div>
                        <div style="font-size: 10px; color: var(--sp-text-muted); text-transform: uppercase;">Member Name</div>
                        <strong id="card_member_name" style="font-size: 16px; color: #000000;">John Doe</strong>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 10px; color: var(--sp-text-muted); text-transform: uppercase;">Member ID</div>
                        <strong id="card_member_id" style="font-size: 14px; font-family: monospace;">MEM-1001</strong>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px; border: 1px solid #e5e7eb;">
                    <div>
                        <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">PROGRAM</span>
                        <strong id="card_program" style="font-size: 12px; color: #000000;">Football Training</strong>
                    </div>
                    <div>
                        <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">ASSIGNED COACH</span>
                        <strong id="card_coach" style="font-size: 12px; color: #000000;">Coach Alex</strong>
                    </div>
                    <div>
                        <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">START DATE</span>
                        <span id="card_start_date" style="font-size: 12px; font-family: monospace;">2026-03-01</span>
                    </div>
                    <div>
                        <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">DATE OF BIRTH</span>
                        <span id="card_dob" style="font-size: 12px;">1998-05-15</span>
                    </div>
                </div>

                <div style="text-align: center; border-top: 1px dashed #e5e7eb; padding-top: 12px;">
                    <div id="card_barcode_lines" style="display: flex; justify-content: center; gap: 2px; height: 45px; margin-bottom: 6px;"></div>
                    <span id="card_barcode_text" style="font-family: monospace; font-size: 12px; font-weight: 700; letter-spacing: 2px; color: #000000;">*MEM-1001*</span>
                </div>
            </div>
        </div>

        <div style="padding: 12px 20px; background: #f8f9fa; border-top: 1px solid #e5e7eb; display: flex; gap: 8px; justify-content: flex-end;" class="sp-no-print">
            <button type="button" class="sp-btn sp-btn-secondary sp-btn-sm" onclick="window.print()">Print A6 Card</button>
            <a id="card_whatsapp_link" href="#" target="_blank" class="sp-btn sp-btn-primary sp-btn-sm" style="background-color: #16a34a; border-color: #16a34a;">Share WhatsApp</a>
            <button type="button" class="sp-btn sp-btn-secondary sp-btn-sm" onclick="spCloseModal('spCardModal')">Close</button>
        </div>
    </div>
</div>

<script>
var currentSubStep = 1;

function goToSubStep(stepNum) {
    currentSubStep = stepNum;
    jQuery('#sub_step_1, #sub_step_2, #sub_step_3').hide();
    jQuery('#sub_step_' + stepNum).show();

    jQuery('.sp-step-tab').css({ background: 'transparent', color: 'var(--sp-text-muted)', boxShadow: 'none' });
    jQuery('#step_tab_' + stepNum).css({ background: '#ffffff', color: '#0284c7', boxShadow: '0 2px 4px rgba(0,0,0,0.05)' });

    if (window.spUpdateFloatingLabels) spUpdateFloatingLabels();
}

function saveSubDraft() {
    var data = {
        member_name: jQuery('#sp_member_name').val(),
        member_phone: jQuery('#sp_member_phone').val(),
        member_dob: jQuery('#sp_member_dob').val(),
        member_id: jQuery('#sp_member_id').val(),
        member_email: jQuery('#sp_member_email').val(),
        plan_name: jQuery('#sp_plan_name').val(),
        program_id: jQuery('#sp_sub_program_id').val(),
        price: jQuery('#sp_sub_price').val(),
        notes: jQuery('#sp_sub_notes').val(),
        sub_type: jQuery('#sp_subscription_type').val()
    };
    try {
        localStorage.setItem('sportedia_sub_draft', JSON.stringify(data));
    } catch(e) {}
}

function loadSubDraft() {
    try {
        var raw = localStorage.getItem('sportedia_sub_draft');
        if (raw) {
            var data = JSON.parse(raw);
            if (data.member_name && !jQuery('#sp_member_name').val()) jQuery('#sp_member_name').val(data.member_name);
            if (data.member_phone && !jQuery('#sp_member_phone').val()) jQuery('#sp_member_phone').val(data.member_phone);
            if (data.member_dob && !jQuery('#sp_member_dob').val()) jQuery('#sp_member_dob').val(data.member_dob);
            if (data.member_id && !jQuery('#sp_member_id').val()) jQuery('#sp_member_id').val(data.member_id);
            if (data.member_email && !jQuery('#sp_member_email').val()) jQuery('#sp_member_email').val(data.member_email);
            if (data.plan_name && !jQuery('#sp_plan_name').val()) jQuery('#sp_plan_name').val(data.plan_name);
            if (data.price && !jQuery('#sp_sub_price').val()) jQuery('#sp_sub_price').val(data.price);
            if (data.notes && !jQuery('#sp_sub_notes').val()) jQuery('#sp_sub_notes').val(data.notes);
            if (window.spUpdateFloatingLabels) spUpdateFloatingLabels();
        }
    } catch(e) {}
}

function updateVatPreview() {
    var price = parseFloat(jQuery('#sp_sub_price').val()) || 0;
    var basePrice = (price / 1.05).toFixed(2);
    var vat = (price - basePrice).toFixed(2);

    jQuery('#sub_base_preview').text('AED ' + basePrice);
    jQuery('#sub_vat_preview').text('AED ' + vat);
    jQuery('#sub_total_preview').text('AED ' + price.toFixed(2));
}

function setSubMode(mode) {
    if (mode === 'renewal') {
        jQuery('#sp_is_renewal').val('1');
        jQuery('#spModeRenewalBtn').removeClass('sp-btn-secondary').addClass('sp-btn-primary');
        jQuery('#spModeNewBtn').removeClass('sp-btn-primary').addClass('sp-btn-secondary');
        jQuery('#spRenewalMemberBox').show();
        jQuery('#spNewMemberBox').hide();
    } else {
        jQuery('#sp_is_renewal').val('0');
        jQuery('#spModeNewBtn').removeClass('sp-btn-secondary').addClass('sp-btn-primary');
        jQuery('#spModeRenewalBtn').removeClass('sp-btn-primary').addClass('sp-btn-secondary');
        jQuery('#spRenewalMemberBox').hide();
        jQuery('#spNewMemberBox').show();
    }
    if (window.spUpdateFloatingLabels) spUpdateFloatingLabels();
}

function onRenewalMemberSelect() {
    var $opt = jQuery('#sp_sub_user_id option:selected');
    if ($opt.val()) {
        jQuery('#ren_card_name').text($opt.data('name') || 'Member');
        jQuery('#ren_card_empid').text($opt.data('empid') || 'MEM-0000');
        jQuery('#spRenewalInfoCard').slideDown(150);
    } else {
        jQuery('#spRenewalInfoCard').slideUp(150);
    }
}

function onProgramChange() {
    var $opt = jQuery('#sp_sub_program_id option:selected');
    if ($opt.val() !== "0") {
        var pName = $opt.data('name') || $opt.text().trim();
        jQuery('#sp_plan_name').val(pName);
    }
    calculateEndDate();
}

function calculateEndDate() {
    var startDateVal = jQuery('#sp_start_date').val();
    if (!startDateVal) return;

    var $opt = jQuery('#sp_sub_program_id option:selected');
    var durationDays = parseInt($opt.data('duration')) || 30;

    var start = new Date(startDateVal);
    start.setDate(start.getDate() + durationDays);

    var y = start.getFullYear();
    var m = String(start.getMonth() + 1).padStart(2, '0');
    var d = String(start.getDate()).padStart(2, '0');

    jQuery('#sp_end_date').val(y + '-' + m + '-' + d);
    if (window.spUpdateFloatingLabels) spUpdateFloatingLabels();
}

function openSubModal() {
    jQuery('#spSubModalTitle').text('New Member Registration & Subscription');
    jQuery('#sp_sub_id').val('0');
    jQuery('#spSubForm')[0].reset();
    jQuery('#sp_start_date').val(new Date().toISOString().split('T')[0]);
    setSubMode('new');
    goToSubStep(1);
    calculateEndDate();
    loadSubDraft();
    spOpenModal('spSubModal');
}

function editSub(s) {
    jQuery('#spSubModalTitle').text('Edit Subscription');
    jQuery('#sp_sub_id').val(s.id);
    jQuery('#sp_sub_user_id').val(s.user_id);
    jQuery('#sp_plan_name').val(s.plan_name);
    jQuery('#sp_subscription_type').val(s.subscription_type);
    jQuery('#sp_sub_branch_id').val(s.branch_id);
    jQuery('#sp_sub_program_id').val(s.program_id);
    jQuery('#sp_start_date').val(s.start_date);
    jQuery('#sp_end_date').val(s.end_date);
    jQuery('#sp_sub_price').val(s.price);
    jQuery('#sp_sub_notes').val(s.notes || '');
    jQuery('#sp_sub_status').val(s.status);
    setSubMode('renewal');
    goToSubStep(1);
    onRenewalMemberSelect();
    updateVatPreview();
    spOpenModal('spSubModal');
}

function deleteSub(subId) {
    if (confirm('Are you sure you want to delete this subscription?')) {
        jQuery.post(sportedia_vars.ajax_url, {
            action: 'sportedia_delete_subscription',
            nonce: sportedia_vars.nonce,
            sub_id: subId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || 'Failed to delete subscription.');
            }
        });
    }
}

var latestSubData = null;

function renderBarcodeLines(containerId, text) {
    var $box = jQuery('#' + containerId).empty();
    var hash = 0;
    for (var i = 0; i < text.length; i++) hash = text.charCodeAt(i) + ((hash << 5) - hash);
    for (var j = 0; j < 32; j++) {
        var w = (Math.abs(hash + j * 7) % 4) + 1;
        $box.append(jQuery('<div>').css({ background: '#000', width: w + 'px', height: '100%', borderRadius: '1px' }));
    }
}

function viewA5Invoice(s) {
    var mName  = s.member_name || 'Member';
    var mId    = s.employee_id || 'MEM-1001';
    var mPhone = s.member_phone || '+971 50 000 0000';
    var pName  = s.plan_name || 'Subscription Plan';
    var dates  = s.start_date + ' to ' + s.end_date;
    var price  = parseFloat(s.price) || 0;

    var basePrice = (price / 1.05).toFixed(2);
    var vat       = (price - basePrice).toFixed(2);

    jQuery('#inv_no').text('#' + (s.invoice_number || 'INV-1001'));
    jQuery('#inv_member_name').text(mName);
    jQuery('#inv_member_id').text(mId);
    jQuery('#inv_member_phone').text(mPhone);
    jQuery('#inv_plan_name').text(pName);
    jQuery('#inv_dates').text(dates);
    jQuery('#inv_base_price').text('AED ' + basePrice);
    jQuery('#inv_vat').text('AED ' + vat);
    jQuery('#inv_total').text('AED ' + price.toFixed(2));

    var cleanPhone = mPhone.replace(/[^0-9]/g, '');
    var waText = encodeURIComponent(
        "Hello " + mName + ",\n\nYour Sportedia Official Invoice:\n" +
        "Invoice No: " + (s.invoice_number || 'INV-1001') + "\n" +
        "Plan: " + pName + "\n" +
        "Validity: " + dates + "\n" +
        "Total Paid: AED " + price.toFixed(2) + "\n\nThank you for choosing Sportedia!"
    );
    jQuery('#inv_whatsapp_link').attr('href', 'https://api.whatsapp.com/send?phone=' + cleanPhone + '&text=' + waText);

    latestSubData = s;
    spOpenModal('spInvoiceModal');
}

function viewA6Card(s) {
    var mName = s.member_name || s.name || 'Member';
    var mId = s.employee_id || s.member_id || 'MEM-1001';
    var mPhone = s.member_phone || s.phone || '';
    var mDob = s.member_dob || s.dob || 'N/A';
    var pName = s.plan_name || s.program_name || 'Training Program';
    var cName = s.coach_name || 'Assigned Coach';
    var bName = s.branch_name || 'Main Branch';
    var sDate = s.start_date || new Date().toISOString().split('T')[0];

    jQuery('#card_member_name').text(mName);
    jQuery('#card_member_id').text(mId);
    jQuery('#card_branch_badge').text(bName);
    jQuery('#card_program').text(pName);
    jQuery('#card_coach').text(cName);
    jQuery('#card_start_date').text(sDate);
    jQuery('#card_dob').text(mDob);
    jQuery('#card_barcode_text').text('*' + mId + '*');
    renderBarcodeLines('card_barcode_lines', mId);

    var cleanPhone = mPhone.replace(/[^0-9]/g, '');
    var waText = encodeURIComponent("Hello " + mName + ",\n\nHere is your Sportedia Official Membership Card:\nMember ID: " + mId + "\nProgram: " + pName + "\nCoach: " + cName + "\nStart Date: " + sDate);
    jQuery('#card_whatsapp_link').attr('href', 'https://api.whatsapp.com/send?phone=' + cleanPhone + '&text=' + waText);

    spOpenModal('spCardModal');
}

function openA6CardFromInvoice() {
    if (latestSubData) {
        spCloseModal('spInvoiceModal');
        viewA6Card(latestSubData);
    }
}

jQuery('#spSubForm').on('submit', function(e) {
    e.preventDefault();
    var formData = jQuery(this).serialize() + '&action=sportedia_save_subscription&nonce=' + sportedia_vars.nonce;
    jQuery.post(sportedia_vars.ajax_url, formData, function(response) {
        if (response.success) {
            try { localStorage.removeItem('sportedia_sub_draft'); } catch(e) {}
            var res = response.data;
            latestSubData = res;
            spCloseModal('spSubModal');

            // Populate Invoice Modal
            jQuery('#inv_no').text('#' + res.invoice_number);
            jQuery('#inv_member_name').text(res.member_name);
            jQuery('#inv_member_id').text(res.member_id);
            jQuery('#inv_member_phone').text(res.member_phone);
            jQuery('#inv_plan_name').text(res.plan_name);
            jQuery('#inv_dates').text(res.start_date + ' to ' + res.end_date);
            jQuery('#inv_base_price').text(res.base_price);
            jQuery('#inv_vat').text(res.vat);
            jQuery('#inv_total').text(res.total);

            var cleanPhone = (res.member_phone || '').replace(/[^0-9]/g, '');
            var waText = encodeURIComponent(
                "Hello " + res.member_name + ",\n\nYour Sportedia subscription details:\n" +
                "Invoice: " + res.invoice_number + "\n" +
                "Plan: " + res.plan_name + "\n" +
                "Period: " + res.start_date + " to " + res.end_date + "\n" +
                "Total Amount: " + res.total + "\n\nThank you for choosing Sportedia!"
            );
            jQuery('#inv_whatsapp_link').attr('href', 'https://api.whatsapp.com/send?phone=' + cleanPhone + '&text=' + waText);

            spOpenModal('spInvoiceModal');
        } else {
            alert(response.data || 'Error saving subscription.');
        }
    });
});
</script>
