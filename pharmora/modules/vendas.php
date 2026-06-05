<?php
include_once("../config_api.php");
$operador = $_SESSION['usuario_nome'] ?? 'Operador';
?>

<style>
/* ============ ESTRUTURA GERAL ============ */
.pdv-scope { height: calc(100vh - 100px); display: flex; flex-direction: column; overflow: hidden; padding: 0 15px 15px 15px; box-sizing: border-box; }
.pdv-grid { display: flex; gap: 15px; flex: 1; min-height: 0; width: 100%; }
.pdv-column-left { flex: 0 0 70%; display: flex; flex-direction: column; min-height: 0; }
.pdv-column-right { flex: 1; display: flex; flex-direction: column; min-height: 0; }
.pdv-panel { background: var(--card-bg); border-radius: 12px; border: 1px solid var(--card-border); backdrop-filter: blur(15px); display: flex; flex-direction: column; height: 100%; overflow: hidden; }
.scroll-area { flex: 1; overflow-y: auto; padding: 15px; }

/* ============ PESQUISA E SUGESTÕES ============ */
.pdv-search-clean { display: flex; align-items: center; gap: 20px; padding: 10px 0 20px 0; }


.search-field {
    position: relative;
    min-width: 220px;
    flex: 1 1 auto;
    max-width: 400px;
}

.search-field i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-dim);
}

.search-field input {
    width: 100%;
    background: var(--input-fill);
    border: 2px solid var(--card-border);
    color: var(--text-main);
    padding: 12px 15px 12px 42px;
    border-radius: 12px;
    outline: none;
    transition: all 0.3s ease;
    font-size: 13px;
}

.search-field input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(0, 255, 204, 0.15);
}

.sugestoes-box { position: absolute; top: 100%; left: 0; right: 0; background: var(--card-bg); border: 1px solid var(--accent); border-radius: 8px; margin-top: 5px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); z-index: 100; max-height: 250px; overflow-y: auto; display: none; }
.sugestao-item { padding: 12px 15px; border-bottom: 1px solid var(--card-border); cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
.sugestao-item:hover { background: rgba(0, 255, 204, 0.1); }

/* ============ TABELA E EXPANSÃO ============ */
.pdv-table { width: 100%; border-collapse: collapse; }
.pdv-table th { position: sticky; top: 0; background: var(--card-bg); padding: 12px; text-align: left; font-size: 10px; text-transform: uppercase; color: var(--text-dim); border-bottom: 2px solid var(--card-border); z-index: 10; }
.pdv-table td { padding: 12px; border-bottom: 1px solid var(--card-border); font-size: 12px; }

.expand-row { display: none; background: rgba(0, 255, 204, 0.02); border-left: 4px solid var(--accent); }
.expand-row.show { display: table-row; animation: expandIn 0.3s ease forwards; }

.detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; padding: 15px; }
.detail-item { padding: 8px; background: var(--input-fill); border-radius: 6px; border: 1px solid var(--card-border); }
.detail-label { font-size: 9px; text-transform: uppercase; color: var(--accent); font-weight: 700; display: block; margin-bottom: 3px; }
.detail-value { font-size: 12px; color: var(--text-main); }

@keyframes expandIn { from { opacity: 0; } to { opacity: 1; } }

/* ============ FINANCEIRO ============ */
.total-box { background: var(--input-fill); padding: 15px; border-radius: 10px; text-align: right; margin-bottom: 15px; border: 1px solid var(--card-border); }
.total-box .value { font-size: 28px; font-weight: 900; color: var(--text-main); display: block; }
.btn-finish-venda { width: 100%; padding: 18px; background: var(--accent); color: #000; border: none; border-radius: 10px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }

.btn-icon { background: transparent; border: none; cursor: pointer; font-size: 16px; margin: 0 5px; }
.btn-detalhes { color: #4da6ff; }
.btn-remover { color: #ff4d4d; }

.detail-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); 
    gap: 12px; 
    padding: 20px;
    background: rgba(0,0,0,0.2);
}
.detail-section-title {
    grid-column: 1 / -1;
    color: var(--accent);
    font-size: 11px;
    text-transform: uppercase;
    font-weight: 800;
    margin-top: 10px;
    border-bottom: 1px solid var(--card-border);
    padding-bottom: 5px;
}

/* Atalhos e Status */
.pdv-footer-info {
    display: flex;
    justify-content: space-between;
    padding: 10px 5px 0;
    font-size: 10px;
    color: var(--text-dim);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.pdv-footer-info b { color: var(--accent); margin: 0 3px; }
/* Configuração da área de scroll */
.scroll-area {
    flex: 1;                /* Faz a área ocupar todo o espaço disponível no painel */
    overflow-y: auto;       /* Ativa o scroll vertical quando o conteúdo exceder a altura */
    overflow-x: hidden;     /* Evita scroll horizontal indesejado */
    min-height: 0;          /* Importante para o flexbox permitir o encolhimento do container */
}

/* Fixar o Cabeçalho da Tabela (Opcional, mas recomendado para PDV) */
.pdv-table thead th {
    position: sticky;
    top: 0;
    background: var(--card-bg); /* Ou a cor de fundo do seu painel */
    z-index: 2;
    border-bottom: 2px solid var(--card-border);
}

/* Scrollbar Customizada (Seu código já está ótimo, apenas mantendo a referência) */
.scroll-area::-webkit-scrollbar {
    width: 6px;
}
.scroll-area::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02);
    border-radius: 10px;
}
.scroll-area::-webkit-scrollbar-thumb {
    background: var(--accent);
    border-radius: 10px;
}

