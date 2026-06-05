<?php
include_once("../config_api.php");

/**
 * BUSCA DE DADOS COM JOIN
 */
$sql = "SELECT 
            p.id_produto,
            p.nome_produto, 
            p.foto_produto, 
            f.nome_completo AS nome_funcionario,
            pr.* 
        FROM perdas pr
        JOIN produtos p ON pr.id_produto = p.id_produto 
        LEFT JOIN funcionarios f ON pr.id_funcionario_responsavel = f.id_funcionario
        ORDER BY pr.data_registro DESC"; 

$resultado = $conn->query($sql);
$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/pharmora/";
?>

<style>
/* ============ CONFIGURAÇÕES GERAIS ============ */
.rh-container {
    width: 100%;
    animation: fadeIn 0.4s ease;
    color: var(--text-main);
    font-family: 'Inter', sans-serif;
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
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-info p {
    color: var(--text-dim);
    font-size: 12px;
    margin-top: 4px;
}

/* ============ BUSCA E FILTROS ============ */
.header-actions {
    display: flex;
    gap: 12px;
    flex: 1 1 auto;
    justify-content: flex-end;
}

/* ============ BUSCA E BOTÕES PRINCIPAIS ============ */
/* Container pai relativo para posicionar o dropdown */
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
    z-index: 2; /* Garante que o ícone fique acima do preenchimento */
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

/* Dropdown de Resultados */
.dropdown-resultados {
    position: absolute;
    top: calc(100% + 5px); /* Pequeno espaço abaixo do input */
    left: 0;
    right: 0;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 12px;
    z-index: 2000; /* Aumentado para sobrepor modais se necessário */
    max-height: 280px;
    overflow-y: auto;
    display: none; /* Controlado pelo JS */
    box-shadow: 0 15px 35px rgba(0,0,0,0.4);
    backdrop-filter: blur(15px);
}

/* Customização da Scrollbar interna do dropdown */
.dropdown-resultados::-webkit-scrollbar {
    width: 6px;
}
.dropdown-resultados::-webkit-scrollbar-thumb {
    background: var(--card-border);
    border-radius: 10px;
}

.result-item {
    padding: 10px 15px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    transition: background 0.2s ease;
}

.result-item:last-child {
    border-bottom: none;
}

.result-item:hover {
    background: rgba(0, 255, 204, 0.08);
}

.result-item img {
    width: 38px; /* Aumentado levemente para melhor visibilidade */
    height: 38px;
    border-radius: 8px;
    object-fit: cover;
    background: #333;
    border: 1px solid var(--card-border);
}

.result-info {
    display: flex;
    flex-direction: column;
    overflow: hidden; /* Evita que nomes longos quebrem o layout */
}

.result-name { 
    font-size: 13px; 
    font-weight: 600; 
    color: var(--text-main);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.result-stock { 
    font-size: 11px; 
    color: var(--text-dim); 
    margin-top: 2px;
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

.main-row:hover {
    background: rgba(255, 255, 255, 0.02);
}

/* ============ CÉLULA DO PRODUTO ============ */
.product-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.product-img {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    object-fit: cover;
    border: 1px solid var(--card-border);
    background: var(--input-fill);
}

.product-name {
    font-weight: 600;
    font-size: 14px;
    color: var(--text-main);
    display: block;
}

.product-info-sub {
    font-size: 11px;
    color: var(--text-dim);
}

/* ============ BADGES E STATUS ============ */
.badge-motivo {
    padding: 5px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

/* Cores específicas para perdas */
.status-quebra { background: rgba(231, 76, 60, 0.15); color: #ff5e57; border: 1px solid rgba(231, 76, 60, 0.2); }
.status-validade { background: rgba(243, 156, 18, 0.15); color: #ffab00; border: 1px solid rgba(243, 156, 18, 0.2); }
.status-uso { background: rgba(52, 152, 219, 0.15); color: #3498db; border: 1px solid rgba(52, 152, 219, 0.2); }

/* ============ LINHA DE OBSERVAÇÃO (EXPANSÍVEL) ============ */
.obs-row {
    background: rgba(0, 0, 0, 0.1);
}

.obs-content {
    padding: 10px 20px;
    font-size: 12px;
    color: var(--text-dim);
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.obs-content i {
    color: var(--accent);
    margin-top: 3px;
}

/* ============ CARD DE RESUMO FINANCEIRO OTIMIZADO ============ */
:root {
    --danger-color: #e74c3c;
    --danger-glow: rgba(231, 76, 60, 0.15);
}

.summary-container {
    display: flex;
    justify-content: flex-end;
    margin-top: 30px;
    padding: 0 10px;
}

.summary-card {
    background: var(--card-bg);
    padding: 20px 30px;
    border-radius: 20px;
    border: 1px solid var(--card-border);
    border-left: 6px solid var(--danger-color);
    min-width: 10%;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 10px 25px -5px var(--danger-glow);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px -5px rgba(231, 76, 60, 0.25);
}

.summary-label {
    display: block;
    font-size: 10px;
    text-transform: uppercase;
    color: var(--text-dim);
    letter-spacing: 1.5px;
    font-weight: 700;
    margin-bottom: 4px;
}

.summary-value {
    margin: 0;
    color: var(--danger-color);
    font-size: 28px;
    font-weight: 900;
    display: flex;
    align-items: baseline;
    gap: 5px;
}

.summary-value .currency {
    font-size: 14px;
    font-weight: 600;
    opacity: 0.8;
}

/* ============ RESPONSIVIDADE (Mobile First) ============ */
@media (max-width: 768px) {
    .summary-container {
        justify-content: center; /* Centraliza no mobile */
        margin-top: 20px;
    }

    .summary-card {
        width: 100%;
        min-width: unset;
        padding: 15px 20px;
        justify-content: space-between;
    }

    .summary-value {
        font-size: 24px;
    }
}

/* Animação de entrada */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.summary-card {
    animation: fadeInUp 0.5s ease-out;
}
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


/* ============ ANIMAÇÕES ============ */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<div class="rh-container">
    <div class="top-bar">
        <div class="header-info">
            <h2><i class="fas fa-box-open"></i> Histórico de Perdas</h2>
            <p>Controle de quebras, validades e edição de registros</p>
        </div>
        
        <div class="header-actions">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="buscaProdutoPerda" placeholder="Pesquisar no histórico..." autocomplete="off">
            </div>
                    </div>
    </div>

    <div class="table-glass">
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Qtd</th>
                    <th>Motivo</th>
                    <th>Responsável</th>
                    <th>Data</th>
                    <th style="text-align: right;">Prejuízo (Kz)</th>
                    <th style="text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody id="bodyPerdas">
                <?php if($resultado && $resultado->num_rows > 0): ?>
                    <?php 
                    $totalGeralPrejuizo = 0;
                    while($row = $resultado->fetch_assoc()): 
                        $totalGeralPrejuizo += $row['valor_prejuizo_total'];
                        
                        $classeMotivo = 'status-Inativo';
                        if($row['motivo'] == 'Uso Interno') $classeMotivo = 'status-Ativo';
                        if($row['motivo'] == 'Erro de Contagem') $classeMotivo = 'status-Esgotado';
                        
                        $id_perda = $row['id_perda'] ?? $row['id'] ?? 0;
                    ?>
                    
                    <tr class="main-row">
                        <td>
                            <div class="product-cell">
                                <img class="product-img" src="<?php echo $base_url; ?>uploads/produtos/<?php echo !empty($row['foto_produto']) ? $row['foto_produto'] : 'produto_default.png'; ?>">
                                <div>
                                    <span class="product-name"><strong><?php echo htmlspecialchars($row['nome_produto']); ?></strong></span>
                                    <span style="font-size: 11px; color: var(--text-dim);">Un: Kz <?php echo number_format($row['preco_custo_unidade'], 2, ',', '.'); ?></span>
                                </div>
                            </div>
                        </td>
                        <td><strong><?php echo $row['quantidade']; ?></strong></td>
                        <td><span class="badge-status <?php echo $classeMotivo; ?>"><?php echo htmlspecialchars($row['motivo']); ?></span></td>
                        <td><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($row['nome_funcionario'] ?? 'Sistema'); ?></td>
                        <td><span style="font-size: 11px;"><?php echo date('d/m/Y H:i', strtotime($row['data_registro'])); ?></span></td>
                        <td style="text-align: right; font-weight: 800; color: #e74c3c;">
                            <?php echo number_format($row['valor_prejuizo_total'], 2, ',', '.'); ?>
                        </td>
                        <td style="text-align: center;">
                            <button onclick="abrirModalEditar('<?php echo $id_perda; ?>', '<?php echo $row['id_produto']; ?>', '<?php echo addslashes($row['nome_produto']); ?>', '<?php echo $row['quantidade']; ?>', '<?php echo $row['motivo']; ?>', '<?php echo addslashes($row['observacao'] ?? ''); ?>', '<?php echo $row['preco_custo_unidade']; ?>')" style="background:none; border:none; color:#3498db; cursor:pointer;">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>

                    <?php if(!empty($row['observacao'])): ?>
                    <tr class="obs-row" style="background: rgba(255,255,255,0.02);">
                        <td colspan="7" style="padding: 8px 20px; font-size: 11px; color: var(--text-dim); font-style: italic;">
                            <i class="fas fa-comment-dots" style="color: var(--accent);"></i> <strong>Nota:</strong> <?php echo htmlspecialchars($row['observacao']); ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center; padding: 40px;">Nenhum registro encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if(isset($totalGeralPrejuizo) && $totalGeralPrejuizo > 0): ?>
    <div style="display: flex; justify-content: flex-end;">
        <div style="background: var(--card-bg); padding: 15px 25px; border-radius: 12px; border-left: 5px solid #e74c3c; border: 1px solid var(--card-border);">
            <span style="font-size: 10px; text-transform: uppercase; color: var(--text-dim);">Prejuízo Total</span>
            <h2 style="margin:0; color: #e74c3c;">Kz <?php echo number_format($totalGeralPrejuizo, 2, ',', '.'); ?></h2>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL DE EDIÇÃO (VISUAL NOVO + SEUS IDs ORIGINAIS) -->
<div class="modal-overlay" id="modalEditarPerda" onclick="window.fecharModal(event, 'modalEditarPerda')">
    <div class="modal-content" style="max-width: 500px;" onclick="event.stopPropagation()">
        <!-- Botão fechar original -->
        <button class="close-modal" type="button" onclick="fecharModalForcado('modalEditarPerda')">&times;</button>
        
        <!-- Título com estilo do registro -->
        <h2 style="color: #e74c3c; margin-top:0; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-edit"></i> Editar Registro de Perda
        </h2>
        
        <!-- Box de informações com seus IDs de texto -->
        <div style="background: rgba(231, 76, 60, 0.05); padding: 15px; border-left: 4px solid #e74c3c; border-radius: 4px; margin-bottom: 20px;">
            <span id="editProdutoNome" style="font-weight: 700; color: #c0392b; font-size: 16px; display: block;"></span>
            <!-- Mantive o ID da data caso você o use, senão ele apenas não atrapalha -->
            <small style="color: var(--text-dim);">Ajuste os dados abaixo para corrigir o registro.</small>
        </div>

        <form id="formEditarPerda" onsubmit="atualizarPerda(event)">
            <!-- MANTIDOS TODOS OS SEUS IDs DE INPUT HIDDEN -->
            <input type="hidden" name="acao" value="editar_perda">
            <input type="hidden" name="id_perda" id="edit_id_perda">
            <input type="hidden" name="id_produto" id="edit_id_produto">
            <input type="hidden" id="edit_preco_custo_un_val"> <!-- SEU ID ORIGINAL -->

            <!-- Grid de inputs com visual novo -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="input-group">
                    <label style="font-weight: 600; display:block; margin-bottom:5px; font-size:13px;">Quantidade Perdida *</label>
                    <input type="number" name="qtd_perda" id="edit_qtd_perda" min="1" required 
                           style="width:100%; border: 1px solid #dcdfe6; padding: 10px; border-radius: 6px; background: var(--input-fill); color: var(--text-main);"
                           oninput="calcularPrejuizoEdicao()">
                </div>
                <div class="input-group">
                    <label style="font-weight: 600; display:block; margin-bottom:5px; font-size:13px;">Motivo da Perda *</label>
                    <select name="motivo_perda" id="edit_motivo_perda" required 
                            style="width:100%; border: 1px solid #dcdfe6; padding: 10px; border-radius: 6px; background: var(--input-fill); color: var(--text-main);">
                        <option value="Validade Vencida">Validade Vencida</option>
                        <option value="Quebra/Avaria">Quebra / Avaria</option>
                        <option value="Furto/Roubo">Furto ou Roubo</option>
                        <option value="Uso Interno">Uso Interno</option>
                        <option value="Erro de Contagem">Erro de Contagem</option>
                    </select>
                </div>
            </div>

            <div class="input-group" style="margin-top: 15px;">
                <label style="font-weight: 600; display:block; margin-bottom:5px; font-size:13px;">Observação / Relato *</label>
                <textarea name="observacao_perda" id="edit_observacao_perda" 
                          style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #dcdfe6; min-height: 90px; font-family: inherit; resize: none; background: var(--input-fill); color: var(--text-main);" 
                          required></textarea>
            </div>

            <!-- Box de Prejuízo com visual tracejado e SEU ID ORIGINAL -->
            <div id="infoPrejuizoEdit" style="margin-top: 20px; padding: 12px; background: #fdf2f2; border-radius: 6px; border: 1px dashed #e74c3c;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #c0392b; font-size: 13px;">Novo prejuízo estimado:</span>
                    <strong style="color: #e74c3c; font-size: 15px;">Kz <span id="valorPrejuizoEdit">0,00</span></strong>
                </div>
            </div>

            <!-- Botões com estilo do registro -->
            <div style="margin-top: 25px; display: flex; gap: 12px;">
                <button type="button" onclick="fecharModalForcado('modalEditarPerda')" 
                        style="flex: 1; background: #f4f4f5; color: #606266; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    CANCELAR
                </button>
                <button type="submit" id="btnSubmitEditPerda" 
                        style="flex: 2; background: #e74c3c; color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-save"></i> SALVAR ALTERAÇÕES
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const BASE_URL_API = "/pharmora/modules/api_produtos.php";

window.abrirModalEditar = function(idPerda, idProduto, nomeProduto, qtd, motivo, observacao, precoCusto) {
    document.getElementById('edit_id_perda').value = idPerda;
    document.getElementById('edit_id_produto').value = idProduto;
    document.getElementById('edit_preco_custo_un_val').value = precoCusto;
    
    document.getElementById('editProdutoNome').innerText = nomeProduto;
    document.getElementById('edit_qtd_perda').value = qtd;
    document.getElementById('edit_observacao_perda').value = observacao;
    document.getElementById('edit_motivo_perda').value = motivo;

    window.calcularPrejuizoEdicao();
    document.getElementById('modalEditarPerda').classList.add('active');
};

window.calcularPrejuizoEdicao = function() {
    const qtd = parseFloat(document.getElementById('edit_qtd_perda').value) || 0;
    const precoUn = parseFloat(document.getElementById('edit_preco_custo_un_val').value) || 0;
    const total = qtd * precoUn;
    document.getElementById('valorPrejuizoEdit').innerText = total.toLocaleString('pt-AO', { minimumFractionDigits: 2 });
};

window.fecharModalForcado = function(id) {
    document.getElementById(id).classList.remove('active');
};

window.atualizarPerda = async function(event) {
    event.preventDefault();
    const btn = document.getElementById('btnSubmitEditPerda');
    const formData = new FormData(event.target);
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PROCESSANDO...';

    try {
        const response = await fetch(BASE_URL_API, { method: 'POST', body: formData });
        const result = await response.json();

        if (result.status === 'success') {
            alert('Atualizado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (result.message || 'Erro desconhecido'));
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> SALVAR ALTERAÇÕES';
        }
    } catch (error) {
        alert('Erro de conexão com a API.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> SALVAR ALTERAÇÕES';
    }
};

// Filtro de busca na tabela
document.getElementById('buscaProdutoPerda').addEventListener('input', function() {
    const termo = this.value.toLowerCase();
    const linhas = document.querySelectorAll('#bodyPerdas tr.main-row');
    
    linhas.forEach(linha => {
        const texto = linha.innerText.toLowerCase();
        const obsLinha = linha.nextElementSibling;
        const visivel = texto.includes(termo);
        
        linha.style.display = visivel ? "" : "none";
        if(obsLinha && obsLinha.classList.contains('obs-row')) {
            obsLinha.style.display = visivel ? "" : "none";
        }
    });
});
</script>