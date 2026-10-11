<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$stats = Sportedia_Dashboard::get_stats_for_user();
$current_user = wp_get_current_user();
$user_id = get_current_user_id();

// Monthly Institutional Income Analytics (Actual Invoice Data)
$subs_table  = $wpdb->prefix . 'sportedia_subscriptions';
$curr_month  = date('Y-m');
$prev_month  = date('Y-m', strtotime('-1 month'));

$curr_revenue = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(price) FROM $subs_table WHERE DATE_FORMAT(start_date, '%%Y-%%m') = %s",
    $curr_month
));
$curr_revenue = floatval($curr_revenue);

$prev_revenue = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(price) FROM $subs_table WHERE DATE_FORMAT(start_date, '%%Y-%%m') = %s",
    $prev_month
));
$prev_revenue = floatval($prev_revenue);

$revenue_diff = $curr_revenue - $prev_revenue;
$pct_change   = $prev_revenue > 0 ? round(($revenue_diff / $prev_revenue) * 100, 1) : ($curr_revenue > 0 ? 100.0 : 0.0);

$is_positive = $revenue_diff >= 0;
$arrow_symbol = $is_positive ? '▲' : '▼';
$trend_color  = $is_positive ? '#16a34a' : '#dc2626';
$trend_bg     = $is_positive ? '#dcfce7' : '#fee2e2';

$user_subs = Sportedia_Subscription_Manager::get_subscriptions('', 0, '');
$has_expired = false;
$has_active  = false;
foreach ($user_subs as $ms) {
    if (intval($ms['user_id']) === $user_id) {
        if ($ms['status'] === 'expired') $has_expired = true;
        if ($ms['status'] === 'active') $has_active = true;
    }
}

$app_url = get_permalink(get_option('sportedia_page_id'));
$kiosk_page_id = get_option('sportedia_kiosk_page_id');
$kiosk_url = $kiosk_page_id ? get_permalink($kiosk_page_id) : home_url('/sportedia-kiosk/');
?>

<?php if ($has_expired && !$has_active) : ?>
    <div class="sp-card" style="background-color: #fef2f2; border-color: #fecaca; padding: 16px 20px; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 12px; color: #991b1b;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 01-2.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <div>
                <strong style="font-size: 16px; display: block;">Subscription Expired</strong>
                <span style="font-size: 13px;">Your membership subscription has expired. Please contact reception or your facility administrator to renew your membership.</span>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Dashboard Header -->
<div class="sp-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <div>
        <h1 class="sp-page-title">Main Dashboard Overview</h1>
        <p class="sp-page-subtitle">Welcome back, <?php echo esc_html($current_user->display_name); ?>. Here is your institutional performance & financial KPI analytics.</p>
    </div>
</div>

<!-- THREE PRIMARY QUICK ACCESS BUTTONS -->
<div class="sp-card" style="padding: 16px 20px; margin-bottom: 24px; background: #ffffff; border: 1px solid var(--sp-border-color);">
    <div style="font-size: 11px; font-weight: 700; color: var(--sp-text-muted); text-transform: uppercase; margin-bottom: 12px; letter-spacing: 0.5px;">
        PRIMARY QUICK ACCESS ACTIONS
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
        <!-- ACTION 1: Verification System Kiosk -->
        <a href="<?php echo esc_url($kiosk_url); ?>" target="_blank" class="sp-btn sp-btn-primary" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 12h10"/><path d="M12 7v10"/></svg>
            1. Verification System
        </a>

        <!-- ACTION 2: New Member / New Subscription -->
        <a href="<?php echo esc_url(add_query_arg('module', 'subscriptions', $app_url)); ?>" class="sp-btn sp-btn-primary" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px; background-color: #0284c7; border-color: #0284c7;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="17" y1="11" x2="23" y2="11"/></svg>
            2. New Member / Subscription
        </a>

        <!-- ACTION 3: Daily Operations & Reports -->
        <a href="<?php echo esc_url(add_query_arg('module', 'reports', $app_url)); ?>" class="sp-btn sp-btn-primary" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px; background-color: #0f172a; border-color: #0f172a;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            3. Daily Operations & Reports
        </a>
    </div>
</div>

