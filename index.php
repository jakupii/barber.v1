<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$barbers = db()->query('SELECT * FROM barbers WHERE active = 1 ORDER BY display_order, id')->fetchAll();
$services = db()->query('SELECT * FROM services WHERE active = 1 ORDER BY display_order, id')->fetchAll();
$dates = booking_dates();
$firstBarber = $barbers[0] ?? null;
?>
<!doctype html>
<html lang="sq">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0b0c0e" media="(prefers-color-scheme: dark)">
    <meta name="description" content="GentlemanBarber — zgjidh berberin, shërbimin dhe orën tënde online.">
    <title>GentlemanBarber — Stil i pastër. Rezervim i thjeshtë.</title>
    <link rel="icon" type="image/png" href="<?= e(asset_url('/assets/images/favicon-v2.png')) ?>">
    <script src="<?= e(asset_url('/assets/js/theme.js')) ?>"></script>
    <link rel="stylesheet" href="<?= e(asset_url('/assets/css/fonts.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('/assets/css/style.css')) ?>">
    <script src="<?= e(asset_url('/assets/js/app.js')) ?>" defer></script>
</head>
<body data-base-path="<?= e((string) config('base_path', '')) ?>">
    <a class="skip-link" href="#main" data-i18n="common.skip">Kalo te përmbajtja</a>

    <header class="site-header" id="home">
        <div class="container header-inner">
            <a class="brand" href="#home" aria-label="GentlemanBarber - Kreu">
                <span class="brand-logo" aria-hidden="true">
                    <img class="brand-logo-light" src="<?= e(asset_url('/assets/images/gentlemanbarber-logo-light.png')) ?>" width="68" height="68" alt="">
                    <img class="brand-logo-dark" src="<?= e(asset_url('/assets/images/gentlemanbarber-logo-dark.png')) ?>" width="68" height="68" alt="">
                </span>
                <span class="sr-only">GentlemanBarber</span>
            </a>

            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-nav">
                <span class="sr-only" data-i18n="nav.menu">Hap menunë</span>
                <span></span><span></span><span></span>
            </button>

            <nav class="main-nav" id="main-nav" aria-label="Navigimi kryesor" data-i18n-aria-label="nav.aria">
                <a href="#services" data-i18n="nav.services">Shërbimet</a>
                <a href="#barbers" data-i18n="nav.barbers">Berberët</a>
                <a href="<?= e(url('/client/')) ?>" data-i18n="nav.my_booking">Rezervimi im</a>
            </nav>

            <div class="header-actions">
                <button class="theme-toggle" type="button" aria-label="Aktivizo dark mode" aria-pressed="false"><span class="theme-sun" aria-hidden="true">☼</span><span class="theme-moon" aria-hidden="true">●</span></button>
                <div class="language-switcher" role="group" aria-label="Gjuha" data-i18n-aria-label="common.language">
                    <button type="button" class="language-button is-active" data-language="sq" aria-pressed="true">SQ</button>
                    <span aria-hidden="true">·</span>
                    <button type="button" class="language-button" data-language="mk" aria-pressed="false">MK</button>
                    <span aria-hidden="true">·</span>
                    <button type="button" class="language-button" data-language="en" aria-pressed="false">EN</button>
                </div>
                <?php if ($firstBarber): ?>
                    <button class="button button-dark header-book" type="button" data-book-barber="<?= (int) $firstBarber['id'] ?>" data-barber-name="<?= e($firstBarber['name']) ?>" data-i18n="nav.book">Rezervo tani</button>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main id="main">
        <section class="hero section-shell" aria-labelledby="hero-title">
            <div class="container hero-grid">
                <div class="hero-copy">
                    <p class="eyebrow"><span class="eyebrow-dot"></span><span data-i18n="hero.eyebrow">GentlemanBarber</span></p>
                    <h1 id="hero-title" data-i18n="hero.title">Stil i pastër. Rezervim i thjeshtë.</h1>
                    <p class="hero-lead" data-i18n="hero.lead">Zgjidh berberin, shërbimin dhe orën. Ne kujdesemi për pjesën tjetër.</p>
                    <div class="hero-actions">
                        <?php if ($firstBarber): ?>
                            <button class="button button-dark button-large" type="button" data-book-barber="<?= (int) $firstBarber['id'] ?>" data-barber-name="<?= e($firstBarber['name']) ?>">
                                <span data-i18n="hero.primary">Rezervoni termin</span><span aria-hidden="true">↗</span>
                            </button>
                        <?php endif; ?>
                        <a class="button button-ghost button-large" href="#services" data-i18n="hero.secondary">Shikoni shërbimet</a>
                    </div>
                </div>

                <div class="hero-visual" aria-label="GentlemanBarber studio">
                    <div class="hero-image-wrap">
                        <img src="<?= e(asset_url('/assets/images/hero-barbershop-v2.jpg')) ?>" width="1400" height="875" alt="Ambient modern i një barbershop-i" data-i18n-alt="hero.image_alt" fetchpriority="high">
                    </div>
                    <div class="availability-card">
                        <span class="calendar-icon" aria-hidden="true">03</span>
                        <span><strong data-i18n="hero.availability_short">Termine të lira</strong><small data-i18n="hero.availability">Për 3 ditët e ardhshme</small></span>
                    </div>
                    <div class="hero-monogram" aria-hidden="true">GB</div>
                </div>
            </div>
        </section>

        <section class="benefits" aria-label="Përfitimet">
            <div class="container benefit-grid">
                <article>
                    <span class="feature-number">01</span>
                    <div><h2 data-i18n="benefit.fast.title">Rezervo shpejt</h2><p data-i18n="benefit.fast.text">Tre zgjedhje dhe termini është dërguar.</p></div>
                </article>
                <article>
                    <span class="feature-number">02</span>
                    <div><h2 data-i18n="benefit.pro.title">Zgjidh berberin</h2><p data-i18n="benefit.pro.text">Rezervo direkt te profesionisti që preferon.</p></div>
                </article>
                <article>
                    <span class="feature-number">03</span>
                    <div><h2 data-i18n="benefit.wait.title">Eja në orar</h2><p data-i18n="benefit.wait.text">Ora e rezervuar ruhet vetëm për ty.</p></div>
                </article>
            </div>
        </section>

        <section class="services-section section-shell" id="services" aria-labelledby="services-title">
            <div class="container">
                <div class="section-heading">
                    <div><p class="section-kicker" data-i18n="services.kicker">Çmime të qarta</p><h2 id="services-title" data-i18n="services.title">Shërbimet dhe çmimet</h2></div>
                    <p data-i18n="services.lead">Zgjidh shërbimin dhe shiko çmimin para rezervimit.</p>
                </div>

                <div class="services-list">
                    <?php foreach ($services as $index => $service): ?>
                        <article class="service-row" data-service-card
                                 data-name-sq="<?= e($service['name_sq']) ?>" data-name-mk="<?= e($service['name_mk']) ?>" data-name-en="<?= e($service['name_en']) ?>"
                                 data-description-sq="<?= e($service['description_sq']) ?>" data-description-mk="<?= e($service['description_mk']) ?>" data-description-en="<?= e($service['description_en']) ?>">
                            <span class="service-index"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            <div class="service-copy"><h3 data-service-name><?= e($service['name_sq']) ?></h3><p data-service-description><?= e($service['description_sq']) ?></p></div>
                            <span class="service-duration"><?= (int) $service['duration_minutes'] ?> <span data-i18n="duration.minutes">min</span></span>
                            <strong class="service-price"><?= e(format_price((int) $service['price_cents'])) ?></strong>
                            <?php if ($firstBarber): ?>
                                <button class="circle-button" type="button" data-book-barber="<?= (int) $firstBarber['id'] ?>" data-barber-name="<?= e($firstBarber['name']) ?>" data-service-id="<?= (int) $service['id'] ?>" aria-label="Rezervo këtë shërbim" data-i18n-aria-label="booking.book_service">↗</button>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="barbers-section section-shell" id="barbers" aria-labelledby="barbers-title">
            <div class="container">
                <div class="section-heading">
                    <div><p class="section-kicker" data-i18n="barbers.kicker">Ekipi</p><h2 id="barbers-title" data-i18n="barbers.title">Zgjidh berberin</h2></div>
                    <p data-i18n="barbers.lead">Shiko ekipin dhe rezervo me një klik.</p>
                </div>

                <div class="barber-grid">
                    <?php foreach ($barbers as $barber): ?>
                        <article class="barber-card" data-barber-card
                                 data-title-sq="<?= e($barber['title_sq']) ?>" data-title-mk="<?= e($barber['title_mk']) ?>" data-title-en="<?= e($barber['title_en']) ?>"
                                 data-bio-sq="<?= e($barber['bio_sq']) ?>" data-bio-mk="<?= e($barber['bio_mk']) ?>" data-bio-en="<?= e($barber['bio_en']) ?>">
                            <div class="barber-photo">
                                <img src="<?= e(asset_url($barber['image_path'])) ?>" width="900" height="1100" loading="lazy" alt="Foto profili e <?= e($barber['name']) ?>" data-barber-photo-alt data-barber-name="<?= e($barber['name']) ?>">
                                <span class="experience-pill"><b><?= (int) $barber['experience_years'] ?>+</b> <span data-i18n="barber.years">vite</span></span>
                            </div>
                            <div class="barber-info">
                                <div><h3><?= e($barber['name']) ?></h3><p data-barber-title><?= e($barber['title_sq']) ?></p></div>
                                <p class="barber-bio" data-barber-bio><?= e($barber['bio_sq']) ?></p>
                                <button class="button button-blue button-block" type="button" data-book-barber="<?= (int) $barber['id'] ?>" data-barber-name="<?= e($barber['name']) ?>">
                                    <span data-i18n="barber.book">Rezervo te</span> <?= e($barber['name']) ?><span aria-hidden="true">↗</span>
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="status-cta section-shell" aria-labelledby="status-title">
            <div class="container status-cta-inner">
                <div class="status-icon" aria-hidden="true">✓</div>
                <div><p class="section-kicker" data-i18n="lookup.kicker">Ke rezervuar?</p><h2 id="status-title" data-i18n="lookup.title">Kontrollo rezervimin</h2><p data-i18n="lookup.lead">Shkruaj numrin e telefonit për të parë statusin.</p></div>
                <a class="button button-light button-large" href="<?= e(url('/client/')) ?>"><span data-i18n="lookup.submit">Kontrollo statusin</span><span aria-hidden="true">↗</span></a>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container footer-grid">
            <div><a class="brand brand-footer" href="#home"><span class="brand-logo" aria-hidden="true"><img class="brand-logo-light" src="<?= e(asset_url('/assets/images/gentlemanbarber-logo-light.png')) ?>" width="68" height="68" alt=""><img class="brand-logo-dark" src="<?= e(asset_url('/assets/images/gentlemanbarber-logo-dark.png')) ?>" width="68" height="68" alt=""></span><span class="sr-only">GentlemanBarber</span></a><p data-i18n="footer.tagline">Stil i mirë. Pa pritje.</p></div>
            <div><strong data-i18n="footer.hours">Orari</strong><p data-i18n="footer.days">Çdo ditë</p><p>09:00 – 22:00</p></div>
            <div><strong data-i18n="footer.contact">Kontakt</strong><p>+389 70 111 200</p><p>hello@gentlemanbarber.mk</p></div>
            <p class="copyright">© <?= date('Y') ?> GentlemanBarber</p>
        </div>
    </footer>

    <div class="modal" id="booking-modal" aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <section class="booking-dialog" role="dialog" aria-modal="true" aria-labelledby="booking-title" tabindex="-1">
            <div class="modal-header">
                <div><p class="section-kicker" data-i18n="booking.eyebrow">Termini yt</p><h2 id="booking-title" data-i18n="booking.title">Rezervo termin</h2><p><span id="selected-barber-prefix" data-i18n="booking.with">Te</span> <strong id="selected-barber-name"></strong></p></div>
                <button class="modal-close" type="button" data-close-modal aria-label="Mbyll" data-i18n-aria-label="common.close">×</button>
            </div>

            <div class="booking-notice"><span aria-hidden="true">i</span><p data-i18n="booking.window">Rezervimet janë të hapura vetëm për 3 ditët e ardhshme.</p></div>

            <form id="booking-form" class="booking-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="language" id="booking-language" value="sq">

                <div class="form-grid">
                    <label class="field field-full"><span data-i18n="booking.barber">Zgjidh berberin</span>
                        <select name="barber_id" id="barber-id" required>
                            <option value="" data-i18n="booking.barber_placeholder">— Zgjidh —</option>
                            <?php foreach ($barbers as $barber): ?>
                                <option value="<?= (int) $barber['id'] ?>" data-name="<?= e($barber['name']) ?>"><?= e($barber['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field"><span data-i18n="booking.name">Emri dhe mbiemri</span><input type="text" name="customer_name" autocomplete="name" maxlength="120" required></label>
                    <label class="field"><span data-i18n="booking.phone">Numri i telefonit</span><input type="tel" name="phone" autocomplete="tel" maxlength="30" placeholder="+389 70 000 000" required></label>
                    <label class="field"><span data-i18n="booking.email">Email (opsional)</span><input type="email" name="email" autocomplete="email" maxlength="190"></label>
                    <label class="field"><span data-i18n="booking.service">Zgjidh shërbimin</span>
                        <select name="service_id" id="service-select" required>
                            <option value="" data-i18n="booking.service_placeholder">— Zgjidh —</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?= (int) $service['id'] ?>" data-name-sq="<?= e($service['name_sq']) ?>" data-name-mk="<?= e($service['name_mk']) ?>" data-name-en="<?= e($service['name_en']) ?>" data-price="<?= e(format_price((int) $service['price_cents'])) ?>"><?= e($service['name_sq']) ?> · <?= e(format_price((int) $service['price_cents'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field field-full"><span data-i18n="booking.date">Zgjidh datën</span>
                        <select name="appointment_date" id="appointment-date" required>
                            <?php foreach ($dates as $date): ?><option value="<?= e($date) ?>"><?= e($date) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <fieldset class="time-fieldset">
                    <legend data-i18n="booking.time">Zgjidh orën</legend>
                    <div class="time-slots" id="time-slots" aria-live="polite"><p class="slots-message" data-i18n="booking.loading">Po kontrollohen oraret…</p></div>
                    <input type="hidden" name="appointment_time" id="appointment-time" required>
                </fieldset>

                <label class="field"><span data-i18n="booking.note">Shënim (opsional)</span><textarea name="notes" rows="3" maxlength="500" data-i18n-placeholder="booking.note_placeholder" placeholder="p.sh. stili i dëshiruar"></textarea></label>
                <div class="form-message" id="booking-message" role="alert" aria-live="assertive"></div>
                <button class="button button-dark button-large button-block submit-booking" type="submit"><span data-i18n="booking.submit">Konfirmo rezervimin</span><span class="button-arrow" aria-hidden="true">↗</span></button>
            </form>

            <div class="booking-success" id="booking-success" hidden>
                <div class="success-check" aria-hidden="true">✓</div>
                <p class="section-kicker" data-i18n="booking.success_eyebrow">U dërgua</p>
                <h2 data-i18n="booking.success_title">Rezervimi është në pritje</h2>
                <p data-i18n="booking.success_text">Kontrollo statusin kur të duash vetëm me numrin e telefonit.</p>
                <div class="success-actions">
                    <a class="button button-dark" id="status-link" href="<?= e(url('/client/')) ?>" data-i18n="booking.open_status">Kontrollo statusin</a>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
