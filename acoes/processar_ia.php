<?php
// Arquivo: acoes/processar_ia.php
// Pasta: acoes

session_start();

if (!isset($_SESSION['id_usuario_logado'])) {
    die("Acesso negado.");
}

// 1. CHAMA O NOSSO ARQUIVO DE CONEXÃO COM O GEMINI
require_once '../ia/gemini.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Pegamos as informações da tela
    $nomeCliente = $_POST['id_cliente_ia'];
    $tipoPeticao = $_POST['tipo_peticao_ia'];
    $fatos = $_POST['fatos_ia'];
    $nomeAdvogado = $_SESSION['nome_usuario_logado'];

    // =========================================================================
    // O PROMPT: A ARTE DE FALAR COM A IA
    // =========================================================================
    // Aqui nós dizemos à IA quem ela é e o que ela deve fazer detalhadamente.
    
    $ordemParaAInteligencia = "Você é um assistente jurídico sênior e especialista em direito brasileiro. ";
    $ordemParaAInteligencia .= "Sua tarefa é redigir uma petição inicial ou contestação completa e profissional. ";
    $ordemParaAInteligencia .= "Não use formatação markdown (como asteriscos duplos **), use apenas texto limpo e quebras de linha normais.\n\n";
    $ordemParaAInteligencia .= "DADOS DO PROCESSO:\n";
    $ordemParaAInteligencia .= "- Tipo de Ação: " . $tipoPeticao . "\n";
    $ordemParaAInteligencia .= "- Cliente (Autor/Réu): " . $nomeCliente . "\n";
    $ordemParaAInteligencia .= "- Advogado assinante: " . $nomeAdvogado . "\n";
    $ordemParaAInteligencia .= "- Fatos ocorridos: " . $fatos . "\n\n";
    $ordemParaAInteligencia .= "ESTRUTURA OBRIGATÓRIA DA PETIÇÃO:\n";
    $ordemParaAInteligencia .= "1. Endereçamento (Excelentíssimo Senhor Doutor Juiz...)\n";
    $ordemParaAInteligencia .= "2. Qualificação do cliente\n";
    $ordemParaAInteligencia .= "3. Dos Fatos (desenvolva os fatos fornecidos com linguagem jurídica culta)\n";
    $ordemParaAInteligencia .= "4. Do Direito (cite fundamentos jurídicos, princípios e, se possível, menção a jurisprudência genérica aplicável)\n";
    $ordemParaAInteligencia .= "5. Dos Pedidos (liste os pedidos de forma clara e numerada)\n";
    $ordemParaAInteligencia .= "6. Fechamento e Assinatura com a data de hoje.\n";

    // =========================================================================
    // A MAGIA ACONTECE AQUI! Chamamos a função do arquivo gemini.php
    // =========================================================================
    
    $peticaoGeradaPeloGoogle = consultarInteligenciaArtificialDoGoogle($ordemParaAInteligencia);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JURIDEX - Petição Gerada por IA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .ai-header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: #00d2ff; }
        .caixa-peticao { font-family: "Times New Roman", Times, serif; font-size: 16px; line-height: 1.6; background-color: #fff; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-info" href="../telas/painel.php">JURIDEX AI</a>
        <div class="d-flex">
            <a href="../telas/gerador_peticao_ia.php" class="btn btn-outline-light btn-sm">Voltar ao Gerador</a>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="card shadow-lg border-info">
        <div class="card-header ai-header text-center py-3">
            <h4 class="fw-bold mb-0">✨ Peça Jurídica Gerada pelo Google Gemini</h4>
        </div>
        <div class="card-body p-4 bg-light">
            
            <p class="text-muted text-center mb-4">Abaixo está a petição elaborada pela Inteligência Artificial baseada nos fatos informados. Leia com atenção e edite o que for necessário.</p>
            
            <textarea class="form-control caixa-peticao shadow-sm p-4 border-info" rows="30"><?php echo htmlspecialchars($peticaoGeradaPeloGoogle); ?></textarea>
            
            <div class="d-grid mt-4">
                <button class="btn btn-dark btn-lg shadow fw-bold" onclick="alert('Petição copiada! Você já pode colar no Microsoft Word.')">
                    📋 Copiar Texto para o Word
                </button>
            </div>

            <div class="alert alert-warning mt-3 mb-0 text-center">
                <small><strong>Aviso Importante:</strong> A IA é uma ferramenta de auxílio. Toda peça jurídica gerada deve ser lida, revisada e validada por um advogado habilitado antes do peticionamento oficial.</small>
            </div>

        </div>
    </div>
</div>

</body>
</html>
<?php
} else {
    echo "Acesso inválido.";
}
?>