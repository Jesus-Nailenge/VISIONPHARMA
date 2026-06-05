<?php
/**
 * PHARMORA - Ecossistema de Gestão Financeira e Auditoria Integral
 */
require_once("../config_api.php");

// ID do operador logado simulado (substituir por $_SESSION['id_usuario'] se necessário)
$id_operador_atual = 1; 
$mensagem_acao = "";

// =========================================================================
// 1. PROCESSAMENTO DE LANÇAMENTOS (INSERÇÃO DE DADOS VIA FORMULÁRIOS)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_financeira'])) {
    $acao = $_POST['acao_financeira'];

    // A. Cadastrar Nova Conta a Pagar / Dívida / Folha de Salário
    if ($acao === 'cadastrar_conta') {
        $descricao = $conn->real_escape_string($_POST['descricao']);
        $categoria = $conn->real_escape_string($_POST['categoria']);
        $id_fornecedor = !empty($_POST['id_fornecedor']) ? intval($_POST['id_fornecedor']) : "NULL";
        $valor_original = floatval($_POST['valor_original']);
        $data_vencimento = $conn->real_escape_string($_POST['data_vencimento']);

        $sql = "INSERT INTO financeiro_contas_pagar (descricao, category, id_referencia_fornecedor, valor_original, data_vencimento, id_operador_cadastro, status_conta) 
                VALUES ('$descricao', '$categoria', $id_fornecedor, $valor_original, '$data_vencimento', $id_operador_atual, 'PENDENTE')";
        
        if ($conn->query($sql)) {
            $mensagem_acao = "<div class='f-badge badge-sucesso' style='padding:15px; margin-bottom:20px; width:100%; text-align:center; font-size:13px;'>✓ Conta/Obrigação financeira cadastrada com sucesso!</div>";
        } else {
            $mensagem_acao = "<div class='f-badge badge-perigo' style='padding:15px; margin-bottom:20px; width:100%; text-align:center; font-size:13px;'>Erro: " . $conn->error . "</div>";
        }
    }

    // B. Dar Baixa / Liquidar Conta Existente (Efetuar Pagamento)
    if ($acao === 'pagar_conta') {
        $id_conta = intval($_POST['id_conta']);
        $valor_pago = floatval($_POST['valor_pago']);
        $metodo = $conn->real_escape_string($_POST['metodo_pagamento']);
        
        // Obter dados da conta para espelhar na movimentação
        $busca_conta = $conn->query("SELECT descricao, categoria FROM financeiro_contas_pagar WHERE id_conta = $id_conta")->fetch_assoc();
        $desc_caixa = "Pagamento: " . $busca_conta['descricao'];
        $categoria_mov = $busca_conta['categoria'];

        // 1. Atualiza o estado da obrigação
        $conn->query("UPDATE financeiro_contas_pagar SET valor_pago = valor_pago + $valor_pago, status_conta = 'PAGO', data_pagamento = CURDATE(), id_operador_baixa = $id_operador_atual WHERE id_conta = $id_conta");
        
        // 2. Descarrega o fluxo real de saída na tesouraria (financeiro_movimentacoes)
        $conn->query("INSERT INTO financeiro_movimentacoes (tipo, origem, id_referencia, valor_bruto, desconto, valor_liquido, metodo_pagamento, id_operador, descricao) 
                      VALUES ('DESPESA', '$categoria_mov', $id_conta, $valor_pago, 0.00, $valor_pago, '$metodo', $id_operador_atual, '$desc_caixa')");
        
        $mensagem_acao = "<div class='f-badge badge-sucesso' style='padding:15px; margin-bottom:20px; width:100%; text-align:center; font-size:13px;'>✓ Pagamento efetuado e movimentação de caixa gerada com sucesso!</div>";
    }
}

// =========================================================================
// 2. PROCESSAMENTO DE MÉTRICAS E BALANÇOS CONSOLIDADOS (TODAS AS TABELAS)
// =========================================================================

