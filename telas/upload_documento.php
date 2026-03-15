<?php
// Arquivo: telas/upload_documento.php
// Pasta: telas

session_start();

if (!isset($_SESSION['id_usuario_logado'])) {
    header("Location: login.php");
    exit;
}

/**
 * Buscamos os processos para a advogada saber em qual pasta virtual guardar o PDF.
 */
function buscarProcessosParaUpload($idDoAdvogado) {
    $lista = [];
    $arquivos = glob('../dados/Processos_*.json');
    if ($arquivos !== false) {
        foreach ($arquivos as $arquivo) {
            $conteudo = file_get_contents($arquivo);
            $processos = json_decode($conteudo, true);
            if (is_array($processos)) {
                foreach ($processos as $processo) {
                    if ($processo['id_advogado_responsavel'] == $idDoAdvogado) {
                        $lista[] = $processo;
                    }
                }
            }
        }
    }
    return $lista;
}

$meusProcessos = buscarProcessosParaUpload($_SESSION['id_usuario_logado']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JURIDEX - Anexar Documento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="painel.php">JURIDEX</a>
        <div class="d-flex">
            <a href="lista_processos.php" class="btn btn-outline-light btn-sm">Voltar aos Processos</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="card shadow border-secondary">
        <div class="card-header bg-secondary text-white">
            <h4>Anexar Novo Documento (PDF)</h4>
        </div>
        <div class="card-body">
            
            <?php if (empty($meusProcessos)) { ?>
                <div class="alert alert-warning">Você precisa ter processos cadastrados para anexar documentos.</div>
            <?php } else { ?>
            
                <form action="../acoes/salvar_documento.php" method="POST" enctype="multipart/form-data">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Vincular a qual Processo?</label>
                        <select class="form-select" name="id_processo_vinculado" required>
                            <option value="">Selecione o Processo...</option>
                            <?php 
                            foreach ($meusProcessos as $processo) { 
                                echo "<option value='" . htmlspecialchars($processo['id_processo']) . "'>";
                                echo "Processo: " . htmlspecialchars($processo['numero_processo']);
                                echo "</option>";
                            } 
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome / Título do Documento</label>
                        <input type="text" class="form-control" name="titulo_documento" placeholder="Ex: Petição Inicial Assinada" required>
                    </div>

                    <div class="mb-4 p-3 bg-light border border-dashed rounded">
                        <label class="form-label fw-bold text-danger">Selecione o Arquivo (Somente PDF)</label>
                        <input type="file" class="form-control" name="arquivo_pdf" accept=".pdf" required>
                    </div>

                    <button type="submit" class="btn btn-dark btn-lg w-100 fw-bold">Fazer Upload e Guardar no Sistema</button>

                </form>

            <?php } ?>
            
        </div>
    </div>
</div>

</body>
</html>