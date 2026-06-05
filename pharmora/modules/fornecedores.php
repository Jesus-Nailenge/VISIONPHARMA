<?php
/**
 * PHARMORA - Painel de Listagem e Cadastro de Fornecedores
 */
require_once("../config_api.php");

// Busca todos os fornecedores reais cadastrados no banco de dados
$sql = "SELECT * FROM fornecedores ORDER BY id_fornecedor DESC";
$res_fornecedores = $conn->query($sql);

// Base URL dinâmica idêntica à tela de perdas
$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/pharmora/";
?>

<style>
/* ============ CONFIGURAÇÕES GERAIS UNIFICADAS ============ */
.rh-container {
    width: 100%;
    animation: fadeIn 0.4s ease;
    color: var(--text-main);
    font-family: 'Inter', sans-serif;
    padding: 20px;
}

/* ============ CABEÇALHO (TOP BAR) ============ */
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

/* ============ BUSCA E BOTÕES PRINCIPAIS ============ */
.header-actions {
    display: flex;
    gap: 12px;
    flex: 1 1 auto;
    justify-content: flex-end;
    align-items: center;
}

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
    z-index: 2;
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
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.btn-new {
    background: var(--accent);
    color: white;
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
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
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
    min-width: 950px;
}

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
    padding: 14px 12px;
    border-bottom: 1px solid var(--card-border);
    font-size: 13px;
    vertical-align: middle;
}

tbody tr:hover { 
    background: rgba(255, 255, 255, 0.02); 
}

/* Fornecedor Estilo Célula */
.supplier-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.supplier-icon {
    font-size: 20px;
    color: var(--accent);
    background: rgba(37, 99, 235, 0.1);
    padding: 10px;
    border-radius: 10px;
    border: 1px solid rgba(37, 99, 235, 0.2);
}

/* ============ AÇÕES (BOTÕES DA TABELA) ============ */
.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
    align-items: center;
}

.btn-action {
    background: transparent;
    border: 1px solid var(--card-border);
    color: var(--text-dim);
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-action.edit:hover {
    background: rgba(37, 99, 235, 0.1);
    color: var(--accent);
    border-color: var(--accent);
}

.btn-action.delete:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border-color: #ef4444;
}

/* ============ BADGES E STATUS ============ */
.status-badge { 
    padding: 5px 12px; 
    border-radius: 8px; 
    font-size: 11px; 
    font-weight: 700; 
    text-transform: uppercase;
}
.status-active { 
    background: rgba(22, 163, 74, 0.15); 
    color: #4ade80; 
    border: 1px solid rgba(22, 163, 74, 0.2); 
}

/* ============ MODAL SYSTEM UNIFICADO ============ */
.modal-overlay {
    position: fixed; 
    inset: 0;
    background: rgba(0, 0, 0, 0.7); 
    backdrop-filter: blur(8px);
    z-index: 9999; 
    display: none; 
    justify-content: center; 
    align-items: center;
    padding: 20px;
}

.modal-overlay.active { 
    display: flex; 
}

.modal-content {
    width: 100%;
    max-width: 550px;
    max-height: 90vh;
    overflow-y: auto;
    background: #111827; 
    border-radius: 20px;
    border: 1px solid var(--card-border);
    padding: 25px;
    position: relative;
    animation: slideUp 0.3s ease;
}

.close-modal {
    position: absolute; 
    top: 15px; 
    right: 15px;
    color: var(--text-dim); 
    cursor: pointer; 
    font-size: 24px;
    border: none; 
    background: transparent; 
    transition: 0.3s;
}
.close-modal:hover { 
    color: var(--accent); 
    transform: rotate(90deg); 
}

/* Elementos do Formulário Customizados */
.form-group { 
    margin-bottom: 15px; 
    display: flex; 
    flex-direction: column; 
    gap: 5px; 
}

.form-group label { 
    font-weight: 600; 
    display: block; 
    font-size: 13px; 
    color: var(--text-main);
}

.form-group input, .form-group select, .form-group textarea {
    width: 100%; 
    border: 1px solid var(--card-border); 
    padding: 10px; 
    border-radius: 8px; 
    background: var(--input-fill); 
    color: var(--text-main);
    font-family: inherit;
    outline: none;
    transition: all 0.3s;
}

.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: var(--accent);
}

.form-row { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 15px; 
}

