<?php
// Arquivo: telas/lista_financeiro.php
// Atualização Enterprise: Layout Sidebar + Soft-Delete (Lixeira Segura) e File Locking (LOCK_EX)

session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }
$idAdvogado = $_SESSION['id_usuario_logado'];

$arquivoFinanceiro = '../dados/Financeiro_' . $idAdvogado . '.json';
$meuCaixa = file_exists($arquivoFinanceiro) ? json_decode(file_get_contents($arquivoFinanceiro), true) ?? [] : [];

// Carrega as Configurações do Escritório (Para puxar o PIX)
$config = [];
$arquivoConfig = '../dados/Configuracoes_' . $idAdvogado . '.json';
if (file_exists($arquivoConfig)) {
    $config = json_decode(file_get_contents($arquivoConfig), true) ?? [];
}

// =================================================================================
// SOFT DELETE E LOCK EXCLUSIVO
// =================================================================================
if (isset($_GET['excluir_id'])) {
    foreach ($meuCaixa as $key => $f) { 
        if ($f['id_lancamento'] == $_GET['excluir_id']) { 
            // Em vez de dar unset(), ocultamos o dado. (Auditoria e Recuperação segura)
            $meuCaixa[$key]['deletado'] = true;
            $meuCaixa[$key]['data_exclusao'] = date('Y-m-d H:i:s');
            break;
        }
    }
    file_put_contents($arquivoFinanceiro, json_encode(array_values($meuCaixa), JSON_PRETTY_PRINT), LOCK_EX);
    header("Location: lista_financeiro.php?msg=excluido"); exit;
}

// Matemática Financeira
$totalRecebido = 0; $totalAReceber = 0; $totalDespesasPagas = 0; $totalInadimplente = 0;
$hoje = date('Y-m-d');

usort($meuCaixa, function($a, $b) { return strtotime($a['data_vencimento']) - strtotime($b['data_vencimento']); });

foreach ($meuCaixa as $f) {
    // PULA OS REGISTROS QUE ESTÃO NA LIXEIRA (Soft Delete)
    if (isset($f['deletado']) && $f['deletado'] === true) continue;

    $valor = (float)$f['valor'];
    if ($f['tipo'] == 'Receita') {
        if ($f['status'] == 'Pago') { $totalRecebido += $valor; }
        else { 
            $totalAReceber += $valor; 
            if ($f['data_vencimento'] < $hoje) { $totalInadimplente += $valor; }
        }
    } else {
        if ($f['status'] == 'Pago') { $totalDespesasPagas += $valor; }
    }
}
$saldoLiquido = $totalRecebido - $totalDespesasPagas;

