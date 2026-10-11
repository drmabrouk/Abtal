<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Dashboard {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function get_stats_for_user() {
        global $wpdb;

        $user = wp_get_current_user();
        $is_sys_admin = Sportedia_Roles::is_sys_admin($user);

        $branches_table = $wpdb->prefix . 'sportedia_branches';
        $subs_table     = $wpdb->prefix . 'sportedia_subscriptions';
        $programs_table = $wpdb->prefix . 'sportedia_programs';
        $att_table      = $wpdb->prefix . 'sportedia_attendance';

        $total_branches = $wpdb->get_var("SELECT COUNT(*) FROM $branches_table WHERE status = 'active'");
        $total_subs     = $wpdb->get_var("SELECT COUNT(*) FROM $subs_table WHERE status = 'active'");
        $total_programs = $wpdb->get_var("SELECT COUNT(*) FROM $programs_table WHERE status = 'active'");
        $sess_remain    = $wpdb->get_var("SELECT SUM(sessions_count - sessions_used) FROM $subs_table WHERE status = 'active'");

        $today = date('Y-m-d');
        $today_att  = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $att_table WHERE attendance_date = %s AND status = 'present'", $today));

        $active_members = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $subs_table WHERE status = 'active'");

        $total_users    = count_users()['total_users'];

        return array(
            'total_branches'           => $total_branches ? intval($total_branches) : 0,
            'total_subs'               => $total_subs ? intval($total_subs) : 0,
            'total_programs'           => $total_programs ? intval($total_programs) : 0,
            'today_att'                => $today_att ? intval($today_att) : 0,
            'total_users'              => $total_users ? intval($total_users) : 0,
            'active_members'           => $active_members ? intval($active_members) : 0,
            'total_sessions_remaining' => $sess_remain ? intval($sess_remain) : 0,
        );
    }
}
