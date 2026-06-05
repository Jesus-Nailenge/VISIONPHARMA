<?php
/**
 * PHARMORA API - Sistema de Auditoria e Logs Permanente
 * Reestruturado para Visualização e Gravação
 */

require_once("../config_api.php");

// 1. LÓGICA DE GRAVAÇÃO (POST) - Mantida a lógica original
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/json; charset=UTF-8");
    
    $id_funcionario = isset($_POST['id_funcionario']) ? intval($_POST['id_funcionario']) : null;
    $acao           = trim($_POST['acao'] ?? 'ACAO_NAO_DEFINIDA');
    $detalhes       = trim($_POST['detalhes'] ?? '');
    $modulo         = trim($_POST['modulo'] ?? 'Geral');
    $ip_origem      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (empty($detalhes)) {
        echo json_encode(["success" => false, "message" => "O log precisa de detalhes."]);
        exit;
    }

    try {
        $sql = "INSERT INTO auditoria_logs (id_funcionario, acao, detalhes, modulo, ip_origem) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $id_funcionario, $acao, $detalhes, $modulo, $ip_origem);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Evento registado.", "log_id" => $stmt->insert_id]);
        } else {
            throw new Exception("Falha na inserção.");
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    $conn->close();
    exit; // Finaliza aqui se for POST
}

// 2. LÓGICA DE BUSCA (GET) - Para exibir na interface
$sql = "SELECT 
            a.*, 
            f.nome_completo AS nome_funcionario 
        FROM auditoria_logs a
        LEFT JOIN funcionarios f ON a.id_funcionario = f.id_funcionario
        ORDER BY a.data_registro DESC"; 

$resultado = $conn->query($sql);
$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/pharmora/";
?>

<!-- 3. ESTILO (Baseado no Histórico de Perdas) -->
<style>
.rh-container { width: 100%; animation: fadeIn 0.4s ease; color: var(--text-main); font-family: 'Inter', sans-serif; }
.top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
.header-info h2 { color: var(--accent); font-weight: 800; margin: 0; display: flex; align-items: center; gap: 12px; }
.header-info p { color: var(--text-dim); font-size: 12px; margin-top: 4px; }

/* Busca */
.search-box { position: relative; min-width: 220px; flex: 1 1 auto; max-width: 400px; }
.search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-dim); }
.search-box input { 
    width: 100%; background: var(--input-fill); border: 2px solid var(--card-border); 
    color: var(--text-main); padding: 12px 15px 12px 42px; border-radius: 12px; outline: none; 
}

/* Tabela Glass */
.table-glass { 
    width: 100%; background: var(--card-bg); border-radius: 16px; overflow-x: auto; 
    border: 1px solid var(--card-border); backdrop-filter: blur(15px); margin-bottom: 30px; 
}
table { width: 100%; border-collapse: collapse; min-width: 800px; }
th { 
    background: var(--input-fill); padding: 14px 12px; text-align: left; 
    color: var(--text-dim); font-size: 10px; text-transform: uppercase; letter-spacing: 1px; 
}
td { padding: 12px 12px; border-bottom: 1px solid var(--card-border); font-size: 13px; }
.main-row:hover { background: rgba(255, 255, 255, 0.02); }

/* Badges de Módulo/Ação */
.badge-modulo { padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 700; background: rgba(0, 255, 204, 0.1); color: var(--accent); border: 1px solid rgba(0, 255, 204, 0.2); }
.badge-ip { font-family: monospace; color: var(--text-dim); font-size: 11px; }

@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="rh-container">
    <div class="top-bar">
        <div class="header-info">
            <h2><i class="fas fa-shield-alt"></i> Logs de Auditoria</h2>
            <p>Rastreamento permanente de ações e segurança do sistema</p>
        </div>
        
        <div class="header-actions">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="buscaLogs" placeholder="Filtrar por ação, usuário ou módulo..." autocomplete="off">
            </div>
            <button class="btn-new" onclick="window.print()" style="background: var(--accent); border:none; padding: 10px 15px; border-radius: 10px; cursor:pointer;">
                <i class="fas fa-print"></i>
            </button>
        </div>
    </div>

    <div class="table-glass">
        <table>
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Usuário</th>
                    <th>Módulo</th>
                    <th>Ação</th>
                    <th>Detalhes do Evento</th>
                    <th style="text-align: right;">IP de Origem</th>
                </tr>
            </thead>
            <tbody id="bodyLogs">
                <?php if($resultado && $resultado->num_rows > 0): ?>
                    <?php while($row = $resultado->fetch_assoc()): ?>
                    <tr class="main-row">
                        <td><span style="font-size: 11px; color: var(--text-dim);"><?php echo date('d/m/Y H:i:s', strtotime($row['data_registro'])); ?></span></td>
                        <td><strong><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($row['nome_funcionario'] ?? 'Sistema/Admin'); ?></strong></td>
                        <td><span class="badge-modulo"><?php echo htmlspecialchars($row['modulo']); ?></span></td>
                        <td><code style="color: var(--accent);"><?php echo htmlspecialchars($row['acao']); ?></code></td>
                        <td style="max-width: 300px; color: var(--text-dim); font-size: 12px;">
                            <?php echo htmlspecialchars($row['detalhes']); ?>
                        </td>
                        <td style="text-align: right;"><span class="badge-ip"><?php echo $row['ip_origem']; ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; padding: 40px;">Nenhum log registado até ao momento.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Filtro de busca em tempo real (Igual ao de Perdas)
document.getElementById('buscaLogs').addEventListener('input', function() {
    const termo = this.value.toLowerCase();
    const linhas = document.querySelectorAll('#bodyLogs tr.main-row');
    
    linhas.forEach(linha => {
        const texto = linha.innerText.toLowerCase();
        linha.style.display = texto.includes(termo) ? "" : "none";
    });
});
</script>