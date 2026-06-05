<?php
include_once("../config_api.php");

// Inicia a sessão para capturar o id_funcionario logado
if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $acao = $_POST['acao'] ?? '';

    // Definição padrão do operador para auditoria segura
    $id_operador   = $_SESSION['id_funcionario'] ?? $_SESSION['id_usuario'] ?? '0';
    $nome_operador = 'Usuário Desconhecido';
    $ip_origem     = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    // ----------------------------------------------------
    // 1. AÇÃO: ELIMINAR PRODUTO
    // ----------------------------------------------------
    if ($acao === 'eliminar') {
        $id = (int)$_POST['id_produto'];

        // Busca o nome do produto antes de apagar para o log ficar rico em detalhes
        $nome_produto = 'Desconhecido';
        $resProd = $conn->query("SELECT nome_produto FROM produtos WHERE id_produto = $id");
        if ($dadosProd = $resProd->fetch_assoc()) {
            $nome_produto = $dadosProd['nome_produto'];
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("DELETE FROM produtos WHERE id_produto = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                // GRAVA O LOG DE ELIMINAÇÃO
                $acaoLog = "DELETE_PRODUTO";
                $detalhesLog = "O operador {$nome_operador} (ID: {$id_operador}) ELIMINOU permanentemente o produto '{$nome_produto}' (ID: {$id}).";
                
                $logStmt = $conn->prepare("INSERT INTO auditoria_logs (acao, detalhes, modulo, ip_origem) VALUES (?, ?, 'Produtos', ?)");
                $logStmt->bind_param("sss", $acaoLog, $detalhesLog, $ip_origem);
                $logStmt->execute();

                $conn->commit();
                echo json_encode(['status' => 'success']);
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ----------------------------------------------------
    // 2. AÇÃO: REGISTRAR NOVA PERDA
    // ----------------------------------------------------
    if ($acao === 'registrar_perda') {
        if (!isset($_SESSION['id_funcionario'])) {
            die(json_encode(['status' => 'error', 'message' => 'Sessão expirada. Recarregue a página.']));
        }

        $id_produto = (int)$_POST['id_produto_perda'];
        $id_func    = $_SESSION['id_funcionario'];
        $qtd        = (int)$_POST['qtd_perda'];
        $motivo     = $conn->real_escape_string($_POST['motivo_perda']);
        $obs        = $conn->real_escape_string($_POST['observacao_perda'] ?? '');
        
        // Busca preço de custo e nome para registrar o prejuízo histórico
        $resProd = $conn->query("SELECT nome_produto, preco_compra FROM produtos WHERE id_produto = $id_produto");
        $dados = $resProd->fetch_assoc();
        
        if (!$dados) die(json_encode(['status' => 'error', 'message' => 'Produto não encontrado.']));

        $nome_produto = $dados['nome_produto'];
        $preco_custo = (float)$dados['preco_compra'];
        $prejuizo = $qtd * $preco_custo;

        $conn->begin_transaction();
        try {
            // A) Descontar do estoque
            $conn->query("UPDATE produtos SET estoque_atual_caixas = estoque_atual_caixas - $qtd WHERE id_produto = $id_produto");
            
            // B) Inserir na tabela perdas
            $sql_perda = "INSERT INTO perdas (id_produto, id_funcionario_responsavel, quantidade, motivo, observacao, preco_custo_unidade, valor_prejuizo_total, data_registro) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt_perda = $conn->prepare($sql_perda);
            $stmt_perda->bind_param("isissdd", $id_produto, $id_func, $qtd, $motivo, $obs, $preco_custo, $prejuizo);
            $stmt_perda->execute();

            // C) GRAVA O LOG DE PERDA
            $acaoLog = "CADASTRO_PERDA";
            $detalhesLog = "O operador {$nome_operador} (ID: {$id_operador}) registou uma PERDA de {$qtd} caixas do produto '{$nome_produto}' (ID: {$id_produto}). Motivo: {$motivo}. Prejuízo Total: {$prejuizo} Kz.";
            
            $logStmt = $conn->prepare("INSERT INTO auditoria_logs (acao, detalhes, modulo, ip_origem) VALUES (?, ?, 'Produtos', ?)");
            $logStmt->bind_param("sss", $acaoLog, $detalhesLog, $ip_origem);
            $logStmt->execute();

            $conn->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
        }
        exit;
    }

    // ----------------------------------------------------
    // 3. AÇÃO: EDITAR PERDA EXISTENTE (COM ESTORNO)
    // ----------------------------------------------------
    if ($acao === 'editar_perda') {
        $id_perda = (int)$_POST['id_perda'];
        $nova_qtd = (int)$_POST['qtd_perda'];
        $motivo   = $conn->real_escape_string($_POST['motivo_perda']);
        $obs      = $conn->real_escape_string($_POST['observacao_perda'] ?? '');

        // Recupera dados antigos para cálculo de estorno
        $query = "SELECT p.id_produto, p.quantidade, p.preco_custo_unidade, prod.nome_produto 
                  FROM perdas p 
                  JOIN produtos prod ON p.id_produto = prod.id_produto 
                  WHERE p.id_perda = ?";
        $stmt_q = $conn->prepare($query);
        $stmt_q->bind_param("i", $id_perda);
        $stmt_q->execute();
        $perda_antiga = $stmt_q->get_result()->fetch_assoc();

        if (!$perda_antiga) die(json_encode(['status' => 'error', 'message' => 'Registro não encontrado.']));

        $id_prod       = $perda_antiga['id_produto'];
        $qtd_antiga    = $perda_antiga['quantidade'];
        $nome_produto  = $perda_antiga['nome_produto'];
        $preco_un      = (float)$perda_antiga['preco_custo_unidade'];
        $novo_prejuizo = $nova_qtd * $preco_un;

        $conn->begin_transaction();
        try {
            // Ajusta estoque: Devolve a antiga e retira a nova
            $sql_est = "UPDATE produtos SET estoque_atual_caixas = estoque_atual_caixas + ? - ? WHERE id_produto = ?";
            $stmt_est = $conn->prepare($sql_est);
            $stmt_est->bind_param("iii", $qtd_antiga, $nova_qtd, $id_prod);
            $stmt_est->execute();

            // Atualiza o registro da perda
            $sql_up = "UPDATE perdas SET quantidade = ?, motivo = ?, observacao = ?, valor_prejuizo_total = ? WHERE id_perda = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("issdi", $nova_qtd, $motivo, $obs, $novo_prejuizo, $id_perda);
            $stmt_up->execute();

            // GRAVA O LOG DE EDIÇÃO DE PERDA
            $acaoLog = "UPDATE_PERDA";
            $detalhesLog = "O operador {$nome_operador} (ID: {$id_operador}) ALTEROU a perda (ID: {$id_perda}) do produto '{$nome_produto}'. Quantidade antiga: {$qtd_antiga}, Nova quantidade: {$nova_qtd}.";
            
            $logStmt = $conn->prepare("INSERT INTO auditoria_logs (acao, detalhes, modulo, ip_origem) VALUES (?, ?, 'Produtos', ?)");
            $logStmt->bind_param("sss", $acaoLog, $detalhesLog, $ip_origem);
            $logStmt->execute();

            $conn->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
        }
        exit;
    }

    // ----------------------------------------------------
    // 4. AÇÃO: SALVAR OU EDITAR PRODUTO (POLÍTICA DE ESTOQUE INTELIGENTE)
    // ----------------------------------------------------
    if ($acao === 'salvar') {
        $id = !empty($_POST['id_produto']) ? (int)$_POST['id_produto'] : null;
        
        $codigo   = trim($_POST['codigo_barra'] ?? '');
        $nome     = trim($_POST['nome_produto'] ?? '');
        $lote     = trim($_POST['lote'] ?? '');
        $validade = !empty($_POST['data_validade']) ? $_POST['data_validade'] : null;
        $est_nova = (int)($_POST['estoque_atual_caixas'] ?? 0);

        // --- REGRA DE OURO: VERIFICAÇÃO DE DUPLICIDADE (Apenas para novos cadastros) ---
        if (!$id) {
            $check = $conn->prepare("SELECT id_produto, nome_produto FROM produtos WHERE codigo_barra = ? AND lote = ? AND data_validade <=> ?");
            $check->bind_param("sss", $codigo, $lote, $validade);
            $check->execute();
            $res = $check->get_result();

            if ($res->num_rows > 0) {
                $prod_existente = $res->fetch_assoc();
                $id_existente = $prod_existente['id_produto'];
                
                $conn->begin_transaction();
                $update = $conn->prepare("UPDATE produtos SET estoque_atual_caixas = estoque_atual_caixas + ?, ultima_atualizacao = NOW() WHERE id_produto = ?");
                $update->bind_param("ii", $est_nova, $id_existente);
                
                if ($update->execute()) {
                    // GRAVA O LOG DE ATUALIZAÇÃO AUTOMÁTICA DE ESTOQUE POR DUPLICIDADE
                    $acaoLog = "UPDATE_ESTOQUE_DUPLICADO";
                    $detalhesLog = "O operador {$nome_operador} (ID: {$id_operador}) adicionou {$est_nova} caixas ao estoque existente do produto '{$prod_existente['nome_produto']}' (ID: {$id_existente}, Lote: {$lote}) via entrada duplicada.";
                    
                    $logStmt = $conn->prepare("INSERT INTO auditoria_logs (acao, detalhes, modulo, ip_origem) VALUES (?, ?, 'Produtos', ?)");
                    $logStmt->bind_param("sss", $acaoLog, $detalhesLog, $ip_origem);
                    $logStmt->execute();

                    $conn->commit();
                    exit(json_encode([
                        'status' => 'success', 
                        'message' => "Estoque do produto '{$prod_existente['nome_produto']}' (Lote: $lote) atualizado com sucesso!"
                    ]));
                } else {
                    $conn->rollback();
                    exit(json_encode(['status' => 'error', 'message' => 'Erro ao somar estoque.']));
                }
            }
        }

        // --- SE CHEGOU AQUI, É UM NOVO LOTE OU UM NOVO PRODUTO ---
        $id_fornecedor = !empty($_POST['id_fornecedor']) ? (int)$_POST['id_fornecedor'] : null;

        $dados_base = [
            $codigo, $nome, $_POST['principio_ativo'] ?? '', $_POST['categoria'] ?? '',
            $_POST['tipo_apresentacao'] ?? '', $_POST['dosagem_peso'] ?? '',
            (float)($_POST['preco_compra'] ?? 0), (float)($_POST['preco_venda_caixa'] ?? 0),
            (float)($_POST['taxa_iva'] ?? 0), (int)($_POST['permite_retalho'] ?? 0),
            (int)($_POST['unidades_por_caixa'] ?? 1), (float)($_POST['preco_venda_unidade'] ?? 0),
            (float)($_POST['preco_promocional'] ?? 0), $est_nova, (int)($_POST['estoque_minimo_caixas'] ?? 5),
            $id_fornecedor, $lote, $validade, $_POST['localizacao_corredor'] ?? '',
            (int)($_POST['requer_receita'] ?? 0), (int)($_POST['refrigerado'] ?? 0), $_POST['status_item'] ?? 'Ativo'
        ];

        $tipos = "ssssssddididiiiisssiis";

        // Tratamento de Imagem
        $foto_nome = null;
        if (!empty($_FILES['foto_produto']['name'])) {
            $ext = pathinfo($_FILES['foto_produto']['name'], PATHINFO_EXTENSION);
            $foto_nome = time() . "_" . uniqid() . "." . $ext;
            if (!is_dir("../uploads/produtos/")) mkdir("../uploads/produtos/", 0777, true);
            move_uploaded_file($_FILES['foto_produto']['tmp_name'], "../uploads/produtos/" . $foto_nome);
        }

        $conn->begin_transaction();
        try {
            if ($id) {
                // MODO EDIÇÃO (UPDATE)
                $sql = "UPDATE produtos SET 
                        codigo_barra=?, nome_produto=?, principio_ativo=?, categoria=?, tipo_apresentacao=?, 
                        dosagem_peso=?, preco_compra=?, preco_venda_caixa=?, taxa_iva=?, permite_retalho=?, 
                        unidades_por_caixa=?, preco_venda_unidade=?, preco_promocional=?, estoque_atual_caixas=?, 
                        estoque_minimo_caixas=?, id_fornecedor=?, lote=?, data_validade=?, localizacao_corredor=?, 
                        requer_receita=?, refrigerado=?, status_item=?, ultima_atualizacao=NOW()";
                
                if ($foto_nome) $sql .= ", foto_produto = '$foto_nome'";
                $sql .= " WHERE id_produto = ?";
                
                $dados_base[] = $id; 
                $tipos .= "i";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($tipos, ...$dados_base);
                $stmt->execute();

                // LOG DE EDIÇÃO DE PRODUTO
                $acaoLog = "UPDATE_PRODUTO";
                $detalhesLog = "O operador {$nome_operador} (ID: {$id_operador}) ALTEROU os dados do produto '{$nome}' (ID: {$id}, Lote: {$lote}).";
            } else {
                // MODO CADASTRO (INSERT)
                $sql = "INSERT INTO produtos (
                            codigo_barra, nome_produto, principio_ativo, categoria, tipo_apresentacao, dosagem_peso,
                            preco_compra, preco_venda_caixa, taxa_iva, permite_retalho, unidades_por_caixa, preco_venda_unidade,
                            preco_promocional, estoque_atual_caixas, estoque_minimo_caixas, id_fornecedor, lote, data_validade,
                            localizacao_corredor, requer_receita, refrigerado, status_item, foto_produto, data_cadastro
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $dados_base[] = $foto_nome; 
                $tipos .= "s";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($tipos, ...$dados_base);
                $stmt->execute();
                
                $id_novo_prod = $conn->insert_id;

                // LOG DE NOVO CADASTRO
                $acaoLog = "CADASTRO_PRODUTO";
                $detalhesLog = "O operador {$nome_operador} (ID: {$id_operador}) CADASTROU o novo produto '{$nome}' (ID Gerado: {$id_novo_prod}, Lote: {$lote}, Estoque Inicial: {$est_nova}).";
            }

            // GRAVAÇÃO EFETIVA NO BANCO DE LOGS
            $logStmt = $conn->prepare("INSERT INTO auditoria_logs (acao, detalhes, modulo, ip_origem) VALUES (?, ?, 'Produtos', ?)");
            $logStmt->bind_param("sss", $acaoLog, $detalhesLog, $ip_origem);
            $logStmt->execute();

            $conn->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => "Erro: " . $e->getMessage()]);
        }
        exit;
    }
}
?>