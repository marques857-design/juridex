<?php
// Arquivo: telas/cadastro_agenda.php
// Pasta: telas

session_start();

// Proteção da página
if (!isset($_SESSION['id_usuario_logado'])) {
    header("Location: login.php");
    exit;
}

/**
 * O que faz: Busca todos os processos DESSA advogada para colocar na caixinha de seleção.
 * Por que fazemos: Para ela poder vincular um prazo a um processo existente.
 */
function buscarTodosOsProcessosDoAdvogadoLogado($idDoAdvogado) {
    $listaDeProcessos = [];
    $arquivos = glob('../dados/Processos_*.json');
    
    if ($arquivos !== false) {
        foreach ($arquivos as $arquivo) {
            $conteudo = file_get_contents($arquivo);
            $processosDoMes = json_decode($conteudo, true);
            
            if (is_array($processosDoMes)) {
                foreach ($processosDoMes as $processo) {
                    if ($processo['id_advogado_responsavel'] == $idDoAdvogado) {
                        $listaDeProcessos[] = $processo;
                    }
                }
            }
        }
    }
    return $listaDeProcessos;
}

// Executamos a função e guardamos na variável
$meusProcessos = buscarTodosOsProcessosDoAdvogadoLogado($_SESSION['id_usuario_logado']);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JURIDEX - Novo Compromisso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="painel.php">JURIDEX</a>
        <div class="d-flex">
            <a href="painel.php" class="btn btn-outline-light btn-sm">Voltar ao Painel</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-danger text-white">
            <h4>Agendar Prazo ou Compromisso</h4>
        </div>
        <div class="card-body">
            
            <form action="../acoes/salvar_agenda.php" method="POST">
                
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-bold">Título do Compromisso</label>
                        <input type="text" class="form-control" name="titulo_compromisso" placeholder="Ex: Audiência de Conciliação" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Tipo</label>
                        <select class="form-select" name="tipo_compromisso" required>
                            <option value="Audiência">Audiência</option>
                            <option value="Prazo Processual">Prazo Processual</option>
                            <option value="Reunião com Cliente">Reunião com Cliente</option>
                            <option value="Tarefa Interna">Tarefa Interna</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Data e Hora</label>
                        <input type="datetime-local" class="form-control border-danger" name="data_hora_compromisso" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Vincular a um Processo? (Opcional)</label>
                        <select class="form-select" name="id_processo_vinculado">
                            <option value="">-- Compromisso Avulso (Nenhum) --</option>
                            <?php 
                            foreach ($meusProcessos as $processo) { 
                                echo "<option value='" . htmlspecialchars($processo['id_processo']) . "'>";
                                echo "Processo: " . htmlspecialchars($processo['numero_processo']);
                                echo "</option>";
                            } 
                            ?>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Descrição ou Link da Reunião Online</label>
                    <textarea class="form-control" name="descricao_compromisso" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-danger btn-lg w-100 fw-bold">Salvar na Agenda</button>

            </form>
            
        </div>
    </div>
</div>

</body>
</html>