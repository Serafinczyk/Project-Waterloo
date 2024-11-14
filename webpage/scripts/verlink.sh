#!/usr/local/bin/php82

<?php
require_once "../public_html/connect.php";

$connection = @new mysqli($db_server, $db_user, $db_password, $db_name);

if ($connection->connect_errno!=0){
			echo "Błąd serwera.";
			exit();
}

$TIME = new DateTime('now');
$TIME->modify('-1 day');


$check = sprintf("DELETE FROM `verlink` WHERE `creation time`< '%s' ",$TIME->format('Y-m-d H:i:s'));
	if(!($response = @$connection->query($check))){
		$connection->close();
		echo "Błąd serwera.";
		exit();
	}
	
$connection->close();
echo "Zadanie wykonane pomyślnie. Wpisy starsze niż jeden dzień zostały usunięte.";

exit();
?>