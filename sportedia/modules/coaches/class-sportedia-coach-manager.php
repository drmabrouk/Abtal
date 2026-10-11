<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Coach_Manager {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_sportedia_export_coaches_excel', array($this, 'handle_export_coaches_excel'));
    }

    public function handle_export_coaches_excel() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        $curr_u = wp_get_current_user();
        $allowed_roles = array('sportedia_sys_admin', 'sportedia_general_mgr', 'sportedia_admin_mgr', 'sportedia_facility_mgr', 'sportedia_ops_mgr', 'sportedia_finance_mgr', 'administrator');
        $has_perm = false;
        foreach ((array)$curr_u->roles as $r) {
            if (in_array($r, $allowed_roles, true) || current_user_can('manage_options')) {
                $has_perm = true;
                break;
            }
        }
        if (!$has_perm) {
            wp_die('Unauthorized. Coach data export is restricted to authorized management staff.');
        }

        @set_time_limit(300);
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $coaches = self::get_coaches_summary($search);

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename=Coach_Management_Report_' . date('Y-m-d') . '.xls');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
        echo '<x:Name>Coaches Report</x:Name>';
        echo '<x:WorksheetOptions><x:DisplayGridlines/><x:Print><x:ValidPrinterInfo/><x:PaperSizeIndex>9</x:PaperSizeIndex><x:Scale>100</x:Scale><x:FitWidth>1</x:FitWidth><x:FitHeight>0</x:FitHeight></x:Print><x:Selected/><x:FreezePanes/><x:FrozenNoSplit/><x:SplitHorizontal>4</x:SplitHorizontal><x:TopRowBottomPane>4</x:TopRowBottomPane><x:ActivePane>2</x:ActivePane></x:WorksheetOptions>';
        echo '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
        echo '<style>';
        echo 'body { font-family: "Calibri", "Segoe UI", Arial, sans-serif; font-size: 11pt; }';
        echo 'table { border-collapse: collapse; width: 100%; table-layout: fixed; }';
        echo 'th, td { border: 0.5pt solid #CBD5E1; padding: 8px; font-size: 10pt; vertical-align: middle; }';
        echo '.title-row { background-color: #0F172A; color: #FFFFFF; font-size: 16pt; font-weight: bold; text-align: center; height: 40px; }';
        echo '.subtitle-row { background-color: #1E293B; color: #F8FAFC; font-size: 11pt; font-weight: bold; text-align: center; height: 28px; }';
        echo '.header-cell { background-color: #0284C7; color: #FFFFFF; font-weight: bold; text-align: center; height: 32px; font-size: 10pt; }';
        echo '.text-cell { text-align: left; mso-number-format:"\@"; }';
        echo '.center-cell { text-align: center; mso-number-format:"\@"; }';
        echo '</style></head><body>';

        echo '<table>';
        echo '<col style="width: 50px;">';  // A: S
        echo '<col style="width: 120px;">'; // B: ID
        echo '<col style="width: 180px;">'; // C: NAME
        echo '<col style="width: 200px;">'; // D: EMAIL
        echo '<col style="width: 130px;">'; // E: PHONE
        echo '<col style="width: 140px;">'; // F: PROGRAMS
        echo '<col style="width: 140px;">'; // G: MEMBERS
        echo '<col style="width: 160px;">'; // H: SESSIONS

        echo '<tr><td colspan="8" class="title-row">SPORTEDIA ACADEMY — MAIN COACH MANAGEMENT REPORT</td></tr>';
        echo '<tr><td colspan="8" class="subtitle-row">REPORT DATE: ' . esc_html(date('Y-m-d')) . ' | TOTAL COACHES: ' . count($coaches) . '</td></tr>';
        echo '<tr><td colspan="8" style="height: 8px; border: none;"></td></tr>';

        echo '<tr>';
        echo '<th class="header-cell">S</th>';
        echo '<th class="header-cell">COACH ID</th>';
        echo '<th class="header-cell">COACH NAME</th>';
        echo '<th class="header-cell">EMAIL ADDRESS</th>';
        echo '<th class="header-cell">MOBILE NUMBER</th>';
        echo '<th class="header-cell">ASSIGNED PROGRAMS</th>';
        echo '<th class="header-cell">ACTIVE MEMBERS</th>';
        echo '<th class="header-cell">COMPLETED SESSIONS</th>';
        echo '</tr>';

        $sr = 1;
        foreach ($coaches as $c) {
            echo '<tr>';
            echo '<td class="center-cell">' . $sr++ . '</td>';
            echo '<td class="center-cell">' . esc_html($c['employee_id']) . '</td>';
            echo '<td class="text-cell">' . esc_html(mb_strtoupper($c['name'], 'UTF-8')) . '</td>';
            echo '<td class="text-cell">' . esc_html(mb_strtoupper($c['email'], 'UTF-8')) . '</td>';
            echo '<td class="center-cell">' . esc_html($c['phone']) . '</td>';
            echo '<td class="center-cell"><strong>' . $c['assigned_programs'] . '</strong></td>';
            echo '<td class="center-cell"><strong>' . $c['assigned_members'] . '</strong></td>';
            echo '<td class="center-cell" style="color: #16A34A; font-weight: bold;">' . $c['completed_sessions'] . '</td>';
            echo '</tr>';
        }

        echo '</table></body></html>';
        exit;
    }

    public static function get_coaches_summary($search = '', $branch_id = 0) {
        global $wpdb;

        $coaches = get_users(array('role' => 'sportedia_coach'));
        $summary = array();

        $subs_table = $wpdb->prefix . 'sportedia_subscriptions';
        $prog_table = $wpdb->prefix . 'sportedia_programs';
        $att_table  = $wpdb->prefix . 'sportedia_attendance';

        foreach ($coaches as $c) {
            $coach_id = $c->ID;
            $name     = $c->display_name;
            $email    = $c->user_email;
            $phone    = get_user_meta($coach_id, 'sportedia_phone', true);
            $emp_id   = get_user_meta($coach_id, 'sportedia_employee_id', true);

            if (!empty($search)) {
                if (stripos($name, $search) === false && stripos($email, $search) === false && stripos($emp_id, $search) === false) {
                    continue;
                }
            }

            // Get assigned programs count
            $assigned_programs = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $prog_table WHERE coach_id = %d AND status = 'active'",
                $coach_id
            ));

            // Get assigned members count
            $assigned_members = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT user_id) FROM $subs_table WHERE coach_id = %d AND status = 'active'",
                $coach_id
            ));

            // Get total verified completed sessions conducted by coach
            $completed_sessions = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT a.id) FROM $att_table a
                 INNER JOIN $subs_table s ON a.program_id = s.program_id AND a.user_id = s.user_id
                 WHERE s.coach_id = %d AND a.status = 'present'",
                $coach_id
            ));

            $summary[] = array(
                'id'                 => $coach_id,
                'name'               => $name,
                'email'              => $email,
                'phone'              => $phone ? $phone : 'N/A',
                'employee_id'        => $emp_id ? $emp_id : 'COACH-' . $coach_id,
                'assigned_programs'  => intval($assigned_programs),
                'assigned_members'   => intval($assigned_members),
                'completed_sessions' => intval($completed_sessions)
            );
        }

        return $summary;
    }

    public static function get_coach_history($coach_id) {
        global $wpdb;
        $subs_table = $wpdb->prefix . 'sportedia_subscriptions';
        $att_table  = $wpdb->prefix . 'sportedia_attendance';

        $sql = $wpdb->prepare(
            "SELECT DISTINCT a.*, s.plan_name FROM $att_table a
             INNER JOIN $subs_table s ON a.program_id = s.program_id AND a.user_id = s.user_id
             WHERE s.coach_id = %d ORDER BY a.id DESC LIMIT 50",
            $coach_id
        );

        return $wpdb->get_results($sql, ARRAY_A);
    }
}
