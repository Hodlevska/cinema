<?php
require_once 'db.php';

// Отримуємо обрані дані
$show_id = $_GET['show_id'] ?? null;
$selected_seats = $_GET['seats'] ?? [];

if (!$show_id || empty($selected_seats)) {
    header('Location: index.php');
    exit;
}

// Отримуємо інформацію про сеанс та фільм
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

// Розраховуємо вартість квитків
$ticketsCount = count($selected_seats);
$ticketsTotal = $ticketsCount * $show['ticket_price'];

// Отримуємо товари кінобару з БД
$stmtProducts = $pdo->query("SELECT * FROM products ORDER BY category ASC, price ASC");
$products = $stmtProducts->fetchAll();
?>
<!DOCTYPE html>
<html lang="uk">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кінобар — <?= htmlspecialchars($show['title']) ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Mono:ital,wght@0,300;0,400;0,500;1,300;1,400;1,500&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Fira+Sans+Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Fraunces:ital,opsz,wght@0,9..144,100..900;1,9..144,100..900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Додаємо стилі для коректного відображення лінії, якщо в CSS вони скинуті */
        .line {
            border: none;
            height: 1px;
            background-color: #d1d5db;
            margin: 20px 0;
            display: block;
        }
    </style>
</head>

<body>

    <nav>
        <div class="logo-text">
            <a><img class="logo" src="assets\img\red_logo.png" alt=""></a>
            <h1>Кінотеатр</h1>
        </div>
        <div class="nav-a">
            <a href="index.php">Афіша</a>
            <a href="about-us.html">Про нас</a>
            <a href="we-in-map.html">Як нас знайти</a>
        </div>
    </nav>

    <main class="bar-page-main">
        <h2 class="title-bar">Додайте смаколики до сеансу 🍿🥤</h2>
        <div class="bar-page">
            <form action="checkout.php" method="POST" class="bar-form" id="barForm">
                <input type="hidden" name="show_id" value="<?= $show['id'] ?>">
                <?php foreach ($selected_seats as $seat): ?>
                    <input type="hidden" name="seats[]" value="<?= htmlspecialchars($seat) ?>">
                <?php endforeach; ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="<?= htmlspecialchars($product['image_url']) ?>"
                                    alt="<?= htmlspecialchars($product['name']) ?>">
                            <?php endif; ?>

                            <h4><?= htmlspecialchars($product['name']) ?></h4>
                            <p class="product-category"><?= htmlspecialchars($product['category']) ?></p>
                            <p class="product-price" data-price="<?= $product['price'] ?>">
                                <?= number_format($product['price'], 0) ?> грн</p>

                            <div class="product-quantity">
                                <label>Кількість:</label>
                                <input type="number" name="products[<?= $product['id'] ?>]" value="0" min="0" max="10"
                                    class="qty-input product-qty">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>

            <div class="booking-summary">
                <h3 class="titile-of-check">Деталі замовлення:</h3>
                <p><strong>Фільм:</strong> <?= htmlspecialchars($show['title']) ?></p>
                <p><strong>Час:</strong> <?= date('d.m.Y H:i', strtotime($show['start_time'])) ?> (Зал
                    №<?= $show['hall_number'] ?>)</p>
                <p><strong>Обрані місця:</strong>
                    <?php
                    $formattedSeats = array_map(function ($seat) {
                        list($r, $s) = explode('_', $seat);
                        return "Ряд $r, Місце $s";
                    }, $selected_seats);
                    echo implode(' | ', $formattedSeats);
                    ?>
                </p>
                <p><strong>Сума за квитки:</strong> <span id="ticketsTotal"
                        data-price="<?= $ticketsTotal ?>"><?= number_format($ticketsTotal, 0) ?></span> грн
                    (<?= $ticketsCount ?> шт.)</p>
                    <hr class="line">
                <p><strong>Обрані товари з бару:</strong> </p>
                <span id="selectedProductsSummary">Нічого не обрано</span>
                
                <!-- Блок введення промокоду -->
                <div class="promo-section">
                    <label for="promo_code_input"><strong>Маєте промокод?</strong></label>
                    <div class="promocode-container">
                        <input type="text" id="promo_code_input" name="promo_code" placeholder="Введіть промокод">
                        <button type="button" id="applyPromoBtn" class="btn">Застосувати</button>
                    </div>
                    <p id="promoMessage"></p>
                </div>
                           
                

                <div class="booking-summ">
                    <h3 class="price-of-all-title">Загальна сума: </h3>
                    <h3 id="grandTotalDisplay">0</h3>
                </div>
                <div class="bar-actions">
                    <button type="submit" form="barForm" class="submit-bar-btn">Підтвердити та сплатити 💳</button>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <a class="email" href="mailto:cinema@gmail.com">cinema@gmail.com</a>
        <a class="phone" href="tel:380123456780">+380 12 345 6789</a>
        <p>&copy; <?= date('Y') ?> Cinema DB. Усі права захищені.</p>
    </footer>

    <script>
        const ticketsTotal = parseFloat(document.getElementById('ticketsTotal').dataset.price);
        const qtyInputs = document.querySelectorAll('.product-qty');
        const grandTotalDisplay = document.getElementById('grandTotalDisplay');
        const selectedProductsSummary = document.getElementById('selectedProductsSummary');
        const applyPromoBtn = document.getElementById('applyPromoBtn');
        const promoInput = document.getElementById('promo_code_input');
        const promoMessage = document.getElementById('promoMessage');

        let currentDiscountPercent = 0;

        function updateTotals() {
            let barTotal = 0;
            let summaryArray = [];

            qtyInputs.forEach(input => {
                const card = input.closest('.product-card');
                const name = card.querySelector('h4').textContent;
                const price = parseFloat(card.querySelector('.product-price').dataset.price);
                const qty = parseInt(input.value) || 0;

                if (qty > 0) {
                    barTotal += price * qty;
                    summaryArray.push(`${name} (${qty} шт.)`);
                }
            });

            if (summaryArray.length > 0) {
                selectedProductsSummary.innerHTML = summaryArray.join("<br>");
            } else {
                selectedProductsSummary.textContent = 'Нічого не обрано';
            }

            let subtotal = ticketsTotal + barTotal;
            let discount = (subtotal * currentDiscountPercent) / 100;
            let finalTotal = subtotal - discount;

            grandTotalDisplay.textContent = Math.round(finalTotal) + " грн";
        }

        qtyInputs.forEach(input => {
            input.addEventListener('input', updateTotals);
        });

        applyPromoBtn.addEventListener('click', async () => {
            const code = promoInput.value.trim();
            if (!code) return;

            try {
                let response = await fetch('check_promo.php?code=' + encodeURIComponent(code));
                let data = await response.json();

                if (data.success) {
                    currentDiscountPercent = data.discount;
                    promoMessage.style.color = '#4caf50';
                    promoMessage.textContent = `Промокод успішно застосовано! Знижка: ${data.discount}%`;
                    updateTotals();
                } else {
                    currentDiscountPercent = 0;
                    promoMessage.style.color = '#f44336';
                    promoMessage.textContent = data.message;
                    updateTotals();
                }
            } catch (e) {
                promoMessage.style.color = '#f44336';
                promoMessage.textContent = 'Помилка перевірки промокоду';
            }
        });

        updateTotals();
    </script>
</body>

</html>