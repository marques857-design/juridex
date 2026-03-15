<?php
// Arquivo: telas/lista_processos.php
// Função: Lista de Processos com CPF/CNPJ e Botão de Exclusão Definitiva

session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }

$idAdvogado = $_SESSION['id_usuario_logado'];
$nomeAdvogado = $_SESSION['nome_usuario'] ?? 'Doutor(a)';
$cargoUsuario = $_SESSION['cargo'] ?? 'Advogado';

$arquivoProcessos = '../dados/Processos_' . $idAdvogado . '.json';

// =========================================================================
// LÓGICA DE EXCLUSÃO DEFINITIVA DE PROCESSO
// =========================================================================
if (isset($_GET['excluir'])) {
    $idExcluir = $_GET['excluir'];
    if (file_exists($arquivoProcessos)) {
        $processosAtuais = json_decode(file_get_contents($arquivoProcessos), true) ?? [];
        $novaListaProcessos = [];
        
        foreach ($processosAtuais as $p) {
            $idProcessoNaBase = $p['id'] ?? ($p['id_processo'] ?? '');
            if ($idProcessoNaBase !== $idExcluir) {
                $novaListaProcessos[] = $p;
            }
        }
        
        file_put_contents($arquivoProcessos, json_encode($novaListaProcessos, JSON_PRETTY_PRINT));
        header("Location: lista_processos.php?msg=excluido");
        exit;
    }
}

$processos = file_exists($arquivoProcessos) ? json_decode(file_get_contents($arquivoProcessos), true) ?? [] : [];

// Mapeia Clientes para puxar o nome e o CPF/CNPJ na tabela
$clientesMap = [];
if (file_exists('../dados/Clientes_' . $idAdvogado . '.json')) {
    $listaC = json_decode(file_get_contents('../dados/Clientes_' . $idAdvogado . '.json'), true) ?? [];
    foreach($listaC as $c) { 
        if(isset($c['id'])) { 
            $clientesMap[$c['id']] = [
                'nome' => $c['nome'] ?? '',
                'cpf_cnpj' => $c['cpf_cnpj'] ?? 'Não informado'
            ]; 
        } 
    }
}