// A. Fluxo de Caixa Real (A partir de financeiro_movimentacoes)
$query_receitas = $conn->query("SELECT SUM(valor_liquido) as total FROM financeiro_movimentacoes WHERE tipo = 'RECEITA'");
$tot_receitas = $query_receitas->fetch_assoc()['total'] ?? 0.00;

$query_despesas = $conn->query("SELECT SUM(valor_liquido) as total FROM financeiro_movimentacoes WHERE tipo = 'DESPESA'");
$tot_despesas = $query_despesas->fetch_assoc()['total'] ?? 0.00;

$query_perdas_caixa = $conn->query("SELECT SUM(valor_liquido) as total FROM financeiro_movimentacoes WHERE tipo = 'PERDA'");
$tot_perdas_caixa = $query_perdas_caixa->fetch_assoc()['total'] ?? 0.00;

$saldo_caixa = $tot_receitas - ($tot_despesas + $tot_perdas_caixa);

// B. Faturamento Total do PDV (A partir da tabela de vendas)
$query_faturamento_vendas = $conn->query("SELECT SUM(total_final) as total FROM vendas WHERE status_venda = 'Concluida'");
$tot_vendas_concluidas = $query_faturamento_vendas->fetch_assoc()['total'] ?? 0.00;

// C. Prejuízo Acumulado por Perdas e Quebras de Estoque (A partir da nova tabela de perdas)
$query_prejuizo_perdas = $conn->query("SELECT SUM(valor_prejuizo_total) as total FROM perdas");
$tot_prejuizo_perdas = $query_prejuizo_perdas->fetch_assoc()['total'] ?? 0.00;

// D. Valor Absoluto e Patrimonial do Estoque (Custo vs Venda Esperada)
$query_patrimonio = $conn->query("SELECT 
    SUM(estoque_atual_caixas * preco_compra) as total_custo,
    SUM(estoque_atual_caixas * preco_venda_caixa) as total_venda
    FROM produtos WHERE status_item = 'Ativo'");
$dados_patrimonio = $query_patrimonio->fetch_assoc();
$valor_estoque_custo = $dados_patrimonio['total_custo'] ?? 0.00;
$valor_estoque_venda = $dados_patrimonio['total_venda'] ?? 0.00;

// ---> ADICIONA ESTA LINHA AQUI <---
$patrimonio_liquide_custo = $valor_estoque_custo - $tot_prejuizo_perdas;

// E. Compromissos Financeiros Ativos / Dívidas Pendentes
$query_compromissos = $conn->query("SELECT SUM(valor_original - valor_pago) as total FROM financeiro_contas_pagar WHERE status_conta IN ('PENDENTE', 'PAGO_PARCIAL', 'VENCIDO')");
$tot_compromissos_pendentes = $query_compromissos->fetch_assoc()['total'] ?? 0.00;


// =========================================================================
// 3. CONSULTAS DE LISTAGEM PARA AS ABAS OPERACIONAIS
// =========================================================================

// Aba 1: Histórico de Movimentações (Caixa Real)
$sql_movimentacoes = "SELECT m.*, f.nome_completo as nome_operador 
                      FROM financeiro_movimentacoes m 
                      LEFT JOIN funcionarios f ON m.id_operador = f.id_sistema 
                      ORDER BY m.id_financeiro DESC LIMIT 50";
$res_movimentacoes = $conn->query($sql_movimentacoes);

// Aba 2: Livro de Vendas do PDV
$sql_vendas = "SELECT v.*, f.nome_completo as nome_vendedor 
               FROM vendas v 
               LEFT JOIN funcionarios f ON v.id_usuario = f.id_sistema 
               ORDER BY v.id_venda DESC LIMIT 50";
$res_vendas = $conn->query($sql_vendas);

// Aba 3: Contas a Pagar Cruzada com Fornecedores
$sql_contas = "SELECT cp.*, forn.nome as nome_fornecedor, forn.nif as nif_fornecedor 
               FROM financeiro_contas_pagar cp
               LEFT JOIN fornecedores forn ON cp.id_referencia_fornecedor = forn.id_fornecedor
               ORDER BY cp.data_vencimento ASC";
$res_contas = $conn->query($sql_contas);

// Aba 4: Inventário de Perdas com Rastreamento de Responsável e Produto
$sql_perdas = "SELECT p.*, prod.nome_produto, func.nome_completo as nome_responsavel 
               FROM perdas p
               LEFT JOIN produtos prod ON p.id_produto = prod.id_produto
               LEFT JOIN funcionarios func ON p.id_funcionario_responsavel = func.id_funcionario
               ORDER BY p.data_registro DESC";
$res_perdas = $conn->query($sql_perdas);

// Consultas Auxiliares para preenchimento dos Formulários/Modais
$select_fornecedores = $conn->query("SELECT id_fornecedor, nome FROM fornecedores WHERE status_fornecedor = 'Ativo' ORDER BY nome ASC");
?>

<style>
/* ============ ESTRUTURA E TEMA GLASSMORPHISM ============ */
.rh-container {
    width: 100%;
    animation: fadeIn 0.4s ease;
    color: var(--text-main);
    font-family: 'Inter', sans-serif;
    padding: 20px;
}

.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
}

