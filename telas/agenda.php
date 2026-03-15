<?php
// Arquivo: telas/agenda.php
// Função: Gestão Global de Prazos (Layout Enterprise Sidebar)

session_start();
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE); 
ini_set('display_errors', 0);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }

$idEscritorio = $_SESSION['id_usuario_logado'];
$arquivoPadrao = '../dados/Agenda_' . $idEscritorio . '.json';
$arquivoClientes = '../dados/Clientes_' . $idEscritorio . '.json';

// O ASPIRADOR E CORRETOR DE DADOS
$agenda = [];
$arquivosExistentes = glob('../dados/*genda*.json'); 

if ($arquivosExistentes !== false) {
    foreach ($arquivosExistentes as $arq) {
        $conteudo = json_decode(file_get_contents($arq), true);
        $modificado = false; 
        
        if (is_array($conteudo)) {
            foreach ($conteudo as $k => $p) {
                if (isset($p['titulo'])) { 
                    if (empty($p['id'])) { $conteudo[$k]['id'] = uniqid('ev_'); $p['id'] = $conteudo[$k]['id']; $modificado = true; }
                    if (empty($p['data_evento']) && isset($p['data_limite'])) { $conteudo[$k]['data_evento'] = $p['data_limite']; $p['data_evento'] = $p['data_limite']; $modificado = true; }
                    if (!isset($p['hora_evento'])) { $conteudo[$k]['hora_evento'] = $p['hora_limite'] ?? '00:00'; $p['hora_evento'] = $conteudo[$k]['hora_evento']; $modificado = true; }
                    if (!isset($p['tipo_evento'])) { $conteudo[$k]['tipo_evento'] = 'Prazo'; $p['tipo_evento'] = 'Prazo'; $modificado = true; }
                    if (!isset($p['cliente_vinculado'])) { $conteudo[$k]['cliente_vinculado'] = 'Não informado'; $p['cliente_vinculado'] = 'Não informado'; $modificado = true; }
                    if (!isset($p['descricao'])) { $conteudo[$k]['descricao'] = ''; $p['descricao'] = ''; $modificado = true; }

                    $p['arquivo_origem'] = $arq;
                    $agenda[] = $p;
                }
            }
            if ($modificado) {
                file_put_contents($arq, json_encode(array_values($conteudo), JSON_PRETTY_PRINT));
            }
        }
    }
}

// PROCESSAR FORMULÁRIOS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] == 'novo_prazo') {
        $dadosPadrao = file_exists($arquivoPadrao) ? json_decode(file_get_contents($arquivoPadrao), true) : [];
        if (!is_array($dadosPadrao)) $dadosPadrao = [];
        
        $dadosPadrao[] = [
            'id' => uniqid(),
            'titulo' => trim($_POST['titulo']),
            'data_evento' => $_POST['data_evento'],
            'hora_evento' => $_POST['hora_evento'],
            'tipo_evento' => $_POST['tipo_evento'],
            'cliente_vinculado' => $_POST['cliente_vinculado'],
            'descricao' => trim($_POST['descricao']),
            'status' => 'Pendente',
            'id_advogado_responsavel' => $idEscritorio
        ];
        file_put_contents($arquivoPadrao, json_encode(array_values($dadosPadrao), JSON_PRETTY_PRINT));
        header("Location: agenda.php?msg=sucesso"); exit;
    }

    if ($_POST['acao'] == 'editar_prazo') {
        $idEditar = $_POST['id_prazo'];
        $arqOrigem = $_POST['arquivo_origem'];
        
        if (file_exists($arqOrigem)) {
            $dadosOrigem = json_decode(file_get_contents($arqOrigem), true);
            foreach ($dadosOrigem as $key => $item) {
                if (isset($item['id']) && $item['id'] == $idEditar) {
                    $dadosOrigem[$key]['titulo'] = trim($_POST['titulo']);
                    $dadosOrigem[$key]['data_evento'] = $_POST['data_evento'];
                    $dadosOrigem[$key]['hora_evento'] = $_POST['hora_evento'];
                    $dadosOrigem[$key]['tipo_evento'] = $_POST['tipo_evento'];
                    $dadosOrigem[$key]['cliente_vinculado'] = $_POST['cliente_vinculado'];
                    $dadosOrigem[$key]['descricao'] = trim($_POST['descricao']);
                    break;
                }
            }
            file_put_contents($arqOrigem, json_encode(array_values($dadosOrigem), JSON_PRETTY_PRINT));
        }
        header("Location: agenda.php?msg=editado"); exit;
    }
}

