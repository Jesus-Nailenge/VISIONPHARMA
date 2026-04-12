
<?php
// Ativar exibição de erros temporariamente para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Conexão com o Banco de Dados
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pharmora_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("<h2 style='color:red; text-align:center;'>Erro de Conexão: " . $conn->connect_error . "</h2>");
}

// 2. Verificar se a requisição é POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Captura dos campos (Obrigatórios)
    $nome   = trim($_POST['nome_completo']);
    $doc_id = trim($_POST['documento_id']);
    $tel    = trim($_POST['telefone']);
    $user   = trim($_POST['username']);
    $senha  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Captura dos campos (Opcionais com valores padrão)
    $email  = trim($_POST['email'] ?? '');
    $morada = trim($_POST['morada'] ?? '');
    $nasc   = !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : NULL;
    $sexo   = $_POST['sexo'] ?? 'Masculino';
    $cargo  = $_POST['cargo'] ?? 'Caixa';
    $depto  = $_POST['departamento'] ?? 'Geral';
    $tipo_c = $_POST['tipo_contrato'] ?? 'Efetivo';
    $filial = trim($_POST['filial'] ?? '');
    $nivel  = $_POST['nivel_acesso'] ?? 'Staff';

    // 3. Processamento Seguro da Foto
    $foto_nome = "default.png"; // Imagem padrão caso o usuário não envie
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $diretorio = 'uploads/';
        if(!is_dir($diretorio)) {
            mkdir($diretorio, 0777, true); // Cria a pasta se não existir
        }
        
        $extensao = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($extensao, $extensoes_permitidas)) {
            $foto_nome = "USER_" . uniqid() . "." . $extensao;
            move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $diretorio . $foto_nome);
        }
    }

    // 4. Geração Automática do ID Visual (Ex: PH-2026-005)
    $query_count = $conn->query("SELECT COUNT(*) as total FROM funcionarios");
    $total_atual = $query_count->fetch_assoc()['total'];
    $id_func = "PH-" . date("Y") . "-" . str_pad(($total_atual + 1), 3, "0", STR_PAD_LEFT);

    // 5. INSERÇÃO SUPER SEGURA (Prepared Statement)
    $sql = "INSERT INTO funcionarios (
                nome_completo, documento_id, telefone, data_nascimento, sexo, 
                email, morada, cargo, departamento, tipo_contrato, filial, 
                username, password_hash, nivel_acesso, foto_perfil, id_funcionario
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Erro na preparação do SQL: " . $conn->error);
    }

    // O "s" significa que todos os 16 parâmetros estão a ser enviados como Strings/Texto
    $stmt->bind_param("ssssssssssssssss", 
        $nome, $doc_id, $tel, $nasc, $sexo, 
        $email, $morada, $cargo, $depto, $tipo_c, $filial, 
        $user, $senha, $nivel, $foto_nome, $id_func
    );

    // 6. Execução e Redirecionamento
    try {
        if ($stmt->execute()) {
            // Sucesso total! Redireciona para a lista mostrando uma mensagem verde.
            header("Location: lista_funcionarios.php?msg=sucesso");
            exit();
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        echo "<div style='background:#111; color:white; padding:30px; font-family:sans-serif; text-align:center;'>";
        echo "<h2 style='color:#ff4444;'>❌ Ocorreu um erro ao salvar</h2>";
        
        // Mensagem amigável se o BI ou Username já existirem
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo "<p>O <b>Nº do BI</b> ou o <b>Nome de Usuário</b> já estão cadastrados no sistema.</p>";
        } else {
            echo "<p>Detalhe técnico: " . $e->getMessage() . "</p>";
        }
        
        echo "<br><a href='cadastro.php' style='color:#00ffcc; text-decoration:none;'>&larr; Voltar e tentar novamente</a>";
        echo "</div>";
    }

    $stmt->close();
}
$conn->close();
?>