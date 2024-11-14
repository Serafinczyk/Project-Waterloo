<?php
    session_start();

    if (isset($_SESSION['loggedin']) || !isset($_POST['login']) || !isset($_POST['email']) || !isset($_POST['password']) || !isset($_POST['conf-password']))
    {
        header('Location: /register');
        exit();
    }

    $isOK = true;

    // Login validation

    $login = $_POST['login'];

    if (!ctype_alnum($login)) {
        $isOK = false;
        $_SESSION['e_login'] = "Login może składać się tylko z liter i cyfr.";
    }

    if (strlen($login) < 3 || strlen($login) > 20) {
        $isOK = false;
        $_SESSION['e_login'] = "Login musi posiadać od 3 do 20 znaków.";
    }

    // E-mail validation

    $email = $_POST['email'];
    $emailSanitized = filter_var($email, FILTER_SANITIZE_EMAIL);

    if (!filter_var($emailSanitized, FILTER_VALIDATE_EMAIL) || $emailSanitized != $email) {
        $isOK = false;
        $_SESSION['e_email'] = "Niepoprawny adres e-mail.";
    }

    // Password validation

    $pass = $_POST['password'];
    $pass2 = $_POST['conf-password'];

    if (strlen($pass) < 8) {
        $isOK = false;
        $_SESSION['e_pass'] = "Hasło musi posiadać minimum 8 znaków.";
    }

    if ($pass != $pass2) {
        $isOK = false;
        $_SESSION['e_pass'] = "Podane hasła nie są identyczne.";
    }

    $passHash = password_hash($pass, PASSWORD_DEFAULT);

    // Login & email duplicates validation

    require_once "../connect.php";

    try {
        $connection = new mysqli($db_server, $db_user, $db_password, $db_name);

        if ($connection -> connect_errno != 0)
        {
			$connection -> close();
            throw new Exception(mysqli_connect_errno());
        }

        // Login

        $result = $connection -> query("SELECT id FROM users WHERE login='$login'");

        if (!$result) throw new Exception($connection -> error);

        if ($result -> num_rows > 0) {
            $isOK = false;
            $_SESSION['e_login'] = "Podany login jest już zajęty.";
        }

        // E-mail

        $result = $connection -> query("SELECT id FROM users WHERE email='$email'");

        if (!$result){
			$connection -> close();
			throw new Exception($connection -> error);
		}

        if ($result -> num_rows > 0) {
            $isOK = false;
            $_SESSION['e_email'] = "Podany e-mail jest już zajęty.";
        }

        // Check if everything's okay

        if (!$isOK) {
            header('Location: /register');
			$connection -> close();
            exit();
        }

        // Add user to database
    
        $result = $connection -> query("INSERT INTO users VALUES (NULL, '$login', '$passHash', '$email', '',0)");

        if (!$result){
			$connection -> close();
			throw new Exception($connection -> error);
		}

        $_SESSION['registered'] = true;
        header('Location: /login');

        $connection -> close();
    }
    catch(Exception $exception) {
		http_response_code(500);
        echo "Błąd serwera.";
    }
?>