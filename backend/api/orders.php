<?php
require_once(__DIR__.'/../db.php');
$dbh=connectDB();
header('Content-Type: application/json; charset=utf-8');

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $rawBody = file_get_contents('php://input');
    $data = json_decode($rawBody, true);

    if($data===null){
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(["error" => "Bad_Request"]);
        exit();
    }
    if(!(isset($data['items'])&&is_array($data['items'])&&count($data['items'])>0)){
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(["error" => "Bad_Request"]);
        exit();
    }
    if(!(isset($data['cash_num'])&&isset($data['in_out']))){
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(["error" => "Bad_Request"]);
        exit();
    }
    if(!(ctype_digit((string)$data['cash_num'])&&0<=(int)$data['cash_num']&&(int)$data['cash_num']<100)){
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(["error" => "Bad_Request"]);
        exit();
    }
    $cash_num=(int)$data['cash_num'];

    if(!($data['in_out']==="IN"||$data['in_out']==="OUT")){
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(["error" => "Bad_Request"]);
        exit();
    }
    $in_out=$data['in_out'];

    $items=$data['items'];
    $makeA=false;
    $makeB=false;

    try{
        for($i=0;$i<count($items);$i++){
            if(!(isset($items[$i]["menu_id"])&&isset($items[$i]["quantity"])&&ctype_digit((string)$items[$i]["quantity"])&&$items[$i]["quantity"]>0)){
                header('HTTP/1.1 400 Bad Request');
                echo json_encode(["error" => "Bad_Request"]);
                exit();
            }

            $sql="SELECT `cook` ,`make` FROM `menus` WHERE `id`= :menu_id";
            $sth=$dbh->prepare($sql);
            $sth->execute(array(':menu_id'=>$items[$i]['menu_id']));
            $result=$sth->fetchALL(PDO::FETCH_ASSOC);

            if(count($result)!=1){
                header('HTTP/1.1 400 Bad Request');
                echo json_encode(["error" => "Bad_Request"]);
                exit();
            }

            if($result[0]['cook']){
                if(!$makeA&&$result[0]['make']==="A"){
                    $makeA=true;
                }
                if(!$makeB&&$result[0]['make']==="B"){
                    $makeB=true;
                }
            }
        }

        $sql="SELECT `order_num`
              FROM orders
              WHERE DATE(ordered_at) = CURDATE()
              ORDER BY ordered_at DESC, id DESC
              LIMIT 1";
        $sth=$dbh->query($sql);
        $result=$sth->fetchALL(PDO::FETCH_ASSOC);

        if(isset($result[0]['order_num'])){
            $order_num=((int)$result[0]['order_num']+1)%100;
        }else{
            $order_num=0;
        }

        $dbh->beginTransaction();

        $sql="INSERT INTO `orders` (`cash_num`, `order_num`, `in_out`, `state`, `state_B`)
              VALUES (:cash_num, :order_num, :in_out, :state, :state_B)";
        $sth=$dbh->prepare($sql);

        $state = $makeA ? "A" : "C";
        $sth->execute([
            ':cash_num' => $cash_num,
            ':order_num' => $order_num,
            ':in_out' => $in_out,
            ':state' => $state,
            ':state_B' => $makeB ? 1 : 0
        ]);

        $orderId = (int)$dbh->lastInsertId();

        $sql="INSERT INTO `order_items` (`order_id`, `menu_id`, `quantity`)
              VALUES (:order_id, :menu_id, :quantity)";
        $sth=$dbh->prepare($sql);

        for($i=0;$i<count($items);$i++){
            $sth->execute([
                ':order_id' => $orderId,
                ':menu_id' => (int)$items[$i]["menu_id"],
                ':quantity' => (int)$items[$i]["quantity"]
            ]);
        }

        $dbh->commit();

        $display = sprintf('%02d%02d', $cash_num, $order_num);
        $res = [
            "result" => "ok",
            "order_id" => $orderId,
            "display_order_num" => $display,
            "state" => $state,
            "state_B" => $makeB
        ];

        $json=json_encode($res);
        if($json===false){
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(["error" => "json_encode_error"]);
            error_log(json_last_error_msg());
            exit();
        }

        header('HTTP/1.1 201 Created');
        echo $json;

    }catch(PDOException $e){
        if($dbh->inTransaction()){
            $dbh->rollBack();
        }
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(["error" => "db_error"]);
        error_log($e->getMessage());
        exit();
    }

}else if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["id"])){

    if(!ctype_digit($_GET["id"]) || (int)$_GET["id"]<=0){
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(["error" => "Bad_Request"]);
        exit();
    }
    $order_id=(int)$_GET["id"];

    try{
        // order本体
        $sql="SELECT id, cash_num, order_num, in_out, state, state_B, ordered_at
              FROM orders
              WHERE id = :order_id
              LIMIT 1";
        $sth=$dbh->prepare($sql);
        $sth->execute([":order_id"=>$order_id]);
        $orderRow=$sth->fetch(PDO::FETCH_ASSOC);

        if(!$orderRow){
            header('HTTP/1.1 404 Not Found');
            echo json_encode(["error" => "notfound"]);
            exit();
        }

        $display = sprintf('%02d%02d', (int)$orderRow['cash_num'], (int)$orderRow['order_num']);

        $res = [
            "order_id" => (int)$orderRow["id"],
            "display_order_num" => $display,
            "ordered_at" => $orderRow["ordered_at"],
            "in_out" => $orderRow["in_out"],
            "state" => $orderRow["state"],
            "state_B" => (bool)$orderRow["state_B"],
            "items" => []
        ];

        // items（名前/cook/makeつき）
        $sql="SELECT m.name, m.cook, m.make, oi.quantity
              FROM order_items oi
              INNER JOIN menus m ON m.id = oi.menu_id
              WHERE oi.order_id = :order_id
              ORDER BY oi.id ASC";
        $sth=$dbh->prepare($sql);
        $sth->execute([":order_id"=>$order_id]);
        $items=$sth->fetchAll(PDO::FETCH_ASSOC);

        for($i=0;$i<count($items);$i++){
            $res["items"][] = [
                "name" => $items[$i]["name"],
                "cook" => (bool)$items[$i]["cook"],
                "make" => $items[$i]["make"],
                "quantity" => (int)$items[$i]["quantity"]
            ];
        }

        $json=json_encode($res);
        if($json===false){
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(["error" => "json_encode_error"]);
            error_log(json_last_error_msg());
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

}else if($_SERVER["REQUEST_METHOD"] == "GET"){
    try{
        if(!isset($_GET["screen"])){
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(["error" => "Bad_Request"]);
            exit();
        }

        $screen = (string)$_GET["screen"];

        if($screen === 'A' || $screen === 'C'){
            $sql="SELECT id, cash_num, order_num, in_out, state
                  FROM orders
                  WHERE state = :state
                  ORDER BY ordered_at ASC, id ASC";
            $sth=$dbh->prepare($sql);
            $sth->execute([':state'=>$screen]);

        }else if($screen === 'B'){
            $sql="SELECT id, cash_num, order_num, in_out, state
                  FROM orders
                  WHERE state_B = 1
                  ORDER BY ordered_at ASC, id ASC";
            $sth=$dbh->prepare($sql);
            $sth->execute();

        }else if($screen === 'D'){
            $sql="SELECT id, cash_num, order_num, in_out, state
                  FROM orders
                  WHERE state IN ('D','cool')
                  ORDER BY ordered_at ASC, id ASC";
            $sth=$dbh->prepare($sql);
            $sth->execute();

        }else{
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(["error" => "Bad_Request"]);
            exit();
        }

        $result=$sth->fetchAll(PDO::FETCH_ASSOC);
        $res = [];

        for ($i = 0; $i < count($result); $i++) {
            $display = sprintf('%02d%02d', (int)$result[$i]['cash_num'], (int)$result[$i]['order_num']);
            $state = (string)$result[$i]["state"];

            $order = [
                "order_id" => (int)$result[$i]["id"],
                "display_order_num" => $display,
                "in_out" => $result[$i]["in_out"],
                "state" => $state,
                "items" => []
            ];

            // ★ D画面かつ state=cool のときだけ items を返さない（空のまま）
            $shouldHideItems = ($screen === 'D' && $state === 'cool');

            if(!$shouldHideItems){
                $sql = "SELECT m.name, oi.quantity
                        FROM order_items oi
                        INNER JOIN menus m ON m.id = oi.menu_id
                        WHERE oi.order_id = :order_id
                        ORDER BY oi.id ASC";
                $sth2 = $dbh->prepare($sql);
                $sth2->execute([":order_id" => (int)$result[$i]["id"]]);
                $items = $sth2->fetchAll(PDO::FETCH_ASSOC);

                for ($j = 0; $j < count($items); $j++) {
                    $order["items"][] = [
                        "name" => $items[$j]["name"],
                        "quantity" => (int)$items[$j]["quantity"]
                    ];
                }
            }

            $res[] = $order;
        }

        header('HTTP/1.1 200 OK');
        echo json_encode($res);

    }catch(PDOException $e){
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(["error" => "db_error"]);
        error_log($e->getMessage());
        exit();
    }


}else{
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(["error" => "method_not_allowed"]);
    exit();
}
?>
