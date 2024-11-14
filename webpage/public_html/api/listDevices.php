<?php
    session_start();
    require_once "../connect.php";
    if (!isset($_SESSION['loggedin'],$_SESSION['id'])) {
		http_response_code(401);
        echo json_encode("Brak uprawnień");
		exit();
    }
	
	$connection = @new mysqli($db_server, $db_user, $db_password, $db_name);
    if ($connection -> connect_errno != 0) {
        http_response_code(500);// http serwer error
		echo json_encode("Błąd serwera");
		exit();
    }
    
	$query = sprintf("SELECT deviceID, name, emailNotify, SMSNotify FROM devices WHERE userID=%d", $_SESSION['id']);
    if ($result = @$connection -> query($query)){
        $deviceList = [];
        $deviceCount = $result -> num_rows;
        for($i = 0; $i < $deviceCount; $i++){
			$row = $result -> fetch_assoc();
			$row['name'] = html_entity_decode($row['name'],ENT_HTML5 | ENT_QUOTES, "UTF-8");
            $deviceList[$i] = $row;
        }
		echo json_encode($deviceList);
    }else{
        http_response_code(500);
		echo json_encode("Błąd serwera");
    }
    $connection -> close();

?>