<?php
// Arquivo: telas/painel_master.php
session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['id_usuario_logado']) || $_SESSION['perfil'] != 'master') { header("Location: login.php"); exit; }
$nomeCEO = $_SESSION['nome_usuario'] ?? 'CEO';

$arquivoUsuarios = '../dados/Usuarios_SaaS.json';

if (isset($_GET['acao']) && isset($_GET['id'])) {
    $idEsc = $_GET['id'];
    $assinantes = json_decode(file_get_contents($arquivoUsuarios), true) ?? [];
    
    if ($_GET['acao'] == 'bloquear' || $_GET['acao'] == 'ativar') {
        $novaAcao = $_GET['acao'] == 'bloquear' ? 'Inadimplente/Bloqueado' : 'Ativo';
        foreach ($assinantes as $key => $ass) {
            if (($ass['id_escritorio'] ?? $ass['id'] ?? '') == $idEsc) {
                $assinantes[$key]['status'] = $novaAcao; break;
            }
        }
        file_put_contents($arquivoUsuarios, json_encode($assinantes, JSON_PRETTY_PRINT));
        header("Location: painel_master.php"); exit;
    }
    
    if ($_GET['acao'] == 'excluir') {
        $novaLista = [];
        foreach ($assinantes as $ass) {
            if (($ass['id_escritorio'] ?? $ass['id'] ?? '') != $idEsc) { $novaLista[] = $ass; }
        }
        file_put_contents($arquivoUsuarios, json_encode($novaLista, JSON_PRETTY_PRINT));
        
        $arquivosDoCliente = glob("../dados/*" . $idEsc . "*.json");
        foreach ($arquivosDoCliente as $arquivoParaApagar) {
            if(file_exists($arquivoParaApagar)) { unlink($arquivoParaApagar); }
        }
        header("Location: painel_master.php?msg=excluido"); exit;
    }

    if ($_GET['acao'] == 'logar_como') {
        foreach ($assinantes as $ass) {
            if (($ass['id_escritorio'] ?? $ass['id'] ?? '') == $idEsc) {
                $_SESSION['id_usuario_logado'] = $idEsc;
                $_SESSION['nome_usuario'] = $ass['responsavel'] . ' (Acesso Admin)';
                $_SESSION['perfil'] = 'advogado';
                $_SESSION['plano'] = $ass['plano'];
                header("Location: painel.php"); exit;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'alterar_plano') {
    $assinantes = json_decode(file_get_contents($arquivoUsuarios), true) ?? [];
    foreach ($assinantes as $key => $ass) {
        if (($ass['id_escritorio'] ?? $ass['id'] ?? '') == $_POST['id_escritorio']) {
            $assinantes[$key]['plano'] = $_POST['novo_plano']; break;
        }
    }
    file_put_contents($arquivoUsuarios, json_encode($assinantes, JSON_PRETTY_PRINT));
    header("Location: painel_master.php?msg=plano_alterado"); exit;
}

$assinantes = file_exists($arquivoUsuarios) ? json_decode(file_get_contents($arquivoUsuarios), true) ?? [] : [];
$mrr = 0; $ativos = 0;
foreach($assinantes as $ass) {
    if(isset($ass['status']) && $ass['status'] == 'Ativo') {
        $ativos++;
        if(strpos($ass['plano'] ?? '', '300') !== false) $mrr += 300;
        elseif(strpos($ass['plano'] ?? '', '100') !== false) $mrr += 100;
        elseif(strpos($ass['plano'] ?? '', '50') !== false) $mrr += 50;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - Painel Administrativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { background-color: #0f172a; color: #f8fafc; font-family: 'Segoe UI', sans-serif; }
        .navbar-master { background-color: #1e293b; border-bottom: 2px solid #e94560; }
        .card-dark { background-color: #1e293b; border: 1px solid #334155; border-radius: 10px; }
        .text-accent { color: #e94560; }
        .text-muted { color: #94a3b8 !important; } 
        .table-dark-custom { color: #f8fafc; margin-bottom: 0; }
        .table-dark-custom th { border-bottom: 2px solid #e94560; color: #cbd5e1; font-weight: bold; background-color: transparent; }
        .table-dark-custom td { border-bottom: 1px solid #334155; color: #e2e8f0; background-color: transparent; vertical-align: middle; }
        .table-dark-custom tbody tr:hover td { background-color: rgba(255, 255, 255, 0.05); }
        .status-online { display: inline-block; width: 10px; height: 10px; background-color: #10b981; border-radius: 50%; box-shadow: 0 0 8px #10b981; }
        .status-offline { display: inline-block; width: 10px; height: 10px; background-color: #64748b; border-radius: 50%; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-master py-3 mb-4 shadow">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold fs-4 text-white" href="#">🚀 JURIDEX <span class="text-accent">| ADMINISTRAÇÃO</span></a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-light fw-bold">Administrador: <?php echo htmlspecialchars($nomeCEO); ?></span>
            <a href="painel_master_financeiro.php" class="btn btn-success btn-sm fw-bold shadow-sm">💰 Painel Financeiro</a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm fw-bold">Encerrar Sessão</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'excluido') echo "<div class='alert alert-danger fw-bold'>🗑️ O escritório e todos os seus dados foram apagados do servidor.</div>"; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'plano_alterado') echo "<div class='alert alert-success fw-bold'>✅ O Plano do cliente foi alterado com sucesso!</div>"; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-white">Dashboard Administrativo</h3>
        <a href="cadastro_escritorio.php" class="btn btn-danger fw-bold shadow btn-lg">➕ Vender Nova Licença</a>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4"><div class="card card-dark p-4 h-100 text-center shadow"><h6 class="text-muted fw-bold text-uppercase mb-2">Escritórios Ativos</h6><h1 class="fw-bold text-white mb-0"><?php echo $ativos; ?></h1></div></div>
        <div class="col-md-4"><div class="card card-dark p-4 h-100 text-center shadow" style="border-bottom: 3px solid #e94560;"><h6 class="text-accent fw-bold text-uppercase mb-2">MRR (Faturamento)</h6><h1 class="fw-bold text-white mb-0">R$ <?php echo number_format($mrr, 2, ',', '.'); ?></h1></div></div>
        <div class="col-md-4"><div class="card card-dark p-4 h-100 text-center shadow"><h6 class="text-info fw-bold text-uppercase mb-2">Status do Sistema</h6><h2 class="fw-bold text-info mb-0">🟢 Operacional</h2></div></div>
    </div>

    <div class="card card-dark shadow mb-5">
        <div class="card-header border-bottom-0 pt-4 pb-2 px-4">
            <h5 class="fw-bold text-white mb-0">🏢 Gestão de Contas e Licenças</h5>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table class="table table-dark-custom align-middle">
                    <thead>
                        <tr>
                            <th>Status Net</th>
                            <th>Escritório / ID</th>
                            <th>Responsável / Login</th>
                            <th>Plano Atual</th>
                            <th>Status Licença</th>
                            <th class="text-end">Gerenciar Conta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($assinantes)) { echo "<tr><td colspan='6' class='text-center text-muted p-4'>Nenhuma licença vendida ainda.</td></tr>"; } ?>
                        <?php foreach($assinantes as $ass) { 
                            $idEsc = $ass['id_escritorio'] ?? $ass['id'] ?? 'Sem ID';
                            $loginEsc = $ass['login'] ?? $ass['email'] ?? 'Sem Login';
                            $statusEsc = $ass['status'] ?? 'Ativo';
                            $isAtivo = ($statusEsc == 'Ativo');
                            $corStatus = $isAtivo ? 'bg-success text-white' : 'bg-danger text-white';
                            
                            $ultimoAcesso = $ass['ultimo_acesso'] ?? null;
                            $isOnline = false;
                            $textoOnline = "Offline";
                            if ($ultimoAcesso) {
                                $minutos = round(abs(strtotime(date('Y-m-d H:i:s')) - strtotime($ultimoAcesso)) / 60);
                                if ($minutos < 15) { $isOnline = true; $textoOnline = "Online Agora"; }
                                else { $textoOnline = "Visto há " . $minutos . " min"; }
                            }
                        ?>
                            <tr>
                                <td class="text-center" title="<?php echo $textoOnline; ?>">
                                    <span class="<?php echo $isOnline ? 'status-online' : 'status-offline'; ?>"></span><br>
                                    <small class="text-muted" style="font-size: 0.65rem;"><?php echo $textoOnline; ?></small>
                                </td>
                                <td>
                                    <strong class="text-white fs-6"><?php echo htmlspecialchars($ass['nome_escritorio'] ?? 'Sem Nome'); ?></strong><br>
                                    <small class="text-muted">ID: <?php echo htmlspecialchars($idEsc); ?></small>
                                </td>
                                <td>
                                    <span class="text-white"><?php echo htmlspecialchars($ass['responsavel'] ?? 'Sem Resp.'); ?></span><br>
                                    <small class="text-info fw-bold">Login: <?php echo htmlspecialchars($loginEsc); ?></small>
                                </td>
                                <td>
                                    <span class="text-warning fw-bold d-block"><?php echo htmlspecialchars($ass['plano'] ?? 'Básico'); ?></span>
                                    <button class="btn btn-sm btn-link text-info p-0 text-decoration-none" onclick="abrirModalPlano('<?php echo $idEsc; ?>', '<?php echo htmlspecialchars($ass['plano'] ?? ''); ?>')">✏️ Alterar Plano</button>
                                </td>
                                <td><span class="badge <?php echo $corStatus; ?> px-3 py-2 shadow-sm"><?php echo htmlspecialchars($statusEsc); ?></span></td>
                                <td class="text-end">
                                    <a href="painel_master.php?acao=logar_como&id=<?php echo urlencode($idEsc); ?>" class="btn btn-sm btn-primary fw-bold text-white shadow-sm mb-1">👁️ Acessar Conta</a>
                                    
                                    <?php if($isAtivo) { ?>
                                        <a href="painel_master.php?acao=bloquear&id=<?php echo urlencode($idEsc); ?>" class="btn btn-sm btn-outline-warning fw-bold mb-1">Suspender</a>
                                    <?php } else { ?>
                                        <a href="painel_master.php?acao=ativar&id=<?php echo urlencode($idEsc); ?>" class="btn btn-sm btn-success fw-bold text-dark mb-1">Ativar</a>
                                    <?php } ?>
                                    
                                    <a href="painel_master.php?acao=excluir&id=<?php echo urlencode($idEsc); ?>" class="btn btn-sm btn-danger fw-bold mb-1" onclick="return confirm('⚠️ ATENÇÃO ADMINISTRADOR:\n\nIsto apagará este escritório e TODOS OS PROCESSOS e DADOS dele do servidor para sempre.\n\nTem a certeza absoluta?');">🗑️ Excluir Dados</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPlano" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: #1e293b; border: 1px solid #334155;">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-white fw-bold">Alterar Plano do Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="painel_master.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="alterar_plano">
                    <input type="hidden" name="id_escritorio" id="input_id_escritorio">
                    
                    <label class="form-label text-light fw-bold">Selecione o Novo Plano:</label>
                    <select class="form-select bg-dark text-warning border-warning fw-bold" name="novo_plano" id="select_plano" required>
                        <option value="Básico (R$ 50)">Plano Básico (R$ 50/mês)</option>
                        <option value="Pro (R$ 100)">Plano Pro (R$ 100/mês)</option>
                        <option value="VIP (R$ 300)">Plano VIP (R$ 300/mês)</option>
                    </select>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger fw-bold">💾 Salvar Novo Plano</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalPlano(id, planoAtual) {
    document.getElementById('input_id_escritorio').value = id;
    let select = document.getElementById('select_plano');
    for (let i = 0; i < select.options.length; i++) {
        if (select.options[i].value === planoAtual) { select.selectedIndex = i; break; }
    }
    var myModal = new bootstrap.Modal(document.getElementById('modalPlano'));
    myModal.show();
}
</script>

</body>
</html>