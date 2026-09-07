<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login(['admin', 'barber']);
$isAdmin = $user['role'] === 'admin';
$allowedTabs = $isAdmin ? ['overview', 'bookings', 'schedule', 'services', 'barbers', 'account'] : ['bookings', 'account'];
$tab = in_array($_GET['tab'] ?? '', $allowedTabs, true) ? (string) $_GET['tab'] : ($isAdmin ? 'overview' : 'bookings');
$statusFilter = in_array($_GET['status'] ?? '', ['pending', 'accepted', 'rejected', 'cancelled'], true) ? (string) $_GET['status'] : '';
$flash = pull_flash();

$baseWhere = [];
$baseParams = [];
if (!$isAdmin) {
    $visibleDates = booking_dates();
    $baseWhere[] = 'bk.barber_id = :barber_id';
    $baseWhere[] = 'bk.appointment_date BETWEEN :window_start AND :window_end';
    $baseParams['barber_id'] = (int) $user['barber_id'];
    $baseParams['window_start'] = $visibleDates[0];
    $baseParams['window_end'] = $visibleDates[count($visibleDates) - 1];
}

$bookingWhere = $baseWhere;
$bookingParams = $baseParams;
if ($statusFilter !== '') {
    $bookingWhere[] = 'bk.status = :status';
    $bookingParams['status'] = $statusFilter;
}
$bookingSql = 'SELECT bk.*, b.name AS barber_name FROM bookings bk JOIN barbers b ON b.id = bk.barber_id';
if ($bookingWhere !== []) {
    $bookingSql .= ' WHERE ' . implode(' AND ', $bookingWhere);
}
$bookingSql .= ' ORDER BY CASE WHEN bk.appointment_date >= CURRENT_DATE THEN 0 ELSE 1 END, bk.appointment_date ASC, bk.appointment_time ASC LIMIT 150';
$bookingStatement = db()->prepare($bookingSql);
$bookingStatement->execute($bookingParams);
$bookings = $bookingStatement->fetchAll();

