<?php
    session_start();

    if (!isset($_SESSION['loggedin'])) {
        header('Location: ../index.php');
        exit();
    }
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Project Waterloo &ndash; Dashboard</title>

    <link rel="icon" href="/public/img/water-drop.svg">
    <link rel="stylesheet" href="/public/css/index.css">
    <link rel="stylesheet" href="/dashboard/css/index.css">
</head>
<body>
    <div class="dashboard">
        <nav class="nav" id="nav">
            <div class="nav__header">
                <button class="nav__expand" id="nav-expand-btn">
                    <span class="nav__expand-icon"></span>
                </button>
                <img src="/public/img/water-drop.svg" alt="logo" class="nav__logo">
            </div>
            <ul class="nav__list">
                <li class="nav__item">
                    <a href="#/" class="nav__link">
                        <span class="icon" aria-hidden="true">home</span>Start
                    </a>
                </li>
                <li class="nav__item">
                    <a href="#/devices" class="nav__link">
                        <span class="icon" aria-hidden="true">gas_meter</span>Urządzenia
                    </a>
                </li>
                <li class="nav__item">
                    <a href="#/settings" class="nav__link">
                        <span class="icon" aria-hidden="true">settings</span>Ustawienia
                    </a>
                </li>
                <li class="nav__item">
                    <a href="/login/logout.php" class="button">
                        <span class="icon" aria-hidden="true">logout</span>Wyloguj się
                    </a>
                </li>
            </ul>
        </nav>
        <div class="dashboard__root" id="root"></div>
    </div>

    <script src="/public/js/chart.min.js"></script>
    <script src="/public/js/moment.js"></script>

    <script src="/public/js/device-api.js"></script>
    <script src="/public/js/elements.js"></script>
    <script src="/public/js/popups.js"></script>
    <script src="/public/js/router.js"></script>
    <script src="/public/js/user-api.js"></script>

    <script src="/dashboard/routes/start.js"></script>
    <script src="/dashboard/routes/devices.js"></script>
    <script src="/dashboard/routes/settings.js"></script>

    <script>
        const router = new Router({
            "/": {
                action: startRoute,
            },
            "/devices": {
                action: devicesRoute,
            },
            "/settings": {
                action: settingsRoute,
            },
            "404": {
                action: (root) => {
                    root.innerText = "404";
                },
            },
        });
    </script>

    <script>
        document.querySelector("#nav-expand-btn").addEventListener("click", () => {
            document.querySelector("#nav").classList.toggle("nav--expanded");
        });

        document.querySelectorAll(".nav__item").forEach(item => {
            item.addEventListener("click", () => {
                document.querySelector("#nav").classList.toggle("nav--expanded");
            });
        });
    </script>
</body>
</html>