<?php
    require_once(__DIR__.'/../db.php');
    $dbh=connectDB();
    header('Content-Type: application/json; charset=utf-8');
    if(!(isset($_GET['category_id'])&&ctype_digit($_GET['category_id'])&&(int)$_GET['category_id']>0)){
        header('HTTP/1.1 400 Bad Request');
            echo json_encode(["error" => "num_error"]);
            exit();
    }
    $category=$_GET['category_id'];
    try{
        $sql="SELECT `id` , `name`,`category_id` FROM `menus` WHERE category_id=:category ORDER BY id";
        $sth=$dbh->prepare($sql);
        $sth->execute(array(':category'=>(int)$category));
        $result=$sth->fetchALL(PDO::FETCH_ASSOC);
        if(count($result)===0){
            header('HTTP/1.1 404 Not Found');
            
            echo json_encode(["error" => "notfound"]);
            exit();
        }
        $json=json_encode($result);
        
        if($json===false){
            header('HTTP/1.1 500 Internal Server Error');
            error_log(json_last_error_msg());
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