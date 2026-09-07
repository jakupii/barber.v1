<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');
header('Vary: Cookie');

/**
 * Return upcoming appointments first (oldest first), followed by recent
 * appointments (newest first). Older history is intentionally omitted.
 */
function client_bookings_for_phone(string $phone): array
{
    [$phoneCondition, $phoneValues] = phone_lookup_condition('bk.phone', $phone);
    $statement = db()->prepare(
        "SELECT bk.id, bk.status, bk.decision_note, bk.appointment_date,
                bk.appointment_time, bk.service_name_snapshot, bk.price_cents_snapshot,
                bk.updated_at, b.name AS barber_name,
                s.name_sq AS service_name_sq, s.name_mk AS service_name_mk,
                s.name_en AS service_name_en
         FROM bookings bk
         JOIN barbers b ON b.id = bk.barber_id
         JOIN services s ON s.id = bk.service_id
         WHERE {$phoneCondition} AND bk.appointment_date >= ?"
    );
    $statement->execute([...$phoneValues, booking_history_cutoff()]);

    return sort_client_bookings($statement->fetchAll());
}

if (($_GET['clear'] ?? '') === '1') {
    unset(
        $_SESSION['client_lookup_phone'],
        $_SESSION['client_lookup_verified_at']
    );
    session_regenerate_id(true);
    redirect('/client/');
}

$bookings = [];
$error = null;
$errorKey = null;
$phoneInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $phoneInput = normalize_phone((string) ($_POST['phone'] ?? ''));

    if (rate_limit_exceeded('client_lookup_ip', request_ip(), 25, 600)) {
        header('Retry-After: 600');
        http_response_code(429);
        $error = 'Shumë kërkesa. Prit 10 minuta dhe provo përsëri.';
        $errorKey = 'client.too_many';
    } else {
        record_rate_limit_event('client_lookup_ip', request_ip());
    }

    if ($error === null && valid_phone($phoneInput)) {
        $bookings = client_bookings_for_phone($phoneInput);
    }

    if ($error === null && $bookings === []) {
        unset($_SESSION['client_lookup_phone'], $_SESSION['client_lookup_verified_at']);
        $error = 'Nuk u gjet asnjë rezervim i fundit me këtë numër telefoni.';
        $errorKey = 'client.not_found';
    } elseif ($bookings !== []) {
        session_regenerate_id(true);
        $_SESSION['client_lookup_phone'] = $phoneInput;
        $_SESSION['client_lookup_verified_at'] = time();
        redirect('/client/');
    }
} else {
    $sessionPhone = normalize_phone((string) ($_SESSION['client_lookup_phone'] ?? ''));
    $verifiedAt = (int) ($_SESSION['client_lookup_verified_at'] ?? 0);
    if (valid_phone($sessionPhone) && $verifiedAt >= time() - 3600) {
        $bookings = client_bookings_for_phone($sessionPhone);
        if ($bookings === []) {
            unset($_SESSION['client_lookup_phone'], $_SESSION['client_lookup_verified_at']);
        }
    } else {
        unset($_SESSION['client_lookup_phone'], $_SESSION['client_lookup_verified_at']);
    }
}
?>
<!doctype html>
<html lang="sq">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#f7f3ea">
    <title>Rezervimet e mia — GentlemanBarber</title>
    <link rel="icon" type="image/png" href="<?= e(asset_url('/assets/images/favicon-v2.png')) ?>">
    <script src="<?= e(asset_url('/assets/js/theme.js')) ?>"></script>
    <link rel="stylesheet" href="<?= e(asset_url('/assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('/assets/css/client.css')) ?>">
    <script src="<?= e(asset_url('/assets/js/app.js')) ?>" defer></script>
