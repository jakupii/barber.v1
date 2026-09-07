<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

require_login(['admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/barber/dashboard.php?tab=services');
}
require_csrf();

$serviceId = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT) ?: 0;
$fields = [];
foreach (['name_sq', 'name_mk', 'name_en', 'description_sq', 'description_mk', 'description_en'] as $field) {
    $fields[$field] = trim((string) ($_POST[$field] ?? ''));
}
$priceInput = str_replace(',', '.', trim((string) ($_POST['price'] ?? '')));
$duration = filter_input(INPUT_POST, 'duration_minutes', FILTER_VALIDATE_INT);
$displayOrder = filter_input(INPUT_POST, 'display_order', FILTER_VALIDATE_INT);
$active = isset($_POST['active']) ? 1 : 0;

if (array_filter([$fields['name_sq'], $fields['name_mk'], $fields['name_en']], static fn (string $value): bool => mb_strlen($value) < 2) !== []
    || !preg_match('/^\d{1,4}(?:\.\d{1,2})?$/', $priceInput)
    || !in_array($duration, [30, 60, 90, 120], true)) {
    flash('error', 'Plotëso emrat, çmimin dhe kohëzgjatjen në mënyrë të vlefshme.');
    redirect('/barber/dashboard.php?tab=services');
}

$priceCents = (int) round((float) $priceInput * 100);
$displayOrder = max(0, min(999, (int) $displayOrder));

if ($serviceId > 0) {
    $statement = db()->prepare(
        'UPDATE services SET name_sq = ?, name_mk = ?, name_en = ?, description_sq = ?, description_mk = ?, description_en = ?,
         price_cents = ?, duration_minutes = ?, active = ?, display_order = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
    );
    $statement->execute([$fields['name_sq'], $fields['name_mk'], $fields['name_en'], $fields['description_sq'], $fields['description_mk'], $fields['description_en'], $priceCents, $duration, $active, $displayOrder, $serviceId]);
    flash('success', 'Shërbimi dhe çmimi u përditësuan.');
} else {
    $statement = db()->prepare(
        'INSERT INTO services (name_sq, name_mk, name_en, description_sq, description_mk, description_en, price_cents, duration_minutes, active, display_order, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
    );
    $statement->execute([$fields['name_sq'], $fields['name_mk'], $fields['name_en'], $fields['description_sq'], $fields['description_mk'], $fields['description_en'], $priceCents, $duration, $active, $displayOrder]);
    flash('success', 'Shërbimi i ri u shtua në çmimore.');
}

redirect('/barber/dashboard.php?tab=services');
