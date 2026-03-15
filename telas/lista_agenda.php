<?php
// Arquivo: telas/lista_agenda.php
// Pasta: telas

session_start();

// Proteção do crachá
if (!isset($_SESSION['id_usuario_logado'])) {
    header("Location: login.php");
    exit;
}

$idDoAdvogadoLogado = $_SESSION['id_usuario_logado'];

/**
 * PASSO 1: Dicionário de Processos
 * O que faz: Lê os processos e guarda o "Número do Processo" ligado ao seu ID.
 */
function buscarDicionarioDeProcessosDoAdvogado($idAdvogado) {
    $dicionario = [];
    $arquivos = glob('../dados/Processos_*.json');
    if ($arquivos !== false) {
        foreach ($arquivos as $arquivo) {
            $conteudo = file_get_contents($arquivo);
            $lista = json_decode($conteudo, true);
            if (is_array($lista)) {
                foreach ($lista as $processo) {
                    if ($processo['id_advogado_responsavel'] == $idAdvogado) {
                        $id = $processo['id_processo'];
                        $numero = $processo['numero_processo'];
                        $dicionario[$id] = $numero;
                    }
                }
            }
        }
    }
    return $dicionario;
}

/**
 * PASSO 2: Buscar toda a Agenda e organizar.
 */
function buscarEOrdenarAgendaDoAdvogado($idAdvogado) {
    $listaFinal = [];
    $arquivos = glob('../dados/Agenda_*.json');
    
    if ($arquivos !== false) {
        foreach ($arquivos as $arquivo) {
            $conteudo = file_get_contents($arquivo);
            $lista = json_decode($conteudo, true);
            if (is_array($lista)) {
                foreach ($lista as $compromisso) {
                    if ($compromisso['id_advogado_responsavel'] == $idAdvogado) {
                        $listaFinal[] = $compromisso;
                    }
                }
            }
        }
    }

    // A MÁGICA DA ORDENAÇÃO: Ensina o PHP a organizar da data mais antiga para a mais nova
    usort($listaFinal, function($a, $b) {
        $tempoA = strtotime($a['data_hora']);
        $tempoB = strtotime($b['data_hora']);
        return $tempoA - $tempoB; // Se der negativo, o 'a' vem antes!
    });

    return $listaFinal;
}

// Executando o cérebro
$dicionarioProcessos = buscarDicionarioDeProcessosDoAdvogado($idDoAdvogadoLogado);
$minhaAgenda = buscarEOrdenarAgendaDoAdvogado($idDoAdvogadoLogado);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JURIDEX - Minha Agenda</title>
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
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Meus Prazos e Compromissos</h2>
        <a href="cadastro_agenda.php" class="btn btn-danger fw-bold">+ Novo Agendamento</a>
    </div>

    <div class="card shadow border-danger">
        <div class="card-body">
            
            <?php 
            if (empty($minhaAgenda)) { 
                echo "<div class='alert alert-secondary text-center'>";
                echo "<strong>Sua agenda está livre!</strong><br>Não há compromissos marcados no momento.";
                echo "</div>";
            } else { 
            ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Data e Hora</th>
                                <th>Título do Compromisso</th>
                                <th>Tipo</th>
                                <th>Vinculado a</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach ($minhaAgenda as $compromisso) { 
                                
                                // Formatando a data do jeito brasileiro (Ex: 15/03/2026 às 14:30)
                                $dataObjeto = new DateTime($compromisso['data_hora']);
                                $dataBonita = $dataObjeto->format('d/m/Y \à\s H:i');

                                // Verificando se tem processo vinculado usando o Dicionário
                                $idProcesso = $compromisso['id_processo_vinculado'];
                                if (!empty($idProcesso) && isset($dicionarioProcessos[$idProcesso])) {
                                    $textoVinculo = "Processo: " . $dicionarioProcessos[$idProcesso];
                                    $classeTexto = "text-primary fw-bold";
                                } else {
                                    $textoVinculo = "Compromisso Avulso";
                                    $classeTexto = "text-muted";
                                }
                            ?>
                                <tr>
                                    <td class="fw-bold text-danger"><?php echo $dataBonita; ?></td>
                                    
                                    <td class="fw-bold"><?php echo htmlspecialchars($compromisso['titulo']); ?></td>
                                    
                                    <td>
                                        <span class="badge bg-info text-dark"><?php echo htmlspecialchars($compromisso['tipo']); ?></span>
                                    </td>
                                    
                                    <td class="<?php echo $classeTexto; ?>">
                                        <small><?php echo htmlspecialchars($textoVinculo); ?></small>
                                    </td>
                                    
                                    <td>
                                        <button class="btn btn-sm btn-outline-secondary">Concluir</button>
                                    </td>
                                </tr>
                            <?php 
                            } 
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php 
            } 
            ?>

        </div>
    </div>
</div>

</body>
</html>