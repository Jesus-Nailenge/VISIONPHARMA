<?php
/**
 * PHARMORA - Endpoint para Eliminar Funcionário com Remoção Absoluta e Auditoria
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

require_once("../config_api.php");

$response = [
    "success" => false,
    "message" => "Ocorreu um erro desconhecido no servidor."
];

// Captura e validação estrita do ID enviado pelo JavaScript (Superglobal $_GET)
if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    if (!isset($_POST['id']) || empty(trim($_POST['id']))) {
        $response["message"] = "O identificador (ID) do funcionário não foi fornecido.";
        echo json_encode($response);
        exit;
    } else {
        $id_sistema = intval($_POST['id']);
    }
} else {
    $id_sistema = intval($_GET['id']);
}

// Identifica quem está operando o sistema para registrar no histórico de segurança
$id_operador = $_SESSION['id_usuario'] ?? $_SESSION['id_funcionario'] ?? '0';
$nome_operador = $_SESSION['username'] ?? $_SESSION['nome_completo'] ?? 'Usuário Desconhecido';
$ip_origem = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

try {
    // Inicia a transação protetiva
    $conn->begin_transaction();

    // 1. DESATIVA TEMPORARIAMENTE AS CHECAGENS DE CHAVE ESTRANGEIRA (Força a exclusão absoluta)
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // 2. BUSCA DADOS DO FUNCIONÁRIO ANTES DA EXCLUSÃO (Para enriquecer o histórico do Log)
    $nome_funcionario = "Desconhecido";
    $id_visual = "N/A";
    $sql_busca = "SELECT nome_completo, id_funcionario FROM funcionarios WHERE id_sistema = ?";
    $stmt_busca = $conn->prepare($sql_busca);
    
    if ($stmt_busca) {
        $stmt_busca->bind_param("i", $id_sistema);
        $stmt_busca->execute();
        $resultado_busca = $stmt_busca->get_result();
        if ($row = $resultado_busca->fetch_assoc()) {
            $nome_funcionario = $row['nome_completo'];
            $id_visual = $row['id_funcionario'];
        }
        $stmt_busca->close();
    }

    // 3. LIMPEZA MANUAL DA TABELA DE USUÁRIOS (Garantia extra de tabela separada)
    $sql_del_user = "DELETE FROM usuarios WHERE id_sistema = ?";
    $stmt_del_user = $conn->prepare($sql_del_user);
    if ($stmt_del_user) {
        $stmt_del_user->bind_param("i", $id_sistema);
        $stmt_del_user->execute();
        $stmt_del_user->close();
    }

    // 4. ELIMINAÇÃO DO REGISTRO PRINCIPAL NA TABELA FUNCIONARIOS
    $sql_delete = "DELETE FROM funcionarios WHERE id_sistema = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    
    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $id_sistema);
        $stmt_delete->execute();
        
        if ($stmt_delete->affected_rows > 0) {
            
            // 5. REGISTRO DE AUDITORIA PERMANENTE DO EVENTO
            $acaoLog = "DELETE_FUNCIONARIO_ABSOLUTO";
            $detalhesLog = "O operador {$nome_operador} (ID: {$id_operador}) REALIZOU UMA REMOÇÃO ABSOLUTA do funcionário '{$nome_funcionario}' (Código: {$id_visual}, ID Sistema: {$id_sistema}) e de todas as suas credenciais de acesso.";
            
            $sql_log = "INSERT INTO auditoria_logs (acao, detalhes, modulo, ip_origem) VALUES (?, ?, 'Funcionários', ?)";
            $stmt_log = $conn->prepare($sql_log);
            
            if ($stmt_log) {
                $stmt_log->bind_param("sss", $acaoLog, $detalhesLog, $ip_origem);
                $stmt_log->execute();
                $stmt_log->close();
            }

            // REATIVA AS CHECAGENS DE CHAVE ESTRANGEIRA ANTES DE CONFIRMAR
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            $conn->commit();

            $response["success"] = true;
            $response["message"] = "Funcionário e credenciais eliminados com sucesso absoluto!";
        } else {
            // Se nenhuma linha foi afetada, o funcionário já não existia
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            $conn->rollback();
            $response["message"] = "Nenhum funcionário encontrado com o ID enviado ou já foi removido por outro operador.";
        }
        $stmt_delete->close();
    } else {
        throw new Exception("Falha ao preparar a consulta de exclusão principal.");
    }

} catch (Exception $e) {
    // Em caso de qualquer falha crítica, reativa as chaves e desfaz as alterações
    if (isset($conn)) { 
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        $conn->rollback(); 
    }
    $response["message"] = "Erro crítico do servidor ao forçar exclusão absoluta: " . $e->getMessage();
}

$conn->close();
echo json_encode($response);
exit;