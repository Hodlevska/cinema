<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$show_id = $_POST['show_id'] ?? null;
$selected_seats = $_POST['seats'] ?? [];
$selected_products = $_POST['products'] ?? [];
$promo_code = trim($_POST['promo_code'] ?? '');

if (!$show_id || empty($selected_seats)) {
    header('Location: index.php');
    exit;
}

// 1. Отримуємо дані про сеанс та фільм
$stmtShow = $pdo->prepare("
    SELECT s.id, s.start_time, s.ticket_price, m.title, h.hall_number
    FROM shows s
    JOIN movies m ON s.movie_id = m.id
    JOIN halls h ON s.hall_id = h.id
    WHERE s.id = ?
");
$stmtShow->execute([$show_id]);
$show = $stmtShow->fetch();

if (!$show) {
    die('Сеанс не знайдено');
}

// 2. Перевірка промокоду в базі даних
$promoId = null;
$discountPercent = 0;

if (!empty($promo_code)) {
    $stmtPromo = $pdo->prepare("SELECT id, discount_percent, valid_until FROM promocodes WHERE code = ?");
    $stmtPromo->execute([$promo_code]);
    $promo = $stmtPromo->fetch();

    if ($promo) {
        if (empty($promo['valid_until']) || $promo['valid_until'] >= date('Y-m-d')) {
            $promoId = $promo['id'];
            $discountPercent = (int) $promo['discount_percent'];
        }
    }
}

// 3. Розрахунок сум квитків та бару
$ticketsTotal = count($selected_seats) * $show['ticket_price'];

$barTotal = 0;
$orderedProducts = [];

if (!empty($selected_products)) {
    $productIds = array_keys(array_filter($selected_products, function ($qty) {
        return $qty > 0;
    }));

    if (!empty($productIds)) {
        $inQuery = implode(',', array_fill(0, count($productIds), '?'));
        $stmtProd = $pdo->prepare("SELECT id, name, price FROM products WHERE id IN ($inQuery)");
        $stmtProd->execute($productIds);
        $productsData = $stmtProd->fetchAll();

        foreach ($productsData as $prod) {
            $qty = (int) $selected_products[$prod['id']];
            if ($qty > 0) {
                $sum = $prod['price'] * $qty;
                $barTotal += $sum;

                $orderedProducts[] = [
                    'id' => $prod['id'],
                    'name' => $prod['name'],
                    'qty' => $qty,
                    'price' => $prod['price'],
                    'sum' => $sum
                ];
            }
        }
    }
}

$subtotal = $ticketsTotal + $barTotal;
$discountSum = ($subtotal * $discountPercent) / 100;
$grandTotal = $subtotal - $discountSum;

// 4. Зберігаємо замовлення в таблицю orders
$stmtInsertOrder = $pdo->prepare("INSERT INTO orders (order_date, total_price) VALUES (NOW(), ?)");
$stmtInsertOrder->execute([$grandTotal]);
$orderId = $pdo->lastInsertId();

// 5. Зберігаємо квитки (без передачі price)
$createdTickets = [];
$stmtInsertTicket = $pdo->prepare("
    INSERT INTO tickets (show_id, promocode_id, order_id, `row_number`, `seat_number`, status) 
    VALUES (?, ?, ?, ?, ?, 'booked')
");

foreach ($selected_seats as $seat) {
    list($row, $seatNum) = explode('_', $seat);

    $stmtInsertTicket->execute([$show_id, $promoId, $orderId, $row, $seatNum]);
    $createdTickets[] = "Ряд $row, Місце $seatNum";
}

// 6. Зберігаємо товари з бару в таблицю order_items
if (!empty($orderedProducts)) {
    $stmtInsertItem = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, unit_price) 
        VALUES (?, ?, ?, ?)
    ");

    foreach ($orderedProducts as $item) {
        $stmtInsertItem->execute([$orderId, $item['id'], $item['qty'], $item['price']]);
    }
}
?>
<!DOCTYPE html>
<html lang="uk">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чек замовлення — Cinema DB</title>
    <link rel="stylesheet" href="styles.css">
     <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:ital,wght@0,300;0,400;0,500;1,300;1,400;1,500&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Fira+Sans+Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Fraunces:ital,opsz,wght@0,9..144,100..900;1,9..144,100..900&display=swap" rel="stylesheet">

</head>

<body>

    <nav>
        <div class="logo-text">
            <a><img class="logo" src="assets\img\red_logo.png" alt=""></a>
            <h1>Кінотеатр</h1>
        </div>
        <div class="nav-a">
            <a href="index.php">Афіша</a>
            <a href="we-in-map.html">Як нас знайти</a>
            <a href="about-us.html">Про нас</a>
            <a href="news.php">Рейтинги</a>
        </div>

    </nav>
    <main class="checkout-page">

        <div class="ticket-receipt">
            <h2 class="thanks">Дякуємо за покупку!</h2>
            <p class="receipt-status">Замовлення №<?= $orderId ?> успішно оформлено та оплачено.</p>

            <hr>

            <div class="receipt-section">
                <h3>Деталі сеансу</h3>
                <p><strong>Фільм:</strong> <?= htmlspecialchars($show['title']) ?></p>
                <p><strong>Дата та час:</strong> <?= date('d.m.Y H:i', strtotime($show['start_time'])) ?></p>
                <p><strong>Зал:</strong> №<?= $show['hall_number'] ?></p>
                <p><strong>Квитки:</strong> <?= implode(' | ', $createdTickets) ?></p>
                <p><strong>Сума за квитки:</strong> <?= number_format($ticketsTotal, 0) ?> грн</p>
            </div>

            <?php if (!empty($orderedProducts)): ?>
                <hr>
                <div class="receipt-section">
                    <h3>Кінобар</h3>
                    <ul class="list-check">
                        <?php foreach ($orderedProducts as $item): ?>
                            <li>
                                <?= htmlspecialchars($item['name']) ?> x<?= $item['qty'] ?> —
                                <?= number_format($item['sum'], 0) ?> грн
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p><strong>Сума за бар:</strong> <?= number_format($barTotal, 0) ?> грн</p>
                </div>
            <?php endif; ?>

            <?php if ($discountPercent > 0): ?>
                <hr>
                <div class="receipt-section">
                    <p><strong>Застосовано промокод:</strong> <?= htmlspecialchars($promo_code) ?> (знижка
                        <?= $discountPercent ?>%)</p>
                    <p><strong>Сума знижки:</strong> -<?= number_format($discountSum, 0) ?> грн</p>
                </div>
            <?php endif; ?>

            <hr>

            <div class="receipt-total">
                <h3>Загальна сума: <?= number_format($grandTotal, 0) ?> грн</h3>
            </div>

            <div class="receipt-actions">
                <a href="index.php" class="btn-go-to-main">Повернутися на головну</a>
            </div>
        </div>
    </main>

        <footer>
    <a class="email" href="mailto:cinema@gmail.com">cinema@gmail.com</a>
    <a class="phone" href="tel:380123456780">+380 12 345 6789</a>
    <p>&copy Cinema DB. Усі права захищені.</p>
</footer>

</body>

</html>