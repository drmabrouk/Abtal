<?php
if (!defined('ABSPATH')) exit;

$search        = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$branch_filter = isset($_GET['branch_filter']) ? intval($_GET['branch_filter']) : 0;

$coachesList  = Sportedia_Coach_Manager::get_coaches_summary($search, $branch_filter);
$branchesList = Sportedia_Branch_Manager::get_branches();

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
        <h1 class="sp-page-title">Coach Management</h1>
        <p class="sp-page-subtitle">Track assigned sports coaches, active member allocations, and verified completed sessions.</p>
    </div>
    <div>
        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=sportedia_export_coaches_excel&search=' . urlencode($search) . '&nonce=' . $export_nonce)); ?>" class="sp-btn sp-btn-primary sp-btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export to Excel (.XLS)
        </a>
    </div>
</div>

<div class="sp-card" style="padding: 16px; margin-bottom: 20px;">
    <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap;">
        <input type="hidden" name="module" value="coaches">

        <div style="flex: 1; min-width: 200px;">
            <input type="text" name="search" class="sp-floating-input" placeholder="Search coach name, ID or email..." value="<?php echo esc_attr($search); ?>">
        </div>

        <button type="submit" class="sp-btn sp-btn-primary">Filter</button>
    </form>
</div>

<!-- Modern Cards Display (Sorted Newest to Oldest) -->
<?php if (!empty($coachesList)) : ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px;">
        <?php foreach ($coachesList as $c) : ?>
            <div class="sp-card" style="margin-bottom: 0; padding: 18px; border-radius: var(--sp-radius); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                        <div>
                            <strong style="font-size: 16px; color: var(--sp-text-main); display: block;"><?php echo esc_html($c['name']); ?></strong>
                            <span style="font-size: 11px; color: var(--sp-text-muted); font-family: monospace;"><?php echo esc_html($c['employee_id']); ?></span>
                        </div>
                        <span class="sp-badge sp-badge-active">Coach / Trainer</span>
                    </div>

                    <div style="background: #f8f9fa; border: 1px solid var(--sp-border-color); border-radius: 8px; padding: 12px; font-size: 12px; margin-bottom: 14px;">
                        <div style="margin-bottom: 6px;">
                            <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">EMAIL</span>
                            <span><?php echo esc_html($c['email']); ?></span>
                        </div>
                        <div style="margin-bottom: 8px;">
                            <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">MOBILE</span>
                            <span><?php echo esc_html($c['phone']); ?></span>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; border-top: 1px dashed var(--sp-border-color); padding-top: 8px;">
                            <div>
                                <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">ASSIGNED PROGRAMS</span>
                                <strong><?php echo esc_html($c['assigned_programs']); ?> Programs</strong>
                            </div>
                            <div>
                                <span style="font-size: 10px; color: var(--sp-text-muted); display: block;">ACTIVE MEMBERS</span>
                                <strong><?php echo esc_html($c['assigned_members']); ?> Members</strong>
                            </div>
                        </div>

                        <?php if ($can_view_session_count) : ?>
                            <div style="margin-top: 8px; border-top: 1px dashed var(--sp-border-color); padding-top: 6px; display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 10px; color: var(--sp-text-muted);">COMPLETED SESSIONS</span>
                                <strong style="font-size: 14px; color: #166534;"><?php echo esc_html($c['completed_sessions']); ?> Verified Sessions</strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; border-top: 1px solid var(--sp-border-color); padding-top: 10px; margin-top: auto;">
                    <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='viewCoachStats(<?php echo json_encode($c); ?>)'>Session Stats</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <div class="sp-card" style="text-align: center; color: var(--sp-text-muted); padding: 48px;">
        <h3>No coaches found</h3>
        <p>No coach profiles match your query.</p>
    </div>
<?php endif; ?>

<!-- Coach Session Stats Modal -->
<div id="spCoachModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 500px;">
        <div class="sp-modal-header">
            <h3 id="spCoachModalTitle" class="sp-modal-title">Coach Performance Statistics</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spCoachModal')">&times;</button>
        </div>

        <div style="font-size: 13px; margin-bottom: 16px;">
            <div><strong>Coach:</strong> <span id="coach_modal_name">Coach Name</span></div>
            <div><strong>Assigned Active Programs:</strong> <span id="coach_modal_progs">0</span></div>
            <div><strong>Active Members Managed:</strong> <span id="coach_modal_mems">0</span></div>
            <div><strong>Total Completed Sessions:</strong> <strong id="coach_modal_sess" style="color:#166534;">0</strong></div>
        </div>
        <p style="font-size: 12px; color: var(--sp-text-muted);">
            Completed sessions are calculated automatically whenever member barcodes are verified and deducted in the Verification System.
        </p>
        <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
            <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spCoachModal')">Close</button>
        </div>
    </div>
</div>

<script>
function viewCoachStats(c) {
    jQuery('#coach_modal_name').text(c.name + ' (' + c.employee_id + ')');
    jQuery('#coach_modal_progs').text(c.assigned_programs);
    jQuery('#coach_modal_mems').text(c.assigned_members);
    jQuery('#coach_modal_sess').text(c.completed_sessions);
    spOpenModal('spCoachModal');
}
</script>
