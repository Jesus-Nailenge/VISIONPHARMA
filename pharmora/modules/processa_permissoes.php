<?php
/**
 * PHARMORA - Endpoint para Atualizar Permissões com Auditoria Permanente
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

include_once("../config_api.php");

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Falha na conexão com a base de dados.']);
    exit;
}

$id_sistema = $_POST['id_funcionario'] ?? null; 

if (!$id_sistema) {
    echo json_encode(['success' => false, 'message' => 'ID do funcionário (sistema) não fornecido.']);
    exit;
}

$permissoes = [
    "ver_dashboard"    => isset($_POST['ver_dashboard']) ? 1 : 0,
    "ver_vendas"       => isset($_POST['ver_vendas']) ? 1 : 0,
    "ver_perdas"       => isset($_POST['ver_perdas']) ? 1 : 0,
    "ver_estoque"      => isset($_POST['ver_estoque']) ? 1 : 0,
    "ver_fornecedores" => isset($_POST['ver_fornecedores']) ? 1 : 0,
    "gerir_usuarios"   => isset($_POST['gerir_usuarios']) ? 1 : 0,
    "ver_financeiro"   => isset($_POST['ver_financeiro']) ? 1 : 0,
    "ver_logs"         => isset($_POST['ver_logs']) ? 1 : 0,
    "ver_relatorios"   => isset($_POST['ver_relatorios']) ? 1 : 0
];

$json_permissoes = json_encode($permissoes);

// Identifica o operador atual logado na sessão
$id_operador = $_SESSION['id_usuario'] ?? $_SESSION['id_funcionario'] ?? '0';
$nome_operador = $_SESSION['username'] ?? $_SESSION['nome_completo'] ?? 'Usuário Desconhecido';
$ip_origem = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

try {
    $conn->begin_transaction();

    // 1. BUSCA O NOME E UTILIZADOR DO ALVO DA ALTERAÇÃO
    $nome_alvo = "Desconhecido";
    $sql_busca = "SELECT nome_completo FROM funcionarios WHERE id_sistema = ?";
    $stmt_busca = $conn->prepare($sql_busca);
    if ($stmt_busca) {
        $stmt_busca->bind_param("i", $id_sistema);
        $stmt_busca->execute();
        $res_busca = $stmt_busca->get_result();
        if ($row = $res_busca->fetch_assoc()) {
            $nome_alvo = $row['nome_completo'];
        }
        $stmt_busca->close();
    }

    // 2. ATUALIZA AS PERMISSÕES
    $stmt = $conn->prepare("UPDATE usuarios SET permissoes_especiais = ? WHERE id_sistema = ?");
    if (!$stmt) throw new Exception('Erro na preparação da consulta: ' . $conn->error);

    $stmt->bind_param("si", $json_permissoes, $id_sistema);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0 || $stmt->info == "") {
            
            // 3. SE ATUALIZOU, SALVA O LOG DE AUDITORIA
            $acaoLog = "UPDATE_PERMISSIONS";
            $detalhesLog = "O operador {$nome_operador} (ID: {$id_operador}) MODIFICOU as permissões de acesso do funcionário '{$nome_alvo}' (ID Sistema: {$id_sistema}). Novo privilégio JSON: {$json_permissoes}";
            
            $sql_log = "INSERT INTO auditoria_logs (acao, detalhes, modulo, ip_origem) VALUES (?, ?, 'Funcionários', ?)";
            $stmt_log = $conn->prepare($sql_log);
            
            if ($stmt_log) {
                $stmt_log->bind_param("sss", $acaoLog, $detalhesLog, $ip_origem);
                $stmt_log->execute();
                $stmt_log->close();
            }

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Permissões atualizadas com sucesso!']);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Nenhum utilizador encontrado na tabela de credenciais para este funcionário.']);
        }
    } else {
        throw new Exception($stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    if (isset($conn)) { $conn->rollback(); }
    echo json_encode(['success' => false, 'message' => 'Erro ao processar alteração: ' . $e->getMessage()]);
}

$conn->close();
exit;