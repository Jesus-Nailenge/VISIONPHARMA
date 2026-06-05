<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>VisionPharma | Centro de Comando</title>
    
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
	<link rel="stylesheet" href="/pharmora/global.css">

<style>
    :root {
        --bg-color: #020202;
        --card-bg: rgba(255, 255, 255, 0.03);
        --card-border: rgba(255, 255, 255, 0.08);
        --text-main: #ffffff;
        --text-dim: #888888;
        --accent: #00ffcc;
        --danger: #ff4444;
        --input-fill: rgba(255, 255, 255, 0.05);
        --logo-filter: invert(1) brightness(1.8);
        --sidebar-width: 260px;
    }

    [data-theme="light"] {
        --bg-color: #f0f0f2;
        --card-bg: rgba(255, 255, 255, 0.7);
        --card-border: rgba(0, 0, 0, 0.06);
        --text-main: #000000;
        --text-dim: #666666;
        --accent: #008f72;
        --input-fill: rgba(0, 0, 0, 0.04);
        --logo-filter: none;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Inter", sans-serif; }

    body {
        background-color: var(--bg-color); color: var(--text-main);
        height: 100vh; display: flex; overflow: hidden;
        transition: background 0.4s ease;
    }

    /* TEXTURA CINEMATOGRÁFICA */
    body::before {
        content: ""; position: fixed; inset: 0; z-index: 0;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        opacity: 0.03; pointer-events: none;
    }

    /* SIDEBAR (MENU LATERAL) */
    .sidebar {
        width: var(--sidebar-width); height: 100%;
        background: var(--card-bg); border-right: 1px solid var(--card-border);
        backdrop-filter: blur(20px); z-index: 50;
        display: flex; flex-direction: column; transition: 0.4s ease;
        flex-shrink: 0; /* Impede que a tela esprema o menu */
    }

    .sidebar.collapsed { width: 80px; }
    
    .brand-area {
        height: 80px; display: flex; align-items: center; justify-content: center;
        border-bottom: 1px solid var(--card-border);
    }
    .brand-logo { width: 140px; filter: var(--logo-filter); transition: 0.3s; }
    .sidebar.collapsed .brand-logo { width: 40px; }

    .menu-wrapper { flex: 1; overflow-y: auto; padding: 20px 0; }
    .menu-wrapper::-webkit-scrollbar { width: 4px; }
    .menu-wrapper::-webkit-scrollbar-thumb { background: var(--card-border); }

    .menu-category {
        font-size: 10px; color: var(--text-dim); text-transform: uppercase;
        letter-spacing: 2px; margin: 15px 25px 10px; font-weight: 700;
    }
    .sidebar.collapsed .menu-category { display: none; }

    .menu-item {
        display: flex; align-items: center; padding: 14px 25px;
        color: var(--text-main); text-decoration: none; font-size: 14px;
        border-left: 3px solid transparent; transition: 0.3s; cursor: pointer;
    }
    .menu-item i { width: 25px; font-size: 18px; color: var(--text-dim); transition: 0.3s; }
    .menu-item span { margin-left: 10px; white-space: nowrap; transition: opacity 0.3s; }
    .sidebar.collapsed .menu-item span { opacity: 0; pointer-events: none; }

    .menu-item:hover, .menu-item.active { background: var(--input-fill); border-left-color: var(--accent); }
    .menu-item:hover i, .menu-item.active i { color: var(--accent); }

    /* ÁREA PRINCIPAL (MAIN STAGE) - AQUI ESTÁ A MÁGICA */
    .main-content {
        flex: 1; 
        display: flex; 
        flex-direction: column; 
        position: relative; 
        z-index: 10;
        min-width: 0; /* CRÍTICO: Impede que tabelas internas criem barra de rolagem horizontal na tela inteira */
        transform: translateZ(0); /* CRÍTICO: Prende os modais com position:fixed DENTRO desta área. Nunca mais vaza para o sidebar! */
    }

    /* TOPBAR */
    .topbar {
        height: 80px; display: flex; justify-content: space-between; align-items: center;
        padding: 0 30px; background: var(--card-bg); border-bottom: 1px solid var(--card-border);
        backdrop-filter: blur(10px); flex-shrink: 0;
    }

    .top-left { display: flex; align-items: center; gap: 20px; }
    .toggle-btn { background: none; border: none; color: var(--text-main); font-size: 20px; cursor: pointer; }
    .page-title { font-size: 18px; font-weight: 600; letter-spacing: 1px; }
    .top-right { display: flex; align-items: center; gap: 25px; }

    /* NOTIFICAÇÕES */
    .notification-bell { position: relative; cursor: pointer; font-size: 20px; color: var(--text-dim); transition: 0.3s; }
    .notification-bell:hover { color: var(--text-main); }
    .badge {
        position: absolute; top: -5px; right: -8px; background: var(--danger);
        color: white; font-size: 9px; font-weight: bold; width: 16px; height: 16px;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        border: 2px solid var(--bg-color);
    }

    /* PERFIL DO USUÁRIO */
    .user-profile { display: flex; align-items: center; gap: 15px; cursor: pointer; padding: 5px 10px; border-radius: 12px; transition: 0.3s; }
    .user-profile:hover { background: var(--input-fill); }
    .user-info { text-align: right; }
    .user-name { font-size: 13px; font-weight: 700; color: var(--text-main); }
    .user-role { font-size: 11px; color: var(--accent); letter-spacing: 1px; text-transform: uppercase; }
    .user-avatar { width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 1px solid var(--card-border); }

    /* VIEWPORT (ONDE AS TELAS ABREM) */
    .viewport {
        flex: 1; padding: 30px; overflow-y: auto; overflow-x: hidden; position: relative; min-width: 0;
    }

    /* RODAPÉ DO SISTEMA */
    footer {
        padding: 15px; text-align: center; border-top: 1px solid var(--card-border);
        background: var(--card-bg); z-index: 10; flex-shrink: 0;
    }
    .footer-line { font-size: 9px; letter-spacing: 2px; color: var(--text-dim); line-height: 1.8; font-weight: 500; }
    .footer-line b { color: var(--text-main); opacity: 0.8; }

    /* Loader */
    .loader { width: 40px; height: 40px; border: 3px solid var(--card-border); border-top-color: var(--accent); border-radius: 50%; animation: spin 1s linear infinite; margin: 100px auto; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* RESPONSIVIDADE EXTREMA DO DASHBOARD */
    @media (max-width: 768px) {
        .topbar { padding: 0 15px; }
        .viewport { padding: 15px; }
        .page-title { display: none; } /* Oculta título longo em celulares pequenos */
        .user-info { display: none; } /* Oculta nome/cargo, deixa só o avatar */
    }
</style>
</head>
<body data-theme="dark">

    <nav class="sidebar" id="sidebar">
        <div class="brand-area">
            <h2 id="logo-text" style="letter-spacing: 2px;">VISION<span style="color:var(--accent)">PHARMA</span></h2>
        </div>
        <div class="menu-wrapper" id="menu-container"></div>
    </nav>

    <main class="main-content">
        <header class="topbar">
            <div class="top-left">
                <button class="toggle-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h1 class="page-title" id="active-page-title">Dashboard Inicial</h1>
            </div>

            <div class="top-right" style="display:flex; align-items:center; gap:20px;">
                <button onclick="toggleTheme()" style="background:none; border:none; color:var(--text-dim); cursor:pointer;"><i class="fas fa-moon" id="theme-icon"></i></button>
                
                <div class="user-profile">
                    <div class="user-info">
                        <div class="user-name" id="ui-username">Carregando...</div>
                        <div class="user-role" id="ui-userrole">...</div>
                    </div>
                    <img src="assets/imagem/default.jpg" alt="Avatar" class="user-avatar" id="ui-avatar">
                </div>
            </div>
        </header>

        <div class="viewport" id="viewport-stage">
            </div>

        <footer>
            <div class="footer-line">VISIONPHARMA V1.0.0 • © 2026</div>
        </footer>
		<!-- Modal de Confirmação Global -->
<div id="modal-confirm" class="modal-confirm-overlay" style="display: none;">
    <div class="modal-confirm-box">
        <div class="modal-confirm-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3 id="confirm-title">Confirmar Ação</h3>
        <p id="confirm-msg">Você tem certeza que deseja prosseguir com esta operação?</p>
        <div class="modal-confirm-buttons">
            <button class="btn-confirm-cancel" onclick="closeConfirmModal()">Cancelar</button>
            <button id="btn-confirm-execute" class="btn-confirm-action">Confirmar</button>
        </div>
    </div>
</div>
    </main>

    <script>
    // 1. INJEÇÃO DE DADOS DO SERVIDOR (PHP -> JS)
    // Aqui pegamos o que está na sua $_SESSION do XAMPP
    const permissoesServidor = <?php echo json_encode($_SESSION['permissoes']); ?>;
    const nomeUsuario = "<?php echo $_SESSION['usuario_nome']; ?>";
    const cargoUsuario = "<?php echo $_SESSION['usuario_cargo']; ?>";
    const fotoUsuario = "<?php echo $_SESSION['usuario_foto'] ?? 'assets/imagem/default.jpg'; ?>";

    const systemMenu = [
        { type: 'category', title: 'Operacional' },
        { id: 'painel', icon: 'fa-chart-pie', text: 'Painel Principal', perm: 'ver_dashboard' },
        { id: 'vendas', icon: 'fa-shopping-cart', text: 'Terminal de Vendas', perm: 'ver_vendas' },
        { id: 'perdas', icon: 'fa-trash-alt', text: 'Gestão de Perdas', perm: 'ver_perdas' },
        
        { type: 'category', title: 'Administração' },
        { id: 'funcionarios', icon: 'fa-users-cog', text: 'Equipa & Acessos', perm: 'gerir_usuarios' },
        { id: 'estoque', icon: 'fa-boxes', text: 'Stock', perm: 'ver_estoque' },
        { id: 'fornecedor', icon: 'fa-truck', text: 'Fornecedores', perm: 'ver_fornecedores' },
        
        { type: 'category', title: 'Sair' },
        { id: 'logout', icon: 'fa-sign-out-alt', text: 'Sair do Sistema', perm: 'ver_dashboard', isAction: true }
    ];

    function initDashboard() {
        // Preenche a interface com dados do servidor
        document.getElementById('ui-username').textContent = nomeUsuario;
        document.getElementById('ui-userrole').textContent = cargoUsuario;
        document.getElementById('ui-avatar').src = fotoUsuario;

        // Monta o menu baseado nas permissões reais do banco
        buildMenu(permissoesServidor);
        loadModule('dashboard', 'Painel Principal'); 
    }

    function buildMenu(userPerms) {
        const container = document.getElementById('menu-container');
        if (!container) return;
        container.innerHTML = '';

        systemMenu.forEach(item => {
            if (item.type === 'category') {
                const cat = document.createElement('div');
                cat.className = 'menu-category';
                cat.textContent = item.title;
                container.appendChild(cat);
            } else {
                // Checa se a permissão existe no array e se é verdadeira (1 ou true)
                const temAcesso = userPerms && (userPerms[item.perm] == 1 || userPerms[item.perm] === true);
                
                if (temAcesso) {
                    const link = document.createElement('a');
                    link.className = 'menu-item';
                    link.id = `menu-${item.id}`;
                    link.innerHTML = `<i class="fas ${item.icon}"></i><span>${item.text}</span>`;
                    link.onclick = () => item.isAction ? handleLogout() : loadModule(item.id, item.text);
                    container.appendChild(link);
                }
            }
        });
    }

    async function loadModule(moduleId, title) {
        const viewport = document.getElementById('viewport-stage');
        const titleEl = document.getElementById('active-page-title');
        
        document.querySelectorAll('.menu-item').forEach(el => el.classList.remove('active'));
        const activeMenu = document.getElementById(`menu-${moduleId}`);
        if(activeMenu) activeMenu.classList.add('active');

        titleEl.textContent = title;
        viewport.innerHTML = '<div class="loader"></div>';

        try {
            const response = await fetch(`modules/${moduleId}.php`);
            if (!response.ok) throw new Error();
            const html = await response.text();
            viewport.innerHTML = html;

            const scripts = viewport.querySelectorAll("script");
            scripts.forEach(oldScript => {
                const newScript = document.createElement("script");
                newScript.text = oldScript.text;
                document.body.appendChild(newScript).parentNode.removeChild(newScript);
            });
        } catch (err) {
            viewport.innerHTML = `<div style="text-align:center; padding:50px;">
                <i class="fas fa-exclamation-triangle" style="font-size:40px; color:var(--danger)"></i>
                <p style="margin-top:20px;">O módulo <b>${moduleId}.php</b> não foi encontrado.</p>
            </div>`;
        }
    }

    function toggleSidebar() { document.getElementById('sidebar').classList.toggle('collapsed'); }

    function toggleTheme() {
        const isDark = document.body.getAttribute('data-theme') === 'dark';
        document.body.setAttribute('data-theme', isDark ? 'light' : 'dark');
        document.getElementById('theme-icon').className = isDark ? 'fas fa-sun' : 'fas fa-moon';
    }

    async function handleLogout() {
        if(confirm("Deseja encerrar a sessão?")) {
            // No XAMPP, redirecionamos para o PHP que mata a sessão
            window.location.href = "logout.php";
        }
    }

    window.onload = initDashboard;
</script>
</body>
</html>