// CONCLUIR E EXCLUIR
if (isset($_GET['concluir']) || isset($_GET['excluir'])) {
    $idAlvo = isset($_GET['concluir']) ? $_GET['concluir'] : $_GET['excluir'];
    $acao = isset($_GET['concluir']) ? 'concluir' : 'excluir';
    
    foreach ($arquivosExistentes as $arq) {
        $dados = json_decode(file_get_contents($arq), true);
        if (is_array($dados)) {
            $modificado = false;
            foreach ($dados as $k => $v) {
                if (isset($v['id']) && $v['id'] == $idAlvo) {
                    if ($acao == 'excluir') { unset($dados[$k]); } 
                    else { $dados[$k]['status'] = 'Concluido'; }
                    $modificado = true; break;
                }
            }
            if ($modificado) {
                file_put_contents($arq, json_encode(array_values($dados), JSON_PRETTY_PRINT));
            }
        }
    }
    $msg = ($acao == 'concluir') ? 'concluido' : 'excluido';
    header("Location: agenda.php?msg=" . $msg); exit;
}

// ORGANIZAR OS PRAZOS
$hoje = date('Y-m-d');
$prazosAtrasados = []; $prazosHoje = []; $prazosFuturos = []; $prazosConcluidos = [];

usort($agenda, function($a, $b) {
    return strtotime($a['data_evento'] . ' ' . $a['hora_evento']) - strtotime($b['data_evento'] . ' ' . $b['hora_evento']);
});

foreach ($agenda as $p) {
    $status = $p['status'] ?? 'Pendente';
    $dataEv = $p['data_evento'];
    
    if (strtolower($status) == 'concluido' || strtolower($status) == 'concluído') { 
        $prazosConcluidos[] = $p; 
    } else {
        if ($dataEv < $hoje) { $prazosAtrasados[] = $p; } 
        elseif ($dataEv == $hoje) { $prazosHoje[] = $p; } 
        else { $prazosFuturos[] = $p; }
    }
}

// CARREGAR CLIENTES
$clientesOptions = "<option value='Sem Cliente Vinculado'>Nenhum / Administrativo</option>";
if (file_exists($arquivoClientes)) {
    $listaC = json_decode(file_get_contents($arquivoClientes), true) ?? [];
    usort($listaC, function($a, $b) { return strcmp($a['nome'], $b['nome']); });
    foreach($listaC as $c) {
        $clientesOptions .= "<option value='" . htmlspecialchars($c['nome']) . "'>👥 " . htmlspecialchars($c['nome']) . "</option>";
    }
}