/* ============ ANIMAÇÕES ============ */
@keyframes fadeIn { 
    from { opacity: 0; transform: translateY(10px); } 
    to { opacity: 1; transform: translateY(0); } 
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<div class="rh-container">
    <!-- Topo da Página -->
    <div class="top-bar">
        <div class="header-info">
            <h2><i class="fas fa-truck"></i> Lista de Fornecedores</h2>
            <p>Gerenciamento de parcerias e contratos da Farmácia</p>
        </div>
        
        <div class="header-actions">
            <!-- Barra de Pesquisa -->
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="buscaFornecedor" onkeyup="filtrarFornecedores()" placeholder="Pesquisar por nome ou NIF..." autocomplete="off">
            </div>
            <!-- Botão Novo -->
            <button class="btn-new" onclick="abrirModalNovo()">
                <i class="fas fa-plus"></i> NOVO FORNECEDOR
            </button>
        </div>
    </div>

    <!-- Tabela -->
    <div class="table-glass">
        <table id="tabelaFornecedores">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome do Fornecedor</th>
                    <th>NIF</th>
                    <th>Telefone</th>
                    <th>Email</th>
                    <th>Categoria</th>
                    <th>Pagamento</th>
                    <th style="text-align: center;">Status</th>
                    <th style="text-align: center;">Ações</th> <!-- Nova Coluna -->
                </tr>
            </thead>
            <tbody>
                <?php if ($res_fornecedores && $res_fornecedores->num_rows > 0): ?>
                    <?php while($forn = $res_fornecedores->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $forn['id_fornecedor']; ?></td>
                        <td>
                            <div class="supplier-cell">
                                <i class="fas fa-building supplier-icon"></i>
                                <div>
                                    <span class="product-name"><strong><?php echo htmlspecialchars($forn['nome']); ?></strong></span>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($forn['nif']); ?></td>
                        <td><i class="fas fa-phone-alt" style="font-size: 11px; color: var(--text-dim);"></i> <?php echo htmlspecialchars($forn['telefone'] ?: '---'); ?></td>
                        <td><?php echo htmlspecialchars($forn['email'] ?: '---'); ?></td>
                        <td><span style="font-size: 12px; font-weight: 500;"><?php echo htmlspecialchars($forn['categoria']); ?></span></td>
                        <td><?php echo htmlspecialchars($forn['condicoes_pagamento']); ?></td>
                        <td style="text-align: center;"><span class="status-badge status-active">Ativo</span></td>
                        <td>
                            <!-- Botões de Ação -->
                            <div class="action-buttons">
                                <button class="btn-action edit" onclick='abrirModalEditar(<?php echo json_encode($forn); ?>)' title="Editar Fornecedor">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action delete" onclick="eliminarFornecedor(<?php echo $forn['id_fornecedor']; ?>)" title="Eliminar Fornecedor">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr id="linhaVazia">
                        <td colspan="9" style="text-align: center; color: var(--text-dim); padding: 40px;">
                            Nenhum fornecedor cadastrado ainda. Clique em "Novo Fornecedor" para começar.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ================= MODAL ÚNICO (CADASTRO E EDIÇÃO) ================= -->
<div class="modal-overlay" id="modalFornecedor" onclick="window.fecharModalExterno(event)">
    <div class="modal-content">
        <button class="close-modal" type="button" onclick="fecharModal()">&times;</button>
        
        <h2 id="modalTitle" style="color: var(--accent); margin-top:0; display: flex; align-items: center; gap: 10px; font-size: 20px;">
            <i class="fas fa-plus-circle"></i> Cadastrar Novo Fornecedor
        </h2>
        
        <div style="background: rgba(37, 99, 235, 0.05); padding: 12px; border-left: 4px solid var(--accent); border-radius: 4px; margin-bottom: 20px;">
            <small id="modalSubtitle" style="color: var(--text-dim);">Insira as informações comerciais para adicionar à base do sistema.</small>
        </div>

        <form id="formFornecedor" onsubmit="salvarFornecedor(event)">
            <!-- CAMPO OCULTO PARA O ID AO EDITAR -->
            <input type="hidden" name="id_fornecedor" id="input_id_fornecedor" value="">

            <div class="form-group">
                <label>Nome da Empresa *</label>
                <input type="text" name="nome" id="input_nome" required placeholder="Ex: Angola Medicamentos Lda">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>NIF *</label>
                    <input type="text" name="nif" id="input_nif" required placeholder="Ex: 540123456">
                </div>
                <div class="form-group">
                    <label>Telefone</label>
                    <input type="text" name="telefone" id="input_telefone" placeholder="Ex: +244 923 000 000">
                </div>
            </div>

            <div class="form-group">
                <label>E-mail</label>
                <input type="email" name="email" id="input_email" placeholder="Ex: comercial@empresa.com">
            </div>

            <div class="form-group">
                <label>Endereço Residencial/Comercial</label>
                <textarea name="endereco" id="input_endereco" rows="2" placeholder="Cidade, Província..." style="resize: none;"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Categoria</label>
                    <select name="categoria" id="input_categoria">
                        <option value="Medicamentos">Medicamentos</option>
                        <option value="Higiene">Higiene</option>
                        <option value="Infantil">Infantil</option>
                        <option value="Geral">Geral</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Condições de Pagamento</label>
                    <select name="pagamento" id="input_pagamento">
                        <option value="Pronto Pagamento">Pronto Pagamento</option>
                        <option value="15 Dias">15 Dias</option>
                        <option value="30 Dias">30 Dias</option>
                        <option value="60 Dias">60 Dias</option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 25px; display: flex; gap: 12px;">
                <button type="button" onclick="fecharModal()" 
                        style="flex: 1; background: #f4f4f5; color: #606266; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    CANCELAR
                </button>
                <button type="submit" 
                        style="flex: 2; background: var(--success); color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-save"></i> SALVAR
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ================= SCRIPTS DE INTERAÇÃO ================= -->
<script>
// Abrir modal para NOVO cadastro
window.abrirModalNovo = function() {
    const form = document.getElementById('formFornecedor');
    form.reset();
    document.getElementById('input_id_fornecedor').value = ''; // Limpa ID
    
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Cadastrar Novo Fornecedor';
    document.getElementById('modalSubtitle').innerText = 'Insira as informações comerciais para adicionar à base do sistema.';
    
    document.getElementById('modalFornecedor').classList.add('active');
}

// Abrir modal para EDIÇÃO (preenche os campos)
window.abrirModalEditar = function(forn) {
    document.getElementById('input_id_fornecedor').value = forn.id_fornecedor;
    document.getElementById('input_nome').value = forn.nome;
    document.getElementById('input_nif').value = forn.nif;
    document.getElementById('input_telefone').value = forn.telefone;
    document.getElementById('input_email').value = forn.email;
    document.getElementById('input_endereco').value = forn.endereco;
    
    // Selecionar valor correto no dropdown se existir
    if(forn.categoria) document.getElementById('input_categoria').value = forn.categoria;
    if(forn.condicoes_pagamento) document.getElementById('input_pagamento').value = forn.condicoes_pagamento;

    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Fornecedor';
    document.getElementById('modalSubtitle').innerText = 'Atualize os dados comerciais deste fornecedor abaixo.';

    document.getElementById('modalFornecedor').classList.add('active');
}

// Fechar e limpar Modal
window.fecharModal = function() {
    const modal = document.getElementById('modalFornecedor');
    if (modal) modal.classList.remove('active');
}

// Clique fora do modal
window.fecharModalExterno = function(event) {
    if(event.target.classList.contains('modal-overlay')) {
        window.fecharModal();
    }
}

// Salvar / Atualizar Fornecedor
window.salvarFornecedor = function(event) {
    event.preventDefault();
    const form = document.getElementById('formFornecedor');
    const formData = new FormData(form);

    fetch('./modules/salvar_fornecedor.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error('Servidor respondeu com erro ' + response.status);
        return response.json();
    })
    .then(data => {
        if(data.success) {
            alert(data.message);
            window.fecharModal();
            location.reload();
        } else {
            alert("Erro: " + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert("Ocorreu um erro ao salvar o fornecedor. Verifique o console.");
    });
}

// Eliminar Fornecedor
window.eliminarFornecedor = function(id) {
    if(confirm("ATENÇÃO: Tem certeza que deseja ELIMINAR este fornecedor?\n\nEsta ação é irreversível.")) {
        
        // Use FormData para enviar como POST de forma simples
        const formData = new FormData();
        formData.append('id_fornecedor', id);

        fetch('./modules/eliminar_fornecedor.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert("Erro ao eliminar: " + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert("Ocorreu um erro ao eliminar. Verifique a consola.");
        });
    }
}

// Pesquisa dinâmica na tabela
window.filtrarFornecedores = function() {
    const input = document.getElementById('buscaFornecedor');
    if (!input) return;
    const termo = input.value.toLowerCase();
    const linhas = document.querySelectorAll('#tabelaFornecedores tbody tr');
    
    linhas.forEach(linha => {
        if(linha.id === "linhaVazia") return;
        linha.style.display = linha.innerText.toLowerCase().includes(termo) ? "" : "none";
    });
}
</script>