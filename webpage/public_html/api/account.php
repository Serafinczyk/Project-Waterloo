<?php

	session_start();
	require_once "../connect.php";
	    
	if (!isset($_SESSION['loggedin'],$_SESSION['id'])) {
     http_response_code(401);
	 echo json_encode("Brak uprawnień");
	 exit();
	}
	
	if($_SERVER['REQUEST_METHOD']=='GET'){
		$contact['email']=$_SESSION['email'];
		$contact['phone']=$_SESSION['phone'];
		echo json_encode($contact);
		exit();
	}
	
	if($_SERVER['REQUEST_METHOD']=='POST'){
		$contact = json_decode(file_get_contents('php://input'), true);
		$connection = @new mysqli($db_server, $db_user, $db_password, $db_name);
		
		if ($connection->connect_errno!=0){
			http_response_code(500);// http serwer error
			echo json_encode("Błąd serwera");
			exit();
		}
		
		$allOK = false;
		
		if(isset($contact['email'])){
			$allOK = true;
			$email = $contact['email'];
			$emailSanitized = filter_var($email, FILTER_SANITIZE_EMAIL);
			if (!filter_var($emailSanitized, FILTER_VALIDATE_EMAIL) || $emailSanitized != $email) {
				$isOK = false;
			}else{
				$check = sprintf("UPDATE `users` SET `email`='%s' WHERE id=%d", $emailSanitized, $_SESSION['id']);

				if(!($response = @$connection->query($check))){

					$connection->close();

					http_response_code(500);// http serwer error

					echo "Błąd serwera.";

					exit();

				}
			}
		}
		
		if(isset($contact['phone'])){
			$allOK = true;
			$valid_number = filter_var($contact['phone'], FILTER_SANITIZE_NUMBER_INT);
			if ($valid_number != $contact['phone']) {
				$isOK = false;
			}else{
				$check = sprintf("UPDATE `users` SET `phone`='%s' WHERE id=%d", $valid_number, $_SESSION['id']);

				if(!($response = @$connection->query($check))){

					$connection->close();

					http_response_code(500);// http serwer error

					echo "Błąd serwera.";

					exit();

				}
			}
		}
		
		if(isset($contact['password'])){
			$allOK = true;
			$hashed = password_hash($contact['password'], PASSWORD_DEFAULT);
			$check = sprintf("UPDATE `users` SET `password`='%s' WHERE id=%d", $hashed, $id);

			if(!($response = @$connection->query($check))){

				$connection->close();

				http_response_code(500);// http serwer error

				echo "Błąd serwera.";

				exit();

			}
			
		}
		
		
		
		$connection->close();
		if($allOK){
			http_response_code(400); 
			echo json_encode("Błędne zapytanie");
		}else{
			echo json_encode("Wykonano pomyślnie");
		}
		exit();
		
		
	}
	
	http_response_code(405);
	echo json_encode("Metoda nie dozwolona");
	
?>