<?php
	require_once "../connect.php";
if (!(isset($_POST['chipID']) and is_numeric($_POST['chipID']))){
		echo "Not Authorized";
		exit();
	}


	$chipID = $_POST['chipID'];
	$connection = @new mysqli($db_server,$db_user,$db_password,$db_name);
	if ($connection->connect_errno!=0){
		echo "DB connection error";
		exit();
	}
	
	$query = sprintf("SELECT devices.name, devices.emailNotify, devices.SMSNotify, users.email, users.phone, users.login FROM devices, users WHERE devices.userID=users.id AND devices.deviceID=%d", $chipID);
	if(!($response = @$connection->query($query))){
		echo "DB error1";
		exit();
    }
	$rowsNum = $response->num_rows;
	if($rowsNum==0){
		echo "Not Authorized";
		exit();
	}
	if($rowsNum>1){
		echo "DB error2";
		exit();
	}
	$row = $response-> fetch_assoc();
	$device = html_entity_decode($row['name'],ENT_HTML5 | ENT_QUOTES, "UTF-8");
	$login = $row['login'];
	$message = "Witaj $login,
Twoje urządzenie przedchwilą wykryło niezaplanowane zurzycie wody.
Ze względów bezpieczeństwa zalecamy sprawdzenie instalacji wodnej.
Pozdrawiamy
Ekipa projektu Waterloo";
	if($row['emailNotify'] and $row['email']!=NULL){
		
		if(!mail($row['email'],"Project Waterloo - wykryto incydent",$message)){
			echo "Mail Eror!";
			exit();
		}
	}
	
	if($row['SMSNotify'] and $row['phone']!=NULL){
		if(0){
			echo "SMS Eror!";
			exit();
		}
	}
	
	echo "OK";
	
	
?>