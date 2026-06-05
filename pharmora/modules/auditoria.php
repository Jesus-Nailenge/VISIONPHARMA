<?php
/**
 * PHARMORA API - Sistema de Auditoria e Logs Permanente
 * Reestruturado para Visualização e Gravação Segura do Sistema
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once("../config_api.php");

// 1. LÓGICA DE GRAVAÇÃO (POST) - Focada 100% em eventos do Sistema
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/json; charset=UTF-8");
    
    $acao      = trim($_POST['acao'] ?? 'ACAO_NAO_DEFINIDA');
    $detalhes  = trim($_POST['detalhes'] ?? '');
    $modulo    = trim($_POST['modulo'] ?? 'Sistema');
    $ip_origem = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    if (empty($detalhes)) {
        echo json_encode(["success" => false, "message" => "O log precisa de detalhes estruturados."]);
        exit;
    }

    try {
        $sql = "INSERT INTO auditoria_logs (acao, detalhes, modulo, ip_origem) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $acao, $detalhes, $modulo, $ip_origem);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Evento do sistema registado.", "log_id" => $stmt->insert_id]);
        } else {
            throw new Exception("Falha na gravação do log interno.");
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    $conn->close();
    exit;
}

// 2. LÓGICA DE BUSCA (GET) - Leitura direta da tabela simplificada
$sql = "SELECT id_log, acao, detalhes, modulo, ip_origem, data_hora 
        FROM auditoria_logs 
        ORDER BY data_hora DESC"; 

$resultado = $conn->query($sql);
$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/pharmora/";
?>

<style>
.rh-container { width: 100%; animation: fadeIn 0.4s ease; color: var(--text-main); font-family: 'Inter', sans-serif; }
.top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
.header-info h2 {
    color: var(--accent);
    font-weight: 800;
    margin: 0;
    font-size: clamp(18px, 3vw, 24px);
    display: flex;
    align-items: center;
    gap: 10px;
}
.header-info p { color: var(--text-dim); font-size: 12px; margin-top: 4px; }

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

.badge-modulo { padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 700; background: rgba(0, 255, 204, 0.1); color: var(--accent); border: 1px solid rgba(0, 255, 204, 0.2); }
.badge-ip { font-family: monospace; color: var(--text-dim); font-size: 11px; }
.sys-entity { font-weight: 600; color: #a5b4fc; display: flex; align-items: center; gap: 6px; }
.user-entity { font-weight: 600; color: #34d399; display: flex; align-items: center; gap: 6px; }

@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="rh-container">
    <div class="top-bar">
        <div class="header-info">
            <h2><i class="fas fa-shield-alt"></i> Logs de Auditoria Global</h2>
            <p>Rastreamento automatizado de eventos e segurança do ecossistema</p>
        </div>
        
        <div class="header-actions">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="buscaLogs" placeholder="Filtrar por ação, módulo ou detalhes..." autocomplete="off">
            </div>
        </div>
    </div>

    <div class="table-glass">
        <table>
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Módulo</th>
                    <th>Ação Executada</th>
                    <th>Detalhes Técnicos do Evento</th>
                    <th style="text-align: right;">IP do Gateway</th>
                </tr>
            </thead>
            <tbody id="bodyLogs">
                <?php if($resultado && $resultado->num_rows > 0): ?>
                    <?php while($row = $resultado->fetch_assoc()): ?>
                    <tr class="main-row">
                        <td><span style="font-size: 11px; color: var(--text-dim);"><?php echo date('d/m/Y H:i:s', strtotime($row['data_hora'])); ?></span></td>
                        <td><span class="badge-modulo"><?php echo htmlspecialchars($row['modulo']); ?></span></td>
                        <td><code style="color: var(--accent); font-weight: bold;"><?php echo htmlspecialchars($row['acao']); ?></code></td>
                        <td style="max-width: 450px; color: var(--text-dim); font-size: 12px; line-height: 1.4;">
                            <?php echo htmlspecialchars($row['detalhes']); ?>
                        </td>
                        <td style="text-align: right;"><span class="badge-ip"><?php echo htmlspecialchars($row['ip_origem']); ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding: 40px; color: var(--text-dim);">Nenhum log ou evento registrado pelo sistema até o momento.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('buscaLogs').addEventListener('input', function() {
    const termo = this.value.toLowerCase();
    const linhas = document.querySelectorAll('#bodyLogs tr.main-row');
    
    linhas.forEach(linha => {
        const texto = linha.innerText.toLowerCase();
        linha.style.display = texto.includes(termo) ? "" : "none";
    });
});
</script>