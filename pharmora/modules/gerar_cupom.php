<?php
include_once("../config_api.php");

// Recupera os dados (Lógica PHP isolada no topo)
$id_venda = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sqlVenda = "SELECT * FROM vendas WHERE id_venda = $id_venda";
$resVenda = $conn->query($sqlVenda);
$venda = $resVenda->fetch_assoc();

if (!$venda) {
    die("Venda não encontrada.");
}

$sqlItens = "SELECT vi.*, p.nome_produto FROM vendas_itens vi 
             INNER JOIN produtos p ON vi.produto_id = p.id_produto 
             WHERE vi.venda_id = $id_venda";
$itens = $conn->query($sqlItens);
?>

<!-- A partir daqui é HTML PURO, igual à sua tela de Perdas -->
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Cupom de Venda #<?php echo $id_venda; ?></title>
    <style>
        /* Estilo simulando o Glassmorphism que você usa, mas para papel */
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f4f4f5; 
            padding: 20px; 
            display: flex; 
            justify-content: center; 
        }

        .cupom-card {
            width: 80mm; /* Largura padrão de impressora térmica */
            background: #ffffff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            color: #333;
        }

        .centro { text-align: center; }
        .linha-tracejada { border-top: 1px dashed #ccc; margin: 10px 0; }
        
        .item-row { 
            display: flex; 
            justify-content: space-between; 
            font-size: 13px; 
            margin-bottom: 5px; 
        }

        .total-box {
            background: #fdf2f2;
            padding: 10px;
            border-radius: 6px;
            border-left: 4px solid #e74c3c;
            margin-top: 10px;
        }

        .btn-imprimir {
            margin-bottom: 20px;
            background: #00ffcc; /* Cor var(--accent) que você usa */
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
        }

        @media print {
            .no-print { display: none; }
            body { background: none; padding: 0; }
            .cupom-card { box-shadow: none; width: 100%; }
        }
    </style>
</head>
<body>

<div style="display: flex; flex-direction: column; align-items: center;">
    
    <button class="btn-imprimir no-print" onclick="window.print()">
        <i class="fas fa-print"></i> IMPRIMIR CUPOM
    </button>

    <div class="cupom-card">
        <div class="centro">
            <h2 style="margin:0; color: #e74c3c;">PHARMORA</h2>
            <small>FARMÁCIA E SERVIÇOS</small><br>
            <small>Data: <?php echo date('d/m/Y H:i', strtotime($venda['data_venda'])); ?></small>
        </div>

        <div class="linha-tracejada"></div>
        <p style="font-size: 12px;"><strong>VENDA: #<?php echo str_pad($id_venda, 6, '0', STR_PAD_LEFT); ?></strong></p>

        <div class="itens-lista">
            <?php while($row = $itens->fetch_assoc()): ?>
                <div class="item-row">
                    <span><?php echo $row['quantidade']; ?>x <?php echo htmlspecialchars($row['nome_produto']); ?></span>
                    <strong><?php echo number_format($row['subtotal'], 2, ',', '.'); ?></strong>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="linha-tracejada"></div>

        <div class="total-box">
            <div class="item-row">
                <span>Subtotal:</span>
                <span><?php echo number_format($venda['subtotal'], 2, ',', '.'); ?> Kz</span>
            </div>
            <div class="item-row" style="color: #e74c3c;">
                <span>Desconto:</span>
                <span>- <?php echo number_format($venda['desconto'], 2, ',', '.'); ?> Kz</span>
            </div>
            <div class="item-row" style="font-size: 16px; font-weight: 900;">
                <span>TOTAL:</span>
                <span><?php echo number_format($venda['valor_total'], 2, ',', '.'); ?> Kz</span>
            </div>
        </div>

        <div class="linha-tracejada"></div>
        <div class="centro" style="font-size: 11px; color: #777;">
            Obrigado pela preferência!<br>
            Software Pharmora v1.0
        </div>
    </div>
</div>

</body>
</html>