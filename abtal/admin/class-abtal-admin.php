<?php
if (!defined('ABSPATH')) exit;

class Abtal_Admin {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Abtal Management',
            'Abtal',
            'manage_options',
            'abtal',
            array($this, 'render_dashboard'),
            'dashicons-groups',
            6
        );

        add_submenu_page('abtal', 'Dashboard', 'Dashboard', 'manage_options', 'abtal', array($this, 'render_dashboard'));
        add_submenu_page('abtal', 'Employees', 'Employees', 'manage_options', 'abtal-employees', array('Abtal_Employee_Admin', 'render_page'));
        add_submenu_page('abtal', 'Subscriptions', 'Subscriptions', 'manage_options', 'abtal-subscriptions', array('Abtal_Subscription_Admin', 'render_page'));
        add_submenu_page('abtal', 'Settings', 'Settings', 'manage_options', 'abtal-settings', array('Abtal_Settings_Admin', 'render_page'));
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>Abtal Management System</h1>
            <p>Welcome to Abtal Employee, Attendance, Subscriptions and Kiosk Management System.</p>
        </div>
        <?php
    }
}
