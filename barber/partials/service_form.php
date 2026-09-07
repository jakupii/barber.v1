<form class="editor-form" method="post" action="<?= e(url('/barber/actions/service_save.php')) ?>">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="service_id" value="<?= (int) $service['id'] ?>">
    <p class="editor-section-title">Emri i shërbimit</p>
    <div class="editor-form-grid">
        <label class="field"><span>Shqip</span><input type="text" name="name_sq" value="<?= e($service['name_sq']) ?>" maxlength="160" required></label>
        <label class="field"><span>Македонски</span><input type="text" name="name_mk" value="<?= e($service['name_mk']) ?>" maxlength="160" required></label>
    </div>
    <label class="field"><span>English</span><input type="text" name="name_en" value="<?= e($service['name_en']) ?>" maxlength="160" required></label>
    <p class="editor-section-title">Përshkrimi</p>
    <label class="field"><span>Shqip</span><textarea name="description_sq" rows="2" maxlength="500"><?= e($service['description_sq']) ?></textarea></label>
    <label class="field"><span>Македонски</span><textarea name="description_mk" rows="2" maxlength="500"><?= e($service['description_mk']) ?></textarea></label>
    <label class="field"><span>English</span><textarea name="description_en" rows="2" maxlength="500"><?= e($service['description_en']) ?></textarea></label>
    <div class="editor-form-grid">
        <label class="field"><span>Çmimi (€)</span><input type="number" name="price" value="<?= e(number_format((int) $service['price_cents'] / 100, 2, '.', '')) ?>" min="0" max="9999" step="0.01" required></label>
        <label class="field"><span>Kohëzgjatja</span><select name="duration_minutes"><option value="30" <?= (int) $service['duration_minutes'] === 30 ? 'selected' : '' ?>>30 min</option><option value="60" <?= (int) $service['duration_minutes'] === 60 ? 'selected' : '' ?>>60 min</option><option value="90" <?= (int) $service['duration_minutes'] === 90 ? 'selected' : '' ?>>90 min</option><option value="120" <?= (int) $service['duration_minutes'] === 120 ? 'selected' : '' ?>>120 min</option></select></label>
    </div>
    <label class="field"><span>Renditja</span><input type="number" name="display_order" value="<?= (int) $service['display_order'] ?>" min="0" max="999"></label>
    <div class="editor-form-actions"><button class="button button-dark" type="submit"><?= (int) $service['id'] ? 'Ruaj ndryshimet' : 'Shto shërbimin' ?></button><label class="active-check"><input type="checkbox" name="active" value="1" <?= $service['active'] ? 'checked' : '' ?>> Aktiv në faqe</label></div>
</form>
