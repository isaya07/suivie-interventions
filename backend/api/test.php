<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
echo json_encode([
    "message" => "Test API fonctionne",
    "time" => date("Y-m-d H:i:s"),
    "method" => $_SERVER['REQUEST_METHOD']
]);
?>