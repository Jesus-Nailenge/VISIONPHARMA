<?php
include_once("../config_api.php");

// Inicia a sessão para capturar o id_funcionario logado
if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ----------------------------------------------------
    // 1. AÇÃO: ELIMINAR PRODUTO
    // ----------------------------------------------------
    if (isset($_POST['acao']) && $_POST['acao'] === 'eliminar') {
        $id = (int)$_POST['id_produto'];
        $stmt = $conn->prepare("DELETE FROM produtos WHERE id_produto = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) die(json_encode(['status' => 'success']));
        else die(json_encode(['status' => 'error', 'message' => $conn->error]));
    }

    // ----------------------------------------------------
    // 2. AÇÃO: REGISTRAR PERDA (VERSÃO ATUALIZADA)
    // ----------------------------------------------------
    if (isset($_POST['acao']) && $_POST['acao'] === 'registrar_perda') {
        
        // Validação de Sessão (id_funcionario do seu script de login)
        if (!isset($_SESSION['id_funcionario'])) {
            die(json_encode(['status' => 'error', 'message' => 'Sessão expirada. Recarregue a página.']));
        }

        $id_produto = (int)$_POST['id_produto_perda'];
        $id_func    = $_SESSION['id_funcionario']; // Captura automática
        $qtd        = (int)$_POST['qtd_perda'];
        $motivo     = $conn->real_escape_string($_POST['motivo_perda']);
        $obs        = $conn->real_escape_string($_POST['observacao_perda'] ?? '');
        
        // 1. Busca preço de custo atual para o histórico
        $resProd = $conn->query("SELECT preco_compra FROM produtos WHERE id_produto = $id_produto");
        $dados = $resProd->fetch_assoc();
        
        if (!$dados) die(json_encode(['status' => 'error', 'message' => 'Produto não encontrado.']));

        $preco_custo = (float)$dados['preco_compra'];
        $prejuizo = $qtd * $preco_custo;

        // Inicia Transação para segurança dos dados
        $conn->begin_transaction();

        try {
            // A) Descontar do estoque
            $conn->query("UPDATE produtos SET estoque_atual_caixas = estoque_atual_caixas - $qtd WHERE id_produto = $id_produto");
            
            // B) Inserir na nova tabela 'perdas'
            $sql_perda = "INSERT INTO perdas (id_produto, id_funcionario_responsavel, quantidade, motivo, observacao, preco_custo_unidade, valor_prejuizo_total, data_registro) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt_perda = $conn->prepare($sql_perda);
            $stmt_perda->bind_param("isissdd", $id_produto, $id_func, $qtd, $motivo, $obs, $preco_custo, $prejuizo);
            $stmt_perda->execute();

            $conn->commit();
            die(json_encode(['status' => 'success']));

        } catch (Exception $e) {
            $conn->rollback();
            die(json_encode(['status' => 'error', 'message' => 'Erro ao processar: ' . $e->getMessage()]));
        }
    }

    // ----------------------------------------------------
    // 3. AÇÃO: SALVAR / EDITAR PRODUTO
    // ----------------------------------------------------
    if (isset($_POST['acao']) && $_POST['acao'] === 'salvar') {
        $id = !empty($_POST['id_produto']) ? (int)$_POST['id_produto'] : null;
        
        $codigo = $conn->real_escape_string($_POST['codigo_barra'] ?? '');
        $nome = $conn->real_escape_string($_POST['nome_produto'] ?? '');
        $principio = $conn->real_escape_string($_POST['principio_ativo'] ?? '');
        $categoria = $conn->real_escape_string($_POST['categoria'] ?? '');
        $apresentacao = $conn->real_escape_string($_POST['tipo_apresentacao'] ?? '');
        $dosagem = $conn->real_escape_string($_POST['dosagem_peso'] ?? '');
        $lote = $conn->real_escape_string($_POST['lote'] ?? '');
        $validade = !empty($_POST['data_validade']) ? "'".$conn->real_escape_string($_POST['data_validade'])."'" : "NULL";
        
        $p_compra = (float)($_POST['preco_compra'] ?? 0);
        $iva = (float)($_POST['taxa_iva'] ?? 0);
        $p_venda_caixa = (float)($_POST['preco_venda_caixa'] ?? 0);
        $p_promo = (float)($_POST['preco_promocional'] ?? 0);
        
        $retalho = (int)($_POST['permite_retalho'] ?? 0);
        $un_caixa = (int)($_POST['unidades_por_caixa'] ?? 1);
        $p_unidade = (float)($_POST['preco_venda_unidade'] ?? 0);
        
        $est_atual = (int)($_POST['estoque_atual_caixas'] ?? 0);
        $est_min = (int)($_POST['estoque_minimo_caixas'] ?? 5);
        
        $fornecedor = $conn->real_escape_string($_POST['fornecedor'] ?? '');
        $local = $conn->real_escape_string($_POST['localizacao_corredor'] ?? '');
        $receita = (int)($_POST['requer_receita'] ?? 0);
        $refrigerado = (int)($_POST['refrigerado'] ?? 0);
        $status = $conn->real_escape_string($_POST['status_item'] ?? 'Ativo');

        // Lógica de Foto
        $foto_query_part = "";
        if (!empty($_FILES['foto_produto']['name'])) {
            $ext = pathinfo($_FILES['foto_produto']['name'], PATHINFO_EXTENSION);
            $foto_nome = time() . "." . $ext;
            
            if (!is_dir("../uploads/produtos/")) {
                mkdir("../uploads/produtos/", 0777, true);
            }
            
            move_uploaded_file($_FILES['foto_produto']['tmp_name'], "../uploads/produtos/" . $foto_nome);
            $foto_query_part = ", foto_produto = '$foto_nome'";
        }

        if ($id) {
            $sql = "UPDATE produtos SET 
                    codigo_barra = '$codigo', nome_produto = '$nome', principio_ativo = '$principio', 
                    categoria = '$categoria', tipo_apresentacao = '$apresentacao', dosagem_peso = '$dosagem', 
                    preco_compra = $p_compra, preco_venda_caixa = $p_venda_caixa, taxa_iva = $iva, 
                    permite_retalho = $retalho, unidades_por_caixa = $un_caixa, preco_venda_unidade = $p_unidade, 
                    preco_promocional = $p_promo, estoque_atual_caixas = $est_atual, estoque_minimo_caixas = $est_min, 
                    lote = '$lote', data_validade = $validade, fornecedor = '$fornecedor', 
                    localizacao_corredor = '$local', requer_receita = $receita, refrigerado = $refrigerado, 
                    status_item = '$status', ultima_atualizacao = NOW() 
                    $foto_query_part
                    WHERE id_produto = $id";
        } else {
            $foto_nome_insert = isset($foto_nome) ? "'$foto_nome'" : "NULL";
            
            $sql = "INSERT INTO produtos (
                        codigo_barra, nome_produto, principio_ativo, categoria, tipo_apresentacao, dosagem_peso,
                        preco_compra, preco_venda_caixa, taxa_iva, permite_retalho, unidades_por_caixa, preco_venda_unidade,
                        preco_promocional, estoque_atual_caixas, estoque_minimo_caixas, lote, data_validade,
                        fornecedor, localizacao_corredor, requer_receita, refrigerado, status_item, foto_produto, data_cadastro
                    ) VALUES (
                        '$codigo', '$nome', '$principio', '$categoria', '$apresentacao', '$dosagem',
                        $p_compra, $p_venda_caixa, $iva, $retalho, $un_caixa, $p_unidade,
                        $p_promo, $est_atual, $est_min, '$lote', $validade,
                        '$fornecedor', '$local', $receita, $refrigerado, '$status', $foto_nome_insert, NOW()
                    )";
        }

        if ($conn->query($sql)) {
            die(json_encode(['status' => 'success']));
        } else {
            die(json_encode(['status' => 'error', 'message' => $conn->error]));
        }
    }
}
?>