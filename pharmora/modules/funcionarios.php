<?php
include_once("../config_api.php");

// INNER JOIN opcional para trazer dados de acesso apenas se existirem na tabela usuarios
$sql = "SELECT f.*, u.username, u.nivel_acesso, u.permissoes_especiais, u.estado_conta, u.ultimo_login, u.dispositivo_usado, u.numero_logins 
        FROM funcionarios f 
        LEFT JOIN usuarios u ON f.id_sistema = u.id_sistema 
        ORDER BY f.id_sistema DESC"; 

$resultado = $conn->query($sql);
$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/pharmora/";
?>

<style>
/* ============ CONTAINER PRINCIPAL ============ */
.rh-container {
    width: 100%;
    max-width: 100%;
    margin: 0;
    padding: 0;
    animation: fadeIn 0.4s ease;
    color: var(--text-main);
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    box-sizing: border-box;
    overflow-x: hidden; /* Evita vazamento horizontal */
}

/* ============ HEADER ============ */
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

/* ============ SEARCH BOX ============ */
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
    transition: 0.3s;
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
    box-sizing: border-box;
}

.search-box input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(0, 255, 204, 0.15);
}

/* ============ BOTÃO NOVO REGISTRO ============ */
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
    border: 1px solid transparent;
    box-shadow: 0 4px 12px rgba(0, 255, 204, 0.25);
}

.btn-new:hover {
    background: transparent;
    color: var(--accent);
    border-color: var(--accent);
    transform: translateY(-2px);
}

/* ============ TABELA (CRIADA PARA ROLAR HORIZONTALMENTE SEM QUEBRAR TELA) ============ */
.table-glass {
    width: 100%;
    max-width: 100%;
    border-collapse: collapse;
    background: var(--card-bg);
    border-radius: 16px;
    overflow-x: auto; /* Permite rolar a tabela no celular */
    overflow-y: hidden;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(15px);
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    -webkit-overflow-scrolling: touch;
}

table { 
    width: 100%; 
    border-collapse: collapse; 
    table-layout: auto; 
    min-width: 800px; /* Impede que colunas fiquem muito esmagadas */
}

/* Customização da barra de rolagem da tabela */
.table-glass::-webkit-scrollbar {
    height: 4px; /* Altura da barra horizontal */
    width: 3px;  /* Largura da barra vertical (se houver) */
}

.table-glass::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02); /* Fundo da barra */
    border-radius: 10px;
}

.table-glass::-webkit-scrollbar-thumb {
    background: var(--accent); /* Cor verde neon/accent */
    border-radius: 10px;
    border: 2px solid rgba(0, 0, 0, 0.3); /* Dá um efeito de espaçamento */
}

.table-glass::-webkit-scrollbar-thumb:hover {
    background: #00ccaa; /* Um tom mais escuro ao passar o mouse */
    cursor: pointer;
}

thead {
    position: sticky;
    top: 0;
    z-index: 10;
}

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
    white-space: nowrap;
}

td {
    padding: 12px 12px;
    border-bottom: 1px solid var(--card-border);
    font-size: 13px;
    transition: background 0.2s;
    word-break: break-word;
}

tbody tr.main-row { transition: all 0.2s ease; }
tbody tr.main-row:hover { background: rgba(255, 255, 255, 0.02); }

/* ============ COLUNAS RESPONSIVAS ============ */
.col-pc { display: none; }
.col-tel { display: table-cell; }

@media (min-width: 200px) { 
    .col-pc { display: table-cell; } 
}

@media (max-width: 767px) {
    .search-box { min-width: 100%; max-width: 100%; }
    .header-actions { width: 100%; justify-content: space-between; }
    .btn-new { flex: 1; justify-content: center; }
    th, td { padding: 10px 8px; }
}

/* ============ AVATAR ============ */
.user-cell { display: flex; align-items: center; gap: 10px; min-width: 150px; }
.user-avatar {
    width: 38px; height: 38px; border-radius: 10px; object-fit: cover;
    border: 2px solid var(--card-border); transition: transform 0.3s;
    background: var(--input-fill); flex-shrink: 0;
}
tr.main-row:hover .user-avatar { transform: scale(1.1); border-color: var(--accent); }
.user-name { display: block; font-size: 14px; font-weight: 600; color: var(--text-main); }
.user-doc { color: var(--text-dim); font-size: 11px; margin-top: 2px; }

