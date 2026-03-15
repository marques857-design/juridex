<?php
// Arquivo: telas/painel_master_financeiro.php
// Função: O Hub Financeiro do CEO para controle de mensalidades dos clientes.

session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);
date_default_timezone_set('America/Sao_Paulo');

// Segurança
if (!isset($_SESSION['id_usuario_logado']) || $_SESSION['perfil'] != 'master') { header("Location: login.php"); exit; }
$nomeCEO = $_SESSION['nome_usuario'] ?? 'CEO';

$arquivoFinanceiroCEO = '../dados/Financeiro_SaaS.json';
$arquivoUsuarios = '../dados/Usuarios_SaaS.json';

// Mês e Ano de cobrança selecionado (Padrão: Atual)
$mesReferencia = $_GET['mes'] ?? date('m');
$anoReferencia = $_GET['ano'] ?? date('Y');
$refCobranca = $anoReferencia . '_' . $mesReferencia; // ID para o JSON

// ==========================================================
// AÇÕES DO CEO (SALVAR PAGAMENTO)
// ==========================================================
if (isset($_GET['acao']) && $_GET['acao'] == 'dar_baixa' && isset($_GET['id_escritorio'])) {
    $idEsc = $_GET['id_escritorio'];
    $valorPagar = $_GET['valor'];
    
    // Carrega o caixa do CEO
    $caixaCEO = file_exists($arquivoFinanceiroCEO) ? json_decode(file_get_contents($arquivoFinanceiroCEO), true) ?? [] : [];
    
    // Carrega os usuários para garantir os dados
    $assinantes = json_decode(file_get_contents($arquivoUsuarios), true) ?? [];
    $dadosEsc = null;
    foreach($assinantes as $ass) {
        if(($ass['id_escritorio'] ?? $ass['id'] ?? '') == $idEsc) { $dadosEsc = $ass; break; }
    }
    
    // Se o registro para esse mês/ano já existe, atualiza. Senão, cria.
    if (!isset($caixaCEO[$refCobranca])) { $caixaCEO[$refCobranca] = []; }
    
    $jaExiste = false;
    foreach($caixaCEO[$refCobranca] as $key => $pag) {
        if ($pag['id_escritorio'] == $idEsc) {
            $caixaCEO[$refCobranca][$key]['status'] = 'Pago';
            $caixaCEO[$refCobranca][$key]['data_pagamento'] = date('Y-m-d H:i:s');
            $jaExiste = true; break;
        }
    }
    
    if (!$jaExiste) {
        $caixaCEO[$refCobranca][] = [
            "id_escritorio" => $idEsc,
            "nome_escritorio" => $dadosEsc['nome_escritorio'],
            "valor" => (float)$valorPagar,
            "data_pagamento" => date('Y-m-d H:i:s'),
            "status" => "Pago"
        ];
    }
    
    file_put_contents($arquivoFinanceiroCEO, json_encode($caixaCEO, JSON_PRETTY_PRINT));
    header("Location: painel_master_financeiro.php?mes=$mesReferencia&ano=$anoReferencia&msg=baixa_sucesso"); exit;
}

// ==========================================================
// MATEMÁTICA FINANCEIRA DO CEO (Pura Engenharia)
// ==========================================================
$assinantes = file_exists($arquivoUsuarios) ? json_decode(file_get_contents($arquivoUsuarios), true) ?? [] : [];
$caixaCEO = file_exists($arquivoFinanceiroCEO) ? json_decode(file_get_contents($arquivoFinanceiroCEO), true) ?? [] : [];
$historicoPagamentosDoMes = $caixaCEO[$refCobranca] ?? [];

$listaGeralCobrança = [];
$totalRecebidoCaixa = 0; $totalPendenciaInadimplencia = 0;

