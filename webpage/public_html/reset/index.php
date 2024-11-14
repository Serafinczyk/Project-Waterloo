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

    <title>Project Waterloo &ndash; Odzyskiwanie konta</title>

    <link rel="icon" href="/public/img/water-drop.svg">
    <link rel="stylesheet" href="/public/css/index.css">
    <link rel="stylesheet" href="/reset/css/index.css">
</head>
<body>
    <div class="reset">
        <div class="reset__header">
            <img src="/public/img/water-drop.svg" alt="logo" class="reset__logo">
        </div>
        <form action="/reset/reset.php" method="POST" class="reset__form">

            <div class="input">
                <label for="email" class="input__label">
                    <span class="icon" aria-hidden="true">email</span>E-mail
                </label>
                <input type="text" id="email" name="email" class="input__input">
            </div>
			
			<?php
                if (isset($_SESSION['e_email']))
                {
                    echo '<p class="reset__info reset__info--error"><span class="icon">error</span>'.$_SESSION['e_email'].'</p>';
                    unset($_SESSION['e_email']);
                }
            ?>

            <div class="reset__buttons">

                <button class="button">
                    <span class="icon" aria-hidden="true">done</span>Zresetuj hasło
                </button>

                <a href="/login" class="button button--outlined">
                    <span class="icon" aria-hidden="true">undo</span>Powrót
                </a>

            </div>
            
        </form>
    </div>
</body>
</html>