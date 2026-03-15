<?php
// Arquivo: telas/lista_clientes.php
// Função: CRM - Lista de Clientes com Botão de Exclusão Definitiva

session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }

$idAdvogado = $_SESSION['id_usuario_logado'];
$nomeAdvogado = $_SESSION['nome_usuario'] ?? 'Doutor(a)';
$cargoUsuario = $_SESSION['cargo'] ?? 'Advogado';

$arquivoClientes = '../dados/Clientes_' . $idAdvogado . '.json';

// =========================================================================
// LÓGICA DE EXCLUSÃO DEFINITIVA DE CLIENTE
// =========================================================================
if (isset($_GET['excluir'])) {
    $idExcluir = $_GET['excluir'];
    if (file_exists($arquivoClientes)) {
        $clientesAtuais = json_decode(file_get_contents($arquivoClientes), true) ?? [];
        $novaListaClientes = [];
        
        foreach ($clientesAtuais as $c) {
            if ($c['id'] !== $idExcluir) {
                $novaListaClientes[] = $c;
            }
        }
        
        file_put_contents($arquivoClientes, json_encode($novaListaClientes, JSON_PRETTY_PRINT));
        header("Location: lista_clientes.php?msg=excluido");
        exit;
    }
}

$clientes = file_exists($arquivoClientes) ? json_decode(file_get_contents($arquivoClientes), true) ?? [] : [];

// Ordenar por ordem alfabética
usort($clientes, function($a, $b) {
    return strcmp(strtolower($a['nome'] ?? ''), strtolower($b['nome'] ?? ''));
});
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        
        .main-content { margin-left: 280px; min-height: 100vh; display: flex; flex-direction: column; }
        .topbar { background: #fff; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; }
        
        /* Banner Clientes */
        .page-header { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; border-radius: 12px; padding: 25px 30px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; box-shadow: 0 5px 15px rgba(17, 153, 142, 0.2); }
        
        /* Tabela Enterprise */
        .table-custom { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #eee; }
        .table-custom thead { background-color: #f8f9fa; color: #333; border-bottom: 2px solid #dee2e6;}
        .table-custom th { font-weight: 700; padding: 15px; border: none; font-size: 0.85rem; text-transform: uppercase; color: #6c757d;}
        .table-custom td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f0f0f0; }
        .table-custom tr:hover { background-color: #f8f9fa; }
        
        .nome-cliente { font-weight: 800; color: #1c1f3b; font-size: 1.1rem; text-decoration: none; display: block; margin-bottom: 2px;}
        .nome-cliente:hover { color: #0084ff; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h4 class="mb-0 fw-bold text-dark">Gestão de Clientes</h4>
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold text-dark">Olá, <?php echo htmlspecialchars($nomeAdvogado); ?> <small class="text-muted">(<?php echo htmlspecialchars($cargoUsuario); ?>)</small></span>
        </div>
    </div>

    <div class="container-fluid px-4 mb-5">
        
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'excluido'): ?>
            <div class="alert alert-success fw-bold">✅ Cliente excluído com sucesso da base de dados!</div>
        <?php endif; ?>

        <div class="page-header">
            <div>
                <h2 class="fw-bold mb-1">👥 Carteira de Clientes</h2>
                <p class="mb-0 opacity-75">CRM completo: Fichas, processos vinculados e faturamento.</p>
            </div>
            <div>
                <a href="cadastro_cliente.php" class="btn btn-dark btn-lg fw-bold shadow-sm d-flex align-items-center gap-2 border-0">
                    ➕ Cadastrar Novo Cliente
                </a>
            </div>
        </div>

        <div class="mb-4">
            <input type="text" id="buscaCli" class="form-control form-control-lg border-success shadow-sm" placeholder="🔎 Digite o nome, CPF ou e-mail do cliente..." style="font-size: 1rem;">
        </div>

        <div class="table-custom table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Nome Completo</th>
                        <th>Contato</th>
                        <th>CPF / CNPJ</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($clientes)) { ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Nenhum cliente cadastrado no momento.</td></tr>
                    <?php } else { 
                        foreach($clientes as $c) { 
                            $status = $c['status_cliente'] ?? 'Ativo';
                            $corStatus = 'bg-secondary';
                            if($status == 'Ativo') $corStatus = 'bg-success';
                            if($status == 'Em negociação') $corStatus = 'bg-warning text-dark';
                            if($status == 'Inadimplente') $corStatus = 'bg-danger';
                    ?>
                        <tr class="linha-cli">
                            <td>
                                <a href="perfil_cliente.php?id=<?php echo htmlspecialchars($c['id']); ?>" class="nome-cliente">
                                    <?php echo htmlspecialchars($c['nome'] ?? 'Sem Nome'); ?>
                                    <?php if(!empty($c['score_cliente']) && $c['score_cliente'] == 'VIP') echo '<span class="text-warning fs-6" title="Cliente VIP">⭐</span>'; ?>
                                </a>
                                <small class="text-muted"><?php echo htmlspecialchars($c['cidade'] ?? ''); ?> <?php echo !empty($c['estado']) ? '- ' . htmlspecialchars($c['estado']) : ''; ?></small>
                            </td>
                            <td>
                                <div class="text-dark"><i class="text-success fw-bold">📱</i> <?php echo htmlspecialchars($c['telefone'] ?? 'N/D'); ?></div>
                                <small class="text-muted">📧 <?php echo htmlspecialchars($c['email'] ?? 'N/D'); ?></small>
                            </td>
                            <td>
                                <span class="fw-bold text-secondary"><?php echo htmlspecialchars($c['cpf_cnpj'] ?? '-'); ?></span>
                            </td>
                            <td><span class="badge <?php echo $corStatus; ?> fw-bold px-2 py-1"><?php echo htmlspecialchars($status); ?></span></td>
                            <td class="text-end">
                                <a href="perfil_cliente.php?id=<?php echo htmlspecialchars($c['id']); ?>" class="btn btn-sm btn-dark fw-bold shadow-sm px-3">Abrir Pasta 📂</a>
                                <a href="lista_clientes.php?excluir=<?php echo htmlspecialchars($c['id']); ?>" class="btn btn-sm btn-outline-danger fw-bold shadow-sm ms-1" onclick="return confirm('ATENÇÃO: Tem a certeza que deseja EXCLUIR DEFINITIVAMENTE este cliente?');">❌</a>
                            </td>
                        </tr>
                    <?php } } ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
document.getElementById('buscaCli').addEventListener('keyup', function() {
    let termo = this.value.toLowerCase();
    document.querySelectorAll('.linha-cli').forEach(function(linha) {
        linha.style.display = linha.textContent.toLowerCase().includes(termo) ? '' : 'none';
    });
});
</script>
</body>
</html>