</head>
<body class="client-page" data-base-path="<?= e((string) config('base_path', '')) ?>">
    <header class="client-header">
        <div class="container client-header-inner">
            <a class="brand" href="<?= e(url('/')) ?>"><span class="brand-logo" aria-hidden="true"><img class="brand-logo-light" src="<?= e(asset_url('/assets/images/gentlemanbarber-logo-light.png')) ?>" width="68" height="68" alt=""><img class="brand-logo-dark" src="<?= e(asset_url('/assets/images/gentlemanbarber-logo-dark.png')) ?>" width="68" height="68" alt=""></span><span class="sr-only">GentlemanBarber</span></a>
            <div class="client-header-actions">
                <button class="theme-toggle" type="button" aria-label="Aktivizo dark mode" aria-pressed="false"><span class="theme-sun" aria-hidden="true">☼</span><span class="theme-moon" aria-hidden="true">●</span></button>
                <div class="language-switcher" role="group" aria-label="Gjuha" data-i18n-aria-label="common.language">
                <button type="button" class="language-button is-active" data-language="sq" aria-pressed="true">SQ</button><span>·</span>
                <button type="button" class="language-button" data-language="mk" aria-pressed="false">MK</button><span>·</span>
                <button type="button" class="language-button" data-language="en" aria-pressed="false">EN</button>
                </div>
            </div>
        </div>
    </header>

    <main class="client-main">
        <div class="client-orb client-orb-one"></div><div class="client-orb client-orb-two"></div>
        <div class="container client-layout">
            <section class="client-intro">
                <a class="back-link" href="<?= e(url('/')) ?>">← <span data-i18n="client.back">Kthehu te faqja</span></a>
                <p class="section-kicker" data-i18n="client.eyebrow">Rezervimet e tua</p>
                <h1 data-i18n="client.title">Kontrollo statusin</h1>
                <p data-i18n="client.lead">Shkruaj numrin që përdore gjatë rezervimit.</p>
                <div class="privacy-note"><span aria-hidden="true">✓</span><p data-i18n="client.privacy">Numri përdoret vetëm për të gjetur rezervimet e tua dhe nuk shfaqet publikisht.</p></div>
            </section>

            <section class="lookup-card" aria-live="polite">
                <?php if ($bookings !== []): ?>
                    <div class="booking-results" data-client-bookings data-client-status-url="<?= e(url('/api/client-status.php')) ?>">
                        <?php foreach ($bookings as $index => $booking): ?>
                            <article class="booking-result" data-booking-id="<?= (int) $booking['id'] ?>">
                                <div class="result-topline"><span class="status-badge <?= e(status_class($booking['status'])) ?>" data-status-badge><span class="status-dot"></span><span data-status-value="<?= e($booking['status']) ?>"><?= e(status_label($booking['status'])) ?></span></span></div>
                                <div class="result-person"><span class="result-avatar"><?= e(mb_strtoupper(mb_substr($booking['barber_name'], 0, 1))) ?></span><div><small data-i18n="client.barber">Berberi</small><h2><?= e($booking['barber_name']) ?></h2></div></div>
                                <dl class="booking-details">
                                    <div><dt data-i18n="client.service">Shërbimi</dt><dd data-client-service data-service-sq="<?= e($booking['service_name_sq']) ?>" data-service-mk="<?= e($booking['service_name_mk']) ?>" data-service-en="<?= e($booking['service_name_en']) ?>" data-service-fallback="<?= e($booking['service_name_snapshot']) ?>"><?= e($booking['service_name_sq']) ?></dd></div>
                                    <div><dt data-i18n="client.date">Data</dt><dd><?= e((new DateTimeImmutable($booking['appointment_date']))->format('d.m.Y')) ?></dd></div>
                                    <div><dt data-i18n="client.time">Ora</dt><dd><?= e(substr((string) $booking['appointment_time'], 0, 5)) ?></dd></div>
                                    <div><dt data-i18n="client.price">Çmimi</dt><dd><?= e(format_price((int) $booking['price_cents_snapshot'])) ?></dd></div>
                                </dl>
                                <div class="status-message <?= e(status_class($booking['status'])) ?>" data-status-message>
                                    <span aria-hidden="true" data-status-icon><?= $booking['status'] === 'accepted' ? '✓' : ($booking['status'] === 'pending' ? '…' : '!') ?></span>
                                    <p data-status-description="<?= e($booking['status']) ?>"><?= e(match ($booking['status']) {
                                        'accepted' => 'Rezervimi është pranuar. Të presim!',
                                        'rejected' => 'Rezervimi nuk u pranua. Zgjidh një termin të ri.',
                                        'cancelled' => 'Rezervimi është anuluar.',
                                        default => 'Rezervimi është ende në pritje të konfirmimit.',
                                    }) ?></p>
                                </div>
                                <?php if ($booking['decision_note'] !== ''): ?><p class="decision-note"><strong data-i18n="client.note">Shënim:</strong> <?= e($booking['decision_note']) ?></p><?php endif; ?>
                            </article>
                            <?php if ($index < count($bookings) - 1): ?><hr class="booking-result-divider" aria-hidden="true"><?php endif; ?>
                        <?php endforeach; ?>
                        <a class="button button-dark button-large button-block" href="<?= e(url('/#barbers')) ?>" data-i18n="client.new_booking">Bëj një rezervim të ri</a>
                        <a class="button button-light button-large button-block" href="<?= e(url('/client/?clear=1')) ?>" data-i18n="client.other_phone">Përdor numër tjetër</a>
                    </div>
                <?php else: ?>
                    <div class="lookup-card-heading"><span>GB</span><div><h2 data-i18n="client.title">Kontrollo statusin</h2><p data-i18n="lookup.lead">Shkruaj numrin e telefonit për të parë statusin.</p></div></div>
                    <?php if ($error): ?><div class="lookup-error" role="alert"><span>!</span><p<?= $errorKey ? ' data-i18n="' . e($errorKey) . '"' : '' ?>><?= e($error) ?></p></div><?php endif; ?>
                    <form method="post" class="lookup-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <label class="field"><span data-i18n="client.phone">Numri i telefonit</span><input type="tel" name="phone" value="<?= e($phoneInput) ?>" placeholder="+389 70 000 000" autocomplete="tel" inputmode="tel" maxlength="30" required></label>
                        <button class="button button-dark button-large button-block" type="submit"><span data-i18n="client.submit">Kontrollo statusin</span><span aria-hidden="true">↗</span></button>
                    </form>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>
</html>
