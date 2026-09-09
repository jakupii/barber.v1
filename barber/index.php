<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (current_user()) {
    redirect('/barber/dashboard.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    if (login_rate_limited($username)) {
        header('Retry-After: 600');
        http_response_code(429);
        $error = 'Shumë tentativa. Prit 10 minuta dhe provo përsëri.';
    } elseif (attempt_login($username, (string) ($_POST['password'] ?? ''))) {
        redirect('/barber/dashboard.php');
    } else {
        $error = 'Emri i përdoruesit ose fjalëkalimi nuk është i saktë.';
    }
}
?>
<!doctype html>
<html lang="sq">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0b0c0e" media="(prefers-color-scheme: dark)">
    <title>Hyrje në panel — GentlemanBarber</title>
    <link rel="icon" type="image/png" href="<?= e(asset_url('/assets/images/favicon-v2.png')) ?>">
    <script src="<?= e(asset_url('/assets/js/theme.js')) ?>"></script>
    <link rel="stylesheet" href="<?= e(asset_url('/assets/css/fonts.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('/assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('/assets/css/admin.css')) ?>">
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-visual" aria-label="GentlemanBarber">
            <a class="brand brand-on-dark" href="<?= e(url('/')) ?>"><span class="brand-logo" aria-hidden="true"><img class="brand-logo-light" src="<?= e(asset_url('/assets/images/gentlemanbarber-logo-light.png')) ?>" width="68" height="68" alt=""><img class="brand-logo-dark" src="<?= e(asset_url('/assets/images/gentlemanbarber-logo-dark.png')) ?>" width="68" height="68" alt=""></span><span class="sr-only">GentlemanBarber</span></a>
            <div class="login-visual-copy"><p class="login-kicker">Staff only · /barber</p><h1>Çdo termin.<br>Nën kontroll.</h1><p>Panel i sigurt për menaxhimin e rezervimeve, ekipit dhe çmimeve.</p></div>
            <div class="login-pattern" aria-hidden="true"><span>G</span><span>B</span></div>
        </section>

        <section class="login-panel">
            <button class="theme-toggle login-theme-toggle" type="button" aria-label="Aktivizo dark mode" aria-pressed="false"><span class="theme-sun" aria-hidden="true">☼</span><span class="theme-moon" aria-hidden="true">●</span></button>
            <div class="login-form-wrap">
                <a class="back-link" href="<?= e(url('/')) ?>">← Kthehu te faqja</a>
                <p class="section-kicker">GentlemanBarber Panel</p>
                <h2>Mirë se u ktheve.</h2>
                <p class="login-lead">Hyr si administrator ose berber për të vazhduar.</p>
                <?php if ($error): ?><div class="admin-alert alert-error" role="alert"><span>!</span><?= e($error) ?></div><?php endif; ?>
                <form method="post" class="login-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <label class="field"><span>Emri i përdoruesit</span><input type="text" name="username" autocomplete="username" maxlength="80" autofocus required></label>
                    <label class="field"><span>Fjalëkalimi</span><input type="password" name="password" autocomplete="current-password" required></label>
                    <button class="button button-dark button-large button-block" type="submit"><span>Hyr në panel</span><span aria-hidden="true">↗</span></button>
                </form>
                <p class="login-security"><span aria-hidden="true">✓</span> Hyrje e mbrojtur me session dhe fjalëkalim të hash-uar.</p>
            </div>
        </section>
    </main>
</body>
</html>
