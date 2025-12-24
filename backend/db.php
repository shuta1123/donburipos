<?php
    require_once(__DIR__.'/config.php');

    function connectDB(){
        try{
            error_log("test");
            return new PDO(DSN,DB_USER,DB_PASSWORD);
            
        }catch(){
            error_log($e->getMessage());
            exit();
        }
    }
?>