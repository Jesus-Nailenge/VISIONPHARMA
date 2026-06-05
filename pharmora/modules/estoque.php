<?php
include_once("../config_api.php");

// Busca os produtos no banco de dados
$sql = "SELECT * FROM produtos ORDER BY id_produto DESC"; 
$resultado = $conn->query($sql);
$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/pharmora/";
?>

<style>
/* ============ CONFIGURAÇÕES GERAIS E CONTAINER ============ */
.rh-container {
    width: 100%;
    max-width: 100%;
    margin: 0;
    padding: 0;
    animation: fadeIn 0.4s ease;
    color: var(--text-main);
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    box-sizing: border-box;
    overflow-x: hidden;
}

/* ============ HEADER & TOP BAR ============ */
.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
    width: 100%;
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
    margin-top: 5px;
}

.header-actions {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
    flex: 1 1 auto;
    justify-content: flex-end;
}

/* ============ BUSCA E BOTÕES PRINCIPAIS ============ */
.search-box {
    position: relative;
    min-width: 220px;
    flex: 1 1 auto;
    max-width: 400px;
}

.search-box i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-dim);
}

.search-box input {
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

.search-box input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(0, 255, 204, 0.15);
}

.btn-new {
    background: var(--accent);
    color: #000;
    border: none;
    padding: 12px 20px;
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 13px;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(0, 255, 204, 0.25);
}

.btn-new:hover {
    background: transparent;
    color: var(--accent);
    border: 1px solid var(--accent);
    transform: translateY(-2px);
}

/* ============ TABELA GLASSMORPHISM ============ */
.table-glass {
    width: 100%;
    background: var(--card-bg);
    border-radius: 16px;
    overflow-x: auto;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(15px);
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    -webkit-overflow-scrolling: touch;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

/* Scrollbar customizada para a tabela */
.table-glass::-webkit-scrollbar { height: 4px; }
.table-glass::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.02); border-radius: 10px; }
.table-glass::-webkit-scrollbar-thumb { background: var(--accent); border-radius: 10px; }

th {
    background: var(--input-fill);
    padding: 14px 12px;
    text-align: left;
    color: var(--text-dim);
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 700;
    border-bottom: 2px solid var(--card-border);
}

td {
    padding: 12px 12px;
    border-bottom: 1px solid var(--card-border);
    font-size: 13px;
}

tbody tr.main-row:hover { background: rgba(255, 255, 255, 0.02); }

/* ============ CÉLULA DE ENTIDADE (USER OU PRODUTO) ============ */
.user-cell, .product-cell { 
    display: flex; 
    align-items: center; 
    gap: 12px; 
    min-width: 200px; 
}

.user-avatar, .product-img {
    width: 40px; 
    height: 40px; 
    border-radius: 10px; 
    object-fit: cover;
    border: 2px solid var(--card-border); 
    transition: 0.3s;
    background: var(--input-fill);
}

tr.main-row:hover .user-avatar, 
tr.main-row:hover .product-img { 
    transform: scale(1.1); 
    border-color: var(--accent); 
}

.user-name, .product-name { 
    display: block; 
    font-size: 14px; 
    font-weight: 600; 
    color: var(--text-main); 
}

.user-doc, .product-code { 
    color: var(--text-dim); 
    font-size: 11px; 
}

/* ============ BADGES DE STATUS ============ */
.badge-status, .detail-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
    white-space: nowrap;
}

.badge-active, .status-Ativo { background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); }
.badge-inactive, .status-Inativo { background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3); }
.status-Esgotado, .badge-warning { background: rgba(243, 156, 18, 0.15); color: #f39c12; border: 1px solid rgba(243, 156, 18, 0.3); }

/* ============ BOTÕES DE AÇÃO ============ */
.actions-group { display: flex; justify-content: center; gap: 6px; }
.btn-action {
    width: 32px; height: 32px; border-radius: 8px;
    border: 1.5px solid var(--card-border);
    background: var(--input-fill); color: var(--text-dim);
    cursor: pointer; transition: all 0.25s ease;
    display: flex; align-items: center; justify-content: center; font-size: 13px;
}

.btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); }
.btn-view:hover { color: #3498db; border-color: #3498db; }
.btn-edit:hover { color: var(--accent); border-color: var(--accent); }
.btn-del:hover { color: var(--danger); border-color: var(--danger); }

/* ============ MODAL SYSTEM ============ */
.modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    z-index: 9999;
    display: none;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.modal-overlay.active { display: flex; }

.modal-content {
    width: 100%;
    max-width: 850px;
    max-height: 90vh;
    overflow-y: auto;
    background: var(--card-bg);
    border-radius: 20px;
    border: 1px solid var(--card-border);
    padding: 25px;
    position: relative;
    animation: slideUp 0.3s ease;
}

.close-modal {
    position: absolute; top: 15px; right: 15px;
    color: var(--text-dim); cursor: pointer; font-size: 24px;
    border: none; background: transparent; transition: 0.3s;
}
.close-modal:hover { color: var(--accent); transform: rotate(90deg); }

/* ============ FORMULÁRIOS INTERNOS ============ */
.section-title {
    font-size: 11px; color: var(--accent);
    text-transform: uppercase; letter-spacing: 1px;
    margin: 20px 0 12px; padding-bottom: 8px;
    border-bottom: 2px solid var(--card-border);
    display: flex; align-items: center; gap: 8px; font-weight: 700;
}

.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 200px), 1fr));
    gap: 14px;
}

