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

    <title>Project Waterloo &ndash; Logowanie</title>

    <link rel="icon" href="/public/img/water-drop.svg">
    <link rel="stylesheet" href="/public/css/index.css">
    <link rel="stylesheet" href="/login/css/index.css">
</head>
<body>
    <div class="login">
        <div class="login__header">
            <img src="/public/img/water-drop.svg" alt="logo" class="login__logo">
        </div>
        <form action="/login/login.php" method="POST" class="login__form">

            <div class="input">
                <label for="login" class="input__label">
                    <span class="icon" aria-hidden="true">person</span>Login
                </label>
                <input type="text" id="login" name="login" class="input__input">
            </div>

            <div class="input">
                <label for="password" class="input__label">
                    <span class="icon" aria-hidden="true">lock</span>Hasło
                </label>
                <input type="password" id="password" name="password" class="input__input">
            </div>

            <?php
                if (isset($_SESSION['error_msg']))
                {
                    echo '<p class="login__info login__info--error"><span class="icon">error</span>'.$_SESSION['error_msg'].'</p>';
                    unset($_SESSION['error_msg']);
                }
                
                if (isset($_SESSION['registered'])) {
                    echo '<p class="login__info"><span class="icon">info</span>Rejestracja zakończona pomyślnie. Możesz już zalogować się na swoje konto.</p>';
                    unset($_SESSION['registered']);
                }

                if (isset($_SESSION['passreset'])) {
                    echo '<p class="login__info"><span class="icon">info</span>Wysłaliśmy na podanego e-maila wiadomość z instrukcjami dotyczącymi resetu hasła.</p>';
                    unset($_SESSION['passreset']);
                }
            ?>

            <a href="/reset" class="login__reset-pass">Nie pamiętasz hasła?</a>

            <div class="login__buttons">

                <button class="button">
                    <span class="icon" aria-hidden="true">login</span>Zaloguj się
                </button>

                <a href="/register" class="button button--outlined">
                    <span class="icon" aria-hidden="true">person_add</span>Załóż konto
                </a>

            </div>
            
        </form>
    </div>
</body>
</html>