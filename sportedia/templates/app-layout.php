<?php
if (!defined('ABSPATH')) exit;

$module = isset($_GET['module']) ? sanitize_text_field($_GET['module']) : 'dashboard';

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sportedia Online Management</title>
    <?php wp_head(); ?>
</head>
<body class="sportedia-app-body">

<div class="sp-app-layout">
    <?php include SPORTEDIA_PLUGIN_DIR . 'templates/floating-sidebar.php'; ?>

    <main class="sp-main-content">
        <?php
        switch ($module) {
            case 'users':
                if (current_user_can('sportedia_manage_users') || Sportedia_Roles::is_sys_admin()) {
                    include SPORTEDIA_PLUGIN_DIR . 'templates/user-management.php';
                } else {
                    echo '<div class="sp-card"><h3>Access Denied</h3><p>You do not have permission to access user management.</p></div>';
                }
                break;
            case 'branches':
                if (current_user_can('sportedia_manage_branches') || Sportedia_Roles::is_sys_admin()) {
                    include SPORTEDIA_PLUGIN_DIR . 'templates/branch-management.php';
                } else {
                    echo '<div class="sp-card"><h3>Access Denied</h3><p>You do not have permission to access branch management.</p></div>';
                }
                break;
            case 'subscriptions':
                if (current_user_can('sportedia_manage_subscriptions') || Sportedia_Roles::is_sys_admin()) {
                    include SPORTEDIA_PLUGIN_DIR . 'templates/subscription-management.php';
                } else {
                    echo '<div class="sp-card"><h3>Access Denied</h3><p>You do not have permission to access subscription management.</p></div>';
                }
                break;
            case 'programs':
                if (current_user_can('sportedia_manage_programs') || current_user_can('sportedia_view_programs') || Sportedia_Roles::is_sys_admin()) {
                    include SPORTEDIA_PLUGIN_DIR . 'templates/program-management.php';
                } else {
                    echo '<div class="sp-card"><h3>Access Denied</h3><p>You do not have permission to access program management.</p></div>';
                }
                break;
            case 'coaches':
                if (current_user_can('sportedia_manage_programs') || current_user_can('sportedia_view_programs') || Sportedia_Roles::is_sys_admin()) {
                    include SPORTEDIA_PLUGIN_DIR . 'templates/coach-management.php';
                } else {
                    echo '<div class="sp-card"><h3>Access Denied</h3><p>You do not have permission to access coach management.</p></div>';
                }
                break;
            case 'tournaments':
                include SPORTEDIA_PLUGIN_DIR . 'templates/tournament-management.php';
                break;
            case 'attendance':
                if (current_user_can('sportedia_manage_attendance') || Sportedia_Roles::is_sys_admin()) {
                    include SPORTEDIA_PLUGIN_DIR . 'templates/attendance-management.php';
                } else {
                    echo '<div class="sp-card"><h3>Access Denied</h3><p>You do not have permission to access attendance management.</p></div>';
                }
                break;
            case 'reports':
                if (current_user_can('sportedia_view_reports') || Sportedia_Roles::is_sys_admin()) {
                    include SPORTEDIA_PLUGIN_DIR . 'templates/daily-reports.php';
                } else {
                    echo '<div class="sp-card"><h3>Access Denied</h3><p>You do not have permission to access daily reports.</p></div>';
                }
                break;
            case 'settings':
                if (current_user_can('sportedia_manage_settings') || Sportedia_Roles::is_sys_admin()) {
                    include SPORTEDIA_PLUGIN_DIR . 'templates/system-settings.php';
                } else {
                    echo '<div class="sp-card"><h3>Access Denied</h3><p>You do not have permission to access system settings.</p></div>';
                }
                break;
            case 'dashboard':
            default:
                include SPORTEDIA_PLUGIN_DIR . 'templates/dashboard.php';
                break;
        }
        ?>
    </main>
</div>

<?php wp_footer(); ?>
</body>
</html>
