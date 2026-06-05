<?php
/**
 * PHARMORA / VISIONPHARMA - Painel Gerencial & Módulo de Relatórios Dinâmicos
 * Este ficheiro é injetado via Fetch na Viewport do arquivo principal.
 * Sincronizado dinamicamente com o sistema de permissões de utilizadores.
 */
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// Resgata com precisão matemática as permissões da sessão PHP para blindagem de infraestrutura
$permissoes = $_SESSION['permissoes'] ?? [];

// Se quiseres testar localmente todas as visões antes de integrar a autenticação completa, 
// basta descomentar a linha mock abaixo:
// $permissoes = ['ver_dashboard' => true, 'ver_vendas' => true, 'ver_estoque' => true, 'ver_financeiro' => true, 'gerir_usuarios' => true];
?>

<style>
    /* =======================================================================
       ESTILOS EXCLUSIVOS DO DASHBOARD (DESIGN CINEMATOGRÁFICO DARK)
       ======================================================================= */
    .dashboard-grid {
        display: flex;
        flex-direction: column;
        gap: 25px;
        animation: fadeInDashboard 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
        width: 100%;
        box-sizing: border-box;
    }

    @keyframes fadeInDashboard {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* CONTAINER DE CARDS DE INDICADORES (KPIs) */
    .kpi-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 20px;
        width: 100%;
    }

    .kpi-card {
        background: var(--card-bg, #111);
        border: 1px solid var(--card-border, #222);
        border-radius: 14px;
        padding: 24px;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .kpi-card:hover {
        transform: translateY(-4px);
        border-color: rgba(0, 255, 204, 0.25);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
    }

    .kpi-card::before {
        content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%;
        background: var(--accent, #00ffcc);
    }
    .kpi-card.perigo::before { background: var(--danger, #ff4444); }
    .kpi-card.alerta::before { background: #ffaa00; }
    .kpi-card.info::before { background: #00bfff; }

    .kpi-title {
        font-size: 10px; 
        color: var(--text-dim, #888); 
        text-transform: uppercase;
        letter-spacing: 1.5px; 
        font-weight: 600;
    }

    .kpi-value {
        font-size: 26px; 
        font-weight: 800; 
        margin-top: 10px; 
        letter-spacing: -0.5px;
        color: var(--text-main, #fff);
    }

    .kpi-icon {
        position: absolute; right: 22px; bottom: 22px; font-size: 24px;
        color: var(--text-dim, #888); opacity: 0.12;
    }

    /* FILAS E BLOCOS ESTRUTURAIS DE RELATÓRIOS */
    .dashboard-row-main {
        display: grid; 
        grid-template-columns: 1.6fr 1.4fr; 
        gap: 20px;
        width: 100%;
    }

    .dashboard-row-sub {
        display: grid; 
        grid-template-columns: 1fr 1fr; 
        gap: 20px;
        width: 100%;
    }

    @media (max-width: 1100px) {
        .dashboard-row-main, .dashboard-row-sub { grid-template-columns: 1fr; }
    }

    .panel-box {
        background: var(--card-bg, #111); 
        border: 1px solid var(--card-border, #222);
        border-radius: 14px; 
        padding: 24px; 
        display: flex; 
        flex-direction: column;
        min-width: 0;
    }

    .panel-header {
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 20px;
    }

    .panel-title {
        font-size: 13px; 
        font-weight: 700; 
        letter-spacing: 0.5px;
        display: flex; 
        align-items: center; 
        gap: 10px; 
        text-transform: uppercase; 
        color: var(--text-main, #fff);
    }

    /* ESTILIZAÇÃO DE TABELAS EXECUTIVAS */
    .report-table-wrapper {
        width: 100%; 
        overflow-x: auto;
    }

    .report-table {
        width: 100%; 
        border-collapse: collapse; 
        text-align: left; 
        font-size: 13px;
    }

    .report-table th {
        padding: 12px; 
        color: var(--text-dim, #888); 
        font-size: 11px;
        text-transform: uppercase; 
        letter-spacing: 1px; 
        border-bottom: 1px solid var(--card-border, #222);
    }

    .report-table td {
        padding: 14px 12px; 
        color: var(--text-main, #fff); 
        border-bottom: 1px solid rgba(255,255,255,0.01);
        vertical-align: middle;
    }

    .report-table tr:last-child td { border-bottom: none; }

    /* STATUS BADGES */
    .badge-status {
        padding: 4px 9px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; display: inline-block;
    }
    .badge-status.active { background: rgba(0, 255, 204, 0.08); color: var(--accent, #00ffcc); }
    .badge-status.warning { background: rgba(255, 170, 0, 0.08); color: #ffaa00; }
    .badge-status.danger { background: rgba(255, 68, 68, 0.08); color: var(--danger, #ff4444); }

    /* LAYOUT DE CONTROLO DO CAIXA ISOLADO */
    .caixa-foco-container {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        text-align: center; padding: 70px 24px; background: var(--card-bg, #111);
        border: 1px solid var(--card-border, #222); border-radius: 18px; margin-top: 10px;
    }

    .btn-caixa-direto {
        background: var(--accent, #00ffcc); color: #000; border: none;
        padding: 14px 32px; font-size: 12px; font-weight: 700; letter-spacing: 1px;
        text-transform: uppercase; border-radius: 8px; cursor: pointer; margin-top: 25px;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        display: flex; align-items: center; gap: 10px;
    }

    .btn-caixa-direto:hover {
        opacity: 0.95; transform: translateY(-2px); box-shadow: 0 0 20px rgba(0, 255, 204, 0.4);
    }
</style>

<div class="dashboard-grid">

    <?php if (!empty($permissoes['ver_dashboard'])): ?>
        
        <div class="kpi-container">
            <?php if (!empty($permissoes['ver_financeiro'])): ?>
                <div class="kpi-card">
                    <div class="kpi-title">Faturamento Mensal</div>
                    <div class="kpi-value" id="kpi-faturamento">0,00 Kz</div>
                    <i class="fas fa-wallet kpi-icon"></i>
                </div>
            <?php endif; ?>

            <?php if (!empty($permissoes['ver_estoque'])): ?>
                <div class="kpi-card alerta" style="cursor: pointer;" onclick="if(typeof loadModule === 'function') loadModule('estoque', 'Controle de Estoque')">
                    <div class="kpi-title">Stock Crítico</div>
                    <div class="kpi-value" id="kpi-estoque-min">0 Itens</div>
                    <i class="fas fa-boxes kpi-icon"></i>
                </div>
            <?php endif; ?>

            <div class="kpi-card perigo" style="cursor: pointer;" onclick="if(typeof loadModule === 'function') loadModule('perdas', 'Controle de Perdas')">
                <div class="kpi-title">Lotes Próximos do Vencimento</div>
                <div class="kpi-value" id="kpi-validades">0 Lotes</div>
                <i class="fas fa-hourglass-end kpi-icon"></i>
            </div>

            <?php if (!empty($permissoes['ver_vendas'])): ?>
                <div class="kpi-card info" style="cursor: pointer;" onclick="if(typeof loadModule === 'function') loadModule('vendas', 'Terminal de Vendas')">
                    <div class="kpi-title">Vendas Emitidas Hoje</div>
                    <div class="kpi-value" id="kpi-vendas-qtd">0 Transações</div>
                    <i class="fas fa-shopping-basket kpi-icon"></i>
                </div>
            <?php endif; ?>
        </div>

        <div class="dashboard-row-main">
            
            <?php if (!empty($permissoes['ver_financeiro'])): ?>
                <div class="panel-box">
                    <div class="panel-header">
                        <div class="panel-title"><i class="fas fa-chart-line" style="color:var(--accent, #00ffcc)"></i> Fluxo de Faturamento Semanal (Kz)</div>
                    </div>
                    <div style="position: relative; height:260px; width:100%;">
                        <canvas id="chart-fluxo-vendas"></canvas>
                    </div>
                </div>
            <?php else: ?>
                <div class="panel-box">
                    <div class="panel-header">
                        <div class="panel-title"><i class="fas fa-shield-alt" style="color:var(--accent, #00ffcc)"></i> Segurança Operacional</div>
                    </div>
                    <div style="color: var(--text-dim, #888); font-size: 13px; line-height: 1.6;">
                        <p><i class="fas fa-check-circle" style="color: var(--accent); margin-right: 8px;"></i> Módulos de auditoria e logs permanentes ativos.</p>
                        <p style="margin-top: 10px;">A sua conta possui privilégios de monitoramento operacional básico. Informações confidenciais de faturamento e balanços de caixa ocultados do painel principal.</p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="panel-box">
                <div class="panel-header">
                    <div class="panel-title"><i class="fas fa-exclamation-circle" style="color:var(--danger, #ff4444)"></i> Urgências de Validade</div>
                </div>
                <div class="report-table-wrapper">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Medicamento</th>
                                <th>Lote</th>
                                <th>Estado / Prazo</th>
                            </tr>
                        </thead>
                        <tbody id="lista-urgencias-dashboard">
                            <tr><td colspan="3" style="color:var(--text-dim, #888); padding: 20px 0;">Mapeando o inventário da farmácia...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="dashboard-row-sub">
            
            <?php if (!empty($permissoes['gerir_usuarios'])): ?>
                <div class="panel-box">
                    <div class="panel-header">
                        <div class="panel-title"><i class="fas fa-users-cog" style="color:var(--accent, #00ffcc)"></i> Produtividade dos Funcionários</div>
                    </div>
                    <div class="report-table-wrapper">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Operador</th>
                                    <th>Cargo</th>
                                    <th>Vendas</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="lista-funcionarios-dashboard">
                                <tr><td colspan="4" style="color:var(--text-dim, #888); padding: 20px 0;">Sincronizando operadores ativos...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <div class="panel-box">
                <div class="panel-header">
                    <div class="panel-title"><i class="fas fa-fire" style="color:#ffaa00"></i> Produtos Mais Procurados</div>
                </div>
                <div class="report-table-wrapper">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Descrição do Item</th>
                                <th>Saídas Registadas</th>
                                <th>Categoria</th>
                            </tr>
                        </thead>
                        <tbody id="lista-produtos-dashboard">
                            <tr><td colspan="3" style="color:var(--text-dim, #888); padding: 20px 0;">Calculando fluxo do inventário...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="caixa-foco-container">
            <i class="fas fa-cash-register" style="font-size: 55px; color: var(--accent, #00ffcc); margin-bottom: 25px;"></i>
            <h2 style="letter-spacing: 1px; color: var(--text-main, #fff); font-weight: 800;">Modo Operador de Caixa Ativo</h2>
            <p style="color: var(--text-dim, #888); margin-top: 12px; max-width: 520px; font-size: 14px; line-height: 1.6;">
                O seu perfil de acesso está configurado estritamente para vendas diretas ao balcão. Por motivos de segurança, os dados analíticos gerais e relatórios financeiros estão restritos à gerência.
            </p>
            <?php if (!empty($permissoes['ver_vendas'])): ?>
                <button class="btn-caixa-direto" onclick="if(typeof loadModule === 'function') loadModule('vendas', 'Terminal de Vendas')">
                    <i class="fas fa-shopping-cart"></i> Iniciar Terminal de Vendas (F2)
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<script>
    (function() {
        // Interrompe imediatamente se a div mestre sumir ou for reinjetada incorretamente
        const containerValido = document.querySelector('.dashboard-grid');
        if (!containerValido) return;

        // Recupera os dados locais para dupla verificação de segurança no Front
        const userDataStr = localStorage.getItem('pharmora_user_data');
        const user = userDataStr ? JSON.parse(userDataStr) : {};
        const perms = user.permissoes || {};

        // Se o funcionário não tem acesso gerencial, o script de renderização pesada para por aqui
        if (!perms.ver_dashboard) return;

        // ENGINE COMPACTA PARA ALIMENTAR OS DADOS DO BANCO
        async function alimentarDadosDashboard() {
            try {
                // Aqui a tua aplicação fará a chamada assíncrona real futuramente:
                // const response = await fetch(`${BASE_URL}api/get_dashboard_stats.php`);
                // const data = await response.json();

                // Mock de Dados Estruturados de acordo com o padrão do teu Banco de Dados do Pharmora
                document.getElementById('kpi-validades').textContent = "4 Lotes";
                
                const kpiVendas = document.getElementById('kpi-vendas-qtd');
                if (kpiVendas) kpiVendas.textContent = "28 Faturas";

                if (document.getElementById('kpi-faturamento')) {
                    document.getElementById('kpi-faturamento').textContent = "642.500,00 Kz";
                }
                if (document.getElementById('kpi-estoque-min')) {
                    document.getElementById('kpi-estoque-min').textContent = "9 Artigos";
                }

                // Alimentar Tabela: Urgências de Validades (Puxando os lotes curtos)
                const listaUrgencias = document.getElementById('lista-urgencias-dashboard');
                if (listaUrgencias) {
                    listaUrgencias.innerHTML = `
                        <tr>
                            <td><b>Paracetamol 500mg</b></td>
                            <td><code style="color:var(--accent, #00ffcc)">#LOTE-20A</code></td>
                            <td><span class="badge-status danger">12 Dias</span></td>
                        </tr>
                        <tr>
                            <td>Amoxicilina 875mg</td>
                            <td><code style="color:var(--accent, #00ffcc)">#LOTE-09X</code></td>
                            <td><span class="badge-status danger">29 Dias</span></td>
                        </tr>
                        <tr>
                            <td>Ibuprofeno 400mg</td>
                            <td><code style="color:var(--accent, #00ffcc)">#LOTE-88B</code></td>
                            <td><span class="badge-status warning">45 Dias</span></td>
                        </tr>
                    `;
                }

                // Alimentar Tabela: Produtividade dos Funcionários (Integrando dados do teu funcionarios.php)
                const listaFuncionarios = document.getElementById('lista-funcionarios-dashboard');
                if (listaFuncionarios) {
                    listaFuncionarios.innerHTML = `
                        <tr>
                            <td><b>Manuel Diogo</b></td>
                            <td style="color:var(--text-dim, #888)">Operador Caixa</td>
                            <td>18 Vendas</td>
                            <td><span class="badge-status active">Online</span></td>
                        </tr>
                        <tr>
                            <td>Edna Silva</td>
                            <td style="color:var(--text-dim, #888)">Farmacêutica</td>
                            <td>10 Vendas</td>
                            <td><span class="badge-status active">Online</span></td>
                        </tr>
                    `;
                }

                // Alimentar Tabela: Produtos Mais Procurados (Curva ABC Rápida)
                const listaProdutos = document.getElementById('lista-produtos-dashboard');
                if (listaProdutos) {
                    listaProdutos.innerHTML = `
                        <tr>
                            <td>Cataflam Gel 50g</td>
                            <td><b style="color:var(--accent, #00ffcc)">142</b> Caixas</td>
                            <td style="color:var(--text-dim, #888)">Anti-inflamatório</td>
                        </tr>
                        <tr>
                            <td>Vitamina C 1g Efervescente</td>
                            <td><b style="color:var(--accent, #00ffcc)">95</b> Caixas</td>
                            <td style="color:var(--text-dim, #888)">Suplementos</td>
                        </tr>
                        <tr>
                            <td>Dipirona Sódica Gotas</td>
                            <td><b style="color:var(--accent, #00ffcc)">84</b> Fracos</td>
                            <td style="color:var(--text-dim, #888)">Analgésicos</td>
                        </tr>
                    `;
                }

                // VERIFICAÇÃO E INICIALIZAÇÃO SEGURA DO GRÁFICO (Chart.js)
                if (perms.ver_financeiro && document.getElementById('chart-fluxo-vendas')) {
                    if (typeof Chart === 'undefined') {
                        await carregarChartJSDeFormaSegura();
                    }
                    gerarGraficoExecutivo();
                }

            } catch (error) {
                console.error("Erro na alimentação de dados do dashboard:", error);
            }
        }

        // Garante a injeção correta da CDN sem duplicar tags na Head da SPA
        function carregarChartJSDeFormaSegura() {
            return new Promise((resolve) => {
                const existente = document.querySelector('script[src*="chart.js"]');
                if (existente) {
                    // Se o script já está no cabeçalho mas ainda está a processar, espera um ciclo curto
                    setTimeout(resolve, 100);
                    return;
                }
                const script = document.createElement('script');
                script.src = "https://cdn.jsdelivr.net/npm/chart.js";
                script.onload = resolve;
                document.head.appendChild(script);
            });
        }

        function gerarGraficoExecutivo() {
            const canvas = document.getElementById('chart-fluxo-vendas');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            
            // Gradiente linear customizado para o design Dark do Pharmora
            const gradiente = ctx.createLinearGradient(0, 0, 0, 230);
            gradiente.addColorStop(0, 'rgba(0, 255, 204, 0.12)');
            gradiente.addColorStop(1, 'rgba(0, 255, 204, 0.00)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
                    datasets: [{
                        label: 'Faturamento Diário',
                        data: [195000, 240000, 162500, 310000, 280000, 342500],
                        borderColor: '#00ffcc',
                        backgroundColor: gradiente,
                        borderWidth: 3,
                        pointBackgroundColor: '#0c0c0c',
                        pointBorderColor: '#00ffcc',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        tension: 0.38,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { color: 'rgba(255, 255, 255, 0.02)' }, ticks: { color: '#777', font: { family: 'Inter', size: 11 } } },
                        y: { grid: { color: 'rgba(255, 255, 255, 0.02)' }, ticks: { color: '#777', font: { family: 'Inter', size: 11 } } }
                    }
                }
            });
        }

        // Inicializa o processo imediatamente ao acoplar o ficheiro na SPA
        alimentarDadosDashboard();
    })();
</script>