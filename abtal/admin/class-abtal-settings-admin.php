<?php
if (!defined('ABSPATH')) exit;

class Abtal_Settings_Admin {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_abtal_save_settings', array($this, 'ajax_save_settings'));
    }

    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $system_logo = get_option('abtal_system_logo', '');
        $org_name    = get_option('abtal_org_name', 'Abtal Sports Club');
        ?>
        <div class="wrap">
            <h1>Abtal System Settings & Branding</h1>
            <p>Customize organization system logo and branding defaults across all generated Digital Cards, PDF exports, ID layouts, and Kiosk views.</p>

            <form id="abtalSettingsForm" style="max-width: 600px; background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 8px;">
                <p>
                    <label><strong>Organization Name</strong></label><br>
                    <input type="text" name="org_name" value="<?php echo esc_attr($org_name); ?>" required style="width: 100%;">
                </p>

                <p>
                    <label><strong>Organization System Logo URL</strong></label><br>
                    <input type="text" id="abtal_system_logo" name="system_logo" value="<?php echo esc_attr($system_logo); ?>" style="width: 75%;">
                    <button type="button" class="button button-secondary" onclick="uploadSystemLogo()">Select / Upload Logo</button>
                </p>

                <?php if (!empty($system_logo)) : ?>
                    <div style="margin-bottom: 20px;">
                        <label>Current System Logo Preview:</label><br>
                        <img src="<?php echo esc_url($system_logo); ?>" style="max-height: 80px; border: 1px solid #ddd; padding: 4px; border-radius: 4px;">
                    </div>
                <?php endif; ?>

                <p>
                    <button type="submit" class="button button-primary">Save Settings</button>
                </p>
            </form>
        </div>

        <script>
        function uploadSystemLogo() {
            var mediaUploader = wp.media({
                title: 'Select System Logo',
                button: { text: 'Use as System Logo' },
                multiple: false
            });

            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                jQuery('#abtal_system_logo').val(attachment.url);
            });

            mediaUploader.open();
        }

        jQuery('#abtalSettingsForm').on('submit', function(e) {
            e.preventDefault();
            var formData = jQuery(this).serialize() + '&action=abtal_save_settings&nonce=' + abtal_admin_vars.nonce;
            jQuery.post(abtal_admin_vars.ajax_url, formData, function(res) {
                if (res.success) {
                    alert('System logo and settings updated successfully.');
                    location.reload();
                } else {
                    alert(res.data || 'Failed to update settings.');
                }
            });
        });
        </script>
        <?php
    }

    public function ajax_save_settings() {
        check_ajax_referer('abtal_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized.');
        }

        if (isset($_POST['system_logo'])) {
            update_option('abtal_system_logo', esc_url_raw($_POST['system_logo']));
        }

        if (isset($_POST['org_name'])) {
            update_option('abtal_org_name', sanitize_text_field($_POST['org_name']));
        }

        wp_send_json_success('Settings saved.');
    }
}
