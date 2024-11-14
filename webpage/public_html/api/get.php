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
		$connection->close();
		exit();
	}
	if(!($response = @$connection->query("SHOW TABLES LIKE '$chipID';"))){
		echo "DB Error";
		$connection->close();
		exit();
	}
	if($response->num_rows!=1){
		echo "Not Authorized";
		$connection->close();
		exit();
	}
	if(!($newResponse = @$connection->query("SELECT counter FROM `$chipID` ORDER BY id DESC LIMIT 1;"))){
		echo "DB Error1";
		$connection->close();
		exit();
	}
	if($newResponse->num_rows!=1){
		echo "DB Empty";
		$connection->close();
		exit();
	}
	
	$row = $newResponse-> fetch_assoc();
	
	
	if(!($newResponse = @$connection->query("SELECT Sunday, Monday, Tuesday, Wednesday, Thursday, Friday, Saturday FROM `limits` WHERE deviceID = $chipID;"))){
		echo "DB Error2";
		$connection->close();
		exit();
	}
	if($newResponse->num_rows!=1){
		echo "DB Empty";
		$connection->close();
		exit();
	}
	$limits = $newResponse-> fetch_row();
	$txt = sprintf("*%0.5f\n", $row['counter']);
	foreach($limits as $col){
		$txt= sprintf("$txt%032b\n", $col);
	}
	echo $txt;
	

	$connection->close();
?>