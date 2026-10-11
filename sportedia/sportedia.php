<?php
/**
 * Plugin Name: Sportedia
 * Plugin URI: https://sportedia.online
 * Description: Complete online management system for Sportedia.
 * Version: 1.0.0
 * Author: Sportedia Development Team
 * Text Domain: sportedia
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('SPORTEDIA_VERSION', '1.0.0');
define('SPORTEDIA_PLUGIN_FILE', __FILE__);
define('SPORTEDIA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPORTEDIA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Require Core Files
require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-db.php';
require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-finance.php';
require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-activator.php';
require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-page-generator.php';
require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-roles.php';
require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-restrictions.php';
require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-template-loader.php';
require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-auth.php';
require_once SPORTEDIA_PLUGIN_DIR . 'includes/class-sportedia-whitelabel.php';

// Require Modules
require_once SPORTEDIA_PLUGIN_DIR . 'modules/dashboard/class-sportedia-dashboard.php';
require_once SPORTEDIA_PLUGIN_DIR . 'modules/users/class-sportedia-user-manager.php';
require_once SPORTEDIA_PLUGIN_DIR . 'modules/branches/class-sportedia-branch-manager.php';
require_once SPORTEDIA_PLUGIN_DIR . 'modules/subscriptions/class-sportedia-subscription-manager.php';
require_once SPORTEDIA_PLUGIN_DIR . 'modules/programs/class-sportedia-program-manager.php';
require_once SPORTEDIA_PLUGIN_DIR . 'modules/attendance/class-sportedia-attendance-manager.php';
require_once SPORTEDIA_PLUGIN_DIR . 'modules/coaches/class-sportedia-coach-manager.php';
require_once SPORTEDIA_PLUGIN_DIR . 'modules/tournaments/class-sportedia-tournament-manager.php';
require_once SPORTEDIA_PLUGIN_DIR . 'modules/reports/class-sportedia-reports-manager.php';
require_once SPORTEDIA_PLUGIN_DIR . 'modules/settings/class-sportedia-settings-manager.php';
require_once SPORTEDIA_PLUGIN_DIR . 'modules/import-export/class-sportedia-import-export.php';

/**
 * Main Sportedia Class
 */
final class Sportedia {
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
        register_activation_hook(SPORTEDIA_PLUGIN_FILE, array('Sportedia_Activator', 'activate'));
        register_deactivation_hook(SPORTEDIA_PLUGIN_FILE, array('Sportedia_Activator', 'deactivate'));

        add_action('plugins_loaded', array($this, 'init_roles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    private function init_components() {
        new Sportedia_Restrictions();
        new Sportedia_Template_Loader();
        new Sportedia_Auth();
        Sportedia_Whitelabel::instance();

        // Instantiate Modules
        Sportedia_Dashboard::instance();
        Sportedia_User_Manager::instance();
        Sportedia_Branch_Manager::instance();
        Sportedia_Subscription_Manager::instance();
        Sportedia_Program_Manager::instance();
        Sportedia_Attendance_Manager::instance();
        Sportedia_Coach_Manager::instance();
        Sportedia_Tournament_Manager::instance();
        Sportedia_Reports_Manager::instance();
        Sportedia_Settings_Manager::instance();
        Sportedia_Import_Export::instance();
    }

    public function init_roles() {
        Sportedia_Roles::init();
    }

    public function enqueue_scripts() {
        if (Sportedia_Template_Loader::is_sportedia_page()) {
            wp_enqueue_style(
                'google-sans-flex',
                'https://fonts.googleapis.com/css2?family=Google+Sans+Flex:wght@300;400;500;600;700&display=swap',
                array(),
                null
            );
            wp_enqueue_style(
                'sportedia-style',
                SPORTEDIA_PLUGIN_URL . 'assets/css/sportedia-style.css',
                array(),
                SPORTEDIA_VERSION
            );
            wp_enqueue_script(
                'sportedia-main',
                SPORTEDIA_PLUGIN_URL . 'assets/js/sportedia-main.js',
                array('jquery'),
                SPORTEDIA_VERSION,
                true
            );

            wp_localize_script('sportedia-main', 'sportedia_vars', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('sportedia_nonce'),
                'app_url'  => get_permalink(get_option('sportedia_page_id'))
            ));
        }
    }
}

function sportedia() {
    return Sportedia::instance();
}

sportedia();
