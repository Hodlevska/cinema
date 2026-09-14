<?php
require_once 'db.php';

// Отримуємо ID фільму з URL (наприклад: movie.php?id=5)
$movie_id = $_GET['id'] ?? null;

if (!$movie_id) {
    header('Location: index.php');
    exit;
}

// Отримуємо деталі фільму
$stmtMovie = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$stmtMovie->execute([$movie_id]);
$movie = $stmtMovie->fetch();

if (!$movie) {
    die('Фільм не знайдено');
}

// Отримуємо сеанси для цього фільму разом із даними про зал
$stmtShows = $pdo->prepare("
    SELECT s.id, s.start_time, s.ticket_price, h.hall_number, h.technology
    FROM shows s
    JOIN halls h ON s.hall_id = h.id
    WHERE s.movie_id = ?
    ORDER BY s.start_time ASC
");
$stmtShows->execute([$movie_id]);
$shows = $stmtShows->fetchAll();
?>
<!DOCTYPE html>
<html lang="uk">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($movie['title']) ?> — Вибір сеансу</title>
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
            <a href="we-in-map.html">Як нас знайти</a>
            <a href="about-us.html">Про нас</a>
            <a href="rating.php">Рейтинги</a>
            
        </div>

    </nav>

    <main class="movie-details-page">
        <div class="movie-poster-box">
            <img src="<?= htmlspecialchars($movie['poster_url']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
        </div>

        <div class="movie-info-box">
            <div class="container-title-of-this-film">
                <h2 class="title-of-this-film"><?= htmlspecialchars($movie['title']) ?></h2>
                <p class="age-restriction-of-this-film"> <?= htmlspecialchars($movie['age_restriction']) ?></p>

            </div>

            <p class="genre"><strong>Жанр:</strong> <?= htmlspecialchars($movie['genre']) ?></p>
            <p class="duration"><strong>Тривалість:</strong> <?= $movie['duration_min'] ?> хв</p>

            <hr>

            <h3 class="sianses-title">Доступні сеанси</h3>
            <div class="fog"></div>
            <div class="shows-list">
                <?php if (empty($shows)): ?>
                    <p>Сеансів для цього фільму немає.</p>
                <?php else: ?>
                    <?php foreach ($shows as $show): ?>
                        <a href="booking.php?show_id=<?= $show['id'] ?>" class="show-card">
                            <div class="time-and-date">
                                <span class="time"><?= date('H:i', strtotime($show['start_time'])) ?></span>
                                <span class="date"><?= date('d.m.Y', strtotime($show['start_time'])) ?></span>
                            </div>
                            <span class="hall">Зал №<?= $show['hall_number'] ?>
                                (<?= htmlspecialchars($show['technology']) ?>)</span>
                            <span class="price"><?= number_format($show['ticket_price'], 0) ?>₴</span>
                        </a>

                    <?php endforeach; ?>
                <?php endif; ?>
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