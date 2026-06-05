<?php
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once("../config_api.php");

$id_usuario_logado = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : null;
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['itens'])) {
    echo json_encode(['success' => false, 'message' => 'Carrinho vazio.']);
    exit;
}

try {
    $conn->begin_transaction();

    // AJUSTE: Removida a coluna valor_troco da query
    $sqlVenda = "INSERT INTO vendas (nome_cliente, total_produtos, desconto_venda, acrescimo_venda, total_final, forma_pagamento, valor_recebido, status_venda, id_usuario) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'Concluida', ?)";
    
    $stmtVenda = $conn->prepare($sqlVenda);
    
    // AJUSTE: String de tipos corrigida para "sdddddsi" (8 parâmetros ao todo)
    // s -> nome_cliente (string)
    // d -> total_produtos (decimal/double)
    // d -> desconto_venda (decimal/double)
    // d -> acrescimo_venda (decimal/double)
    // d -> total_final (decimal/double)
    // s -> forma_pagamento (string / enum)
    // d -> valor_recebido (decimal/double)
    // i -> id_usuario (integer)
    $stmtVenda->bind_param("sddddssi", 
        $data['nome_cliente'], 
        $data['subtotal'], 
        $data['desconto'], 
        $data['acrescimo'], 
        $data['total_final'], 
        $data['metodo_pagamento'], 
        $data['valor_pago'], 
        $id_usuario_logado
    );

    if (!$stmtVenda->execute()) throw new Exception("Erro ao salvar venda.");
    $vendaId = $conn->insert_id;

    foreach ($data['itens'] as $item) {
        $qtd = intval($item['qtd']);
        $id_prod = intval($item['id_produto']);
        $modo = $item['modo_venda']; // 'caixa' ou 'unidade'
        
        // Define o preço unitário usado na venda para salvar no histórico
        $preco_usado = ($modo === 'unidade') ? floatval($item['preco_venda_unidade']) : floatval($item['preco_venda_caixa']);
        $subItem = $qtd * $preco_usado;
        
        // 1. Grava o item na venda
        $sqlItem = "INSERT INTO vendas_itens (venda_id, produto_id, qtd, preco_unitario, subtotal, modo_venda) VALUES (?, ?, ?, ?, ?, ?)";
        $stmtItem = $conn->prepare($sqlItem);
        $stmtItem->bind_param("iiidds", $vendaId, $id_prod, $qtd, $preco_usado, $subItem, $modo);
        $stmtItem->execute();

        // 2. Baixa de estoque inteligente
        if ($modo === 'caixa') {
            $quantidadeBaixar = $qtd; // Baixa N caixas inteiras
        } else {
            // Baixa fracionada: unidades vendidas / unidades por caixa
            $unidadesPorCaixa = intval($item['unidades_por_caixa']) ?: 1;
            $quantidadeBaixar = $qtd / $unidadesPorCaixa; 
        }

        $sqlEstoque = "UPDATE produtos SET estoque_atual_caixas = estoque_atual_caixas - ? WHERE id_produto = ?";
        $stmtEstoque = $conn->prepare($sqlEstoque);
        $stmtEstoque->bind_param("di", $quantidadeBaixar, $id_prod); // "d" para decimal
        $stmtEstoque->execute();
    }

    $conn->commit();

    // ====================================================
    // GRAVAÇÃO DO LOG APÓS O SUCESSO ABSOLUTO DA VENDA
    // ====================================================
    try {
        $id_operador = $_SESSION['id_usuario'] ?? $_SESSION['id_funcionario'] ?? '0';
        $nome_operador = 'Usuário Desconhecido';
        $ip_origem = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $acaoLog = "CONCLUSAO_VENDA";
        $clienteLog = !empty($data['nome_cliente']) ? $data['nome_cliente'] : 'Consumidor Final';
        
        $detalhesLog = "O operador " . $nome_operador . " (ID: " . $id_operador . ") concluiu a venda nº " . $vendaId . ". Cliente: " . $clienteLog . ". Total Final: " . $data['total_final'] . " Kz.";

        $logStmt = $conn->prepare("INSERT INTO auditoria_logs (acao, detalhes, modulo, ip_origem) VALUES (?, ?, 'Vendas', ?)");
        $logStmt->bind_param("sss", $acaoLog, $detalhesLog, $ip_origem);
        $logStmt->execute();
        $logStmt->close();
    } catch (Exception $e_log) {
        // Ignora falhas do log para nunca bloquear a resposta de sucesso da venda ao usuário
    }
    // ====================================================

    echo json_encode(['success' => true, 'venda_id' => $vendaId]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>