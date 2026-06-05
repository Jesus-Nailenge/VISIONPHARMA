<?php
/**
 * VISIONPHARMA - Painel Principal (Módulo Interno)
 * Injetado dinamicamente via AJAX no viewport principal.
 */

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// 1. INCLUSÃO DA CONEXÃO DO SISTEMA
require_once("../config_api.php");

if (!$conn) {
    echo "<div style='color:#ff4444; padding:20px; text-align:center;'>Falha na ligação com a base de dados.</div>";
    exit;
}

// 2. CONTROLO SEGURO DE SESSÃO DO OPERADOR
$id_sistema_logado = $_SESSION['id_sistema'] ?? $_SESSION['id_usuario'] ?? null;

if (!$id_sistema_logado) {
    echo "<div style='color: #ff4444; padding: 20px; text-align: center; font-size: 14px;'>
            <i class='fas fa-ban'></i> Sessão expirada. Por favor, reautentique-se no sistema.
          </div>";
    exit;
}

// 3. CAPTURA EM TEMPO REAL DOS PRIVILÉGIOS PARA MAPEAMENTO DE SEGURANÇA
$sql_user = "SELECT u.nivel_acesso, u.permissoes_especiais 
             FROM usuarios u
             WHERE u.id_sistema = ? AND u.estado_conta = 'Ativa'";
             
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $id_sistema_logado);
$stmt_user->execute();
$res_user = $stmt_user->get_result();

if ($res_user && $res_user->num_rows > 0) {
    $dados_usuario = $res_user->fetch_assoc();
} else {
    echo "<div style='color: #ff4444; padding: 20px; text-align: center;'>Utilizador inválido ou suspenso.</div>";
    exit;
}
$stmt_user->close();

// 4. DESCOMPRESSÃO DA MATRIZ DE PERMISSÕES (JSON)
$e_admin = (strcasecmp($dados_usuario['nivel_acesso'], 'Admin') === 0);
$permissoes_json = json_decode($dados_usuario['permissoes_especiais'], true) ?? [];

$p = [
    "ver_dashboard"    => $e_admin ? 1 : ($permissoes_json['ver_dashboard'] ?? 0),
    "ver_vendas"       => $e_admin ? 1 : ($permissoes_json['ver_vendas'] ?? 0),
    "ver_perdas"       => $e_admin ? 1 : ($permissoes_json['ver_perdas'] ?? 0),
    "ver_estoque"      => $e_admin ? 1 : ($permissoes_json['ver_estoque'] ?? 0),
    "ver_fornecedores" => $e_admin ? 1 : ($permissoes_json['ver_fornecedores'] ?? 0),
    "gerir_usuarios"   => $e_admin ? 1 : ($permissoes_json['gerir_usuarios'] ?? 0),
    "ver_financeiro"   => $e_admin ? 1 : ($permissoes_json['ver_financeiro'] ?? 0),
    "ver_logs"         => $e_admin ? 1 : ($permissoes_json['ver_logs'] ?? 0),
    "ver_relatorios"   => $e_admin ? 1 : ($permissoes_json['ver_relatorios'] ?? 0)
];

if (!$p['ver_dashboard']) {
    echo "<div style='color: #fbbf24; padding: 20px; border: 1px solid var(--card-border); background: var(--card-bg); border-radius: 12px;'>
            <i class='fas fa-shield-halved'></i> O seu perfil não possui autorização para visualizar este painel.
          </div>";
    exit;
}

// 5. PROCESSAMENTO DE DADOS (APENAS SE HOUVER PERMISSÃO ESPECÍFICA)

