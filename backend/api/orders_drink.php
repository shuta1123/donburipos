<?php
require_once(__DIR__.'/../db.php');
$dbh = connectDB();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER["REQUEST_METHOD"] !== "PATCH") {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(["error" => "method_not_allowed"]);
    exit();
}

if (!isset($_GET["id"]) || !ctype_digit($_GET["id"]) || (int)$_GET["id"] <= 0) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(["error" => "Bad_Request"]);
    exit();
}
$order_id = (int)$_GET["id"];

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

if (!is_array($data) || !array_key_exists("done", $data) || $data["done"] !== true) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(["error" => "Bad_Request"]);
    exit();
}

try {
    $dbh->beginTransaction();

    // 対象注文が存在するか + 現在の state_B を確認（行ロック）
    $sql = "SELECT id, state_B
            FROM orders
            WHERE id = :order_id
            FOR UPDATE";
    $sth = $dbh->prepare($sql);
    $sth->execute([":order_id" => $order_id]);
    $row = $sth->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $dbh->rollBack();
        header('HTTP/1.1 404 Not Found');
        echo json_encode(["error" => "notfound"]);
        exit();
    }

    // すでに false なら、そのまま ok で返す（冪等）
    if (!(bool)$row["state_B"]) {
        $dbh->commit();
        header('HTTP/1.1 200 OK');
        echo json_encode([
            "result" => "ok",
            "order_id" => $order_id,
            "state_B" => false
        ]);
        exit();
    }

    // state_B を false にする
    $sql = "UPDATE orders
            SET state_B = 0
            WHERE id = :order_id";
    $sth = $dbh->prepare($sql);
    $sth->execute([":order_id" => $order_id]);

    $dbh->commit();

    header('HTTP/1.1 200 OK');
    echo json_encode([
        "result" => "ok",
        "order_id" => $order_id,
        "state_B" => false
    ]);
    exit();

} catch (PDOException $e) {
    if ($dbh->inTransaction()) {
        $dbh->rollBack();
    }
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(["error" => "db_error"]);
    error_log($e->getMessage());
    exit();
}
?>