/* ============ BADGES ============ */
.badge-nivel {
    padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
    background: var(--input-fill); border: 1px solid var(--card-border);
    display: inline-block; white-space: nowrap; color: var(--text-main);
}
.badge-active { background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); }
.badge-inactive { background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3); }
.badge-warning { background: rgba(243, 156, 18, 0.15); color: #f39c12; border: 1px solid rgba(243, 156, 18, 0.3); }

/* ============ BOTÕES DE AÇÃO ============ */
.actions-group { display: flex; justify-content: center; gap: 4px; flex-wrap: wrap; }
.btn-action {
    width: 32px; height: 32px; border-radius: 8px; border: 1.5px solid var(--card-border);
    background: var(--input-fill); color: var(--text-dim); cursor: pointer;
    transition: all 0.25s ease; display: flex; align-items: center; justify-content: center;
    font-size: 13px; position: relative; overflow: hidden;
}
.btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); }
.btn-view:hover { color: #3498db; border-color: #3498db; }
.btn-shield:hover { color: #f39c12; border-color: #f39c12; }
.btn-edit:hover { color: var(--accent); border-color: var(--accent); }
.btn-del:hover { color: var(--danger); border-color: var(--danger); }

/* ============ LINHA EXPANSÍVEL ============ */
.expand-row { display: none; background: rgba(52, 152, 219, 0.03); border-left: 3px solid #3498db; animation: expandIn 0.3s ease; }
.expand-row.show { display: table-row; }
.expand-row td { padding: 16px 12px; }
.detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; }
.detail-item { display: flex; flex-direction: column; gap: 4px; padding: 10px; background: var(--card-bg); border-radius: 8px; border: 1px solid var(--card-border); }
.detail-label { font-size: 10px; text-transform: uppercase; color: #3498db; font-weight: 700; display: flex; align-items: center; gap: 6px; }
.detail-value { font-size: 13px; color: var(--text-main); }

/* ============ MODAIS (CORREÇÃO DE ALINHAMENTO E RESPEITO AO SIDEBAR) ============ */
.modal-overlay {
    position: fixed; /* O segredo está no Dashboard CSS que segura esse fixed! */
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    z-index: 9999;
    display: none;
    justify-content: center;
    align-items: center; /* Centraliza perfeitamente o modal no espaço livre */
    padding: 20px;
    animation: fadeIn 0.2s ease;
}

.modal-overlay.active { display: flex; }

.modal-content {
    width: 100%;
    max-width: 850px;
    max-height: 90vh; /* Nunca passa do tamanho da tela */
    overflow-y: auto; /* Permite rolar apenas dentro do modal se for muito grande */
    background: var(--card-bg);
    border-radius: 20px;
    border: 1px solid var(--card-border);
    padding: 25px;
    position: relative;
    animation: slideUp 0.3s ease;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}

/* Customização do scroll interno do Modal */
.modal-content::-webkit-scrollbar { width: 6px; }
.modal-content::-webkit-scrollbar-thumb { background: var(--accent); border-radius: 10px; }

@media (max-width: 600px) {
    .modal-overlay { padding: 10px; }
    .modal-content { max-width: 100%; padding: 20px 15px; }
    .close-modal { top: 10px; right: 10px; }
}

.close-modal {
    position: absolute;
    top: 15px; right: 15px;
    color: var(--text-dim); cursor: pointer;
    font-size: 24px; width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 50%; transition: all 0.3s;
    background: transparent; border: none; z-index: 10;
}
.close-modal:hover { color: var(--accent); background: rgba(255,255,255,0.05); transform: rotate(90deg); }

/* ============ FORMULÁRIOS ============ */
.section-title {
    font-size: 11px; color: var(--accent); text-transform: uppercase; letter-spacing: 1px;
    margin: 20px 0 12px; padding-bottom: 8px; border-bottom: 2px solid var(--card-border);
    display: flex; align-items: center; gap: 8px; font-weight: 700;
}

.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 200px), 1fr)); /* 100% responsivo sem transbordar */
    gap: 14px;
}
.full-width { grid-column: 1 / -1; }

