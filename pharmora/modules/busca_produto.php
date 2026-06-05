<?php
// Força o PHP a não cuspir avisos de texto que quebrem o formato JSON
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

// Inclui o arquivo de configuração e CORS
include_once("../config_api.php");

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Pesquisa vazia']);
    exit;
}

try {
    // REMOVIDA a coluna 'fornecedor' para evitar o erro de coluna inexistente
    $sql = "SELECT 
                id_produto, 
                codigo_barra, 
                nome_produto, 
                principio_ativo, 
                categoria, 
                tipo_apresentacao, 
                dosagem_peso, 
                lote, 
                data_validade, 
                preco_compra, 
                taxa_iva, 
                permite_retalho, 
                unidades_por_caixa, 
                preco_venda_caixa, 
                preco_venda_unidade, 
                preco_promocional, 
                estoque_atual_caixas, 
                estoque_minimo_caixas, 
                localizacao_corredor, 
                requer_receita, 
                refrigerado, 
                foto_produto, 
                status_item, 
                data_cadastro, 
                ultima_atualizacao
            FROM produtos 
            WHERE (codigo_barra = ? OR nome_produto LIKE ?) 
            AND status_item = 'Ativo'
            LIMIT 10";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Erro interno na query do banco: " . $conn->error);
    }

    $param_parcial = "%" . $query . "%";
    $stmt->bind_param("ss", $query, $param_parcial);
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Método compatível com todas as versões de drivers do Apache/XAMPP
    $produtos = [];
    while ($row = $result->fetch_assoc()) {
        $produtos[] = $row;
    }

    if (count($produtos) > 0) {
        echo json_encode([
            'success' => true,
            'data' => $produtos
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'data' => [],
            'message' => 'Nenhum item encontrado'
        ]);
    }

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>