/* Container de Quantidade na Tabela */
.qty-control {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    background: var(--input-fill);
    border-radius: 6px;
    padding: 2px;
    border: 1px solid var(--card-border);
    width: fit-content;
    margin: 0 auto;
}

.btn-qty {
    background: transparent;
    border: none;
    color: var(--text-dim);
    cursor: pointer;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.2s;
    border-radius: 4px;
}

.btn-qty:hover {
    background: var(--accent);
    color: #000;
}

.qty-value {
    font-weight: 700;
    color: var(--text-main);
    min-width: 25px;
    text-align: center;
    font-size: 14px;
}

/* Efeito de destaque na linha ao passar o mouse */
.pdv-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.03);
}

/* ============ MODAL DE CONFIRMAÇÃO GLOBAL ============ */
.modal-confirm-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(10px);
    z-index: 10000;
    display: none;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.modal-confirm-overlay.active {
    display: flex !important;
}

.modal-confirm-box {
    width: 100%;
    max-width: 400px;
    background: var(--card-bg, #1e1e2d);
    border: 1px solid var(--card-border, #323248);
    border-radius: 20px;
    padding: 30px;
    text-align: center;
    animation: modalPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.modal-confirm-icon {
    width: 60px;
    height: 60px;
    background: rgba(243, 156, 18, 0.1);
    color: #f39c12;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin: 0 auto 20px;
    border: 1px solid rgba(243, 156, 18, 0.2);
}

.modal-confirm-box h3 {
    color: var(--text-main, #fff);
    margin-bottom: 10px;
    font-size: 18px;
}

.modal-confirm-box p {
    color: var(--text-dim, #9a9cae);
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 25px;
}

.modal-confirm-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.btn-confirm-cancel {
    padding: 12px;
    border-radius: 12px;
    border: 1px solid var(--card-border, #323248);
    background: var(--input-fill, rgba(0,0,0,0.2));
    color: var(--text-main, #fff);
    cursor: pointer;
    font-weight: 600;
    transition: 0.3s;
}

.btn-confirm-cancel:hover { background: rgba(255,255,255,0.1); }

.btn-confirm-action {
    padding: 12px;
    border-radius: 12px;
    border: none;
    background: #e74c3c;
    color: white;
    cursor: pointer;
    font-weight: 700;
    transition: 0.3s;
}

.btn-confirm-action:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

@keyframes modalPop {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

/* ============ ESTADO VAZIO E TOAST ============ */
.empty-state { text-align: center; padding: 40px 20px; color: var(--text-dim); }
.empty-state i { font-size: 40px; margin-bottom: 10px; display: block; }

.toast-container { position: fixed; top: 20px; right: 20px; z-index: 99999; display: flex; flex-direction: column; gap: 10px; }
.toast-item {
    padding: 14px 20px; border-radius: 10px; color: #fff; font-size: 14px; font-weight: 600;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3); animation: slideInToast 0.3s ease;
    display: flex; align-items: center; gap: 10px; min-width: 280px; max-width: 400px;
    backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1);
}
.toast-success { background: rgba(46,204,113,0.9); }
.toast-error { background: rgba(231,76,60,0.9); }
.toast-warning { background: rgba(243, 156, 18, 0.9); }

@keyframes slideInToast { from { transform: translateX(100px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

.select-modo {
    background: var(--input-fill, #2b2b3d);
    color: var(--text-main, #fff);
    border: 1px solid var(--card-border, #323248);
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 12px;
    cursor: pointer;
    outline: none;
}

.select-modo:focus {
    border-color: var(--accent);
}
</style>

<!-- HTML DO MODAL (Coloque no fim do <body>) -->
<div id="modal-confirm" class="modal-confirm-overlay">
    <div class="modal-confirm-box">
        <div class="modal-confirm-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3 id="confirm-title">Confirmação</h3>
        <p id="confirm-msg">Tem certeza que deseja continuar?</p>
        <div class="modal-confirm-buttons">
            <button class="btn-confirm-cancel" onclick="closeConfirmModal()">Cancelar</button>
            <button id="btn-confirm-execute" class="btn-confirm-action">Confirmar</button>
        </div>
    </div>
</div>

<div class="pdv-scope">
    <div class="pdv-search-clean">
        <h2 style="margin:0; font-weight: 900; color: var(--text-main); font-size: 20px;"> <i class="fas fa-shopping-cart"></i> Terminal de Vendas <span style="color:var(--accent);">●</span></h2>
        <div class="search-field">
            <i class="fas fa-search"></i>
            <input type="text" id="input_venda" placeholder="Digite o nome ou bipe o código..." autofocus autocomplete="off">
            <div id="sugestoes_container" class="sugestoes-box"></div>
        </div>
    </div>

    <div class="pdv-grid">
        <!-- Coluna Esquerda -->
<div class="pdv-column-left" style="display: flex; flex-direction: column; height: 100%;">
    
    <!-- O Painel deve ser flex para a scroll-area crescer -->
    <div class="pdv-panel" style="display: flex; flex-direction: column; flex: 1; overflow: hidden;">
        
        <div class="scroll-area">
            <table class="pdv-table">
                <thead>
    <tr>
        <th>Cód. Barra</th>
        <th>Produto</th>
        <th>Venda</th> 
        <th>Dosagem</th>
        <th>P. Unitário</th>
        <th style="text-align:center;">Qtd</th>
        <th style="text-align:right;">Subtotal</th>
        <th style="text-align:center;">Ações</th>
    </tr>
</thead>
                <tbody id="venda_itens">
                    <!-- Itens aqui -->
                    <tr class="empty-row">
                        <td colspan="7" style="text-align: center; padding: 100px; color: var(--text-dim); font-style: italic;">
                            Aguardando produtos para iniciar a venda...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Rodapé fora da área de scroll para ficar sempre visível -->
    <div class="pdv-footer-info" style="margin-top: 10px;">
        <span>Operador: <b><?php echo $operador; ?></b></span>
        <span>F2:<b>Pesquisar</b> | F10:<b>Finalizar</b> | ESC:<b>Limpar</b></span>
    </div>
</div>

        <div class="pdv-column-right">
    <div class="pdv-panel" style="display: flex; flex-direction: column; height: 100%;">
        
        <!-- AREA DE ROLAGEM: Contém tudo menos o botão de finalizar -->
        <div class="scroll-area" style="flex: 1; overflow-y: auto; padding: 15px;">
            
            <!-- BLOCO DE TOTAIS -->
            <div class="total-box">
                <span style="font-size: 10px; color: var(--accent); font-weight: 800; text-transform: uppercase;">Valor Total da Venda</span>
                <span class="value" id="resumo_total">0,00 Kz</span>
            </div>

            <!-- AJUSTES FINANCEIROS -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px;">
                <div class="input-group">
                    <label style="font-size: 10px; color: var(--text-dim); font-weight: 700;">DESCONTO (Kz)</label>
                    <input type="number" id="valor_desconto" value="0.00" oninput="calcularFinanceiro()" style="background: var(--input-fill); border: 1px solid var(--card-border); border-radius: 8px; color: #ff6b6b; padding: 10px; width: 100%; font-weight: bold;">
                </div>
                <div class="input-group">
                    <label style="font-size: 10px; color: var(--text-dim); font-weight: 700;">ACRÉSCIMO (Kz)</label>
                    <input type="number" id="valor_acrescimo" value="0.00" oninput="calcularFinanceiro()" style="background: var(--input-fill); border: 1px solid var(--card-border); border-radius: 8px; color: #4da6ff; padding: 10px; width: 100%; font-weight: bold;">
                </div>
            </div>

            <!-- DADOS DO CLIENTE -->
            <div class="input-group" style="margin-bottom: 20px;">
                <label style="font-size: 10px; color: var(--text-dim); font-weight: 700;">IDENTIFICAÇÃO DO CLIENTE</label>
                <input type="text" value="Consumidor Final" style="background: var(--input-fill); border: 1px solid var(--card-border); border-radius: 8px; color: #fff; padding: 12px; width: 100%;">
            </div>

            <!-- FORMA DE PAGAMENTO -->
            <div style="margin-top: 10px;">
                <label style="font-size: 10px; color: var(--accent); font-weight: 800;">MÉTODO DE PAGAMENTO</label>
                <select id="metodo_pagamento" onchange="toggleTroco()" style="background: var(--input-fill); border: 1px solid var(--card-border); border-radius: 8px; color: #fff; padding: 12px; width: 100%; cursor: pointer;">
                    <option value="dinheiro">Numerário (Dinheiro)</option>
                    <option value="mpesa">M-Pesa</option>
                    <option value="emola">E-Mola</option>
                    <option value="pos">TPA / Multicaixa</option>
                    <option value="misto">Pagamento Misto</option>
                </select>
            </div>

            <div id="container_recebimento" style="margin-top: 20px; padding: 15px; background: rgba(0, 255, 204, 0.05); border-radius: 10px; border: 1px dashed var(--accent);">
    <div style="margin-bottom: 15px;">
        <label style="font-size: 10px; color: var(--text-dim); font-weight: 700;">VALOR ENTREGUE PELO CLIENTE</label>
        <input type="number" id="valor_pago" oninput="calcularFinanceiro()" placeholder="0.00" style="background: transparent; border: none; border-bottom: 2px solid var(--card-border); color: var(--accent); font-size: 24px; font-weight: 800; width: 100%; outline: none;">
    </div>
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <span style="font-size: 11px; color: var(--text-dim); font-weight: 700;">TROCO A DEVOLVER:</span>
        <span id="valor_troco" style="font-size: 20px; font-weight: 900; color: #fff;">0,00 Kz</span>
    </div>
</div>
        </div> 
        <!-- Fim da scroll-area -->

        <!-- RODAPÉ FIXO: O botão nunca "foge" da tela -->
        <div style="padding: 15px; background: rgba(0,0,0,0.3); border-top: 1px solid var(--card-border);">
            <div id="info_final" style="margin-bottom: 10px; text-align: center; font-size: 11px; color: var(--text-dim);">
                Subtotal: <span id="subtotal_venda" style="font-weight: bold; color: #fff;">0,00</span> Kz
            </div>
            <button class="btn-finish-venda" onclick="finalizarVenda()" style="width: 100%; height: 50px; font-weight: 800;">
                <i class="fas fa-check-double"></i> CONFIRMAR TRANSAÇÃO (F10)
            </button>
        </div>

    </div>
</div>
    </div>
</div>

<script>
// ================= TOAST E MODAL GLOBAL =================
function showToast(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    const icon = type === 'success' ? '✔️' : (type === 'error' ? '❌' : '⚠️');
    const toast = document.createElement('div');
    toast.className = `toast-item toast-${type}`;
    toast.innerHTML = `<span>${icon}</span> ${message}`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

function confirmarAcao(titulo, mensagem, corBotao = '#e74c3c') {
    return new Promise((resolve) => {
        const modal = document.getElementById('modal-confirm');
        const titleEl = document.getElementById('confirm-title');
        const msgEl = document.getElementById('confirm-msg');
        const btnExec = document.getElementById('btn-confirm-execute');

        titleEl.innerText = titulo;
        msgEl.innerText = mensagem;
        btnExec.style.backgroundColor = corBotao;
        
        modal.classList.add('active');

        btnExec.onclick = () => {
            modal.classList.remove('active');
            resolve(true);
        };

        modal.onclick = (e) => {
            if(e.target === modal) {
                modal.classList.remove('active');
                resolve(false);
            }
        };
    });
}

function closeConfirmModal() {
    document.getElementById('modal-confirm').classList.remove('active');
}

// ================= LÓGICA DE VENDAS =================
window.carrinho = window.carrinho || [];
window.produtosRetornados = [];

const inputVenda = document.getElementById('input_venda');
const sugestoesBox = document.getElementById('sugestoes_container');
const tabelaItens = document.getElementById('venda_itens');
const displayTotal = document.getElementById('resumo_total');

window.buscarProdutos = async function(query) {
    try {
        const response = await fetch(`modules/busca_produto.php?query=${encodeURIComponent(query)}`);
        const result = await response.json();
        if (result.success && result.data.length > 0) {
            window.produtosRetornados = result.data;
            sugestoesBox.innerHTML = result.data.map((p, i) => `
                <div class="sugestao-item" onclick="adicionarAoCarrinho(${i})">
                    <div>
                        <div style="font-weight:700; color:var(--text-main); font-size:13px;">${p.nome_produto}</div>
                        <div style="font-size:11px; color:var(--text-dim);">Cód: ${p.codigo_barra} | Est: ${p.estoque_atual_caixas}</div>
                    </div>
                    <div style="color:var(--accent); font-weight:800;">${parseFloat(p.preco_venda_caixa).toLocaleString('pt-BR')} Kz</div>
                </div>
            `).join('');
            sugestoesBox.style.display = 'block';
        }
    } catch (e) { 
        console.error("Erro na busca", e);
        showToast("Erro ao buscar produtos.", "error");
    }
};

window.adicionarAoCarrinho = function(index) {
    const p = window.produtosRetornados[index];
    const itemNoCarrinho = window.carrinho.find(item => item.id_produto === p.id_produto);

    if (itemNoCarrinho) {
        itemNoCarrinho.qtd++;
    } else {
        window.carrinho.push({ 
            ...p, 
            qtd: 1, 
            modo_venda: 'caixa' 
        });
    }

    if(typeof inputVenda !== 'undefined' && inputVenda) inputVenda.value = '';
    if(typeof sugestoesBox !== 'undefined' && sugestoesBox) sugestoesBox.style.display = 'none';
    
    renderizarTabela();
    if(typeof inputVenda !== 'undefined' && inputVenda) inputVenda.focus();
};

window.removerItem = function(i) {
    window.carrinho.splice(i, 1);
    renderizarTabela();
    showToast("Item removido do carrinho.", "warning");
};

window.alterarQtd = async function(index, delta) {
    const item = window.carrinho[index];
    const novaQtd = item.qtd + delta;

    if (novaQtd > 0) {
        if (novaQtd <= item.estoque_atual_caixas) {
            item.qtd = novaQtd;
            renderizarTabela();
        } else {
            showToast("Quantidade excede o estoque disponível!", "error");
        }
    } else {
        const confirmarRemocao = await confirmarAcao(
            "Remover Item", 
            "Deseja remover este produto do carrinho?", 
            "#e74c3c"
        );
        
        if (confirmarRemocao) {
            window.removerItem(index);
        }
    }
};

window.renderizarTabela = function() {
    if (window.carrinho.length === 0) {
        tabelaItens.innerHTML = `
            <tr class="empty-row">
                <td colspan="8" style="text-align:center; padding:100px; color:var(--text-dim);">
                    <i class="fas fa-shopping-basket" style="font-size: 30px; margin-bottom: 10px; display: block; opacity: 0.3;"></i>
                    Aguardando produtos para iniciar a venda...
                </td>
            </tr>`;
        if(displayTotal) displayTotal.innerText = "0,00 Kz";
        window.calcularFinanceiro();
        return;
    }

    let html = '';
    window.carrinho.forEach((item, i) => {
        if (!item.modo_venda) item.modo_venda = 'caixa';

        const eRetalho = item.modo_venda === 'unidade';
        const precoUnitario = eRetalho ? parseFloat(item.preco_venda_unidade) : parseFloat(item.preco_venda_caixa);
        const subtotal = item.qtd * precoUnitario;

        html += `
            <tr class="main-row" id="row-${item.id_produto}">
                <td style="font-size: 11px; color: var(--text-dim);">${item.codigo_barra}</td>
                <td>
                    <div style="display:flex; flex-direction:column;">
                        <b style="color:var(--text-main);">${item.nome_produto}</b>
                        <small style="font-size:10px; color:var(--text-dim);">${item.categoria || 'Geral'}</small>
                    </div>
                </td>
                <td style="text-align:center;">
                    ${item.permite_retalho == 1 ? `
                        <select class="select-modo" onchange="window.mudarModoVenda(${i}, this.value)" 
                                style="background: var(--card-bg); color: var(--text-main); border: 1px solid var(--card-border); border-radius: 4px; font-size: 11px; padding: 2px;">
                            <option value="caixa" ${!eRetalho ? 'selected' : ''}>📦 Grosso</option>
                            <option value="unidade" ${eRetalho ? 'selected' : ''}>💊 Retalho</option>
                        </select>
                    ` : '<span style="font-size:10px; color:var(--text-dim);">Apenas Caixa</span>'}
                </td>
                <td style="font-size: 12px;">${item.dosagem_peso || '-'}</td>
                <td style="font-size: 13px; font-weight: 600;">
                    ${precoUnitario.toLocaleString('pt-BR', {minimumFractionDigits:2})}
                </td>
                <td style="text-align:center;">
                    <div class="qty-control">
                        <button class="btn-qty" onclick="window.alterarQtd(${i}, -1)">
                            <i class="fas fa-minus" style="font-size: 10px;"></i>
                        </button>
                        <span class="qty-value">${item.qtd}</span>
                        <button class="btn-qty" onclick="window.alterarQtd(${i}, 1)">
                            <i class="fas fa-plus" style="font-size: 10px;"></i>
                        </button>
                    </div>
                </td>
                <td style="text-align:right; font-weight:700; color:var(--accent); font-size: 14px;">
                    ${subtotal.toLocaleString('pt-BR', {minimumFractionDigits:2})} Kz
                </td>
                <td style="text-align:center;">
                    <button class="btn-icon btn-detalhes" title="Ver Detalhes" onclick='toggleDetalhes(${JSON.stringify(item)}, this)'>
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-icon btn-remover" title="Remover" onclick="window.alterarQtd(${i}, -${item.qtd})">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tabelaItens.innerHTML = html;
    window.calcularFinanceiro();
};

window.mudarModoVenda = function(index, novoModo) {
    const item = window.carrinho[index];
    item.modo_venda = novoModo; 
    item.qtd = 1; 

    showToast(`Modo alterado para ${novoModo.toUpperCase()}`, "success");
    renderizarTabela();
};

window.toggleDetalhes = function(p, btn) {
    if (!p) return;

    const rowId = `row-${p.id_produto}`;
    const expandId = `expand-${p.id_produto}`;
    const mainRow = document.getElementById(rowId);
    let expandRow = document.getElementById(expandId);
    const icon = btn.querySelector('i');

    if (expandRow) {
        expandRow.classList.toggle('show');
        const isShown = expandRow.classList.contains('show');
        icon.className = isShown ? 'fas fa-eye-slash' : 'fas fa-eye';
        btn.style.color = isShown ? 'var(--accent)' : '#4da6ff';
        return;
    }

    expandRow = document.createElement('tr');
    expandRow.id = expandId;
    expandRow.className = 'expand-row show';
    
    const fMoeda = (v) => parseFloat(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) + " Kz";
    const fData = (d) => d ? d.split(' ')[0].split('-').reverse().join('/') : '---';
    const fSimNao = (v) => parseInt(v) === 1 ? '<b style="color:var(--accent);">Sim</b>' : 'Não';

    expandRow.innerHTML = `
        <td colspan="8">
            <div class="detail-grid">
                <div style="grid-column: 1/-1; border-bottom: 1px solid var(--card-border); padding-bottom: 5px; margin-bottom: 5px; color: var(--accent); font-size: 11px; font-weight: 800; text-transform: uppercase;">
                    <i class="fas fa-info-circle"></i> Detalhes do Item
                </div>
                <div class="detail-item"><span class="detail-label">Princípio Ativo</span><span class="detail-value">${p.principio_ativo || 'N/A'}</span></div>
                <div class="detail-item"><span class="detail-label">Categoria</span><span class="detail-value">${p.categoria || 'Geral'}</span></div>
                <div class="detail-item"><span class="detail-label">Apresentação</span><span class="detail-value">${p.tipo_apresentacao || 'N/A'} (${p.dosagem_peso || ''})</span></div>
                <div class="detail-item"><span class="detail-label">Lote</span><span class="detail-value">${p.lote || '---'}</span></div>
                <div class="detail-item"><span class="detail-label">Validade</span><span class="detail-value" style="color:#e67e22; font-weight:700;">${fData(p.data_validade)}</span></div>
                <div class="detail-item"><span class="detail-label">Status</span><span class="detail-value">${p.status_item}</span></div>

                <div style="grid-column: 1/-1; border-bottom: 1px solid var(--card-border); padding-bottom: 5px; margin-top: 10px; margin-bottom: 5px; color: var(--accent); font-size: 11px; font-weight: 800; text-transform: uppercase;">
                    <i class="fas fa-dollar-sign"></i> Financeiro e Precificação
                </div>
                <div class="detail-item"><span class="detail-label">Preço Compra</span><span class="detail-value">${fMoeda(p.preco_compra)}</span></div>
                <div class="detail-item"><span class="detail-label">Taxa IVA</span><span class="detail-value">${p.taxa_iva}%</span></div>
                <div class="detail-item"><span class="detail-label">Preço Caixa</span><span class="detail-value" style="color:var(--accent); font-weight:800;">${fMoeda(p.preco_venda_caixa)}</span></div>
                <div class="detail-item"><span class="detail-label">Preço Unidade</span><span class="detail-value">${fMoeda(p.preco_venda_unidade)}</span></div>
                <div class="detail-item"><span class="detail-label">Promocional</span><span class="detail-value">${fMoeda(p.preco_promocional)}</span></div>
                <div class="detail-item"><span class="detail-label">Retalho</span><span class="detail-value">${fSimNao(p.permite_retalho)} (${p.unidades_por_caixa} un/cx)</span></div>

                <div style="grid-column: 1/-1; border-bottom: 1px solid var(--card-border); padding-bottom: 5px; margin-top: 10px; margin-bottom: 5px; color: var(--accent); font-size: 11px; font-weight: 800; text-transform: uppercase;">
                    <i class="fas fa-truck"></i> Estoque e Logística
                </div>
                <div class="detail-item"><span class="detail-label">Fornecedor</span><span class="detail-value">${p.fornecedor || 'N/A'}</span></div>
                <div class="detail-item"><span class="detail-label">Estoque Atual</span><span class="detail-value">${p.estoque_atual_caixas} cx</span></div>
                <div class="detail-item"><span class="detail-label">Estoque Mínimo</span><span class="detail-value">${p.estoque_minimo_caixas} cx</span></div>
                <div class="detail-item"><span class="detail-label">Localização</span><span class="detail-value">${p.localizacao_corredor || 'N/A'}</span></div>
                <div class="detail-item"><span class="detail-label">Requer Receita</span><span class="detail-value">${fSimNao(p.requer_receita)}</span></div>
                <div class="detail-item"><span class="detail-label">Refrigerado</span><span class="detail-value">${fSimNao(p.refrigerado)}</span></div>
                <div style="grid-column: 1/-1; text-align: right; font-size: 9px; color: var(--text-dim); margin-top: 10px;">
                    Cadastrado em: ${fData(p.data_cadastro)} | Última atualização: ${p.ultima_atualizacao}
                </div>
            </div>
        </td>
    `;

    mainRow.after(expandRow);
    icon.className = 'fas fa-eye-slash';
    btn.style.color = 'var(--accent)';
};

if(inputVenda) {
    inputVenda.addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length >= 3) {
            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => window.buscarProdutos(query), 300);
        } else {
            if(sugestoesBox) sugestoesBox.style.display = 'none';
        }
    });
}

document.addEventListener('click', function(e) {
    if (sugestoesBox && inputVenda && !sugestoesBox.contains(e.target) && e.target !== inputVenda) {
        sugestoesBox.style.display = 'none';
    }
});

window.toggleTroco = function() {
    const metodo = document.getElementById('metodo_pagamento').value;
    const container = document.getElementById('container_recebimento');
    const inputPago = document.getElementById('valor_pago');

    if (metodo === 'dinheiro' || metodo === 'misto') {
        container.style.opacity = "1";
        container.style.pointerEvents = "auto";
        inputPago.disabled = false;
    } else {
        container.style.opacity = "0.5";
        container.style.pointerEvents = "none";
        inputPago.value = "";
        document.getElementById('valor_troco').innerText = "0,00 Kz";
        inputPago.disabled = true;
    }
    window.calcularFinanceiro();
};

window.calcularFinanceiro = function() {
    const elMetodo = document.getElementById('metodo_pagamento');
    const descEl = document.getElementById('valor_desconto');
    const acrEl = document.getElementById('valor_acrescimo');
    const pagoEl = document.getElementById('valor_pago');
    const subtotalEl = document.getElementById('subtotal_venda');
    const displayTroco = document.getElementById('valor_troco');
    const displayTotalGeral = document.getElementById('resumo_total'); 
    const btnFinalizar = document.querySelector('.btn-finish-venda');

    if (!elMetodo) return;

    const metodo = elMetodo.value;
    const desconto = descEl ? (parseFloat(descEl.value) || 0) : 0;
    const acrescimo = acrEl ? (parseFloat(acrEl.value) || 0) : 0;
    const valorPago = pagoEl ? (parseFloat(pagoEl.value) || 0) : 0;

    const subtotal = (window.carrinho || []).reduce((acc, item) => {
        const preco = item.modo_venda === 'unidade' 
            ? parseFloat(item.preco_venda_unidade) 
            : parseFloat(item.preco_venda_caixa);
        return acc + (item.qtd * (preco || 0));
    }, 0);

    const totalFinal = subtotal + acrescimo - desconto;

    if (subtotalEl) {
        subtotalEl.innerText = subtotal.toLocaleString('pt-BR', {minimumFractionDigits: 2});
    }

    if (displayTotalGeral) {
        displayTotalGeral.innerText = totalFinal.toLocaleString('pt-BR', {minimumFractionDigits: 2}) + " Kz";
    }

    if (!displayTroco || !btnFinalizar) return;

    const saldo = valorPago - totalFinal;

    if (metodo === 'dinheiro') {
        if (valorPago <= 0 || saldo < 0) {
            displayTroco.innerText = valorPago <= 0 ? "Aguardando pagamento..." : "Faltam: " + Math.abs(saldo).toLocaleString('pt-BR', {minimumFractionDigits: 2}) + " Kz";
            displayTroco.style.color = "#ff4d4d";
            btnFinalizar.disabled = true;
            btnFinalizar.style.opacity = "0.5";
        } else {
            displayTroco.innerText = saldo.toLocaleString('pt-BR', {minimumFractionDigits: 2}) + " Kz";
            displayTroco.style.color = "var(--accent)"; 
            btnFinalizar.disabled = false;
            btnFinalizar.style.opacity = "1";
        }
    } 
    else if (metodo === 'misto') {
        if (valorPago <= 0) {
            displayTroco.innerText = "Informe o valor em dinheiro";
            displayTroco.style.color = "#ff4d4d";
            btnFinalizar.disabled = true;
        } else {
            const restanteCartao = totalFinal - valorPago;
            displayTroco.innerText = restanteCartao > 0 
                ? "Restante no Cartão: " + restanteCartao.toLocaleString('pt-BR', {minimumFractionDigits: 2}) + " Kz" 
                : "Troco: " + Math.abs(restanteCartao).toLocaleString('pt-BR', {minimumFractionDigits: 2}) + " Kz";
            displayTroco.style.color = "#4da6ff";
            btnFinalizar.disabled = false;
            btnFinalizar.style.opacity = "1";
        }
    } 
    else {
        displayTroco.innerText = "Pagamento Digital";
        displayTroco.style.color = "#fff";
        btnFinalizar.disabled = false;
        btnFinalizar.style.opacity = "1";
    }
};

window.imprimirCupom = function(vendaId) {
    const url = `modules/gerar_cupom.php?id=${vendaId}`;
    const nomeJanela = `Cupom_${vendaId}`;
    const specs = "width=400,height=600,menubar=no,toolbar=no,location=no,status=no";
    window.open(url, nomeJanela, specs);
};

window.finalizarVenda = async function() {
    if (window.carrinho.length === 0) {
        showToast("O carrinho está vazio!", "error");
        return;
    }

    for (const item of window.carrinho) {
        const estoqueDisponivel = parseFloat(item.estoque_atual_caixas);
        
        if (item.modo_venda === 'caixa') {
            if (item.qtd > estoqueDisponivel) {
                showToast(`Estoque insuficiente: ${item.nome_produto} (Disponível: ${estoqueDisponivel.toFixed(3)} Cx)`, "error");
                return;
            }
        } else {
            const unidadesTotaisDisponiveis = estoqueDisponivel * parseInt(item.unidades_por_caixa);
            if (item.qtd > unidadesTotaisDisponiveis) {
                showToast(`Estoque insuficiente: ${item.nome_produto} (Disponível: ${unidadesTotaisDisponiveis} Un)`, "error");
                return;
            }
        }
    }

    const metodo = document.getElementById('metodo_pagamento').value;
    const valorPago = parseFloat(document.getElementById('valor_pago').value) || 0;
    const desconto = parseFloat(document.getElementById('valor_desconto').value) || 0;
    const acrescimo = parseFloat(document.getElementById('valor_acrescimo').value) || 0;
    
    const subtotalReal = window.carrinho.reduce((acc, item) => {
        const preco = item.modo_venda === 'unidade' ? parseFloat(item.preco_venda_unidade) : parseFloat(item.preco_venda_caixa);
        return acc + (item.qtd * preco);
    }, 0);

    const totalFinalCalculado = subtotalReal + acrescimo - desconto;

    if ((metodo === 'dinheiro' || metodo === 'misto') && valorPago <= 0) {
        showToast("Insira o valor recebido!", "warning");
        document.getElementById('valor_pago').focus();
        return;
    }

    if (metodo === 'dinheiro' && valorPago < (totalFinalCalculado - 0.01)) {
        showToast("Valor pago insuficiente!", "error");
        return;
    }

    const inputCliente = document.querySelector('input[type="text"][value="Consumidor Final"]');
    const nomeCliente = inputCliente ? inputCliente.value : "Consumidor Final";

    // AJUSTE AQUI: Removido o campo 'troco:' para não enviar ao PHP
    const dadosVenda = {
        itens: window.carrinho,
        nome_cliente: nomeCliente,
        subtotal: subtotalReal,
        desconto: desconto,
        acrescimo: acrescimo,
        total_final: totalFinalCalculado,
        metodo_pagamento: metodo,
        valor_pago: valorPago
    };

    const confirmado = await confirmarAcao(
        "Finalizar Venda", 
        `Confirmar venda de ${totalFinalCalculado.toLocaleString('pt-BR', {minimumFractionDigits: 2})} Kz?`,
        "#2ecc71"
    );

    if (confirmado) {
        try {
            const response = await fetch('modules/processar_venda.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dadosVenda)
            });

            const resultado = await response.json();

            if (resultado.success || resultado.status === 'success') {
                showToast("Venda processada com sucesso!", "success");
                
                const idParaImprimir = resultado.venda_id || resultado.id_venda;
                if (idParaImprimir) {
                    window.imprimirCupom(idParaImprimir);
                }

                window.carrinho = [];
                if (typeof renderizarTabela === "function") renderizarTabela();
                
                ['valor_desconto', 'valor_acrescimo', 'valor_pago'].forEach(id => {
                    const el = document.getElementById(id);
                    if(el) el.value = (id === 'valor_pago' ? "" : "0.00");
                });

                if (typeof window.calcularFinanceiro === "function") window.calcularFinanceiro();
                
            } else {
                showToast("Erro: " + resultado.message, "error");
            }
        } catch (e) {
            console.error(e);
            showToast("Erro de comunicação com o servidor.", "error");
        }
    }
};

document.addEventListener('keydown', function(e) {
    if (e.key === 'F10') {
        e.preventDefault();
        window.finalizarVenda();
    }
    if (e.key === 'F2') {
        e.preventDefault();
        const inputBusca = document.getElementById('input_venda');
        if(inputBusca) inputBusca.focus();
    }
    if (e.key === 'Escape') {
        if(confirm("Deseja limpar o carrinho atual?")) {
            window.carrinho = [];
            window.renderizarTabela();
        }
    }
});

window.renderizarTabela();
</script>