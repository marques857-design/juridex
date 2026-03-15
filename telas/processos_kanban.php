<?php
// Arquivo: telas/processos_kanban.php
session_start();
if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }

$idAdvogado = $_SESSION['id_usuario_logado'];

// Dicionário de Clientes para mostrar o nome nos cards
$clientes = [];
$arquivoClientes = '../dados/Clientes_' . $idAdvogado . '.json';
if (file_exists($arquivoClientes)) {
    $listaC = json_decode(file_get_contents($arquivoClientes), true) ?? [];
    foreach($listaC as $c) { $clientes[$c['id']] = $c['nome']; }
}

// Carrega os Processos e organiza pelo Status
$funil = ['Ativo' => [], 'Suspenso' => [], 'Encerrado' => [], 'Arquivado' => []];

$arquivoProcessos = '../dados/Processos_' . $idAdvogado . '.json';
if (file_exists($arquivoProcessos)) {
    $meusProcessos = json_decode(file_get_contents($arquivoProcessos), true) ?? [];
    foreach ($meusProcessos as $p) {
        $status = $p['status'] ?? 'Ativo';
        if (array_key_exists($status, $funil)) {
            $funil[$status][] = $p;
        } else {
            $funil['Ativo'][] = $p;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - Kanban de Processos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .kanban-board { display: flex; overflow-x: auto; padding-bottom: 20px; gap: 20px; align-items: flex-start; }
        .kanban-col { min-width: 320px; max-width: 320px; background-color: #f4f5f7; border-radius: 8px; padding: 15px; }
        .kanban-card { background: white; border-radius: 6px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #ccc; transition: 0.2s; }
        .kanban-card:hover { transform: translateY(-3px); box-shadow: 0 5px 10px rgba(0,0,0,0.15); }
        .border-ativo { border-left-color: #198754; }
        .border-suspenso { border-left-color: #ffc107; }
        .border-encerrado { border-left-color: #212529; }
        .border-arquivado { border-left-color: #6c757d; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-white" href="painel.php">JURIDEX</a>
        <div>
            <a href="lista_processos.php" class="btn btn-outline-light btn-sm me-2">📋 Ver em Lista</a>
            <a href="painel.php" class="btn btn-outline-light btn-sm">Voltar ao Painel</a>
        </div>
    </div>
</nav>

<div class="container-fluid mt-4 mb-5 px-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">⚖️ Quadro Visual de Processos (Kanban)</h3>
            <p class="text-muted">Acompanhe a saúde processual do escritório em tempo real.</p>
        </div>
    </div>

    <div class="kanban-board">
        
        <div class="kanban-col shadow-sm">
            <h6 class="fw-bold text-success mb-3 text-uppercase">🟢 Em Andamento (<?php echo count($funil['Ativo']); ?>)</h6>
            <?php foreach ($funil['Ativo'] as $p) { ?>
                <div class="kanban-card border-ativo">
                    <?php if(isset($p['prioridade']) && strpos($p['prioridade'], 'Urgente') !== false) echo '<span class="badge bg-danger mb-2">🚨 Urgente</span>'; ?>
                    <h6 class="fw-bold mb-1 text-primary"><?php echo htmlspecialchars($p['numero_processo']); ?></h6>
                    <small class="fw-bold text-dark d-block">👤 <?php echo htmlspecialchars($clientes[$p['cliente_id']] ?? 'Cliente'); ?></small>
                    <small class="text-muted d-block mb-3">Fase: <?php echo htmlspecialchars($p['fase_processual'] ?? 'Inicial'); ?></small>
                    <a href="perfil_processo.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-success btn-sm w-100 fw-bold">Abrir Ficha 360</a>
                </div>
            <?php } ?>
        </div>

        <div class="kanban-col shadow-sm">
            <h6 class="fw-bold text-warning mb-3 text-uppercase">🟡 Suspensos / Sobrestados (<?php echo count($funil['Suspenso']); ?>)</h6>
            <?php foreach ($funil['Suspenso'] as $p) { ?>
                <div class="kanban-card border-suspenso">
                    <h6 class="fw-bold mb-1 text-primary"><?php echo htmlspecialchars($p['numero_processo']); ?></h6>
                    <small class="fw-bold text-dark d-block">👤 <?php echo htmlspecialchars($clientes[$p['cliente_id']] ?? 'Cliente'); ?></small>
                    <a href="perfil_processo.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-warning btn-sm w-100 text-dark fw-bold mt-3">Abrir Ficha 360</a>
                </div>
            <?php } ?>
        </div>

        <div class="kanban-col shadow-sm">
            <h6 class="fw-bold text-dark mb-3 text-uppercase">⚫ Trânsito em Julgado (<?php echo count($funil['Encerrado']); ?>)</h6>
            <?php foreach ($funil['Encerrado'] as $p) { ?>
                <div class="kanban-card border-encerrado">
                    <h6 class="fw-bold mb-1 text-primary"><?php echo htmlspecialchars($p['numero_processo']); ?></h6>
                    <small class="fw-bold text-dark d-block">👤 <?php echo htmlspecialchars($clientes[$p['cliente_id']] ?? 'Cliente'); ?></small>
                    <small class="text-success fw-bold d-block mt-1">R$ <?php echo number_format((float)($p['valor_causa'] ?? 0), 2, ',', '.'); ?></small>
                    <a href="perfil_processo.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-dark btn-sm w-100 fw-bold mt-3">Ver Ficha</a>
                </div>
            <?php } ?>
        </div>

        <div class="kanban-col shadow-sm">
            <h6 class="fw-bold text-secondary mb-3 text-uppercase">📁 Arquivados (<?php echo count($funil['Arquivado']); ?>)</h6>
            <?php foreach ($funil['Arquivado'] as $p) { ?>
                <div class="kanban-card border-arquivado">
                    <h6 class="fw-bold mb-1 opacity-50"><?php echo htmlspecialchars($p['numero_processo']); ?></h6>
                    <small class="text-muted d-block mb-3">👤 <?php echo htmlspecialchars($clientes[$p['cliente_id']] ?? 'Cliente'); ?></small>
                    <a href="perfil_processo.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-secondary btn-sm w-100">Ver Ficha</a>
                </div>
            <?php } ?>
        </div>

    </div>
</div>

</body>
</html>