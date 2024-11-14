<?php
    session_start();

    if (!isset($_POST['login']) || !isset($_POST['password']))
    {
        header('Location: /login');
		
        exit();
    }

    require_once "../connect.php";
    $connection = @new mysqli($db_server, $db_user, $db_password, $db_name);

    if ($connection -> connect_errno != 0)
    {
        echo "<b>Error: </b>" . $connection -> connect_errno;
    }
    else
    {
        $login = $_POST['login'];
        $password = $_POST['password'];

        if (!ctype_alnum($login)) {
			$_SESSION['error_msg'] = "Nieprawidłowy login lub hasło.";
			header('Location: /login');
			exit();
		}

        $query = sprintf("SELECT * FROM users WHERE login = '%s'", $login);

        if ($result = @$connection -> query($query))
        {
            $users_count = $result -> num_rows;

            if ($users_count > 0)
            {
                $row = $result -> fetch_assoc();

                if (password_verify($password, $row['password']))
                {
                    $_SESSION['loggedin'] = true;

                    $_SESSION['id'] = $row['id'];
                    $_SESSION['login'] = $row['login'];
                    $_SESSION['email'] = $row['email'];
					$_SESSION['phone'] = $row['phone'];
                    unset($_SESSION['error_msg']);

                    $result -> free_result();
                    header('Location: /dashboard');
                }
                else
                {
                    $_SESSION['error_msg'] = "Nieprawidłowy login lub hasło.";
                    header('Location: /login');
                }
            }
            else
            {
                $_SESSION['error_msg'] = "Nieprawidłowy login lub hasło.";
                header('Location: /login');
            }
        }

        $connection -> close();
    }
?>