.input-group { display: flex; flex-direction: column; gap: 6px; }
.input-group label { font-size: 11px; color: var(--text-dim); font-weight: 600; text-transform: uppercase; }
.input-group input, .input-group select, .input-group textarea {
    background: var(--input-fill); border: 2px solid var(--card-border);
    padding: 10px 12px; border-radius: 8px; color: var(--text-main);
    font-size: 13px; transition: all 0.3s; outline: none; font-family: inherit; width: 100%; box-sizing: border-box;
}
.input-group input:focus, .input-group select:focus, .input-group textarea:focus {
    border-color: var(--accent); box-shadow: 0 0 0 3px rgba(0, 255, 204, 0.15);
}
.input-group textarea { resize: vertical; min-height: 70px; }

.btn-save {
    width: 100%; padding: 14px; background: var(--accent); color: #000;
    border: none; border-radius: 12px; font-weight: 700; cursor: pointer;
    margin-top: 20px; transition: all 0.3s; font-size: 14px;
    text-transform: uppercase; border: 1px solid transparent;
}
.btn-save:hover { background: transparent; color: var(--accent); border-color: var(--accent); transform: translateY(-2px); }
.btn-save:disabled { opacity: 0.6; cursor: not-allowed; animation: pulse 1.5s infinite; }