usort($processos, function($a, $b) {
    return strtotime($b['data_registro'] ?? '2000-01-01') - strtotime($a['data_registro'] ?? '2000-01-01');
});
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - Processos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        
        .main-content { margin-left: 280px; min-height: 100vh; display: flex; flex-direction: column; }
        .topbar { background: #fff; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; }
        
        /* Banner Central de Processos (Do seu Print) */
        .page-header { background: #1c3b6c; color: white; border-radius: 12px; padding: 25px 30px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; box-shadow: 0 5px 15px rgba(28, 59, 108, 0.2); }
        
        /* Tabela Enterprise */
        .table-custom { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #eee; }
        .table-custom thead { background-color: #212529; color: white; }
        .table-custom th { font-weight: 600; padding: 15px; border: none; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;}
        .table-custom td { padding: 18px 15px; vertical-align: middle; border-bottom: 1px solid #f0f0f0; }
        .table-custom tr:hover { background-color: #f8f9fa; }
        
        .num-processo { color: #0084ff; font-weight: bold; font-size: 1.05rem; text-decoration: none; }
        .num-processo:hover { text-decoration: underline; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h4 class="mb-0 fw-bold text-dark">Gestão de Processos</h4>
        <div class="d-flex align-items-center gap-3">
            <a href="kanban_processos.php" class="btn btn-light border fw-bold shadow-sm">🗂️ Ver Kanban</a>
        </div>
    </div>

    <div class="container-fluid px-4 mb-5">
        
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'excluido'): ?>
            <div class="alert alert-success fw-bold">✅ Processo excluído com sucesso da base de dados!</div>
        <?php endif; ?>

        <div class="page-header">
            <div>
                <h2 class="fw-bold mb-1">⚖️ Central de Processos</h2>
                <p class="mb-0 text-white-50">Gestão completa de ações, andamentos e prazos.</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-success fw-bold shadow-sm d-flex align-items-center gap-2">
                    📊 Baixar Relatório (Excel)
                </button>
                <a href="cadastro_processo.php" class="btn btn-light text-primary fw-bold shadow-sm d-flex align-items-center gap-2">
                    ➕ Novo Processo
                </a>
            </div>
        </div>

        <div class="mb-4">
            <input type="text" id="buscaProc" class="form-control form-control-lg border-primary shadow-sm" placeholder="🔎 Buscar por Número do Processo, Cliente, CPF ou Parte Contrária..." style="font-size: 1rem;">
        </div>

        <div class="table-custom table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Número CNJ</th>
                        <th>Nosso Cliente</th>
                        <th>CPF / CNPJ</th>
                        <th>Parte Contrária</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($processos)) { ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">Nenhum processo cadastrado no escritório.</td></tr>
                    <?php } else { 
                        foreach($processos as $p) { 
                            $idRealProc = $p['id'] ?? ($p['id_processo'] ?? '');
                            $status = $p['status'] ?? 'Ativo';
                            $corStatus = 'bg-success';
                            if($status == 'Suspenso') $corStatus = 'bg-warning text-dark';
                            if($status == 'Encerrado') $corStatus = 'bg-dark';
                            
                            $nomeCli = 'Não vinculado';
                            $docCli = '-';
                            $idCli = $p['cliente_id'] ?? '';
                            if(!empty($idCli) && isset($clientesMap[$idCli])) { 
                                $nomeCli = $clientesMap[$idCli]['nome']; 
                                $docCli = $clientesMap[$idCli]['cpf_cnpj'];
                            } elseif(!empty($p['nome_cliente'])) { 
                                $nomeCli = $p['nome_cliente']; 
                            }
                    ?>
                        <tr class="linha-proc">
                            <td>
                                <a href="perfil_processo.php?id=<?php echo htmlspecialchars($idRealProc); ?>" class="num-processo d-block mb-1">
                                    <?php echo htmlspecialchars($p['numero_processo'] ?? 'Sem Número'); ?>
                                </a>
                                <small class="text-muted"><?php echo htmlspecialchars($p['tipo_acao'] ?? 'Ação'); ?> • <?php echo htmlspecialchars($p['fase_processual'] ?? 'Inicial'); ?></small>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">👤 <?php echo htmlspecialchars($nomeCli); ?></div>
                            </td>
                            <td>
                                <span class="text-secondary fw-bold"><?php echo htmlspecialchars($docCli); ?></span>
                            </td>
                            <td>
                                <span class="text-secondary"><?php echo htmlspecialchars($p['parte_contraria'] ?? '-'); ?></span>
                            </td>
                            <td><span class="badge <?php echo $corStatus; ?> fw-bold px-2 py-1"><?php echo htmlspecialchars($status); ?></span></td>
                            <td class="text-end">
                                <a href="perfil_processo.php?id=<?php echo htmlspecialchars($idRealProc); ?>" class="btn btn-sm btn-info fw-bold text-white shadow-sm">👁️ Ficha 360</a>
                                <a href="cadastro_processo.php?id=<?php echo htmlspecialchars($idRealProc); ?>" class="btn btn-sm btn-warning fw-bold text-dark shadow-sm ms-1">✏️ Editar</a>
                                <a href="lista_processos.php?excluir=<?php echo htmlspecialchars($idRealProc); ?>" class="btn btn-sm btn-outline-danger fw-bold shadow-sm ms-1" onclick="return confirm('CUIDADO: Tem a certeza que deseja APAGAR este processo definitivamente?');">❌</a>
                            </td>
                        </tr>
                    <?php } } ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
document.getElementById('buscaProc').addEventListener('keyup', function() {
    let termo = this.value.toLowerCase();
    document.querySelectorAll('.linha-proc').forEach(function(linha) {
        linha.style.display = linha.textContent.toLowerCase().includes(termo) ? '' : 'none';
    });
});
</script>
</body>
</html>