.header-info h2 {
    color: var(--accent);
    font-weight: 800;
    margin: 0;
    font-size: clamp(18px, 3vw, 24px);
    display: flex;
    align-items: center;
    gap: 10px;
}

.header-info p {
    color: var(--text-dim);
    font-size: 12px;
    margin-top: 4px;
}

/* BOTÕES DE INTERAÇÃO */
.btn-primary-pharmora { background: var(--accent); color: white; border: none; padding: 12px 20px; border-radius: 12px; font-weight: 600; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: background 0.2s; text-decoration:none; }
.btn-primary-pharmora:hover { background: #1d4ed8; }
.btn-baixa { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; }
.btn-baixa:hover { background: #22c55e; color: white; }

/* ============ NAVEGAÇÃO DE ABAS PREMIUM ============ */
.tabs-navigation {
    display: flex;
    gap: 8px;
    margin-bottom: 25px;
    border-bottom: 1px solid var(--card-border);
    padding-bottom: 10px;
    overflow-x: auto;
}

.tab-btn {
    background: transparent;
    border: 1px solid transparent;
    color: var(--text-dim);
    padding: 10px 18px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
}

.tab-btn:hover {
    color: var(--text-main);
    background: rgba(255, 255, 255, 0.02);
}

.tab-btn.active {
    background: var(--accent);
    color: white;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.tab-panel {
    display: none;
    animation: fadeIn 0.3s ease;
}

.tab-panel.active {
    display: block;
}

/* ============ INDICADORES DE PERFORMANCE (KPIs) ============ */
.finance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.finance-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 16px;
    padding: 20px;
    backdrop-filter: blur(15px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    display: flex;
    align-items: center;
    gap: 15px;
}

.finance-card-icon {
    font-size: 20px;
    padding: 12px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.finance-card-info { display: flex; flex-direction: column; }
.finance-card-info .label { font-size: 11px; color: var(--text-dim); text-transform: uppercase; font-weight: 600; }
.finance-card-info .value { font-size: 18px; font-weight: 800; margin-top: 4px; color: var(--text-main); }
.finance-card-info .sub-value { font-size: 10px; color: var(--text-dim); margin-top: 2px; }

.card-saldo .finance-card-icon { background: rgba(37, 99, 235, 0.1); color: var(--accent); border: 1px solid rgba(37, 99, 235, 0.2); }
.card-vendas .finance-card-icon { background: rgba(22, 163, 74, 0.1); color: #4ade80; border: 1px solid rgba(22, 163, 74, 0.2); }
.card-perdas .finance-card-icon { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }
.card-contas .finance-card-icon { background: rgba(245, 158, 11, 0.1); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.2); }

/* ============ INTERAÇÕES E FILTROS ============ */
.action-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    gap: 15px;
}

.search-box {
    position: relative;
    min-width: 260px;
    max-width: 400px;
    flex: 1;
}

.search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-dim); }
.search-box input {
    width: 100%;
    background: var(--input-fill);
    border: 2px solid var(--card-border);
    color: var(--text-main);
    padding: 12px 15px 12px 42px;
    border-radius: 12px;
    outline: none;
    font-size: 13px;
    transition: all 0.3s ease;
}
.search-box input:focus { border-color: var(--accent); }

/* ============ DESIGN DA TABELA E BADGES ============ */
.table-glass {
    width: 100%;
    background: var(--card-bg);
    border-radius: 16px;
    overflow-x: auto;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(15px);
    margin-bottom: 30px;
}

table { width: 100%; border-collapse: collapse; min-width: 1000px; }
th {
    background: var(--input-fill);
    padding: 14px 12px;
    text-align: left;
    color: var(--text-dim);
    font-size: 10px;
    text-transform: uppercase;
    font-weight: 700;
    border-bottom: 2px solid var(--card-border);
}
td { padding: 14px 12px; border-bottom: 1px solid var(--card-border); font-size: 13px; vertical-align: middle; }
tbody tr:hover { background: rgba(255, 255, 255, 0.01); }

.f-badge { padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; display: inline-block; }
.badge-sucesso { background: rgba(22, 163, 74, 0.15); color: #4ade80; border: 1px solid rgba(22, 163, 74, 0.2); }
.badge-perigo { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }
.badge-alerta { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.2); }
.badge-info { background: rgba(37, 99, 235, 0.15); color: #60a5fa; border: 1px solid rgba(37, 99, 235, 0.2); }

.text-positive { color: #4ade80; font-weight: 600; }
.text-negative { color: #f87171; font-weight: 600; }

/* MODAIS GLASSMORPHISM */
.pharmora-modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 9999; justify-content: center; align-items: center; }
.modal-content { background: #111827; border: 1px solid rgba(255,255,255,0.08); padding: 25px; border-radius: 16px; width: 100%; max-width: 480px; box-shadow: 0 20px 40px rgba(0,0,0,0.6); }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.modal-header h3 { margin:0; font-size: 16px; color: var(--accent); text-transform: uppercase; font-weight: 700; }
.modal-close { background: none; border:none; color: var(--text-dim); font-size: 22px; cursor: pointer; }
.modal-close:hover { color: #fff; }
.form-group { margin-bottom: 15px; display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 11px; color: var(--text-dim); font-weight: 600; text-transform: uppercase; }
.form-group input, .form-group select { background: #1f2937; border: 1px solid #374151; padding: 11px; border-radius: 8px; color: #fff; font-size: 13px; outline:none; }
.form-group input:focus, .form-group select:focus { border-color: var(--accent); }

@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="rh-container">
    
    <?php echo $mensagem_acao; ?>
    
    <div class="top-bar">
        <div class="header-info">
            <h2><i class="fas fa-chart-line"></i> Gestão Financeira Unificada</h2>
            <p>Auditoria cruzada em tempo real de faturamento, tesouraria, despesas e perdas físicas</p>
        </div>
        <button class="btn-primary-pharmora" onclick="abrirModal('modalConta')">
            <i class="fas fa-plus"></i> Lançar Conta / Obrigação
        </button>
    </div>

    <div class="finance-grid">
        <div class="finance-card card-saldo">
            <div class="finance-card-icon"><i class="fas fa-vault"></i></div>
            <div class="finance-card-info">
                <span class="label">Disponibilidade em Caixa</span>
                <span class="value"><?php echo number_format($saldo_caixa, 2, ',', '.'); ?> Kz</span>
                <span class="sub-value">Fluxo líquido real</span>
            </div>
        </div>

        <div class="finance-card card-vendas">
            <div class="finance-card-icon"><i class="fas fa-shopping-basket"></i></div>
            <div class="finance-card-info">
                <span class="label">Vendas Concluídas (PDV)</span>
                <span class="value"><?php echo number_format($tot_vendas_concluidas, 2, ',', '.'); ?> Kz</span>
                <span class="sub-value">Faturamento acumulado</span>
            </div>
        </div>

        <div class="finance-card card-perdas">
    <div class="finance-card-icon"><i class="fas fa-dumpster"></i></div>
    <div class="finance-card-info">
        <span class="label">Custo de Perdas/Avarias</span>
        <span class="value"><?php echo number_format($tot_prejuizo_perdas, 2, ',', '.'); ?> Kz</span>
        <span class="sub-value" style="color:#f87171; font-weight: 600;">Património Real (Custo): <?php echo number_format($patrimonio_liquide_custo, 2, ',', '.'); ?> Kz</span>
        <span class="sub-value" style="color:#60a5fa; margin-top: 2px;">Venda Estimada (Se vender tudo): <?php echo number_format($valor_estoque_venda, 2, ',', '.'); ?> Kz</span>
    </div>
</div>

        <div class="finance-card card-contas">
            <div class="finance-card-icon"><i class="fas fa-file-invoice-dollar"></i></div>
            <div class="finance-card-info">
                <span class="label">Contas a Pagar Ativas</span>
                <span class="value"><?php echo number_format($tot_compromissos_pendentes, 2, ',', '.'); ?> Kz</span>
                <span class="sub-value">Obrigações futuras</span>
            </div>
        </div>
    </div>

    <div class="tabs-navigation">
        <button class="tab-btn active" onclick="trocarModulo(event, 'modulo-caixa')">
            <i class="fas fa-cash-register"></i> Movimentações de Caixa
        </button>
        <button class="tab-btn" onclick="trocarModulo(event, 'modulo-vendas')">
            <i class="fas fa-receipt"></i> Histórico de Vendas
        </button>
        <button class="tab-btn" onclick="trocarModulo(event, 'modulo-contas')">
            <i class="fas fa-truck-loading"></i> Contas a Pagar & Fornecedores
        </button>
        <button class="tab-btn" onclick="trocarModulo(event, 'modulo-perdas')">
            <i class="fas fa-exclamation-circle"></i> Auditoria de Perdas
        </button>
    </div>

    <div id="modulo-caixa" class="tab-panel active">
        <div class="action-row">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="filtroCaixa" onkeyup="filtrarTabela('filtroCaixa', 'tabelaCaixa')" placeholder="Pesquisar por origem ou operador...">
            </div>
        </div>
        <div class="table-glass">
            <table id="tabelaCaixa">
                <thead>
                    <tr>
                        <th>ID Ref</th>
                        <th>Tipo</th>
                        <th>Origem do Fluxo</th>
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
                            <td style="text-transform:uppercase; font-size:11px; font-weight:500;"><?php echo htmlspecialchars($mov['metodo_pagamento']); ?></td>
                            <td><?php echo number_format($mov['valor_bruto'], 2, ',', '.'); ?> Kz</td>
                            <td style="color:var(--text-dim);"><?php echo number_format($mov['desconto'], 2, ',', '.'); ?> Kz</td>
                            <td class="<?php echo $is_receita ? 'text-positive' : 'text-negative'; ?>">
                                <?php echo ($is_receita ? '+ ' : '- ') . number_format($mov['valor_liquido'], 2, ',', '.'); ?> Kz
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($mov['data_registro'])); ?></td>
                            <td><?php echo htmlspecialchars($mov['nome_operador'] ?: 'Sistema'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align:center; padding:40px; color:var(--text-dim);">Nenhum fluxo de caixa registrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modulo-vendas" class="tab-panel">
        <div class="action-row">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="filtroVendas" onkeyup="filtrarTabela('filtroVendas', 'tabelaVendas')" placeholder="Pesquisar por cliente, método ou status...">
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
                        <th>Produtos (Bruto)</th>
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
                            <td style="font-weight:500; color:var(--accent);"><?php echo $vd['forma_pagamento']; ?></td>
                            <td><?php echo number_format($vd['total_produtos'], 2, ',', '.'); ?> Kz</td>
                            <td style="color:var(--text-dim);"><?php echo number_format($vd['desconto_venda'], 2, ',', '.'); ?> Kz</td>
                            <td style="font-weight:700;"><?php echo number_format($vd['total_final'], 2, ',', '.'); ?> Kz</td>
                            <td><?php echo htmlspecialchars($vd['nome_vendedor'] ?: 'PDV Automático'); ?></td>
                            <td><span class="f-badge <?php echo $status_cl; ?>"><?php echo $vd['status_venda']; ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align:center; padding:40px; color:var(--text-dim);">Nenhuma venda localizada no banco de dados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modulo-contas" class="tab-panel">
        <div class="action-row">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="filtroContas" onkeyup="filtrarTabela('filtroContas', 'tabelaContas')" placeholder="Pesquisar por despesa ou fornecedor...">
            </div>
        </div>
        <div class="table-glass">
            <table id="tabelaContas">
                <thead>
                    <tr>
                        <th>ID Conta</th>
                        <th>Fornecedor / Credor</th>
                        <th>NIF Fornecedor</th>
                        <th>Descrição da Conta</th>
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
                            <td><strong><?php echo htmlspecialchars($cp['nome_fornecedor'] ?: 'Despesa Administrativa/Geral'); ?></strong></td>
                            <td><small style="color:var(--text-dim);"><?php echo htmlspecialchars($cp['nif_fornecedor'] ?: 'N/D'); ?></small></td>
                            <td><?php echo htmlspecialchars($cp['descricao']); ?></td>
                            <td><?php echo number_format($cp['valor_original'], 2, ',', '.'); ?> Kz</td>
                            <td class="text-positive"><?php echo number_format($cp['valor_pago'], 2, ',', '.'); ?> Kz</td>
                            <td><?php echo date('d/m/Y', strtotime($cp['data_vencimento'])); ?></td>
                            <td><span class="f-badge <?php echo $badge_status; ?>"><?php echo str_replace('_', ' ', $cp['status_conta']); ?></span></td>
                            <td>
                                <?php if($esta_pendente): ?>
                                    <button class="btn-baixa" onclick="prepararBaixa(<?php echo $cp['id_conta']; ?>, <?php echo $cp['valor_original'] - $cp['valor_pago']; ?>)">
                                        <i class="fas fa-dollar-sign"></i> Pagar
                                    </button>
                                <?php else: ?>
                                    <span style="color:var(--text-dim); font-size:11px;">✓ Liquidado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align:center; padding:40px; color:var(--text-dim);">Nenhuma obrigação financeira pendente.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modulo-perdas" class="tab-panel">
        <div class="action-row">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="filtroPerdas" onkeyup="filtrarTabela('filtroPerdas', 'tabelaPerdas')" placeholder="Filtrar por produto ou motivo...">
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
                            <td><strong><?php echo htmlspecialchars($pr['nome_produto'] ?: 'Produto Desconhecido (#'.$pr['id_produto'].')'); ?></strong></td>
                            <td style="font-weight:600;"><?php echo $pr['quantidade']; ?> un</td>
                            <td><span class="f-badge badge-alerta"><?php echo htmlspecialchars($pr['motivo']); ?></span></td>
                            <td><?php echo number_format($pr['preco_custo_unidade'], 2, ',', '.'); ?> Kz</td>
                            <td class="text-negative"><?php echo number_format($pr['valor_prejuizo_total'], 2, ',', '.'); ?> Kz</td>
                            <td><?php echo date('d/m/Y H:i', strtotime($pr['data_registro'])); ?></td>
                            <td><small style="font-weight:500;"><?php echo htmlspecialchars($pr['nome_responsavel'] ?: $pr['id_funcionario_responsavel']); ?></small></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center; padding:40px; color:var(--text-dim);">Nenhuma avaria ou perda física de inventário catalogada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<div id="modalConta" class="pharmora-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Novo Lançamento Financeiro</h3>
            <button class="modal-close" onclick="fecharModal('modalConta')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="acao_financeira" value="cadastrar_conta">
            
            <div class="form-group">
                <label>Descrição do Custo</label>
                <input type="text" name="descricao" placeholder="Ex: Salário Mensal" required>
            </div>
            
            <div class="form-group">
                <label>Categoria de Fluxo</label>
                <select name="categoria">
                    <option value="FORNECEDOR">Fornecedor de Medicamentos</option>
                    <option value="SALARIOS">Pagamento de Funcionários</option>
                    <option value="INFRAESTRUTURA">Infraestrutura (Luz, Água, Renda)</option>
                    <option value="IMPOSTOS">AGT / Impostos e Taxas</option>
                    <option value="LOGISTICA">Custos de Logística e Frete</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Credor / Fornecedor Vinculado</label>
                <select name="id_fornecedor">
                    <option value="">Nenhum (Geral / Custo Administrativo)</option>
                    <?php if($select_fornecedores): $select_fornecedores->data_seek(0); while($f = $select_fornecedores->fetch_assoc()): ?>
                        <option value="<?php echo $f['id_fornecedor']; ?>"><?php echo htmlspecialchars($f['nome']); ?></option>
                    <?php endwhile; endif; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Valor Nominal (Kz)</label>
                <input type="number" step="0.02" name="valor_original" placeholder="0.00" required>
            </div>
            
            <div class="form-group">
                <label>Data Limite / Vencimento</label>
                <input type="date" name="data_vencimento" required>
            </div>
            
            <button type="submit" class="btn-primary-pharmora" style="width:100%; justify-content:center; margin-top:10px;">Gravar Registro</button>
        </form>
    </div>
</div>

<div id="modalBaixa" class="pharmora-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirmar Saída de Caixa</h3>
            <button class="modal-close" onclick="fecharModal('modalBaixa')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="acao_financeira" value="pagar_conta">
            <input type="hidden" id="baixa_id_conta" name="id_conta">
            
            <div class="form-group">
                <label>Valor a ser Liquidado (Kz)</label>
                <input type="number" step="0.02" id="baixa_valor_pago" name="valor_pago" required>
            </div>
            
            <div class="form-group">
                <label>Método de Desembolso</label>
                <select name="metodo_pagamento">
                    <option value="dinheiro">Dinheiro Físico</option>
                    <option value="pos">Multicaixa Express / POS</option>
                    <option value="transferência">Transferência Bancária</option>
                </select>
            </div>
            
            <button type="submit" class="btn-primary-pharmora" style="width:100%; justify-content:center; margin-top:10px; background:#22c55e;">Efetuar Saída</button>
        </form>
    </div>
</div>

<script>
// Manipulação nativa das Abas
function trocarModulo(event, moduloId) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
    
    event.currentTarget.classList.add('active');
    document.getElementById(moduloId).classList.add('active');
}

// Filtro em tempo real por string nas tabelas
function filtrarTabela(inputId, tableId) {
    const termo = document.getElementById(inputId).value.toLowerCase();
    const linhas = document.querySelectorAll(`#${tableId} tbody tr`);
    
    linhas.forEach(linha => {
        if(linha.cells.length <= 1) return;
        linha.style.display = i = linha.innerText.toLowerCase().includes(termo) ? "" : "none";
    });
}

// Modais
function abrirModal(id) { document.getElementById(id).style.display = 'flex'; }
function fecharModal(id) { document.getElementById(id).style.display = 'none'; }

function prepararBaixa(idConta, valorRestante) {
    document.getElementById('baixa_id_conta').value = idConta;
    document.getElementById('baixa_valor_pago').value = valorRestante;
    abrirModal('modalBaixa');
}
</script>