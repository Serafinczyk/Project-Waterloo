<?php
	session_start();
    require_once "../connect.php";
	
	function isbool($a){
		if(is_numeric($a) and ($a==0 or $a==1)){
			return true;
		}else{
			return false;
		}
	}
	

    if (!isset($_SESSION['loggedin'],$_SESSION['id'])) {
     http_response_code(401);
     echo json_encode("Brak uprawnień");
     exit();
    } 
	 $data = json_decode(file_get_contents('php://input'), true);
	 
	 if(!is_array($data)){
		http_response_code(400);   
		echo json_encode("Błędne zapytanie");
		exit();
	 }
	 
	 $connection = @new mysqli($db_server, $db_user, $db_password, $db_name);
	 if ($connection->connect_errno!=0){

        http_response_code(500);// http serwer error

        echo json_encode("Błąd serwera");

        exit();

    }
	 
	 
	 
	 
	 foreach ($data as $dev){
		if(!(is_numeric($dev['deviceID']) and isbool($dev['SMSNotify']) and isbool($dev['emailNotify']))){
			http_response_code(400);   
			echo json_encode("Błędne zapytanie");
			$connection->close();
			exit();
			
		}	
		
		$query = sprintf("SELECT id FROM devices WHERE userID=%d AND deviceID=%d", $_SESSION['id'],$dev['deviceID']);
		if (!($result = @$connection -> query($query))){
            http_response_code(500);
			$connection->close();
            exit();
        }
        $isDevice = $result -> num_rows;
        if($isDevice!=1){
            http_response_code(403);//Forbidden
            echo json_encode("Zabronione");
			$connection->close();
            exit();
        }
		
		$alertChanger = sprintf('UPDATE devices SET emailNotify="%d", SMSNotify="%d" WHERE userID=%d AND deviceID=%d',$dev['emailNotify'],$dev['SMSNotify'], $_SESSION['id'],$dev['deviceID']);
		if (!(@$connection -> query($alertChanger))){
			http_response_code(500);
			echo json_encode("Błąd serwera");
			$connection->close();
			exit();
		}
		
		
	 }
	echo json_encode("Wykonano pomyślnie");
	$connection -> close();
	
	 
	
	
	

?>
	