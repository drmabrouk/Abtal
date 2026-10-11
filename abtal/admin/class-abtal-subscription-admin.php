<?php
if (!defined('ABSPATH')) exit;

class Abtal_Subscription_Admin {

    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $subscriptions = Abtal_Subscription_Manager::get_subscriptions($search);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Abtal Subscriptions Log & Digital Cards</h1>
            <button type="button" class="page-title-action" onclick="openAddSubModal()">Add New Subscription</button>
            <hr class="wp-header-end">

            <form method="get" style="margin-bottom: 15px; margin-top: 10px;">
                <input type="hidden" name="page" value="abtal-subscriptions">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search Subscriber ID, Name, Phone...">
                <input type="submit" class="button" value="Search Subscriptions">
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Subscriber ID</th>
                        <th>Name & Mobile Phone</th>
                        <th>Plan & Sessions</th>
                        <th>Validity Period</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions & Digital Card Dispatch</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($subscriptions)) : ?>
                        <?php foreach ($subscriptions as $sub) : ?>
                            <?php
                            $card_url = add_query_arg(array('abtal_card' => $sub['subscriber_id']), site_url('/'));
                            $wa_msg   = sprintf("Hello %s, here is your electronic Digital ID Card from Abtal: %s", $sub['subscriber_name'], $card_url);
                            $wa_url   = Abtal_Subscription_Manager::get_whatsapp_url($sub['phone'], $wa_msg);
                            ?>
                            <tr>
                                <td><strong><code><?php echo esc_html($sub['subscriber_id']); ?></code></strong></td>
                                <td>
                                    <strong><?php echo esc_html($sub['subscriber_name']); ?></strong><br>
                                    <small><?php echo esc_html($sub['phone']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($sub['plan_name']); ?></strong><br>
                                    <small>Sessions: <?php echo esc_html($sub['sessions_used'] . '/' . $sub['sessions_total']); ?></small>
                                </td>
                                <td><small><?php echo esc_html($sub['start_date'] . ' to ' . $sub['end_date']); ?></small></td>
                                <td><strong><?php echo esc_html(number_format($sub['price'], 2)); ?> AED</strong></td>
                                <td>
                                    <span class="badge <?php echo $sub['status'] === 'active' ? 'badge-success' : 'badge-secondary'; ?>">
                                        <?php echo esc_html(ucfirst($sub['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url($card_url); ?>" target="_blank" class="button button-small">View ID Card</a>
                                    <a href="<?php echo esc_url($wa_url); ?>" target="_blank" class="button button-small" style="background:#25D366; color:#fff; border-color:#25D366;">WhatsApp Card</a>
                                    <button type="button" class="button button-small" onclick='editSub(<?php echo json_encode($sub); ?>)'>Edit</button>
                                    <button type="button" class="button button-small button-link-delete" onclick="deleteSub(<?php echo $sub['id']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="7">No active subscriptions found. Click "Add New Subscription" to register a member.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Subscription Modal -->
        <div id="abtalSubModal" style="display:none; position:fixed; z-index:99999; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
            <div style="background:#fff; width:100%; max-width:500px; padding:24px; border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
                <h2 id="subModalTitle" style="margin-top:0;">New Subscription</h2>
                <form id="abtalSubForm">
                    <input type="hidden" id="sub_id" name="id" value="0">

                    <p>
                        <label>Subscriber Full Name *</label><br>
                        <input type="text" id="sub_name" name="subscriber_name" required style="width:100%;">
                    </p>

                    <p>
                        <label>Mobile Number (WhatsApp) *</label><br>
                        <input type="text" id="sub_phone" name="phone" placeholder="+971500000000" required style="width:100%;">
                    </p>

                    <p>
                        <label>Email Address</label><br>
                        <input type="email" id="sub_email" name="email" style="width:100%;">
                    </p>

                    <p>
                        <label>Plan Name & Total Sessions *</label><br>
                        <input type="text" id="sub_plan" name="plan_name" placeholder="Gold Membership / Swimming Academy" required style="width:68%;">
                        <input type="number" id="sub_sessions" name="sessions_total" value="12" min="1" required style="width:28%;">
                    </p>

                    <p>
                        <label>Start Date & End Date *</label><br>
                        <input type="date" id="sub_start_date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required style="width:48%;">
                        <input type="date" id="sub_end_date" name="end_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required style="width:48%;">
                    </p>

                    <p>
                        <label>Price (AED) & Status</label><br>
                        <input type="number" step="0.01" id="sub_price" name="price" value="0.00" style="width:48%;">
                        <select id="sub_status" name="status" style="width:48%;">
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                        </select>
                    </p>

                    <div style="text-align:right; margin-top:20px;">
                        <button type="button" class="button" onclick="closeAddSubModal()">Cancel</button>
                        <button type="submit" class="button button-primary">Save Subscription</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function openAddSubModal() {
            jQuery('#subModalTitle').text('New Subscription');
            jQuery('#sub_id').val('0');
            jQuery('#abtalSubForm')[0].reset();
            jQuery('#abtalSubModal').css('display', 'flex');
        }

        function closeAddSubModal() {
            jQuery('#abtalSubModal').hide();
        }

        function editSub(sub) {
            jQuery('#subModalTitle').text('Edit Subscription: ' + sub.subscriber_id);
            jQuery('#sub_id').val(sub.id);
            jQuery('#sub_name').val(sub.subscriber_name);
            jQuery('#sub_phone').val(sub.phone);
            jQuery('#sub_email').val(sub.email);
            jQuery('#sub_plan').val(sub.plan_name);
            jQuery('#sub_sessions').val(sub.sessions_total);
            jQuery('#sub_start_date').val(sub.start_date);
            jQuery('#sub_end_date').val(sub.end_date);
            jQuery('#sub_price').val(sub.price);
            jQuery('#sub_status').val(sub.status);
            jQuery('#abtalSubModal').css('display', 'flex');
        }

        function deleteSub(id) {
            if (confirm('Are you sure you want to delete this subscription record?')) {
                jQuery.post(abtal_admin_vars.ajax_url, {
                    action: 'abtal_delete_subscription',
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

        jQuery('#abtalSubForm').on('submit', function(e) {
            e.preventDefault();
            var formData = jQuery(this).serialize() + '&action=abtal_save_subscription&nonce=' + abtal_admin_vars.nonce;
            jQuery.post(abtal_admin_vars.ajax_url, formData, function(res) {
                if (res.success) {
                    alert(res.data.message || res.data);
                    location.reload();
                } else {
                    alert(res.data || 'Failed to save subscription.');
                }
            });
        });
        </script>
        <?php
    }
}
