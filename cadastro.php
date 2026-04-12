
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmora | Novo Funcionário</title>
    <style>
        :root { --bg: #050505; --card: rgba(20, 20, 20, 0.9); --accent: #00ffcc; --text: #eaeaea; }
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; justify-content: center; padding: 40px 20px; margin: 0; }
        .container { width: 100%; max-width: 900px; background: var(--card); padding: 40px; border-radius: 12px; border: 1px solid #333; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        h2 { text-align: center; color: var(--accent); letter-spacing: 2px; text-transform: uppercase; margin-top: 0; }
        .section-title { font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 1.5px; margin: 30px 0 15px; border-bottom: 1px solid #333; padding-bottom: 8px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .input-group { display: flex; flex-direction: column; }
        label { font-size: 12px; margin-bottom: 8px; color: #bbb; font-weight: 500; }
        .required { color: var(--accent); }
        input, select, textarea { padding: 14px; background: #111; border: 1px solid #333; border-radius: 8px; color: white; outline: none; transition: 0.3s; }
        input:focus, select:focus, textarea:focus { border-color: var(--accent); box-shadow: 0 0 8px rgba(0, 255, 204, 0.2); }
        .full-width { grid-column: 1 / -1; }
        button { grid-column: 1 / -1; padding: 16px; margin-top: 20px; background: var(--accent); color: #000; border: none; border-radius: 8px; font-weight: bold; font-size: 16px; cursor: pointer; text-transform: uppercase; transition: 0.3s; }
        button:hover { background: #00e6b8; transform: translateY(-2px); }
        .nav-link { display: block; text-align: center; margin-top: 20px; color: #888; text-decoration: none; font-size: 14px; }
        .nav-link:hover { color: var(--accent); }
    </style>
</head>
<body>

<div class="container">
    <h2>Registro de Funcionário</h2>
    
    <form action="processa_cadastro.php" method="POST" enctype="multipart/form-data">
        
        <div class="section-title">👤 Dados Pessoais</div>
        <div class="grid">
            <div class="input-group"><label>Nome Completo <span class="required">*</span></label><input type="text" name="nome_completo" required></div>
            <div class="input-group"><label>Nº do BI <span class="required">*</span></label><input type="text" name="documento_id" required></div>
            <div class="input-group"><label>Telefone <span class="required">*</span></label><input type="text" name="telefone" required></div>
            <div class="input-group"><label>Data Nascimento</label><input type="date" name="data_nascimento"></div>
            <div class="input-group">
                <label>Sexo</label>
                <select name="sexo">
                    <option value="Masculino">Masculino</option>
                    <option value="Feminino">Feminino</option>
                </select>
            </div>
            <div class="input-group"><label>E-mail</label><input type="email" name="email"></div>
            <div class="input-group full-width"><label>Morada (Endereço)</label><textarea name="morada" rows="2"></textarea></div>
        </div>

        <div class="section-title">💼 Dados Profissionais</div>
        <div class="grid">
            <div class="input-group">
                <label>Cargo <span class="required">*</span></label>
                <select name="cargo" required>
                    <option value="Caixa">Caixa</option>
                    <option value="Farmacêutico">Farmacêutico</option>
                    <option value="Gestor de Stock">Gestor de Stock</option>
                    <option value="Administrador">Administrador</option>
                </select>
            </div>
            <div class="input-group">
                <label>Departamento</label>
                <select name="departamento">
                    <option value="Balcão">Balcão</option>
                    <option value="Stock">Stock</option>
                    <option value="Financeiro">Financeiro</option>
                    <option value="Geral">Geral</option>
                </select>
            </div>
            <div class="input-group">
                <label>Tipo de Contrato</label>
                <select name="tipo_contrato">
                    <option value="Efetivo">Efetivo</option>
                    <option value="Temporário">Temporário</option>
                    <option value="Estágio">Estágio</option>
                </select>
            </div>
            <div class="input-group"><label>Filial</label><input type="text" name="filial" value="Sede Principal"></div>
            <div class="input-group full-width"><label>Foto de Perfil (Opcional)</label><input type="file" name="foto_perfil" accept="image/*"></div>
        </div>

        <div class="section-title">🔐 Credenciais de Acesso</div>
        <div class="grid">
            <div class="input-group"><label>Nome de Usuário <span class="required">*</span></label><input type="text" name="username" required></div>
            <div class="input-group"><label>Senha <span class="required">*</span></label><input type="password" name="password" required></div>
            <div class="input-group">
                <label>Nível de Acesso</label>
                <select name="nivel_acesso">
                    <option value="Staff">Staff (Padrão)</option>
                    <option value="Supervisor">Supervisor</option>
                    <option value="Admin">Administrador</option>
                </select>
            </div>
            <button type="submit">Gravar Funcionário no Sistema</button>
        </div>
    </form>
    
    <a href="lista_funcionarios.php" class="nav-link">Ver Lista de Funcionários &rarr;</a>
</div>

</body>
</html>