<!-- PROFESSIONAL FINANCIAL & INSTITUTIONAL KPI CARDS GRID (EXACTLY 5 BOXES ON DESKTOP ROW) -->
<div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; margin-bottom: 24px;" class="sp-kpi-grid">
    <!-- 1. TODAY'S ATTENDANCE -->
    <div class="sp-card" style="margin-bottom:0; padding: 18px 16px; border-top: 3px solid #6366f1;">
        <div style="font-size: 10px; color: var(--sp-text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px;">TODAY'S ATTENDANCE</div>
        <div style="font-size: 26px; font-weight: 800; margin: 8px 0 4px 0; color: #0f172a;"><?php echo esc_html($stats['today_att']); ?></div>
        <div style="font-size: 11px; color: var(--sp-text-muted);">Check-ins Today</div>
    </div>

    <!-- 2. REMAINING SESSIONS -->
    <div class="sp-card" style="margin-bottom:0; padding: 18px 16px; border-top: 3px solid #f59e0b;">
        <div style="font-size: 10px; color: var(--sp-text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px;">REMAINING SESSIONS</div>
        <div style="font-size: 26px; font-weight: 800; margin: 8px 0 4px 0; color: #166534;"><?php echo esc_html($stats['total_sessions_remaining']); ?></div>
        <div style="font-size: 11px; color: var(--sp-text-muted);">Available Sessions</div>
    </div>

    <!-- 3. ACTIVE SUBSCRIPTIONS -->
    <div class="sp-card" style="margin-bottom:0; padding: 18px 16px; border-top: 3px solid #10b981;">
        <div style="font-size: 10px; color: var(--sp-text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px;">ACTIVE SUBSCRIPTIONS</div>
        <div style="font-size: 26px; font-weight: 800; margin: 8px 0 4px 0; color: #0f172a;"><?php echo esc_html($stats['total_subs']); ?></div>
        <div style="font-size: 11px; color: #166534;">Active Subscriptions</div>
    </div>

    <!-- 4. MONTHLY REVENUE -->
    <div class="sp-card" style="margin-bottom:0; padding: 18px 16px; border-top: 3px solid #0284c7;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <span style="font-size: 10px; color: var(--sp-text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px;">MONTHLY REVENUE</span>
            <span class="sp-badge" style="background: <?php echo $trend_bg; ?>; color: <?php echo $trend_color; ?>; font-weight: 800; font-size: 9px; padding: 2px 5px;">
                <?php echo $arrow_symbol . ' ' . abs($pct_change) . '%'; ?>
            </span>
        </div>
        <div style="font-size: 22px; font-weight: 800; margin: 8px 0 4px 0; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
            <?php echo esc_html(Sportedia_Finance::format_price($curr_revenue)); ?>
        </div>
        <div style="font-size: 11px; color: var(--sp-text-muted);">
            Prev: <strong><?php echo esc_html(Sportedia_Finance::format_price($prev_revenue)); ?></strong>
        </div>
    </div>

    <!-- 5. ACTIVE MEMBERS (NEW 5TH OPERATIONAL KPI) -->
    <div class="sp-card" style="margin-bottom:0; padding: 18px 16px; border-top: 3px solid #8b5cf6;">
        <div style="font-size: 10px; color: var(--sp-text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px;">ACTIVE MEMBERS</div>
        <div style="font-size: 26px; font-weight: 800; margin: 8px 0 4px 0; color: #0f172a;"><?php echo esc_html($stats['active_members']); ?></div>
        <div style="font-size: 11px; color: var(--sp-text-muted);">Unique Enrolled Members</div>
    </div>
</div>

<style>
@media (max-width: 1200px) {
    .sp-kpi-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
    }
}
</style>

<?php
$my_member_subs = array_filter($user_subs, function($s) use ($user_id) {
    return intval($s['user_id']) === $user_id;
});
?>

<?php if (!empty($my_member_subs)) : ?>
    <div class="sp-card" style="margin-bottom: 24px;">
        <h3 style="margin-top: 0; font-size: 18px; font-weight: 700; margin-bottom: 16px;">My Active Programs & Subscriptions</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
            <?php foreach ($my_member_subs as $ms) : ?>
                <div style="background: #f8f9fa; border: 1px solid var(--sp-border-color); border-radius: var(--sp-radius); padding: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <div>
                            <strong style="font-size: 15px; color: #000; display: block;"><?php echo esc_html($ms['plan_name']); ?></strong>
                            <code style="font-size: 11px; background: #fff; padding: 2px 6px; border-radius: 4px; border: 1px solid #e5e7eb;">#<?php echo esc_html($ms['invoice_number'] ? $ms['invoice_number'] : 'INV-OLD'); ?></code>
                        </div>
                        <span class="sp-badge <?php echo $ms['status'] === 'active' ? 'sp-badge-active' : 'sp-badge-inactive'; ?>">
                            <?php echo esc_html(ucfirst($ms['status'])); ?>
                        </span>
                    </div>

                    <div style="font-size: 12px; color: var(--sp-text-muted); margin-bottom: 8px;">
                        <div>Coach: <?php echo esc_html($ms['coach_name']); ?></div>
                        <div>Branch: <?php echo esc_html($ms['branch_name']); ?></div>
                        <div>Period: <?php echo esc_html($ms['start_date'] . ' to ' . $ms['end_date']); ?></div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed var(--sp-border-color); padding-top: 8px; font-size: 12px;">
                        <span>Sessions Used:</span>
                        <strong style="color: #166534;"><?php echo esc_html(intval($ms['sessions_used'])); ?> / <?php echo esc_html(intval($ms['sessions_count'])); ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- System Status & Operations -->
<div class="sp-card">
    <h3 style="margin-top: 0; font-size: 18px; font-weight: 700; margin-bottom: 8px;">System Status & Operational Scope</h3>
    <p style="color: var(--sp-text-muted); font-size: 13.5px; margin: 0; line-height: 1.6;">
        The Sportedia online management system is active and operational. Use the interactive left sidebar navigation menu to manage system users, branch locations, training programs, customer subscriptions, daily attendance logs, and financial reports.
    </p>
</div>
