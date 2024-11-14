<?php
	
	//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	require_once "../connect.php";
	if (!(isset($_POST['chipID']) and is_numeric($_POST['chipID']))){
		echo "Not Authorized";
		exit();
	}
	$chipID = $_POST['chipID'];
	
	if(!isset($_POST['flow'],$_POST['counter'],$_POST['dateAndTime'])){
		echo "Data not send at all";
		exit();
	}

	$flow = $_POST['flow'];
	$counter = $_POST['counter'];
	$dateAndTime = $_POST['dateAndTime'];
	if(!(is_numeric($flow) and is_numeric($counter))){ // frame validation
		echo "Data corupted";
		exit();
	}
	
	
	$dateAndTimeO = DateTime::createFromFormat('Y-m-d H:i:s',$dateAndTime);
	if(!$dateAndTimeO){
		echo "Incorrect Date/Time format";
		exit();
	}
	if($dateAndTimeO->format('Y-m-d H:i:s')!=$dateAndTime){
		echo "Incorrect Date/Time format";
		exit();
	}
	$connection = @new mysqli($db_server,$db_user,$db_password,$db_name);
							
	if ($connection->connect_errno!=0){
		echo "DB connection error";
		$connection->close();
		exit();
	}
	
	$date = $dateAndTimeO->format('Y-m-d');
	$time = $dateAndTimeO->format('H:i:s');
	
	if(!($response = @$connection->query("SELECT id FROM `$chipID` WHERE date='$date' AND time='$time';"))){// check for duplicates
		echo "DB error 1";
		$connection->close();
		exit();
	}
	$rowsNum = $response->num_rows;
	if($rowsNum!=0){
		echo "Same row is already in DB";
		$connection->close();
		exit();
	}
	
	if(!(@$connection->query("INSERT INTO `$chipID` VALUES (NULL, '$date', '$time', $counter, $flow );"))){// inserting values
		echo "DB error 2";
		$connection->close();
		exit();
	}
		
		
		if(!($newResponse = @$connection->query("SELECT Sunday, Monday, Tuesday, Wednesday, Thursday, Friday, Saturday FROM `limits` WHERE deviceID = $chipID;"))){

		echo "DB Error 3";

		$connection->close();

		exit();

	}

	if($newResponse->num_rows!=1){

		echo "DB Empty";

		$connection->close();

		exit();

	}

	$limits = $newResponse-> fetch_row();

	$txt = "*";
	foreach($limits as $col){

		$txt= sprintf("$txt%032b\n", $col);

	}
	echo $txt;	
	$connection->close();
						



?>