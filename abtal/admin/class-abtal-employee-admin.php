<?php
if (!defined('ABSPATH')) exit;

class Abtal_Employee_Admin {

    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $employees = Abtal_Employee_Manager::get_employees($search);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Abtal Employee & Attendance Management</h1>
            <button type="button" class="page-title-action" onclick="openAddEmployeeModal()">Add New Employee</button>
            <a href="<?php echo esc_url(add_query_arg(array('action' => 'abtal_export_csv', 'export_type' => 'employees', 'nonce' => wp_create_nonce('abtal_nonce')), admin_url('admin-ajax.php'))); ?>" class="page-title-action">Export Employees CSV</a>
            <button type="button" class="page-title-action" onclick="openImportModal('employees')">Import CSV</button>
            <hr class="wp-header-end">

            <form method="get" style="margin-bottom: 15px; margin-top: 10px;">
                <input type="hidden" name="page" value="abtal-employees">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search Employee ID, Name, Phone...">
                <input type="submit" class="button" value="Search Employees">
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name & System User</th>
                        <th>Job Title / Dept</th>
                        <th>Phone</th>
                        <th>Barcode Preview</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($employees)) : ?>
                        <?php foreach ($employees as $emp) : ?>
                            <tr>
                                <td><strong><code><?php echo esc_html($emp['employee_id']); ?></code></strong></td>
                                <td>
                                    <strong><?php echo esc_html($emp['display_name']); ?></strong><br>
                                    <small><?php echo esc_html($emp['user_email']); ?> (User #<?php echo esc_html($emp['user_id']); ?>)</small>
                                </td>
                                <td><?php echo esc_html($emp['job_title'] ? $emp['job_title'] : 'N/A'); ?> <small>(<?php echo esc_html($emp['department']); ?>)</small></td>
                                <td><?php echo esc_html($emp['phone']); ?></td>
                                <td>
                                    <div style="max-width: 140px;">
                                        <?php echo Abtal_Employee_Manager::generate_barcode_svg($emp['employee_id']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $emp['status'] === 'active' ? 'badge-success' : 'badge-secondary'; ?>">
                                        <?php echo esc_html(ucfirst($emp['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="button button-small" onclick='editEmployee(<?php echo json_encode($emp); ?>)'>Edit</button>
                                    <button type="button" class="button button-small button-link-delete" onclick="deleteEmployee(<?php echo $emp['id']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="7">No employees found. Click "Add New Employee" to provision employee records.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Import Modal -->
        <div id="abtalImportModal" style="display:none; position:fixed; z-index:99999; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
            <div style="background:#fff; width:100%; max-width:400px; padding:24px; border-radius:8px;">
                <h3>Import CSV File</h3>
                <form id="abtalImportForm" enctype="multipart/form-data">
                    <p><input type="file" name="csv_file" accept=".csv" required></p>
                    <div style="text-align:right;">
                        <button type="button" class="button" onclick="jQuery('#abtalImportModal').hide()">Cancel</button>
                        <button type="submit" class="button button-primary">Upload & Import</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Employee Modal -->
        <div id="abtalEmpModal" style="display:none; position:fixed; z-index:99999; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
            <div style="background:#fff; width:100%; max-width:500px; padding:24px; border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
                <h2 id="empModalTitle" style="margin-top:0;">Add New Employee</h2>
                <form id="abtalEmpForm">
                    <input type="hidden" id="emp_id" name="id" value="0">
                    <input type="hidden" id="emp_user_id" name="user_id" value="0">

                    <p>
                        <label>Branch 3-Letter Prefix *</label><br>
                        <input type="text" id="emp_branch_prefix" name="branch_prefix" value="BR1" maxlength="3" required style="width:100%; text-transform:uppercase;">
                        <small>E.g., BR1, DXB, AUH. System formats ID as PREFIX-001 sequentially.</small>
                    </p>

                    <p>
                        <label>Employee Name *</label><br>
                        <input type="text" id="emp_display_name" name="display_name" required style="width:100%;">
                    </p>

                    <p>
                        <label>Email Address *</label><br>
                        <input type="email" id="emp_email" name="email" required style="width:100%;">
                    </p>

                    <p>
                        <label>Phone Number *</label><br>
                        <input type="text" id="emp_phone" name="phone" placeholder="+971500000000" style="width:100%;">
                    </p>

                    <p>
                        <label>Job Title / Department</label><br>
                        <input type="text" id="emp_job_title" name="job_title" placeholder="Fitness Trainer" style="width:48%;">
                        <input type="text" id="emp_department" name="department" placeholder="Operations" style="width:48%;">
                    </p>

                    <p>
                        <label>Status</label><br>
                        <select id="emp_status" name="status" style="width:100%;">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </p>

                    <div style="text-align:right; margin-top:20px;">
                        <button type="button" class="button" onclick="closeAddEmployeeModal()">Cancel</button>
                        <button type="submit" class="button button-primary">Save Employee & System User</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function openImportModal() {
            jQuery('#abtalImportModal').css('display', 'flex');
        }

        jQuery('#abtalImportForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            formData.append('action', 'abtal_import_csv');
            formData.append('nonce', abtal_admin_vars.nonce);

            jQuery.ajax({
                url: abtal_admin_vars.ajax_url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(res) {
                    if (res.success) {
                        alert(res.data);
                        location.reload();
                    } else {
                        alert(res.data || 'Import failed.');
                    }
                }
            });
        });

        function openAddEmployeeModal() {
            jQuery('#empModalTitle').text('Add New Employee');
            jQuery('#emp_id, #emp_user_id').val('0');
            jQuery('#abtalEmpForm')[0].reset();
            jQuery('#emp_branch_prefix').val('BR1');
            jQuery('#abtalEmpModal').css('display', 'flex');
        }

        function closeAddEmployeeModal() {
            jQuery('#abtalEmpModal').hide();
        }

        function editEmployee(emp) {
            jQuery('#empModalTitle').text('Edit Employee: ' + emp.employee_id);
            jQuery('#emp_id').val(emp.id);
            jQuery('#emp_user_id').val(emp.user_id);
            jQuery('#emp_branch_prefix').val(emp.branch_prefix || 'BR1');
            jQuery('#emp_display_name').val(emp.display_name);
            jQuery('#emp_email').val(emp.user_email);
            jQuery('#emp_phone').val(emp.phone);
            jQuery('#emp_job_title').val(emp.job_title);
            jQuery('#emp_department').val(emp.department);
            jQuery('#emp_status').val(emp.status);
            jQuery('#abtalEmpModal').css('display', 'flex');
        }

        function deleteEmployee(id) {
            if (confirm('Are you sure you want to delete this employee record?')) {
                jQuery.post(abtal_admin_vars.ajax_url, {
                    action: 'abtal_delete_employee',
                    nonce: abtal_admin_vars.nonce,
                    id: id
                }, function(res) {
                    if (res.success) {
                        alert(res.data);
                        location.reload();
                    } else {
                        alert(res.data || 'Deletion failed.');
                    }
                });
            }
        }

        jQuery('#abtalEmpForm').on('submit', function(e) {
            e.preventDefault();
            var formData = jQuery(this).serialize() + '&action=abtal_save_employee&nonce=' + abtal_admin_vars.nonce;
            jQuery.post(abtal_admin_vars.ajax_url, formData, function(res) {
                if (res.success) {
                    alert(res.data.message || res.data);
                    location.reload();
                } else {
                    alert(res.data || 'Failed to save employee.');
                }
            });
        });
        </script>
        <?php
    }
}
