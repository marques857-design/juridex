<?php
// Arquivo: telas/perfil_cliente.php
session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }
$idAdvogado = $_SESSION['id_usuario_logado'];
if (!isset($_GET['id'])) { header("Location: lista_clientes.php"); exit; }
$idCliente = $_GET['id'];

// LÓGICA MANTIDA INTACTA
$cliente = null;
$arquivoClientes = '../dados/Clientes_' . $idAdvogado . '.json';
if (file_exists($arquivoClientes)) {
    $listaClientes = json_decode(file_get_contents($arquivoClientes), true) ?? [];
    foreach ($listaClientes as $c) {
        if (isset($c['id']) && $c['id'] == $idCliente) { $cliente = $c; break; }
    }
}
if (!$cliente) { echo "<script>alert('Cliente não encontrado.'); window.location.href='lista_clientes.php';</script>"; exit; }

$processosDoCliente = [];
$processosAtivos = 0;
$nomesProcessosMap = []; 
foreach (glob('../dados/Processos_*.json') as $arq) {
    $lista = json_decode(file_get_contents($arq), true) ?? [];
    foreach ($lista as $p) { 
        if (isset($p['cliente_id']) && $p['cliente_id'] == $idCliente) {
            $processosDoCliente[] = $p; 
            $idProc = $p['id'] ?? '';
            if(!empty($idProc)) {
                $nomesProcessosMap[$idProc] = ($p['numero_processo'] ?? 'Sem Número') . ' (' . ($p['tipo_acao'] ?? 'Ação') . ')';
            }
            if(isset($p['status']) && $p['status'] != 'Arquivado' && $p['status'] != 'Encerrado') $processosAtivos++;
        }
    }
}

$financeiroDoCliente = []; 
$totalRecebido = 0;
foreach (glob('../dados/Financeiro_*.json') as $arq) {
    $listaFin = json_decode(file_get_contents($arq), true) ?? [];
    foreach ($listaFin as $f) {
        if (isset($f['cliente_id']) && $f['cliente_id'] == $idCliente && empty($f['deletado'])) {
            $financeiroDoCliente[] = $f;
            $statusItem = strtolower($f['status'] ?? '');
            if (isset($f['tipo']) && $f['tipo'] == 'Receita' && ($statusItem == 'pago' || $statusItem == 'paga')) {
                $totalRecebido += (float)$f['valor'];
            }
        }
    }
}
usort($financeiroDoCliente, function($a, $b) { return strtotime($a['data_vencimento']) - strtotime($b['data_vencimento']); });
$ticketMedio = count($processosDoCliente) > 0 ? $totalRecebido / count($processosDoCliente) : $totalRecebido;

$agendaDoCliente = [];
$hoje = date('Y-m-d');
foreach (glob('../dados/Agenda_*.json') as $arq) {
    $listaAg = json_decode(file_get_contents($arq), true) ?? [];
    foreach ($listaAg as $ag) {
        if (isset($ag['cliente_id']) && $ag['cliente_id'] == $idCliente && $ag['data_evento'] >= $hoje) {
            $agendaDoCliente[] = $ag;
        }
    }
}
usort($agendaDoCliente, function($a, $b) { return strtotime($a['data_evento']) - strtotime($b['data_evento']); });

$atendimentos = [];
$arqAten = '../dados/Atendimentos_' . $idCliente . '.json';
if (file_exists($arqAten)) {
    $atendimentos = json_decode(file_get_contents($arqAten), true) ?? [];
    usort($atendimentos, function($a, $b) { return strtotime($b['data_hora']) - strtotime($a['data_hora']); });
}
$clienteDesde = isset($cliente['data_cadastro']) ? date('Y', strtotime($cliente['data_cadastro'])) : date('Y');
$diasUltimoContato = "Sem contato";
if (!empty($atendimentos)) {
    $dataUltimo = new DateTime($atendimentos[0]['data_hora']);
    $diferenca = (new DateTime())->diff($dataUltimo)->days;
    $diasUltimoContato = $diferenca == 0 ? "Hoje" : $diferenca . " dia(s) atrás";
}

$documentos = [];
$arqDocs = '../dados/Documentos_' . $idCliente . '.json';
if (file_exists($arqDocs)) { $documentos = json_decode(file_get_contents($arqDocs), true) ?? []; }

