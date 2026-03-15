<?php
// Arquivo: telas/perfil_processo.php
session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }
$idAdvogado = $_SESSION['id_usuario_logado'];

if (!isset($_GET['id'])) { header("Location: lista_processos.php"); exit; }
$idProcesso = $_GET['id'];

// LÓGICA DE DADOS 
$processo = null;
$arquivoProcessos = '../dados/Processos_' . $idAdvogado . '.json';
if (file_exists($arquivoProcessos)) {
    $lista = json_decode(file_get_contents($arquivoProcessos), true) ?? [];
    foreach ($lista as $p) { if (isset($p['id']) && $p['id'] == $idProcesso) { $processo = $p; break; } }
}
if (!$processo) { echo "<script>alert('Processo não encontrado.'); window.location.href='lista_processos.php';</script>"; exit; }

$cliente = null;
$arquivoClientes = '../dados/Clientes_' . $idAdvogado . '.json';
if (file_exists($arquivoClientes) && !empty($processo['cliente_id'])) {
    $listaC = json_decode(file_get_contents($arquivoClientes), true) ?? [];
    foreach ($listaC as $c) { if ($c['id'] == $processo['cliente_id']) { $cliente = $c; break; } }
}
$nomeCliente = $cliente['nome'] ?? 'Cliente Desconhecido/Excluído';

$andamentos = [];
$arqAndamentos = '../dados/Andamentos_Processo_' . $idProcesso . '.json';
if (file_exists($arqAndamentos)) {
    $andamentos = json_decode(file_get_contents($arqAndamentos), true) ?? [];
    usort($andamentos, function($a, $b) { return strtotime($b['data_andamento']) - strtotime($a['data_andamento']); });
}

$prazos = [];
foreach (glob('../dados/Agenda_*.json') as $arq) {
    $listaAg = json_decode(file_get_contents($arq), true) ?? [];
    foreach ($listaAg as $ag) { if (isset($ag['processo_id']) && $ag['processo_id'] == $idProcesso) { $prazos[] = $ag; } }
}
usort($prazos, function($a, $b) { return strtotime($a['data_evento']) - strtotime($b['data_evento']); });

$documentos = [];
$arqDocs = '../dados/Documentos_Processo_' . $idProcesso . '.json';
if (file_exists($arqDocs)) { $documentos = json_decode(file_get_contents($arqDocs), true) ?? []; }

$financeiroProcesso = [];
$arquivoFinanceiro = '../dados/Financeiro_' . $idAdvogado . '.json';
if (file_exists($arquivoFinanceiro)) {
    $listaFin = json_decode(file_get_contents($arquivoFinanceiro), true) ?? [];
    foreach ($listaFin as $f) {
        if (isset($f['processo_id']) && $f['processo_id'] == $idProcesso && empty($f['deletado'])) {
            $financeiroProcesso[] = $f;
        }
    }
}
usort($financeiroProcesso, function($a, $b) { return strtotime($a['data_vencimento']) - strtotime($b['data_vencimento']); });