.input-group { display: flex; flex-direction: column; gap: 6px; }
.input-group label { font-size: 11px; color: var(--text-dim); font-weight: 600; text-transform: uppercase; }
.input-group input, .input-group select, .input-group textarea {
    background: var(--input-fill); border: 2px solid var(--card-border);
    padding: 10px 12px; border-radius: 8px; color: var(--text-main);
    font-size: 13px; transition: 0.3s; width: 100%; box-sizing: border-box;
}

.input-group input:focus { border-color: var(--accent); outline: none; }

.input-money { position: relative; }
.input-money::before { 
    content: 'Kz'; position: absolute; left: 12px; top: 32px; 
    color: var(--text-dim); font-size: 12px; font-weight: 600; 
}
.input-money input { padding-left: 35px; }

.btn-save {
    width: 100%; padding: 14px; background: #e74c3c; color: white;
    border: none; border-radius: 12px; font-weight: 700; cursor: pointer;
    margin-top: 25px; transition: 0.3s; text-transform: uppercase;
}

.btn-save:hover { background: transparent; color: var(--accent); border: 1px solid var(--accent); }

.btn-cancel {
    width: 100%; padding: 14px; background: var(--accent); color: #000;
    border: none; border-radius: 12px; font-weight: 700; cursor: pointer;
    margin-top: 25px; transition: 0.3s; text-transform: uppercase;
}

.btn-cancel:hover { background: transparent; color: var(--accent); border: 1px solid var(--accent); }

/* ============ RESPONSIVIDADE ============ */
.col-pc { display: none; }
@media (min-width: 360px) { .col-pc { display: table-cell; } }

@media (max-width: 767px) {
    .search-box { min-width: 100%; max-width: 100%; }
    .header-actions { width: 100%; justify-content: space-between; }
    .btn-new { flex: 1; justify-content: center; }
    th, td { padding: 10px 8px; }
}

/* ============ LINHA EXPANSÍVEL ============ */
.expand-row { display: none; background: rgba(52, 152, 219, 0.03); border-left: 3px solid #3498db; animation: expandIn 0.3s ease; }
.expand-row.show { display: table-row; }
.expand-row td { padding: 16px 12px; }
.detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; }
.detail-item { display: flex; flex-direction: column; gap: 4px; padding: 10px; background: var(--card-bg); border-radius: 8px; border: 1px solid var(--card-border); }
.detail-label { font-size: 10px; text-transform: uppercase; color: #3498db; font-weight: 700; display: flex; align-items: center; gap: 6px; }
.detail-value { font-size: 13px; color: var(--text-main); }

.detail-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); 
    gap: 15px; 
    padding: 20px;
}

.detail-item { 
    display: flex; 
    flex-direction: column; 
    gap: 5px; 
    padding: 12px; 
    background: var(--input-fill); 
    border-radius: 10px; 
    border: 1px solid var(--card-border); 
}

.detail-label { 
    font-size: 10px; 
    text-transform: uppercase; 
    color: var(--accent); 
    font-weight: 700; 
    display: flex; 
    align-items: center; 
    gap: 6px; 
}

.detail-value { 
    font-size: 13px; 
    color: var(--text-main); 
    font-weight: 500;
}

@keyframes expandIn { 
    from { opacity: 0; transform: translateY(-10px); } 
    to { opacity: 1; transform: translateY(0); } 
}

/* ============ ESTADO VAZIO E TOAST ============ */
.empty-state { text-align: center; padding: 40px 20px; color: var(--text-dim); }

.toast-container { position: fixed; top: 20px; right: 20px; z-index: 99999; display: flex; flex-direction: column; gap: 10px; }
.toast-item {
    padding: 14px 20px; border-radius: 10px; color: #fff; font-size: 14px; font-weight: 600;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3); animation: slideInToast 0.3s ease;
    display: flex; align-items: center; gap: 10px; min-width: 280px; max-width: 400px;
    backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1);
}
.toast-success { background: rgba(46,204,113,0.9); }
.toast-error { background: rgba(231,76,60,0.9); }

