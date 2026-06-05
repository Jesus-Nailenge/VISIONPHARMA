<?php
/**
 * PHARMORA API - Salvar ou Atualizar Fornecedor com Auditoria Automática
 */

ob_start();
header("Content-Type: application/json; charset=UTF-8");

error_reporting(0);
ini_set('display_errors', 0);

// Garante o início da sessão para sabermos quem fez a ação
if (session_status() === PHP_SESSION_NONE) { session_start(); }

try {
    if (!file_exists("../config_api.php")) {
        throw new Exception("Arquivo de configuração config_api.php não foi encontrado.");
    }
    require_once("../config_api.php");

    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Falha na conexão com a base de dados.");
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        
        $id_fornecedor = trim($_POST['id_fornecedor'] ?? '');
        $nome          = trim($_POST['nome'] ?? '');
        $nif           = trim($_POST['nif'] ?? '');
        $telefone      = trim($_POST['telefone'] ?? '');
        $email         = trim($_POST['email'] ?? '');
        $endereco      = trim($_POST['endereco'] ?? '');
        $categoria     = trim($_POST['categoria'] ?? 'Geral');
        $pagamento     = trim($_POST['pagamento'] ?? 'Pronto Pagamento');

        // Identifica o operador atual logado na sessão (Focado no ID que já funciona)
        $id_operador = $_SESSION['id_usuario'] ?? $_SESSION['id_funcionario'] ?? '0';
        $nome_operador = 'Usuário Desconhecido';
        $ip_origem = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        if (empty($nome) || empty($nif)) {
            ob_clean();
            echo json_encode(["success" => false, "message" => "Nome e NIF são obrigatórios."]);
            exit;
        }

        $conn->begin_transaction();

        if (!empty($id_fornecedor) && is_numeric($id_fornecedor)) {
            
            // ================= MODO EDIÇÃO (UPDATE) =================
            $id_fornecedor = intval($id_fornecedor);

            // Busca os dados antigos para deixar o log rico em detalhes (Auditoria Real)
            $resAntigo = $conn->query("SELECT nome, nif FROM fornecedores WHERE id_fornecedor = $id_fornecedor");
            $dadosAntigos = $resAntigo->fetch_assoc();
            $nome_antigo = $dadosAntigos['nome'] ?? 'Desconhecido';

            $sql = "UPDATE fornecedores SET nome=?, nif=?, telefone=?, email=?, endereco=?, categoria=?, condicoes_pagamento=? WHERE id_fornecedor=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssi", $nome, $nif, $telefone, $email, $endereco, $categoria, $pagamento, $id_fornecedor);
            
            if (!$stmt->execute()) {
                if ($conn->errno === 1062) throw new Exception("Este NIF já está cadastrado.");
                throw new Exception("Erro ao atualizar: " . $stmt->error);
            }

            // GRAVA O LOG DE ALTERAÇÃO
            $acaoLog = "UPDATE_FORNECEDOR";
            $detalhesLog = "O operador {$nome_operador} (ID: {$id_operador}) alterou os dados do fornecedor '{$nome_antigo}' (ID: {$id_fornecedor}). Novo Nome: '{$nome}', Novo NIF: {$nif}.";
            
            $logStmt = $conn->prepare("INSERT INTO auditoria_logs (acao, detalhes, modulo, ip_origem) VALUES (?, ?, 'Fornecedores', ?)");
            $logStmt->bind_param("sss", $acaoLog, $detalhesLog, $ip_origem);
            $logStmt->execute();

            $conn->commit();
            ob_clean();
            echo json_encode(["success" => true, "message" => "Fornecedor atualizado com sucesso!"]);

        } else {
            
            // ================= MODO NOVO CADASTRO (INSERT) =================
            $sql = "INSERT INTO fornecedores (nome, nif, telefone, email, endereco, categoria, condicoes_pagamento) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $nome, $nif, $telefone, $email, $endereco, $categoria, $pagamento);
            
            if (!$stmt->execute()) {
                if ($conn->errno === 1062) throw new Exception("Este NIF já está cadastrado.");
                throw new Exception("Erro ao cadastrar: " . $stmt->error);
            }

            $novo_id = $conn->insert_id;

            // GRAVA O LOG DE INSERÇÃO
            $acaoLog = "CADASTRO_FORNECEDOR";
            $detalhesLog = "O operador {$nome_operador} (ID: {$id_operador}) cadastrou um novo fornecedor: '{$nome}' (NIF: {$nif}, ID Gerado: {$novo_id}).";
            
            $logStmt = $conn->prepare("INSERT INTO auditoria_logs (acao, detalhes, modulo, ip_origem) VALUES (?, ?, 'Fornecedores', ?)");
            $logStmt->bind_param("sss", $acaoLog, $detalhesLog, $ip_origem);
            $logStmt->execute();

            $conn->commit();
            ob_clean();
            echo json_encode(["success" => true, "message" => "Fornecedor cadastrado com sucesso!"]);
        }
        
        $stmt->close();
        $conn->close();
        exit;
        
    } else {
        throw new Exception("Método inválido.");
    }

} catch (Exception $e) {
    if(isset($conn)) { $conn->rollback(); }
    ob_clean();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    exit;
}