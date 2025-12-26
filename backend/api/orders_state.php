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

if (!is_array($data) || !isset($data["action"]) || !is_string($data["action"])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(["error" => "Bad_Request"]);
    exit();
}

$action = $data["action"];

// action -> (from_state, to_state)
$transitions = [
    "A_DONE" => ["from" => "A",    "to" => "C"],
    "C_DONE" => ["from" => "C",    "to" => "D"],
    "CALL"   => ["from" => "D",    "to" => "cool"],
    "FINISH" => ["from" => "cool", "to" => "fin"],
];

if (!isset($transitions[$action])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(["error" => "Bad_Request"]);
    exit();
}

try {
    $dbh->beginTransaction();

    // 現在stateを取得（行ロック）
    $sql = "SELECT id, state
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

    $current = (string)$row["state"];
    $needFrom = $transitions[$action]["from"];
    $toState  = $transitions[$action]["to"];

    // 状態遷移チェック
    if ($current !== $needFrom) {
        $dbh->rollBack();
        header('HTTP/1.1 409 Conflict');
        echo json_encode([
            "result" => "ng",
            "error" => [
                "code" => "INVALID_STATE_TRANSITION",
                "message" => "state={$current} から {$action} は実行できません"
            ]
        ]);
        exit();
    }

    // 更新
    $sql = "UPDATE orders
            SET state = :to_state
            WHERE id = :order_id";
    $sth = $dbh->prepare($sql);
    $sth->execute([
        ":to_state" => $toState,
        ":order_id" => $order_id
    ]);

    $dbh->commit();

    header('HTTP/1.1 200 OK');
    echo json_encode([
        "result" => "ok",
        "order_id" => $order_id,
        "state" => $toState
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