$abaAtiva = $_GET['aba'] ?? 'resumo';
$corStatus = 'bg-secondary';
$statusTexto = $cliente['status_cliente'] ?? 'Não definido';
if ($statusTexto == 'Ativo') $corStatus = 'bg-success';
if ($statusTexto == 'Em negociação') $corStatus = 'bg-warning text-dark';
if ($statusTexto == 'Inadimplente') $corStatus = 'bg-danger';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JURIDEX - Ficha 360 do Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .main-content { margin-left: 280px; min-height: 100vh; display: flex; flex-direction: column; transition: margin-left 0.3s ease; }
        .topbar { background: #fff; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; }
        
        .header-360 { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); color: white; border-radius: 10px; }
        .resumo-inteligente { background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px 15px; font-size: 0.9rem; }
        .nav-tabs .nav-link.active { font-weight: bold; color: #3498db; border-bottom: 3px solid #3498db; }
        .nav-tabs .nav-link { color: #555; font-weight: 500; border: none; }
        .tab-content { background: white; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 10px 10px; padding: 30px; }
        .timeline-item { border-left: 3px solid #3498db; padding-left: 15px; margin-bottom: 15px; position: relative; }
        .timeline-item::before { content: ''; width: 11px; height: 11px; background: #3498db; border-radius: 50%; position: absolute; left: -7px; top: 5px; }
        .ltv-card { background-color: #f8f9fa; border-left: 4px solid #198754; padding: 15px; border-radius: 5px; }

        @media (max-width: 991px) {
            .main-content { margin-left: 0; width: 100%; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h4 class="mb-0 fw-bold text-dark">Ficha 360 do Cliente</h4>
        <a href="lista_clientes.php" class="btn btn-dark btn-sm fw-bold">Voltar à Lista</a>
    </div>

    <div class="container-fluid px-4 mb-5">
        <div class="header-360 p-4 mb-4 shadow-sm">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <span class="badge <?php echo $corStatus; ?> mb-2 fs-6 border border-light"><?php echo $statusTexto; ?></span>
                    <?php if(!empty($cliente['score_cliente']) && $cliente['score_cliente'] == 'VIP') echo '<span class="badge bg-warning text-dark mb-2 ms-2 fs-6">⭐ VIP</span>'; ?>
                    <?php if(!empty($cliente['score_cliente']) && $cliente['score_cliente'] == 'Risco') echo '<span class="badge bg-danger mb-2 ms-2 fs-6">⚠️ Risco</span>'; ?>
                    
                    <h2 class="fw-bold mb-1">👤 <?php echo htmlspecialchars($cliente['nome'] ?? 'Nome não informado'); ?></h2>
                    <p class="mb-3 opacity-75">CPF/CNPJ: <?php echo htmlspecialchars($cliente['cpf_cnpj'] ?? ''); ?> | Tipo: <?php echo htmlspecialchars($cliente['tipo_cliente_juridico'] ?? ''); ?></p>
                    
                    <div class="resumo-inteligente d-inline-block shadow-sm">
                        <strong>Cliente desde:</strong> <?php echo $clienteDesde; ?> &nbsp;|&nbsp; 
                        <strong>Processos:</strong> <?php echo $processosAtivos; ?> &nbsp;|&nbsp; 
                        <strong>Honorários:</strong> R$ <?php echo number_format($totalRecebido, 2, ',', '.'); ?> &nbsp;|&nbsp; 
                        <strong>Contato:</strong> <?php echo $diasUltimoContato; ?>
                    </div>
                </div>
                
                <div class="col-md-5 text-end mt-3 mt-md-0">
                    <a href="cadastro_cliente.php?id=<?php echo htmlspecialchars($cliente['id']); ?>" class="btn btn-sm btn-light fw-bold mb-1 shadow-sm px-3">✏️ Editar Ficha</a>
                    <a href="cadastro_processo.php?cliente_id=<?php echo htmlspecialchars($cliente['id']); ?>" class="btn btn-sm btn-info fw-bold mb-1 shadow-sm px-3">⚖️ Novo Processo</a>
                    <button class="btn btn-sm btn-warning fw-bold mb-1 shadow-sm px-3" onclick="document.getElementById('documentos-tab').click();">📄 Documento</button>
                    <button class="btn btn-sm btn-success fw-bold mb-1 shadow-sm px-3" onclick="document.getElementById('resumo-tab').click(); document.getElementById('campo-anotacao').focus();">📞 Atendimento</button>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs flex-column flex-sm-row" id="myTab">
            <li class="nav-item"><button class="nav-link <?php if($abaAtiva == 'resumo') echo 'active'; ?>" id="resumo-tab" data-bs-toggle="tab" data-bs-target="#resumo" type="button">👤 Resumo Geral</button></li>
            <li class="nav-item"><button class="nav-link <?php if($abaAtiva == 'processos') echo 'active'; ?>" data-bs-toggle="tab" data-bs-target="#processos" type="button">⚖️ Processos</button></li>
            <li class="nav-item"><button class="nav-link text-success <?php if($abaAtiva == 'financeiro') echo 'active'; ?>" data-bs-toggle="tab" data-bs-target="#financeiro" type="button">💰 Financeiro</button></li>
            <li class="nav-item"><button class="nav-link text-dark fw-bold <?php if($abaAtiva == 'documentos') echo 'active'; ?>" id="documentos-tab" data-bs-toggle="tab" data-bs-target="#documentos" type="button">📁 Documentos</button></li>
            <li class="nav-item"><button class="nav-link text-primary <?php if($abaAtiva == 'ia') echo 'active'; ?>" data-bs-toggle="tab" data-bs-target="#ia" type="button">✨ IA do Cliente</button></li>
        </ul>

        <div class="tab-content shadow-sm" id="myTabContent">
            
            <div class="tab-pane fade <?php if($abaAtiva == 'resumo') echo 'show active'; ?>" id="resumo">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="ltv-card shadow-sm border-success">
                            <small class="text-success fw-bold text-uppercase">Lifetime Value (Valor Pago)</small>
                            <h3 class="fw-bold mb-0">R$ <?php echo number_format($totalRecebido, 2, ',', '.'); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="ltv-card shadow-sm border-primary">
                            <small class="text-primary fw-bold text-uppercase">Processos (Ativos / Total)</small>
                            <h3 class="fw-bold mb-0"><?php echo $processosAtivos; ?> / <?php echo count($processosDoCliente); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="ltv-card shadow-sm border-warning">
                            <small class="text-warning fw-bold text-uppercase">Ticket Médio</small>
                            <h3 class="fw-bold text-dark mb-0">R$ <?php echo number_format($ticketMedio, 2, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <h5 class="fw-bold border-bottom pb-2">Contatos e Endereço</h5>
                        <p class="mb-1"><strong>Telefone:</strong> <?php echo htmlspecialchars($cliente['telefone'] ?? ''); ?></p>
                        <p class="mb-1"><strong>E-mail:</strong> <?php echo htmlspecialchars($cliente['email'] ?? ''); ?></p>
                        <p class="mb-1 mt-3"><strong>Endereço:</strong> <?php echo htmlspecialchars(($cliente['rua'] ?? '') . ', ' . ($cliente['numero'] ?? '')); ?> - <?php echo htmlspecialchars(($cliente['bairro'] ?? '') . ' - ' . ($cliente['cidade'] ?? '') . '/' . ($cliente['estado'] ?? '')); ?></p>
                        
                        <?php if(!empty($cliente['valor_contrato']) || !empty($cliente['forma_pagamento'])) { ?>
                            <div class="alert alert-success mt-4 shadow-sm border-success">
                                <h6 class="fw-bold text-success mb-2">📄 Condições do Contrato Base</h6>
                                <p class="mb-1"><strong>Valor:</strong> R$ <?php echo number_format((float)($cliente['valor_contrato'] ?? 0), 2, ',', '.'); ?></p>
                                <p class="mb-1"><strong>Forma:</strong> <?php echo htmlspecialchars($cliente['forma_pagamento'] ?? '-'); ?></p>
                                <p class="mb-0"><strong>Parcelas:</strong> <?php echo htmlspecialchars($cliente['numero_parcelas'] ?? '-'); ?>x</p>
                            </div>
                        <?php } ?>

                        <h5 class="fw-bold border-bottom pb-2 mt-4">📅 Próximos Compromissos</h5>
                        <?php if(empty($agendaDoCliente)) { echo '<p class="text-muted small">Nenhum compromisso marcado.</p>'; } else { ?>
                            <ul class="list-group list-group-flush border rounded shadow-sm">
                                <?php foreach($agendaDoCliente as $ag) { ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($ag['titulo']); ?></h6>
                                            <small class="text-muted"><?php echo date('d/m/Y', strtotime($ag['data_evento'])); ?></small>
                                        </div>
                                        <span class="badge bg-danger"><?php echo htmlspecialchars($ag['tipo_evento']); ?></span>
                                    </li>
                                <?php } ?>
                            </ul>
                        <?php } ?>
                    </div>
                    
                    <div class="col-md-6">
                        <h5 class="fw-bold border-bottom pb-2">Linha do Tempo de Atendimento</h5>
                        <form method="POST" action="processar_cliente_extra.php" class="mb-4">
                            <input type="hidden" name="acao" value="adicionar_historico">
                            <input type="hidden" name="cliente_id" value="<?php echo htmlspecialchars($idCliente); ?>">
                            <div class="input-group shadow-sm">
                                <input type="text" class="form-control border-primary" id="campo-anotacao" name="anotacao" required>
                                <button class="btn btn-primary fw-bold" type="submit">Gravar</button>
                            </div>
                        </form>
                        <div style="max-height: 300px; overflow-y: auto;" class="pe-2">
                            <?php foreach($atendimentos as $atendimento) { ?>
                                <div class="timeline-item">
                                    <small class="text-primary fw-bold"><?php echo date('d/m/Y \à\s H:i', strtotime($atendimento['data_hora'])); ?></small>
                                    <p class="mb-0 text-dark"><?php echo htmlspecialchars($atendimento['anotacao']); ?></p>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade <?php if($abaAtiva == 'processos') echo 'show active'; ?>" id="processos">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="fw-bold">Processos Vinculados</h5>
                    <a href="cadastro_processo.php?cliente_id=<?php echo htmlspecialchars($idCliente); ?>" class="btn btn-sm btn-outline-primary">Novo Processo</a>
                </div>
                <div class="row g-3">
                    <?php foreach($processosDoCliente as $p) { ?>
                        <div class="col-md-6">
                            <div class="card shadow-sm border-0 border-start border-4 border-primary h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold text-primary mb-1"><?php echo htmlspecialchars($p['numero_processo'] ?? 'Sem Número'); ?></h6>
                                    <p class="mb-2 text-muted small"><?php echo htmlspecialchars($p['tipo_acao'] ?? 'Ação'); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($p['status'] ?? 'Ativo'); ?></span>
                                        <a href="perfil_processo.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-dark fw-bold">Abrir Processo</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="tab-pane fade <?php if($abaAtiva == 'financeiro') echo 'show active'; ?>" id="financeiro">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-success mb-0">Contas a Pagar e Receber</h5>
                    <a href="cadastro_financeiro.php?cliente_id=<?php echo htmlspecialchars($idCliente); ?>" class="btn btn-sm btn-success fw-bold shadow-sm">➕ Novo Lançamento</a>
                </div>
                
                <?php if(empty($financeiroDoCliente)) { ?>
                    <div class="alert alert-light text-center border text-muted p-4 mt-3">Nenhum lançamento financeiro.</div>
                <?php } else { ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle shadow-sm border mt-3">
                            <thead class="table-light">
                                <tr><th>Vencimento</th><th>Descrição / Processo</th><th>Tipo</th><th>Valor (R$)</th><th>Status</th><th class="text-end">Ações</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($financeiroDoCliente as $f) { 
                                    $isReceita = (isset($f['tipo']) && $f['tipo'] == 'Receita');
                                    $isPago = (strtolower($f['status'] ?? '') == 'pago');
                                    $corStatus = $isPago ? 'bg-success' : 'bg-warning text-dark';
                                    $hoje = date('Y-m-d');
                                    if(!$isPago && ($f['data_vencimento'] ?? '') < $hoje) $corStatus = 'bg-danger';
                                    
                                    $nomeProcesso = '-';
                                    if(!empty($f['processo_id']) && isset($nomesProcessosMap[$f['processo_id']])) {
                                        $nomeProcesso = $nomesProcessosMap[$f['processo_id']];
                                    }
                                ?>
                                    <tr>
                                        <td class="fw-bold <?php echo (!$isPago && ($f['data_vencimento'] ?? '') < $hoje) ? 'text-danger' : ''; ?>">
                                            <?php echo date('d/m/Y', strtotime($f['data_vencimento'] ?? date('Y-m-d'))); ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($f['descricao'] ?? 'Sem descrição'); ?></div>
                                            <?php if($nomeProcesso != '-') { echo "<small class='text-muted'>⚖️ {$nomeProcesso}</small>"; } ?>
                                        </td>
                                        <td><span class="badge <?php echo $isReceita ? 'bg-success' : 'bg-danger'; ?>"><?php echo htmlspecialchars($f['tipo'] ?? 'N/D'); ?></span></td>
                                        <td class="fw-bold text-<?php echo $isReceita ? 'success' : 'danger'; ?>">
                                            <?php echo number_format((float)($f['valor'] ?? 0), 2, ',', '.'); ?>
                                        </td>
                                        <td><span class="badge <?php echo $corStatus; ?> px-2 py-1"><?php echo htmlspecialchars($f['status'] ?? 'Pendente'); ?></span></td>
                                        <td class="text-end">
                                            <?php if(!$isPago) { ?>
                                                <a href="baixa_financeira.php?id=<?php echo htmlspecialchars($f['id_lancamento'] ?? ''); ?>" class="btn btn-sm btn-dark fw-bold">💰 Pagar</a>
                                            <?php } else { ?>
                                                <span class="text-success fw-bold small">✔ Pago</span>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            </div>

            <div class="tab-pane fade <?php if($abaAtiva == 'documentos') echo 'show active'; ?>" id="documentos">
                
                <div class="alert shadow-sm border-0 d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 p-4 rounded-3" style="background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);">
                    <div class="text-center text-md-start mb-3 mb-md-0">
                        <h5 class="fw-bold mb-1 text-white">Ferramenta Mágica: Mesclador de Documentos</h5>
                        <p class="mb-0 text-white-50">Junte CNH, Procuração, Imagens e Contratos do Cliente num único PDF.</p>
                    </div>
                    <a href="ferramenta_mesclar_pdf.php" class="btn btn-danger btn-lg fw-bold shadow-lg text-white border-white text-nowrap">📕 Juntar PDFs e Imagens</a>
                </div>

                <h5 class="fw-bold mb-3">Cofre de Documentos Pessoais</h5>
                <form method="POST" action="processar_cliente_extra.php" enctype="multipart/form-data" class="d-flex flex-column flex-md-row gap-2 mb-4 bg-light p-3 rounded border shadow-sm">
                    <input type="hidden" name="acao" value="upload_documento">
                    <input type="hidden" name="cliente_id" value="<?php echo htmlspecialchars($idCliente); ?>">
                    <input type="text" class="form-control" name="nome_documento" placeholder="Nome (Ex: RG, CNH, Comprovante)" required>
                    <input type="file" class="form-control" name="arquivo" accept=".pdf,.doc,.docx,.jpg,.png" required>
                    <button type="submit" class="btn btn-dark fw-bold px-4">Salvar no Cofre</button>
                </form>
                
                <div class="row g-3">
                    <?php if(empty($documentos)) { echo '<div class="col-12"><p class="text-muted text-center p-4 border border-dashed">Nenhum documento salvo. Anexe o RG, CPF ou Contratos acima.</p></div>'; } ?>
                    <?php foreach($documentos as $doc) { ?>
                        <div class="col-md-4 col-lg-3">
                            <div class="card text-center p-3 shadow-sm h-100 position-relative">
                                <a href="processar_cliente_extra.php?excluir_doc=<?php echo $doc['id_doc']; ?>&cliente_id=<?php echo htmlspecialchars($idCliente); ?>" class="position-absolute top-0 end-0 m-2 text-danger text-decoration-none" onclick="return confirm('Excluir arquivo do servidor?');" title="Excluir Documento">🗑️</a>
                                <h2 class="mb-2 mt-2"><?php echo $doc['extensao'] == 'pdf' ? '📕' : '📄'; ?></h2>
                                <h6 class="fw-bold text-truncate px-3" title="<?php echo htmlspecialchars($doc['nome_amigavel']); ?>"><?php echo htmlspecialchars($doc['nome_amigavel']); ?></h6>
                                <a href="<?php echo htmlspecialchars($doc['caminho_arquivo']); ?>" target="_blank" class="btn btn-sm btn-outline-dark mt-auto mx-3">Visualizar / Baixar</a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="tab-pane fade <?php if($abaAtiva == 'ia') echo 'show active'; ?>" id="ia">
                <h4 class="fw-bold text-primary text-center mb-4 mt-3">✨ Assistente de IA do Cliente</h4>
                <div class="row justify-content-center g-4">
                    <div class="col-md-6 col-lg-5">
                        <a href="gerador_peticao_ia.php?cliente_id=<?php echo htmlspecialchars($idCliente); ?>" class="btn btn-primary w-100 py-4 fw-bold shadow-lg">
                            <h2 class="mb-2">📄</h2>Gerar Petição Auto-Preenchida
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-5">
                        <a href="gerador_contratos.php?cliente_id=<?php echo htmlspecialchars($idCliente); ?>" class="btn btn-dark w-100 py-4 fw-bold shadow-lg">
                            <h2 class="mb-2">📜</h2>Gerar Contrato / Procuração
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>