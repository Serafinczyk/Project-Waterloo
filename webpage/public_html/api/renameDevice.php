<?php
    session_start();
    require_once "../connect.php";
    if (!isset($_SESSION['loggedin'],$_SESSION['id'])) {
     http_response_code(401);
     echo json_encode("Brak uprawnień");
     exit();
    } 

    $data = json_decode(file_get_contents('php://input'), true);
    if (!(isset($data['name']) && isset($data['id']) && is_numeric($data['id']))) {
        http_response_code(400); 
        echo json_encode("Błędne zapytanie"); 
        exit();
    }

    $name = $data['name'];

    $connection = @new mysqli($db_server, $db_user, $db_password, $db_name);
	
	$name = htmlentities($data['name'],ENT_HTML5 | ENT_QUOTES, "UTF-8");
	
    if ($connection -> connect_errno != 0)
    {
        http_response_code(500);// http serwer error
        echo json_encode("Błąd serwera");
        exit();
    }

    $query = sprintf("SELECT id FROM devices WHERE userID=%d AND deviceID=%d", $_SESSION['id'],$data['id']);
    if (!($result = @$connection -> query($query))){
        http_response_code(500);
        echo json_encode("Błąd serwera");
        exit();
    }
    $isDevice = $result -> num_rows;
    if($isDevice!=1){
        http_response_code(403);//Forbidden
        echo json_encode("Zabronione");
        exit();
    }

    $query = sprintf('SELECT `id` FROM `devices` WHERE `userID`="%d" AND `name`="%s"', $_SESSION['id'], $name);
    if(!($response = @$connection->query($query))){
        http_response_code(500);// http serwer error
        echo json_encode("Błąd serwera");
        exit();
    }
    if($response->num_rows!=0){
        http_response_code(400);
        echo json_encode("Ta nazwa jest już zajęta!");
        exit();
    }

    $nameChanger = sprintf('UPDATE devices SET name="%s" WHERE userID=%d AND deviceID=%d',$name, $_SESSION['id'],$data['id']);
    if (!(@$connection -> query($nameChanger))){
        http_response_code(500);
        echo json_encode("Błąd serwera");
        exit();
    }

    echo json_encode("Wykonano pomyślnie");
    $connection -> close();
?>







    
    

    

        

        


            
                
               