// A. Vendas & Gráficos
$total_vendas_hoje = 0.00; $qtd_vendas_hoje = 0; $res_feed_vendas = null; $labels_dias = []; $valores_dias = [];
if ($p['ver_vendas']) {
    $res_vhoje = $conn->query("SELECT COUNT(id_venda) as qtd, SUM(total_final) as total FROM vendas WHERE DATE(data_venda) = CURDATE() AND status_venda = 'Concluida'");
    if($res_vhoje) {
        $dv = $res_vhoje->fetch_assoc();
        $total_vendas_hoje = $dv['total'] ?? 0.00;
        $qtd_vendas_hoje = $dv['qtd'] ?? 0;
    }
    $res_feed_vendas = $conn->query("SELECT v.*, f.nome_completo as vendedor FROM vendas v LEFT JOIN funcionarios f ON v.id_usuario = f.id_sistema ORDER BY v.id_venda DESC LIMIT 5");
    
    $res_grafico = $conn->query("SELECT DATE(data_venda) as dia, SUM(total_final) as total FROM vendas WHERE data_venda >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status_venda = 'Concluida' GROUP BY DATE(data_venda) ORDER BY DATE(data_venda) ASC");
    if($res_grafico){
        while($g = $res_grafico->fetch_assoc()){
            $labels_dias[] = date('d/m', strtotime($g['dia']));
            $valores_dias[] = (float)$g['total'];
        }
    }
}

// B. Logística & Estoque Crítico
$res_estoque_critico = null; $res_validade_critica = null;
if ($p['ver_estoque']) {
    $res_estoque_critico = $conn->query("SELECT nome_produto, estoque_atual_caixas FROM produtos WHERE estoque_atual_caixas <= 10 AND status_item = 'Ativo' ORDER BY estoque_atual_caixas ASC LIMIT 4");
    $res_validade_critica = $conn->query("SELECT nome_produto, data_validade FROM produtos WHERE data_validade <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND data_validade >= CURDATE() AND status_item = 'Ativo' ORDER BY data_validade ASC LIMIT 4");
}

// C. Finanças
$contas_vencem_hoje = 0; $valor_debitos_hoje = 0.00;
if ($p['ver_financeiro']) {
    $res_financeiro_hoje = $conn->query("SELECT COUNT(id_conta) as qtd, SUM(valor_original) as total FROM financeiro_contas_pagar WHERE data_vencimento = CURDATE() AND status_conta IN ('PENDENTE', 'PAGO_PARCIAL')");
    if($res_financeiro_hoje) {
        $df = $res_financeiro_hoje->fetch_assoc();
        $contas_vencem_hoje = $df['qtd'] ?? 0;
        $valor_debitos_hoje = $df['total'] ?? 0.00;
    }
}

// D. Auditoria
$res_logs_auditoria = null;
if ($p['ver_logs']) {
    $res_logs_auditoria = $conn->query("SELECT acao, detalhes, data_registo FROM auditoria_logs ORDER BY id_log DESC LIMIT 4");
}
?>

<style>
.vision-dash-container {
    width: 100%;
    animation: fadeInDash 0.35s ease-out;
}

/* Painel de Atalhos Operacionais */
.shortcuts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.shortcut-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 14px;
    padding: 18px 14px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    text-decoration: none;
    color: var(--text-main);
    transition: all 0.2s ease;
    text-align: center;
}
.shortcut-card i { font-size: 24px; }
.shortcut-card span { font-size: 13px; font-weight: 600; }
.shortcut-card:hover {
    border-color: var(--accent);
    background: var(--input-fill);
    transform: translateY(-2px);
}

/* Grids e Blocos do Painel */
.dash-row-major {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}
.dash-row-minor {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.dash-panel-box {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 16px;
    padding: 20px;
    box-sizing: border-box;
}
.panel-box-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}
.panel-box-title h3 { 
    margin: 0; font-size: 13px; font-weight: 700; text-transform: uppercase; 
    color: var(--text-dim); display: flex; align-items: center; gap: 8px; letter-spacing: 0.5px;
}

/* Listas internas de Registros */
.feed-row-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 8px;
    border-bottom: 1px solid var(--card-border);
    font-size: 12.5px;
}
.feed-row-item:last-child { border-bottom: none; }

