<?php
    require_once(__DIR__.'/config.php');

    function connectDB(){
        try{
            // error_log("test");
            $dbh = new PDO(DSN, DB_USER, DB_PASSWORD);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $dbh;
        }catch(PDOException $e){
            header('HTTP/1.1 500 Internal Server Error');
            error_log($e->getMessage());
            exit();
        }
    }
?>