function getCorTipo($tipo) {
    $t = strtolower($tipo);
    if($t == 'audiência' || $t == 'audiencia') return 'bg-primary';
    if($t == 'prazo fatal') return 'bg-danger';
    if($t == 'diligência' || $t == 'diligencia') return 'bg-info text-dark';
    return 'bg-secondary';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - Agenda Global</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        
        .main-content { margin-left: 280px; min-height: 100vh; display: flex; flex-direction: column; }
        .topbar { background: #fff; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; }
        
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .section-title { font-weight: 800; color: #1c1f3b; margin-bottom: 15px; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; }
        
        .prazo-card { background: white; border-radius: 8px; border-left: 5px solid #ccc; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: 0.2s;}
        .prazo-card:hover { transform: translateX(5px); }
        
        .prazo-atrasado { border-left-color: #dc3545; background-color: #fff8f8;}
        .prazo-hoje { border-left-color: #ffc107; background-color: #fffcf2;}
        .prazo-futuro { border-left-color: #0084ff; }
        .prazo-concluido { border-left-color: #198754; opacity: 0.7; }
        
        .prazo-data { font-size: 1.1rem; font-weight: 900; }
        .prazo-hora { font-size: 0.85rem; color: #666; font-weight: bold; background: #eee; padding: 2px 8px; border-radius: 10px; }
        .prazo-titulo { font-size: 1.1rem; font-weight: bold; color: #333; margin: 5px 0; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h4 class="mb-0 fw-bold text-dark">Central de Prazos</h4>
        <button class="btn btn-dark fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovoPrazo">➕ Novo Evento</button>
    </div>

    <div class="container-fluid px-4 mb-5">

        <?php if(isset($_GET['msg'])): ?>
            <?php if($_GET['msg'] == 'sucesso') echo "<div class='alert alert-success fw-bold shadow-sm'>✅ Evento agendado!</div>"; ?>
            <?php if($_GET['msg'] == 'editado') echo "<div class='alert alert-info fw-bold shadow-sm'>✏️ Evento atualizado!</div>"; ?>
            <?php if($_GET['msg'] == 'concluido') echo "<div class='alert alert-success fw-bold shadow-sm'>🎯 Marcado como concluído!</div>"; ?>
            <?php if($_GET['msg'] == 'excluido') echo "<div class='alert alert-warning fw-bold shadow-sm'>🗑️ Evento excluído.</div>"; ?>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-md-8">
                <div class="card card-custom p-4">
                    
                    <?php if(count($prazosAtrasados) > 0): ?>
                        <h5 class="section-title text-danger">⚠️ Prazos Atrasados</h5>
                        <?php foreach($prazosAtrasados as $p): ?>
                            <div class="prazo-card prazo-atrasado d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="prazo-data text-danger"><?php echo date('d/m/Y', strtotime($p['data_evento'])); ?></span>
                                        <span class="prazo-hora"><?php echo $p['hora_evento']; ?></span>
                                        <span class="badge <?php echo getCorTipo($p['tipo_evento']); ?> ms-2"><?php echo htmlspecialchars($p['tipo_evento']); ?></span>
                                    </div>
                                    <div class="prazo-titulo"><?php echo htmlspecialchars($p['titulo']); ?></div>
                                </div>
                                <div class="btn-group shadow-sm">
                                    <button class="btn btn-sm btn-light border fw-bold text-secondary" onclick="abrirModalEditar('<?php echo $p['id']; ?>', '<?php echo htmlspecialchars($p['titulo'], ENT_QUOTES); ?>', '<?php echo $p['data_evento']; ?>', '<?php echo $p['hora_evento']; ?>', '<?php echo htmlspecialchars($p['tipo_evento'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['cliente_vinculado'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['descricao'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['arquivo_origem'], ENT_QUOTES); ?>')" title="Editar">✏️ Editar</button>
                                    <a href="agenda.php?concluir=<?php echo $p['id']; ?>" class="btn btn-sm btn-success fw-bold border-success">✔️ Concluir</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <h5 class="section-title text-warning mt-4" style="color: #d39e00 !important;">🔥 Vence Hoje</h5>
                    <?php if(count($prazosHoje) == 0): ?>
                        <p class="text-muted small">Nenhum prazo urgente para hoje.</p>
                    <?php else: ?>
                        <?php foreach($prazosHoje as $p): ?>
                            <div class="prazo-card prazo-hoje d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="prazo-data text-warning" style="color: #d39e00 !important;">Hoje</span>
                                        <span class="prazo-hora"><?php echo $p['hora_evento']; ?></span>
                                        <span class="badge <?php echo getCorTipo($p['tipo_evento']); ?> ms-2"><?php echo htmlspecialchars($p['tipo_evento']); ?></span>
                                    </div>
                                    <div class="prazo-titulo"><?php echo htmlspecialchars($p['titulo']); ?></div>
                                </div>
                                <div class="btn-group shadow-sm">
                                    <button class="btn btn-sm btn-light border fw-bold text-secondary" onclick="abrirModalEditar('<?php echo $p['id']; ?>', '<?php echo htmlspecialchars($p['titulo'], ENT_QUOTES); ?>', '<?php echo $p['data_evento']; ?>', '<?php echo $p['hora_evento']; ?>', '<?php echo htmlspecialchars($p['tipo_evento'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['cliente_vinculado'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['descricao'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['arquivo_origem'], ENT_QUOTES); ?>')" title="Editar">✏️ Editar</button>
                                    <a href="agenda.php?concluir=<?php echo $p['id']; ?>" class="btn btn-sm btn-success fw-bold border-success">✔️ Concluir</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <h5 class="section-title text-primary mt-4">📅 Próximos Eventos</h5>
                    <?php if(count($prazosFuturos) == 0): ?>
                        <p class="text-muted small">Nenhum evento futuro agendado.</p>
                    <?php else: ?>
                        <?php foreach($prazosFuturos as $p): ?>
                            <div class="prazo-card prazo-futuro d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="prazo-data" style="color: #0084ff;"><?php echo date('d/m/Y', strtotime($p['data_evento'])); ?></span>
                                        <span class="prazo-hora"><?php echo $p['hora_evento']; ?></span>
                                        <span class="badge <?php echo getCorTipo($p['tipo_evento']); ?> ms-2"><?php echo htmlspecialchars($p['tipo_evento']); ?></span>
                                    </div>
                                    <div class="prazo-titulo"><?php echo htmlspecialchars($p['titulo']); ?></div>
                                </div>
                                <div class="btn-group shadow-sm">
                                    <button class="btn btn-sm btn-light border fw-bold text-secondary" onclick="abrirModalEditar('<?php echo $p['id']; ?>', '<?php echo htmlspecialchars($p['titulo'], ENT_QUOTES); ?>', '<?php echo $p['data_evento']; ?>', '<?php echo $p['hora_evento']; ?>', '<?php echo htmlspecialchars($p['tipo_evento'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['cliente_vinculado'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['descricao'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['arquivo_origem'], ENT_QUOTES); ?>')" title="Editar">✏️ Editar</button>
                                    <a href="agenda.php?concluir=<?php echo $p['id']; ?>" class="btn btn-sm btn-success fw-bold border-success">✔️ Concluir</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-custom p-4 bg-light">
                    <h5 class="section-title text-success">✅ Concluídos</h5>
                    <?php if(count($prazosConcluidos) == 0): ?>
                        <p class="text-muted small">Nenhum prazo concluído ainda.</p>
                    <?php else: ?>
                        <?php foreach($prazosConcluidos as $p): ?>
                            <div class="prazo-card prazo-concluido">
                                <div class="prazo-titulo text-decoration-line-through text-muted" style="font-size: 0.95rem;"><?php echo htmlspecialchars($p['titulo']); ?></div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="badge bg-success">Feito</span>
                                    <a href="agenda.php?excluir=<?php echo $p['id']; ?>" class="text-danger small text-decoration-none fw-bold" onclick="return confirm('Excluir definitivamente?');">🗑️ Excluir</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNovoPrazo" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content"><div class="modal-header bg-dark text-white"><h5 class="modal-title fw-bold">➕ Agendar Novo Evento</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <form method="POST" action="agenda.php"><div class="modal-body"><input type="hidden" name="acao" value="novo_prazo"><div class="row g-2 mb-3"><div class="col-8"><label class="form-label fw-bold">Título</label><input type="text" name="titulo" class="form-control" required></div><div class="col-4"><label class="form-label fw-bold">Tipo</label><select name="tipo_evento" class="form-select"><option value="Prazo Fatal">Prazo Fatal</option><option value="Audiência">Audiência</option><option value="Diligência">Diligência</option><option value="Outros">Outros</option></select></div></div><div class="row g-2 mb-3"><div class="col-6"><label class="form-label fw-bold">Data</label><input type="date" name="data_evento" class="form-control" required></div><div class="col-6"><label class="form-label fw-bold">Hora</label><input type="time" name="hora_evento" class="form-control" value="23:59" required></div></div><div class="mb-3"><label class="form-label fw-bold">Cliente Vinculado</label><select name="cliente_vinculado" class="form-select"><?php echo $clientesOptions; ?></select></div><div class="mb-3"><label class="form-label fw-bold">Detalhes</label><textarea name="descricao" class="form-control" rows="2"></textarea></div></div><div class="modal-footer bg-light"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary fw-bold">Salvar na Agenda</button></div></form></div></div>
</div>

<div class="modal fade" id="modalEditarPrazo" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content"><div class="modal-header bg-primary text-white"><h5 class="modal-title fw-bold">✏️ Editar Evento</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <form method="POST" action="agenda.php"><div class="modal-body"><input type="hidden" name="acao" value="editar_prazo"><input type="hidden" name="id_prazo" id="edit_id_prazo"><input type="hidden" name="arquivo_origem" id="edit_arquivo_origem"><div class="row g-2 mb-3"><div class="col-8"><label class="form-label fw-bold">Título</label><input type="text" name="titulo" id="edit_titulo" class="form-control" required></div><div class="col-4"><label class="form-label fw-bold">Tipo</label><select name="tipo_evento" id="edit_tipo" class="form-select"><option value="Prazo Fatal">Prazo Fatal</option><option value="Audiência">Audiência</option><option value="Diligência">Diligência</option><option value="Outros">Outros</option></select></div></div><div class="row g-2 mb-3"><div class="col-6"><label class="form-label fw-bold">Data</label><input type="date" name="data_evento" id="edit_data" class="form-control" required></div><div class="col-6"><label class="form-label fw-bold">Hora</label><input type="time" name="hora_evento" id="edit_hora" class="form-control" required></div></div><div class="mb-3"><label class="form-label fw-bold">Cliente Vinculado</label><select name="cliente_vinculado" id="edit_cliente" class="form-select"><?php echo $clientesOptions; ?></select></div><div class="mb-3"><label class="form-label fw-bold">Detalhes</label><textarea name="descricao" id="edit_descricao" class="form-control" rows="2"></textarea></div></div><div class="modal-footer bg-light"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary fw-bold">💾 Atualizar</button></div></form></div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function abrirModalEditar(id, titulo, data, hora, tipo, cliente, descricao, arquivo) {
    document.getElementById('edit_id_prazo').value = id;
    document.getElementById('edit_titulo').value = titulo;
    document.getElementById('edit_data').value = data;
    document.getElementById('edit_hora').value = hora;
    document.getElementById('edit_tipo').value = tipo;
    document.getElementById('edit_cliente').value = cliente;
    document.getElementById('edit_descricao').value = descricao;
    document.getElementById('edit_arquivo_origem').value = arquivo;
    var myModal = new bootstrap.Modal(document.getElementById('modalEditarPrazo'));
    myModal.show();
}
</script>
</body>
</html>