/* ============ ANIMAÇÕES ============ */
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
@keyframes expandIn { from { opacity: 0; max-height: 0; } to { opacity: 1; max-height: 1000px; } }
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
@keyframes slideInToast { from { transform: translateX(100px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

/* ANIMAÇÕES */
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

.empty-state { text-align: center; padding: 40px; color: var(--text-dim); }
.empty-state i { font-size: 40px; margin-bottom: 10px; display: block; }
</style>

<div class="rh-container">
    <div class="top-bar">
        <div class="header-info">
            <h2><i class="fas fa-boxes"></i> Controle de Estoque</h2>
            <p>Gestão de Estoque • Validades • Logística Farmacêutica</p>
        </div>
        <div class="header-actions">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="inputPesquisa" onkeyup="window.pesquisarProduto()" placeholder="Pesquisar por nome, código, categoria...">
            </div>
            <button class="btn-new" onclick="window.abrirModalCadastro()">
                <i class="fas fa-plus-circle"></i> NOVO PRODUTO
            </button>
        </div>
    </div>

    <div class="table-glass">
        <table id="tabelaProdutos">
            <thead>
                <tr>
                    <th>Produto / Código</th>
                    <th class="col-pc">Categoria</th>
                    <th class="col-pc">Preço (Venda)</th>
                    <th class="col-pc">Estoque</th>
                    <th class="col-tel">Status</th>
                    <th style="text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody id="tableBody">
               <?php if($resultado && $resultado->num_rows > 0): ?>
    <?php while($prod = $resultado->fetch_assoc()): ?>
    <tr class="main-row" id="row-<?php echo $prod['id_produto']; ?>">
        <td>
            <div class="user-cell">
                <img class="user-avatar" 
                     src="<?php echo $base_url; ?>uploads/produtos/<?php echo htmlspecialchars($prod['foto_produto'] ?? 'produto_default.png'); ?>" 
                     onerror="this.src='<?php echo $base_url; ?>uploads/produto_default.png'"
                     alt="Produto"
                     loading="lazy">
                <div>
                    <span class="user-name"><?php echo htmlspecialchars($prod['nome_produto']); ?></span>
                    <span class="user-doc">REF: <?php echo htmlspecialchars($prod['codigo_barra'] ?? $prod['id_produto']); ?></span>
                </div>
            </div>
        </td>
        
        <td class="col-pc"><?php echo htmlspecialchars($prod['categoria']); ?></td>
        
        <!-- Coluna de Preço com Lógica de Promoção -->
        <td class="col-pc">
            <?php if ($prod['preco_promocional'] > 0 && $prod['preco_promocional'] < $prod['preco_venda_caixa']): ?>
                <div style="display: flex; flex-direction: column;">
                    <span style="text-decoration: line-through; color: var(--text-dim); font-size: 12px;">
                        Kz <?php echo number_format($prod['preco_venda_caixa'], 2, ',', '.'); ?>
                    </span>
                    <span style="font-weight: 700; color: #e67e22;" title="Em Promoção">
                        <i class="fas fa-star" style="font-size: 10px;"></i> Kz <?php echo number_format($prod['preco_promocional'], 2, ',', '.'); ?>
                    </span>
                </div>
            <?php else: ?>
                <span style="font-weight: 600; color: var(--accent);">
                    Kz <?php echo number_format($prod['preco_venda_caixa'], 2, ',', '.'); ?>
                </span>
            <?php endif; ?>
        </td>

        <td class="col-pc">
            <span style="font-weight:700;"><?php echo $prod['estoque_atual_caixas']; ?></span> 
            <small style="color:var(--text-dim)">cx</small>
        </td>

        <td class="col-tel">
            <?php 
                $critico = ($prod['estoque_atual_caixas'] <= $prod['estoque_minimo_caixas']);
                $statusClass = $critico ? 'badge-inactive' : 'badge-active';
                $statusText = $critico ? 'Crítico' : 'Normal';
            ?>
            <span class="detail-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
        </td>

        <td>
            <div class="actions-group">
                <!-- Botão Ver Detalhes -->
                <button class="btn-action btn-view" data-tooltip="Ver Detalhes"
                        onclick="window.verDetalhesProduto(<?php echo htmlspecialchars(json_encode($prod)); ?>, this)">
                    <i class="fas fa-eye"></i>
                </button>
                
                <!-- Botão Registrar Perda (Ajustado) -->
                <button class="btn-action" data-tooltip="Registrar Perda" style="color: #e74c3c;"
                        onclick="window.abrirModalPerda(
                            <?php echo $prod['id_produto']; ?>, 
                            '<?php echo htmlspecialchars(addslashes($prod['nome_produto'])); ?>', 
                            <?php echo (int)$prod['estoque_atual_caixas']; ?>, 
                            <?php echo (float)$prod['preco_compra']; ?>
                        )">
                    <i class="fas fa-heart-broken"></i>
                </button>

                <!-- Botão Editar -->
                <button class="btn-action btn-edit" data-tooltip="Editar"
                        onclick="window.editarProduto(<?php echo htmlspecialchars(json_encode($prod)); ?>)">
                    <i class="fas fa-edit"></i>
                </button>

                <!-- Botão Eliminar -->
                <button class="btn-action btn-del" data-tooltip="Eliminar"
                        onclick="window.eliminarProduto(<?php echo $prod['id_produto']; ?>, '<?php echo htmlspecialchars(addslashes($prod['nome_produto'])); ?>')">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </td>
    </tr>
    <?php endwhile; ?>

    <?php else: ?>
        <!-- Este bloco só aparece se NÃO houver produtos -->
        <tr>
            <td colspan="6">
                <div class="empty-state">
                    <i class="fas fa-boxes"></i>
                    <p><strong>Nenhum produto encontrado</strong></p>
                    <p style="font-size: 13px;">Clique em "NOVO PRODUTO" para adicionar ao inventário</p>
                </div>
            </td>
        </tr>
    <?php endif; ?> <!-- Único endif necessário para fechar o if da linha 27 -->
</tbody>
        </table>
    </div>
</div>

<!-- MODAL CADASTRO / EDIÇÃO -->
<div class="modal-overlay" id="modalCadastro" onclick="window.fecharModal(event, 'modalCadastro')">
    <div class="modal-content" onclick="event.stopPropagation()">
        <button class="close-modal" onclick="window.fecharModalForcado('modalCadastro')">&times;</button>
        <h2 id="modalTitle" style="color: var(--accent); margin-top:0; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-box"></i> Cadastro de Produto
        </h2>
        
        <form id="formCadastro" onsubmit="window.salvarProduto(event)" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="id_produto" id="id_produto">
            <input type="hidden" name="acao" value="salvar">
            
            <div class="section-title"><i class="fas fa-info-circle"></i> Informação Básica</div>
            <div class="grid">
                <div class="input-group"><label>Código de Barras *</label><input type="text" name="codigo_barra" id="codigo_barra" required></div>
                <div class="input-group"><label>Nome do Produto *</label><input type="text" name="nome_produto" id="nome_produto" required></div>
                <div class="input-group"><label>Princípio Ativo</label><input type="text" name="principio_ativo" id="principio_ativo"></div>
                <div class="input-group">
                    <label>Categoria</label>
                    <select name="categoria" id="categoria">
                        <option value="Medicamento">Medicamento</option>
                        <option value="Higiene e Beleza">Higiene e Beleza</option>
                        <option value="Infantil">Infantil</option>
                        <option value="Suplemento">Suplemento / Vitamina</option>
                        <option value="Material Médico">Material Médico</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>Apresentação</label>
                    <select name="tipo_apresentacao" id="tipo_apresentacao">
                        <option value="Caixa">Caixa</option>
                        <option value="Frasco">Frasco</option>
                        <option value="Pacote">Pacote</option>
                        <option value="Bisnaga">Bisnaga</option>
                        <option value="Unidade">Unidade</option>
                    </select>
                </div>
                <div class="input-group"><label>Dosagem</label><input type="text" name="dosagem_peso" id="dosagem_peso"></div>
            </div>

            <div class="section-title"><i class="fas fa-dollar-sign"></i> Preços e Venda a Retalho</div>
            <div class="grid">
                <div class="input-group input-money"><label>Preço de Compra *</label><input type="number" step="0.01" name="preco_compra" id="preco_compra" required oninput="window.calcularMargem()"></div>
                
                <!-- AJUSTE: oninput adicionado ao IVA e novo campo Custo c/ IVA -->
                <div class="input-group"><label>Taxa de IVA (%)</label><input type="number" step="0.01" name="taxa_iva" id="taxa_iva" value="0.00" oninput="window.calcularMargem()"></div>
                <div class="input-group"><label>Custo c/ IVA</label><input type="text" id="custo_com_iva" readonly style="background: rgba(0,0,0,0.02); font-weight: bold; border-style: dashed;"></div>
                
                <div class="input-group input-money"><label>Preço Venda (Caixa) *</label><input type="number" step="0.01" name="preco_venda_caixa" id="preco_venda_caixa" required oninput="window.calcularMargem()"></div>
                <div class="input-group"><label>Preço Promocional</label><input type="number" step="0.01" name="preco_promocional" id="preco_promocional" oninput="window.calcularMargem()"></div>
                <div class="input-group"><label>Margem Lucro (%)</label><input type="text" id="margem_lucro" readonly style="background: rgba(0,0,0,0.02); font-weight: bold; border-style: dashed;"></div>
                
                <div class="input-group">
                    <label style="color: #3498db;">Permite Retalho?</label>
                    <select name="permite_retalho" id="permite_retalho" onchange="window.toggleRetalhoFields()">
                        <option value="0">Não</option>
                        <option value="1">Sim</option>
                    </select>
                </div>
                <div class="input-group"><label>Unidades</label><input type="number" name="unidades_por_caixa" id="unidades_por_caixa" value="1" min="1"></div>
                <div class="input-group"><label>Preço Por Unidade</label><input type="number" step="0.01" name="preco_venda_unidade" id="preco_venda_unidade"></div>
            </div>

            <!-- Seção de Estoque Ajustada -->
			<div class="section-title"><i class="fas fa-boxes"></i> Estoque e Validade</div>
			<div class="grid">
				<div class="input-group">
					<label>Estoque Atual (em Caixas)</label>
					<!-- step="0.001" permite frações como 0.500 -->
					<input type="number" step="0.001" name="estoque_atual_caixas" id="estoque_atual_caixas" value="0.000">
				</div>
				<div class="input-group">
					<label>Estoque Mínimo</label>
					<input type="number" step="0.001" name="estoque_minimo_caixas" id="estoque_minimo_caixas" value="5.000">
				</div>
				<div class="input-group"><label>Lote</label><input type="text" name="lote" id="lote"></div>
				<div class="input-group"><label>Data Validade</label><input type="date" name="data_validade" id="data_validade"></div>
			</div>

            <div class="section-title"><i class="fas fa-truck-loading"></i> Logística e Saúde</div>
            <div class="grid">
                <div class="input-group"><label>Fornecedor</label><input type="text" name="fornecedor" id="fornecedor"></div>
                <div class="input-group"><label>Corredor/Prateleira</label><input type="text" name="localizacao_corredor" id="localizacao_corredor"></div>
                <div class="input-group">
                    <label>Exige Receita?</label>
                    <select name="requer_receita" id="requer_receita">
                        <option value="0">Não</option>
                        <option value="1">Sim</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>Refrigerado?</label>
                    <select name="refrigerado" id="refrigerado">
                        <option value="0">Não</option>
                        <option value="1">Sim</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>Status</label>
                    <select name="status_item" id="status_item">
                        <option value="Ativo">Ativo</option>
                        <option value="Inativo">Inativo</option>
                        <option value="Esgotado">Esgotado</option>
						<option value="Vencido">Vencido</option>
                    </select>
                </div>
                <div class="input-group"><label>Foto</label><input type="file" name="foto_produto" id="foto_produto" accept="image/*"></div>
            </div>
            
            <button type="submit" class="btn-save" id="btnSubmit">
                <i class="fas fa-save"></i> SALVAR NO ESTOQUE
            </button>
        </form>
    </div>
</div>

<!-- MODAL DE VISUALIZAÇÃO DETALHADA -->
<div class="modal-overlay" id="modalVisao" onclick="window.fecharModal(event, 'modalVisao')">
    <div class="modal-content" style="max-width: 600px;">
        <button class="close-modal" onclick="window.fecharModalForcado('modalVisao')">&times;</button>
        <h2 style="color: var(--accent);"><i class="fas fa-search-plus"></i> Detalhes do Produto</h2>
        <div id="conteudoVisao" style="margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <!-- Preenchido via JS -->
        </div>
    </div>
</div>

<!-- MODAL DE PERDAS / QUEBRAS - VERSÃO PROFISSIONAL -->
<div class="modal-overlay" id="modalPerda" onclick="window.fecharModal(event, 'modalPerda')">
    <div class="modal-content" style="max-width: 500px;" onclick="event.stopPropagation()">
        <button class="close-modal" onclick="window.fecharModalForcado('modalPerda')">&times;</button>
        
        <h2 style="color: #e74c3c; margin-top:0; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-trash-alt"></i> Registrar Saída por Perda
        </h2>
        
        <div style="background: rgba(231, 76, 60, 0.05); padding: 15px; border-left: 4px solid #e74c3c; border-radius: 4px; margin-bottom: 20px;">
            <span id="perdaProdutoNome" style="font-weight: 700; color: #c0392b; font-size: 16px; display: block;"></span>
            <small style="color: #666;">Estoque Atual: <strong id="perdaEstoqueAtual"></strong> unidades/cx</small>
        </div>
        
        <form id="formPerda" onsubmit="window.salvarPerda(event)">
            <input type="hidden" name="acao" value="registrar_perda">
            <input type="hidden" name="id_produto_perda" id="id_produto_perda">
            
            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="input-group">
                    <label style="font-weight: 600;">Quantidade Perdida *</label>
                    <input type="number" name="qtd_perda" id="qtd_perda" min="1" required 
                           style="border: 1px solid #dcdfe6; padding: 10px; border-radius: 6px;"
                           oninput="window.calcularPrejuizo()">
                </div>
                <div class="input-group">
                    <label style="font-weight: 600;">Motivo da Perda *</label>
                    <select name="motivo_perda" id="motivo_perda" required style="border: 1px solid #dcdfe6; padding: 10px; border-radius: 6px;">
                        <option value="" disabled selected>Selecione...</option>
                        <option value="Validade Vencida">Validade Vencida</option>
                        <option value="Quebra/Avaria">Quebra / Avaria</option>
                        <option value="Furto/Roubo">Furto ou Roubo</option>
                        <option value="Uso Interno">Uso Interno</option>
                        <option value="Erro de Contagem">Erro de Contagem</option>
                    </select>
                </div>
            </div>

            <div class="input-group" style="margin-top: 15px;">
                <label style="font-weight: 600;">Observações / Relato Detalhado *</label>
                <textarea name="observacao_perda" id="observacao_perda" 
                          placeholder="Ex: Lote danificado durante o transporte interno ou vencimento verificado no balanço..." 
                          style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #dcdfe6; min-height: 90px; font-family: inherit; resize: none;" 
                          required></textarea>
            </div>

            <!-- Box de Prejuízo em Tempo Real -->
            <div id="infoPrejuizo" style="margin-top: 20px; padding: 12px; background: #fdf2f2; border-radius: 6px; border: 1px dashed #e74c3c; display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #c0392b; font-size: 13px;">Prejuízo estimado (Custo):</span>
                    <strong style="color: #e74c3c; font-size: 15px;">Kz <span id="valorPrejuizo">0,00</span></strong>
                </div>
            </div>
            
            <div style="margin-top: 25px; display: flex; gap: 12px;">
                <button type="button" class="btn-cancel" onclick="window.fecharModalForcado('modalPerda')" 
                        style="flex: 2; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    CANCELAR
                </button>
                <button type="submit" class="btn-save" id="btnSubmitPerda" 
                        style="flex: 2; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-check-circle"></i> CONFIRMAR REGISTRO
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputCodigo = document.getElementById('codigo_barra');

    if (!inputCodigo) {
        console.error("Erro: O campo 'codigo_barra' não existe no HTML.");
        return;
    }

    inputCodigo.addEventListener('blur', function() {
        const codigo = this.value.trim();
        if (codigo.length < 3) return;

        // O 'fetch' sem o 'modules/'
        fetch('get_produto.php?codigo=' + codigo)
            .then(response => {
                if (!response.ok) {
                    alert("Erro: O arquivo get_produto.php não foi encontrado na pasta!");
                    throw new Error('404');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    const p = data.dados;
                    
                    // Preenchimento manual campo a campo para garantir
                    document.getElementById('nome_produto').value = p.nome_produto || '';
                    document.getElementById('principio_ativo').value = p.principio_ativo || '';
                    document.getElementById('categoria').value = p.categoria || 'Medicamento';
                    document.getElementById('tipo_apresentacao').value = p.tipo_apresentacao || 'Caixa';
                    document.getElementById('preco_compra').value = p.preco_compra || '';
                    document.getElementById('preco_venda_caixa').value = p.preco_venda_caixa || '';
                    
                    if (typeof window.calcularMargem === 'function') {
                        window.calcularMargem();
                    }
                    alert("Produto encontrado e preenchido!");
                } else if (data.status === 'not_found') {
                    console.log("Este código ainda não existe no banco.");
                }
            })
            .catch(err => {
                alert("Erro na requisição. Verifique o Console (F12).");
                console.error(err);
            });
    });
});
</script>

