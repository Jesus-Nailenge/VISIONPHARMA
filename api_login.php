<?php
/**
 * PHARMORA API - Autenticação de Usuários
 * Desenvolvido por: Belardino Capessa
 */

// 1. CONFIGURAÇÕES DE ACESSO (CORS) - Essencial para o Telemóvel conseguir ler os dados
header("Access-Control-Allow-Origin: *"); // Permite acesso de qualquer IP
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json; charset=utf-8');

// Responde prontamente a requisições de teste (pre-flight)
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

// Verifica se a conexão falhou
if ($conn->connect_error) {
    echo json_encode([
        "success" => false, 
        "message" => "Erro técnico: Não foi possível conectar ao banco de dados."
    ]);
    exit;
}

// 3. PROCESSAMENTO DO LOGIN
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Captura os dados enviados pelo formulário ou pelo Fetch (JSON/FormData)
    $usuario_digitado = isset($_POST['username']) ? trim($_POST['username']) : '';
    $senha_digitada = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($usuario_digitado) || empty($senha_digitada)) {
        echo json_encode(["success" => false, "message" => "Usuário e senha são obrigatórios."]);
        exit;
    }

    // Busca o usuário no banco de dados
    $sql = "SELECT id_funcionario, nome_completo, password_hash, nivel_acesso FROM funcionarios WHERE username = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $usuario_digitado);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $user = $resultado->fetch_assoc();

            // 4. VERIFICAÇÃO DA SENHA (BCRYPT)
            if (password_verify($senha_digitada, $user['password_hash'])) {
                
                // Inicia sessão no servidor (opcional para APIs, mas útil para web)
                session_start();
                $_SESSION['id_funcionario'] = $user['id_funcionario'];
                $_SESSION['nome'] = $user['nome_completo'];

                // Resposta de Sucesso para o App
                echo json_encode([
                    "success" => true,
                    "message" => "Autenticação realizada com sucesso!",
                    "id" => $user['id_funcionario'],
                    "nome" => $user['nome_completo'],
                    "nivel" => $user['nivel_acesso']
                ]);
            } else {
                // Senha errada
                echo json_encode(["success" => false, "message" => "Senha incorreta."]);
            }
        } else {
            // Usuário não existe
            echo json_encode(["success" => false, "message" => "Utilizador não cadastrado."]);
        }
        $stmt->close();
    } else {
        echo json_encode(["success" => false, "message" => "Erro na consulta SQL."]);
    }
} else {
    // Caso tentem acessar o arquivo sem ser via POST
    echo json_encode([
        "success" => false, 
        "message" => "Método não permitido. Use POST para autenticar."
    ]);
}

$conn->close();
?>