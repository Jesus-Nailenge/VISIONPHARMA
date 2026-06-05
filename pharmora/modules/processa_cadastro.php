<?php
/**
 * PHARMORA API - Processamento de Cadastro e Edição com Logs de Auditoria
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once("../config_api.php");

if (!$conn) {
    echo json_encode(["success" => false, "message" => "Falha na conexão."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Método inválido."]);
    exit;
}

// RECEPÇÃO E LIMPEZA
$id_sistema      = isset($_POST['id_sistema']) ? trim($_POST['id_sistema']) : null;
$nome            = trim($_POST['nome_completo'] ?? '');
$documento_id    = trim($_POST['documento_id'] ?? '');
$telefone        = trim($_POST['telefone'] ?? '');
$data_nasc       = trim($_POST['data_nascimento'] ?? '');
$sexo            = trim($_POST['sexo'] ?? '');
$email           = trim($_POST['email'] ?? '');
$morada          = trim($_POST['morada'] ?? '');
$cargo           = trim($_POST['cargo'] ?? '');
$departamento    = trim($_POST['departamento'] ?? '');
$tipo_contrato   = trim($_POST['tipo_contrato'] ?? '');
$filial          = trim($_POST['filial'] ?? '');
$estado_trabalho = trim($_POST['estado_trabalho'] ?? 'Ativo');

$permitir_login  = isset($_POST['permitir_login']) && ($_POST['permitir_login'] == '1' || $_POST['permitir_login'] == 'true');
$username        = trim($_POST['username'] ?? '');
$nivel_acesso    = trim($_POST['nivel_acesso'] ?? 'Staff');
$password        = trim($_POST['password'] ?? '');

if (!$permitir_login || empty($username)) {
    $permitir_login = false;
    $username       = null;
    $nivel_acesso   = null;
    $password       = null;
}

if (empty($nome) || empty($documento_id)) {
    echo json_encode(["success" => false, "message" => "Nome e número do BI são obrigatórios."]);
    exit;
}

// Captura o operador da sessão para o Log
$id_operador = $_SESSION['id_usuario'] ?? $_SESSION['id_funcionario'] ?? '0';
$nome_operador = $_SESSION['username'] ?? $_SESSION['nome_completo'] ?? 'Usuário Desconhecido';
$ip_origem = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// UPLOAD DE FOTO
$foto_final = "avatar.png";
if (!empty($_FILES['foto_perfil']['name']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
    $extensoes = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $extensoes)) {
        $foto_final = "USER_" . time() . "_" . rand(100,999) . "." . $ext;
        $destino = '../uploads/';
        if (!file_exists($destino)) mkdir($destino, 0777, true);
        move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $destino . $foto_final);
    }
}

$conn->begin_transaction();

try {
    if (!empty($id_sistema)) {
        // ========== EDIÇÃO ==========

        $sql_func = "UPDATE funcionarios SET 
            nome_completo=?, documento_id=?, telefone=?, data_nascimento=?, 
            sexo=?, email=?, morada=?, cargo=?, departamento=?, tipo_contrato=?, 
            filial=?, estado_trabalho=?";
        
        $params_func = [
            $nome, $documento_id, $telefone, $data_nasc, $sexo, $email, $morada,
            $cargo, $departamento, $tipo_contrato, $filial, $estado_trabalho
        ];
        $types_func = "ssssssssssss";

        if ($foto_final !== "avatar.png") {
            $sql_func .= ", foto_perfil=?";
            $params_func[] = $foto_final;
            $types_func .= "s";
        }

        $sql_func .= " WHERE id_sistema=?";
        $params_func[] = $id_sistema;
        $types_func .= "i";

        $stmt_func = $conn->prepare($sql_func);
        $stmt_func->bind_param($types_func, ...$params_func);
        if (!$stmt_func->execute()) throw new Exception("Erro ao editar dados de RH: " . $stmt_func->error);
        $stmt_func->close();

        if ($permitir_login) {
            $stmt_check = $conn->prepare("SELECT id_sistema FROM usuarios WHERE id_sistema = ?");
            $stmt_check->bind_param("i", $id_sistema);
            $stmt_check->execute();
            $res_check = $stmt_check->get_result();
            $stmt_check->close();

            if ($res_check && $res_check->num_rows > 0) {
                $sql_user = "UPDATE usuarios SET username=?, nivel_acesso=?";
                $params_user = [$username, $nivel_acesso];
                $types_user = "ss";

                if (!empty($password)) {
                    $sql_user .= ", password_hash=?";
                    $params_user[] = $password; 
                    $types_user .= "s";
                }

                $sql_user .= " WHERE id_sistema=?";
                $params_user[] = $id_sistema;
                $types_user .= "i";

                $stmt_user = $conn->prepare($sql_user);
                $stmt_user->bind_param($types_user, ...$params_user);
            } else {
                $permissoes_final = json_encode([
                    "ver_dashboard" => 1, "ver_vendas" => 1, "ver_perdas" => 0,
                    "ver_estoque" => 0, "ver_fornecedores" => 0, "gerir_usuarios" => 0,
                    "ver_financeiro" => 0, "ver_logs" => 0, "ver_relatorios" => 0
                ]);
                $pass_final = !empty($password) ? $password : "1234";

                $sql_user = "INSERT INTO usuarios (id_sistema, username, password_hash, nivel_acesso, permissoes_especiais) 
                             VALUES (?, ?, ?, ?, ?)";
                $stmt_user = $conn->prepare($sql_user);
                $stmt_user->bind_param("issss", $id_sistema, $username, $pass_final, $nivel_acesso, $permissoes_final);
            }

            if (!$stmt_user->execute()) {
                if ($conn->errno == 1062) {
                    throw new Exception("Este Nome de Utilizador já está atribuído a outro funcionário.");
                }
                throw new Exception("Erro ao editar dados de acesso: " . $stmt_user->error);
            }
            $stmt_user->close();
        } else {
            $sql_user = "DELETE FROM usuarios WHERE id_sistema=?";
            $stmt_user = $conn->prepare($sql_user);
            $stmt_user->bind_param("i", $id_sistema);
            $stmt_user->execute();
            $stmt_user->close();
        }

        // AUDITORIA DA EDIÇÃO
        $acaoLog = "EDIT_FUNCIONARIO";
        $detalhesLog = "O operador {$nome_operador} (ID: {$id_operador}) ATUALIZOU os dados do funcionário '{$nome}' (ID Sistema: {$id_sistema}). Cargo: {$cargo}, Login Ativo: " . ($permitir_login ? "Sim ($username)" : "Não");

    } else {
        // ========== NOVO CADASTRO ==========

        $res = $conn->query("SELECT id_sistema FROM funcionarios ORDER BY id_sistema DESC LIMIT 1");
        $prox_id = ($res && $res->num_rows > 0) ? $res->fetch_assoc()['id_sistema'] + 1 : 1;
        $id_visual = "PH-" . date("Y") . "-" . str_pad($prox_id, 3, "0", STR_PAD_LEFT);

        $sql_func = "INSERT INTO funcionarios 
        (id_funcionario, nome_completo, documento_id, telefone, data_nascimento, sexo, email, morada, cargo, departamento, tipo_contrato, filial, estado_trabalho, foto_perfil)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt_func = $conn->prepare($sql_func);
        if (!$stmt_func) throw new Exception("Erro na preparação do funcionário: " . $conn->error);

        $stmt_func->bind_param(
            "ssssssssssssss",
            $id_visual, $nome, $documento_id, $telefone, $data_nasc, $sexo,
            $email, $morada, $cargo, $departamento, $tipo_contrato, $filial, $estado_trabalho, $foto_final
        );
        
        if (!$stmt_func->execute()) {
            if ($conn->errno == 1062) {
                throw new Exception("Este número de BI ou Documento de Identidade já está cadastrado.");
            }
            throw new Exception("Erro ao cadastrar funcionário: " . $stmt_func->error);
        }
        
        $novo_id_sistema = $conn->insert_id;
        $stmt_func->close();

        if ($permitir_login) {
            $permissoes_final = json_encode([
                "ver_dashboard" => 1, "ver_vendas" => 1, "ver_perdas" => 0,
                "ver_estoque" => 0, "ver_fornecedores" => 0, "gerir_usuarios" => 0,
                "ver_financeiro" => 0, "ver_logs" => 0, "ver_relatorios" => 0
            ]);
            $pass_final = !empty($password) ? $password : "1234";

            $sql_user = "INSERT INTO usuarios (id_sistema, username, password_hash, nivel_acesso, permissoes_especiais) 
                         VALUES (?, ?, ?, ?, ?)";
            
            $stmt_user = $conn->prepare($sql_user);
            if (!$stmt_user) throw new Exception("Erro na preparação do usuário: " . $conn->error);
            $stmt_user->bind_param("issss", $novo_id_sistema, $username, $pass_final, $nivel_acesso, $permissoes_final);
            
            if (!$stmt_user->execute()) {
                if ($conn->errno == 1062) {
                    throw new Exception("Este Nome de Utilizador já está em uso.");
                }
                throw new Exception("Erro ao criar credenciais de acesso: " . $stmt_user->error);
            }
            $stmt_user->close();
        }

        // AUDITORIA DO CADASTRO
        $id_sistema = $novo_id_sistema;
        $acaoLog = "ADD_FUNCIONARIO";
        $detalhesLog = "O operador {$nome_operador} (ID: {$id_operador}) CADASTROU o funcionário '{$nome}' (Código: {$id_visual}, Cargo: {$cargo}).";
    }

    // GRAVAÇÃO EFETIVA DO LOG DE AUDITORIA NO BANCO
    $sql_log = "INSERT INTO auditoria_logs (acao, detalhes, modulo, ip_origem) VALUES (?, ?, 'Funcionários', ?)";
    $stmt_log = $conn->prepare($sql_log);
    if ($stmt_log) {
        $stmt_log->bind_param("sss", $acaoLog, $detalhesLog, $ip_origem);
        $stmt_log->execute();
        $stmt_log->close();
    }

    $conn->commit();
    echo json_encode(["success" => true, "message" => "Sucesso ao processar registo!"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
?>