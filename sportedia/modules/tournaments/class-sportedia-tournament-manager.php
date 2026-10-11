<?php
if (!defined('ABSPATH')) exit;

class Sportedia_Tournament_Manager {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_sportedia_save_tournament', array($this, 'ajax_save_tournament'));
        add_action('wp_ajax_sportedia_delete_tournament', array($this, 'ajax_delete_tournament'));
        add_action('wp_ajax_sportedia_search_tournaments', array($this, 'ajax_search_tournaments'));
        add_action('wp_ajax_sportedia_save_tournament_team', array($this, 'ajax_save_tournament_team'));
        add_action('wp_ajax_sportedia_delete_tournament_team', array($this, 'ajax_delete_tournament_team'));
        add_action('wp_ajax_sportedia_generate_random_draw', array($this, 'ajax_generate_random_draw'));
        add_action('wp_ajax_sportedia_save_fixture_score', array($this, 'ajax_save_fixture_score'));
    }

    public static function get_tournaments($search = '', $branch_id = 0, $status = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_tournaments';
        $teams_table = $wpdb->prefix . 'sportedia_tournament_teams';

        $where = array('1=1');
        $params = array();

        if ($branch_id > 0) {
            $where[] = '(branch_id = %d OR branch_id = 0)';
            $params[] = $branch_id;
        }

        if (!empty($status)) {
            $where[] = 'status = %s';
            $params[] = $status;
        }

        $where_sql = implode(' AND ', $where);

        if (!empty($params)) {
            $sql = $wpdb->prepare("SELECT * FROM $table WHERE $where_sql ORDER BY id DESC", $params);
        } else {
            $sql = "SELECT * FROM $table WHERE $where_sql ORDER BY id DESC";
        }

        $results = $wpdb->get_results($sql, ARRAY_A);
        $tournaments = array();

        foreach ($results as $r) {
            if (!empty($search)) {
                $s = strtolower(trim($search));
                $match = (stripos(strtolower($r['tournament_name']), $s) !== false) ||
                         (stripos(strtolower($r['sport']), $s) !== false) ||
                         (stripos(strtolower($r['category']), $s) !== false);

                if (!$match) continue;
            }

            // Calculate registered teams count
            $reg_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $teams_table WHERE tournament_id = %d AND status != 'cancelled'", $r['id']));
            $r['registered_teams'] = intval($reg_count);

            // Branch name
            $branch_name = 'All Branches';
            if ($r['branch_id'] > 0) {
                $b = $wpdb->get_row($wpdb->prepare("SELECT branch_name FROM {$wpdb->prefix}sportedia_branches WHERE id = %d", $r['branch_id']));
                if ($b) $branch_name = $b->branch_name;
            }
            $r['branch_name'] = $branch_name;

            // Check 12-hour lock status
            $start_ts = strtotime($r['start_date']);
            $hours_until_start = ($start_ts - time()) / 3600;
            $r['is_locked'] = ($hours_until_start <= 12) || ($r['status'] === 'locked') || ($r['status'] === 'closed');

            $tournaments[] = $r;
        }

        return $tournaments;
    }

    public static function get_tournament_teams($tournament_id, $coach_id = 0) {
        global $wpdb;
        $teams_table   = $wpdb->prefix . 'sportedia_tournament_teams';
        $players_table = $wpdb->prefix . 'sportedia_tournament_players';

        $where = array("tournament_id = %d");
        $params = array($tournament_id);

        if ($coach_id > 0 && !Sportedia_Roles::is_sys_admin() && !Sportedia_Roles::is_general_mgr()) {
            $where[] = "coach_id = %d";
            $params[] = $coach_id;
        }

        $where_sql = implode(' AND ', $where);
        $sql = $wpdb->prepare("SELECT * FROM $teams_table WHERE $where_sql ORDER BY id DESC", $params);
        $teams = $wpdb->get_results($sql, ARRAY_A);

        foreach ($teams as &$t) {
            $coach_u = get_userdata($t['coach_id']);
            $t['coach_name'] = $coach_u ? $coach_u->display_name : 'Authorized Coach';

            $players = $wpdb->get_results($wpdb->prepare("SELECT * FROM $players_table WHERE team_id = %d", $t['id']), ARRAY_A);
            $t['players'] = $players;
            $t['player_count'] = count($players);
        }

        return $teams;
    }

    public static function get_tournament_fixtures($tournament_id) {
        global $wpdb;
        $fixtures_table = $wpdb->prefix . 'sportedia_tournament_fixtures';
        $teams_table    = $wpdb->prefix . 'sportedia_tournament_teams';

        $fixtures = $wpdb->get_results($wpdb->prepare("SELECT * FROM $fixtures_table WHERE tournament_id = %d ORDER BY round_number ASC, match_number ASC", $tournament_id), ARRAY_A);

        foreach ($fixtures as &$f) {
            $t1 = $wpdb->get_row($wpdb->prepare("SELECT team_name FROM $teams_table WHERE id = %d", $f['team1_id']));
            $t2 = $wpdb->get_row($wpdb->prepare("SELECT team_name FROM $teams_table WHERE id = %d", $f['team2_id']));

            $f['team1_name'] = $t1 ? $t1->team_name : ($f['team1_id'] === 0 ? 'BYE' : 'TBD');
            $f['team2_name'] = $t2 ? $t2->team_name : ($f['team2_id'] === 0 ? 'BYE' : 'TBD');
        }

        return $fixtures;
    }

    public function ajax_save_tournament() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!current_user_can('sportedia_manage_programs') && !Sportedia_Roles::is_sys_admin() && !Sportedia_Roles::is_general_mgr()) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sportedia_tournaments';

        $id                  = isset($_POST['tournament_id']) ? intval($_POST['tournament_id']) : 0;
        $tournament_name     = sanitize_text_field($_POST['tournament_name']);
        $sport               = sanitize_text_field($_POST['sport']);
        $category            = sanitize_text_field($_POST['category']);
        $description         = sanitize_textarea_field($_POST['description']);
        $branch_id           = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        $start_date          = sanitize_text_field($_POST['start_date']);
        $end_date            = sanitize_text_field($_POST['end_date']);
        $reg_open_date       = sanitize_text_field($_POST['reg_open_date']);
        $reg_close_date      = sanitize_text_field($_POST['reg_close_date']);
        $team_limit          = isset($_POST['team_limit']) ? intval($_POST['team_limit']) : 16;
        $players_per_team    = isset($_POST['players_per_team']) ? intval($_POST['players_per_team']) : 7;
        $max_substitutes     = isset($_POST['max_substitutes']) ? intval($_POST['max_substitutes']) : 5;
        $status              = sanitize_text_field($_POST['status']);
        $rules_notes         = sanitize_textarea_field($_POST['rules_notes']);

        if (empty($tournament_name) || empty($start_date) || empty($end_date)) {
            wp_send_json_error('Tournament name, start date, and end date are required.');
        }

        $data = array(
            'tournament_name'  => $tournament_name,
            'sport'            => $sport,
            'category'         => $category,
            'description'      => $description,
            'branch_id'        => $branch_id,
            'start_date'       => $start_date,
            'end_date'         => $end_date,
            'reg_open_date'    => $reg_open_date,
            'reg_close_date'   => $reg_close_date,
            'team_limit'       => $team_limit,
            'players_per_team' => $players_per_team,
            'max_substitutes'  => $max_substitutes,
            'status'           => $status,
            'rules_notes'      => $rules_notes,
        );

        $formats = array('%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s');

        if ($id > 0) {
            $wpdb->update($table, $data, array('id' => $id), $formats, array('%d'));
            Sportedia_DB::log_activity('tournament_updated', 'Updated tournament ' . $tournament_name);
            wp_send_json_success('Tournament updated.');
        } else {
            $wpdb->insert($table, $data, $formats);
            Sportedia_DB::log_activity('tournament_created', 'Created tournament ' . $tournament_name);
            wp_send_json_success('Tournament created.');
        }
    }

    public function ajax_delete_tournament() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!Sportedia_Roles::is_sys_admin() && !Sportedia_Roles::is_general_mgr()) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $id = isset($_POST['tournament_id']) ? intval($_POST['tournament_id']) : 0;
        if ($id > 0) {
            $wpdb->delete($wpdb->prefix . 'sportedia_tournaments', array('id' => $id));
            $wpdb->delete($wpdb->prefix . 'sportedia_tournament_teams', array('tournament_id' => $id));
            $wpdb->delete($wpdb->prefix . 'sportedia_tournament_players', array('tournament_id' => $id));
            $wpdb->delete($wpdb->prefix . 'sportedia_tournament_fixtures', array('tournament_id' => $id));

            Sportedia_DB::log_activity('tournament_deleted', 'Deleted tournament ID #' . $id);
            wp_send_json_success('Tournament deleted.');
        }
        wp_send_json_error('Invalid ID.');
    }

    public function ajax_search_tournaments() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        $search    = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $branch_id = isset($_POST['branch_filter']) ? intval($_POST['branch_filter']) : 0;
        $status    = isset($_POST['status_filter']) ? sanitize_text_field($_POST['status_filter']) : '';

        $tournaments = self::get_tournaments($search, $branch_id, $status);

        ob_start();
        if (!empty($tournaments)) :
            foreach ($tournaments as $t) : ?>
                <div class="sp-card" style="margin-bottom: 0; padding: 16px 20px; border-radius: var(--sp-radius); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                    <div style="flex: 2; min-width: 220px;">
                        <strong style="font-size: 15px; color: var(--sp-text-main); display: block;"><?php echo esc_html($t['tournament_name']); ?></strong>
                        <span style="font-size: 11px; color: var(--sp-text-muted); font-weight: 600; text-transform: uppercase;"><?php echo esc_html($t['sport'] . ' | ' . $t['category']); ?></span>
                    </div>

                    <div style="flex: 2; min-width: 180px;">
                        <span style="font-size: 10px; color: var(--sp-text-muted); display: block; text-transform: uppercase;">Venue & Dates</span>
                        <strong style="font-size: 12px; color: #000;"><?php echo esc_html($t['branch_name']); ?></strong>
                        <span style="font-size: 11px; color: var(--sp-text-muted); font-family: monospace; display: block;"><?php echo esc_html(date('Y-m-d', strtotime($t['start_date'])) . ' to ' . date('Y-m-d', strtotime($t['end_date']))); ?></span>
                    </div>

                    <div style="flex: 1; min-width: 130px;">
                        <span style="font-size: 10px; color: var(--sp-text-muted); display: block; text-transform: uppercase;">Teams Capacity</span>
                        <strong style="font-size: 14px; color: #0284c7;"><?php echo esc_html($t['registered_teams'] . ' / ' . $t['team_limit']); ?> Teams</strong>
                    </div>

                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="sp-badge <?php echo $t['status'] === 'open' ? 'sp-badge-active' : 'sp-badge-inactive'; ?>">
                            <?php echo esc_html(ucfirst($t['status'])); ?>
                        </span>

                        <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='openRegisterTeamModal(<?php echo json_encode($t); ?>)'>Register Team</button>
                        <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='viewDrawModal(<?php echo json_encode($t); ?>)'>Draw & Brackets</button>
                        <?php if (Sportedia_Roles::is_sys_admin() || Sportedia_Roles::is_general_mgr()) : ?>
                            <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='editTournament(<?php echo json_encode($t); ?>)'>Edit</button>
                            <button class="sp-btn sp-btn-secondary sp-btn-sm" style="color:#dc2626;" onclick="deleteTournament(<?php echo $t['id']; ?>)">Delete</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach;
        else : ?>
            <div class="sp-card" style="text-align: center; color: var(--sp-text-muted); padding: 48px;">
                <h3>No tournaments found</h3>
                <p>No tournament records match your search criteria.</p>
            </div>
        <?php endif;
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html, 'count' => count($tournaments)));
    }

    public function ajax_save_tournament_team() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        global $wpdb;
        $teams_table   = $wpdb->prefix . 'sportedia_tournament_teams';
        $players_table = $wpdb->prefix . 'sportedia_tournament_players';
        $tourn_table   = $wpdb->prefix . 'sportedia_tournaments';

        $team_id       = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
        $tournament_id = isset($_POST['tournament_id']) ? intval($_POST['tournament_id']) : 0;
        $raw_team_name = isset($_POST['team_name']) ? sanitize_text_field($_POST['team_name']) : '';
        $coach_id      = get_current_user_id();

        // 1. Team Name Validation: English, uppercase, >= 5 chars
        $team_name = strtoupper(trim($raw_team_name));
        if (strlen($team_name) < 5 || !preg_match('/^[A-Z0-9\s\-_]+$/', $team_name)) {
            wp_send_json_error('Team name must be in English, uppercase, and at least 5 characters long.');
        }

        $tourn = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tourn_table WHERE id = %d", $tournament_id), ARRAY_A);
        if (!$tourn) {
            wp_send_json_error('Tournament not found.');
        }

        // 2. 12-Hour Edit Lock Calculation
        $start_ts = strtotime($tourn['start_date']);
        $hours_until_start = ($start_ts - time()) / 3600;

        if ($team_id > 0 && $hours_until_start <= 12 && !Sportedia_Roles::is_sys_admin() && !Sportedia_Roles::is_general_mgr()) {
            wp_send_json_error('Team registration editing is locked exactly 12 hours before tournament start.');
        }

        // 3. Unique Team Name in Tournament
        $existing_team = $wpdb->get_var($wpdb->prepare("SELECT id FROM $teams_table WHERE tournament_id = %d AND team_name = %s AND id != %d", $tournament_id, $team_name, $team_id));
        if ($existing_team) {
            wp_send_json_error('A team with this exact name is already registered in this tournament.');
        }

        if ($team_id > 0) {
            $wpdb->update($teams_table, array('team_name' => $team_name), array('id' => $team_id));
            $wpdb->delete($players_table, array('team_id' => $team_id));
        } else {
            // Check Capacity
            $current_teams = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $teams_table WHERE tournament_id = %d AND status != 'cancelled'", $tournament_id));
            if ($current_teams >= intval($tourn['team_limit'])) {
                wp_send_json_error('Tournament has reached its maximum team capacity limit.');
            }

            $wpdb->insert($teams_table, array(
                'tournament_id' => $tournament_id,
                'coach_id'      => $coach_id,
                'team_name'     => $team_name,
                'status'        => 'submitted',
                'submitted_at'  => date('Y-m-d H:i:s')
            ));
            $team_id = $wpdb->insert_id;
        }

        // Save Players Roster
        $players_data = isset($_POST['players']) && is_array($_POST['players']) ? $_POST['players'] : array();
        $player_count = 0;

        foreach ($players_data as $p) {
            $p_name = sanitize_text_field($p['name'] ?? '');
            if (empty($p_name)) continue;

            $wpdb->insert($players_table, array(
                'team_id'       => $team_id,
                'tournament_id' => $tournament_id,
                'user_id'       => intval($p['user_id'] ?? 0),
                'player_name'   => $p_name,
                'employee_id'   => sanitize_text_field($p['employee_id'] ?? ''),
                'phone'         => sanitize_text_field($p['phone'] ?? ''),
                'dob'           => !empty($p['dob']) ? sanitize_text_field($p['dob']) : null,
                'position_role' => sanitize_text_field($p['role'] ?? 'Player')
            ));
            $player_count++;
        }

        Sportedia_DB::log_activity('tournament_team_registered', 'Registered team ' . $team_name . ' (' . $player_count . ' players) in tournament #' . $tournament_id);

        wp_send_json_success(array('message' => 'Team registered successfully.', 'team_id' => $team_id));
    }

    public function ajax_delete_tournament_team() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        global $wpdb;
        $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;

        if ($team_id > 0) {
            $wpdb->delete($wpdb->prefix . 'sportedia_tournament_teams', array('id' => $team_id));
            $wpdb->delete($wpdb->prefix . 'sportedia_tournament_players', array('team_id' => $team_id));
            wp_send_json_success('Team registration cancelled.');
        }
        wp_send_json_error('Invalid ID.');
    }

    public function ajax_generate_random_draw() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!Sportedia_Roles::is_sys_admin() && !Sportedia_Roles::is_general_mgr() && !current_user_can('sportedia_manage_programs')) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $tournament_id  = isset($_POST['tournament_id']) ? intval($_POST['tournament_id']) : 0;
        $teams_table    = $wpdb->prefix . 'sportedia_tournament_teams';
        $fixtures_table = $wpdb->prefix . 'sportedia_tournament_fixtures';

        $teams = $wpdb->get_col($wpdb->prepare("SELECT id FROM $teams_table WHERE tournament_id = %d AND status != 'cancelled'", $tournament_id));
        if (count($teams) < 2) {
            wp_send_json_error('At least 2 registered teams are required to generate a random draw.');
        }

        // Shuffle teams randomly for server-side reproducible draw
        shuffle($teams);

        // Delete previous fixtures for round 1
        $wpdb->delete($fixtures_table, array('tournament_id' => $tournament_id, 'round_number' => 1));

        $match_no = 1;
        for ($i = 0; $i < count($teams); $i += 2) {
            $t1 = $teams[$i];
            $t2 = isset($teams[$i + 1]) ? $teams[$i + 1] : 0; // Bye if odd number

            $wpdb->insert($fixtures_table, array(
                'tournament_id'  => $tournament_id,
                'round_number'   => 1,
                'match_number'   => $match_no++,
                'team1_id'       => $t1,
                'team2_id'       => $t2,
                'winner_team_id' => ($t2 === 0) ? $t1 : 0,
                'status'         => ($t2 === 0) ? 'completed' : 'scheduled'
            ));
        }

        $wpdb->update($wpdb->prefix . 'sportedia_tournaments', array('status' => 'locked'), array('id' => $tournament_id));

        Sportedia_DB::log_activity('random_draw_generated', 'Generated random tournament draw for tournament #' . $tournament_id);

        wp_send_json_success('Random draw generated successfully.');
    }

    public function ajax_save_fixture_score() {
        check_ajax_referer('sportedia_nonce', 'nonce');

        if (!Sportedia_Roles::is_sys_admin() && !Sportedia_Roles::is_general_mgr() && !current_user_can('sportedia_manage_programs')) {
            wp_send_json_error('Unauthorized.');
        }

        global $wpdb;
        $fixture_id  = isset($_POST['fixture_id']) ? intval($_POST['fixture_id']) : 0;
        $team1_score = isset($_POST['team1_score']) ? intval($_POST['team1_score']) : 0;
        $team2_score = isset($_POST['team2_score']) ? intval($_POST['team2_score']) : 0;
        $status      = sanitize_text_field($_POST['status'] ?? 'completed');

        $fixtures_table = $wpdb->prefix . 'sportedia_tournament_fixtures';
        $fixture = $wpdb->get_row($wpdb->prepare("SELECT * FROM $fixtures_table WHERE id = %d", $fixture_id), ARRAY_A);

        if (!$fixture) {
            wp_send_json_error('Fixture not found.');
        }

        $winner_id = 0;
        if ($team1_score > $team2_score) $winner_id = $fixture['team1_id'];
        else if ($team2_score > $team1_score) $winner_id = $fixture['team2_id'];

        $wpdb->update($fixtures_table, array(
            'team1_score'    => $team1_score,
            'team2_score'    => $team2_score,
            'winner_team_id' => $winner_id,
            'status'         => $status
        ), array('id' => $fixture_id));

        wp_send_json_success('Match score updated.');
    }
}
