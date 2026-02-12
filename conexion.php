<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "store";

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Fallo la conexion a la BD"
    ]);
    exit;
}
