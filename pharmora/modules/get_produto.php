<?php
// Define que o retorno será JSON antes de qualquer output
header('Content-Type: application/json');

include_once "../config_api.php"; 

if (isset($_GET['codigo'])) {
    $codigo = $_GET['codigo'];
    
    try {
        // Preparação da consulta
        $stmt = $conn->prepare("SELECT nome_produto, principio_ativo, categoria, tipo_apresentacao, preco_venda_caixa, preco_compra, taxa_iva FROM produtos WHERE codigo_barra = ? ORDER BY id_produto DESC LIMIT 1");
        
        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Sucesso: Produto encontrado
            echo json_encode(['status' => 'success', 'dados' => $row]);
        } else {
            // Código não cadastrado
            echo json_encode(['status' => 'not_found']);
        }
        
    } catch (Exception $e) {
        // Erro de banco de dados
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    
    $stmt->close();
    exit;
}