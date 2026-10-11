<?php
if (!defined('ABSPATH')) exit;

$search        = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$branch_filter = isset($_GET['branch_filter']) ? intval($_GET['branch_filter']) : 0;
$status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';

$tournaments  = Sportedia_Tournament_Manager::get_tournaments($search, $branch_filter, $status_filter);
$branchesList = Sportedia_Branch_Manager::get_branches();
$usersList    = Sportedia_User_Manager::get_users();

$is_admin = Sportedia_Roles::is_sys_admin() || Sportedia_Roles::is_general_mgr();
?>

<div class="sp-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
    <div>
        <h1 class="sp-page-title">Tournament Management</h1>
        <p class="sp-page-subtitle">Configure sports tournaments, coach team rosters, capacity rules, random draws, and match fixtures.</p>
    </div>
    <?php if ($is_admin || current_user_can('sportedia_manage_programs')) : ?>
        <button class="sp-btn sp-btn-primary" onclick="openCreateTournamentModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Create New Tournament
        </button>
    <?php endif; ?>
</div>

<div class="sp-card" style="padding: 16px; margin-bottom: 20px;">
    <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <input type="hidden" name="module" value="tournaments">

        <div style="flex: 2; min-width: 220px;">
            <input type="text" name="search" id="sp_tourn_search" class="sp-floating-input" placeholder="Search tournament name, sport or category..." value="<?php echo esc_attr($search); ?>">
        </div>

        <div style="flex: 1; min-width: 160px;">
            <select name="branch_filter" id="sp_tourn_branch" class="sp-floating-select" style="min-width: 180px;">
                <option value="0">All Branches / Venues</option>
                <?php foreach ($branchesList as $b) : ?>
                    <option value="<?php echo esc_attr($b['id']); ?>" <?php selected($branch_filter, $b['id']); ?>><?php echo esc_html($b['branch_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="flex: 1; min-width: 160px;">
            <select name="status_filter" id="sp_tourn_status" class="sp-floating-select" style="min-width: 160px;">
                <option value="">All Statuses</option>
                <option value="open" <?php selected($status_filter, 'open'); ?>>Registration Open</option>
                <option value="locked" <?php selected($status_filter, 'locked'); ?>>Registration Locked</option>
                <option value="closed" <?php selected($status_filter, 'closed'); ?>>Completed / Closed</option>
            </select>
        </div>

        <button type="submit" class="sp-btn sp-btn-primary">Filter</button>
    </form>
</div>

<!-- Unified Full-Width Extended Rows Layout (1 Row Per Tournament) -->
<?php if (!empty($tournaments)) : ?>
    <div style="display: flex; flex-direction: column; gap: 12px;" id="tournaments_container">
        <?php foreach ($tournaments as $t) : ?>
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
                    <?php if ($is_admin) : ?>
                        <button class="sp-btn sp-btn-secondary sp-btn-sm" onclick='editTournament(<?php echo json_encode($t); ?>)'>Edit</button>
                        <button class="sp-btn sp-btn-secondary sp-btn-sm" style="color:#dc2626;" onclick="deleteTournament(<?php echo $t['id']; ?>)">Delete</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <div class="sp-card" style="text-align: center; color: var(--sp-text-muted); padding: 48px;">
        <h3>No tournaments found</h3>
        <p>No tournaments match your search criteria.</p>
    </div>
<?php endif; ?>

<!-- Create / Edit Tournament Modal -->
<div id="spTournamentModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 640px;">
        <div class="sp-modal-header">
            <h3 id="spTournModalTitle" class="sp-modal-title">Create Tournament</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spTournamentModal')">&times;</button>
        </div>

        <form id="spTournForm">
            <input type="hidden" id="sp_tourn_id" name="tournament_id" value="0">

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="text" id="tourn_name" name="tournament_name" class="sp-floating-input" placeholder=" " required>
                    <label for="tourn_name" class="sp-floating-label">Tournament Name *</label>
                </div>

                <div class="sp-form-group">
                    <select id="tourn_sport" name="sport" class="sp-floating-select" required>
                        <option value="Football">Football</option>
                        <option value="Basketball">Basketball</option>
                        <option value="Swimming">Swimming</option>
                        <option value="Volleyball">Volleyball</option>
                        <option value="Tennis">Tennis</option>
                        <option value="Martial Arts">Martial Arts</option>
                    </select>
                    <label for="tourn_sport" class="sp-floating-label">Sport Category *</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="text" id="tourn_category" name="category" class="sp-floating-input" placeholder=" " value="U-16 Youth">
                    <label for="tourn_category" class="sp-floating-label">Age Group / Division</label>
                </div>

                <div class="sp-form-group">
                    <select id="tourn_branch_id" name="branch_id" class="sp-floating-select">
                        <option value="0">All Branches / Main Complex</option>
                        <?php foreach ($branchesList as $b) : ?>
                            <option value="<?php echo esc_attr($b['id']); ?>"><?php echo esc_html($b['branch_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="tourn_branch_id" class="sp-floating-label">Venue / Branch</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="datetime-local" id="tourn_start_date" name="start_date" class="sp-floating-input" required>
                    <label for="tourn_start_date" class="sp-floating-label">Start Date & Time *</label>
                </div>

                <div class="sp-form-group">
                    <input type="datetime-local" id="tourn_end_date" name="end_date" class="sp-floating-input" required>
                    <label for="tourn_end_date" class="sp-floating-label">End Date & Time *</label>
                </div>
            </div>

            <div class="sp-grid-2">
                <div class="sp-form-group">
                    <input type="datetime-local" id="tourn_reg_open" name="reg_open_date" class="sp-floating-input">
                    <label for="tourn_reg_open" class="sp-floating-label">Registration Opening</label>
                </div>

                <div class="sp-form-group">
                    <input type="datetime-local" id="tourn_reg_close" name="reg_close_date" class="sp-floating-input">
                    <label for="tourn_reg_close" class="sp-floating-label">Registration Deadline</label>
                </div>
            </div>

            <div class="sp-grid-3" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                <div class="sp-form-group">
                    <input type="number" id="tourn_team_limit" name="team_limit" class="sp-floating-input" value="16" min="2">
                    <label for="tourn_team_limit" class="sp-floating-label">Team Limit</label>
                </div>

                <div class="sp-form-group">
                    <input type="number" id="tourn_players_per_team" name="players_per_team" class="sp-floating-input" value="7" min="1">
                    <label for="tourn_players_per_team" class="sp-floating-label">Main Players</label>
                </div>

                <div class="sp-form-group">
                    <input type="number" id="tourn_max_substitutes" name="max_substitutes" class="sp-floating-input" value="5" min="0">
                    <label for="tourn_max_substitutes" class="sp-floating-label">Substitutes</label>
                </div>
            </div>

            <div class="sp-form-group">
                <select id="tourn_status" name="status" class="sp-floating-select">
                    <option value="open">Registration Open</option>
                    <option value="locked">Registration Locked / In Progress</option>
                    <option value="closed">Completed / Closed</option>
                </select>
                <label for="tourn_status" class="sp-floating-label">Tournament Status</label>
            </div>

            <div class="sp-form-group">
                <textarea id="tourn_description" name="description" class="sp-floating-input" style="height: 60px;" placeholder=" "></textarea>
                <label for="tourn_description" class="sp-floating-label">Tournament Rules & Operational Notes</label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--sp-border-color); padding-top: 16px;">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spTournamentModal')">Cancel</button>
                <button type="submit" class="sp-btn sp-btn-primary">Save Tournament</button>
            </div>
        </form>
    </div>
</div>

<!-- Team Registration Multi-Step Modal -->
<div id="spTeamRegModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 720px;">
        <div class="sp-modal-header">
            <h3 id="spTeamRegModalTitle" class="sp-modal-title">Register Team for Tournament</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spTeamRegModal')">&times;</button>
        </div>

        <div style="display: flex; background: #f8fafc; border: 1px solid var(--sp-border-color); border-radius: 8px; padding: 6px; margin-bottom: 20px;">
            <div id="reg_tab_1" class="sp-step-tab active" style="flex:1; text-align:center; padding: 8px; font-size: 12px; font-weight: 700; border-radius: 6px; color: #0284c7; background: #ffffff;">
                1. Team Info & Rules
            </div>
            <div id="reg_tab_2" class="sp-step-tab" style="flex:1; text-align:center; padding: 8px; font-size: 12px; font-weight: 700; border-radius: 6px; color: var(--sp-text-muted);">
                2. Player Roster
            </div>
            <div id="reg_tab_3" class="sp-step-tab" style="flex:1; text-align:center; padding: 8px; font-size: 12px; font-weight: 700; border-radius: 6px; color: var(--sp-text-muted);">
                3. Final Confirmation
            </div>
        </div>

        <form id="spTeamRegForm">
            <input type="hidden" id="reg_tourn_id" name="tournament_id" value="0">
            <input type="hidden" id="reg_team_id" name="team_id" value="0">

            <div id="reg_step_1">
                <div class="sp-form-group">
                    <input type="text" id="reg_team_name" name="team_name" class="sp-floating-input" placeholder=" " required style="text-transform: uppercase;">
                    <label for="reg_team_name" class="sp-floating-label">Team Name (ENGLISH UPPERCASE, MIN 5 CHARS) *</label>
                </div>

                <div style="background: #f8fafc; border: 1px solid var(--sp-border-color); border-radius: 8px; padding: 14px; font-size: 12px; margin-bottom: 20px;">
                    <strong>Tournament Capacity & Rules:</strong>
                    <div id="reg_tourn_rules_box" style="margin-top: 6px; color: var(--sp-text-muted);">
                        Loading rules...
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="button" class="sp-btn sp-btn-primary" onclick="goToRegStep(2)">Next: Enter Roster &rarr;</button>
                </div>
            </div>

            <div id="reg_step_2" style="display: none;">
                <h4 style="margin: 0 0 12px 0; font-size: 14px;">Participating Player Roster</h4>
                <div id="player_rows_container" style="display: flex; flex-direction: column; gap: 10px; max-height: 320px; overflow-y: auto; padding-right: 4px; margin-bottom: 20px;">
                    <!-- Player rows injected via JS -->
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <button type="button" class="sp-btn sp-btn-secondary" onclick="goToRegStep(1)">&larr; Back</button>
                    <button type="button" class="sp-btn sp-btn-primary" onclick="goToRegStep(3)">Next: Review & Submit &rarr;</button>
                </div>
            </div>

            <div id="reg_step_3" style="display: none;">
                <h4 style="margin: 0 0 12px 0; font-size: 14px;">Review Team Registration Summary</h4>
                <div style="background: #f8fafc; border: 1px solid var(--sp-border-color); border-radius: 8px; padding: 14px; font-size: 13px; margin-bottom: 20px;">
                    <div><strong>Team Name:</strong> <span id="summary_team_name"></span></div>
                    <div><strong>Tournament:</strong> <span id="summary_tourn_name"></span></div>
                    <div><strong>Registered Players:</strong> <span id="summary_player_count"></span></div>
                    <div style="font-size: 11px; color: var(--sp-text-muted); margin-top: 8px;">Note: Registration can be edited until 12 hours before the tournament start time.</div>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <button type="button" class="sp-btn sp-btn-secondary" onclick="goToRegStep(2)">&larr; Back</button>
                    <button type="submit" class="sp-btn sp-btn-primary">Confirm & Register Team</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Dedicated Printable A4 Team Registration Modal -->
<div id="spTeamPrintModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 600px; padding: 0; overflow: hidden;" id="spPrintableTeamSheetModal">
        <div class="sp-modal-header sp-no-print" style="padding: 16px 20px; margin: 0; background: var(--sp-bg-main);">
            <h3 class="sp-modal-title">Team Registration Official Printable Document</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spTeamPrintModal')">&times;</button>
        </div>

        <div style="padding: 30px; background: #ffffff; color: #111827;" id="spA4TeamSheet">
            <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #000000; padding-bottom: 12px; margin-bottom: 16px;">
                <div>
                    <strong style="font-size: 20px; letter-spacing: -0.5px;">Sportedia Tournaments</strong>
                    <span style="font-size: 11px; color: var(--sp-text-muted); display: block; margin-top: 2px;">Official Team Roster & Entry Document</span>
                </div>
                <div style="text-align: right;">
                    <h3 style="margin: 0; font-size: 16px; text-transform: uppercase;" id="pr_tourn_name">TOURNAMENT</h3>
                    <span id="pr_submitted_at" style="font-size: 11px; color: var(--sp-text-muted); display: block;">2026-03-30</span>
                </div>
            </div>

            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb; font-size: 12px; margin-bottom: 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <span style="font-size: 10px; color: var(--sp-text-muted); display: block; text-transform: uppercase;">Team Name</span>
                    <strong id="pr_team_name" style="font-size: 15px; color: #000;">TEAM NAME</strong>
                </div>
                <div>
                    <span style="font-size: 10px; color: var(--sp-text-muted); display: block; text-transform: uppercase;">Authorized Coach</span>
                    <strong id="pr_coach_name" style="font-size: 13px;">Coach Name</strong>
                </div>
            </div>

            <table style="width: 100%; font-size: 12px; border-collapse: collapse; margin-bottom: 16px;">
                <thead>
                    <tr style="background: #000; color: #fff;">
                        <th style="padding: 8px; text-align: left;">#</th>
                        <th style="padding: 8px; text-align: left;">Player Name</th>
                        <th style="padding: 8px; text-align: left;">Mobile / ID</th>
                        <th style="padding: 8px; text-align: left;">Role</th>
                    </tr>
                </thead>
                <tbody id="pr_roster_tbody">
                    <!-- Roster rows -->
                </tbody>
            </table>

            <div style="display: flex; justify-content: space-between; align-items: flex-end; border-top: 1px dashed #e5e7eb; padding-top: 16px; font-size: 11px; color: var(--sp-text-muted);">
                <div>
                    <span>Official Stamp & Signature: _______________________</span>
                </div>
                <div style="text-align: right;">
                    <span id="pr_team_barcode">*TEAM-1001*</span>
                </div>
            </div>
        </div>

        <div style="padding: 12px 20px; background: var(--sp-bg-main); border-top: 1px solid var(--sp-border-color); display: flex; gap: 8px; justify-content: flex-end;" class="sp-no-print">
            <button type="button" class="sp-btn sp-btn-secondary sp-btn-sm" onclick="window.print()">Print Registration Document</button>
            <button type="button" class="sp-btn sp-btn-secondary sp-btn-sm" onclick="spCloseModal('spTeamPrintModal')">Close</button>
        </div>
    </div>
</div>

<!-- Draw & Fixtures Modal -->
<div id="spDrawModal" class="sp-modal">
    <div class="sp-modal-content" style="max-width: 780px;">
        <div class="sp-modal-header">
            <h3 id="spDrawModalTitle" class="sp-modal-title">Tournament Draw & Match Brackets</h3>
            <button type="button" class="sp-modal-close" onclick="spCloseModal('spDrawModal')">&times;</button>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div style="font-size: 13px; color: var(--sp-text-muted);">
                Tournament: <strong id="draw_modal_tourn_name" style="color:#000;"></strong>
            </div>
            <?php if ($is_admin) : ?>
                <button type="button" class="sp-btn sp-btn-primary sp-btn-sm" onclick="triggerRandomDraw()">
                    Generate Server Random Draw
                </button>
            <?php endif; ?>
        </div>

        <div id="fixtures_bracket_container" style="max-height: 400px; overflow-y: auto; padding: 10px; background: #f8fafc; border: 1px solid var(--sp-border-color); border-radius: 8px;">
            <!-- Fixtures loaded dynamically -->
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 16px;">
            <button type="button" class="sp-btn sp-btn-secondary" onclick="spCloseModal('spDrawModal')">Close</button>
        </div>
    </div>
</div>

<script>
var activeTournForReg = null;

function openCreateTournamentModal() {
    jQuery('#spTournModalTitle').text('Create Tournament');
    jQuery('#sp_tourn_id').val('0');
    jQuery('#spTournForm')[0].reset();
    spOpenModal('spTournamentModal');
}

function editTournament(t) {
    jQuery('#spTournModalTitle').text('Edit Tournament');
    jQuery('#sp_tourn_id').val(t.id);
    jQuery('#tourn_name').val(t.tournament_name);
    jQuery('#tourn_sport').val(t.sport);
    jQuery('#tourn_category').val(t.category);
    jQuery('#tourn_branch_id').val(t.branch_id);
    jQuery('#tourn_start_date').val(t.start_date.replace(' ', 'T'));
    jQuery('#tourn_end_date').val(t.end_date.replace(' ', 'T'));
    jQuery('#tourn_reg_open').val(t.reg_open_date ? t.reg_open_date.replace(' ', 'T') : '');
    jQuery('#tourn_reg_close').val(t.reg_close_date ? t.reg_close_date.replace(' ', 'T') : '');
    jQuery('#tourn_team_limit').val(t.team_limit);
    jQuery('#tourn_players_per_team').val(t.players_per_team);
    jQuery('#tourn_max_substitutes').val(t.max_substitutes);
    jQuery('#tourn_status').val(t.status);
    jQuery('#tourn_description').val(t.description || '');
    spOpenModal('spTournamentModal');
}

function deleteTournament(id) {
    if (confirm('Are you sure you want to delete this tournament?')) {
        jQuery.post(sportedia_vars.ajax_url, {
            action: 'sportedia_delete_tournament',
            nonce: sportedia_vars.nonce,
            tournament_id: id
        }, function(res) {
            if (res.success) location.reload();
            else alert(res.data || 'Failed to delete tournament.');
        });
    }
}

function goToRegStep(step) {
    jQuery('#reg_step_1, #reg_step_2, #reg_step_3').hide();
    jQuery('#reg_step_' + step).show();
    jQuery('.sp-step-tab').css({ background: 'transparent', color: 'var(--sp-text-muted)' });
    jQuery('#reg_tab_' + step).css({ background: '#ffffff', color: '#0284c7' });

    if (step === 3) {
        jQuery('#summary_team_name').text(jQuery('#reg_team_name').val().toUpperCase());
        jQuery('#summary_tourn_name').text(activeTournForReg ? activeTournForReg.tournament_name : '');
        var count = jQuery('#player_rows_container input.p-name').filter(function(){ return this.value.trim() !== ''; }).length;
        jQuery('#summary_player_count').text(count + ' Players');
    }
}

function openRegisterTeamModal(t) {
    activeTournForReg = t;
    jQuery('#reg_tourn_id').val(t.id);
    jQuery('#reg_team_id').val('0');
    jQuery('#spTeamRegForm')[0].reset();

    var rulesHtml = 'Main Players: <strong>' + t.players_per_team + '</strong> | Substitutes: <strong>' + t.max_substitutes + '</strong><br>Capacity: <strong>' + t.registered_teams + ' / ' + t.team_limit + ' Teams</strong>';
    jQuery('#reg_tourn_rules_box').html(rulesHtml);

    // Build player roster inputs dynamically
    var $box = jQuery('#player_rows_container').empty();
    var totalAllowed = parseInt(t.players_per_team) + intval(t.max_substitutes);
    for (var i = 1; i <= totalAllowed; i++) {
        var isSub = i > parseInt(t.players_per_team);
        var roleLabel = isSub ? 'Substitute' : 'Player #' + i;
        var row = '<div style="display:flex; gap:8px; align-items:center;">' +
            '<span style="font-size:11px; width:80px; font-weight:600;">' + roleLabel + ':</span>' +
            '<input type="text" name="players[' + i + '][name]" placeholder="Player Full Name" class="sp-floating-input p-name" style="flex:2;">' +
            '<input type="text" name="players[' + i + '][phone]" placeholder="Mobile / ID" class="sp-floating-input" style="flex:1;">' +
            '<input type="hidden" name="players[' + i + '][role]" value="' + roleLabel + '">' +
            '</div>';
        $box.append(row);
    }

    goToRegStep(1);
    spOpenModal('spTeamRegModal');
}

function intval(v) { return parseInt(v) || 0; }

function viewDrawModal(t) {
    activeTournForReg = t;
    jQuery('#draw_modal_tourn_name').text(t.tournament_name);
    spOpenModal('spDrawModal');
}

function triggerRandomDraw() {
    if (!activeTournForReg) return;
    if (confirm('Generate random server-side team draw matchups?')) {
        jQuery.post(sportedia_vars.ajax_url, {
            action: 'sportedia_generate_random_draw',
            nonce: sportedia_vars.nonce,
            tournament_id: activeTournForReg.id
        }, function(res) {
            if (res.success) {
                alert(res.data);
                location.reload();
            } else {
                alert(res.data || 'Failed to generate draw.');
            }
        });
    }
}

jQuery('#spTournForm').on('submit', function(e) {
    e.preventDefault();
    var data = jQuery(this).serialize() + '&action=sportedia_save_tournament&nonce=' + sportedia_vars.nonce;
    jQuery.post(sportedia_vars.ajax_url, data, function(res) {
        if (res.success) location.reload();
        else alert(res.data || 'Error saving tournament.');
    });
});

jQuery('#spTeamRegForm').on('submit', function(e) {
    e.preventDefault();
    var data = jQuery(this).serialize() + '&action=sportedia_save_tournament_team&nonce=' + sportedia_vars.nonce;
    jQuery.post(sportedia_vars.ajax_url, data, function(res) {
        if (res.success) {
            alert('Team registered successfully!');
            location.reload();
        } else alert(res.data || 'Error registering team.');
    });
});
</script>
