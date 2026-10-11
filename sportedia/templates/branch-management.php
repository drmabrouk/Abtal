<?php
if (!defined('ABSPATH')) exit;

$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$branches = Sportedia_Branch_Manager::get_branches($search);
?>

<div class="sp-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
    <div>
        <h1 class="sp-page-title">Branch Management</h1>
        <p class="sp-page-subtitle">Configure operational branches, locations, and facility settings.</p>
    </div>
    <button class="sp-btn sp-btn-primary" onclick="openBranchModal()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add New Branch
    </button>
</div>

<div class="sp-card" style="padding: 16px; margin-bottom: 20px;">
    <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap;">
        <input type="hidden" name="module" value="branches">
        <div style="flex: 1; min-width: 200px;">
            <input type="text" name="search" class="sp-floating-input" placeholder="Search branch name or code..." value="<?php echo esc_attr($search); ?>">
        </div>
        <button type="submit" class="sp-btn sp-btn-primary">Search</button>
    </form>
</div>

<!-- Unified Full-Width Extended Rows Layout (1 Row Per Branch) -->
<?php if (!empty($branches)) : ?>
    <div style="display: flex; flex-direction: column; gap: 12px;" id="branches_container">
        <?php foreach ($branches as $b) : ?>
            <div class="sp-card" style="margin-bottom: 0; padding: 16px 20px; border-radius: var(--sp-radius); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                <div style="flex: 2; min-width: 220px;">
                    <strong style="font-size: 15px; color: var(--sp-text-main); display: block;"><?php echo esc_html($b['branch_name']); ?></strong>
                    <code style="font-size: 11px; background: #f3f4f6; padding: 2px 6px; border-radius: 4px;"><?php echo esc_html($b['code']); ?></code>
                </div>

                <div style="flex: 2; min-width: 200px;">
                    <span style="font-size: 10px; color: var(--sp-text-muted); display: block; text-transform: uppercase;">Contact Info</span>
                    <strong style="font-size: 13px; color: #000;"><?php echo esc_html($b['phone'] ? $b['phone'] : 'N/A'); ?></strong>
                    <span style="font-size: 11px; color: var(--sp-text-muted); display: block;"><?php echo esc_html($b['email'] ? $b['email'] : 'N/A'); ?></span>
                </div>

                <div style="flex: 3; min-width: 240px;">
                    <span style="font-size: 10px; color: var(--sp-text-muted); display: block; text-transform: uppercase;">Location / Address</span>
                    <span style="font-size: 12px; color: var(--sp-text-main);"><?php echo esc_html($b['address'] ? $b['address'] : 'No physical address specified.'); ?></span>
                </div>

                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="sp-badge <?php echo $b['status'] === 'active' ? 'sp-badge-active' : 'sp-badge-inactive'; ?>">
                        <?php echo esc_html(ucfirst($b['status'])); ?>
                    </span>

                    <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='editBranch(<?php echo json_encode($b); ?>)'>Edit</button>
                    <button class="sp-btn sp-btn-secondary sp-btn-sm" style="color:#dc2626;" onclick="deleteBranch(<?php echo $b['id']; ?>)">Delete</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <div class="sp-card" style="text-align: center; color: var(--sp-text-muted); padding: 48px;">
        <h3>No branches found</h3>
        <p>No operational branches match your query.</p>
    </div>
<?php endif; ?>

<!-- Branch Modal -->
<div id="spBranchModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 500px;">
        <div class="sp-modal-header">
            <h3 id="spBranchModalTitle" class="sp-modal-title">Add Branch</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spBranchModal')">&times;</button>
        </div>

        <form id="spBranchForm">
            <input type="hidden" id="sp_branch_id" name="branch_id" value="0">

            <div class="sp-form-group">
                <input type="text" id="sp_branch_name" name="branch_name" class="sp-floating-input" placeholder=" " required>
                <label for="sp_branch_name" class="sp-floating-label">Branch Name</label>
            </div>

            <div class="sp-form-group">
                <input type="text" id="sp_branch_code" name="code" class="sp-floating-input" placeholder=" ">
                <label for="sp_branch_code" class="sp-floating-label">Branch Code (e.g. BR-01)</label>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="text" id="sp_branch_phone" name="phone" class="sp-floating-input" placeholder=" ">
                    <label for="sp_branch_phone" class="sp-floating-label">Phone Number</label>
                </div>

                <div class="sp-form-group">
                    <input type="email" id="sp_branch_email" name="email" class="sp-floating-input" placeholder=" ">
                    <label for="sp_branch_email" class="sp-floating-label">Email Address</label>
                </div>
            </div>

            <div class="sp-form-group">
                <textarea id="sp_branch_address" name="address" class="sp-floating-input" style="height: 80px;" placeholder=" "></textarea>
                <label for="sp_branch_address" class="sp-floating-label">Physical Address</label>
            </div>

            <div class="sp-form-group">
                <select id="sp_branch_status" name="status" class="sp-floating-select">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <label for="sp_branch_status" class="sp-floating-label">Status</label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--sp-border-color); padding-top: 16px;">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spBranchModal')">Cancel</button>
                <button type="submit" class="sp-btn sp-btn-primary">Save Branch</button>
            </div>
        </form>
    </div>
</div>

<script>
function openBranchModal() {
    jQuery('#spBranchModalTitle').text('Add New Branch');
    jQuery('#sp_branch_id').val('0');
    jQuery('#spBranchForm')[0].reset();
    spOpenModal('spBranchModal');
}

function editBranch(b) {
    jQuery('#spBranchModalTitle').text('Edit Branch');
    jQuery('#sp_branch_id').val(b.id);
    jQuery('#sp_branch_name').val(b.branch_name);
    jQuery('#sp_branch_code').val(b.code);
    jQuery('#sp_branch_phone').val(b.phone);
    jQuery('#sp_branch_email').val(b.email);
    jQuery('#sp_branch_address').val(b.address);
    jQuery('#sp_branch_status').val(b.status);
    spOpenModal('spBranchModal');
}

function deleteBranch(branchId) {
    if (confirm('Are you sure you want to delete this branch?')) {
        jQuery.post(sportedia_vars.ajax_url, {
            action: 'sportedia_delete_branch',
            nonce: sportedia_vars.nonce,
            branch_id: branchId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || 'Failed to delete branch.');
            }
        });
    }
}

jQuery('#spBranchForm').on('submit', function(e) {
    e.preventDefault();
    var formData = jQuery(this).serialize() + '&action=sportedia_save_branch&nonce=' + sportedia_vars.nonce;
    jQuery.post(sportedia_vars.ajax_url, formData, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert(response.data || 'Error saving branch.');
        }
    });
});
</script>
