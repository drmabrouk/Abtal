<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Branch_Manager {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_sportedia_save_branch', array($this, 'ajax_save_branch'));
        add_action('wp_ajax_sportedia_delete_branch', array($this, 'ajax_delete_branch'));
        add_action('wp_ajax_sportedia_search_branches', array($this, 'ajax_search_branches'));
    }

    public function ajax_search_branches() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $branches = self::get_branches($search);

        ob_start();
        if (!empty($branches)) :
            foreach ($branches as $b) : ?>
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
            <?php endforeach;
        else : ?>
            <div class="sp-card" style="text-align: center; color: var(--sp-text-muted); padding: 48px;">
                <h3>No branches found</h3>
                <p>No operational branches match your query.</p>
            </div>
        <?php endif;
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html, 'count' => count($branches)));
    }

    public static function get_branches($search = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_branches';

        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $sql  = $wpdb->prepare("SELECT * FROM $table WHERE branch_name LIKE %s OR code LIKE %s ORDER BY id DESC", $like, $like);
        } else {
            $sql  = "SELECT * FROM $table ORDER BY id DESC";
        }

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public function ajax_save_branch() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_branches') && !Sportedia_Roles::is_sys_admin()) {
            wp_send_json_error('Unauthorized access.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_branches';

        $branch_id   = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        $branch_name = sanitize_text_field($_POST['branch_name']);
        $code        = sanitize_text_field($_POST['code']);
        $address     = sanitize_textarea_field($_POST['address']);
        $phone       = sanitize_text_field($_POST['phone']);
        $email       = sanitize_email($_POST['email']);
        $status      = sanitize_text_field($_POST['status']);

        if (empty($branch_name)) {
            wp_send_json_error('Branch name is required.');
        }

        $data = array(
            'branch_name' => $branch_name,
            'code'        => $code,
            'address'     => $address,
            'phone'       => $phone,
            'email'       => $email,
            'status'      => $status,
        );

        $format = array('%s', '%s', '%s', '%s', '%s', '%s');

        if ($branch_id > 0) {
            $wpdb->update($table, $data, array('id' => $branch_id), $format, array('%d'));
            wp_send_json_success('Branch updated successfully.');
        } else {
            $wpdb->insert($table, $data, $format);
            wp_send_json_success('Branch created successfully.');
        }
    }

    public function ajax_delete_branch() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_branches') && !Sportedia_Roles::is_sys_admin()) {
            wp_send_json_error('Unauthorized access.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_branches';
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;

        if ($branch_id > 0) {
            $wpdb->delete($table, array('id' => $branch_id), array('%d'));
            wp_send_json_success('Branch deleted successfully.');
        }

        wp_send_json_error('Invalid branch ID.');
    }
}
