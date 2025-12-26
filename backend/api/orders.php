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
    if(!(ctype_digit($data['cash_num'])&&00<=(int)$data['cash_num']&&(int)$data['cash_num']<100)){
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
            $sql="SELECT `cook` ,`make` FROM `menus` WHERE `id`= :menu_id ";
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
        $sql="SELECT `order_num` FROM orders WHERE DATE(ordered_at) = CURDATE() ORDER BY ordered_at DESC, id DESC LIMIT 1;";
        $sth=$dbh->query($sql);
        $result=$sth->fetchALL(PDO::FETCH_ASSOC);
        if(isset($result[0]['order_num'])){
            $order_num=($result[0]['order_num']+1)%100;
        }else{
            $order_num=0;
        }
        $dbh->beginTransaction();
        $sql="INSERT INTO `orders` ( `cash_num`, `order_num`, `in_out`, `state`, `state_B`) VALUES ( :cash_num, :order_num,  :in_out, :makeA, :makeB)";
        $sth=$dbh->prepare($sql);
        if($makeA){
            $sth->execute(array(':cash_num'=>$cash_num,':order_num'=>$order_num,':in_out'=>$in_out,':makeA'=>'A',":makeB"=>$makeB));
        }else{
            $sth->execute(array(':cash_num'=>$cash_num,':order_num'=>$order_num,':in_out'=>$in_out,':makeA'=>'C',":makeB"=>$makeB));
        }
        $orderId = (int)$dbh->lastInsertId();
        for($i=0;$i<count($items);$i++){
            $sql="INSERT INTO `order_items` ( `order_id`, `menu_id`, `quantity`) VALUES ( :orderid, :menu, :quant)";
            $sth=$dbh->prepare($sql);
            $sth->execute(array(':orderid'=>$orderId,':menu'=>$items[$i]["menu_id"],':quant'=>$items[$i]["quantity"]));
        }
        $dbh->commit();
        $display = sprintf('%02d%02d', $cash_num, $order_num);
        if($makeA){
            $res=array("order_id"=>$orderId,"display_order_num"=>$display,"state"=>"A","state_B"=>$makeB);
        }else{
            $res=array("order_id"=>$orderId,"display_order_num"=>$display,"state"=>"C","state_B"=>$makeB);
        }
        $res["result"] = "ok";
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




}else if($_SERVER["REQUEST_METHOD"] == "GET"&&isset($_GET["id"])){





}else if($_SERVER["REQUEST_METHOD"] == "GET"){
    try{
        if(!isset($_GET["screen"])){
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(["error" => "Bad_Request"]);
            exit();
        }
        if($_GET["screen"]==='A'||$_GET["screen"]==='C'){
            $sql="SELECT `id`,`cash_num`,`order_num`,`in_out` FROM `orders` WHERE `state`=:screen";
            $sth=$dbh->prepare($sql);
            $sth->execute(array(':screen'=>$_GET["screen"]));
        }else if($_GET["screen"]=='B'){
            $sql="SELECT `id`,`cash_num`,`order_num`,`in_out` FROM `orders` WHERE `state_B`=:screen";
            $sth=$dbh->prepare($sql);
            $sth->execute(array(':screen'=>true));
        }else if($_GET["screen"]==='D'){
            $sql="SELECT `id`,`cash_num`,`order_num`,`in_out` FROM `orders` WHERE `state`=:screenA OR `state`=:screenB";
            $sth=$dbh->prepare($sql);
            $sth->execute(array(':screenA'=>$_GET["screen"],':screenB'=>"cool"));
        }else{
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(["error" => "Bad_Request"]);
            exit();
        }
        $result=$sth->fetchALL(PDO::FETCH_ASSOC);
        $res = [];

        for ($i = 0; $i < count($result); $i++) {
            $display = sprintf('%02d%02d', $result[$i]['cash_num'], $result[$i]['order_num']);

            $order = [
                "order_id" => $result[$i]["id"],
                "display_order_num" => $display,
                "in_out" => $result[$i]["in_out"],
                "items" => []
            ];

            // items を取得
            $sql = "SELECT menu_id, quantity FROM order_items WHERE order_id = :order_id";
            $sth = $dbh->prepare($sql);
            $sth->execute([":order_id" => $result[$i]["id"]]);
            $items = $sth->fetchAll(PDO::FETCH_ASSOC);

            for ($j = 0; $j < count($items); $j++) {
                $sql = "SELECT name FROM menus WHERE id = :menu_id";
                $sth = $dbh->prepare($sql);
                $sth->execute([":menu_id" => $items[$j]["menu_id"]]);
                $menu = $sth->fetch(PDO::FETCH_ASSOC);

                $order["items"][] = [
                    "name" => $menu["name"],
                    "quantity" => $items[$j]["quantity"]
                ];
            }

            // 1注文分を追加
            $res[] = $order;
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


}else{
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(["error" => "num_error"]);
    exit();
}
?>