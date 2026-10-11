<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Reports_Manager {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function get_daily_report_data($report_date = '', $branch_id = 0) {
        global $wpdb;

        if (empty($report_date)) {
            $report_date = date('Y-m-d');
        }

        $att_table  = $wpdb->prefix . 'sportedia_attendance';
        $subs_table = $wpdb->prefix . 'sportedia_subscriptions';

        $branch_att_clause = $branch_id > 0 ? $wpdb->prepare(" AND branch_id = %d", $branch_id) : "";
        $branch_sub_clause = $branch_id > 0 ? $wpdb->prepare(" AND branch_id = %d", $branch_id) : "";

        // Attendance stats for report date
        $present_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $att_table WHERE attendance_date = %s AND status = 'present'" . $branch_att_clause, $report_date));
        $absent_count  = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $att_table WHERE attendance_date = %s AND status = 'absent'" . $branch_att_clause, $report_date));
        $late_count    = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $att_table WHERE attendance_date = %s AND status = 'late'" . $branch_att_clause, $report_date));

        // Subscriptions started on or created on date
        $new_subs = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $subs_table WHERE start_date = %s" . $branch_sub_clause, $report_date));
        $revenue  = $wpdb->get_var($wpdb->prepare("SELECT SUM(price) FROM $subs_table WHERE start_date = %s" . $branch_sub_clause, $report_date));

        return array(
            'date'          => $report_date,
            'present_count' => intval($present_count),
            'absent_count'  => intval($absent_count),
            'late_count'    => intval($late_count),
            'new_subs'      => intval($new_subs),
            'revenue'       => floatval($revenue),
        );
    }
}
