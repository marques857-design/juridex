<?php
// Arquivo: acoes/salvar_processo.php
// Onde salvar: Dentro da pasta 'acoes'

session_start();

// Proteção de Segurança
if (!isset($_SESSION['id_usuario_logado'])) {
    die("Acesso negado.");
}

/**
 * Função para criar o nome do ficheiro do mês atual para os Processos.
 */
function gerarNomeDoArquivoDeProcessosDesteMes() {
    $anoAtual = date('Y');
    $mesAtual = date('m');
    return "../dados/Processos_" . $anoAtual . "_" . $mesAtual . ".json";
}

/**
 * Função para puxar a lista de processos deste mês.
 */
function buscarListaDeProcessosDoMes($caminhoDoArquivo) {
    if (!file_exists($caminhoDoArquivo)) {
        return [];
    }
    $conteudo = file_get_contents($caminhoDoArquivo);
    return json_decode($conteudo, true);
}

/**
 * Função para guardar tudo de volta no JSON.
 */
function salvarDadosDoProcessoNoArquivo($caminhoDoArquivo, $listaAtualizada) {
    $textoJson = json_encode($listaAtualizada, JSON_PRETTY_PRINT);
    file_put_contents($caminhoDoArquivo, $textoJson);
}

// =========================================================================
// PROCESSAMENTO DO FORMULÁRIO
// =========================================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Quem está a guardar?
    $idDoAdvogado = $_SESSION['id_usuario_logado'];

    // 2. Apanhar os dados digitados e escolhidos na tela
    $idCliente = $_POST['id_cliente_vinculado']; // Este é o ID do cliente que veio do dropdown!
    $numeroProcesso = $_POST['numero_processo'];
    $tribunal = $_POST['tribunal'];
    $classeProcessual = $_POST['classe_processual'];
    $statusProcesso = $_POST['status_processo'];
    $resumoAcao = $_POST['resumo_acao'];

    // 3. Montar o Pacote do Processo
    // Repare: guardamos de quem é o processo E de que advogado ele é! Dupla segurança.
    $novoProcesso = [
        "id_processo" => uniqid(),
        "id_advogado_responsavel" => $idDoAdvogado,
        "id_cliente_vinculado" => $idCliente, 
        "numero_processo" => $numeroProcesso,
        "tribunal" => $tribunal,
        "classe_processual" => $classeProcessual,
        "status_processo" => $statusProcesso,
        "resumo_acao" => $resumoAcao,
        "data_cadastro" => date('Y-m-d H:i:s')
    ];

    // 4. Descobre o ficheiro do mês
    $arquivoDesteMes = gerarNomeDoArquivoDeProcessosDesteMes();

    // 5. Puxa a lista
    $listaDeProcessos = buscarListaDeProcessosDoMes($arquivoDesteMes);

    // 6. Adiciona o processo novo
    $listaDeProcessos[] = $novoProcesso;

    // 7. Guarda no ficheiro!
    salvarDadosDoProcessoNoArquivo($arquivoDesteMes, $listaDeProcessos);

    // 8. Mensagem de sucesso e redirecionamento
    echo "<script>
            alert('Sucesso! Processo número " . htmlspecialchars($numeroProcesso) . " guardado no JURIDEX.');
            window.location.href = '../telas/cadastro_processo.php';
          </script>";
}
?>