<?php $previewId = 'photo-preview-' . (int) $barber['id']; ?>
<form class="editor-form" method="post" action="<?= e(url('/barber/actions/barber_save.php')) ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="barber_id" value="<?= (int) $barber['id'] ?>">
    <div class="photo-upload">
        <img class="photo-preview" id="<?= e($previewId) ?>" src="<?= e(asset_url($barber['image_path'] ?: '/assets/images/hero-barbershop-v2.jpg')) ?>" alt="Paraqitja e fotografisë">
        <label class="field"><span>Fotografia (JPG, PNG ose WebP · max 3 MB)</span><input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-photo-input data-preview="<?= e($previewId) ?>" <?= (int) $barber['id'] === 0 ? 'required' : '' ?>></label>
    </div>
    <div class="editor-form-grid">
        <label class="field"><span>Emri dhe mbiemri</span><input type="text" name="name" value="<?= e($barber['name']) ?>" maxlength="120" required></label>
        <label class="field"><span>Vite përvojë</span><input type="number" name="experience_years" value="<?= (int) $barber['experience_years'] ?>" min="0" max="70"></label>
        <label class="field"><span>Telefoni</span><input type="tel" name="phone" value="<?= e($barber['phone']) ?>" maxlength="40"></label>
        <label class="field"><span>Email</span><input type="email" name="email" value="<?= e($barber['email']) ?>" maxlength="190"></label>
    </div>
    <p class="editor-section-title">Titulli / specializimi</p>
    <div class="editor-form-grid"><label class="field"><span>Shqip</span><input type="text" name="title_sq" value="<?= e($barber['title_sq']) ?>" maxlength="180" required></label><label class="field"><span>Македонски</span><input type="text" name="title_mk" value="<?= e($barber['title_mk']) ?>" maxlength="180" required></label></div>
    <label class="field"><span>English</span><input type="text" name="title_en" value="<?= e($barber['title_en']) ?>" maxlength="180" required></label>
    <p class="editor-section-title">Biografia</p>
    <label class="field"><span>Shqip</span><textarea name="bio_sq" rows="2" maxlength="700"><?= e($barber['bio_sq']) ?></textarea></label>
    <label class="field"><span>Македонски</span><textarea name="bio_mk" rows="2" maxlength="700"><?= e($barber['bio_mk']) ?></textarea></label>
    <label class="field"><span>English</span><textarea name="bio_en" rows="2" maxlength="700"><?= e($barber['bio_en']) ?></textarea></label>
    <p class="editor-section-title">Hyrja e berberit në panel</p>
    <div class="editor-form-grid">
        <label class="field"><span>Username</span><input type="text" name="username" value="<?= e($barber['username']) ?>" pattern="[A-Za-z0-9._-]{3,40}" maxlength="40" autocomplete="off" required></label>
        <label class="field"><span><?= (int) $barber['id'] ? 'Fjalëkalim i ri (lëre bosh pa ndryshim)' : 'Fjalëkalimi fillestar' ?></span><input type="password" name="password" minlength="10" autocomplete="new-password" <?= (int) $barber['id'] === 0 ? 'required' : '' ?>></label>
    </div>
    <label class="field"><span>Renditja</span><input type="number" name="display_order" value="<?= (int) $barber['display_order'] ?>" min="0" max="999"></label>
    <div class="editor-form-actions"><button class="button button-dark" type="submit"><?= (int) $barber['id'] ? 'Ruaj profilin' : 'Shto berberin' ?></button><label class="active-check"><input type="checkbox" name="active" value="1" <?= $barber['active'] ? 'checked' : '' ?>> Aktiv në faqe dhe panel</label></div>
</form>
<?php if ((int) $barber['id'] > 0): ?>
    <div class="editor-secondary-actions">
        <a class="action-button" href="<?= e(url('/barber/dashboard.php?tab=schedule&barber_id=' . (int) $barber['id'])) ?>">▦ Menaxho orarin</a>
        <form method="post" action="<?= e(url('/barber/actions/barber_toggle.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="barber_id" value="<?= (int) $barber['id'] ?>">
            <button class="action-button <?= $barber['active'] ? 'action-reject' : 'action-accept' ?>" type="submit" data-confirm="<?= $barber['active'] ? 'Ta fsheh këtë berber nga faqja dhe rezervimet e reja?' : 'Ta rikthej këtë berber në faqe?' ?>"><?= $barber['active'] ? 'Fshih nga faqja' : 'Rikthe në faqe' ?></button>
        </form>
    </div>
<?php endif; ?>
