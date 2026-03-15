<?php
// Arquivo: telas/baixa_financeira.php
// Função: Dar baixa em uma parcela e anexar o comprovante de pagamento.

session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }
$idAdvogado = $_SESSION['id_usuario_logado'];

if (!isset($_GET['id']) && !isset($_POST['id_lancamento'])) { 
    header("Location: lista_financeiro.php"); exit; 
}

$idLancamento = $_GET['id'] ?? $_POST['id_lancamento'];
$arquivoFinanceiro = '../dados/Financeiro_' . $idAdvogado . '.json';

// Carrega o lançamento
$listaFinanceira = file_exists($arquivoFinanceiro) ? json_decode(file_get_contents($arquivoFinanceiro), true) ?? [] : [];
$lancamento = null;
foreach ($listaFinanceira as $f) {
    if ($f['id_lancamento'] == $idLancamento) { $lancamento = $f; break; }
}

if (!$lancamento) {
    echo "<script>alert('Lançamento não encontrado.'); window.location.href='lista_financeiro.php';</script>"; exit;
}

// Processa o Upload e Baixa
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $caminhoComprovante = $lancamento['comprovante'] ?? '';

    if (isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($_FILES['comprovante']['name'], PATHINFO_EXTENSION));
        $permitidas = ['pdf', 'jpg', 'jpeg', 'png'];
        
        if (in_array($extensao, $permitidas)) {
            $novoNome = uniqid() . '_comprovante_' . $idLancamento . '.' . $extensao;
            $caminhoDestino = '../uploads/' . $novoNome;
            
            if (!is_dir('../uploads/')) { mkdir('../uploads/', 0777, true); }
            
            if (move_uploaded_file($_FILES['comprovante']['tmp_name'], $caminhoDestino)) {
                $caminhoComprovante = $caminhoDestino;
            }
        }
    }

    foreach ($listaFinanceira as $key => $f) {
        if ($f['id_lancamento'] == $idLancamento) {
            $listaFinanceira[$key]['status'] = 'Pago';
            $listaFinanceira[$key]['data_pagamento'] = $_POST['data_pagamento'];
            $listaFinanceira[$key]['comprovante'] = $caminhoComprovante;
            break;
        }
    }
    
    file_put_contents($arquivoFinanceiro, json_encode(array_values($listaFinanceira), JSON_PRETTY_PRINT), LOCK_EX);
    header("Location: lista_financeiro.php?msg=pago");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JURIDEX - Baixa Financeira</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .main-content { margin-left: 280px; min-height: 100vh; display: flex; flex-direction: column; transition: margin-left 0.3s ease; }
        .topbar { background: #fff; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; }
        
        @media (max-width: 991px) { .main-content { margin-left: 0; width: 100%; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h4 class="mb-0 fw-bold text-dark">Confirmar Recebimento</h4>
        <a href="lista_financeiro.php" class="btn btn-outline-dark btn-sm fw-bold">Voltar ao Caixa</a>
    </div>

    <div class="container px-4 mb-5" style="max-width: 600px; margin: 0 auto;">
        <div class="card shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header text-white py-4 text-center" style="background: linear-gradient(135deg, #198754 0%, #20c997 100%);">
                <h1 style="font-size: 3rem; margin-bottom: 10px;">💰</h1>
                <h4 class="fw-bold mb-0">Liquidar Lançamento</h4>
            </div>
            <div class="card-body p-4 p-md-5">
                
                <div class="alert alert-light border shadow-sm mb-4 p-4 text-center" style="border-radius: 10px;">
                    <h6 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($lancamento['descricao']); ?></h6>
                    <p class="mb-1 text-success fw-bold" style="font-size: 2.2rem;">R$ <?php echo number_format((float)$lancamento['valor'], 2, ',', '.'); ?></p>
                    <small class="text-muted fw-bold text-uppercase">Vencimento Original: <?php echo date('d/m/Y', strtotime($lancamento['data_vencimento'])); ?></small>
                </div>

                <form method="POST" action="baixa_financeira.php" enctype="multipart/form-data">
                    <input type="hidden" name="id_lancamento" value="<?php echo htmlspecialchars($lancamento['id_lancamento']); ?>">

                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">Data que o valor caiu na conta</label>
                        <input type="date" class="form-control form-control-lg border-success fw-bold text-success text-center" name="data_pagamento" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-4 p-3 bg-light border rounded">
                        <label class="form-label fw-bold">Anexar Comprovativo <span class="text-muted fw-normal">(Opcional)</span></label>
                        <input type="file" class="form-control" name="comprovante" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted mt-2 d-block">Guarde aqui o PDF ou Imagem do PIX / Transferência para auditoria futura.</small>
                    </div>

                    <div class="d-grid gap-2 mt-5">
                        <button type="submit" class="btn btn-success btn-lg fw-bold shadow">✅ Confirmar Pagamento</button>
                        <a href="lista_financeiro.php" class="btn btn-light border fw-bold py-2 mt-2 text-secondary">Cancelar</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>