// Puxa nomes e telefones de clientes para a cobrança
$clientes = [];
if (file_exists('../dados/Clientes_' . $idAdvogado . '.json')) {
    $listaC = json_decode(file_get_contents('../dados/Clientes_' . $idAdvogado . '.json'), true) ?? [];
    foreach($listaC as $c) { $clientes[$c['id']] = $c; }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JURIDEX - Caixa e Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .main-content { margin-left: 280px; min-height: 100vh; display: flex; flex-direction: column; transition: margin-left 0.3s ease; }
        .topbar { background: #fff; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; }
        
        .page-header { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; border-radius: 10px; padding: 25px 30px; margin-bottom: 25px; box-shadow: 0 5px 15px rgba(17, 153, 142, 0.2); }
        .kpi-card { border-left: 5px solid; border-radius: 8px; background: white; padding: 20px; transition: 0.3s; }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 15px rgba(0,0,0,0.05) !important; }
        .kpi-recebido { border-left-color: #198754; }
        .kpi-areceber { border-left-color: #0d6efd; }
        .kpi-inadimplente { border-left-color: #dc3545; }
        .kpi-saldo { border-left-color: #212529; }

        .table-custom { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #eee; }
        .table-custom thead { background-color: #212529; color: white; }
        .table-custom th { font-weight: 600; padding: 15px; border: none; font-size: 0.9rem; text-transform: uppercase;}
        .table-custom td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f0f0f0; }

        @media (max-width: 991px) {
            .main-content { margin-left: 0; width: 100%; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h4 class="mb-0 fw-bold text-dark">Hub Financeiro</h4>
        <a href="painel.php" class="btn btn-light border btn-sm fw-bold">Voltar ao Painel</a>
    </div>

    <div class="container-fluid px-4 mb-5">
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'excluido') echo "<div class='alert alert-warning fw-bold shadow-sm'>🗑️ Lançamento movido para o histórico de excluídos.</div>"; ?>
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'pago') echo "<div class='alert alert-success fw-bold shadow-sm'>✅ Lançamento liquidado e comprovante anexado!</div>"; ?>

        <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-center">
            <div class="mb-3 mb-md-0 text-center text-md-start">
                <h2 class="fw-bold mb-1">💰 Gestão de Caixa</h2>
                <p class="mb-0 opacity-75">Controlo de honorários, comprovativos e cobrança automatizada.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap justify-content-center">
                <a href="configuracoes.php" class="btn btn-light text-dark fw-bold shadow-sm d-flex align-items-center">⚙️ Configurar PIX</a>
                <button onclick="exportarTabelaParaExcel('tabelaFinanceiro', 'Relatorio_Financeiro_JURIDEX')" class="btn btn-light text-success fw-bold shadow-sm d-flex align-items-center">📊 Excel</button>
                <a href="cadastro_financeiro.php" class="btn btn-dark fw-bold shadow-sm d-flex align-items-center">➕ Novo Lançamento</a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="kpi-card kpi-recebido shadow-sm h-100">
                    <small class="text-success fw-bold text-uppercase">Receitas Pagas</small>
                    <h3 class="fw-bold mb-0 text-success">R$ <?php echo number_format($totalRecebido, 2, ',', '.'); ?></h3>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="kpi-card kpi-saldo shadow-sm h-100">
                    <small class="text-dark fw-bold text-uppercase">Saldo Líquido</small>
                    <h3 class="fw-bold mb-0 text-dark">R$ <?php echo number_format($saldoLiquido, 2, ',', '.'); ?></h3>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="kpi-card kpi-areceber shadow-sm h-100">
                    <small class="text-primary fw-bold text-uppercase">A Receber (Futuro)</small>
                    <h3 class="fw-bold mb-0 text-primary">R$ <?php echo number_format($totalAReceber, 2, ',', '.'); ?></h3>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="kpi-card kpi-inadimplente shadow-sm h-100 bg-white">
                    <small class="text-danger fw-bold text-uppercase">Inadimplência</small>
                    <h3 class="fw-bold mb-0 text-danger">R$ <?php echo number_format($totalInadimplente, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <input type="text" id="buscaFin" class="form-control form-control-lg border-success shadow-sm" placeholder="🔎 Filtrar por Cliente, Categoria, Status ou Descrição...">
        </div>

        <div class="table-custom table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabelaFinanceiro">
                <thead>
                    <tr>
                        <th>Vencimento</th>
                        <th>Tipo</th>
                        <th>Descrição / Cliente</th>
                        <th>Valor (R$)</th>
                        <th>Status / Comp.</th>
                        <th class="text-end acoes-coluna">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($meuCaixa)) { ?>
                        <tr><td colspan="6" class="text-center p-5 text-muted">Nenhum lançamento no caixa.</td></tr>
                    <?php } else { 
                        $contadorVisivel = 0;
                        foreach ($meuCaixa as $f) { 
                            if (isset($f['deletado']) && $f['deletado'] === true) continue;
                            
                            $contadorVisivel++;
                            $isReceita = ($f['tipo'] == 'Receita');
                            $corBadgeTipo = $isReceita ? 'bg-success' : 'bg-danger';
                            
                            $isPago = ($f['status'] == 'Pago');
                            $corBadgeStatus = $isPago ? 'bg-success' : 'bg-warning text-dark';
                            $iconeStatus = $isPago ? '✅ Pago' : '⏳ Pendente';
                            
                            $atrasado = false;
                            if (!$isPago && $f['data_vencimento'] < $hoje) {
                                $corBadgeStatus = 'bg-danger';
                                $iconeStatus = '⚠️ Atrasado';
                                $atrasado = true;
                            }

                            $nomeCliente = 'Sem vínculo';
                            $telefoneCliente = '';
                            if(!empty($f['cliente_id']) && isset($clientes[$f['cliente_id']])) {
                                $nomeCliente = $clientes[$f['cliente_id']]['nome'];
                                $telefoneCliente = preg_replace("/[^0-9]/", "", $clientes[$f['cliente_id']]['telefone']); 
                            }
                            
                            // WhatsApp Generator
                            $vencFormatado = date('d/m/Y', strtotime($f['data_vencimento']));
                            $valFormatado = number_format((float)$f['valor'], 2, ',', '.');
                            $textoPix = "";
                            if (!empty($config['chave_pix'])) {
                                $textoPix = "\n\n💳 *Dados para Pagamento (PIX):*\nChave ({$config['tipo_chave_pix']}): {$config['chave_pix']}\nTitular: {$config['titular_pix']}\nBanco: {$config['banco_recebimento']}";
                            }

                            if($atrasado) {
                                $msgWhats = "Olá {$nomeCliente}, tudo bem? Consta em nosso sistema financeiro uma pendência no valor de *R$ {$valFormatado}*, referente a {$f['descricao']}, que venceu no dia {$vencFormatado}. Houve algum problema? Se já efetuou o pagamento, por favor, envie-nos o comprovativo." . $textoPix;
                            } else {
                                $msgWhats = "Olá {$nomeCliente}, tudo bem? Passando apenas para lembrar que o vencimento da sua parcela de *R$ {$valFormatado}*, referente a {$f['descricao']}, será no dia {$vencFormatado}. Qualquer dúvida estamos à disposição!" . $textoPix;
                            }
                            $linkWhats = "https://api.whatsapp.com/send?phone=55{$telefoneCliente}&text=" . urlencode($msgWhats);
                    ?>
                        <tr class="linha-fin">
                            <td class="fw-bold <?php echo $atrasado ? 'text-danger' : ''; ?>">
                                <?php echo $vencFormatado; ?>
                            </td>
                            <td><span class="badge <?php echo $corBadgeTipo; ?>"><?php echo htmlspecialchars($f['tipo']); ?></span></td>
                            <td>
                                <strong><?php echo htmlspecialchars($f['descricao']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($f['categoria']); ?> | 👤 <?php echo htmlspecialchars($nomeCliente); ?></small>
                            </td>
                            <td class="fw-bold text-<?php echo $isReceita ? 'success' : 'danger'; ?>">
                                <?php echo $valFormatado; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $corBadgeStatus; ?> mb-1 d-block w-100" style="max-width: 100px;"><?php echo $iconeStatus; ?></span>
                                <?php if($isPago && !empty($f['comprovante'])) { ?>
                                    <a href="<?php echo $f['comprovante']; ?>" target="_blank" class="badge bg-light text-dark border text-decoration-none">📎 Ver Comp.</a>
                                <?php } ?>
                            </td>
                            <td class="text-end acoes-coluna">
                                <div class="d-flex justify-content-end gap-1 flex-wrap">
                                    <?php if(!$isPago && $isReceita && !empty($telefoneCliente)) { ?>
                                        <a href="<?php echo $linkWhats; ?>" target="_blank" class="btn btn-sm btn-success fw-bold shadow-sm" title="Cobrar no WhatsApp">📱</a>
                                    <?php } ?>

                                    <?php if(!$isPago) { ?>
                                        <a href="baixa_financeira.php?id=<?php echo $f['id_lancamento']; ?>" class="btn btn-sm btn-dark fw-bold shadow-sm" title="Confirmar Pagamento">💰 Baixa</a>
                                    <?php } ?>

                                    <a href="cadastro_financeiro.php?id=<?php echo $f['id_lancamento']; ?>" class="btn btn-sm btn-warning fw-bold text-dark shadow-sm" title="Editar">✏️</a>
                                    <a href="lista_financeiro.php?excluir_id=<?php echo $f['id_lancamento']; ?>" class="btn btn-sm btn-danger fw-bold shadow-sm" onclick="return confirm('Mover para a lixeira do sistema?');" title="Excluir">🗑️</a>
                                </div>
                            </td>
                        </tr>
                    <?php } 
                        if($contadorVisivel == 0) echo '<tr><td colspan="6" class="text-center p-5 text-muted">Nenhum lançamento visível.</td></tr>';
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('buscaFin').addEventListener('keyup', function() {
    let termo = this.value.toLowerCase();
    document.querySelectorAll('.linha-fin').forEach(function(linha) {
        linha.style.display = linha.textContent.toLowerCase().includes(termo) ? '' : 'none';
    });
});

function exportarTabelaParaExcel(tableID, filename = ''){
    var downloadLink;
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
    tableHTML = tableHTML.replace(/<th class="text-end acoes-coluna">Ações<\/th>/g, '');
    tableHTML = tableHTML.replace(/<td class="text-end acoes-coluna">.*?<\/td>/g, '');
    filename = filename?filename+'.xls':'excel_data.xls';
    downloadLink = document.createElement("a");
    document.body.appendChild(downloadLink);
    if(navigator.msSaveOrOpenBlob){
        var blob = new Blob(['\ufeff', tableHTML], { type: dataType });
        navigator.msSaveOrOpenBlob( blob, filename);
    }else{
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
        downloadLink.download = filename;
        downloadLink.click();
    }
    document.body.removeChild(downloadLink);
}
</script>

</body>
</html>