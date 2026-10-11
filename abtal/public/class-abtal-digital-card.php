<?php
if (!defined('ABSPATH')) exit;

class Abtal_Digital_Card {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('template_redirect', array($this, 'render_card_template'));
    }

    public function render_card_template() {
        if (isset($_GET['abtal_card']) && !empty($_GET['abtal_card'])) {
            $subscriber_id = sanitize_text_field($_GET['abtal_card']);
            self::output_digital_card_html($subscriber_id);
            exit;
        }
    }

    public static function output_digital_card_html($subscriber_id) {
        global $wpdb;

        // Try subscriber table first
        $sub = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}abtal_subscriptions WHERE subscriber_id = %s",
            $subscriber_id
        ), ARRAY_A);

        // Try employee table if subscriber not found
        $emp = null;
        if (!$sub) {
            $emp = $wpdb->get_row($wpdb->prepare(
                "SELECT e.*, u.display_name, u.user_email FROM {$wpdb->prefix}abtal_employees e LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID WHERE e.employee_id = %s",
                $subscriber_id
            ), ARRAY_A);
        }

        if (!$sub && !$emp) {
            wp_die('Digital ID Card not found or expired.', 'Card Not Found', array('response' => 404));
        }

        $system_logo = get_option('abtal_system_logo', '');
        $title_name  = $sub ? $sub['subscriber_name'] : $emp['display_name'];
        $card_id     = $sub ? $sub['subscriber_id'] : $emp['employee_id'];
        $role_plan   = $sub ? $sub['plan_name'] : ($emp['job_title'] ? $emp['job_title'] : 'Employee');
        $phone       = $sub ? $sub['phone'] : $emp['phone'];
        $valid_till  = $sub ? $sub['end_date'] : 'Permanent';

        $card_url    = add_query_arg(array('abtal_card' => $card_id), site_url('/'));
        $wa_text     = "Hello, here is my Abtal Digital ID Card: " . $card_url;
        $wa_share    = Abtal_Subscription_Manager::get_whatsapp_url($phone, $wa_text);
        $barcode_svg = Abtal_Employee_Manager::generate_barcode_svg($card_id);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
            <title>Digital ID Card - <?php echo esc_html($title_name); ?></title>
            <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Google+Sans+Flex:wght@400;600;700&display=swap">
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background: #f1f5f9;
                    font-family: 'Google Sans Flex', -apple-system, sans-serif;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                }
                .id-card-container {
                    width: 100%;
                    max-width: 360px;
                    background: #ffffff;
                    border-radius: 20px;
                    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
                    overflow: hidden;
                    border: 1px solid #e2e8f0;
                    margin: 20px;
                    text-align: center;
                }
                .card-header {
                    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                    color: #ffffff;
                    padding: 24px 20px 16px 20px;
                    position: relative;
                }
                .card-header img {
                    max-height: 48px;
                    margin-bottom: 8px;
                }
                .card-header h2 {
                    margin: 0;
                    font-size: 18px;
                    letter-spacing: 0.5px;
                }
                .card-body {
                    padding: 24px 20px;
                }
                .avatar-box {
                    width: 80px;
                    height: 80px;
                    background: #e2e8f0;
                    color: #0f172a;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 32px;
                    font-weight: 700;
                    margin: -50px auto 12px auto;
                    border: 4px solid #ffffff;
                    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
                }
                .member-name {
                    font-size: 20px;
                    font-weight: 700;
                    color: #0f172a;
                    margin: 0 0 4px 0;
                }
                .member-role {
                    font-size: 13px;
                    color: #64748b;
                    font-weight: 600;
                    margin-bottom: 16px;
                }
                .info-badge {
                    display: inline-block;
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 6px 12px;
                    font-size: 12px;
                    color: #334155;
                    font-family: monospace;
                    font-weight: 700;
                    margin-bottom: 16px;
                }
                .barcode-wrapper {
                    margin: 16px 0;
                    padding: 12px;
                    background: #ffffff;
                    border-radius: 12px;
                    border: 1px solid #f1f5f9;
                }
                .card-footer {
                    background: #f8fafc;
                    padding: 16px 20px;
                    border-top: 1px solid #e2e8f0;
                }
                .btn-wa {
                    display: block;
                    width: 100%;
                    padding: 12px;
                    background: #25D366;
                    color: #ffffff;
                    text-decoration: none;
                    border-radius: 12px;
                    font-weight: 700;
                    font-size: 14px;
                    box-sizing: border-box;
                    transition: opacity 0.2s;
                }
                .btn-wa:hover {
                    opacity: 0.9;
                }
            </style>
        </head>
        <body>

        <div class="id-card-container">
            <div class="card-header">
                <?php if (!empty($system_logo)) : ?>
                    <img src="<?php echo esc_url($system_logo); ?>" alt="Logo">
                <?php else : ?>
                    <h2>ABTAL</h2>
                <?php endif; ?>
                <div style="font-size: 11px; opacity: 0.8; text-transform: uppercase; letter-spacing: 1px;">Official Digital ID Card</div>
            </div>

            <div class="card-body">
                <div class="avatar-box">
                    <?php echo esc_html(strtoupper(substr($title_name, 0, 1))); ?>
                </div>

                <div class="member-name"><?php echo esc_html($title_name); ?></div>
                <div class="member-role"><?php echo esc_html($role_plan); ?></div>

                <div class="info-badge">ID: <?php echo esc_html($card_id); ?></div>

                <div class="barcode-wrapper">
                    <?php echo $barcode_svg; ?>
                </div>

                <div style="font-size: 12px; color: #64748b;">
                    <strong>Valid Until:</strong> <?php echo esc_html($valid_till); ?>
                </div>
            </div>

            <div class="card-footer">
                <a href="<?php echo esc_url($wa_share); ?>" target="_blank" class="btn-wa">
                    Share Digital ID via WhatsApp
                </a>
            </div>
        </div>

        </body>
        </html>
        <?php
    }
}
