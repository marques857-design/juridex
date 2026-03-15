<?php
// Arquivo: telas/painel.php
// Pasta: telas

session_start();

if (!isset($_SESSION['id_usuario_logado'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JURIDEX - Painel de Controle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="painel.php">JURIDEX</a>
        <div class="d-flex align-items-center">
            <span class="navbar-text me-4 text-white">
                Bem-vindo(a), <strong><?php echo htmlspecialchars($_SESSION['nome_usuario_logado']); ?></strong>!
            </span>
            <a href="../acoes/sair.php" class="btn btn-danger btn-sm">Sair de Segurança</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <h2>O Meu Escritório Virtual</h2>
    <hr>
    
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3 shadow">
                <div class="card-body">
                    <h5 class="card-title">Carteira de Clientes</h5>
                    <p class="card-text">Cadastre e gerencie as informações das pessoas.</p>
                    <a href="cadastro_cliente.php" class="btn btn-light btn-sm fw-bold">+ Novo</a>
                    <a href="lista_clientes.php" class="btn btn-outline-light btn-sm fw-bold">Ver Todos</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-white bg-success mb-3 shadow">
                <div class="card-body">
                    <h5 class="card-title">Processos Judiciais</h5>
                    <p class="card-text">Acompanhe os andamentos e tribunais.</p>
                    <a href="cadastro_processo.php" class="btn btn-light btn-sm fw-bold">+ Novo</a>
                    <a href="lista_processos.php" class="btn btn-outline-light btn-sm fw-bold">Ver Todos</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card text-white bg-danger mb-3 shadow">
                <div class="card-body">
                    <h5 class="card-title">Agenda e Prazos</h5>
                    <p class="card-text">Controle suas audiências e prazos fatais.</p>
                    <a href="cadastro_agenda.php" class="btn btn-light btn-sm fw-bold">+ Agendar</a>
                    <a href="lista_agenda.php" class="btn btn-outline-light btn-sm fw-bold">Ver Calendário</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>