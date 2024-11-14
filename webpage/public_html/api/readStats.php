<?php
/*
 jeżeli zamiast jednego urządzenia wyślesz tablice z nimi to wypisze w osobnych tablicach dla każdego urządzenia

 Usunięty problem z 0 elementem = null przy pustej tablicy :)
*/
    require_once "../connect.php";




    function checkPerm(&$connection,$ID){
        $query = sprintf("SELECT id FROM devices WHERE userID=%d AND deviceID=%d", $_SESSION['id'],$ID);
        if (!($result = @$connection -> query($query))){
            http_response_code(500);
            throw new Exception();
        }
        $isDevice = $result -> num_rows;
        if($isDevice!=1){
            http_response_code(403);//Forbidden
            echo json_encode("Zabronione");
            throw new Exception();
        }
    }

    function makeRQBDates(&$connection,$ID,$minDate,$maxDate){

        
        $query = sprintf("SELECT * FROM `%d` WHERE date BETWEEN '%s' AND '%s'",$ID,$minDate,$maxDate,);
        if (!($result = @$connection -> query($query))){
            http_response_code(500);
            echo json_encode("Błąd serwera");
            echo json_encode($query);
            throw new Exception();
        }

        $recordsCount = $result -> num_rows;
        $record = [];
        for($i = 0; $i < $recordsCount; $i++){
            $record[] = $result -> fetch_assoc();
        }
        return $record;
    }






    session_start();


    if (!isset($_SESSION['loggedin'],$_SESSION['id'])) {
        http_response_code(401);
        echo json_encode("Brak uprawnień");
        exit();
    }



    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['id'],$data['minDate'],$data['maxDate'])) {
        http_response_code(400);
        echo json_encode("Błędne zapytanie");
        exit();
    }
	
	$regex = '/^([0-9]+(-[0-9]+)+)$/i'; // regex for date veryfication
	
	if (!(preg_match($regex,$data['minDate']/*"2022-11-01'"*/)and preg_match($regex,$data['maxDate']))) {
        http_response_code(400);
        echo json_encode("Błędne zapytanie");
        exit();
    }




    $connection = @new mysqli($db_server,$db_user,$db_password,$db_name);
    if ($connection->connect_errno!=0){
        http_response_code(500);// http serwer error
        exit();
    }

    try{
        if (is_array($data['id'])){
            $devicesR = [];
            foreach($data['id'] as $dID){
                checkPerm($connection,$dID);
                $devicesR[] = makeRQBDates($connection,$dID,$data['minDate'],$data['maxDate']);
                
            }
            echo json_encode($devicesR);
    
        }elseif(is_integer($data['id']) && $data['id']>=0){
            checkPerm($connection,$data['id']);
            echo json_encode(makeRQBDates($connection,$data['id'],$data['minDate'],$data['maxDate']));
            
        }else{
            http_response_code(400);
            echo json_encode("Błędne zapytanie");
        }
    }catch ( Exception $e )  {
    }
    



    



    $connection->close();
    exit();

    
?>