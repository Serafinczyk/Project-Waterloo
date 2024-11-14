<?php
    session_start();
    require_once "../connect.php";
    if (!isset($_SESSION['loggedin'],$_SESSION['id'])) {
        http_response_code(401);
        echo json_encode("Brak uprawnień");
        exit();
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!(isset($data['id']) && is_numeric($data['id']))) {
        http_response_code(400);
        echo json_encode("Błędne zapytanie");
        exit();
    }

    $connection = @new mysqli($db_server, $db_user, $db_password, $db_name);
    if ($connection -> connect_errno != 0)
    {
        http_response_code(500);// http serwer error
        echo json_encode("Błąd serwera");
        exit();
    }


    $query = sprintf("SELECT id FROM devices WHERE userID=%d AND deviceID=%d", $_SESSION['id'],$data['id']);
    if (!($result = @$connection -> query($query))){
        http_response_code(500);
        exit();
    }
    $isDevice = $result -> num_rows;
    if($isDevice!=1){
        http_response_code(403);//Forbidden
        echo json_encode("Zabronione");
        exit();
    }

    $remover = sprintf('DELETE FROM devices WHERE userID=%d AND deviceID=%d',$_SESSION['id'],$data['id']);
    if (!(@$connection -> query($remover))){
        http_response_code(500);
        echo json_encode("Błąd serwera");
        exit();
    }
	
	$remover = sprintf('DELETE FROM limits WHERE deviceID=%d',$data['id']);
    if (!(@$connection -> query($remover))){
        http_response_code(500);
        echo json_encode("Błąd serwera");
        exit();
    }

    $remover = sprintf('DROP TABLE `%d`',$data['id']);
    if (!(@$connection -> query($remover))){
        http_response_code(500);
        echo json_encode("Błąd serwera");
        exit();
    }


    echo json_encode("Wykonano pomyślnie");
    $connection -> close();
?>






        





        

                
              
                

                
                    


                    
                        

                        
