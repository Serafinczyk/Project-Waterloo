<?php
    session_start();

    if (isset($_SESSION['loggedin'])) {
       header("Location: /dashboard");
	   exit(); 
    }
    else {
		header('Location: /login');
        exit();
	}
?>