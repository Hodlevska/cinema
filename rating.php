<?php
require_once 'db.php';

// 1. Найпопулярніші фільми за останній місяць (використовуємо poster_url)
$stmtMovies = $pdo->query("
    SELECT m.title, m.poster_url, COUNT(t.id) AS sold_tickets
    FROM movies m
    JOIN shows s ON m.id = s.movie_id
    JOIN tickets t ON s.id = t.show_id
    WHERE s.start_time >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
    GROUP BY m.id, m.title, m.poster_url
    ORDER BY sold_tickets DESC
    LIMIT 5
");
$topMovies = $stmtMovies->fetchAll();

// 2. Найпопулярніші товари бару за весь час (використовуємо image_url)
$stmtProducts = $pdo->query("
    SELECT p.name, p.image_url, SUM(oi.quantity) AS total_qty
    FROM products p
    JOIN order_items oi ON p.id = oi.product_id
    GROUP BY p.id, p.name, p.image_url
    ORDER BY total_qty DESC
    LIMIT 5
");
$topProducts = $stmtProducts->fetchAll();

// 3. Середня кількість квитків в одному замовленні
$stmtAvgTickets = $pdo->query("
    SELECT AVG(ticket_count) AS avg_tickets FROM (
        SELECT order_id, COUNT(*) AS ticket_count
        FROM tickets
        GROUP BY order_id
    ) AS sub
");
$avgTickets = $stmtAvgTickets->fetch()['avg_tickets'] ?? 0;

// 4. Сеанс із найбільшою кількістю заброньованих місць у залі
$stmtMaxShowSeats = $pdo->query("
    SELECT m.title, m.poster_url, h.hall_number, COUNT(t.id) AS booked_seats, s.start_time
    FROM shows s
    JOIN movies m ON s.movie_id = m.id
    JOIN halls h ON s.hall_id = h.id
    JOIN tickets t ON s.id = t.show_id
    GROUP BY s.id, m.title, m.poster_url, h.hall_number, s.start_time
    ORDER BY booked_seats DESC
    LIMIT 1
");
$maxShow = $stmtMaxShowSeats->fetch();
?>
<!DOCTYPE html>
<html lang="uk">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cтатистика — Cinema DB</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Mono:ital,wght@0,300;0,400;0,500;1,300;1,400;1,500&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Fira+Sans+Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Fraunces:ital,opsz,wght@0,9..144,100..900;1,9..144,100..900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
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
    <main class="news-page">
        <h2 class="title-statistic">Cтатистика кінотеатру</h2>

        <div class="stats-container">
            <!-- Блок 1: Фільми -->
            <div class="stat-block-1">
                <h3 class="static-title">Найпопулярніші фільми за місяць</h3>
                <?php if (!empty($topMovies)): ?>
                    <ul class="stat-list-with-img">
                        <?php foreach ($topMovies as $movie): ?>
                            <li class="list">
                                <?php if (!empty($movie['poster_url'])): ?>
                                    <img class="poster-rating" src="<?= htmlspecialchars($movie['poster_url']) ?>"
                                        alt="<?= htmlspecialchars($movie['title']) ?>" class="stat-thumb">
                                <?php endif; ?>
                                <div class="film-block">
                                    <span class="film-name"><?= htmlspecialchars($movie['title']) ?></span>
                                    <span> <strong><?= $movie['sold_tickets'] ?></strong> квитків</span>
                                </div>

                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Немає даних за останній місяць.</p>
                <?php endif; ?>
            </div>

            <!-- Блок 2: Товари бару -->
            <div class="stat-block-2">
                <h3 class="static-title">Найпопулярніші товари бару</h3>
                <?php if (!empty($topProducts)): ?>
                    <ul class="stat-list-with-img">
                        <?php foreach ($topProducts as $prod): ?>
                            <li class="list">

                                <?php if (!empty($prod['image_url'])): ?>
                                    <img class="food-rating" src="<?= htmlspecialchars($prod['image_url']) ?>"
                                        alt="<?= htmlspecialchars($prod['name']) ?>" class="stat-thumb">
                                <?php endif; ?>
                                <div class="food-info-block">
                                        <span class="food-name"><?= htmlspecialchars($prod['name']) ?></span>
                                        <span><strong><?= $prod['total_qty'] ?></strong>
                                        шт.</span>
                                </div>

                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Товари ще не замовлялися.</p>
                <?php endif; ?>
            </div>
            <div class="blocks-3-and-4">
                <!-- Блок 3: Середня кількість квитків -->
                <div class="stat-block-3">
                    <h3 class="static-title">Середня кількість квитків в замовленні</h3>
                    <p class="stat-big-number"><?= number_format($avgTickets, 1) ?></p>
                    <img class="frame" src="assets\\img\\frame.jpg" alt="">
                </div>

                <!-- Блок 4: Найбільше заброньованих місць -->
                <div class="stat-block-4">
                    <h3 class="static-title">Найбільша кількість заброньованих місць</h3>
                    <?php if ($maxShow): ?>
                        <div class="stat-highlight">
                            <?php if (!empty($maxShow['poster_url'])): ?>
                                <img class="popular-film-poster" src="<?= htmlspecialchars($maxShow['poster_url']) ?>"
                                    alt="<?= htmlspecialchars($maxShow['title']) ?>" class="stat-thumb-large">
                            <?php endif; ?>
                            <div class="info-popular-film">
                                <p><strong>Фільм:</strong> <?= htmlspecialchars($maxShow['title']) ?></p>
                                <p><strong>Зал:</strong> №<?= $maxShow['hall_number'] ?></p>
                                <p><strong>Дата:</strong> <?= date('d.m.Y H:i', strtotime($maxShow['start_time'])) ?></p>
                                <p><strong>Заброньовано:</strong> <span class="red"><?= $maxShow['booked_seats'] ?></span>  місць</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <p>Дані відсутні.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <div class="news-actions">
            <a href="index.php" class="btn">Повернутися на головну</a>
        </div>
    </main>

    <footer>
        <a class="email" href="mailto:cinema@gmail.com">cinema@gmail.com</a>
        <a class="phone" href="tel:380123456780">+380 12 345 6789</a>
        <p>&copy; <?= date('Y') ?> Cinema DB. Усі права захищені.</p>
    </footer>

</body>

</html>