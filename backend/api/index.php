<?php
    header('Content-Type: application/json; charset=utf-8');
    header('HTTP/1.1 404 Not Found');
    echo json_encode(["error" => "not_found_api_error"]);
?>