.status-badge-ui {
    font-size: 9px; font-weight: 700; text-transform: uppercase;
    padding: 3px 8px; border-radius: 20px;
}
.soft-success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.soft-danger { background: rgba(244, 63, 94, 0.1); color: #f43f5e; }
.soft-warning { background: rgba(251, 191, 36, 0.1); color: #fbbf24; }

.pulse-indicator {
    font-size: 11px; background: rgba(0, 255, 204, 0.08); color: var(--accent);
    padding: 4px 10px; border-radius: 20px; font-weight: 700; display: flex; align-items: center; gap: 6px;
}
.pulse-indicator::before { content: ''; width: 6px; height: 6px; background: var(--accent); border-radius: 50%; display: inline-block; animation: pulseAni 1.5s infinite; }

@keyframes pulseAni { 0%, 100% { transform: scale(0.9); opacity: 0.4; } 50% { transform: scale(1.3); opacity: 1; } }
@keyframes fadeInDash { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

@media (max-width: 992px) {
    .dash-row-major, .dash-row-minor { grid-template-columns: 1fr; }
}
</style>

<div class="vision-dash-container">

    <div class="shortcuts-grid">
        <?php if ($p['ver_vendas']): ?>
            <a href="#" onclick="loadModule('vendas', 'Terminal de Vendas')" class="shortcut-card">
                <i class="fas fa-shopping-cart" style="color: #10b981;"></i>
                <span>Abrir Balcão PDV</span>
            </a>
        <?php endif; ?>

        <?php if ($p['ver_estoque']): ?>
            <a href="#" onclick="loadModule('estoque', 'Controle de Estoque')" class="shortcut-card">
                <i class="fas fa-boxes" style="color: #3b82f6;"></i>
                <span>Aceder Estoque</span>
            </a>
        <?php endif; ?>

        <?php if ($p['ver_financeiro']): ?>
            <a href="#" onclick="loadModule('financeiro', 'Gestão Financeira')" class="shortcut-card">
                <i class="fas fa-wallet" style="color: #fbbf24;"></i>
                <span>Fluxo Financeiro</span>
            </a>
        <?php endif; ?>

        <?php if ($p['ver_perdas']): ?>
            <a href="#" onclick="loadModule('perdas', 'Gestão de Perdas')" class="shortcut-card">
                <i class="fas fa-trash-alt" style="color: #f43f5e;"></i>
                <span>Lançar Perdas</span>
            </a>
        <?php endif; ?>

        <?php if ($p['gerir_usuarios']): ?>
            <a href="#" onclick="loadModule('funcionarios', 'Funcionários & Acessos')" class="shortcut-card">
                <i class="fas fa-users-cog" style="color: #a5b4fc;"></i>
                <span>Utilizadores</span>
            </a>
        <?php endif; ?>
    </div>

    <?php if ($p['ver_vendas']): ?>
    <div class="dash-row-major">
        <div class="dash-panel-box">
            <div class="panel-box-title">
                <h3><i class="fas fa-chart-line" style="color: var(--accent);"></i> Desempenho de Faturação Diária</h3>
                <div class="pulse-indicator">Monitor Ativo</div>
            </div>
            <div style="height: 240px; width: 100%;">
                <canvas id="ctxVisionFaturacao"></canvas>
            </div>
        </div>

        <div class="dash-panel-box">
            <div class="panel-box-title">
                <h3><i class="fas fa-receipt" style="color: #10b981;"></i> Últimas Vendas</h3>
                <span style="font-size: 11px; font-weight: 700; color: #10b981;"><?php echo $qtd_vendas_hoje; ?> Hoje</span>
            </div>
            <div style="max-height: 240px; overflow-y: auto; padding-right: 4px;">
                <?php if ($res_feed_vendas && $res_feed_vendas->num_rows > 0): ?>
                    <?php while($v = $res_feed_vendas->fetch_assoc()): ?>
                        <div class="feed-row-item">
                            <div>
                                <strong style="color: var(--text-main);">#<?php echo $v['id_venda']; ?> - <?php echo htmlspecialchars($v['nome_cliente'] ?: 'Consumidor Final'); ?></strong><br>
                                <small style="color: var(--text-dim); font-size:11px;"><?php echo date('H:i', strtotime($v['data_venda'])); ?> • Op: <?php echo htmlspecialchars($v['vendedor'] ?: 'PDV'); ?></small>
                            </div>
                            <div style="text-align: right;">
                                <span style="color: #10b981; font-weight: 700;"><?php echo number_format($v['total_final'], 2, ',', '.'); ?> Kz</span><br>
                                <span class="status-badge-ui soft-success" style="font-size:9px;"><?php echo $v['forma_pagamento']; ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; color: var(--text-dim); padding: 50px 0; font-size: 13px;">Sem movimentações de balcão registadas hoje.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="dash-row-minor">
        
        <?php if ($p['ver_estoque']): ?>
        <div class="dash-panel-box">
            <div class="panel-box-title">
                <h3><i class="fas fa-boxes-stacked" style="color: #f43f5e;"></i> Alertas de Logística</h3>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <div>
                    <small style="color: #f43f5e; font-weight: 800; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 6px;">Ruptura Crítica (≤ 10 caixas)</small>
                    <?php if ($res_estoque_critico && $res_estoque_critico->num_rows > 0): ?>
                        <?php while($e = $res_estoque_critico->fetch_assoc()): ?>
                            <div class="feed-row-item" style="background: rgba(244,63,94,0.02); padding: 8px 10px; border-radius: 8px; border: 1px solid var(--card-border); margin-bottom: 5px;">
                                <span><?php echo htmlspecialchars($e['nome_produto']); ?></span>
                                <strong style="color: #f43f5e;"><?php echo $e['estoque_atual_caixas']; ?> un</strong>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="font-size:12px; color:var(--text-dim); padding: 2px 0;"><i class="fas fa-check-circle" style="color:#10b981;"></i> Níveis de estoque regularizados.</div>
                    <?php endif; ?>
                </div>

                <div>
                    <small style="color: #fbbf24; font-weight: 800; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 6px;">Validades Próximas (Janela 60 Dias)</small>
                    <?php if ($res_validade_critica && $res_validade_critica->num_rows > 0): ?>
                        <?php while($val = $res_validade_critica->fetch_assoc()): 
                            $dias = (int)date_diff(date_create(date('Y-m-d')), date_create($val['data_validade']))->format('%r%a');
                        ?>
                            <div class="feed-row-item" style="background: rgba(251,191,36,0.02); padding: 8px 10px; border-radius: 8px; border: 1px solid var(--card-border); margin-bottom: 5px;">
                                <span><?php echo htmlspecialchars($val['nome_produto']); ?></span>
                                <span style="color: #fbbf24; font-weight: 700;"><?php echo $dias; ?> dias rest.</span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="font-size:12px; color:var(--text-dim); padding: 2px 0;"><i class="fas fa-shield-halved" style="color:#3b82f6;"></i> Nenhum lote sob risco imediato.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="dash-panel-box" style="display: flex; flex-direction: column; justify-content: space-between; gap: 20px;">
            
            <?php if ($p['ver_financeiro']): ?>
            <div>
                <div class="panel-box-title" style="margin-bottom: 10px;">
                    <h3><i class="fas fa-clock-history" style="color: #fbbf24;"></i> Obrigações de Débito (Hoje)</h3>
                </div>
                <div style="display: flex; gap: 15px; background: rgba(255,255,255,0.01); border: 1px solid var(--card-border); padding: 12px; border-radius: 12px;">
                    <div style="flex:1; text-align: center; border-right: 1px solid var(--card-border);">
                        <small style="color: var(--text-dim); font-size:11px;">Contas a Pagar</small>
                        <h4 style="margin: 5px 0 0 0; font-size: 18px; color: #fbbf24;"><?php echo $contas_vencem_hoje; ?></h4>
                    </div>
                    <div style="flex:1.2; text-align: center;">
                        <small style="color: var(--text-dim); font-size:11px;">Montante Total</small>
                        <h4 style="margin: 5px 0 0 0; font-size: 16px; color: #f43f5e;"><?php echo number_format($valor_debitos_hoje, 2, ',', '.'); ?> Kz</h4>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($p['ver_logs']): ?>
            <div style="flex-grow: 1;">
                <div class="panel-box-title" style="margin-bottom: 10px;">
                    <h3><i class="fas fa-fingerprint" style="color: #a5b4fc;"></i> Atividades do Sistema</h3>
                </div>
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <?php if ($res_logs_auditoria && $res_logs_auditoria->num_rows > 0): ?>
                        <?php while($log = $res_logs_auditoria->fetch_assoc()): ?>
                            <div style="font-size: 11.5px; padding: 6px 8px; background: rgba(255,255,255,0.01); border-radius: 6px; border-left: 3px solid #a5b4fc;">
                                <span style="color: var(--text-main); font-weight: 700; font-size:10px; text-transform:uppercase;"><?php echo htmlspecialchars($log['acao']); ?></span> - 
                                <span style="color: var(--text-dim);"><?php echo htmlspecialchars(mb_strimwidth($log['detalhes'], 0, 48, "...")); ?></span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="font-size:12px; color:var(--text-dim); text-align:center; padding: 15px 0;">Sem atividades recentes registadas.</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

</div>

<?php if ($p['ver_vendas']): ?>
<script>
(function() {
    // Função interna que monta o gráfico quando tudo estiver pronto
    function inicializarVisionGrafico() {
        const canvas = document.getElementById('ctxVisionFaturacao');
        if (!canvas) return;

        try {
            // CORREÇÃO CRÍTICA: Se o utilizador alternar de menu e voltar, destrói o gráfico antigo antes de recriar
            const graficoExistente = Chart.getChart(canvas);
            if (graficoExistente) {
                graficoExistente.destroy();
            }
        } catch(e) { console.log(e); }

        // Renderização oficial com os dados vindos do PHP
        new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels_dias); ?>,
                datasets: [{
                    label: 'Faturação (Kz)',
                    data: <?php echo json_encode($valores_dias); ?>,
                    borderColor: '#00ffcc',
                    backgroundColor: 'rgba(0, 255, 204, 0.03)',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#00ffcc',
                    pointHoverRadius: 6,
                    tension: 0.35,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        grid: { color: 'rgba(255, 255, 255, 0.02)', borderDash: [3, 3] },
                        ticks: { color: '#888888', font: { size: 10, family: 'Inter' } }
                    },
                    y: {
                        grid: { color: 'rgba(255, 255, 255, 0.02)', borderDash: [3, 3] },
                        ticks: { color: '#888888', font: { size: 10, family: 'Inter' } }
                    }
                }
            }
        });
    }

    // CORREÇÃO DO LOADER: Injeta a biblioteca dinamicamente via JS puro se ela não existir no escopo global
    if (typeof Chart === 'undefined') {
        const scriptCDN = document.createElement('script');
        scriptCDN.src = "https://cdn.jsdelivr.net/npm/chart.js";
        scriptCDN.onload = function() {
            // Aguarda 60ms para garantir que o DOM injetado pelo Fetch esteja mapeado
            setTimeout(inicializarVisionGrafico, 60);
        };
        document.head.appendChild(scriptCDN);
    } else {
        // Se a biblioteca já existir, apenas executa limpando o cache do canvas anterior
        setTimeout(inicializarVisionGrafico, 60);
    }
})();
</script>
<?php endif; ?>
