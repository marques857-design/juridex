<?php
// Arquivo: acoes/salvar_cliente.php
// Onde salvar: Dentro da pasta 'acoes'

session_start();

// Proteção
if (!isset($_SESSION['id_usuario_logado'])) {
    die("Acesso negado. Intruso bloqueado.");
}

/**
 * O que faz: Descobre o ano e o mês atual para criar o nome do ficheiro.
 * Exemplo: Retorna "../dados/Clientes_2026_03.json"
 */
function gerarNomeDoArquivoDeClientesDesteMes() {
    $anoAtual = date('Y');
    $mesAtual = date('m'); // Retorna 03 para março, 04 para abril, etc.
    return "../dados/Clientes_" . $anoAtual . "_" . $mesAtual . ".json";
}

/**
 * O que faz: Lê o ficheiro do mês. Se ainda não houver nenhum cliente este mês, cria uma lista vazia.
 */
function buscarListaDeClientesDoMes($caminhoDoArquivo) {
    if (!file_exists($caminhoDoArquivo)) {
        return [];
    }
    $conteudo = file_get_contents($caminhoDoArquivo);
    return json_decode($conteudo, true);
}

/**
 * O que faz: Guarda a lista de volta no ficheiro JSON.
 */
function salvarDadosDoClienteNoArquivo($caminhoDoArquivo, $listaAtualizada) {
    $textoJson = json_encode($listaAtualizada, JSON_PRETTY_PRINT);
    file_put_contents($caminhoDoArquivo, $textoJson);
}

// =========================================================================
// PROCESSAMENTO DO FORMULÁRIO
// =========================================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Apanhamos QUEM está a registar o cliente (O Advogado Logado)
    $idDoAdvogado = $_SESSION['id_usuario_logado'];

    // 2. Apanhamos o que foi digitado na tela
    $nome = $_POST['nome_cliente'];
    $documento = $_POST['documento_cliente'];
    $telefone = $_POST['telefone_cliente'];
    $email = $_POST['email_cliente'];
    $endereco = $_POST['endereco_cliente'];
    $observacoes = $_POST['observacoes_cliente'];

    // 3. Montamos a "Ficha do Cliente"
    // DESNORMALIZAÇÃO: Guardamos o ID do advogado aqui dentro para sabermos de quem é este cliente!
    $novoCliente = [
        "id_cliente" => uniqid(),
        "id_advogado_responsavel" => $idDoAdvogado, // A mágica da separação de dados acontece aqui!
        "nome" => $nome,
        "documento" => $documento,
        "telefone" => $telefone,
        "email" => $email,
        "endereco" => $endereco,
        "observacoes" => $observacoes,
        "data_cadastro" => date('Y-m-d H:i:s')
    ];

    // 4. Descobrimos o nome do ficheiro deste mês
    $arquivoDesteMes = gerarNomeDoArquivoDeClientesDesteMes();

    // 5. Puxamos a lista de clientes deste mês
    $listaDeClientes = buscarListaDeClientesDoMes($arquivoDesteMes);

    // 6. Adicionamos o novo cliente na lista
    $listaDeClientes[] = $novoCliente;

    // 7. Guardamos a lista no ficheiro
    salvarDadosDoClienteNoArquivo($arquivoDesteMes, $listaDeClientes);

    // 8. Mostramos uma mensagem de sucesso na tela usando JavaScript e voltamos para o formulário
    echo "<script>
            alert('Sucesso! Cliente " . $nome . " cadastrado perfeitamente no JURIDEX.');
            window.location.href = '../telas/cadastro_cliente.php';
          </script>";
}
?>