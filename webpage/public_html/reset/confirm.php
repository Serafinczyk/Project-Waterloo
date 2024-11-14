<?php





	require_once "../connect.php";

	

		echo ' <!DOCTYPE html>

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

        </div>';

	

	function linkErr (){

		http_response_code(400); // http serwer error

		echo '<p class="reset__info reset__info--error"><span class="icon">error</span>Podany link jest niepoprawny.</p>';

		echo '<div class="reset__buttons reset__buttons--space"><a href="/login" class="button"><span class="icon" aria-hidden="true">undo</span>Powrót do logowania</a></div>';

		echo '</div></body></html>';

		exit();

	}

	

	if (!isset($_GET['id']) || !isset($_GET['pass']))

    {

        linkErr ();

    }

	

	if (!is_numeric($_GET['id']) || !ctype_alnum($_GET['pass'])){

		linkErr ();

	}

	

	$id = $_GET['id'];

	$pass = $_GET['pass'];

	

	$connection = @new mysqli($db_server, $db_user, $db_password, $db_name);

		

	if ($connection->connect_errno!=0){

		http_response_code(500);// http serwer error

		echo "Błąd serwera.";

		exit();

	}

	

	$check = sprintf("SELECT code FROM verlink WHERE userID=%d", $id);

	if(!($response = @$connection->query($check))){

		$connection->close();

		http_response_code(500);// http serwer error

		echo "Błąd serwera.";

		exit();

	}

		

	if($response->num_rows==0){

		$connection->close();

		linkErr ();

	}

		

	$row = $response-> fetch_assoc();

	$code = $row['code'];

	if(!password_verify($pass, $code)){

		$connection->close();

		linkErr ();

	}

	

	$completed = false;

	$err ="";

	

	if($_SERVER['REQUEST_METHOD']=="POST"){

		if(isset($_POST['password']) && isset($_POST['conf-password'])){

			$completed = true;

			$password = $_POST['password'];

			

			 if (strlen($password) < 8) {

				$completed = false;

				$err = "Hasło musi posiadać minimum 8 znaków.";

			 }

			 

			 if ($password != $_POST['conf-password']){

				$completed = false;

				$err = "Podane hasła nie są identyczne.";

			 }

			 

			 if($completed){

				$hashed = password_hash($password, PASSWORD_DEFAULT);

				

				$check = sprintf("UPDATE `users` SET `password`='%s' WHERE id=%d", $hashed, $id);

				if(!($response = @$connection->query($check))){

					$connection->close();

					http_response_code(500);// http serwer error

					echo "Błąd serwera.";

					exit();

				}

				

				$check = sprintf("DELETE FROM `verlink` WHERE userID=%d", $id);

				if(!($response = @$connection->query($check))){

					$connection->close();

					http_response_code(500);// http serwer error

					echo "Błąd serwera.";

					exit();

				}

				

			 }

			

		}

	}

	

	$connection -> close();

	



	

	if($completed){

		echo '<p class="reset__info"><span class="icon">info</span>Procedura zmiany hasła zakończona.</p>';

		echo '<div class="reset__buttons reset__buttons--space"><a href="/login" class="button"><span class="icon" aria-hidden="true">undo</span>Powrót do logowania</a></div>';

		echo '</div></body></html>';

		exit();

	}

?>

        <form action="/reset/confirm.php?<?php echo "id=$id&pass=$pass" ?>" method="POST" class="reset__form">



            <div class="input">

                <label for="password" class="input__label">

                    <span class="icon" aria-hidden="true">lock</span>Hasło

                </label>

                <input type="password" id="password" name="password" class="input__input">

            </div>

			

			

			<?php

                if ($err!="")

                {

                    echo '<p class="reset__info reset__info--error"><span class="icon">error</span>'.$err.'</p>';

                }

            ?>

			

			

			<div class="input">

                <label for="conf-password" class="input__label">

                    <span class="icon" aria-hidden="true">lock</span>Potwierdź hasło

                </label>

                <input type="password" id="conf-password" name="conf-password" class="input__input">

            </div>

			

            <div class="reset__buttons">



                <button class="button">

                    <span class="icon" aria-hidden="true">done</span>Zresetuj hasło

                </button>



            </div>

            

        </form>

    </div>

</body>

</html>

