<?php
include_once("../config_api.php");

$sql = "SELECT * FROM funcionarios ORDER BY id_sistema DESC"; 
$resultado = $conn->query($sql);

$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/pharmora/";
?>

<style>
    :root {
        --accent: #00ffcc;
        --card-bg: rgba(20, 20, 20, 0.6);
        --card-border: rgba(255, 255, 255, 0.1);
        --text-main: #eee;
        --text-dim: #888;
    }

    .rh-container { animation: fadeIn 0.4s ease; color: var(--text-main); font-family: 'Inter', sans-serif; }
    
    /* --- CABEÇALHO E PESQUISA --- */
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
    
    .search-box { position: relative; width: 100%; max-width: 300px; }
    .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-dim); }
    .search-box input { 
        width: 100%; background: rgba(0,0,0,0.5); border: 1px solid var(--card-border); 
        color: #fff; padding: 12px 15px 12px 40px; border-radius: 12px; outline: none; 
        transition: 0.3s; font-size: 13px; font-family: inherit;
    }
    .search-box input:focus { border-color: var(--accent); box-shadow: 0 0 15px rgba(0,255,204,0.15); }
    .search-box input::placeholder { color: #555; }

    /* --- TABELA E LAYOUT --- */
    .table-glass { 
        width: 100%; border-collapse: collapse; background: var(--card-bg); 
        border-radius: 20px; overflow: hidden; border: 1px solid var(--card-border); 
        backdrop-filter: blur(15px); margin-bottom: 30px;
    }
    table { width: 100%; border-collapse: collapse; }
    th { background: rgba(255,255,255,0.03); padding: 18px 15px; text-align: left; color: var(--text-dim); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }
    td { padding: 12px 15px; border-bottom: 1px solid var(--card-border); font-size: 13.5px; vertical-align: middle; }

    .col-pc, .col-large { display: none; }
    @media (min-width: 768px) { .col-pc { display: table-cell; } .top-bar { flex-wrap: nowrap; } }
    @media (min-width: 1200px) { .col-large { display: table-cell; } }

    .details-row { display: none; background: rgba(0,0,0,0.3); }
    .details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; padding: 20px; }
    .info-card { border-left: 2px solid var(--accent); padding-left: 10px; }
    .info-card label { display: block; font-size: 9px; color: var(--text-dim); text-transform: uppercase; font-weight: 800; }
    .info-card span { font-size: 12px; color: var(--text-main); }

    /* --- BOTÕES --- */
    .btn-action { 
        width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--card-border);
        background: rgba(255,255,255,0.05); color: var(--text-dim); cursor: pointer; transition: 0.2s;
    }
    .btn-action:hover { color: var(--accent); border-color: var(--accent); background: rgba(0, 255, 204, 0.1); }
    .btn-del:hover { color: #ff4444; border-color: #ff4444; background: rgba(255, 68, 68, 0.1); }
    .btn-shield:hover { color: #f39c12; border-color: #f39c12; background: rgba(243, 156, 18, 0.1); }

    /* --- MODAIS --- */
    .modal-overlay {
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); z-index: 9999;
        justify-content: center; align-items: center; padding: 20px;
    }
    .modal-content {
        width: 100%; background: #0a0a0a; border-radius: 20px;
        border: 1px solid #222; max-height: 90vh; overflow-y: auto; padding: 30px;
        position: relative; animation: slideUp 0.3s ease;
    }
    .close-modal { position: absolute; top: 20px; right: 20px; color: #666; cursor: pointer; font-size: 24px; }
    .close-modal:hover { color: var(--accent); }
    
    .section-title { font-size: 10px; color: var(--accent); text-transform: uppercase; margin: 20px 0 10px; border-bottom: 1px solid #222; padding-bottom: 5px; }
    .input-group { display: flex; flex-direction: column; margin-bottom: 12px; }
    .input-group label { font-size: 11px; color: #777; margin-bottom: 5px; }
    .input-group input, .input-group select, .input-group textarea { background: #000; border: 1px solid #222; padding: 10px; border-radius: 8px; color: #fff; outline: none; }
    .input-group input:focus, .input-group select:focus { border-color: var(--accent); }
    
    .btn-save { width: 100%; padding: 15px; background: var(--accent); color: #000; border: none; border-radius: 10px; font-weight: 900; cursor: pointer; margin-top: 20px; transition: opacity 0.3s; }
    .btn-save:disabled { opacity: 0.5; cursor: not-allowed; }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>

<div class="rh-container">
    
    <div class="top-bar">
        <div>
            <h2 style="color: var(--accent); font-weight: 800; margin: 0;">Equipa VisionPharma</h2>
            <p style="color: var(--text-dim); font-size: 12px; margin-top: 5px;">Gestão de funcionários e permissões.</p>
        </div>
        
        <div style="display: flex; gap: 15px; align-items: center; width: 100%; justify-content: flex-end;">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="inputPesquisa" onkeyup="window.pesquisarFuncionario()" placeholder="Procurar por nome, BI ou cargo...">
            </div>

            <button onclick="window.abrirModalCadastro()" style="background: var(--accent); color: #000; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 900; cursor: pointer; white-space: nowrap;">
                <i class="fas fa-user-plus"></i> NOVO
            </button>
        </div>
    </div>

    <div class="table-glass">
        <table id="tabelaFuncionarios">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">
                        <button style="background:none; border:none; color:var(--accent); cursor:pointer;" onclick="window.toggleAllDetails()">
                            <i class="fas fa-expand-alt" id="btn-master"></i>
                        </button>
                    </th>
                    <th>Funcionário</th>
                    <th class="col-pc">Cargo</th>
                    <th class="col-pc">Nível</th>
                    <th class="col-large">Telefone</th>
                    <th style="text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if($resultado && $resultado->num_rows > 0): ?>
                    <?php while($user = $resultado->fetch_assoc()): ?>
                    <tr class="main-row" id="row-<?php echo $user['id_sistema']; ?>">
                        <td style="text-align: center;">
                            <button style="background:none; border:none; color:var(--accent); cursor:pointer;" onclick="window.toggleDetails(<?php echo $user['id_sistema']; ?>)">
                                <i class="fas fa-plus-square" id="icon-<?php echo $user['id_sistema']; ?>"></i>
                            </button>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="<?php echo $base_url; ?>uploads/<?php echo htmlspecialchars($user['foto_perfil'] ?? 'avatar.png'); ?>" 
                                     onerror="this.src='<?php echo $base_url; ?>uploads/avatar.png'"
                                     style="width: 38px; height: 38px; border-radius: 8px; object-fit: cover;">
                                <div>
                                    <strong style="display: block; font-size: 13px;"><?php echo htmlspecialchars($user['nome_completo']); ?></strong>
                                    <small style="color: var(--text-dim); font-size: 10px;">BI: <?php echo htmlspecialchars($user['documento_id']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="col-pc"><?php echo htmlspecialchars($user['cargo']); ?></td>
                        <td class="col-pc">
                            <span style="background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 4px; font-size: 11px; border: 1px solid rgba(255,255,255,0.1);">
                                <?php echo htmlspecialchars($user['nivel_acesso']); ?>
                            </span>
                        </td>
                        <td class="col-large"><?php echo htmlspecialchars($user['telefone']); ?></td>
                        <td>
                            <div style="display: flex; justify-content: center; gap: 5px;">
                                <button class="btn-action" onclick="window.editarFuncionario(<?php echo htmlspecialchars(json_encode($user)); ?>)"><i class="fas fa-edit"></i></button>
                                <button class="btn-action btn-shield" onclick="window.configurarAcessos(<?php echo $user['id_sistema']; ?>, '<?php echo htmlspecialchars($user['nivel_acesso']); ?>')"><i class="fas fa-shield-alt"></i></button>
                                <button class="btn-action btn-del" onclick="window.eliminarFuncionario(<?php echo $user['id_sistema']; ?>)"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>

                    <tr class="details-row" id="details-<?php echo $user['id_sistema']; ?>">
                        <td colspan="6">
                            <div class="details-grid">
                                <div class="info-card"><label>Email</label><span><?php echo htmlspecialchars($user['email']); ?></span></div>
                                <div class="info-card"><label>Departamento</label><span><?php echo htmlspecialchars($user['departamento']); ?></span></div>
                                <div class="info-card"><label>Morada</label><span><?php echo htmlspecialchars($user['morada']); ?></span></div>
                                <div class="info-card"><label>Utilizador</label><span>@<?php echo htmlspecialchars($user['username']); ?></span></div>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr id="emptyRow"><td colspan="6" style="text-align:center; color:var(--text-dim); padding:20px;">Nenhum funcionário encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="modalCadastro" onclick="window.fecharModal(event, 'modalCadastro')">
    <div class="modal-content" style="max-width: 800px;">
        <span class="close-modal" onclick="window.fecharModalForcado('modalCadastro')">&times;</span>
        <h2 id="modalTitle" style="color: var(--accent);">Registro de Funcionário</h2>
        
        <form id="formCadastro" onsubmit="window.salvarFuncionario(event)" enctype="multipart/form-data">
            <input type="hidden" name="id_sistema" id="id_sistema">
            
            <div class="section-title">👤 Informação Pessoal</div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div class="input-group"><label>Nome Completo</label><input type="text" name="nome_completo" id="nome_completo" required></div>
                <div class="input-group"><label>Nº BI</label><input type="text" name="documento_id" id="documento_id" required></div>
                <div class="input-group"><label>Telefone</label><input type="text" name="telefone" id="telefone" required></div>
                <div class="input-group"><label>E-mail</label><input type="email" name="email" id="email"></div>
            </div>

            <div class="section-title">💼 Atribuição Profissional</div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div class="input-group">
                    <label>Cargo</label>
                    <select name="cargo" id="cargo">
                        <option value="Operador de Caixa">Operador de Caixa</option>
                        <option value="Balconista">Balconista / Atendente</option>
                        <option value="Farmacêutico Responsável">Farmacêutico Responsável</option>
                        <option value="Gestor de Stock">Gestor de Stock</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>Departamento</label>
                    <select name="departamento" id="departamento">
                        <option value="Vendas">Vendas</option>
                        <option value="Logística">Logística</option>
                        <option value="Financeiro">Financeiro</option>
                    </select>
                </div>
                <div class="input-group"><label>Username</label><input type="text" name="username" id="username" required></div>
                <div class="input-group"><label>Senha (Deixe vazio para manter)</label><input type="password" name="password"></div>
            </div>
            
            <button type="submit" class="btn-save" id="btnSubmit">SALVAR DADOS</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalPermissoes" onclick="window.fecharModal(event, 'modalPermissoes')">
    <div class="modal-content" style="max-width: 400px; border-color: rgba(243, 156, 18, 0.5); box-shadow: 0 10px 30px rgba(243, 156, 18, 0.1);">
        <span class="close-modal" onclick="window.fecharModalForcado('modalPermissoes')">&times;</span>
        <div style="text-align: center; margin-bottom: 20px;">
            <i class="fas fa-shield-alt" style="font-size: 30px; color: #f39c12; margin-bottom: 10px;"></i>
            <h2 style="color: #f39c12; margin: 0;">Controlo de Acesso</h2>
            <p style="color: var(--text-dim); font-size: 11px; margin-top: 5px;">Defina as permissões do sistema para este colaborador.</p>
        </div>
        
        <form onsubmit="window.salvarPermissoes(event)" id="formPermissoes">
            <input type="hidden" name="id_funcionario_permissao" id="id_funcionario_permissao">
            
            <div class="input-group">
                <label>Nível de Acesso no Sistema</label>
                <select name="nivel_acesso" id="permissao_nivel" required style="border-color: #333; font-size: 14px; padding: 12px;">
                    <option value="Staff">Staff (Acesso Básico)</option>
                    <option value="Técnico">Técnico (Operacional/Stock)</option>
                    <option value="Gestão">Gestão (Acesso a Relatórios)</option>
                    <option value="Admin">Administrador (Controlo Total)</option>
                </select>
            </div>
            
            <button type="submit" class="btn-save" id="btnSubmitPermissoes" style="background: #f39c12; color: #000;">ATUALIZAR PERMISSÕES</button>
        </form>
    </div>
</div>

<script>
    window.allExpanded = false;

    // --- NOVA FUNÇÃO: PESQUISA EM TEMPO REAL ---
    window.pesquisarFuncionario = function() {
        let input = document.getElementById('inputPesquisa').value.toLowerCase();
        let rows = document.querySelectorAll('tr.main-row'); // Pega apenas as linhas principais
        
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            let id = row.id.replace('row-', '');
            let detailsRow = document.getElementById('details-' + id);
            
            if(text.includes(input)) {
                row.style.display = ''; // Mostra se bater com a pesquisa
            } else {
                row.style.display = 'none'; // Esconde se não bater
                if(detailsRow) detailsRow.style.display = 'none'; // Esconde os detalhes também
                
                // Reseta o ícone se o detalhe estava aberto
                let icon = document.getElementById('icon-' + id);
                if(icon) {
                    icon.classList.remove('fa-minus-square');
                    icon.classList.add('fa-plus-square');
                }
            }
        });
    };

    // --- CONTROLO VISUAL ---
    window.toggleDetails = function(id) {
        const row = document.getElementById('details-' + id);
        const icon = document.getElementById('icon-' + id);
        if(row && icon) {
            row.style.display = (row.style.display === 'table-row') ? 'none' : 'table-row';
            icon.classList.toggle('fa-plus-square');
            icon.classList.toggle('fa-minus-square');
        }
    };

    window.toggleAllDetails = function() {
        window.allExpanded = !window.allExpanded;
        document.querySelectorAll('.details-row').forEach(r => {
            // Só expande se a linha principal estiver visível (respeita a pesquisa)
            let id = r.id.replace('details-', '');
            let mainRow = document.getElementById('row-' + id);
            if (mainRow.style.display !== 'none') {
                r.style.display = window.allExpanded ? 'table-row' : 'none';
            }
        });
        const btnMaster = document.getElementById('btn-master');
        if(btnMaster) {
            btnMaster.className = window.allExpanded ? 'fas fa-compress-alt' : 'fas fa-expand-alt';
        }
    };

    // --- CONTROLO DOS MODAIS ---
    window.abrirModalCadastro = function() {
        document.getElementById('formCadastro').reset();
        document.getElementById('id_sistema').value = "";
        document.getElementById('modalTitle').innerText = "Novo Funcionário";
        document.getElementById('modalCadastro').style.display = 'flex';
    };

    window.fecharModal = function(event, modalId) {
        if (event.target.id === modalId) document.getElementById(modalId).style.display = 'none';
    };

    window.fecharModalForcado = function(modalId) {
        document.getElementById(modalId).style.display = 'none';
    };

    window.editarFuncionario = function(dados) {
        document.getElementById('id_sistema').value = dados.id_sistema;
        document.getElementById('nome_completo').value = dados.nome_completo;
        document.getElementById('documento_id').value = dados.documento_id;
        document.getElementById('telefone').value = dados.telefone;
        document.getElementById('email').value = dados.email;
        document.getElementById('username').value = dados.username;
        document.getElementById('cargo').value = dados.cargo;
        if(dados.departamento) document.getElementById('departamento').value = dados.departamento;
        
        document.getElementById('modalTitle').innerText = "Editar Colaborador";
        document.getElementById('modalCadastro').style.display = 'flex';
    };

    window.configurarAcessos = function(id, nivelAtual) {
        document.getElementById('id_funcionario_permissao').value = id;
        document.getElementById('permissao_nivel').value = nivelAtual || 'Staff';
        document.getElementById('modalPermissoes').style.display = 'flex';
    };

// --- REQUISIÇÕES AJAX (FETCH) ---
    window.salvarFuncionario = async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmit');
        btn.disabled = true; btn.innerText = "A processar...";

        const formData = new FormData(e.target);
        
        try {
            const response = await fetch('processa_cadastro.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            if(result.success) {
                alert(result.message);
                if(typeof carregarPagina === 'function') carregarPagina('funcionarios.php'); else location.reload();
            } else alert("Erro: " + result.message);
        } catch (err) { alert("Falha de comunicação."); console.error(err); } 
        finally { btn.disabled = false; btn.innerText = "SALVAR DADOS"; }
    };

    window.salvarPermissoes = async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitPermissoes');
        btn.disabled = true; btn.innerText = "A atualizar...";

        const formData = new FormData(e.target);

        try {
            const response = await fetch('processa_permissoes.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            if(result.success) {
                alert(result.message); // Exibe o sucesso
                window.fecharModalForcado('modalPermissoes');
                if(typeof carregarPagina === 'function') carregarPagina('funcionarios.php'); else location.reload();
            } else alert("Erro: " + result.message);
        } catch (err) { alert("Erro na comunicação de permissões."); } 
        finally { btn.disabled = false; btn.innerText = "ATUALIZAR PERMISSÕES"; }
    };

    window.eliminarFuncionario = async function(id) {
        if(!confirm("CUIDADO: Tem certeza que deseja eliminar este funcionário permanentemente?")) return;
        
        try {
            const response = await fetch('processa_eliminar.php?id=' + id);
            const result = await response.json();
            if(result.success) {
                const linha = document.getElementById('row-' + id);
                const detalhes = document.getElementById('details-' + id);
                if(linha) linha.remove();
                if(detalhes) detalhes.remove();
            } else alert("Erro ao remover: " + result.message);
        } catch (e) { alert("Falha no servidor."); }
    };