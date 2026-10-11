<?php
if (!defined('ABSPATH')) exit;

$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$tournaments = Sportedia_Tournament_Manager::get_tournaments($search, 0, 'open');

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sportedia – Public Tournament Portal & Live Results</title>
    <?php wp_head(); ?>
    <style>
        body.sp-public-tourn-body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            font-family: 'Google Sans Flex', -apple-system, sans-serif;
            color: #0f172a;
        }
        .sp-public-header {
            background: #0f172a;
            color: #ffffff;
            padding: 24px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .sp-public-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sp-public-logo {
            width: 40px;
            height: 40px;
            background: #ffffff;
            color: #0f172a;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 20px;
        }
        .sp-public-container {
            max-width: 1100px;
            margin: 32px auto;
            padding: 0 20px;
        }
    </style>
</head>
<body class="sp-public-tourn-body">

<header class="sp-public-header">
    <div class="sp-public-brand">
        <div class="sp-public-logo">S</div>
        <div>
            <strong style="font-size: 18px; display: block;">Sportedia Tournaments</strong>
            <span style="font-size: 11px; opacity: 0.8;">Public Live Fixtures, Match Scores & Brackets</span>
        </div>
    </div>
    <div>
        <a href="<?php echo esc_url(get_permalink(get_option('sportedia_page_id'))); ?>" class="sp-btn sp-btn-secondary sp-btn-sm" style="background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2);">
            Sign In to Dashboard
        </a>
    </div>
</header>

<div class="sp-public-container">
    <div class="sp-page-header" style="margin-bottom: 24px;">
        <h1 class="sp-page-title" style="font-size: 24px; font-weight: 800;">Open Sports Tournaments</h1>
        <p class="sp-page-subtitle">Real-time match fixtures, round brackets, standings, and verified match results.</p>
    </div>

    <!-- Active Tournaments View -->
    <?php if (!empty($tournaments)) : ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($tournaments as $t) : ?>
                <?php $fixtures = Sportedia_Tournament_Manager::get_tournament_fixtures($t['id']); ?>
                <div class="sp-card" style="padding: 24px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; margin-bottom: 16px;">
                        <div>
                            <strong style="font-size: 18px; color: #0f172a; display: block;"><?php echo esc_html($t['tournament_name']); ?></strong>
                            <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;"><?php echo esc_html($t['sport'] . ' • ' . $t['category'] . ' • ' . $t['branch_name']); ?></span>
                        </div>
                        <span class="sp-badge sp-badge-active" style="font-size: 11px;">
                            <?php echo esc_html($t['registered_teams'] . ' Registered Teams'); ?>
                        </span>
                    </div>

                    <!-- Fixtures & Scores Bracket -->
                    <h4 style="font-size: 14px; margin: 0 0 12px 0; color: #0f172a;">Live Match Fixtures & Scores</h4>
                    <?php if (!empty($fixtures)) : ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px;">
                            <?php foreach ($fixtures as $f) : ?>
                                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; font-size: 12px;">
                                    <div style="display: flex; justify-content: space-between; color: #64748b; font-weight: 700; margin-bottom: 8px; font-size: 10px; text-transform: uppercase;">
                                        <span>Round <?php echo esc_html($f['round_number']); ?> • Match #<?php echo esc_html($f['match_number']); ?></span>
                                        <span><?php echo esc_html(ucfirst($f['status'])); ?></span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                        <span style="font-weight: 600; text-transform: uppercase;"><?php echo esc_html($f['team1_name']); ?></span>
                                        <strong style="font-size: 14px; background: #fff; padding: 2px 8px; border-radius: 4px; border: 1px solid #e2e8f0;"><?php echo esc_html($f['team1_score']); ?></strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="font-weight: 600; text-transform: uppercase;"><?php echo esc_html($f['team2_name']); ?></span>
                                        <strong style="font-size: 14px; background: #fff; padding: 2px 8px; border-radius: 4px; border: 1px solid #e2e8f0;"><?php echo esc_html($f['team2_score']); ?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p style="font-size: 12px; color: #64748b; margin: 0;">Tournament registration is open. Official draw and fixtures will be published shortly.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="sp-card" style="text-align: center; color: #64748b; padding: 48px; background: #fff; border-radius: 12px;">
            <h3>No open public tournaments</h3>
            <p>Check back soon for upcoming tournaments and live competition brackets.</p>
        </div>
    <?php endif; ?>
</div>

<?php wp_footer(); ?>
</body>
</html>
