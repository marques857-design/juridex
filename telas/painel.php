<?php
// Arquivo: telas/painel.php
// Função: Dashboard Executivo - Cabeçalho com Foto e Botões de Ação Rápida no Topo

session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

// FORÇA O FUSO HORÁRIO DO BRASIL PARA SAUDAÇÃO CORRETA
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }
$idAdvogado = $_SESSION['id_usuario_logado'];

// Puxa as configurações fixas do Usuário (Nome e Foto Salvos no Perfil)
$arqConfig = '../dados/Usuario_Config_' . $idAdvogado . '.json';
$configUser = file_exists($arqConfig) ? json_decode(file_get_contents($arqConfig), true) : [];

$nomeUsuario = $configUser['nome'] ?? $_SESSION['nome_usuario'] ?? 'Doutor(a)';
$fotoAtual = $configUser['foto'] ?? $_SESSION['foto_perfil'] ?? '../assets/logo.png';

// Saudação Baseada na Hora Exata do Brasil
$hora = (int) date('H');
if ($hora >= 5 && $hora < 12) { $saudacao = 'Bom dia'; } 
elseif ($hora >= 12 && $hora < 18) { $saudacao = 'Boa tarde'; } 
else { $saudacao = 'Boa noite'; }

// Meses em Português para a Data Premium
$meses = ['01'=>'Jan','02'=>'Fev','03'=>'Mar','04'=>'Abr','05'=>'Mai','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Set','10'=>'Out','11'=>'Nov','12'=>'Dez'];
$dataHoje = date('d') . ' de ' . $meses[date('m')] . '. de ' . date('Y');

// =================================================================================
// 1. COLETA DE DADOS OPERACIONAIS REAIS
// =================================================================================
$totalClientes = 0;
$arqClientes = '../dados/Clientes_' . $idAdvogado . '.json';
if (file_exists($arqClientes)) {
    $listaC = json_decode(file_get_contents($arqClientes), true) ?? [];
    $totalClientes = count($listaC);
}

$totalProcessosAtivos = 0;
$funilKanban = ['fase_1' => 0, 'fase_2' => 0, 'fase_3' => 0, 'fase_4' => 0];

$arqProcessos = '../dados/Processos_' . $idAdvogado . '.json';
if (file_exists($arqProcessos)) {
    $listaP = json_decode(file_get_contents($arqProcessos), true) ?? [];
    foreach ($listaP as $p) {
        $status = mb_strtolower(trim($p['status'] ?? 'ativo'), 'UTF-8');
        if (!in_array($status, ['encerrado', 'arquivado', 'excluído', 'excluido'])) {
            $totalProcessosAtivos++;
            $fase = $p['fase_kanban'] ?? 'fase_1';
            if(isset($funilKanban[$fase])) { $funilKanban[$fase]++; }
        }
    }
}

$prazosUrgentes = [];
$totalPrazos7Dias = 0;
$hoje = date('Y-m-d');
$daqui7Dias = date('Y-m-d', strtotime('+7 days'));