/* ============ PERMISSÕES ============ */
.perm-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 200px), 1fr)); gap: 10px; }
.perm-item {
    display: flex; justify-content: space-between; align-items: center;
    background: var(--input-fill); padding: 12px 14px; border-radius: 10px; border: 1.5px solid var(--card-border);
}
.perm-item span { font-size: 13px; display: flex; align-items: center; gap: 8px; }
.switch { position: relative; width: 44px; height: 24px; flex-shrink: 0; }
.switch input { display: none; }
.slider { position: absolute; inset: 0; background: #333; border-radius: 24px; transition: 0.3s; cursor: pointer; }
.slider:before { content: ""; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: 0.3s ease; }
input:checked + .slider { background: #f39c12; }
input:checked + .slider:before { transform: translateX(20px); }

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
</style>

<div class="rh-container">
    <div class="top-bar">
        <div class="header-info">
            <h2><i class="fas fa-users"></i> Registro Profissional</h2>
            <p>Gestão completa de RH • Permissões • Auditoria</p>
        </div>
        <div class="header-actions">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="inputPesquisa" onkeyup="window.pesquisarFuncionario()" placeholder="Pesquisar por nome, BI, cargo...">
            </div>
            <button class="btn-new" onclick="window.abrirModalCadastro()">
                <i class="fas fa-user-plus"></i> NOVO REGISTRO
            </button>
        </div>
    </div>

    <div class="table-glass">
        <table id="tabelaFuncionarios">
            <thead>
                <tr>
                    <th>Funcionário</th>
                    <th class="col-pc">Cargo</th>
                    <th class="col-pc">Acesso</th>
                    <th class="col-tel">Telefone</th>
                    <th style="text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if($resultado && $resultado->num_rows > 0): ?>
                    <?php while($user = $resultado->fetch_assoc()): ?>
                    <tr class="main-row" id="row-<?php echo $user['id_sistema']; ?>">
                        <td>
                            <div class="user-cell">
                                <img class="user-avatar" 
                                     src="<?php echo $base_url; ?>uploads/<?php echo htmlspecialchars($user['foto_perfil'] ?? 'avatar.png'); ?>" 
                                     onerror="this.src='<?php echo $base_url; ?>uploads/avatar.png'"
                                     alt="Foto"
                                     loading="lazy">
                                <div>
                                    <span class="user-name"><?php echo htmlspecialchars($user['nome_completo']); ?></span>
                                    <span class="user-doc">BI: <?php echo htmlspecialchars($user['documento_id']); ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="col-pc"><?php echo htmlspecialchars($user['cargo']); ?></td>
                        <td class="col-pc">
                            <?php if (!empty($user['username'])): ?>
                                <span class="badge-nivel" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71;">
                                    <i class="fas fa-key"></i> <?php echo htmlspecialchars($user['nivel_acesso']); ?>
                                </span>
                            <?php else: ?>
                                <span class="badge-nivel" style="background: rgba(231, 76, 60, 0.1); color: #e74c3c; font-style: italic;">
                                    Apenas RH
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="col-tel">
                            <i class="fas fa-phone" style="color: var(--text-dim); font-size: 10px; margin-right: 5px;"></i>
                            <?php echo htmlspecialchars($user['telefone']); ?>
                        </td>
                        <td>
                            <div class="actions-group">
                                <button class="btn-action btn-view" 
                                        data-tooltip="Ver detalhes"
                                        onclick="window.verDetalhesFuncionario(<?php echo htmlspecialchars(json_encode($user)); ?>, this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-action btn-edit" 
                                        data-tooltip="Editar"
                                        onclick="window.editarFuncionario(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <?php if (!empty($user['username'])): ?>
                                <button class="btn-action btn-shield" 
                                        data-tooltip="Permissões"
                                        onclick="window.configurarAcessos(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                    <i class="fas fa-lock"></i>
                                </button>
                                <?php else: ?>
                                <button class="btn-action btn-shield" style="opacity: 0.3; cursor: not-allowed;" data-tooltip="Sem usuário ativo" disabled>
                                    <i class="fas fa-ban"></i>
                                </button>
                                <?php endif; ?>

                                <button class="btn-action btn-del" 
                                        data-tooltip="Eliminar"
                                        onclick="window.eliminarFuncionario(<?php echo $user['id_sistema']; ?>, '<?php echo htmlspecialchars(addslashes($user['nome_completo'])); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <p><strong>Nenhum funcionário encontrado</strong></p>
                                <p style="font-size: 13px;">Clique em "NOVO REGISTRO" para adicionar</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="modalCadastro" onclick="window.fecharModal(event, 'modalCadastro')">
    <div class="modal-content" onclick="event.stopPropagation()">
        <button class="close-modal" onclick="window.fecharModalForcado('modalCadastro')">&times;</button>
        <h2 id="modalTitle" style="color: var(--accent); margin-top:0; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-user-plus"></i> Registro de Funcionário
        </h2>
        
        <form id="formCadastro" onsubmit="window.salvarFuncionario(event)" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="id_sistema" id="id_sistema">
            
            <div class="section-title"><i class="fas fa-user"></i> Informação Pessoal</div>
            <div class="grid">
                <div class="input-group"><label>Nome Completo *</label><input type="text" name="nome_completo" id="nome_completo" required placeholder="Ex: João Silva"></div>
                <div class="input-group"><label>Nº do BI *</label><input type="text" name="documento_id" id="documento_id" required placeholder="Ex: 123456789LA001"></div>
                <div class="input-group"><label>Telefone *</label><input type="text" name="telefone" id="telefone" required placeholder="Ex: +244 9XX XXX XXX"></div>
                <div class="input-group"><label>Data Nascimento</label><input type="date" name="data_nascimento" id="data_nascimento"></div>
                <div class="input-group">
                    <label>Gênero</label>
                    <select name="sexo" id="sexo">
                        <option value="Masculino">Masculino</option>
                        <option value="Feminino">Feminino</option>
                    </select>
                </div>
                <div class="input-group"><label>E-mail</label><input type="email" name="email" id="email" placeholder="Ex: joao@empresa.com"></div>
                <div class="input-group full-width"><label>Morada Completa</label><textarea name="morada" id="morada" rows="2" placeholder="Ex: Rua X, Bairro Y, Cidade Z"></textarea></div>
            </div>

            <div class="section-title"><i class="fas fa-briefcase"></i> Atribuição Profissional</div>
            <div class="grid">
                <div class="input-group">
                    <label>Cargo *</label>
                    <select name="cargo" id="cargo" required>
                        <option value="">Selecionar...</option>
                        <option value="Operador de Caixa">Operador de Caixa</option>
                        <option value="Balconista">Balconista / Atendente</option>
                        <option value="Farmacêutico Responsável">Farmacêutico Responsável</option>
                        <option value="Gestor de Stock">Gestor de Stock</option>
                        <option value="Diretor Financeiro">Diretor Financeiro</option>
                        <option value="Administrador de TI">Administrador de TI</option>
                    </select>
                </div>
                
                <div class="input-group">
                    <label>Tipo de Contrato</label>
                    <select name="tipo_contrato" id="tipo_contrato">
                        <option value="">Selecionar...</option>
                        <option value="Efetivo">Efetivo</option>
                        <option value="Temporário">Temporário</option>
                        <option value="Estágio">Estágio</option>
                        <option value="Prestador de Serviços">Prestador de Serviços</option>
                    </select>
                </div>
                
                <div class="input-group">
                    <label>Departamento</label>
                    <select name="departamento" id="departamento">
                        <option value="Vendas">Vendas</option>
                        <option value="Logística">Logística / Armazém</option>
                        <option value="Financeiro">Financeiro</option>
                        <option value="Geral">Direção Geral</option>
                    </select>
                </div>
                <div class="input-group"><label>Filial</label><input type="text" name="filial" id="filial" value="Sede Principal"></div>
                <div class="input-group"><label>Estado Profissional</label>
                    <select name="estado_trabalho" id="estado_trabalho">
                        <option value="Ativo">Ativo</option>
                        <option value="Férias">Férias</option>
                        <option value="Suspenso">Suspenso</option>
                        <option value="Inativo">Inativo</option>
                    </select>
                </div>
                <div class="input-group"><label>Foto de Perfil</label><input type="file" name="foto_perfil" id="foto_perfil" accept="image/*"></div>
            </div>

            <div class="section-title" style="display: flex; justify-content: space-between; align-items: center; background: rgba(243, 156, 18, 0.1); border-left: 4px solid #f39c12;">
                <span><i class="fas fa-user-shield"></i> Credenciais e Acesso ao Sistema</span>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; color:#f39c12;">
                    Permitir Login? 
                    <input type="checkbox" id="permitir_login" name="permitir_login" value="1" onchange="window.alternarCamposAcesso(this.checked)">
                </label>
            </div>
            
            <div id="blocoAcesso" style="display: none; transition: all 0.3s ease;">
                <div class="grid">
                    <div class="input-group"><label>Nome de Utilizador *</label><input type="text" name="username" id="username" placeholder="Ex: joao.silva"></div>
                    <div class="input-group"><label>Senha *</label><input type="password" name="password" id="password" placeholder="Mínimo 4 caracteres"></div>
                    <div class="input-group">
                        <label>Nível de Acesso</label>
                        <select name="nivel_acesso" id="nivel_acesso">
                            <option value="Staff">Operacional (Staff)</option>
                            <option value="Técnico">Técnico (Stock/Farmácia)</option>
                            <option value="Gestão">Gestão (Financeiro)</option>
                            <option value="Admin">Administrador Master</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-save" id="btnSubmit" style="margin-top: 20px;">
                <i class="fas fa-save"></i> GRAVAR NO SERVIDOR
            </button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalPermissoes" onclick="window.fecharModal(event, 'modalPermissoes')">
    <div class="modal-content" style="max-width: 700px;" onclick="event.stopPropagation()">
        <button class="close-modal" onclick="window.fecharModalForcado('modalPermissoes')">&times;</button>
        <div style="text-align: center; margin-bottom: 25px;">
            <i class="fas fa-user-shield" style="font-size: 40px; color: #f39c12; margin-bottom: 10px;"></i>
            <h2 style="color: #f39c12; margin: 0; font-size: 22px;">🔐 Controlo de Permissões</h2>
            <p style="color: var(--text-dim); font-size: 13px; margin-top: 8px;" id="permUserLabel">Defina o que o utilizador pode acessar</p>
        </div>
        
        <form onsubmit="window.salvarPermissoes(event)" id="formPermissoes">
            <input type="hidden" name="id_funcionario" id="perm_id_funcionario">
            
            <div class="perm-grid">
                <div class="perm-item"><span><i class="fas fa-chart-pie" style="color:#3498db;"></i> Painel Dashboard</span><label class="switch"><input type="checkbox" name="ver_dashboard" id="p_dashboard" value="1"><span class="slider"></span></label></div>
                <div class="perm-item"><span><i class="fas fa-shopping-cart" style="color:#2ecc71;"></i> Terminal Vendas</span><label class="switch"><input type="checkbox" name="ver_vendas" id="p_vendas" value="1"><span class="slider"></span></label></div>
                <div class="perm-item"><span><i class="fas fa-boxes" style="color:#e67e22;"></i> Controlo Estoque</span><label class="switch"><input type="checkbox" name="ver_estoque" id="p_estoque" value="1"><span class="slider"></span></label></div>
                <div class="perm-item"><span><i class="fas fa-trash-alt" style="color:#e74c3c;"></i> Gerir Perdas</span><label class="switch"><input type="checkbox" name="ver_perdas" id="p_perdas" value="1"><span class="slider"></span></label></div>
                <div class="perm-item"><span><i class="fas fa-truck" style="color:#9b59b6;"></i> Fornecedores</span><label class="switch"><input type="checkbox" name="ver_fornecedores" id="p_fornecedores" value="1"><span class="slider"></span></label></div>
                <div class="perm-item"><span><i class="fas fa-users-cog" style="color:#1abc9c;"></i> Gerir Utilizadores</span><label class="switch"><input type="checkbox" name="gerir_usuarios" id="p_usuarios" value="1"><span class="slider"></span></label></div>
                <div class="perm-item"><span><i class="fas fa-wallet" style="color:#f1c40f;"></i> Gestão Financeira</span><label class="switch"><input type="checkbox" name="ver_financeiro" id="p_financeiro" value="1"><span class="slider"></span></label></div>
                <div class="perm-item"><span><i class="fas fa-file-invoice-dollar" style="color:#34495e;"></i> Relatórios Gerais</span><label class="switch"><input type="checkbox" name="ver_relatorios" id="p_relatorios" value="1"><span class="slider"></span></label></div>
                <div class="perm-item"><span><i class="fas fa-fingerprint" style="color:#95a5a6;"></i> Logs de Auditoria</span><label class="switch"><input type="checkbox" name="ver_logs" id="p_logs" value="1"><span class="slider"></span></label></div>
            </div>
            
            <button type="submit" class="btn-save" id="btnSubmitPermissoes" style="background: #f39c12; color: #000;">
                <i class="fas fa-shield-alt"></i> ATUALIZAR ACESSOS
            </button>
        </form>
    </div>
</div>

<script>
const BASE_URL = "/pharmora/modules/";

const DOM = {
    modalCadastro: document.getElementById('modalCadastro'),
    modalPermissoes: document.getElementById('modalPermissoes'),
    formCadastro: document.getElementById('formCadastro'),
    formPermissoes: document.getElementById('formPermissoes'),
    tableBody: document.getElementById('tableBody'),
    searchInput: document.getElementById('inputPesquisa')
};

// ================= ATIVAR/DESATIVAR CAMPOS DE ACESSO DINAMICAMENTE =================
window.alternarCamposAcesso = function(permitir) {
    const bloco = document.getElementById('blocoAcesso');
    const inputUser = document.getElementById('username');
    const inputPass = document.getElementById('password');
    const idSistema = document.getElementById('id_sistema').value;

    if (permitir) {
        bloco.style.display = 'block';
        inputUser.setAttribute('required', 'required');
        // Senha só é obrigatória no cadastro inicial, se for edição ela é opcional
        if (!idSistema) {
            inputPass.setAttribute('required', 'required');
        } else {
            inputPass.removeAttribute('required');
        }
    } else {
        bloco.style.display = 'none';
        inputUser.removeAttribute('required');
        inputPass.removeAttribute('required');
    }
};

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

function safeJsonParse(text) {
    try { return JSON.parse(text); } 
    catch (e) { 
        showToast('Erro ao processar resposta', 'error');
        return null;
    }
}

window.abrirModalCadastro = function() {
    DOM.formCadastro.reset();
    document.getElementById('id_sistema').value = "";
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Novo Funcionário';
    window.alternarCamposAcesso(false); // Por padrão inicia oculto
    DOM.modalCadastro.classList.add('active');
    document.body.style.overflow = 'hidden';
};

window.fecharModal = function(e, id) {
    if(e.target === document.getElementById(id)) window.fecharModalForcado(id);
};

window.fecharModalForcado = function(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
};

// ================= MAPEAMENTO NA EDIÇÃO =================
window.editarFuncionario = function(user) {
    user = user || {};
    DOM.formCadastro.reset();
    
    document.getElementById('id_sistema').value = user.id_sistema || '';
    document.getElementById('nome_completo').value = user.nome_completo || '';
    document.getElementById('documento_id').value = user.documento_id || '';
    document.getElementById('telefone').value = user.telefone || '';
    document.getElementById('data_nascimento').value = user.data_nascimento || '';
    document.getElementById('sexo').value = user.sexo || 'Masculino';
    document.getElementById('email').value = user.email || '';
    document.getElementById('morada').value = user.morada || '';
    document.getElementById('cargo').value = user.cargo || '';
    document.getElementById('departamento').value = user.departamento || 'Vendas';
    document.getElementById('tipo_contrato').value = user.tipo_contrato || '';
    document.getElementById('filial').value = user.filial || 'Sede Principal';
    document.getElementById('estado_trabalho').value = user.estado_trabalho || 'Ativo';
    
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Editar: ' + user.nome_completo;

    // Se o funcionário já possuir credenciais salvas no banco
    if (user.username) {
        document.getElementById('permitir_login').checked = true;
        document.getElementById('username').value = user.username;
        document.getElementById('nivel_acesso').value = user.nivel_acesso || 'Staff';
        window.alternarCamposAcesso(true);
    } else {
        document.getElementById('permitir_login').checked = false;
        window.alternarCamposAcesso(false);
    }

    DOM.modalCadastro.classList.add('active');
    document.body.style.overflow = 'hidden';
};

window.verDetalhesFuncionario = function(user, btnElement) {
    if (!user) return;
    const rowId = 'row-' + user.id_sistema;
    const expandId = 'expand-' + user.id_sistema;
    const mainRow = document.getElementById(rowId);
    if (!mainRow) return;
    
    let expandRow = document.getElementById(expandId);
    const icon = btnElement ? btnElement.querySelector('i') : null;
    
    if (expandRow) {
        if (expandRow.classList.contains('show')) {
            expandRow.classList.remove('show');
            if (icon) icon.className = 'fas fa-eye';
        } else {
            expandRow.classList.add('show');
            if (icon) icon.className = 'fas fa-eye-slash';
        }
        return;
    }
    
    expandRow = document.createElement('tr');
    expandRow.id = expandId;
    expandRow.className = 'expand-row show';
    
    const ultimoLogin = user.ultimo_login ? user.ultimo_login : 'Nunca';
    const hasAccount = user.username ? `<span style="color:#2ecc71;">Sim (${user.username})</span>` : '<span style="color:#e74c3c;">Não (Apenas RH)</span>';
    
    expandRow.innerHTML = `
        <td colspan="5">
    <div class="detail-grid">
        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-id-card"></i> ID Visual</span>
            <span class="detail-value" style="font-family: monospace; font-weight: 700;">${user.id_funcionario || 'N/A'}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-fingerprint"></i> ID Sistema</span>
            <span class="detail-value">${user.id_sistema}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-address-card"></i> Documento (BI)</span>
            <span class="detail-value">${user.documento_id || 'Não informado'}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-envelope"></i> E-mail Corporativo</span>
            <span class="detail-value">${user.email || 'Não informado'}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-phone"></i> Telefone</span>
            <span class="detail-value">${user.telefone || 'Não informado'}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-calendar-alt"></i> Data de Nascimento</span>
            <span class="detail-value">${user.data_nascimento ? user.data_nascimento.split('-').reverse().join('/') : 'Não informada'}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-venus-mars"></i> Gênero / Sexo</span>
            <span class="detail-value">${user.sexo || 'Não informado'}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-briefcase"></i> Cargo</span>
            <span class="detail-value">${user.cargo || 'Não informado'}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-building"></i> Departamento</span>
            <span class="detail-value">${user.departamento || 'Não informado'}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-file-contract"></i> Tipo de Contrato</span>
            <span class="detail-value">${user.tipo_contrato || 'Não informado'}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-map-signs"></i> Filial</span>
            <span class="detail-value">${user.filial || 'Não informada'}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-calendar-check"></i> Admissão no Sistema</span>
            <span class="detail-value">${user.data_entrada || 'Não informada'}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-user-tag"></i> Situação de Trabalho</span>
            <span class="detail-value">${user.estado_trabalho || 'Ativo'}</span>
        </div>
        <div class="detail-item"><span class="detail-label"><i class="fas fa-map-marker-alt"></i> Morada</span><span class="detail-value">${user.morada || 'Não informada'}</span></div>

        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-user-circle"></i> Utilizador (Username)</span>
            <span class="detail-value" style="color: var(--accent); font-weight: 600;">${user.username || 'Sem conta criada'}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-key"></i> ID Conta Usuário</span>
            <span class="detail-value">${user.id_usuario || 'N/A'}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-shield-alt"></i> Nível de Acesso</span>
            <span class="detail-value">${user.nivel_acesso || 'N/A'}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-toggle-on"></i> Estado da Conta</span>
            <span class="detail-value">${user.estado_conta || 'Inativa'}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-laptop-house"></i> Aparelho / Nodo</span>
            <span class="detail-value">${user.dispositivo_usado || 'Nenhum'}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-clock"></i> Último Acesso</span>
            <span class="detail-value">${user.ultimo_login || 'Nunca acessou'}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><i class="fas fa-hashtag"></i> Sessões Iniciadas</span>
            <span class="detail-value" style="font-weight: bold;">${user.numero_logins || 0}</span>
        </div>
    </div>
</td>
    `;
    mainRow.after(expandRow);
    if (icon) icon.className = 'fas fa-eye-slash';
};

window.pesquisarFuncionario = function() {
    const term = DOM.searchInput.value.toLowerCase().trim();
    const rows = document.querySelectorAll('#tableBody tr.main-row');
    rows.forEach(row => {
        const match = !term || row.textContent.toLowerCase().includes(term);
        row.style.display = match ? '' : 'none';
    });
};

window.salvarFuncionario = async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmit');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> GRAVANDO...';

    const formData = new FormData(e.target);

    // Captura o checkbox real na árvore do DOM
    const checkboxLogin = document.getElementById('permitir_login');
    
    if (checkboxLogin && checkboxLogin.checked) {
        formData.set('permitir_login', '1');
    } else {
        // Se o login não for permitido, limpa os campos explicitamente para prevenir strings vazias
        formData.set('permitir_login', '0');
        formData.set('username', '');
        formData.set('password', '');
        formData.set('nivel_acesso', '');
    }

    try {
        const resp = await fetch(BASE_URL + 'processa_cadastro.php', { method: 'POST', body: formData });
        const text = await resp.text();
        const res = safeJsonParse(text);
        if (res && res.success) {
            showToast('Salvo com sucesso!', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(res?.message || 'Erro ao processar requisição', 'error');
        }
    } catch(err) {
        showToast('Erro de rede com o servidor', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
};

window.configurarAcessos = function(user) {
    if (!user) return;
    document.getElementById('perm_id_funcionario').value = user.id_sistema || '';
    document.getElementById('permUserLabel').innerText = `Acessos para: ${user.nome_completo}`;
    document.querySelectorAll('#formPermissoes input[type="checkbox"]').forEach(cb => cb.checked = false);
    try {
        let perms = typeof user.permissoes_especiais === 'string' ? JSON.parse(user.permissoes_especiais) : user.permissoes_especiais;
        if (perms) {
            const mapping = { p_dashboard:'ver_dashboard', p_vendas:'ver_vendas', p_estoque:'ver_estoque', p_perdas:'ver_perdas', p_fornecedores:'ver_fornecedores', p_usuarios:'gerir_usuarios', p_financeiro:'ver_financeiro', p_relatorios:'ver_relatorios', p_logs:'ver_logs' };
            Object.entries(mapping).forEach(([id, key]) => {
                const el = document.getElementById(id);
                if(el && perms[key]) el.checked = true;
            });
        }
    } catch(err) {}
    DOM.modalPermissoes.classList.add('active');
    document.body.style.overflow = 'hidden';
};

window.salvarPermissoes = async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitPermissoes');
    btn.disabled = true;
    try {
        const resp = await fetch(BASE_URL + 'processa_permissoes.php', { method: 'POST', body: new FormData(e.target) });
        const res = safeJsonParse(await resp.text());
        if (res && res.success) {
            showToast('Permissões salvas!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(res?.message || 'Erro', 'error');
        }
    } catch(err) { showToast('Erro de comunicação', 'error'); }
    btn.disabled = false;
};

window.eliminarFuncionario = async function(id, nome) {
    if(!confirm(`Deseja realmente eliminar o registro de ${nome}?`)) return;
    try {
        const resp = await fetch(BASE_URL + 'processa_eliminar.php?id=' + id);
        const res = safeJsonParse(await resp.text());
        if (res && res.success) {
            document.getElementById('row-' + id)?.remove();
            document.getElementById('expand-' + id)?.remove();
            showToast('Eliminado do servidor com sucesso');
        } else { showToast(res?.message || 'Erro ao eliminar', 'error'); }
    } catch(err) { showToast('Erro', 'error'); }
};
</script>