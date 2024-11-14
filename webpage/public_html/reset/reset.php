<?php

	function abort(){
		header('Location: /reset');
        exit();
	}
	
	session_start();
	require_once "../connect.php";
	
	
	if (isset($_SESSION['loggedin']) || !isset($_POST['email']))
    {
        abort();
    }
	
	$email = $_POST['email'];
    $emailSanitized = filter_var($email, FILTER_SANITIZE_EMAIL);

    if (!filter_var($emailSanitized, FILTER_VALIDATE_EMAIL) || $emailSanitized != $email) {
        $_SESSION['e_email'] = "Niepoprawny adres e-mail.";
		abort();
    }
	
	$connection = @new mysqli($db_server, $db_user, $db_password, $db_name);
	
	if ($connection->connect_errno!=0){
			http_response_code(500);// http serwer error
			echo "Błąd serwera.";
			exit();
	}
	
	//Check for user acount
	$check = sprintf("SELECT id, login FROM users WHERE email='%s'", $emailSanitized);
	if(!($response = @$connection->query($check))){
		$connection->close();
		http_response_code(500);// http serwer error
		echo "Błąd serwera.";
		exit();
	}
		
	if($response->num_rows==0){
		$_SESSION['e_email'] = "Brak konta o podanym adresie.";
		$connection->close();
		abort();
	}
	
	$row = $response-> fetch_assoc();
	$userID = $row['id'];
	$login = $row['login'];
	$code = bin2hex(random_bytes(20));
	$link = "https://waterloo.ct8.pl/reset/confirm.php?id=$userID&pass=$code";
	$message = "Witaj $login,
Otrzymaliśmy właśnie prośbę o zresetowanie hasła.
Jeśli to ty naciśnij w link na końcu wiadomości, aby zresetować hasło. Jeśli tego nie zrobisz link wygaśnie w ciągu 24h.
Jeżeli to nie ty, zignoruj tą wiadomość.
Link: $link
Pozdrawiamy
Ekipa projektu Waterloo";
	
	if(!mail($emailSanitized,"Project Waterloo - reset hasła",$message)){
			$connection->close();
			http_response_code(500);// http serwer error
			echo "Błąd serwera poczty";
			exit();
	}
	
	$check = sprintf("INSERT INTO verlink (`userID`, `code`) VALUES (%d,'%s')",$userID,password_hash($code, PASSWORD_DEFAULT));
	if(!($response = @$connection->query($check))){
		$connection->close();
		http_response_code(500);// http serwer error
		echo "Błąd serwera.";
		exit();
	}
	
	$_SESSION['passreset'] = true;
    header('Location: /login');
    $connection -> close();
	
	
	
?>