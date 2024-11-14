<?php
	session_start();

    if (isset($_SESSION['loggedin'])) {
       header("Location: /dashboard");
	   exit(); 
    }
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Project Waterloo &ndash; Rejestracja</title>

    <link rel="icon" href="/public/img/water-drop.svg">
    <link rel="stylesheet" href="/public/css/index.css">
    <link rel="stylesheet" href="/register/css/index.css">
</head>
<body>
    <div class="register">
        <div class="register__header">
            <img src="/public/img/water-drop.svg" alt="logo" class="register__logo">
        </div>
        <form action="/register/register.php" method="POST" class="register__form">

            <div class="input">
                <label for="login" class="input__label">
                    <span class="icon" aria-hidden="true">person</span>Login
                </label>
                <input type="text" id="login" name="login" class="input__input">
            </div>

            <?php
                if (isset($_SESSION['e_login']))
                {
                    echo '<p class="register__error"><span class="icon">error</span>'.$_SESSION['e_login'].'</p>';
                    unset($_SESSION['e_login']);
                }
            ?>

            <div class="input">
                <label for="email" class="input__label">
                    <span class="icon" aria-hidden="true">email</span>E-mail
                </label>
                <input type="text" id="email" name="email" class="input__input">
            </div>

            <?php
                if (isset($_SESSION['e_email']))
                {
                    echo '<p class="register__error"><span class="icon">error</span>'.$_SESSION['e_email'].'</p>';
                    unset($_SESSION['e_email']);
                }
            ?>

            <div class="input">
                <label for="password" class="input__label">
                    <span class="icon" aria-hidden="true">lock</span>Hasło
                </label>
                <input type="password" id="password" name="password" class="input__input">
            </div>

            <?php
                if (isset($_SESSION['e_pass']))
                {
                    echo '<p class="register__error"><span class="icon">error</span>'.$_SESSION['e_pass'].'</p>';
                    unset($_SESSION['e_pass']);
                }
            ?>

            <div class="input">
                <label for="conf-password" class="input__label">
                    <span class="icon" aria-hidden="true">lock</span>Potwierdź hasło
                </label>
                <input type="password" id="conf-password" name="conf-password" class="input__input">
            </div>

            <div class="register__buttons">

                <button class="button">
                    <span class="icon" aria-hidden="true">person_add</span>Załóż konto
                </button>

                <a href="/login" class="button button--outlined">
                    <span class="icon" aria-hidden="true">undo</span>Powrót
                </a>

            </div>
            
        </form>
    </div>
</body>
</html>