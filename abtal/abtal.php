<?php
/**
 * Plugin Name: Abtal
 * Plugin URI: https://abtal.online
 * Description: Complete Employee, Attendance, Subscriptions, Digital ID Card, and Kiosk Management system.
 * Version: 1.0.0
 * Author: Abtal Development Team
 * Text Domain: abtal
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('ABTAL_VERSION', '1.0.0');
define('ABTAL_PLUGIN_FILE', __FILE__);
define('ABTAL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ABTAL_PLUGIN_URL', plugin_dir_url(__FILE__));

// Includes Core Classes
require_once ABTAL_PLUGIN_DIR . 'includes/class-abtal-db.php';
require_once ABTAL_PLUGIN_DIR . 'includes/class-abtal-activator.php';
require_once ABTAL_PLUGIN_DIR . 'includes/class-abtal-employee-manager.php';
require_once ABTAL_PLUGIN_DIR . 'includes/class-abtal-subscription-manager.php';
require_once ABTAL_PLUGIN_DIR . 'includes/class-abtal-import-export.php';

// Admin Classes
require_once ABTAL_PLUGIN_DIR . 'admin/class-abtal-admin.php';
require_once ABTAL_PLUGIN_DIR . 'admin/class-abtal-employee-admin.php';
require_once ABTAL_PLUGIN_DIR . 'admin/class-abtal-subscription-admin.php';
require_once ABTAL_PLUGIN_DIR . 'admin/class-abtal-settings-admin.php';

// Public Classes
require_once ABTAL_PLUGIN_DIR . 'public/class-abtal-public.php';
require_once ABTAL_PLUGIN_DIR . 'public/class-abtal-kiosk.php';
require_once ABTAL_PLUGIN_DIR . 'public/class-abtal-digital-card.php';

/**
 * Main Abtal Plugin Class
 */
final class Abtal {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
        $this->init_components();
    }

    private function init_hooks() {
        register_activation_hook(ABTAL_PLUGIN_FILE, array('Abtal_Activator', 'activate'));
        register_deactivation_hook(ABTAL_PLUGIN_FILE, array('Abtal_Activator', 'deactivate'));

        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    private function init_components() {
        Abtal_DB::init();
        Abtal_Employee_Manager::instance();
        Abtal_Subscription_Manager::instance();
        Abtal_Import_Export::instance();

        if (is_admin()) {
            Abtal_Admin::instance();
            Abtal_Employee_Admin::instance();
            Abtal_Subscription_Admin::instance();
            Abtal_Settings_Admin::instance();
        }

        Abtal_Public::instance();
        Abtal_Kiosk::instance();
        Abtal_Digital_Card::instance();
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style('abtal-style', ABTAL_PLUGIN_URL . 'assets/css/abtal-style.css', array(), ABTAL_VERSION);
        wp_enqueue_script('abtal-main', ABTAL_PLUGIN_URL . 'assets/js/abtal-main.js', array('jquery'), ABTAL_VERSION, true);

        wp_localize_script('abtal-main', 'abtal_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('abtal_nonce'),
            'kiosk_url'=> site_url('/sportedia-kiosk/')
        ));
    }

    public function enqueue_admin_assets() {
        wp_enqueue_media();
        wp_enqueue_style('abtal-admin-style', ABTAL_PLUGIN_URL . 'assets/css/abtal-admin.css', array(), ABTAL_VERSION);
        wp_enqueue_script('abtal-admin-js', ABTAL_PLUGIN_URL . 'assets/js/abtal-admin.js', array('jquery'), ABTAL_VERSION, true);

        wp_localize_script('abtal-admin-js', 'abtal_admin_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('abtal_nonce')
        ));
    }
}

function abtal() {
    return Abtal::instance();
}

// Initialize Abtal
abtal();
