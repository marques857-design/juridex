<?php
// Arquivo: telas/relatorio_sexta.php
// Função: Central de Comunicação Ativa / Follow-up Processual

session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }
$idAdvogado = $_SESSION['id_usuario_logado'];

// =================================================================================
// MOTOR AJAX: MARCAR COMO ENVIADO (Oculta da fila)
// =================================================================================
if (isset($_POST['acao']) && $_POST['acao'] == 'marcar_enviado') {
    $idCli = $_POST['cliente_id'] ?? '';
    $arqControle = '../dados/Controle_Informes_' . $idAdvogado . '.json';
    $controle = file_exists($arqControle) ? json_decode(file_get_contents($arqControle), true) : [];
    
    if ($idCli == 'TODOS' && isset($_POST['ids'])) {
        $ids = json_decode($_POST['ids'], true);
        foreach($ids as $id) { $controle[$id] = date('Y-m-d H:i:s'); }
    } else {
        $controle[$idCli] = date('Y-m-d H:i:s');
    }
    
    file_put_contents($arqControle, json_encode($controle, JSON_PRETTY_PRINT), LOCK_EX);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok']);
    exit;
}

$nomeAdvogado = $_SESSION['nome_usuario'] ?? 'Doutor(a)';

// 1. CARREGA BANCOS DE DADOS
$clientes = [];
$arqClientes = '../dados/Clientes_' . $idAdvogado . '.json';
if (file_exists($arqClientes)) {
    $listaC = json_decode(file_get_contents($arqClientes), true) ?? [];
    foreach($listaC as $c) { $clientes[$c['id']] = $c; }
}

$processos = [];
$arqProcessos = '../dados/Processos_' . $idAdvogado . '.json';
if (file_exists($arqProcessos)) {
    $listaP = json_decode(file_get_contents($arqProcessos), true) ?? [];
    foreach($listaP as $p) {
        if(isset($p['status']) && $p['status'] != 'Encerrado' && $p['status'] != 'Arquivado') {
            $processos[] = $p;
        }
    }
}

// LÊ QUANDO FOI O ÚLTIMO ENVIO DE CADA CLIENTE
$arqControle = '../dados/Controle_Informes_' . $idAdvogado . '.json';
$controleInformes = file_exists($arqControle) ? json_decode(file_get_contents($arqControle), true) : [];

// 2. MONTA A FILA (SOMENTE QUEM TEM MOVIMENTAÇÃO NOVA)
$relatorios = [];

foreach ($clientes as $idCli => $cli) {
    $processosDesteCliente = array_filter($processos, function($p) use ($idCli) { return isset($p['cliente_id']) && $p['cliente_id'] == $idCli; });
    if (empty($processosDesteCliente)) continue;

    $dataUltimoEnvio = substr($controleInformes[$idCli] ?? '2000-01-01', 0, 10); // YYYY-MM-DD
    $dataMaisRecenteDoCliente = '2000-01-01';
    $textoProcessos = "";
    $qtdProcessosComAndamento = 0;

    foreach ($processosDesteCliente as $p) {
        $idProc = $p['id'] ?? ($p['id_processo'] ?? '');
        $arqAndamentos = '../dados/Andamentos_Processo_' . $idProc . '.json';
        
        if (file_exists($arqAndamentos)) {
            $andamentos = json_decode(file_get_contents($arqAndamentos), true) ?? [];
            if (!empty($andamentos)) {
                usort($andamentos, function($a, $b) { return strtotime($b['data_andamento']) - strtotime($a['data_andamento']); });
                $ultimoAndamento = $andamentos[0];
                
                $dataAndamento = $ultimoAndamento['data_andamento']; // YYYY-MM-DD
                
                if ($dataAndamento > $dataMaisRecenteDoCliente) { $dataMaisRecenteDoCliente = $dataAndamento; }
                
                $qtdProcessosComAndamento++;
                
                $numProc = $p['numero_processo'] ?? 'Sem Número';
                $textoProcessos .= "⚖️ *Processo:* {$numProc} ({$p['tipo_acao']})\n";
                $textoProcessos .= "📅 *Data:* " . date('d/m/Y', strtotime($dataAndamento)) . "\n";
                $textoProcessos .= "📌 *Status:* {$ultimoAndamento['descricao']}\n\n";
            }
        }
    }

    if ($qtdProcessosComAndamento > 0 && $dataMaisRecenteDoCliente > $dataUltimoEnvio) {
        
        $telefoneLipo = preg_replace("/[^0-9]/", "", $cli['telefone'] ?? '');
        $primeiroNome = explode(' ', trim($cli['nome']))[0];
        
        $msgWhats = "Olá *{$primeiroNome}*, tudo bem? Passando para o nosso informe sobre o andamento das suas ações no nosso escritório:\n\n";
        $msgWhats .= $textoProcessos;
        $msgWhats .= "Qualquer dúvida, estamos à disposição!";
        
        $linkWhats = "https://api.whatsapp.com/send?phone=55{$telefoneLipo}&text=" . urlencode($msgWhats);

        $relatorios[] = [
            'id_cliente' => $idCli,
            'nome' => $cli['nome'],
            'telefone' => $cli['telefone'] ?? 'S/N',
            'tem_whatsapp' => !empty($telefoneLipo),
            'qtd_processos' => $qtdProcessosComAndamento,
            'data_recente' => $dataMaisRecenteDoCliente,
            'mensagem_preview' => $msgWhats,
            'link_whatsapp' => $linkWhats
        ];
    }
}