<script>

// ================= CONFIGURAÇÃO =================
const BASE_URL = "/pharmora/modules/";

const DOM_PROD = {
    form: () => document.getElementById('formCadastro'),
    modal: () => document.getElementById('modalCadastro'),
    tableBody: () => document.getElementById('tableBody'),
    searchInput: () => document.getElementById('inputPesquisa')
};


// ================= UTILITÁRIOS =================
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

function safeJsonParse(text) {
    try { return JSON.parse(text); } 
    catch (e) { 
        console.error('JSON inválido:', text);
        return null;
    }
}

// ================= INICIALIZAÇÃO =================
document.addEventListener('DOMContentLoaded', () => {
    // Atalho: Enter no Código de Barras
    const inputBarra = document.getElementById('codigo_barra');
    if(inputBarra) {
        inputBarra.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('nome_produto').focus();
            }
        });
    }

    // Escuta automática para cálculos de margem
    const priceFields = ['preco_compra', 'taxa_iva', 'preco_venda_caixa', 'preco_promocional'];
    priceFields.forEach(id => {
        const el = document.getElementById(id);
        if(el) el.addEventListener('input', window.calcularMargem);
    });
});

// ================= MODAIS =================
window.abrirModalCadastro = function() {
    const form = DOM_PROD.form();
    if(form) form.reset();
    
    document.getElementById('id_produto').value = "";
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-box"></i> Novo Produto';
    
    window.toggleRetalhoFields();
    DOM_PROD.modal().classList.add('active');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('codigo_barra').focus(), 100);
};

