<?php
/**
 * PHARMORA API - Autenticação & Registro de Logins (Tabelas Separadas)
 * Desenvolvido por: Belardino Capessa
 * Integrado com: Registro de Dispositivo e Estatísticas de Acesso
 */

// 1. CONFIGURAÇÕES DE ACESSO (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. CONEXÃO COM O BANCO DE DATOS
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "pharmora_db";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    echo json_encode([
        "success" => false, 
        "message" => "Erro técnico: Falha na conexão com o servidor de dados."
    ]);
    exit;
}

// 3. PROCESSAMENTO DO LOGIN
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $input = json_decode(file_get_contents("php://input"), true);
    
    $usuario_digitado = isset($_POST['username']) ? trim($_POST['username']) : ($input['username'] ?? '');
    $senha_digitada = isset($_POST['password']) ? $_POST['password'] : ($input['password'] ?? '');

    if (empty($usuario_digitado) || empty($senha_digitada)) {
        echo json_encode(["success" => false, "message" => "Por favor, preencha as credenciais."]);
        exit;
    }

    // Busca os dados unificando a tabela 'usuarios' e 'funcionarios' através do id_sistema
    $sql = "SELECT 
                u.id_sistema, 
                u.username, 
                u.password_hash, 
                u.nivel_acesso, 
                u.permissoes_especiais, 
                u.estado_conta,
                f.id_funcionario, 
                f.nome_completo, 
                f.foto_perfil, 
                f.cargo
            FROM usuarios u
            INNER JOIN funcionarios f ON u.id_sistema = f.id_sistema 
            WHERE u.username = ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $usuario_digitado);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $user = $resultado->fetch_assoc();

            // Verificação de segurança: Conta bloqueada (na tabela usuarios)
            if (isset($user['estado_conta']) && $user['estado_conta'] === 'Bloqueada') {
                echo json_encode(["success" => false, "message" => "Esta conta está suspensa pelo administrador."]);
                exit;
            }

            // 4. VERIFICAÇÃO DA SENHA
            if ($senha_digitada === $user['password_hash']) {
                
                // --- INÍCIO DO REGISTRO DE ACESSO ---
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                $dispositivo = "Desktop / PC";

                if (preg_match('/iphone|ipad|android/i', $user_agent)) {
                    $dispositivo = "Mobile Device";
                }

                // Atualiza as estatísticas DIRETAMENTE na tabela 'usuarios'
                $sql_update = "UPDATE usuarios SET 
                               ultimo_login = NOW(), 
                               dispositivo_usado = ?, 
                               numero_logins = numero_logins + 1 
                               WHERE id_sistema = ?";
                
                $stmt_up = $conn->prepare($sql_update);
                if ($stmt_up) {
                    $stmt_up->bind_param("si", $dispositivo, $user['id_sistema']);
                    $stmt_up->execute();
                    $stmt_up->close();
                }
                // --- FIM DO REGISTRO DE ACESSO ---

                // Inicia a sessão PHP
                if (session_status() === PHP_SESSION_NONE) { session_start(); }
                session_regenerate_id(true);
                
                $_SESSION['id_sistema'] = $user['id_sistema'];
                $_SESSION['id_funcionario'] = $user['id_funcionario'];
                $_SESSION['nome'] = $user['nome_completo'];
                $_SESSION['nivel'] = $user['nivel_acesso'];

                // Configuração de caminhos de imagem
                $server_ip = $_SERVER['HTTP_HOST'];
                $base_url = "http://" . $server_ip . "/pharmora/";
                $upload_path = "uploads/";
                
                $foto_nome = $user['foto_perfil'];
                $caminho_fisico = $_SERVER['DOCUMENT_ROOT'] . "/pharmora/" . $upload_path . $foto_nome;

                if (!empty($foto_nome) && file_exists($caminho_fisico)) {
                    $url_final_foto = $base_url . $upload_path . $foto_nome;
                } else {
                    $url_final_foto = $base_url . $upload_path . "avatar.png";
                }

                $permissoes = json_decode($user['permissoes_especiais'], true) ?? [];

                echo json_encode([
                    "success" => true,
                    "message" => "Terminal sincronizado. Bem-vindo!",
                    "user_data" => [
                        "id" => $user['id_funcionario'],
                        "nome" => $user['nome_completo'],
                        "cargo" => $user['cargo'] ?? 'Funcionário',
                        "nivel" => $user['nivel_acesso'],
                        "foto" => $url_final_foto,
                        "permissoes" => $permissoes,
                        "dispositivo" => $dispositivo
                    ]
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "Senha incorreta para este utilizador."]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Utilizador não reconhecido no sistema."]);
        }
        $stmt->close();
    } else {
        echo json_encode(["success" => false, "message" => "Erro crítico ao preparar consulta."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Método não suportado."]);
}

$conn->close();
?>  