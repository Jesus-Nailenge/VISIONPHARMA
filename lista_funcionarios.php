<?php
$conn = new mysqli("localhost", "root", "", "pharmora_db");
if ($conn->connect_error) { die("Erro: " . $conn->connect_error); }

$sql = "SELECT * FROM funcionarios ORDER BY id_sistema DESC";
$resultado = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmora | Gestão de Equipa</title>
    <style>
        :root { --bg: #050505; --card: #111; --accent: #00ffcc; --text: #eaeaea; }
        body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto 20px; }
        h2 { color: var(--accent); text-transform: uppercase; letter-spacing: 1px; }
        .btn-add { background: var(--accent); color: black; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; transition: 0.3s; }
        .btn-add:hover { opacity: 0.8; }
        
        .alert-success { background: rgba(0, 255, 204, 0.1); border: 1px solid var(--accent); color: var(--accent); padding: 15px; border-radius: 8px; max-width: 1200px; margin: 0 auto 20px; text-align: center; font-weight: bold; }
        
        .table-wrapper { max-width: 1200px; margin: 0 auto; background: var(--card); border-radius: 10px; overflow-x: auto; box-shadow: 0 5px 15px rgba(0,0,0,0.5); }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th { background: #1a1a1a; padding: 16px; font-size: 12px; text-transform: uppercase; color: #888; text-align: left; border-bottom: 2px solid #333; }
        td { padding: 16px; border-bottom: 1px solid #222; font-size: 14px; vertical-align: middle; }
        tr:hover { background: rgba(255,255,255,0.03); }

        .profile { display: flex; align-items: center; gap: 15px; }
        .avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #333; }
        .badge { background: #222; color: #fff; padding: 5px 10px; border-radius: 4px; font-family: monospace; font-size: 12px; border: 1px solid #444; }
        .pill { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; background: rgba(0, 255, 204, 0.1); color: var(--accent); }
    </style>
</head>
<body>

<?php if(isset($_GET['msg']) && $_GET['msg'] == 'sucesso'): ?>
    <div class="alert-success">✅ Funcionário cadastrado com sucesso no sistema Pharmora!</div>
<?php endif; ?>

<div class="header">
    <h2>Equipa Pharmora</h2>
    <a href="cadastro.php" class="btn-add">+ NOVO REGISTRO</a>
</div>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Funcionário</th>
                <th>ID</th>
                <th>Cargo / Depto</th>
                <th>Contacto</th>
                <th>Usuário / Nível</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resultado && $resultado->num_rows > 0): ?>
                <?php while($row = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="profile">
                                <?php $foto = !empty($row['foto_perfil']) ? $row['foto_perfil'] : 'default.png'; ?>
                                <img src="uploads/<?php echo $foto; ?>" class="avatar" alt="Avatar">
                                <div>
                                    <strong><?php echo htmlspecialchars($row['nome_completo']); ?></strong><br>
                                    <span style="color:#666; font-size: 12px;">BI: <?php echo htmlspecialchars($row['documento_id']); ?></span>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge"><?php echo htmlspecialchars($row['id_funcionario']); ?></span></td>
                        <td>
                            <?php echo htmlspecialchars($row['cargo']); ?><br>
                            <span style="color:#888; font-size: 12px;"><?php echo htmlspecialchars($row['departamento']); ?></span>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($row['telefone']); ?><br>
                            <span style="color:#888; font-size: 12px;"><?php echo htmlspecialchars($row['email']); ?></span>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($row['username']); ?><br>
                            <span class="pill" style="margin-top: 5px; display: inline-block;"><?php echo htmlspecialchars($row['nivel_acesso']); ?></span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px; color: #666;">Nenhum funcionário encontrado.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
<?php $conn->close(); ?>