foreach ($assinantes as $ass) {
    if (isset($ass['status']) && $ass['status'] == 'Ativo') {
        
        $idEsc = $ass['id_escritorio'] ?? $ass['id'] ?? 'Sem ID';
        $plano = $ass['plano'] ?? 'Básico (R$ 50)';
        
        // Puxa o valor do plano cadastrado
        $valorCobrança = 0;
        if(strpos($plano, '300') !== false) $valorCobrança = 300;
        elseif(strpos($plano, '100') !== false || strpos($plano, '299') !== false) $valorCobrança = 100;
        elseif(strpos($plano, '50') !== false || strpos($plano, '149') !== false) $valorCobrança = 50;
        else $valorCobrança = 50; // Padrão
        
        // Verifica se já pagou este mês
        $pagoEsteMes = false;
        $dataPago = '';
        foreach($historicoPagamentosDoMes as $pag) {
            if ($pag['id_escritorio'] == $idEsc && $pag['status'] == 'Pago') {
                $pagoEsteMes = true; $dataPago = $pag['data_pagamento']; break;
            }
        }
        
        if ($pagoEsteMes) { $totalRecebidoCaixa += $valorCobrança; }
        else { $totalPendenciaInadimplencia += $valorCobrança; }
        
        $listaGeralCobrança[] = [
            "id" => $idEsc,
            "nome_escritorio" => $ass['nome_escritorio'] ?? 'Sem Nome',
            "responsavel" => $ass['responsavel'] ?? 'Sem Responsável',
            "plano" => $plano,
            "valor" => $valorCobrança,
            "pago" => $pagoEsteMes,
            "data_pago" => $dataPago
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - Caixa SaaS do CEO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* ESTILO DARK DE ALTO CONTRATE (CEO) */
        body { background-color: #0f172a; color: #f8fafc; font-family: 'Segoe UI', sans-serif; }
        .navbar-master { background-color: #1e293b; border-bottom: 2px solid #e94560; }
        .card-dark { background-color: #1e293b; border: 1px solid #334155; border-radius: 10px; }
        .text-accent { color: #e94560; }
        .text-muted { color: #94a3b8 !important; } 
        .table-dark-custom { color: #f8fafc; margin-bottom: 0; }
        .table-dark-custom th { border-bottom: 2px solid #e94560; color: #cbd5e1; font-weight: bold; background-color: transparent; }
        .table-dark-custom td { border-bottom: 1px solid #334155; color: #e2e8f0; background-color: transparent; }
        .table-dark-custom tbody tr:hover td { background-color: rgba(255, 255, 255, 0.05); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-master py-3 mb-4 shadow">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold fs-4 text-white" href="painel_master.php">🚀 JURIDEX <span class="text-accent">| ADMIN SAAS</span></a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-light fw-bold">CEO: <?php echo htmlspecialchars($nomeCEO); ?></span>
            <a href="painel_master.php" class="btn btn-outline-light btn-sm fw-bold">Voltar ao Gestão</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'baixa_sucesso') echo "<div class='alert alert-success fw-bold py-2'>✅ Baixa de pagamento registada no caixa!</div>"; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-white mb-0">💰 Hub Financeiro do CEO</h3>
            <p class="text-muted mb-0">Controle de faturamento de licenças SaaS.</p>
        </div>
        
        <form class="d-flex gap-2 bg-dark p-2 rounded border border-secondary" method="GET">
            <label class="text-white small fw-bold mt-2">Mês/Ano Referência:</label>
            <select class="form-select bg-dark text-white border-secondary" name="mes">
                <?php 
                $mesesStr = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
                for($i=1; $i<=12; $i++) {
                    $m = str_pad($i, 2, '0', STR_PAD_LEFT);
                    $sel = ($m == $mesReferencia) ? 'selected' : '';
                    echo "<option value='$m' $sel>{$mesesStr[$i-1]}</option>";
                }
                ?>
            </select>
            <select class="form-select bg-dark text-white border-secondary" name="ano">
                <?php for($i=2023; $i<=2026; $i++) { $sel = ($i == $anoReferencia) ? 'selected' : ''; echo "<option value='$i' $sel>$i</option>"; } ?>
            </select>
            <button type="submit" class="btn btn-danger fw-bold">Filtrar</button>
        </form>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card card-dark p-4 h-100 shadow border-success">
                <small class="text-success fw-bold text-uppercase mb-2">💰 Faturamento Confirmado (Recebido no Mês)</small>
                <h1 class="fw-bold text-white mb-0">R$ <?php echo number_format($totalRecebidoCaixa, 2, ',', '.'); ?></h1>
                <p class="text-muted mb-0 mt-1 small">Total de boletos que você confirmou o recebimento.</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-dark p-4 h-100 shadow border-danger">
                <small class="text-danger fw-bold text-uppercase mb-2">⚠️ Inadimplência/Pendência (Atrasados)</small>
                <h1 class="fw-bold text-white mb-0">R$ <?php echo number_format($totalPendenciaInadimplencia, 2, ',', '.'); ?></h1>
                <p class="text-muted mb-0 mt-1 small">Total que deveria ter entrado de escritórios Ativos.</p>
            </div>
        </div>
    </div>

    <div class="card card-dark shadow mb-5">
        <div class="card-header border-bottom-0 pt-4 pb-2 px-4">
            <h5 class="fw-bold text-white mb-0">🏢 Lista de Cobrança (Ativos - <?php echo $mesesStr[(int)$mesReferencia-1] . '/' . $anoReferencia; ?>)</h5>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table class="table table-dark-custom align-middle">
                    <thead>
                        <tr>
                            <th>Escritório</th>
                            <th>Responsável</th>
                            <th>Plano</th>
                            <th>Valor Cobrado</th>
                            <th>Status Pagamento</th>
                            <th class="text-end">Ações Financeiras</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($listaGeralCobrança)) { echo "<tr><td colspan='6' class='text-center text-muted p-4'>Nenhum escritório ativo para cobrar este mês.</td></tr>"; } ?>
                        <?php foreach($listaGeralCobrança as $cob) { 
                            $badgeStatus = $cob['pago'] ? '<span class="badge bg-success px-3 py-2 shadow">✅ Pago</span>' : '<span class="badge bg-danger px-3 py-2 shadow">⏳ Pendente</span>';
                        ?>
                            <tr>
                                <td><strong class="text-white"><?php echo htmlspecialchars($cob['nome_escritorio']); ?></strong></td>
                                <td class="text-light"><?php echo htmlspecialchars($cob['responsavel']); ?></td>
                                <td class="text-warning fw-bold"><?php echo htmlspecialchars($cob['plano']); ?></td>
                                <td class="fw-bold text-white">R$ <?php echo number_format($cob['valor'], 2, ',', '.'); ?></td>
                                <td class="fw-bold"><?php echo $badgeStatus; ?></td>
                                <td class="text-end">
                                    <?php if(!$cob['pago']) { ?>
                                        <a href="painel_master_financeiro.php?acao=dar_baixa&id_escritorio=<?php echo urlencode($cob['id']); ?>&valor=<?php echo $cob['valor']; ?>&mes=<?php echo $mesReferencia; ?>&ano=<?php echo $anoReferencia; ?>" class="btn btn-sm btn-success fw-bold text-dark" onclick="return confirm('Confirma que recebeu o pagamento deste mês deste cliente?');">💰 Dar Baixa (Registrar PIX)</a>
                                    <?php } else { ?>
                                        <small class="text-muted">Recebido em: <?php echo date('d/m/Y H:i', strtotime($cob['data_pago'])); ?></small>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>