<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "pharmora_db";

$response = [
    "database" => false,
    "system_name" => "Pharmora",
    "version" => "1.0.4",
    "status" => "offline"
];

$conn = new mysqli($host, $user, $pass, $db);

if (!$conn->connect_error) {
    $response["database"] = true;
    $response["status"] = "online";
    
    // Exemplo: Buscar o nome da farmácia configurado no banco
    // $res = $conn->query("SELECT valor FROM configuracoes WHERE chave = 'nome_loja'");
    // if($res) { ... }
}

echo json_encode($response);
$conn->close();
?>