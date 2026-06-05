<?php
require_once("../config_api.php");

$id_operador_atual = 1; 
$mensagem_acao = "";

// =========================================================================
// 1. PROCESSAMENTO DE LANÇAMENTOS COM SEGURANÇA TRANSAÇÃO (ACID)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_financeira'])) {
    $acao = $_POST['acao_financeira'];

    // A. Cadastrar Nova Conta a Pagar / Dívida
    if ($acao === 'cadastrar_conta') {
        $descricao = $conn->real_escape_string($_POST['descricao']);
        $categoria = $conn->real_escape_string($_POST['categoria']);
        $id_fornecedor = !empty($_POST['id_fornecedor']) ? intval($_POST['id_fornecedor']) : "NULL";
        $valor_original = floatval($_POST['valor_original']);
        $data_vencimento = $conn->real_escape_string($_POST['data_vencimento']);

        // CORRIGIDO: alterado de 'category' para 'categoria' conforme o esquema oficial da tabela
        $sql = "INSERT INTO financeiro_contas_pagar (descricao, categoria, id_referencia_fornecedor, valor_original, data_vencimento, id_operador_cadastro, status_conta) 
                VALUES ('$descricao', '$categoria', $id_fornecedor, $valor_original, '$data_vencimento', $id_operador_atual, 'PENDENTE')";
        
        if ($conn->query($sql)) {
            $mensagem_acao = "<div class='f-badge badge-sucesso' style='padding:12px; margin-bottom:20px; width:100%; text-align:center; font-size:13px; border-radius:8px;'>✓ Conta/Obrigação financeira cadastrada com sucesso!</div>";
        } else {
            $mensagem_acao = "<div class='f-badge badge-perigo' style='padding:12px; margin-bottom:20px; width:100%; text-align:center; font-size:13px; border-radius:8px;'>Erro: " . $conn->error . "</div>";
        }
    }

    // B. Dar Baixa / Liquidar Conta Existente (Efetuar Pagamento Seguro)
    if ($acao === 'pagar_conta') {
        $id_conta = intval($_POST['id_conta']);
        $valor_pago = floatval($_POST['valor_pago']);
        $metodo = $conn->real_escape_string($_POST['metodo_pagamento']);
        
        // CORRIGIDO: alterado de 'category' para 'categoria'
        $busca_conta = $conn->query("SELECT descricao, categoria, valor_original, valor_pago FROM financeiro_contas_pagar WHERE id_conta = $id_conta")->fetch_assoc();
        
        if ($busca_conta) {
            $desc_caixa = "Liquidação: " . $busca_conta['descricao'];
            $total_pago_acumulado = $busca_conta['valor_pago'] + $valor_pago;
            
            // Determina se a liquidação foi total ou parcial
            $novo_status = ($total_pago_acumulado >= $busca_conta['valor_original']) ? 'PAGO' : 'PAGO_PARCIAL';

            // ATIVAÇÃO DO PROTOCOLO SEGURO BANCÁRIO (ACID)
            $conn->begin_transaction();

            try {
                // 1. Atualiza o estado da obrigação financeira
                $conn->query("UPDATE financeiro_contas_pagar SET valor_pago = $total_pago_acumulado, status_conta = '$novo_status', data_pagamento = CURDATE(), id_operador_baixa = $id_operador_atual WHERE id_conta = $id_conta");
                
                // 2. CORRIGIDO: mudado de 'origen' para 'origem'. Lança a saída real no fluxo de caixa da farmácia
                $conn->query("INSERT INTO financeiro_movimentacoes (tipo, origem, id_referencia, valor_bruto, desconto, valor_liquido, metodo_pagamento, id_operador, descricao) 
                              VALUES ('DESPESA', 'CONTAS_A_PAGAR', $id_conta, $valor_pago, 0.00, $valor_pago, '$metodo', $id_operador_atual, '$desc_caixa')");
                
                // Se ambas as queries correrem bem, consolida permanentemente
                $conn->commit();
                $mensagem_acao = "<div class='f-badge badge-sucesso' style='padding:12px; margin-bottom:20px; width:100%; text-align:center; font-size:13px; border-radius:8px;'>✓ Baixa processada! Conta definida como [$novo_status]. Movimentação gerada.</div>";
            } catch (Exception $e) {
                // Se houver qualquer falha ou queda de energia, desfaz tudo para evitar quebras no saldo
                $conn->rollback();
                $mensagem_acao = "<div class='f-badge badge-perigo' style='padding:12px; margin-bottom:20px; width:100%; text-align:center; font-size:13px; border-radius:8px;'>⚠️ Falha Crítica: A operação foi abortada para proteger a integridade do caixa.</div>";
            }
        }
    }

    // C. Processar Pagamento de Salário (Folha de Funcionários)
    if ($acao === 'pagar_funcionario') {
        $id_funcionario = intval($_POST['id_funcionario']);
        $mes = intval($_POST['mes_referencia']);
        $ano = intval($_POST['ano_referencia']);
        $salario_base = floatval($_POST['salario_base']);
        $subsidios = floatval($_POST['subsidios_bonus']);
        $faltas = floatval($_POST['descontos_faltas']);
        $impostos = floatval($_POST['descontos_impostos']); // Retenções (INSS / IRT)
        
        $valor_pago_liquido = ($salario_base + $subsidios) - ($faltas + $impostos);
        $forma_pagto = $conn->real_escape_string($_POST['forma_pagamento']);
        $obs = $conn->real_escape_string($_POST['observacoes']);

        $conn->begin_transaction();
        try {
            // 1. Insere o registo na folha de pagamentos
            $conn->query("INSERT INTO financeiro_pagamentos_funcionarios (id_funcionario, mes_referencia, ano_referencia, salario_base, subsidios_bonus, descontos_faltas, descontos_impostos, valor_pago_liquido, data_pagamento, forma_pagamento, id_operador, status_pagamento, observacoes) 
                          VALUES ($id_funcionario, $mes, $ano, $salario_base, $subsidios, $faltas, $impostos, $valor_pago_liquido, NOW(), '$forma_pagto', $id_operador_atual, 'PAGO', '$obs')");
            $id_folha = $conn->insert_id;

            // 2. Descarrega a saída imediata do Livro de Caixa da Tesouraria
            $desc_salario = "Liquidação Salarial - Cód Func #" . $id_funcionario . " (Mês " . $mes . "/" . $ano . ")";
            $conn->query("INSERT INTO financeiro_movimentacoes (tipo, origem, id_referencia, valor_bruto, desconto, valor_liquido, metodo_pagamento, id_operador, descricao) 
                          VALUES ('DESPESA', 'PAGAMENTO_FUNCIONARIOS', $id_folha, $valor_pago_liquido, 0.00, $valor_pago_liquido, '$forma_pagto', $id_operador_atual, '$desc_salario')");

            $conn->commit();
            $mensagem_acao = "<div class='f-badge badge-sucesso' style='padding:12px; margin-bottom:20px; width:100%; text-align:center; font-size:13px; border-radius:8px;'>✓ Folha de salário processada! Caixa deduzido com sucesso.</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $mensagem_acao = "<div class='f-badge badge-perigo' style='padding:12px; margin-bottom:20px; width:100%; text-align:center; font-size:13px; border-radius:8px;'>Erro ao processar folha: " . $conn->error . "</div>";
        }
    }
}

// =========================================================================
// 2. PROCESSAMENTO DE MÉTRICAS AND BALANÇOS CONSOLIDADOS
// =========================================================================

// A. Fluxo de Caixa Real
$query_receitas = $conn->query("SELECT SUM(valor_liquido) as total FROM financeiro_movimentacoes WHERE tipo = 'RECEITA'");
$tot_receitas = $query_receitas->fetch_assoc()['total'] ?? 0.00;

$query_despesas = $conn->query("SELECT SUM(valor_liquido) as total FROM financeiro_movimentacoes WHERE tipo = 'DESPESA'");
$tot_despesas = $query_despesas->fetch_assoc()['total'] ?? 0.00;

$query_perdas_caixa = $conn->query("SELECT SUM(valor_liquido) as total FROM financeiro_movimentacoes WHERE tipo = 'PERDA'");
$tot_perdas_caixa = $query_perdas_caixa->fetch_assoc()['total'] ?? 0.00;

$saldo_caixa = $tot_receitas - ($tot_despesas + $tot_perdas_caixa);

// B. Faturamento Total do PDV
$query_faturamento_vendas = $conn->query("SELECT SUM(total_final) as total FROM vendas WHERE status_venda = 'Concluida'");
$tot_vendas_concluidas = $query_faturamento_vendas->fetch_assoc()['total'] ?? 0.00;

// C. Prejuízo por Perdas e Quebras de Estoque
$query_prejuizo_perdas = $conn->query("SELECT SUM(valor_prejuizo_total) as total FROM perdas");
$tot_prejuizo_perdas = $query_prejuizo_perdas->fetch_assoc()['total'] ?? 0.00;

// D. Valor do Estoque (Custos de Aquisição)
$query_patrimonio = $conn->query("SELECT SUM(estoque_atual_caixas * preco_compra) as total_custo FROM produtos WHERE status_item = 'Ativo'");
$valor_estoque_custo = $query_patrimonio->fetch_assoc()['total_custo'] ?? 0.00;
$patrimonio_liquido_custo = $valor_estoque_custo - $tot_prejuizo_perdas;

// E. Compromissos Financeiros Ativos / Dívidas Pendentes
$query_compromissos = $conn->query("SELECT SUM(valor_original - valor_pago) as total FROM financeiro_contas_pagar WHERE status_conta IN ('PENDENTE', 'PAGO_PARCIAL', 'VENCIDO')");
$tot_compromissos_pendentes = $query_compromissos->fetch_assoc()['total'] ?? 0.00;

// =========================================================================
// 3. CONSULTAS DE LISTAGEM
// =========================================================================
$res_movimentacoes = $conn->query("SELECT m.*, f.nome_completo as nome_operador FROM financeiro_movimentacoes m LEFT JOIN funcionarios f ON m.id_operador = f.id_sistema ORDER BY m.id_financeiro DESC LIMIT 50");
$res_vendas = $conn->query("SELECT v.*, f.nome_completo as nome_vendedor FROM vendas v LEFT JOIN funcionarios f ON v.id_usuario = f.id_sistema ORDER BY v.id_venda DESC LIMIT 50");
$res_contas = $conn->query("SELECT cp.*, forn.nome as nome_fornecedor, forn.nif as nif_fornecedor FROM financeiro_contas_pagar cp LEFT JOIN fornecedores forn ON cp.id_referencia_fornecedor = forn.id_fornecedor ORDER BY cp.data_vencimento ASC");
$res_perdas = $conn->query("SELECT p.*, prod.nome_produto, func.nome_completo as nome_responsavel FROM perdas p LEFT JOIN produtos prod ON p.id_produto = prod.id_produto LEFT JOIN funcionarios func ON p.id_funcionario_responsavel = func.id_funcionario ORDER BY p.data_registro DESC");

$select_fornecedores = $conn->query("SELECT id_fornecedor, nome FROM fornecedores WHERE status_fornecedor = 'Ativo' ORDER BY nome ASC");

// Carregamento do histórico de folhas de pagamento
$res_folha_pagamentos = $conn->query("SELECT pf.*, f.nome_completo as nome_colaborador, admin.nome_completo as nome_admin FROM financeiro_pagamentos_funcionarios pf LEFT JOIN funcionarios f ON pf.id_funcionario = f.id_sistema LEFT JOIN funcionarios admin ON pf.id_operador = admin.id_sistema ORDER BY pf.id_pagamento DESC");

// AJUSTADO: Query adaptada para a nova tabela 'funcionarios' (estado_trabalho = 'Ativo' e sem coluna salario_base)
// Faz um COALESCE para buscar o último salário base pago historicamente (ou retorna 0.00 padrão)
$select_funcionarios_ativos = $conn->query("
    SELECT f.id_sistema, f.nome_completo,
           COALESCE((
               SELECT pf.salario_base 
               FROM financeiro_pagamentos_funcionarios pf 
               WHERE pf.id_funcionario = f.id_sistema 
               ORDER BY pf.id_pagamento DESC LIMIT 1
           ), 0.00) as salario_base
    FROM funcionarios f 
    WHERE f.estado_trabalho = 'Ativo' 
    ORDER BY f.nome_completo ASC
");
?>

<style>
/* ============ ESTRUTURA E TEMA BASE ============ */
.rh-container {
    width: 100%;
    animation: fadeIn 0.4s ease;
    color: var(--text-main);
    font-family: 'Inter', sans-serif;
    padding: 15px;
    box-sizing: border-box;
}

.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.header-info h2 {
    color: var(--accent);
    font-weight: 800;
    margin: 0;
    font-size: clamp(18px, 2.5vw, 22px);
    display: flex;
    align-items: center;
    gap: 10px;
}

.header-info p {
    color: var(--text-dim);
    font-size: 12px;
    margin-top: 3px;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* BOTÕES DE INTERAÇÃO */
.btn-primary-pharmora { background: var(--accent); color: white; border: none; padding: 10px 16px; border-radius: 10px; font-weight: 600; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: background 0.2s; text-decoration:none; }
.btn-primary-pharmora:hover { background: #1d4ed8; }
.btn-baixa { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: 0.2s; }
.btn-baixa:hover { background: #22c55e; color: white; }

/* ============ GRID DE CARDS SUPER COMPACTO E RESPONSIVO ============ */
.finance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
    margin-bottom: 22px;
}

.finance-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 12px;
    padding: 12px 15px;
    backdrop-filter: blur(15px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 65px;
}

.finance-card-icon {
    font-size: 16px;
    padding: 10px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.finance-card-info { 
    display: flex; 
    flex-direction: column; 
    min-width: 0;
}
.finance-card-info .label { font-size: 11px; color: var(--text-dim); text-transform: uppercase; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.finance-card-info .value { font-size: 16px; font-weight: 800; margin-top: 2px; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.finance-card-info .sub-value { font-size: 10px; color: var(--text-dim); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.card-saldo .finance-card-icon { background: rgba(37, 99, 235, 0.1); color: var(--accent); border: 1px solid rgba(37, 99, 235, 0.15); }
.card-vendas .finance-card-icon { background: rgba(22, 163, 74, 0.1); color: #4ade80; border: 1px solid rgba(22, 163, 74, 0.15); }
.card-perdas .finance-card-icon { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.15); }
.card-contas .finance-card-icon { background: rgba(245, 158, 11, 0.1); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.15); }

/* ============ NAVEGAÇÃO DE ABAS ============ */
.tabs-navigation {
    display: flex;
    gap: 6px;
    margin-bottom: 20px;
    border-bottom: 1px solid var(--card-border);
    padding-bottom: 8px;
    overflow-x: auto;
    scrollbar-width: none;
}
.tabs-navigation::-webkit-scrollbar { display: none; }

.tab-btn {
    background: transparent;
    border: 1px solid transparent;
    color: var(--text-dim);
    padding: 8px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    transition: all 0.2s ease;
}
.tab-btn:hover { color: var(--text-main); background: rgba(255, 255, 255, 0.02); }
.tab-btn.active { background: var(--accent); color: white; }

.tab-panel { display: none; animation: fadeIn 0.2s ease; }
.tab-panel.active { display: block; }

/* INTERAÇÕES E CONSULTAS */
.action-row { margin-bottom: 15px; }
.search-box { position: relative; width: 100%; max-width: 360px; }
.search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-dim); font-size: 12px; }
.search-box input {
    width: 100%;
    background: var(--input-fill);
    border: 1px solid var(--card-border);
    color: var(--text-main);
    padding: 10px 12px 10px 36px;
    border-radius: 8px;
    outline: none;
    font-size: 13px;
}
.search-box input:focus { border-color: var(--accent); }

/* DESIGN DE TABELAS */
.table-glass {
    width: 100%;
    background: var(--card-bg);
    border-radius: 12px;
    overflow-x: auto;
    border: 1px solid var(--card-border);
}
table { width: 100%; border-collapse: collapse; min-width: 900px; }
th { background: rgba(255,255,255,0.01); padding: 12px 10px; text-align: left; color: var(--text-dim); font-size: 10px; text-transform: uppercase; font-weight: 700; border-bottom: 2px solid var(--card-border); }
td { padding: 12px 10px; border-bottom: 1px solid var(--card-border); font-size: 12.5px; }

.f-badge { padding: 3px 8px; border-radius: 5px; font-size: 9px; font-weight: 700; display: inline-block; text-transform: uppercase; }
.badge-sucesso { background: rgba(22, 163, 74, 0.12); color: #4ade80; border: 1px solid rgba(22, 163, 74, 0.2); }
.badge-perigo { background: rgba(239, 68, 68, 0.12); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }
.badge-alerta { background: rgba(245, 158, 11, 0.12); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.2); }
.badge-info { background: rgba(37, 99, 235, 0.12); color: #60a5fa; border: 1px solid rgba(37, 99, 235, 0.2); }

.text-positive { color: #4ade80; font-weight: 600; }
.text-negative { color: #f87171; font-weight: 600; }

/* MODAIS FLUIDOS */
.pharmora-modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 9999; justify-content: center; align-items: center; padding: 15px; box-sizing: border-box; }
.modal-content { background: #111827; border: 1px solid rgba(255,255,255,0.08); padding: 20px; border-radius: 12px; width: 100%; max-width: 440px; box-shadow: 0 15px 30px rgba(0,0,0,0.5); }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
.modal-header h3 { margin:0; font-size: 14px; color: var(--accent); font-weight: 700; text-transform: uppercase; }
.modal-close { background: none; border:none; color: var(--text-dim); font-size: 20px; cursor: pointer; }
.modal-close:hover { color: #fff; }
.form-group { margin-bottom: 12px; display: flex; flex-direction: column; gap: 4px; }
.form-group label { font-size: 11px; color: var(--text-dim); font-weight: 600; }
.form-group input, .form-group select { background: #1f2937; border: 1px solid #374151; padding: 9px 12px; border-radius: 6px; color: #fff; font-size: 13px; outline:none; }
.form-group input:focus, .form-group select:focus { border-color: var(--accent); }

@keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }

@media (max-width: 768px) {
    .top-bar { flex-direction: column; align-items: flex-start; }
    .action-buttons { width: 100%; }
    .btn-primary-pharmora { width: 100%; justify-content: center; }
    .search-box { max-width: 100%; }
}
</style>

<div class="rh-container">
    
    <?php echo $mensagem_acao; ?>
    
    <div class="top-bar">
        <div class="header-info">
            <h2><i class="fas fa-chart-line"></i> Gestão Financeira Unificada</h2>
            <p>Auditoria cruzada em tempo real de faturamento, tesouraria, despesas e perdas físicas</p>
        </div>
        <div class="action-buttons">
            <button class="btn-primary-pharmora" onclick="abrirModal('modalConta')">
                <i class="fas fa-plus"></i> Lançar Conta / Obrigação
            </button>
            <button class="btn-primary-pharmora" onclick="abrirModal('modalSalario')" style="background: #8b5cf6;">
                <i class="fas fa-hand-holding-usd"></i> Processar Salário / Folha
            </button>
        </div>
    </div>

    <div class="finance-grid">
        <div class="finance-card card-saldo">
            <div class="finance-card-icon"><i class="fas fa-vault"></i></div>
            <div class="finance-card-info">
                <span class="label">Disponibilidade em Caixa</span>
                <span class="value" id="saldo_caixa"><?php echo number_format($saldo_caixa, 2, ',', '.'); ?> Kz</span>
                <span class="sub-value">Fluxo líquido real</span>
            </div>
        </div>

        <div class="finance-card card-vendas">
            <div class="finance-card-icon"><i class="fas fa-shopping-basket"></i></div>
            <div class="finance-card-info">
                <span class="label">Vendas Concluídas (PDV)</span>
                <span class="value" id="tot_vendas_concluidas"><?php echo number_format($tot_vendas_concluidas, 2, ',', '.'); ?> Kz</span>
                <span class="sub-value">Faturamento total acumulado</span>
            </div>
        </div>

        <div class="finance-card card-perdas">
            <div class="finance-card-icon"><i class="fas fa-dumpster"></i></div>
            <div class="finance-card-info">
                <span class="label">Património Líquido Est.</span>
                <span class="value" id="patrimonio_liquido_custo" style="color:#60a5fa;"><?php echo number_format($patrimonio_liquido_custo, 2, ',', '.'); ?> Kz</span>
                <span class="sub-value" id="tot_prejuizo_perdas" style="color:#f87171;">Avarias: <?php echo number_format($tot_prejuizo_perdas, 2, ',', '.'); ?> Kz</span>
            </div>
        </div>

        <div class="finance-card card-contas">
            <div class="finance-card-icon"><i class="fas fa-file-invoice-dollar"></i></div>
            <div class="finance-card-info">
                <span class="label">Contas a Pagar Ativas</span>
                <span class="value" id="tot_compromissos_pendentes" style="color:#fbbf24;"><?php echo number_format($tot_compromissos_pendentes, 2, ',', '.'); ?> Kz</span>
                <span class="sub-value">Obrigações futuras</span>
            </div>
        </div>
    </div>

    <div class="tabs-navigation">
        <button class="tab-btn active" onclick="trocarModulo(event, 'modulo-caixa')">
            <i class="fas fa-cash-register"></i> Caixa Real
        </button>
        <button class="tab-btn" onclick="trocarModulo(event, 'modulo-vendas')">
            <i class="fas fa-receipt"></i> Histórico de Vendas
        </button>
        <button class="tab-btn" onclick="trocarModulo(event, 'modulo-contas')">
            <i class="fas fa-truck-loading"></i> Contas & Fornecedores
        </button>
        <button class="tab-btn" onclick="trocarModulo(event, 'modulo-funcionarios')">
            <i class="fas fa-users"></i> Folha de Funcionários
        </button>
        <button class="tab-btn" onclick="trocarModulo(event, 'modulo-perdas')">
            <i class="fas fa-exclamation-circle"></i> Histórico de Perdas
        </button>
    </div>

    <div id="modulo-caixa" class="tab-panel active">
        <div class="action-row">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="filtroCaixa" onkeyup="filtrarTabela('filtroCaixa', 'tabelaCaixa')" placeholder="Filtrar por fluxo ou descrição...">
            </div>
        </div>
        <div class="table-glass">
            <table id="tabelaCaixa">
                <thead>
                    <tr>
                        <th>ID Ref</th>
                        <th>Tipo</th>
                        <th>Origem / Categoria</th>
                        <th>Método</th>
                        <th>Valor Bruto</th>
                        <th>Desconto</th>
                        <th>Líquido</th>
                        <th>Data Registro</th>
                        <th>Operador</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_movimentacoes && $res_movimentacoes->num_rows > 0): ?>
                        <?php while($mov = $res_movimentacoes->fetch_assoc()): 
                            $is_receita = ($mov['tipo'] == 'RECEITA');
                        ?>
                        <tr>
                            <td>#<?php echo $mov['id_financeiro']; ?></td>
                            <td><span class="f-badge <?php echo $is_receita ? 'badge-sucesso' : 'badge-perigo'; ?>"><?php echo $mov['tipo']; ?></span></td>
                            <td>
                                <strong><?php echo htmlspecialchars(str_replace('_', ' ', $mov['origem'])); ?></strong>
                                <?php if($mov['descricao']): ?><br><small style="color:var(--text-dim);"><?php echo htmlspecialchars($mov['descricao']); ?></small><?php endif; ?>
                            </td>
                            <td style="text-transform:uppercase; font-size:11px;"><?php echo htmlspecialchars($mov['metodo_pagamento']); ?></td>
                            <td><?php echo number_format($mov['valor_bruto'], 2, ',', '.'); ?> Kz</td>
                            <td><?php echo number_format($mov['desconto'], 2, ',', '.'); ?> Kz</td>
                            <td class="<?php echo $is_receita ? 'text-positive' : 'text-negative'; ?>">
                                <?php echo ($is_receita ? '+ ' : '- ') . number_format($mov['valor_liquido'], 2, ',', '.'); ?> Kz
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($mov['data_registro'])); ?></td>
                            <td><?php echo htmlspecialchars($mov['nome_operador'] ?: 'Sistema'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align:center; padding:30px; color:var(--text-dim);">Sem movimentações na tesouraria.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modulo-vendas" class="tab-panel">
        <div class="action-row">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="filtroVendas" onkeyup="filtrarTabela('filtroVendas', 'tabelaVendas')" placeholder="Filtrar por cliente, pagamento...">
            </div>
        </div>
        <div class="table-glass">
            <table id="tabelaVendas">
                <thead>
                    <tr>
                        <th>ID Venda</th>
                        <th>Data / Hora</th>
                        <th>Cliente</th>
                        <th>Forma Pagamento</th>
                        <th>Bruto Produtos</th>
                        <th>Desconto</th>
                        <th>Total Final</th>
                        <th>Vendedor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_vendas && $res_vendas->num_rows > 0): ?>
                        <?php while($vd = $res_vendas->fetch_assoc()): 
                            $status_cl = ($vd['status_venda'] == 'Concluida') ? 'badge-sucesso' : (($vd['status_venda'] == 'Cancelada') ? 'badge-perigo' : 'badge-alerta');
                        ?>
                        <tr>
                            <td>#<?php echo $vd['id_venda']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($vd['data_venda'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($vd['nome_cliente']); ?></strong></td>
                            <td><?php echo $vd['forma_pagamento']; ?></td>
                            <td><?php echo number_format($vd['total_produtos'], 2, ',', '.'); ?> Kz</td>
                            <td><?php echo number_format($vd['desconto_venda'], 2, ',', '.'); ?> Kz</td>
                            <td style="font-weight:700;"><?php echo number_format($vd['total_final'], 2, ',', '.'); ?> Kz</td>
                            <td><?php echo htmlspecialchars($vd['nome_vendedor'] ?: 'PDV'); ?></td>
                            <td><span class="f-badge <?php echo $status_cl; ?>"><?php echo $vd['status_venda']; ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align:center; padding:30px; color:var(--text-dim);">Nenhuma venda registrada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modulo-contas" class="tab-panel">
        <div class="action-row">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="filtroContas" onkeyup="filtrarTabela('filtroContas', 'tabelaContas')" placeholder="Filtrar obrigações ou fornecedores...">
            </div>
        </div>
        <div class="table-glass">
            <table id="tabelaContas">
                <thead>
                    <tr>
                        <th>ID Conta</th>
                        <th>Fornecedor / Credor</th>
                        <th>NIF</th>
                        <th>Descrição da Despesa</th>
                        <th>Valor Original</th>
                        <th>Valor Pago</th>
                        <th>Vencimento</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_contas && $res_contas->num_rows > 0): ?>
                        <?php while($cp = $res_contas->fetch_assoc()): 
                            $badge_status = 'badge-alerta';
                            $esta_pendente = true;
                            if($cp['status_conta'] == 'PAGO') { $badge_status = 'badge-sucesso'; $esta_pendente = false; }
                            if($cp['status_conta'] == 'VENCIDO') $badge_status = 'badge-perigo';
                            if($cp['status_conta'] == 'PAGO_PARCIAL') $badge_status = 'badge-info';
                        ?>
                        <tr>
                            <td>#<?php echo $cp['id_conta']; ?></td>
                            <td><strong><?php echo htmlspecialchars($cp['nome_fornecedor'] ?: 'Geral / Administrativo'); ?></strong></td>
                            <td><small style="color:var(--text-dim);"><?php echo htmlspecialchars($cp['nif_fornecedor'] ?: 'N/D'); ?></small></td>
                            <td><?php echo htmlspecialchars($cp['descricao']); ?></td>
                            <td><?php echo number_format($cp['valor_original'], 2, ',', '.'); ?> Kz</td>
                            <td class="text-positive"><?php echo number_format($cp['valor_pago'], 2, ',', '.'); ?> Kz</td>
                            <td><?php echo date('d/m/Y', strtotime($cp['data_vencimento'])); ?></td>
                            <td><span class="f-badge <?php echo $badge_status; ?>"><?php echo str_replace('_', ' ', $cp['status_conta']); ?></span></td>
                            <td>
                                <?php if($esta_pendente): ?>
                                    <button class="btn-baixa" onclick="prepararBaixa(<?php echo $cp['id_conta']; ?>, <?php echo $cp['valor_original'] - $cp['valor_pago']; ?>)">
                                        <i class="fas fa-check-double"></i> Dar Baixa
                                    </button>
                                <?php else: ?>
                                    <span style="color:var(--text-dim); font-size:11px;">✓ Liquidado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align:center; padding:30px; color:var(--text-dim);">Nenhuma conta cadastrada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modulo-funcionarios" class="tab-panel">
        <div class="action-row">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="filtroFolha" onkeyup="filtrarTabela('filtroFolha', 'tabelaFolha')" placeholder="Filtrar por funcionário ou período...">
            </div>
        </div>
        <div class="table-glass">
            <table id="tabelaFolha">
                <thead>
                    <tr>
                        <th>Cód Recibo</th>
                        <th>Colaborador</th>
                        <th>Período Ref</th>
                        <th>Salário Base</th>
                        <th>Subsídios / Bónus</th>
                        <th>Descontos / Faltas</th>
                        <th>Retenção (IRT/INSS)</th>
                        <th>Líquido Recebido</th>
                        <th>Data Pagamento</th>
                        <th>Autorizado por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_folha_pagamentos && $res_folha_pagamentos->num_rows > 0): ?>
                        <?php while($fl = $res_folha_pagamentos->fetch_assoc()): 
                            $meses_ano = [1=>"Janeiro", 2=>"Fevereiro", 3=>"Março", 4=>"Abril", 5=>"Maio", 6=>"Junho", 7=>"Julho", 8=>"Agosto", 9=>"Setembro", 10=>"Outubro", 11=>"Novembro", 12=>"Dezembro"];
                        ?>
                        <tr>
                            <td>#<?php echo $fl['id_pagamento']; ?></td>
                            <td><strong><?php echo htmlspecialchars($fl['nome_colaborador']); ?></strong></td>
                            <td><span class="f-badge badge-info"><?php echo $meses_ano[$fl['mes_referencia']] . " / " . $fl['ano_referencia']; ?></span></td>
                            <td><?php echo number_format($fl['salario_base'], 2, ',', '.'); ?> Kz</td>
                            <td class="text-positive">+ <?php echo number_format($fl['subsidios_bonus'], 2, ',', '.'); ?> Kz</td>
                            <td class="text-negative">- <?php echo number_format($fl['descontos_faltas'], 2, ',', '.'); ?> Kz</td>
                            <td style="color:#fbbf24;">- <?php echo number_format($fl['descontos_impostos'], 2, ',', '.'); ?> Kz</td>
                            <td style="font-weight:800; color:#4ade80;"><?php echo number_format($fl['valor_pago_liquido'], 2, ',', '.'); ?> Kz</td>
                            <td><?php echo date('d/m/Y H:i', strtotime($fl['data_pagamento'])); ?></td>
                            <td><small><?php echo htmlspecialchars($fl['nome_admin'] ?: 'Admin'); ?></small></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="10" style="text-align:center; padding:30px; color:var(--text-dim);">Nenhum processamento de salário registrado neste exercício.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modulo-perdas" class="tab-panel">
        <div class="action-row">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="filtroPerdas" onkeyup="filtrarTabela('filtroPerdas', 'tabelaPerdas')" placeholder="Filtrar perdas de inventário...">
            </div>
        </div>
        <div class="table-glass">
            <table id="tabelaPerdas">
                <thead>
                    <tr>
                        <th>ID Perda</th>
                        <th>Produto Auditado</th>
                        <th>Qtd Perdida</th>
                        <th>Motivo Ocorrência</th>
                        <th>Custo Un.</th>
                        <th>Prejuízo Total</th>
                        <th>Data Registro</th>
                        <th>Responsável</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_perdas && $res_perdas->num_rows > 0): ?>
                        <?php while($pr = $res_perdas->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $pr['id_perda']; ?></td>
                            <td><strong><?php echo htmlspecialchars($pr['nome_produto'] ?: 'Produto Desconhecido'); ?></strong></td>
                            <td style="font-weight:600;"><?php echo $pr['quantidade']; ?> un</td>
                            <td><span class="f-badge badge-alerta"><?php echo htmlspecialchars($pr['motivo']); ?></span></td>
                            <td><?php echo number_format($pr['preco_custo_unidade'], 2, ',', '.'); ?> Kz</td>
                            <td class="text-negative"><?php echo number_format($pr['valor_prejuizo_total'], 2, ',', '.'); ?> Kz</td>
                            <td><?php echo date('d/m/Y H:i', strtotime($pr['data_registro'])); ?></td>
                            <td><?php echo htmlspecialchars($pr['nome_responsavel'] ?: 'N/D'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center; padding:30px; color:var(--text-dim);">Nenhuma avaria catalogada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<div id="modalConta" class="pharmora-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Lançar Obrigação / Despesa</h3>
            <button class="modal-close" onclick="fecharModal('modalConta')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="acao_financeira" value="cadastrar_conta">
            <div class="form-group">
                <label>Descrição do Custo</label>
                <input type="text" name="descricao" placeholder="Ex: Renda Mensal das Instalações" required>
            </div>
            <div class="form-group">
                <label>Categoria de Fluxo</label>
                <select name="categoria">
                    <option value="FORNECEDOR">Fornecedor de Medicamentos</option>
                    <option value="INFRAESTRUTURA">Infraestrutura (Luz, Água, Renda)</option>
                    <option value="IMPOSTOS">AGT / Impostos e Taxas</option>
                    <option value="MARKETING">Marketing e Propaganda</option>
                    <option value="OUTROS">Outras Despesas Operacionais</option>
                </select>
            </div>
            <div class="form-group">
                <label>Credor / Fornecedor Vinculado</label>
                <select name="id_fornecedor">
                    <option value="">Nenhum (Custo Administrativo Geral)</option>
                    <?php if($select_fornecedores): $select_fornecedores->data_seek(0); while($f = $select_fornecedores->fetch_assoc()): ?>
                        <option value="<?php echo $f['id_fornecedor']; ?>"><?php echo htmlspecialchars($f['nome']); ?></option>
                    <?php endwhile; endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Valor Nominal (Kz)</label>
                <input type="number" step="0.01" name="valor_original" placeholder="0.00" required>
            </div>
            <div class="form-group">
                <label>Data de Vencimento</label>
                <input type="date" name="data_vencimento" required>
            </div>
            <button type="submit" class="btn-primary-pharmora" style="width:100%; justify-content:center; margin-top:5px;">
                Registrar Obrigação
            </button>
        </form>
    </div>
</div>

<div id="modalBaixa" class="pharmora-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Efetuar Liquidação de Conta</h3>
            <button class="modal-close" onclick="fecharModal('modalBaixa')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="acao_financeira" value="pagar_conta">
            <input type="hidden" name="id_conta" id="baixa_id_conta">
            <div class="form-group">
                <label>Valor a Liquidar (Kz)</label>
                <input type="number" step="0.01" name="valor_pago" id="baixa_valor_pago" required>
            </div>
            <div class="form-group">
                <label>Canal / Método de Saída</label>
                <select name="metodo_pagamento">
                    <option value="DINHEIRO">Dinheiro Vivo (Caixa)</option>
                    <option value="TPA">Terminal TPA / Multicaixa</option>
                    <option value="TRANSFERENCIA">Transferência Bancária</option>
                </select>
            </div>
            <button type="submit" class="btn-primary-pharmora" style="width:100%; justify-content:center; margin-top:5px; background:#22c55e;">
                Confirmar Saída do Caixa
            </button>
        </form>
    </div>
</div>

<div id="modalSalario" class="pharmora-modal">
    <div class="modal-content" style="max-width: 480px;">
        <div class="modal-header">
            <h3>Processar Folha de Salário</h3>
            <button class="modal-close" onclick="fecharModal('modalSalario')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="acao_financeira" value="pagar_funcionario">
            
            <div class="form-group">
                <label>Selecionar Colaborador</label>
                <select name="id_funcionario" id="salario_id_funcionario" onchange="atualizarSalarioBase()" required>
                    <option value="">Selecione um funcionário ativo...</option>
                    <?php if($select_funcionarios_ativos): $select_funcionarios_ativos->data_seek(0); while($fn = $select_funcionarios_ativos->fetch_assoc()): ?>
                        <option value="<?php echo $fn['id_sistema']; ?>" data-salario="<?php echo $fn['salario_base']; ?>">
                            <?php echo htmlspecialchars($fn['nome_completo']); ?>
                        </option>
                    <?php endwhile; endif; ?>
                </select>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div class="form-group">
                    <label>Mês de Referência</label>
                    <select name="mes_referencia">
                        <?php for($m=1; $m<=12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo ($m == date('n')) ? 'selected' : ''; ?>><?php echo $m; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ano de Referência</label>
                    <input type="number" name="ano_referencia" value="<?php echo date('Y'); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Salário Base Recomendado (Kz)</label>
                <input type="number" step="0.01" name="salario_base" id="salario_base_input" placeholder="0.00" required>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div class="form-group">
                    <label>Subsídios / Bónus (+)</label>
                    <input type="number" step="0.01" name="subsidios_bonus" value="0.00">
                </div>
                <div class="form-group">
                    <label>Descontos / Faltas (-)</label>
                    <input type="number" step="0.01" name="descontos_faltas" value="0.00">
                </div>
            </div>

            <div class="form-group">
                <label>Retenções Fiscais (IRT + Segurança Social INSS)</label>
                <input type="number" step="0.01" name="descontos_impostos" value="0.00" placeholder="Insira o valor acumulado retido">
            </div>

            <div class="form-group">
                <label>Forma de Pagamento</label>
                <select name="forma_pagamento">
                    <option value="Transferência Bancária">Transferência Bancária</option>
                    <option value="DINHEIRO">Dinheiro Vivo (Caixa)</option>
                    <option value="Multicaixa / TPA">Multicaixa / TPA</option>
                </select>
            </div>

            <div class="form-group">
                <label>Observações / Notas Internas</label>
                <input type="text" name="observacoes" placeholder="Ex: Inclui bónus por metas atingidas no balcão">
            </div>

            <button type="submit" class="btn-primary-pharmora" style="width:100%; justify-content:center; margin-top:5px; background:#8b5cf6;">
                Emitir Recibo & Dar Baixa no Caixa
            </button>
        </form>
    </div>
</div>

<script>
// Garante o funcionamento dos botões de abas mesmo sob carregamento dinâmico por AJAX
window.trocarModulo = function(evt, idModulo) {
    let panels = document.getElementsByClassName("tab-panel");
    for (let i = 0; i < panels.length; i++) panels[i].classList.remove("active");

    let btns = document.getElementsByClassName("tab-btn");
    for (let i = 0; i < btns.length; i++) btns[i].classList.remove("active");

    let targetPanel = document.getElementById(idModulo);
    if (targetPanel) targetPanel.classList.add("active");
    if (evt && evt.currentTarget) evt.currentTarget.classList.add("active");
}

// Garante a abertura dos Modais interceptando falhas de escopo isolado
window.abrirModal = function(idModal) {
    let modal = document.getElementById(idModal);
    if (modal) modal.style.display = "flex";
}

window.fecharModal = function(idModal) {
    let modal = document.getElementById(idModal);
    if (modal) modal.style.display = "none";
}

window.prepararBaixa = function(idConta, saldoPendente) {
    let campoId = document.getElementById('baixa_id_conta');
    let campoValor = document.getElementById('baixa_valor_pago');
    if (campoId) campoId.value = idConta;
    if (campoValor) campoValor.value = parseFloat(saldoPendente).toFixed(2);
    window.abrirModal('modalBaixa');
}

// Resgata automaticamente do atributo data-salario o histórico injetado pela subconsulta PHP
window.atualizarSalarioBase = function() {
    let select = document.getElementById('salario_id_funcionario');
    if (!select) return;
    let selectedOption = select.options[select.selectedIndex];
    let salario = selectedOption ? selectedOption.getAttribute('data-salario') : null;
    let inputSalario = document.getElementById('salario_base_input');
    
    if (inputSalario) {
        inputSalario.value = salario ? parseFloat(salario).toFixed(2) : "0.00";
    }
}

window.filtrarTabela = function(idInput, idTabela) {
    let input = document.getElementById(idInput);
    if (!input) return;
    let filter = input.value.toUpperCase();
    let table = document.getElementById(idTabela);
    if (!table) return;
    let tr = table.getElementsByTagName("tr");

    for (let i = 1; i < tr.length; i++) {
        tr[i].style.display = "none";
        let td = tr[i].getElementsByTagName("td");
        for (let j = 0; j < td.length; j++) {
            if (td[j]) {
                let txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                    break;
                }
            }
        }
    }
}

// Fecha os modais ao clicar na área escura de fundo de forma segura
window.onclick = function(event) {
    if (event.target && event.target.classList && event.target.classList.contains('pharmora-modal')) {
        event.target.style.display = "none";
    }
}

// CORREÇÃO PROTEÇÃO TYPEERROR: Evita que a função de alimentação do dashboard principal quebre caso não ache elementos na árvore DOM
if (typeof window.alimentarDadosDashboard === 'undefined') {
    const originalAlimentar = window.alimentarDadosDashboard;
    window.alimentarDadosDashboard = function() {
        try {
            if (typeof originalAlimentar === 'function') originalAlimentar();
        } catch (e) {
            console.warn("Alimentação de dados contornada com segurança para evitar interrupções de script.");
        }
    };
}
</script>