foreach (glob('../dados/Agenda_*.json') as $arq) {
    $listaAg = json_decode(file_get_contents($arq), true) ?? [];
    foreach ($listaAg as $ag) {
        $statusAg = mb_strtolower(trim($ag['status'] ?? ''), 'UTF-8');
        if (in_array($statusAg, ['concluído', 'concluido', 'finalizado', 'realizado'])) { continue; }
        if (isset($ag['data_evento'])) {
            $dataEv = $ag['data_evento'];
            if ($dataEv >= $hoje && $dataEv <= $daqui7Dias) { $totalPrazos7Dias++; }
            if ($dataEv <= $hoje) { $prazosUrgentes[] = $ag; }
        }
    }
}
usort($prazosUrgentes, function($a, $b) { return strtotime($a['data_evento']) - strtotime($b['data_evento']); });
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JURIDEX - Painel Executivo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { 
            --bg-body: #f3f4f6; --text-dark: #0f172a; --text-muted: #64748b;
            --primary: #2563eb; --primary-light: #eff6ff; --card-bg: #ffffff;
            --border-light: #e2e8f0; --danger: #ef4444; --danger-light: #fef2f2; --success: #10b981;
            --radius-lg: 20px; --radius-md: 14px;
            --shadow-soft: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
        }
        
        body { font-family: 'Segoe UI', Inter, Tahoma, sans-serif; background-color: var(--bg-body); margin: 0; padding: 0; color: var(--text-dark); }
        .main-content { margin-left: 280px; min-height: 100vh; display: flex; flex-direction: column; transition: margin-left 0.3s ease; }
        
        /* CABEÇALHO CORRIGIDO: Foto na Esquerda com Texto, Botões na Direita */
        .dashboard-header { padding: 40px 40px 30px 40px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;}
        .greeting-title { font-size: 2.2rem; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 5px; color: var(--text-dark); }
        .greeting-date { color: var(--text-muted); font-weight: 500; font-size: 1rem; }
        
        /* BOTÕES DE AÇÃO RÁPIDA */
        .quick-actions-pills { display: flex; gap: 10px; flex-wrap: wrap; }
        .pill-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 50px; font-weight: 600; font-size: 0.85rem; text-decoration: none; transition: 0.2s; border: 1px solid var(--border-light); background: var(--card-bg); color: var(--text-dark); box-shadow: var(--shadow-soft);}
        .pill-btn:hover { background: var(--primary-light); border-color: #bfdbfe; color: var(--primary); transform: translateY(-2px); box-shadow: var(--shadow-hover); }
        
        /* FOTO DE PERFIL */
        .header-photo-link { display: block; transition: transform 0.2s; text-decoration: none; flex-shrink: 0;}
        .header-photo-link:hover { transform: scale(1.05); }
        .header-photo { width: 65px; height: 65px; border-radius: 16px; object-fit: cover; border: 3px solid white; box-shadow: 0 8px 20px rgba(0,0,0,0.08); background-color: #fff; }

        /* Resto do layout expandido... */
        .kpi-link { text-decoration: none; display: block; }
        .kpi-card { background: var(--card-bg); border-radius: var(--radius-lg); padding: 25px; border: 1px solid var(--border-light); box-shadow: var(--shadow-soft); transition: 0.3s; position: relative; overflow: hidden; }
        .kpi-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-hover); border-color: #cbd5e1; }
        .kpi-icon-wrapper { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 15px; }
        .icon-blue { background: #eff6ff; color: #3b82f6; }
        .icon-purple { background: #faf5ff; color: #8b5cf6; }
        .icon-orange { background: #fffbeb; color: #f59e0b; }
        .icon-green { background: #f0fdf4; color: #10b981; }
        .kpi-value { font-size: 2rem; font-weight: 800; color: var(--text-dark); margin-bottom: 2px; line-height: 1; }
        .kpi-label { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        
        .dashboard-panel { background: var(--card-bg); border-radius: var(--radius-lg); padding: 30px; box-shadow: var(--shadow-soft); border: 1px solid var(--border-light); height: 100%; display: flex; flex-direction: column; }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .panel-title { font-size: 1.1rem; font-weight: 800; color: var(--text-dark); margin: 0; }
        
        .task-list { flex-grow: 1; overflow-y: auto; padding-right: 5px; max-height: 320px; }
        .task-item { display: flex; align-items: flex-start; gap: 15px; padding: 15px; border-radius: var(--radius-md); border: 1px solid var(--border-light); margin-bottom: 10px; transition: 0.2s; background: #fdfdfd; }
        .task-item:hover { border-color: #cbd5e1; background: white; box-shadow: var(--shadow-soft); }
        .task-item.urgent { border-left: 4px solid var(--danger); background: var(--danger-light); border-color: #fecaca; }
        .task-icon { width: 40px; height: 40px; border-radius: 10px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .task-icon.urgent-icon { background: #fee2e2; color: var(--danger); }
        
        .task-content { flex-grow: 1; }
        .task-title { font-weight: 700; color: var(--text-dark); font-size: 0.95rem; margin-bottom: 3px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .task-meta { font-size: 0.8rem; color: var(--text-muted); font-weight: 500; }
        
        .task-action { flex-shrink: 0; }
        .btn-task { background: white; border: 1px solid var(--border-light); color: var(--text-dark); font-size: 0.75rem; font-weight: 700; padding: 6px 12px; border-radius: 8px; text-decoration: none; transition: 0.2s; }
        .btn-task:hover { background: var(--bg-body); color: var(--primary); border-color: #cbd5e1; }
        
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        @media (max-width: 991px) { 
            .main-content { margin-left: 0; width: 100%; } 
            .dashboard-header { padding: 20px; flex-direction: column; align-items: flex-start; gap: 15px;}
            .greeting-title { font-size: 1.6rem; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    
    <div class="dashboard-header">
        
        <div class="d-flex align-items-center gap-4">
            <div>
                <h1 class="greeting-title"><?php echo $saudacao; ?>, <?php echo explode(' ', trim($nomeUsuario))[0]; ?>.</h1>
                <div class="greeting-date">Hoje é <?php echo $dataHoje; ?> • Visão Geral do Escritório</div>
            </div>
            
            <a href="meu_perfil.php" class="header-photo-link" title="Editar Meu Perfil">
                <img src="<?php echo htmlspecialchars($fotoAtual); ?>" alt="Foto Perfil" class="header-photo" onerror="this.src='../assets/logo.png';">
            </a>
        </div>
        
        <div class="d-flex align-items-center gap-3 flex-wrap justify-content-end">
            <div class="quick-actions-pills">
                <a href="cadastro_cliente.php" class="pill-btn">👤 Novo Cliente</a>
                <a href="cadastro_processo.php" class="pill-btn" style="background: var(--text-dark); color: white; border-color: var(--text-dark);">⚖️ Novo Processo</a>
                <a href="gerador_peticao_ia.php" class="pill-btn" style="border-color: #bfdbfe; color: #2563eb; background: #eff6ff;">✨ Petição IA</a>
            </div>
        </div>

    </div>

    <div class="container-fluid px-4 pb-5">
        
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-xl-3">
                <a href="lista_clientes.php" class="kpi-link">
                    <div class="kpi-card">
                        <div class="kpi-icon-wrapper icon-blue">👥</div>
                        <div class="kpi-value"><?php echo $totalClientes; ?></div>
                        <div class="kpi-label">Clientes na Base</div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-xl-3">
                <a href="kanban_processos.php" class="kpi-link">
                    <div class="kpi-card">
                        <div class="kpi-icon-wrapper icon-purple">⚖️</div>
                        <div class="kpi-value"><?php echo $totalProcessosAtivos; ?></div>
                        <div class="kpi-label">Processos Ativos</div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-xl-3">
                <a href="agenda.php" class="kpi-link">
                    <div class="kpi-card">
                        <div class="kpi-icon-wrapper icon-orange">⏳</div>
                        <div class="kpi-value"><?php echo $totalPrazos7Dias; ?></div>
                        <div class="kpi-label">Prazos (Próx. 7 Dias)</div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-xl-3">
                <a href="central_ia.php" class="kpi-link">
                    <div class="kpi-card">
                        <div class="kpi-icon-wrapper icon-green">🧠</div>
                        <div class="kpi-value text-success" style="font-size: 1.5rem; padding-top: 5px; padding-bottom: 3px;">ONLINE</div>
                        <div class="kpi-label">JURIDEX Intelligence</div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="dashboard-panel">
                    <div class="panel-header">
                        <h3 class="panel-title">Distribuição de Fluxo (Kanban)</h3>
                    </div>
                    <?php if ($totalProcessosAtivos == 0): ?>
                        <div class="text-center text-muted m-auto" style="padding: 40px 0;">
                            <div style="font-size: 3rem; opacity: 0.2; margin-bottom: 10px;">📊</div>
                            <p class="mb-0 fw-bold">Sem dados de processos.</p>
                            <small>Cadastre um processo para gerar o gráfico.</small>
                        </div>
                    <?php else: ?>
                        <div style="position: relative; height: 320px; width: 100%;">
                            <canvas id="graficoFunil"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="dashboard-panel">
                    <div class="panel-header">
                        <h3 class="panel-title">Agenda Crítica</h3>
                        <?php if(count($prazosUrgentes) > 0): ?>
                            <span class="badge bg-danger rounded-pill px-2 py-1"><?php echo count($prazosUrgentes); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="task-list">
                        <?php if (empty($prazosUrgentes)): ?>
                            <div class="text-center text-muted" style="padding: 40px 0; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1;">
                                <div style="font-size: 3rem; opacity: 0.3; margin-bottom: 10px;">✅</div>
                                <h6 class="fw-bold text-success mb-1">Tudo Limpo!</h6>
                                <p class="small mb-0">Nenhum prazo pendente para hoje.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($prazosUrgentes as $pz): $isFatal = ($pz['tipo_evento'] == 'Prazo Fatal'); ?>
                                <div class="task-item <?php echo $isFatal ? 'urgent' : ''; ?>">
                                    <div class="task-icon <?php echo $isFatal ? 'urgent-icon' : ''; ?>">
                                        <?php echo $isFatal ? '🚨' : '📅'; ?>
                                    </div>
                                    <div class="task-content">
                                        <div class="task-title" title="<?php echo htmlspecialchars($pz['titulo']); ?>">
                                            <?php echo htmlspecialchars($pz['titulo']); ?>
                                        </div>
                                        <div class="task-meta">
                                            <span class="text-<?php echo $isFatal ? 'danger' : 'primary'; ?> fw-bold">• <?php echo htmlspecialchars($pz['tipo_evento']); ?></span> 
                                            | <?php echo date('d/m', strtotime($pz['data_evento'])); ?>
                                        </div>
                                    </div>
                                    <?php if (!empty($pz['processo_id'])): ?>
                                        <div class="task-action">
                                            <a href="perfil_processo.php?id=<?php echo htmlspecialchars($pz['processo_id']); ?>&aba=prazos" class="btn-task">Abrir</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <a href="agenda.php" class="btn text-center mt-3 fw-bold" style="color: var(--primary); font-size: 0.9rem; background: var(--primary-light); border-radius: var(--radius-md); padding: 10px;">
                        Abrir Calendário Completo ➔
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($totalProcessosAtivos > 0): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('graficoFunil').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['📋 Em Análise', '📝 P. Inicial', '⚖️ Audiências', '⏳ Recursos'],
            datasets: [{
                label: 'Processos',
                data: [
                    <?php echo $funilKanban['fase_1']; ?>, 
                    <?php echo $funilKanban['fase_2']; ?>, 
                    <?php echo $funilKanban['fase_3']; ?>, 
                    <?php echo $funilKanban['fase_4']; ?>
                ],
                backgroundColor: ['#94a3b8', '#3b82f6', '#f59e0b', '#ef4444'],
                borderRadius: 8, borderSkipped: false, barThickness: 28
            }]
        },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0f172a', padding: 14, titleFont: { family: 'Inter', size: 13 }, bodyFont: { family: 'Inter', size: 15, weight: 'bold' }, cornerRadius: 8, displayColors: false } },
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1, font: { family: 'Inter', weight: '600', color: '#64748b' } }, grid: { display: false, drawBorder: false } },
                y: { ticks: { font: { family: 'Inter', size: 13, weight: '600' }, color: '#0f172a' }, grid: { color: '#f1f5f9', drawBorder: false } }
            },
            animation: { duration: 1500, easing: 'easeOutQuart' }
        }
    });
});
</script>
<?php endif; ?>

</body>
</html>