// Counted in SQL rather than by fetching every booking row into PHP: the
// dashboard used to load the whole table on each page view just to size four
// tiles.
$statsSql = "SELECT COUNT(*) AS total,
        SUM(CASE WHEN bk.status = 'pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN bk.status = 'accepted' THEN 1 ELSE 0 END) AS accepted,
        SUM(CASE WHEN bk.appointment_date = :today AND bk.status IN ('pending', 'accepted') THEN 1 ELSE 0 END) AS today
     FROM bookings bk JOIN barbers b ON b.id = bk.barber_id";
if ($baseWhere !== []) {
    $statsSql .= ' WHERE ' . implode(' AND ', $baseWhere);
}
$statsStatement = db()->prepare($statsSql);
$statsStatement->execute($baseParams + ['today' => date('Y-m-d')]);
$statsRow = $statsStatement->fetch() ?: [];
$stats = [
    'today' => (int) ($statsRow['today'] ?? 0),
    'pending' => (int) ($statsRow['pending'] ?? 0),
    'accepted' => (int) ($statsRow['accepted'] ?? 0),
    'total' => (int) ($statsRow['total'] ?? 0),
];

$services = [];
$barbers = [];
if ($isAdmin) {
    $services = db()->query('SELECT * FROM services ORDER BY display_order, id')->fetchAll();
    $barbers = db()->query(
        "SELECT b.*, u.username, u.id AS user_id
         FROM barbers b LEFT JOIN users u ON u.barber_id = b.id AND u.role = 'barber'
         ORDER BY b.display_order, b.id"
    )->fetchAll();
}

$title = match ($tab) {
    'bookings' => $isAdmin ? 'Rezervimet' : 'Rezervimet e mia',
    'services' => 'Shërbimet & çmimet',
    'barbers' => 'Menaxho berberët',
    'schedule' => 'Kalendari & oraret',
    'account' => 'Llogaria ime',
    default => 'Përmbledhje',
};

function nav_active(string $name, string $current): string
{
    return $name === $current ? 'is-active' : '';
}
?>
<!doctype html>
<html lang="sq">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="theme-color" content="#f2f8fd" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#05080c" media="(prefers-color-scheme: dark)">
    <title><?= e($title) ?> — GentlemanBarber Panel</title>
    <link rel="icon" type="image/png" href="<?= e(asset_url('/assets/images/favicon-v2.png')) ?>">
    <script src="<?= e(asset_url('/assets/js/theme.js')) ?>"></script>
    <link rel="stylesheet" href="<?= e(asset_url('/assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('/assets/css/admin.css')) ?>">
    <script src="<?= e(asset_url('/assets/js/admin.js')) ?>" defer></script>
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar" id="dashboard-sidebar">
            <a class="brand brand-on-dark" href="<?= e(url('/barber/dashboard.php')) ?>"><span class="brand-logo" aria-hidden="true"><img class="brand-logo-light" src="<?= e(asset_url('/assets/images/gentlemanbarber-logo-light.png')) ?>" width="68" height="68" alt=""><img class="brand-logo-dark" src="<?= e(asset_url('/assets/images/gentlemanbarber-logo-dark.png')) ?>" width="68" height="68" alt=""></span><span class="sr-only">GentlemanBarber</span></a>
            <div class="staff-card">
                <span class="staff-avatar">
                    <?php if (!$isAdmin && $user['barber_image']): ?><img src="<?= e(asset_url($user['barber_image'])) ?>" alt=""><?php else: ?><?= e(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) ?><?php endif; ?>
                </span>
                <span><strong><?= e($user['full_name']) ?></strong><small><?= $isAdmin ? 'Administrator' : 'Berber' ?></small></span>
            </div>
            <nav class="dashboard-nav" aria-label="Paneli">
                <?php if ($isAdmin): ?>
                    <a class="<?= nav_active('overview', $tab) ?>" href="<?= e(url('/barber/dashboard.php?tab=overview')) ?>"><span class="nav-icon">⌂</span>Përmbledhje</a>
                <?php endif; ?>
                <a class="<?= nav_active('bookings', $tab) ?>" href="<?= e(url('/barber/dashboard.php?tab=bookings')) ?>"><span class="nav-icon">◷</span><?= $isAdmin ? 'Rezervimet' : 'Rezervimet e mia' ?><?php if ($stats['pending']): ?><small><?= $stats['pending'] ?></small><?php endif; ?></a>
                <?php if ($isAdmin): ?>
                    <a class="<?= nav_active('services', $tab) ?>" href="<?= e(url('/barber/dashboard.php?tab=services')) ?>"><span class="nav-icon">€</span>Shërbimet & çmimet</a>
                    <a class="<?= nav_active('schedule', $tab) ?>" href="<?= e(url('/barber/dashboard.php?tab=schedule')) ?>"><span class="nav-icon">▦</span>Kalendari & oraret</a>
                    <a class="<?= nav_active('barbers', $tab) ?>" href="<?= e(url('/barber/dashboard.php?tab=barbers')) ?>"><span class="nav-icon">✦</span>Berberët</a>
                <?php endif; ?>
                <a class="<?= nav_active('account', $tab) ?>" href="<?= e(url('/barber/dashboard.php?tab=account')) ?>"><span class="nav-icon">⚙</span>Llogaria ime</a>
            </nav>
            <div class="sidebar-bottom">
                <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><span>↗</span>Shiko faqen</a>
                <form method="post" action="<?= e(url('/barber/logout.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="logout-button" type="submit"><span>←</span>Dil nga paneli</button></form>
            </div>
        </aside>

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <button class="mobile-sidebar-toggle" type="button" aria-controls="dashboard-sidebar" aria-expanded="false">☰</button>
                <div class="dashboard-title"><p><?= $isAdmin ? 'Paneli i administratorit' : 'Paneli i berberit' ?></p><h1><?= e($title) ?></h1></div>
                <button class="theme-toggle" type="button" aria-label="Aktivizo dark mode" aria-pressed="false"><span class="theme-sun" aria-hidden="true">☼</span><span class="theme-moon" aria-hidden="true">●</span></button>
                <span class="dashboard-date"><?= e((new DateTimeImmutable())->format('d.m.Y')) ?></span>
            </header>

            <?php if ($flash): ?><div class="admin-alert <?= $flash['type'] === 'success' ? 'alert-success' : 'alert-error' ?>" role="alert"><span><?= $flash['type'] === 'success' ? '✓' : '!' ?></span><?= e($flash['message']) ?></div><?php endif; ?>

            <?php if (in_array($tab, ['overview', 'bookings'], true)): ?>
                <section class="stats-grid" aria-label="Statistikat">
                    <article class="stat-card"><small>Sot</small><strong><?= $stats['today'] ?></strong><span class="stat-hint">termine aktive</span></article>
                    <article class="stat-card"><small>Në pritje</small><strong><?= $stats['pending'] ?></strong><span class="stat-hint">kërkojnë vendim</span></article>
                    <article class="stat-card"><small>Të pranuara</small><strong><?= $stats['accepted'] ?></strong><span class="stat-hint">të konfirmuara</span></article>
                    <article class="stat-card"><small>Gjithsej</small><strong><?= $stats['total'] ?></strong><span class="stat-hint">rezervime</span></article>
                </section>

                <section class="dashboard-card">
                    <header class="dashboard-card-header">
                        <div><h2><?= $tab === 'overview' ? 'Terminet e ardhshme' : ($isAdmin ? 'Të gjitha rezervimet' : 'Terminet për 3 ditët') ?></h2><p>Berberi pranon ose refuzon çdo kërkesë nga këtu.</p></div>
                        <form class="filter-form" method="get"><input type="hidden" name="tab" value="bookings"><select name="status" aria-label="Filtro sipas statusit" data-auto-submit><option value="">Të gjitha statuset</option><option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Në pritje</option><option value="accepted" <?= $statusFilter === 'accepted' ? 'selected' : '' ?>>Të pranuara</option><option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Të refuzuara</option><option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Të anuluara</option></select><noscript><button class="action-button" type="submit">Filtro</button></noscript></form>
                    </header>
                    <?php if ($bookings): ?>
                        <div class="table-scroll"><table class="booking-table"><thead><tr><th>Klienti</th><th>Data & ora</th><?php if ($isAdmin): ?><th>Berberi</th><?php endif; ?><th>Shërbimi</th><th>Statusi</th><th>Veprime</th></tr></thead><tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td data-label="Klienti" class="client-cell"><span><strong><?= e($booking['customer_name']) ?></strong><small><?= e($booking['phone']) ?> · <?= e($booking['public_code']) ?></small></span></td>
                                <td data-label="Data & ora" class="time-cell"><span><strong><?= e(substr((string) $booking['appointment_time'], 0, 5)) ?></strong><small><?= e((new DateTimeImmutable($booking['appointment_date']))->format('d.m.Y')) ?></small></span></td>
                                <?php if ($isAdmin): ?><td data-label="Berberi"><span class="staff-pill"><span><?= e(mb_strtoupper(mb_substr($booking['barber_name'], 0, 1))) ?></span><?= e($booking['barber_name']) ?></span></td><?php endif; ?>
                                <td data-label="Shërbimi" class="booking-main"><span><strong><?= e($booking['service_name_snapshot']) ?></strong><small><?= e(format_price((int) $booking['price_cents_snapshot'])) ?><?php if ($booking['notes']): ?> · <?= e(mb_strimwidth($booking['notes'], 0, 42, '…')) ?><?php endif; ?></small></span></td>
                                <td data-label="Statusi"><span class="status-badge <?= e(status_class($booking['status'])) ?>"><span class="status-dot"></span><?= e(status_label($booking['status'])) ?></span></td>
                                <td data-label="Veprime"><div class="booking-actions">
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <form method="post" action="<?= e(url('/barber/actions/booking_status.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>"><input type="hidden" name="status" value="accepted"><input type="hidden" name="return_tab" value="<?= e($tab) ?>"><button class="action-button action-accept" type="submit">✓ Prano</button></form>
                                        <form method="post" action="<?= e(url('/barber/actions/booking_status.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>"><input type="hidden" name="status" value="rejected"><input type="hidden" name="return_tab" value="<?= e($tab) ?>"><button class="action-button action-reject" type="submit" data-confirm="Ta refuzoj këtë rezervim?">× Refuzo</button></form>
                                    <?php elseif ($isAdmin && $booking['status'] === 'accepted'): ?>
                                        <form method="post" action="<?= e(url('/barber/actions/booking_status.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>"><input type="hidden" name="status" value="cancelled"><input type="hidden" name="return_tab" value="<?= e($tab) ?>"><button class="action-button action-reject" type="submit" data-confirm="Ta anuloj këtë rezervim?">Anulo</button></form>
                                    <?php else: ?><span>—</span><?php endif; ?>
                                </div></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody></table></div>
                    <?php else: ?><div class="empty-state"><span>◷</span><h3>Nuk ka rezervime</h3><p>Rezervimet e reja do të shfaqen automatikisht këtu.</p></div><?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if ($isAdmin && $tab === 'services'): ?>
                <section class="dashboard-card">
                    <header class="dashboard-card-header"><div><h2>Çmimorja</h2><p>Ndrysho emrat në 3 gjuhë, çmimin dhe kohëzgjatjen.</p></div></header>
                    <div class="editor-grid">
                        <details class="editor-card" open><summary><div><h3>+ Shto shërbim të ri</h3><p>Krijo një rresht të ri në çmimore.</p></div></summary>
                            <?php $service = ['id' => 0, 'name_sq' => '', 'name_mk' => '', 'name_en' => '', 'description_sq' => '', 'description_mk' => '', 'description_en' => '', 'price_cents' => 0, 'duration_minutes' => 60, 'display_order' => count($services) + 1, 'active' => 1]; include __DIR__ . '/partials/service_form.php'; ?>
                        </details>
                        <?php foreach ($services as $service): ?><details class="editor-card"><summary><div><h3><?= e($service['name_sq']) ?></h3><p><?= e(format_price((int) $service['price_cents'])) ?> · <?= (int) $service['duration_minutes'] ?> min · <?= $service['active'] ? 'Aktiv' : 'Joaktiv' ?></p></div></summary><?php include __DIR__ . '/partials/service_form.php'; ?></details><?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($isAdmin && $tab === 'barbers'): ?>
                <section class="dashboard-card">
                    <header class="dashboard-card-header"><div><h2>Ekipi</h2><p>Shto berber, fotografi, biografi dhe hyrje personale në panel.</p></div></header>
                    <div class="editor-grid">
                        <?php $barber = ['id' => 0, 'name' => '', 'title_sq' => '', 'title_mk' => '', 'title_en' => '', 'bio_sq' => '', 'bio_mk' => '', 'bio_en' => '', 'phone' => '', 'email' => '', 'experience_years' => 0, 'image_path' => '', 'display_order' => count($barbers) + 1, 'active' => 1, 'username' => '']; ?>
                        <details class="editor-card barber-editor" open><summary><div class="barber-summary"><span class="barber-placeholder">+</span><span><h3>Shto berber të ri</h3><p>Krijo profilin dhe llogarinë e hyrjes.</p></span></div></summary><?php include __DIR__ . '/partials/barber_form.php'; ?></details>
                        <?php foreach ($barbers as $barber): ?><details class="editor-card barber-editor"><summary><div class="barber-summary"><?php if ($barber['image_path']): ?><img src="<?= e(asset_url($barber['image_path'])) ?>" alt=""><?php else: ?><span class="barber-placeholder"><?= e(mb_strtoupper(mb_substr($barber['name'], 0, 1))) ?></span><?php endif; ?><span><h3><?= e($barber['name']) ?></h3><p>@<?= e($barber['username'] ?: 'pa-llogari') ?> · <?= $barber['active'] ? 'Aktiv' : 'Joaktiv' ?></p></span></div></summary><?php include __DIR__ . '/partials/barber_form.php'; ?></details><?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($isAdmin && $tab === 'schedule'): ?>
                <?php
                $requestedBarber = filter_input(INPUT_GET, 'barber_id', FILTER_VALIDATE_INT) ?: (int) ($barbers[0]['id'] ?? 0);
                $selectedScheduleBarber = in_array($requestedBarber, array_map(static fn (array $item): int => (int) $item['id'], $barbers), true) ? $requestedBarber : (int) ($barbers[0]['id'] ?? 0);
                ?>
                <section class="dashboard-card schedule-card" id="schedule-manager"
                         data-endpoint="<?= e(url('/barber/api/schedule.php')) ?>"
                         data-dates="<?= e(implode(',', booking_dates())) ?>"
                         data-csrf="<?= e(csrf_token()) ?>">
                    <header class="dashboard-card-header"><div><h2>Menaxho disponueshmërinë</h2><p>Kontrollo vetëm 3 ditët e hapura për rezervim. Rezervimet ekzistuese nuk ndryshohen.</p></div></header>
                    <?php if ($barbers): ?>
                        <div class="schedule-toolbar">
                            <label class="field"><span>Berberi</span><select id="schedule-barber"><?php foreach ($barbers as $item): ?><option value="<?= (int) $item['id'] ?>" <?= (int) $item['id'] === $selectedScheduleBarber ? 'selected' : '' ?>><?= e($item['name']) ?><?= $item['active'] ? '' : ' · joaktiv' ?></option><?php endforeach; ?></select></label>
                            <div class="schedule-window-note"><strong>3 ditët e ardhshme</strong><span>09:00–22:00 · çdo 30 minuta</span></div>
                        </div>
                        <div class="schedule-legend"><span><i class="legend-free"></i>E lirë</span><span><i class="legend-blocked"></i>E bllokuar</span><span><i class="legend-booked"></i>E rezervuar</span></div>
                        <div class="schedule-feedback" id="schedule-feedback" role="status" aria-live="polite"></div>
                        <div class="schedule-calendar" id="schedule-calendar" aria-live="polite"><p class="schedule-loading">Po ngarkohet kalendari…</p></div>
                    <?php else: ?><div class="empty-state"><span>▦</span><h3>Shto fillimisht një berber</h3><p>Kalendari aktivizohet pasi të krijohet profili i parë.</p></div><?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if ($tab === 'account'): ?>
                <section class="dashboard-card account-card">
                    <header class="dashboard-card-header"><div><h2>Ndrysho fjalëkalimin</h2><p>Përdor së paku 10 karaktere.</p></div></header>
                    <div class="account-card-body"><form class="editor-form" method="post" action="<?= e(url('/barber/actions/change_password.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label class="field"><span>Fjalëkalimi aktual</span><input type="password" name="current_password" autocomplete="current-password" required></label><label class="field"><span>Fjalëkalimi i ri</span><input type="password" name="new_password" autocomplete="new-password" minlength="10" required></label><label class="field"><span>Përsërite fjalëkalimin</span><input type="password" name="confirm_password" autocomplete="new-password" minlength="10" required></label><button class="button button-dark" type="submit">Ruaj fjalëkalimin</button></form></div>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
