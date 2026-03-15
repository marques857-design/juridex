<?php
// Arquivo: telas/crm_kanban.php
session_start();
if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }

$idAdvogado = $_SESSION['id_usuario_logado'];
$arquivoClientes = '../dados/Clientes_' . $idAdvogado . '.json';
$meusClientes = [];

if (file_exists($arquivoClientes)) {
    $meusClientes = json_decode(file_get_contents($arquivoClientes), true) ?? [];
}

// Organiza os clientes por Status
$funil = [
    'Potencial' => [],
    'Em negociação' => [],
    'Ativo' => [],
    'Inadimplente' => [],
    'Arquivado' => []
];

foreach ($meusClientes as $c) {
    $status = $c['status_cliente'] ?? 'Potencial'; // Joga os sem status no começo do funil
    if (array_key_exists($status, $funil)) {
        $funil[$status][] = $c;
    } else {
        $funil['Potencial'][] = $c;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - CRM Pipeline Kanban</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .kanban-board { display: flex; overflow-x: auto; padding-bottom: 20px; gap: 20px; }
        .kanban-col { min-width: 300px; max-width: 300px; background-color: #f4f5f7; border-radius: 8px; padding: 15px; }
        .kanban-card { background: white; border-radius: 6px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #ccc; transition: 0.2s; }
        .kanban-card:hover { transform: translateY(-3px); box-shadow: 0 5px 10px rgba(0,0,0,0.15); }
        .border-potencial { border-left-color: #0d6efd; }
        .border-negociacao { border-left-color: #ffc107; }
        .border-ativo { border-left-color: #198754; }
        .border-inadimplente { border-left-color: #dc3545; }
        .border-arquivado { border-left-color: #6c757d; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-white" href="painel.php">JURIDEX</a>
        <div>
            <a href="lista_clientes.php" class="btn btn-outline-light btn-sm me-2">📋 Ver em Lista</a>
            <a href="painel.php" class="btn btn-outline-light btn-sm">Voltar ao Painel</a>
        </div>
    </div>
</nav>

<div class="container-fluid mt-4 mb-5 px-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">📊 Pipeline de Clientes (Kanban)</h3>
            <p class="text-muted">Acompanhe a evolução da sua carteira de clientes.</p>
        </div>
        <button class="btn btn-primary fw-bold shadow" onclick="alert('IA: Você possui ' + <?php echo count($funil['Inadimplente']); ?> + ' clientes inadimplentes e ' + <?php echo count($funil['Potencial']); ?> + ' oportunidades de negócio para fechar contrato. Foco neles hoje!')">
            🧠 IA: Analisar Carteira
        </button>
    </div>

    <div class="kanban-board">
        
        <div class="kanban-col shadow-sm">
            <h6 class="fw-bold text-primary mb-3 text-uppercase">🔵 Potencial / Leads (<?php echo count($funil['Potencial']); ?>)</h6>
            <?php foreach ($funil['Potencial'] as $c) { ?>
                <div class="kanban-card border-potencial">
                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($c['nome']); ?></h6>
                    <small class="text-muted d-block mb-2"><?php echo htmlspecialchars($c['telefone']); ?></small>
                    <a href="perfil_cliente.php?id=<?php echo $c['id']; ?>" class="btn btn-outline-primary btn-sm w-100 fw-bold">Abrir Ficha</a>
                </div>
            <?php } ?>
        </div>

        <div class="kanban-col shadow-sm">
            <h6 class="fw-bold text-warning mb-3 text-uppercase">🟡 Em Negociação (<?php echo count($funil['Em negociação']); ?>)</h6>
            <?php foreach ($funil['Em negociação'] as $c) { ?>
                <div class="kanban-card border-negociacao">
                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($c['nome']); ?></h6>
                    <small class="text-muted d-block mb-2">Valor: R$ <?php echo htmlspecialchars($c['valor_contrato'] ?? '0,00'); ?></small>
                    <a href="perfil_cliente.php?id=<?php echo $c['id']; ?>" class="btn btn-outline-warning btn-sm w-100 text-dark fw-bold">Abrir Ficha</a>
                </div>
            <?php } ?>
        </div>

        <div class="kanban-col shadow-sm">
            <h6 class="fw-bold text-success mb-3 text-uppercase">🟢 Ativos (<?php echo count($funil['Ativo']); ?>)</h6>
            <?php foreach ($funil['Ativo'] as $c) { ?>
                <div class="kanban-card border-ativo">
                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($c['nome']); ?> <?php if(isset($c['score_cliente']) && $c['score_cliente'] == 'VIP') echo '⭐'; ?></h6>
                    <small class="text-muted d-block mb-2"><?php echo htmlspecialchars($c['area_juridica'] ?? 'Cível'); ?></small>
                    <a href="perfil_cliente.php?id=<?php echo $c['id']; ?>" class="btn btn-outline-success btn-sm w-100 fw-bold">Abrir Ficha</a>
                </div>
            <?php } ?>
        </div>

        <div class="kanban-col shadow-sm">
            <h6 class="fw-bold text-danger mb-3 text-uppercase">🔴 Inadimplente (<?php echo count($funil['Inadimplente']); ?>)</h6>
            <?php foreach ($funil['Inadimplente'] as $c) { ?>
                <div class="kanban-card border-inadimplente">
                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($c['nome']); ?></h6>
                    <small class="text-danger fw-bold d-block mb-2">⚠️ Ligar para cobrança</small>
                    <a href="perfil_cliente.php?id=<?php echo $c['id']; ?>" class="btn btn-outline-danger btn-sm w-100 fw-bold">Abrir Ficha</a>
                </div>
            <?php } ?>
        </div>

        <div class="kanban-col shadow-sm">
            <h6 class="fw-bold text-secondary mb-3 text-uppercase">⚫ Arquivado (<?php echo count($funil['Arquivado']); ?>)</h6>
            <?php foreach ($funil['Arquivado'] as $c) { ?>
                <div class="kanban-card border-arquivado">
                    <h6 class="fw-bold mb-1 opacity-50"><?php echo htmlspecialchars($c['nome']); ?></h6>
                    <a href="perfil_cliente.php?id=<?php echo $c['id']; ?>" class="btn btn-outline-secondary btn-sm w-100">Abrir Ficha</a>
                </div>
            <?php } ?>
        </div>

    </div>
</div>

</body>
</html>