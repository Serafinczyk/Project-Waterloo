<?php

    session_start();

    require_once "../connect.php";

    if (!isset($_SESSION['loggedin'],$_SESSION['id'])) {

        http_response_code(401);

        echo json_encode("Brak uprawnień");

        exit();

    }



    $data = json_decode(file_get_contents('php://input'), true);

    if (!(isset($data['id'])&&isset($data['name'])&&is_numeric($data['id']))) {

        http_response_code(400);   

        echo json_encode("Błędne zapytanie");

        exit();

    }



    



    

    $connection = @new mysqli($db_server, $db_user, $db_password, $db_name);

	

	$name = htmlentities($data['name'],ENT_HTML5 | ENT_QUOTES, "UTF-8");

	

    if ($connection->connect_errno!=0){

        http_response_code(500);// http serwer error

        echo json_encode("Błąd serwera");

        exit();

    }



    $query = sprintf("SHOW TABLES LIKE '%d';", $data['id']);

    if(!($response = @$connection->query($query))){

        http_response_code(500);// http serwer error

        echo json_encode("Błąd serwera");
		$connection->close();
        exit();

    }

    if($response->num_rows!=0){

        http_response_code(400);

        echo json_encode("To urządzenie już istnieje!");
		$connection->close();
        exit();

    }



    $query = sprintf('SELECT `id` FROM `devices` WHERE `userID`="%d" AND `name`="%s"', $_SESSION['id'], $name);

    if(!($response = @$connection->query($query))){

        http_response_code(500);// http serwer error

        echo json_encode("Błąd serwera");
		$connection->close();
        exit();

    }

    if($response->num_rows!=0){

        http_response_code(400);

        echo json_encode("Ta nazwa jest już zajęta!");
		$connection->close();
        exit();

    }


    $query = sprintf("INSERT INTO `devices` (`id`, `deviceID`, `userID`, `name`, `emailNotify`, `SMSNotify`) VALUES (NULL, '%d', '%d', '%s', 1, 0);", $data['id'],$_SESSION['id'],$name);

    if(!($response = @$connection->query($query))){

        http_response_code(500);// http serwer error

        echo json_encode("Błąd serwera");
		echo json_encode($query);
		$connection->close();
        exit();

    }



    

    $query = sprintf("CREATE TABLE `%d` ( `id` int(11) AUTO_INCREMENT PRIMARY KEY, `date` DATE NOT NULL, `time` TIME NOT NULL ,  `counter` double NOT NULL, `flow_per_h` float NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $data['id']);

    if(!($response = @$connection->query($query))){

        http_response_code(500);// http serwer error

        echo json_encode("Błąd serwera");
		$connection->close();
        exit();

    }





    $query = sprintf("INSERT INTO `%d` (`id`, `date`, `time`, `counter`, `flow_per_h`) VALUES (NULL, '%s', '%s', 0, 0)", $data['id'],gmdate('Y-m-d'), gmdate('H:i:s'));

    if(!($response = @$connection->query($query))){

        http_response_code(500);// http serwer error

        echo json_encode("Błąd serwera");
		$connection->close();
        exit();

    }

  $query = sprintf("INSERT INTO `limits` (`deviceID`, `Sunday`, `Monday`, `Tuesday`, `Wednesday`, `Thursday`, `Friday`, `Saturday`) VALUES ('%s', 0, 0, 0, 0, 0, 0, 0)",$data['id']);

    if(!(@$response = $connection->query($query))){

        http_response_code(500);// http serwer error

        echo json_encode("Błąd serwera");
		$connection->close();
        exit();

    }

    





    $connection->close();

    echo json_encode("Wykonano pomyślnie");

?>