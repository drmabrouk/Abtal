<?php
if (!defined('ABSPATH')) exit;

class Abtal_DB {

    public static function init() {
        // Class constructor/hooks if required
    }

    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // 1. Employees Table
        $table_employees = $wpdb->prefix . 'abtal_employees';
        $sql_employees = "CREATE TABLE $table_employees (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            employee_id varchar(50) NOT NULL,
            branch_prefix varchar(10) DEFAULT 'BR1' NOT NULL,
            seq_num int(11) DEFAULT 1 NOT NULL,
            job_title varchar(100) DEFAULT '' NOT NULL,
            department varchar(100) DEFAULT '' NOT NULL,
            phone varchar(50) DEFAULT '' NOT NULL,
            barcode varchar(100) DEFAULT '' NOT NULL,
            salary decimal(10,2) DEFAULT '0.00' NOT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY employee_id (employee_id),
            KEY user_id (user_id),
            KEY branch_prefix (branch_prefix)
        ) $charset_collate;";

        // 2. Attendance Table
        $table_attendance = $wpdb->prefix . 'abtal_attendance';
        $sql_attendance = "CREATE TABLE $table_attendance (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            employee_id varchar(50) NOT NULL,
            attendance_date date NOT NULL,
            check_in_time datetime DEFAULT NULL,
            check_out_time datetime DEFAULT NULL,
            lateness_minutes int(11) DEFAULT 0 NOT NULL,
            working_duration_minutes int(11) DEFAULT 0 NOT NULL,
            status varchar(20) DEFAULT 'present' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY employee_id (employee_id),
            KEY attendance_date (attendance_date)
        ) $charset_collate;";

        // 3. Subscriptions Table
        $table_subscriptions = $wpdb->prefix . 'abtal_subscriptions';
        $sql_subscriptions = "CREATE TABLE $table_subscriptions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            invoice_number varchar(100) DEFAULT '' NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            subscriber_id varchar(50) NOT NULL,
            subscriber_name varchar(191) NOT NULL,
            phone varchar(50) DEFAULT '' NOT NULL,
            email varchar(100) DEFAULT '' NOT NULL,
            plan_name varchar(191) NOT NULL,
            sessions_total int(11) DEFAULT 12 NOT NULL,
            sessions_used int(11) DEFAULT 0 NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            price decimal(10,2) DEFAULT '0.00' NOT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY subscriber_id (subscriber_id),
            KEY phone (phone)
        ) $charset_collate;";

        // 4. Settings Table
        $table_settings = $wpdb->prefix . 'abtal_settings';
        $sql_settings = "CREATE TABLE $table_settings (
            setting_key varchar(191) NOT NULL,
            setting_value longtext DEFAULT '',
            PRIMARY KEY  (setting_key)
        ) $charset_collate;";

        dbDelta($sql_employees);
        dbDelta($sql_attendance);
        dbDelta($sql_subscriptions);
        dbDelta($sql_settings);
    }
}
