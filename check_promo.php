<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$code = trim($_GET['code'] ?? '');

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Введіть промокод']);
    exit;
}

// Шукаємо промокод у базі даних (включаючи перевірку поля is_used)
$stmt = $pdo->prepare("SELECT id, discount_percent, valid_until, is_used FROM promocodes WHERE code = ?");
$stmt->execute([$code]);
$promo = $stmt->fetch();

if (!$promo) {
    echo json_encode(['success' => false, 'message' => 'Невірний промокод']);
    exit;
}

// 1. Перевірка, чи промокод уже використовувався
if ($promo['is_used'] == 1) {
    echo json_encode(['success' => false, 'message' => 'Цей промокод уже був використаний']);
    exit;
}

// 2. Перевірка терміну дії (якщо задано valid_until)
if (!empty($promo['valid_until']) && $promo['valid_until'] < date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'Термін дії промокоду минув']);
    exit;
}

// Успішно
echo json_encode([
    'success' => true,
    'discount' => (int)$promo['discount_percent']
]);