$abaAtiva = $_GET['aba'] ?? 'resumo';
$corStatus = 'bg-success';
if($processo['status'] == 'Suspenso') $corStatus = 'bg-warning text-dark';
if($processo['status'] == 'Encerrado') $corStatus = 'bg-dark';
if($processo['status'] == 'Arquivado') $corStatus = 'bg-secondary';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JURIDEX - Ficha do Processo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .main-content { margin-left: 280px; min-height: 100vh; display: flex; flex-direction: column; transition: margin-left 0.3s ease; }
        .topbar { background: #fff; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; }
        
        .header-processo { background: linear-gradient(135deg, #182848 0%, #4b6cb7 100%); color: white; border-radius: 10px; }
        .resumo-kpi { background: rgba(255,255,255,0.1); border-radius: 8px; padding: 12px 20px; font-size: 0.95rem; }
        .nav-tabs .nav-link.active { font-weight: bold; color: #4b6cb7; border-bottom: 3px solid #4b6cb7; }
        .nav-tabs .nav-link { color: #555; font-weight: 500; border: none; }
        .tab-content { background: white; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 10px 10px; padding: 30px; }
        .timeline-andamento { border-left: 3px solid #4b6cb7; padding-left: 20px; margin-bottom: 20px; position: relative; }
        .timeline-andamento::before { content: ''; width: 13px; height: 13px; background: #4b6cb7; border-radius: 50%; position: absolute; left: -8px; top: 5px; border: 2px solid white; }
        .card-ia-processo { background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%); color: white; transition: 0.3s; cursor: pointer; border: none; }
        .card-ia-processo:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.2); }

        @media (max-width: 991px) {
            .main-content { margin-left: 0; width: 100%; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h4 class="mb-0 fw-bold text-dark">Ficha do Processo</h4>
        <div>
            <?php if(!empty($processo['cliente_id'])) { ?>
                <a href="perfil_cliente.php?id=<?php echo htmlspecialchars($processo['cliente_id']); ?>" class="btn btn-outline-primary btn-sm me-2 fw-bold">👤 Ir para o Cliente</a>
            <?php } ?>
            <a href="lista_processos.php" class="btn btn-dark btn-sm fw-bold">Voltar à Central</a>
        </div>
    </div>

    <div class="container-fluid px-4 mb-5">
        <div class="header-processo p-4 mb-4 shadow-sm">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <span class="badge <?php echo $corStatus; ?> mb-2 fs-6 border border-light"><?php echo htmlspecialchars($processo['status']); ?></span>
                    <?php if(isset($processo['prioridade']) && strpos($processo['prioridade'], 'Urgente') !== false) echo '<span class="badge bg-danger mb-2 ms-2 fs-6">🚨 Prioridade/Urgente</span>'; ?>
                    <h2 class="fw-bold mb-1">⚖️ Processo nº <?php echo htmlspecialchars($processo['numero_processo'] ?: 'Aguardando Distribuição'); ?></h2>
                    <h5 class="mb-3 text-info fw-bold"><?php echo htmlspecialchars($processo['tipo_acao']); ?></h5>
                    <div class="resumo-kpi d-inline-block shadow-sm">
                        <strong>Cliente:</strong> <?php echo htmlspecialchars($nomeCliente); ?> &nbsp;|&nbsp; 
                        <strong>Adverso:</strong> <?php echo htmlspecialchars($processo['parte_contraria'] ?? '-'); ?> &nbsp;|&nbsp; 
                        <strong>Fase:</strong> <?php echo htmlspecialchars($processo['fase_processual'] ?? 'Inicial'); ?>
                    </div>
                </div>
                
                <div class="col-md-5 text-end mt-3 mt-md-0">
                    <a href="ia_assistente_processo.php?acao=resumo_whatsapp&id_processo=<?php echo htmlspecialchars($idProcesso); ?>" class="btn btn-success fw-bold shadow-sm mb-2 w-100 fs-5 border-white">
                        📱 Gerar Resumo para WhatsApp
                    </a>
                    <div class="d-flex gap-2">
                        <a href="cadastro_processo.php?id=<?php echo htmlspecialchars($idProcesso); ?>" class="btn btn-light fw-bold shadow-sm w-100">✏️ Editar</a>
                        <button class="btn btn-primary fw-bold shadow-sm w-100" onclick="document.getElementById('andamentos-tab').click();">📝 Andamento</button>
                        <button class="btn btn-danger fw-bold shadow-sm w-100" onclick="document.getElementById('prazos-tab').click();">⏳ Prazo</button>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs flex-column flex-sm-row" id="tabProcesso">
            <li class="nav-item"><button class="nav-link <?php if($abaAtiva == 'resumo') echo 'active'; ?>" id="resumo-tab" data-bs-toggle="tab" data-bs-target="#resumo" type="button">📋 Resumo do Caso</button></li>
            <li class="nav-item"><button class="nav-link <?php if($abaAtiva == 'andamentos') echo 'active'; ?>" id="andamentos-tab" data-bs-toggle="tab" data-bs-target="#andamentos" type="button">📝 Andamentos</button></li>
            <li class="nav-item"><button class="nav-link text-danger fw-bold <?php if($abaAtiva == 'prazos') echo 'active'; ?>" id="prazos-tab" data-bs-toggle="tab" data-bs-target="#prazos" type="button">⏳ Prazos</button></li>
            <li class="nav-item"><button class="nav-link text-success fw-bold <?php if($abaAtiva == 'financeiro') echo 'active'; ?>" data-bs-toggle="tab" data-bs-target="#financeiro" type="button">💰 Financeiro</button></li>
            <li class="nav-item"><button class="nav-link text-dark fw-bold <?php if($abaAtiva == 'documentos') echo 'active'; ?>" data-bs-toggle="tab" data-bs-target="#documentos" type="button">📁 Peças e Docs</button></li>
            <li class="nav-item"><button class="nav-link text-primary <?php if($abaAtiva == 'ia') echo 'active'; ?>" data-bs-toggle="tab" data-bs-target="#ia" type="button">✨ IA Jurídica</button></li>
        </ul>

        <div class="tab-content shadow-sm">
            <div class="tab-pane fade <?php if($abaAtiva == 'resumo') echo 'show active'; ?>" id="resumo">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h5 class="fw-bold border-bottom pb-2 text-primary">Detalhes de Jurisdição</h5>
                        <table class="table table-borderless table-sm">
                            <tr><td class="text-muted w-25">Área:</td><td class="fw-bold"><?php echo htmlspecialchars($processo['area_direito'] ?? '-'); ?></td></tr>
                            <tr><td class="text-muted">Tribunal:</td><td class="fw-bold"><?php echo htmlspecialchars($processo['tribunal'] ?? '-'); ?></td></tr>
                            <tr><td class="text-muted">Vara/Comarca:</td><td class="fw-bold"><?php echo htmlspecialchars($processo['vara_comarca'] ?? '-'); ?> - <?php echo htmlspecialchars($processo['estado'] ?? ''); ?></td></tr>
                            <tr><td class="text-muted">Instância:</td><td class="fw-bold"><?php echo htmlspecialchars($processo['instancia'] ?? '-'); ?></td></tr>
                            <tr><td class="text-muted">Data Abertura:</td><td class="fw-bold"><?php echo !empty($processo['data_abertura']) ? date('d/m/Y', strtotime($processo['data_abertura'])) : '-'; ?></td></tr>
                            <tr><td class="text-muted">Valor Causa:</td><td class="fw-bold text-success">R$ <?php echo number_format((float)($processo['valor_causa'] ?? 0), 2, ',', '.'); ?></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5 class="fw-bold border-bottom pb-2 text-primary">Resumo Estratégico do Advogado</h5>
                        <div class="p-3 bg-light border rounded">
                            <?php echo nl2br(htmlspecialchars($processo['observacoes'] ?? 'Nenhuma anotação estratégica registrada.')); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade <?php if($abaAtiva == 'financeiro') echo 'show active'; ?>" id="financeiro">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-success mb-0">Controle de Honorários e Custas</h5>
                    <a href="cadastro_financeiro.php?processo_id=<?php echo htmlspecialchars($idProcesso); ?>&cliente_id=<?php echo htmlspecialchars($processo['cliente_id']); ?>" class="btn btn-sm btn-success fw-bold shadow-sm">➕ Adicionar Lançamento</a>
                </div>

                <?php if(empty($financeiroProcesso)) { ?>
                    <div class="alert alert-light text-center border text-muted p-5">
                        <h5>Nenhum lançamento financeiro vinculado a este processo.</h5>
                        <p>Clique no botão verde acima para lançar honorários iniciais, parcelas ou custas processuais.</p>
                    </div>
                <?php } else { ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle shadow-sm border">
                            <thead class="table-light">
                                <tr><th>Vencimento</th><th>Descrição da Parcela</th><th>Tipo</th><th>Valor (R$)</th><th>Status</th><th class="text-end">Ações</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($financeiroProcesso as $f) { 
                                    $isReceita = (isset($f['tipo']) && $f['tipo'] == 'Receita');
                                    $isPago = (strtolower($f['status']) == 'pago');
                                    $corStatus = $isPago ? 'bg-success' : 'bg-warning text-dark';
                                    $hoje = date('Y-m-d');
                                    if(!$isPago && $f['data_vencimento'] < $hoje) $corStatus = 'bg-danger';
                                ?>
                                    <tr>
                                        <td class="fw-bold <?php echo (!$isPago && $f['data_vencimento'] < $hoje) ? 'text-danger' : ''; ?>"><?php echo date('d/m/Y', strtotime($f['data_vencimento'])); ?></td>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($f['descricao'] ?? 'Sem descrição'); ?></td>
                                        <td><span class="badge <?php echo $isReceita ? 'bg-success' : 'bg-danger'; ?>"><?php echo $f['tipo']; ?></span></td>
                                        <td class="fw-bold text-<?php echo $isReceita ? 'success' : 'danger'; ?>"><?php echo number_format((float)($f['valor'] ?? 0), 2, ',', '.'); ?></td>
                                        <td><span class="badge <?php echo $corStatus; ?> px-2 py-1"><?php echo $f['status']; ?></span></td>
                                        <td class="text-end">
                                            <?php if(!$isPago) { ?>
                                                <a href="baixa_financeira.php?id=<?php echo $f['id_lancamento']; ?>" class="btn btn-sm btn-dark fw-bold" title="Dar Baixa">💰 Pagar</a>
                                            <?php } else { ?>
                                                <span class="text-success fw-bold small">✔ Liquidado</span>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            </div>

            <div class="tab-pane fade <?php if($abaAtiva == 'andamentos') echo 'show active'; ?>" id="andamentos">
                <h5 class="fw-bold mb-3">Linha do Tempo Processual</h5>
                <form method="POST" action="processar_processo_extra.php" class="bg-light p-3 rounded border mb-4">
                    <input type="hidden" name="acao" value="novo_andamento">
                    <input type="hidden" name="processo_id" value="<?php echo htmlspecialchars($idProcesso); ?>">
                    <div class="row g-2">
                        <div class="col-md-3"><label class="form-label fw-bold small">Data</label><input type="date" class="form-control" name="data_andamento" value="<?php echo date('Y-m-d'); ?>" required></div>
                        <div class="col-md-7"><label class="form-label fw-bold small">Descrição</label><input type="text" class="form-control" name="descricao_andamento" required></div>
                        <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100 fw-bold">Salvar</button></div>
                    </div>
                </form>
                <div class="ps-2">
                    <?php if(empty($andamentos)) { echo '<p class="text-muted">Nenhum andamento registrado ainda.</p>'; } ?>
                    <?php foreach($andamentos as $and) { ?>
                        <div class="timeline-andamento d-flex justify-content-between align-items-start">
                            <div class="w-100 me-3">
                                <span class="badge bg-primary mb-1 fs-6"><?php echo date('d/m/Y', strtotime($and['data_andamento'])); ?></span>
                                <p class="mb-0 fs-5 text-dark"><?php echo htmlspecialchars($and['descricao']); ?></p>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <a href="ia_assistente_processo.php?acao=traduzir&texto=<?php echo urlencode($and['descricao']); ?>" class="btn btn-sm btn-outline-primary fw-bold text-nowrap">🪄 Traduzir</a>
                                <a href="processar_processo_extra.php?excluir_andamento=<?php echo $and['id_andamento']; ?>&processo_id=<?php echo htmlspecialchars($idProcesso); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Deseja excluir este andamento?');">🗑️</a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="tab-pane fade <?php if($abaAtiva == 'prazos') echo 'show active'; ?>" id="prazos">
                <h5 class="fw-bold text-danger mb-3">Controle de Prazos</h5>
                <form method="POST" action="processar_processo_extra.php" class="bg-light p-3 rounded border border-danger mb-4">
                    <input type="hidden" name="acao" value="novo_prazo">
                    <input type="hidden" name="processo_id" value="<?php echo htmlspecialchars($idProcesso); ?>">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2"><label class="form-label fw-bold small">Tipo</label><select class="form-select" name="tipo_prazo" required><option value="Prazo Fatal">Prazo Fatal</option><option value="Audiência">Audiência</option><option value="Diligência">Diligência</option></select></div>
                        <div class="col-md-3"><label class="form-label fw-bold small">Título</label><input type="text" class="form-control" name="titulo_prazo" required></div>
                        <div class="col-md-2"><label class="form-label fw-bold small">Data</label><input type="date" class="form-control border-danger fw-bold" name="data_limite" required></div>
                        <div class="col-md-2"><label class="form-label fw-bold small">Hora</label><input type="time" class="form-control" name="hora_limite"></div>
                        <div class="col-md-3"><button type="submit" class="btn btn-danger w-100 fw-bold">Agendar</button></div>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark"><tr><th>Data e Hora</th><th>Tipo</th><th>Título</th><th class="text-end">Ações</th></tr></thead>
                        <tbody>
                            <?php if(empty($prazos)) { echo '<tr><td colspan="4" class="text-center text-muted">Nenhum prazo cadastrado.</td></tr>'; } ?>
                            <?php foreach($prazos as $pr) { $corBadge = ($pr['tipo_evento'] == 'Audiência') ? 'bg-primary' : 'bg-danger'; ?>
                                <tr>
                                    <td class="fw-bold"><?php echo date('d/m/Y', strtotime($pr['data_evento'])); ?> às <?php echo $pr['hora_evento']; ?></td>
                                    <td><span class="badge <?php echo $corBadge; ?>"><?php echo htmlspecialchars($pr['tipo_evento']); ?></span></td>
                                    <td><?php echo htmlspecialchars($pr['titulo']); ?></td>
                                    <td class="text-end"><a href="processar_processo_extra.php?excluir_prazo=<?php echo $pr['id_evento']; ?>&processo_id=<?php echo htmlspecialchars($idProcesso); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Deseja excluir?');">🗑️</a></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade <?php if($abaAtiva == 'documentos') echo 'show active'; ?>" id="documentos">
                
                <div class="alert shadow-sm border-0 d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 p-4 rounded-3" style="background: linear-gradient(135deg, #182848 0%, #4b6cb7 100%);">
                    <div class="text-center text-md-start mb-3 mb-md-0">
                        <h5 class="fw-bold mb-1 text-white">Ferramenta Mágica: Mesclador de Documentos</h5>
                        <p class="mb-0 text-white-50">Junte Várias Fotos (WhatsApp), RGs e Documentos em 1 único PDF padrão CNJ.</p>
                    </div>
                    <a href="ferramenta_mesclar_pdf.php" class="btn btn-danger btn-lg fw-bold shadow-lg text-white border-white text-nowrap">📕 Juntar PDFs e Imagens</a>
                </div>

                <h5 class="fw-bold mb-3">Cofre de Peças e Provas</h5>
                <form method="POST" action="processar_processo_extra.php" enctype="multipart/form-data" class="d-flex flex-column flex-md-row gap-2 mb-4 bg-light p-3 rounded border shadow-sm">
                    <input type="hidden" name="acao" value="upload_documento">
                    <input type="hidden" name="processo_id" value="<?php echo htmlspecialchars($idProcesso); ?>">
                    <input type="text" class="form-control" name="nome_documento" placeholder="Nome da Peça (Ex: Contestação)" required>
                    <input type="file" class="form-control" name="arquivo" accept=".pdf,.doc,.docx,.jpg,.png" required>
                    <button type="submit" class="btn btn-dark fw-bold px-4">Anexar</button>
                </form>

                <div class="row g-3">
                    <?php if(empty($documentos)) { echo '<div class="col-12"><p class="text-muted text-center p-4 border border-dashed">Nenhum documento anexado.</p></div>'; } ?>
                    <?php foreach($documentos as $doc) { ?>
                        <div class="col-md-4 col-lg-3">
                            <div class="card text-center p-3 shadow-sm h-100 position-relative">
                                <a href="processar_processo_extra.php?excluir_doc=<?php echo $doc['id_doc']; ?>&processo_id=<?php echo htmlspecialchars($idProcesso); ?>" class="position-absolute top-0 end-0 m-2 text-danger text-decoration-none" onclick="return confirm('Excluir arquivo do servidor?');">🗑️</a>
                                <h2 class="mb-2 mt-2"><?php echo $doc['extensao'] == 'pdf' ? '📕' : '📄'; ?></h2>
                                <h6 class="fw-bold text-truncate px-3" title="<?php echo htmlspecialchars($doc['nome_amigavel']); ?>"><?php echo htmlspecialchars($doc['nome_amigavel']); ?></h6>
                                <a href="<?php echo htmlspecialchars($doc['caminho_arquivo']); ?>" target="_blank" class="btn btn-sm btn-outline-dark mt-auto mx-3">Baixar / Ver</a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="tab-pane fade <?php if($abaAtiva == 'ia') echo 'show active'; ?>" id="ia">
                <h4 class="fw-bold text-primary text-center mb-2 mt-3">🧠 Inteligência Artificial do Processo</h4>
                <div class="row g-4 justify-content-center mt-3">
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-ia-processo h-100 p-4 text-center" onclick="window.location.href='gerador_peticao_ia.php?cliente_id=<?php echo htmlspecialchars($processo['cliente_id']); ?>&acao=<?php echo urlencode($processo['tipo_acao']); ?>'">
                            <h1 class="mb-3 text-white">📄</h1>
                            <h5 class="fw-bold text-white mb-2">Redigir Petição Auto-Preenchida</h5>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-ia-processo h-100 p-4 text-center" onclick="window.location.href='ia_analisador.php'">
                            <h1 class="mb-3 text-white">🔎</h1>
                            <h5 class="fw-bold text-white mb-2">Analisar Sentença / Andamento</h5>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>