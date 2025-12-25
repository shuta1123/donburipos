<?php
    require_once(__DIR__.'/../db.php');
    $dbh=connectDB();
    header('Content-Type: application/json; charset=utf-8');
    try{
        $sql="SELECT `id` , `name` FROM `categories` ORDER BY id";
        $sth=$dbh->query($sql);
        $result=$sth->fetchALL(PDO::FETCH_ASSOC);
        $json=json_encode($result);
        if($json===false){
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(["error" => "json_encode_error"]);
            exit();
        }
        header('HTTP/1.1 200 OK');
        echo $json;
    }catch(PDOException $e){
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(["error" => "db_error"]);
        error_log($e->getMessage());
        exit();
    }

?>