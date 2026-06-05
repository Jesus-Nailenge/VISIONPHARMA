<?php
// config_api.php

// 1. Permite acesso de qualquer dispositivo (Telemóvel, Tablet, etc.)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Conexão única com a Base de Dados
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "pharmora_db";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Erro de conexão"]));
}
?>