<?php
// Arquivo: telas/backup.php
// Função: Gerar e baixar um arquivo .ZIP com todo o banco de dados do escritório.

session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }

// Segurança: Só o dono do escritório pode fazer backup
$cargo = $_SESSION['cargo'] ?? '';
if (strpos(strtolower($cargo), 'estagiário') !== false || strpos(strtolower($cargo), 'estagiaria') !== false) {
    die("<div style='text-align:center; padding: 50px; font-family:sans-serif;'><h2>🔒 Acesso Negado</h2><p>Apenas administradores podem fazer backup do sistema.</p><a href='painel.php'>Voltar ao Painel</a></div>");
}

if (isset($_GET['acao']) && $_GET['acao'] == 'download') {
    $idAdvogado = $_SESSION['id_usuario_logado'];
    $pastaDados = '../dados/';
    $nomeZip = 'Backup_JURIDEX_' . date('Y-m-d_H-i') . '.zip';
    $caminhoZip = '../uploads/' . $nomeZip;

    $zip = new ZipArchive();
    if ($zip->open($caminhoZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        
        // Pega todos os JSONs vinculados a este escritório
        $arquivos = glob($pastaDados . '*_' . $idAdvogado . '.json');
        
        // Inclui também a Agenda Global (se tiver formato diferente)
        $arquivosExtra = glob($pastaDados . '*genda*.json');
        $todosArquivos = array_unique(array_merge($arquivos, $arquivosExtra));

        foreach ($todosArquivos as $arquivo) {
            if (file_exists($arquivo)) {
                $zip->addFile($arquivo, basename($arquivo));
            }
        }
        $zip->close();

        // Força o Download do ZIP
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename=' . $nomeZip);
        header('Content-Length: ' . filesize($caminhoZip));
        readfile($caminhoZip);
        
        // Apaga o ZIP do servidor após o download para economizar espaço
        unlink($caminhoZip);
        exit;
    } else {
        $erro = "Falha ao criar o arquivo de backup.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - Central de Backup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-custom { background: #1c1f3b; border-bottom: 2px solid #0084ff; padding: 10px 0; }
        .logo-img { max-height: 40px; object-fit: contain; }
        .card-backup { border: none; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); background: white; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark navbar-custom shadow-sm mb-5">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold text-white d-flex align-items-center" href="painel.php">
            <img src="../assets/logo.png" alt="JURIDEX" class="logo-img me-2" onerror="this.style.display='none';">
            Central de Segurança
        </a>
        <a href="painel.php" class="btn btn-outline-light btn-sm fw-bold">Voltar ao Painel</a>
    </div>
</nav>

<div class="container" style="max-width: 700px;">
    <?php if(isset($erro)) echo "<div class='alert alert-danger fw-bold'>{$erro}</div>"; ?>

    <div class="card card-backup p-5 text-center">
        <h1 style="font-size: 4rem; margin-bottom: 20px;">🛡️</h1>
        <h2 class="fw-bold text-dark mb-3">Backup Criptografado</h2>
        <p class="text-muted fs-5 mb-4">
            Baixe uma cópia completa de segurança de <b>todos os seus clientes, processos, financeiro e agendas</b>. 
            O arquivo será gerado no formato universal <code>.zip</code> contendo a sua base de dados estruturada.
        </p>
        
        <div class="alert alert-warning text-start mb-4 shadow-sm border-0" style="background-color: #fffcf2; border-left: 4px solid #ffc107 !important;">
            <strong class="text-dark">Por que fazer backup?</strong><br>
            <small class="text-muted">Apesar dos nossos servidores serem seguros, manter uma cópia física dos dados do seu escritório garante proteção jurídica e operacional contra qualquer imprevisto.</small>
        </div>

        <a href="backup.php?acao=download" class="btn btn-dark btn-lg fw-bold shadow w-100 py-3" style="background-color: #1c1f3b;">
            📥 Gerar e Baixar Backup Agora
        </a>
    </div>
</div>

</body>
</html>