usort($relatorios, function($a, $b) { return strtotime($b['data_recente']) - strtotime($a['data_recente']); });
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JURIDEX - Comunicação Ativa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .main-content { margin-left: 280px; min-height: 100vh; display: flex; flex-direction: column; transition: margin-left 0.3s ease; }
        .topbar { background: #fff; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; }
        
        .page-header { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; border-radius: 10px; padding: 30px; margin-bottom: 25px; box-shadow: 0 5px 15px rgba(17, 153, 142, 0.2); }
        
        .client-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #eee; margin-bottom: 20px; transition: transform 0.3s ease, opacity 0.3s ease; }
        .client-card:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        
        .preview-box { background: #e9ecef; border-left: 4px solid #11998e; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 0.9rem; white-space: pre-wrap; color: #333; max-height: 160px; overflow-y: auto;}
        
        @media (max-width: 991px) { .main-content { margin-left: 0; width: 100%; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h4 class="mb-0 fw-bold text-dark">Follow-up de Clientes</h4>
        <a href="painel.php" class="btn btn-outline-dark btn-sm fw-bold">Voltar ao Painel</a>
    </div>

    <div class="container-fluid px-4 mb-5">
        
        <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-center">
            <div class="text-center text-md-start">
                <h2 class="fw-bold mb-2">📬 Central de Comunicação Ativa</h2>
                <p class="mb-0 text-white-50" style="font-size: 1.1rem;">O sistema organizou os clientes que possuem <b>novas movimentações processuais</b> desde o último contato. Realize o envio das atualizações de forma ágil.</p>
            </div>
            <div class="mt-4 mt-md-0">
                <button onclick="limparFilaInteira()" class="btn btn-light text-success fw-bold btn-lg shadow-sm">
                    ✔️ Concluir Todas as Atualizações
                </button>
            </div>
        </div>

        <div class="mb-4">
            <h5 class="fw-bold text-dark mb-0">Fila de Atualizações Pendentes (<span id="contadorFila" class="text-success"><?php echo count($relatorios); ?></span> clientes aguardam retorno)</h5>
        </div>

        <?php if(empty($relatorios)): ?>
            <div class="alert alert-success text-center p-5 shadow-sm rounded-3" style="background-color: white; border-left: 5px solid #198754;">
                <h4 class="fw-bold text-success">✔️ Tudo atualizado!</h4>
                <p class="text-muted mb-0">Não há clientes aguardando retorno processual no momento. Todos os andamentos recentes já foram informados.</p>
            </div>
        <?php else: ?>
            
            <div class="row" id="listaCards">
                <?php foreach($relatorios as $r): ?>
                    <div class="col-12" id="card-cli-<?php echo htmlspecialchars($r['id_cliente']); ?>" data-cliente-id="<?php echo htmlspecialchars($r['id_cliente']); ?>">
                        <div class="client-card p-4">
                            <div class="row align-items-center">
                                <div class="col-md-3 mb-3 mb-md-0">
                                    <h5 class="fw-bold text-dark mb-1">👤 <?php echo htmlspecialchars($r['nome']); ?></h5>
                                    <p class="text-muted mb-1 small">📞 <?php echo htmlspecialchars($r['telefone']); ?></p>
                                    <span class="badge bg-secondary mb-2"><?php echo $r['qtd_processos']; ?> Processo(s) Atualizado(s)</span>
                                    <div class="text-success fw-bold small">Data da Movimentação: <?php echo date('d/m/Y', strtotime($r['data_recente'])); ?></div>
                                </div>
                                
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <div class="preview-box shadow-sm"><?php echo htmlspecialchars($r['mensagem_preview']); ?></div>
                                </div>
                                
                                <div class="col-md-3 text-end d-flex flex-column gap-2">
                                    <?php if($r['tem_whatsapp']): ?>
                                        <button onclick="enviarEArquivar(this, '<?php echo htmlspecialchars($r['id_cliente']); ?>', '<?php echo addslashes($r['link_whatsapp']); ?>')" class="btn btn-success btn-lg w-100 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                                            <span>💬</span> Enviar Atualização
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-lg w-100 fw-bold shadow-sm" disabled>Telefone Inválido</button>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-outline-dark btn-sm w-100 fw-bold" onclick="copiarEArquivar(this, '<?php echo htmlspecialchars($r['id_cliente']); ?>', `<?php echo addslashes($r['mensagem_preview']); ?>`)">
                                        📋 Copiar Texto (Registrar Manualmente)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function enviarEArquivar(btnElement, clienteId, url) {
    if(url) { window.open(url, '_blank'); }
    arquivarCartao(btnElement, clienteId, "✅ Informado");
}

function copiarEArquivar(btnElement, clienteId, texto) {
    navigator.clipboard.writeText(texto);
    arquivarCartao(btnElement, clienteId, "✅ Copiado");
}

function arquivarCartao(btnElement, clienteId, textoBotao) {
    btnElement.innerHTML = textoBotao;
    btnElement.classList.replace("btn-success", "btn-secondary");
    btnElement.classList.replace("btn-outline-dark", "btn-secondary");
    btnElement.disabled = true;

    let formData = new FormData();
    formData.append('acao', 'marcar_enviado');
    formData.append('cliente_id', clienteId);

    fetch('relatorio_sexta.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'ok') {
            const card = document.getElementById('card-cli-' + clienteId);
            if(card) {
                card.style.opacity = "0";
                card.style.transform = "scale(0.95)";
                setTimeout(() => { 
                    card.remove(); 
                    atualizarContadorVisivel();
                }, 300);
            }
        }
    });
}

function limparFilaInteira() {
    if(!confirm("Tem certeza que deseja marcar todas as atualizações como enviadas? Os clientes só retornarão à fila quando houver novas movimentações nos processos.")) return;
    
    let ids = [];
    document.querySelectorAll('.client-card').forEach(card => {
        ids.push(card.parentElement.getAttribute('data-cliente-id'));
    });

    if(ids.length === 0) { alert('A fila já está vazia!'); return; }

    let formData = new FormData();
    formData.append('acao', 'marcar_enviado');
    formData.append('cliente_id', 'TODOS');
    formData.append('ids', JSON.stringify(ids));

    fetch('relatorio_sexta.php', { method: 'POST', body: formData })
    .then(() => { location.reload(); });
}

function atualizarContadorVisivel() {
    const qtd = document.querySelectorAll('.client-card').length;
    const contador = document.getElementById('contadorFila');
    if(contador) contador.innerText = qtd;
    
    if(qtd === 0) {
        document.getElementById('listaCards').innerHTML = `<div class="alert alert-success text-center p-5 shadow-sm rounded-3" style="background-color: white; border-left: 5px solid #198754;"><h4 class="fw-bold text-success">✔️ Tudo atualizado!</h4><p class="text-muted mb-0">Não há clientes aguardando retorno processual no momento.</p></div>`;
    }
}
</script>
</body>
</html>