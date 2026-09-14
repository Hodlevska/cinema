<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$code = trim($_GET['code'] ?? '');

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Введіть промокод']);
    exit;
}

// Шукаємо промокод у базі даних (відповідно до твоєї структури таблиці promocodes)
$stmt = $pdo->prepare("SELECT id, discount_percent, valid_until FROM promocodes WHERE code = ?");
$stmt->execute([$code]);
$promo = $stmt->fetch();

if (!$promo) {
    echo json_encode(['success' => false, 'message' => 'Невірний промокод']);
    exit;
}

// Перевірка терміну дії (якщо задано valid_until)
if (!empty($promo['valid_until']) && $promo['valid_until'] < date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'Термін дії промокоду минув']);
    exit;
}

// Успішно
echo json_encode([
    'success' => true,
    'discount' => (int)$promo['discount_percent']
]);