window.fecharModal = function(e, id) {
    if(e.target === document.getElementById(id)) window.fecharModalForcado(id);
};

window.fecharModalForcado = function(id) {
    const modal = document.getElementById(id);
    if(modal) modal.classList.remove('active');
    document.body.style.overflow = '';
};

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && DOM_PROD.modal().classList.contains('active')) {
        window.fecharModalForcado('modalCadastro');
    }
});

// ================= TOAST =================
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

// ================= LÓGICA DE NEGÓCIO =================
window.toggleRetalhoFields = function() {
    const permite = document.getElementById('permite_retalho').value === "1";
    const campos = ['unidades_por_caixa', 'preco_venda_unidade'];
    
    campos.forEach(id => {
        const el = document.getElementById(id);
        if(el) {
            el.disabled = !permite;
            el.parentElement.style.opacity = permite ? "1" : "0.4";
            if(!permite && id === 'unidades_por_caixa') el.value = 1;
        }
    });
};

window.calcularMargem = function() {
    const precoCompra = parseFloat(document.getElementById('preco_compra').value) || 0;
    const taxaIva = parseFloat(document.getElementById('taxa_iva').value) || 0;
    const precoVenda = parseFloat(document.getElementById('preco_venda_caixa').value) || 0;
    const precoPromo = parseFloat(document.getElementById('preco_promocional').value) || 0;
    
    const displayMargem = document.getElementById('margem_lucro');
    const displayCustoIva = document.getElementById('custo_com_iva');

    // 1. Calcula Custo Real com IVA
    const custoReal = precoCompra * (1 + (taxaIva / 100));
    if(displayCustoIva) displayCustoIva.value = custoReal > 0 ? "Kz " + custoReal.toFixed(2) : "";

    if(!displayMargem) return;

    // 2. Define qual preço usar para a margem (Promoção substitui Base se for válido)
    let vendaEfetiva = (precoPromo > 0 && precoPromo < precoVenda) ? precoPromo : precoVenda;

    // 3. Calcula Margem
    if (custoReal > 0 && vendaEfetiva > 0) {
        const margem = ((vendaEfetiva - custoReal) / custoReal) * 100;
        displayMargem.value = margem.toFixed(2) + "%";
        
        displayMargem.style.color = margem <= 0 ? "#ff4d4d" : (margem < 15 ? "#f1c40f" : "#2ecc71");
        displayMargem.style.borderColor = displayMargem.style.color;
    } else {
        displayMargem.value = "0.00%";
        displayMargem.style.color = "";
        displayMargem.style.borderColor = "";
    }
};

