<?php
require_once 'db.php';

// Отримуємо списки фільмів з БД
$stmt = $pdo->query("SELECT id, title, poster_url, genre, duration_min, age_restriction FROM movies");
$movies = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кінотеатр — Головна</title>
     <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:ital,wght@0,300;0,400;0,500;1,300;1,400;1,500&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Fira+Sans+Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Fraunces:ital,opsz,wght@0,9..144,100..900;1,9..144,100..900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head>
<body>

    <header>
         <nav>
            <div class="logo-text">
            
                <a><img class="logo" src="assets\img\red_logo.png" alt="" ></a>
                <h1>Кінотеатр</h1>
            

            </div>
            <div class="nav-a">
                <a href="about-us.html">Про нас</a>
                <a href="we-in-map.html">Як нас знайти</a>
                <a href="rating.php">Рейтинги</a>
            </div>

        </nav>
    </header>

    <main>
        <h2>Зараз у кіно</h2>

        <div class="movies-grid">
            <?php foreach ($movies as $movie): ?>
                <div class="movie-card">
                    <img class="poster" src="<?= htmlspecialchars($movie['poster_url']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
                    <div class="container-tile">
                        <h3 class="title-of-film"><?= htmlspecialchars($movie['title']) ?></h3>
                    <p class="age-restriction"><?= htmlspecialchars($movie['age_restriction']) ?></p> 
                    </div>
                    <p class="genre"><?= htmlspecialchars($movie['genre']) ?></p>
                    <div class="time-container">
                        <img src="assets\img\time.svg" alt="">
                        <p class="info"><?= $movie['duration_min'] ?> хв </p>
                    </div>

                    <!-- Посилання для переходу на сторінку вибору часу -->
                    <a type="button" href="movie.php?id=<?= $movie['id'] ?>" class="btn-choose-session">Обрати сеанс</a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
<footer>
    <a class="email" href="mailto:cinema@gmail.com">cinema@gmail.com</a>
    <a class="phone" href="tel:380123456780">+380 12 345 6789</a>
    <p>&copy; <?= date('Y') ?> Cinema DB. Усі права захищені.</p>
</footer>
</body>
</html>