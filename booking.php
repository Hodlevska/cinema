<?php
require_once 'db.php';

// Отримуємо ID сеансу
$show_id = $_GET['show_id'] ?? null;

if (!$show_id) {
    header('Location: index.php');
    exit;
}

// Отримуємо інформацію про сеанс, фільм і зал
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

// Отримуємо вже зайняті місця на цей сеанс (екрануємо зарезервовані слова backticks)
$stmtTickets = $pdo->prepare("SELECT `row_number`, `seat_number` FROM tickets WHERE show_id = ?");
$stmtTickets->execute([$show_id]);
$occupiedSeats = $stmtTickets->fetchAll(PDO::FETCH_ASSOC);

// Формуємо карту зайнятих місць
$occupiedMap = [];
foreach ($occupiedSeats as $ticket) {
    $occupiedMap[$ticket['row_number']][$ticket['seat_number']] = true;
}
?>
<!DOCTYPE html>
<html lang="uk">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вибір місць — <?= htmlspecialchars($show['title']) ?></title>
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

    <main class="booking-page">
        <h2 class="film-name"><?= htmlspecialchars($show['title']) ?></h2>
        <p class="show-meta">
            Зал №<?= $show['hall_number'] ?> |
            <?= date('d.m.Y H:i', strtotime($show['start_time'])) ?> |
            Ціна: <?= number_format($show['ticket_price'], 0) ?> грн
        </p>

        <!-- Форма передає вибрані місця далі на bar.php -->
        <form action="bar.php" method="GET" class="cinema-hall-form">
            <input type="hidden" name="show_id" value="<?= $show['id'] ?>">

            <div class="screen">ЕКРАН</div>

            <div class="seats-container">
                <?php for ($r = 1; $r <= 5; $r++): ?>
                    <div class="seat-row">
                        <span class="row-number">Ряд <?= $r ?></span>
                        <div class="seats-list">
                            <?php for ($s = 1; $s <= 10; $s++):
                                $isOccupied = isset($occupiedMap[$r][$s]);
                                ?>
                               <?php if ($isOccupied): ?>
    <span class="seat-label occupied" title="Зайнято"></span>
<?php else: ?>
                                    <label class="seat-label">
                                        <input type="checkbox" name="seats[]" value="<?= $r ?>_<?= $s ?>" class="seat-checkbox">
                                    </label>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
            <div class="indo"></div>
            <div class="booking-actions">
                <button type="submit" class="btn submit-btn" id="nextBtn" disabled>
                    Перейти до бару 🍿
                </button>
            </div>
        </form>
    </main>
    <footer>
        <a class="email" href="mailto:cinema@gmail.com">cinema@gmail.com</a>
        <a class="phone" href="tel:380123456780">+380 12 345 6789</a>
        <p>&copy; <?= date('Y') ?> Cinema DB. Усі права захищені.</p>
    </footer>

    <script>
        // Активуємо кнопку лише якщо вибрано хоча б одне місце
        const checkboxes = document.querySelectorAll('.seat-checkbox');
        const nextBtn = document.getElementById('nextBtn');

        checkboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                const hasSelected = Array.from(checkboxes).some(input => input.checked);
                nextBtn.disabled = !hasSelected;
            });
        });
    </script>

</body>

</html>