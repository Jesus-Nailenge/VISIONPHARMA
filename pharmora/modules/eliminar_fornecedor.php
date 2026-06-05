<?php
/**
 * PHARMORA - Endpoint para Eliminar Fornecedor com Auditoria Permanente
 */

// Define o cabeçalho para responder estritamente em formato JSON
header('Content-Type: application/json; charset=utf-8');

// Desativa a exibição de erros brutos na tela para não quebrar o retorno JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Garante o início da sessão para capturar o operador do sistema
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// Inclui a conexão com o banco de dados
require_once("../config_api.php");

// Instancia a estrutura padrão da resposta
$response = [
    "success" => false,
    "message" => "Ocorreu um erro desconhecido no servidor."
];

// Garante que a requisição é estritamente do tipo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response["message"] = "Método de requisição inválido. Apenas POST é permitido.";
    echo json_encode($response);
    exit;
}

// Captura e valida se o ID do fornecedor foi enviado
if (!isset($_POST['id_fornecedor']) || empty(trim($_POST['id_fornecedor']))) {
    $response["message"] = "O identificador (ID) do fornecedor não foi fornecido.";
    echo json_encode($response);
    exit;
}

// Converte para inteiro garantindo segurança extra contra SQL Injection
$id_fornecedor = intval($_POST['id_fornecedor']);

// Identifica o operador atual logado na sessão (Focado no ID que já funciona)
$id_operador = $_SESSION['id_usuario'] ?? $_SESSION['id_funcionario'] ?? '0';
$nome_operador = 'Usuário Desconhecido';
$ip_origem = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

try {
    // Inicia a transação para garantir que o log só grave se o fornecedor for realmente deletado
    $conn->begin_transaction();

    // 1. BUSCA O NOME DO FORNECEDOR ANTES DE DELETAR (Para enriquecer o histórico do Log)
    $nome_fornecedor = "Desconhecido";
    $sql_busca = "SELECT nome FROM fornecedores WHERE id_fornecedor = ?";
    $stmt_busca = $conn->prepare($sql_busca);
    
    if ($stmt_busca) {
        $stmt_busca->bind_param("i", $id_fornecedor);
        $stmt_busca->execute();
        $resultado_busca = $stmt_busca->get_result();
        if ($row = $resultado_busca->fetch_assoc()) {
            $nome_fornecedor = $row['nome'];
        }
        $stmt_busca->close();
    }

    // 2. PREPARA E EXECUTA A REMOÇÃO
    $sql_delete = "DELETE FROM fornecedores WHERE id_fornecedor = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    
    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $id_fornecedor);
        
        if ($stmt_delete->execute()) {
            // Verifica se alguma linha foi de fato excluída
            if ($stmt_delete->affected_rows > 0) {
                
                // 3. SE DELETOU COM SUCESSO, GERA O LOG DE AUDITORIA
                $acaoLog = "DELETE_FORNECEDOR";
                $detalhesLog = "O operador {$nome_operador} (ID: {$id_operador}) ELIMINOU permanentemente o fornecedor '{$nome_fornecedor}' (ID do registro: {$id_fornecedor}).";
                
                $sql_log = "INSERT INTO auditoria_logs (acao, detalhes, modulo, ip_origem) VALUES (?, ?, 'Fornecedores', ?)";
                $stmt_log = $conn->prepare($sql_log);
                
                if ($stmt_log) {
                    $stmt_log->bind_param("sss", $acaoLog, $detalhesLog, $ip_origem);
                    $stmt_log->execute();
                    $stmt_log->close();
                }

                // Confirma todas as alterações no banco de dados de forma segura
                $conn->commit();

                $response["success"] = true;
                $response["message"] = "Fornecedor eliminado com sucesso!";
            } else {
                $conn->rollback();
                $response["message"] = "Nenhum fornecedor encontrado com o ID enviado ou já foi removido.";
            }
        } else {
            $conn->rollback();
            $response["message"] = "Erro ao executar a exclusão no banco de dados: " . $stmt_delete->error;
        }
        
        $stmt_delete->close();
    } else {
        $conn->rollback();
        $response["message"] = "Falha ao preparar a consulta de exclusão: " . $conn->error;
    }
} catch (Exception $e) {
    // Desfaz qualquer alteração caso ocorra um erro catastrófico ou violação de Chave Estrangeira
    if (isset($conn)) {
        $conn->rollback();
    }
    
    // Tratamento amigável para integridade referencial (Fornecedor com produtos ou faturas vinculadas)
    $response["message"] = "Não é possível eliminar o fornecedor '{$nome_fornecedor}' porque ele possui registros, compras ou produtos associados ativos no sistema.";
}

// Fecha a conexão com o banco de dados
$conn->close();

// Retorna o resultado final limpo para o front-end
echo json_encode($response);
exit;