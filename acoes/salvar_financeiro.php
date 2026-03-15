<?php
// Arquivo: acoes/salvar_financeiro.php
// Pasta: acoes

session_start();

// Proteção total
if (!isset($_SESSION['id_usuario_logado'])) {
    die("Acesso negado.");
}

/**
 * Função para gerar o nome do arquivo financeiro deste mês.
 */
function gerarNomeDoArquivoFinanceiroDesteMes() {
    $ano = date('Y');
    $mes = date('m');
    return "../dados/Financeiro_" . $ano . "_" . $mes . ".json";
}

/**
 * Função para ler o caixa do mês atual.
 */
function buscarListaFinanceiraDoMes($caminhoDoArquivo) {
    if (!file_exists($caminhoDoArquivo)) {
        return [];
    }
    return json_decode(file_get_contents($caminhoDoArquivo), true);
}

/**
 * Função para guardar o novo lançamento no cofre (arquivo).
 */
function salvarDadosFinanceirosNoArquivo($caminhoDoArquivo, $listaAtualizada) {
    file_put_contents($caminhoDoArquivo, json_encode($listaAtualizada, JSON_PRETTY_PRINT));
}

// =========================================================================
// PROCESSAMENTO DO FORMULÁRIO FINANCEIRO
// =========================================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Quem está operando o caixa?
    $idDoAdvogado = $_SESSION['id_usuario_logado'];

    // 2. Apanhar os dados digitados
    $descricao = $_POST['descricao_financeira'];
    $tipo = $_POST['tipo_lancamento'];
    $valor = $_POST['valor_lancamento'];
    $dataVencimento = $_POST['data_vencimento'];
    $status = $_POST['status_pagamento'];
    $idCliente = $_POST['id_cliente_vinculado']; // Pode ser vazio se for uma conta de luz do escritório

    // 3. Montar o "Boleto/Recibo"
    $novoLancamento = [
        "id_financeiro" => uniqid(),
        "id_advogado_responsavel" => $idDoAdvogado,
        "id_cliente_vinculado" => $idCliente, 
        "descricao" => $descricao,
        "tipo" => $tipo,
        "valor" => (float) $valor, // Converte para número decimal real
        "data_vencimento" => $dataVencimento,
        "status" => $status,
        "data_cadastro" => date('Y-m-d H:i:s')
    ];

    // 4. Descobre o arquivo do mês
    $arquivoDesteMes = gerarNomeDoArquivoFinanceiroDesteMes();

    // 5. Puxa a lista do caixa
    $listaDoCaixa = buscarListaFinanceiraDoMes($arquivoDesteMes);

    // 6. Adiciona o novo lançamento
    $listaDoCaixa[] = $novoLancamento;

    // 7. Salva a lista inteira de volta no arquivo
    salvarDadosFinanceirosNoArquivo($arquivoDesteMes, $listaDoCaixa);

    // 8. Mensagem de sucesso
    echo "<script>
            alert('Lançamento salvo com sucesso no caixa do escritório!');
            window.location.href = '../telas/cadastro_financeiro.php';
          </script>";
}
?>