// ================= (SALVAR E EDITAR) =================
window.salvarProduto = async function(e) {
    if(e) e.preventDefault();
    
    const btn = document.getElementById('btnSubmit');
    const form = DOM_PROD.form();
    const formData = new FormData(form);

    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-sync fa-spin"></i> PROCESSANDO...';

    try {
        const response = await fetch(BASE_URL + 'api_produtos.php', { method: 'POST', body: formData });
        const text = await response.text();
        const res = safeJsonParse(text);

        if(res && res.status === 'success') {
            showToast('Produto salvo com sucesso!', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(res?.message || 'Erro ao processar produto', 'error');
        }
    } catch (error) {
        showToast('Erro de conexão com o servidor', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
};

window.editarProduto = function(p) {
    window.abrirModalCadastro();
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Editar: ' + p.nome_produto;
    
    const campos = [
        'id_produto', 'codigo_barra', 'nome_produto', 'principio_ativo', 
        'categoria', 'tipo_apresentacao', 'dosagem_peso', 'preco_compra', 
        'preco_venda_caixa', 'taxa_iva', 'permite_retalho', 'unidades_por_caixa', 
        'preco_venda_unidade', 'preco_promocional', 'estoque_atual_caixas', 
        'estoque_minimo_caixas', 'lote', 'data_validade', 'fornecedor', 
        'localizacao_corredor', 'requer_receita', 'refrigerado', 'status_item'
    ];

    campos.forEach(campo => {
        const el = document.getElementById(campo);
        if(el) {
            let valor = p[campo];
            
            // Tratamento especial para campos de estoque manterem o decimal ao carregar
            if (campo === 'estoque_atual_caixas' || campo === 'estoque_minimo_caixas') {
                el.value = valor !== null ? parseFloat(valor).toFixed(3) : "0.000";
            } else {
                el.value = valor !== null ? valor : (campo === 'status_item' ? 'Ativo' : '');
            }
        }
    });

    window.toggleRetalhoFields();
    window.calcularMargem();
};

// ================= ELIMINAR =================
window.eliminarProduto = async function(id, nome) {
    if(!confirm(`Excluir "${nome}" permanentemente do estoque?`)) return;

    const fd = new FormData();
    fd.append('acao', 'eliminar');
    fd.append('id_produto', id);

    try {
        const response = await fetch(BASE_URL + 'api_produtos.php', { method: 'POST', body: fd });
        const text = await response.text();
        const res = safeJsonParse(text);

        if(res && res.status === 'success') {
            const row = document.getElementById('row-' + id);
            if(row) {
                row.style.opacity = '0';
                row.style.transform = 'translateX(20px)';
                row.style.transition = 'all 0.4s ease';
                setTimeout(() => row.remove(), 400);
            }
            showToast('Produto removido do estoque');
        } else {
            showToast(res?.message || 'Erro ao eliminar', 'error');
        }
    } catch(err) {
        showToast('Erro de comunicação', 'error');
    }
};

// ================= BUSCA E DETALHES =================
window.pesquisarProduto = debounce(function() {
    const q = DOM_PROD.searchInput().value.toLowerCase().trim();
    const rows = document.querySelectorAll("#tableBody .main-row");
    
    rows.forEach(r => {
        const match = !q || r.innerText.toLowerCase().includes(q);
        r.style.display = match ? "" : "none";
        
        const expandRow = document.getElementById('expand-' + r.id.split('-')[1]);
        if(expandRow) expandRow.classList.remove('show');
    });
}, 300);

window.verDetalhesProduto = function(p, btnElement) {
    if (!p) return;
    
    const rowId = 'row-' + p.id_produto;
    const expandId = 'expand-' + p.id_produto;
    const mainRow = document.getElementById(rowId);
    let expandRow = document.getElementById(expandId);
    const icon = btnElement ? btnElement.querySelector('i') : null;

    // Lógica de Toggle (Abre/Fecha)
    if (expandRow) {
        if (expandRow.classList.contains('show')) {
            expandRow.classList.remove('show');
            if (icon) icon.className = 'fas fa-eye';
            btnElement.style.color = "var(--text-dim)";
        } else {
            expandRow.classList.add('show');
            if (icon) icon.className = 'fas fa-eye-slash';
            btnElement.style.color = "var(--accent)";
            expandRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        return;
    }

    // Criação da linha de detalhes
    expandRow = document.createElement('tr');
    expandRow.id = expandId;
    expandRow.className = 'expand-row show';

    // Funções auxiliares de formatação
    const simNao = (val) => parseInt(val) === 1 
        ? `<span style="color:#2ecc71; font-weight:600;"><i class="fas fa-check-circle"></i> Sim</span>` 
        : `<span style="color:#e74c3c; font-weight:600;"><i class="fas fa-times-circle"></i> Não</span>`;

    const formatMoeda = (val) => `Kz ${parseFloat(val || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;

    // Template COMPLETO com todos os atributos da tabela 'produtos'
    expandRow.innerHTML = `
        <td colspan="100%">
            <div class="detail-grid">
                
                <!-- GRUPO 1: ESPECIFICAÇÕES DO PRODUTO -->
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-barcode"></i> Código de Barras</span>
                    <span class="detail-value">${p.codigo_barra || '---'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-flask"></i> Princípio Ativo</span>
                    <span class="detail-value">${p.principio_ativo || 'N/A'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-layer-group"></i> Apresentação</span>
                    <span class="detail-value">${p.tipo_apresentacao || 'N/A'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-weight-hanging"></i> Dosagem / Peso</span>
                    <span class="detail-value">${p.dosagem_peso || 'N/A'}</span>
                </div>

                <!-- GRUPO 2: VALIDADE E CONTROLE -->
                <div class="detail-item" style="border-left: 3px solid #e74c3c;">
                    <span class="detail-label" style="color:#e74c3c;"><i class="fas fa-calendar-times"></i> Data de Validade</span>
                    <span class="detail-value" style="font-weight:700;">${p.data_validade ? p.data_validade.split('-').reverse().join('/') : '---'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-box-open"></i> Lote</span>
                    <span class="detail-value">${p.lote || 'N/A'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-tag"></i> Categoria</span>
                    <span class="detail-value">${p.categoria || 'N/A'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-info-circle"></i> Status no Sistema</span>
                    <span class="detail-value"><strong>${p.status_item}</strong></span>
                </div>

                <!-- GRUPO 3: FINANCEIRO E IMPOSTOS -->
                <div class="detail-item" style="background: rgba(46, 204, 113, 0.05);">
                    <span class="detail-label"><i class="fas fa-shopping-cart"></i> Preço Compra</span>
                    <span class="detail-value">${formatMoeda(p.preco_compra)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-percentage"></i> Taxa IVA</span>
                    <span class="detail-value">${p.taxa_iva}%</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-hand-holding-usd"></i> Venda (Caixa)</span>
                    <span class="detail-value">${formatMoeda(p.preco_venda_caixa)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-star"></i> Promoção</span>
                    <span class="detail-value">${p.preco_promocional > 0 ? formatMoeda(p.preco_promocional) : 'N/A'}</span>
                </div>

                <!-- GRUPO 4: ESTOQUE E RETALHO -->
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-boxes"></i> Estoque Mínimo</span>
                    <span class="detail-value">${p.estoque_minimo_caixas} un.</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-cut"></i> Permite Retalho</span>
                    <span class="detail-value">${simNao(p.permite_retalho)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-divide"></i> Unidades por Caixa</span>
                    <span class="detail-value">${p.unidades_por_caixa}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-coins"></i> Preço/Unidade</span>
                    <span class="detail-value">${p.preco_venda_unidade > 0 ? formatMoeda(p.preco_venda_unidade) : 'N/A'}</span>
                </div>

                <!-- GRUPO 5: LOGÍSTICA E SAÚDE -->
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-truck"></i> Fornecedor</span>
                    <span class="detail-value">${p.fornecedor || 'Não definido'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-map-marker-alt"></i> Localização/Corredor</span>
                    <span class="detail-value">${p.localizacao_corredor || 'N/A'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-snowflake"></i> Refrigerado</span>
                    <span class="detail-value">${simNao(p.refrigerado)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-notes-medical"></i> Requer Receita</span>
                    <span class="detail-value">${simNao(p.requer_receita)}</span>
                </div>
            </div>
            
            <div style="padding: 10px 20px; font-size: 11px; color: var(--text-dim); border-top: 1px solid var(--card-border);">
                <i class="fas fa-history"></i> Cadastrado em: ${new Date(p.data_cadastro).toLocaleString()} | 
                Última atualização: ${new Date(p.ultima_atualizacao).toLocaleString()}
            </div>
        </td>
    `;

    mainRow.after(expandRow);
    if (icon) icon.className = 'fas fa-eye-slash';
    btnElement.style.color = "var(--accent)";
    
    setTimeout(() => {
        expandRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 50);
};

// Variável global para o cálculo de prejuízo em tempo real
let precoCustoUnitarioAtual = 0;

window.abrirModalPerda = function(id, nome, estoqueAtual, precoCusto) {
    // 1. Resetar formulário e estados visuais
    const form = document.getElementById('formPerda');
    if (form) form.reset();
    
    document.getElementById('infoPrejuizo').style.display = 'none';

    // 2. Preencher campos identificadores
    document.getElementById('id_produto_perda').value = id;
    document.getElementById('perdaProdutoNome').innerText = nome;
    document.getElementById('perdaEstoqueAtual').innerText = estoqueAtual;
    
    // 3. Configurar limites de segurança e valores financeiros
    document.getElementById('qtd_perda').max = estoqueAtual; 
    precoCustoUnitarioAtual = parseFloat(precoCusto) || 0;

    // 4. Mostrar o modal
    const modal = document.getElementById('modalPerda');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden'; // Impede scroll ao fundo
};

/**
 * Fecha o modal de perda
 */
window.fecharModalPerda = function() {
    document.getElementById('modalPerda').classList.remove('active');
    document.body.style.overflow = 'auto';
};

/**
 * Calcula o prejuízo financeiro em tempo real conforme o usuário digita
 */
window.calcularPrejuizo = function() {
    const inputQtd = document.getElementById('qtd_perda');
    const qtd = parseFloat(inputQtd.value) || 0;
    const infoBox = document.getElementById('infoPrejuizo');
    const displayValor = document.getElementById('valorPrejuizo');

    if (qtd > 0) {
        const totalPrejuizo = qtd * precoCustoUnitarioAtual;
        displayValor.innerText = totalPrejuizo.toLocaleString('pt-BR', { 
            style: 'currency', 
            currency: 'AOA' // Ajuste para sua moeda (AOA ou BRL)
        });
        infoBox.style.display = 'block';
    } else {
        infoBox.style.display = 'none';
    }
};

/**
 * Envia os dados validados para api_produtos.php
 */
window.salvarPerda = async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('btnSubmitPerda');
    const form = e.target;
    const formData = new FormData(form);
    
    // Validação de segurança: Quantidade informada vs Estoque disponível
    const qtdInformada = parseFloat(formData.get('qtd_perda'));
    const qtdMax = parseFloat(document.getElementById('qtd_perda').max);

    if (isNaN(qtdInformada) || qtdInformada <= 0) {
        showToast('Informe uma quantidade válida!', 'error');
        return;
    }

    if (qtdInformada > qtdMax) {
        showToast(`A perda (${qtdInformada}) não pode ser maior que o estoque (${qtdMax})!`, 'error');
        return;
    }

    // Adiciona os dados financeiros que o SQL exige e que não estão no form original
    const valorPrejuizoTotal = (qtdInformada * precoCustoUnitarioAtual).toFixed(2);
    formData.append('preco_custo_unidade', precoCustoUnitarioAtual.toFixed(2));
    formData.append('valor_prejuizo_total', valorPrejuizoTotal);
    formData.append('acao', 'registrar_perda'); // Garante que a API saiba qual ação tomar

    // Feedback visual de carregamento
    btn.disabled = true;
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> REGISTRANDO...';

    try {
        const response = await fetch(BASE_URL + 'api_produtos.php', { 
            method: 'POST', 
            body: formData 
        });
        
        const result = await response.json();

        if (result.status === 'success') {
            showToast('Perda registrada com sucesso!', 'success');
            
            // Fechar modal e recarregar a lista para atualizar o estoque na tela
            setTimeout(() => {
                location.reload();
            }, 1200);
        } else {
            showToast(result.message || 'Erro ao registrar perda', 'error');
            btn.disabled = false;
            btn.innerHTML = originalContent;
        }
    } catch (error) {
        console.error('Erro na requisição:', error);
        showToast('Erro de comunicação com o servidor', 'error');
        btn.disabled = false;
        btn.innerHTML = originalContent;
    }
};



</script>