<?php
// Arquivo: telas/ia_assistente_processo.php
session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }
require_once '../ia/gemini.php';

$idAdvogado = $_SESSION['id_usuario_logado'];
$acao = $_GET['acao'] ?? '';
$respostaIA = "";
$tituloPagina = "Assistente de Inteligência Artificial";

if ($acao == 'traduzir' && isset($_GET['texto'])) {
    $tituloPagina = "🪄 Tradutor de Juridiquês";
    $textoOriginal = $_GET['texto'];
    
    $prompt = "Você é um advogado especialista em comunicação clara (Legal Design). ";
    $prompt .= "Traduza o seguinte andamento processual para uma linguagem extremamente simples, acessível e leiga, para que uma pessoa sem conhecimento jurídico entenda o que aconteceu no processo dela. Seja direto e não use markdown.\n\n";
    $prompt .= "ANDAMENTO ORIGINAL: " . $textoOriginal;
    
    $respostaBruta = consultarInteligenciaArtificialDoGoogle($prompt);
    $respostaIA = str_replace(['**', '*', '####', '###', '##', '#', '`'], '', $respostaBruta);

} elseif ($acao == 'resumo_whatsapp' && isset($_GET['id_processo'])) {
    $tituloPagina = "📱 Resumo Automático para o WhatsApp";
    $idProcesso = $_GET['id_processo'];
    
    // Busca Processo
    $processo = null;
    $listaP = file_exists('../dados/Processos_'.$idAdvogado.'.json') ? json_decode(file_get_contents('../dados/Processos_'.$idAdvogado.'.json'), true) : [];
    foreach($listaP as $p) { if($p['id'] == $idProcesso) { $processo = $p; break; } }
    
    // Busca Cliente
    $nomeCliente = "Cliente";
    $listaC = file_exists('../dados/Clientes_'.$idAdvogado.'.json') ? json_decode(file_get_contents('../dados/Clientes_'.$idAdvogado.'.json'), true) : [];
    foreach($listaC as $c) { if($c['id'] == $processo['cliente_id']) { $nomeCliente = $c['nome']; break; } }
    
    // Busca Últimos Andamentos
    $andamentosTexto = "Nenhum andamento recente.";
    $arqAndamentos = '../dados/Andamentos_Processo_' . $idProcesso . '.json';
    if(file_exists($arqAndamentos)) {
        $and = json_decode(file_get_contents($arqAndamentos), true) ?? [];
        usort($and, function($a, $b) { return strtotime($b['data_andamento']) - strtotime($a['data_andamento']); });
        $ultimos = array_slice($and, 0, 3);
        if(count($ultimos) > 0) {
            $andamentosTexto = "";
            foreach($ultimos as $u) { $andamentosTexto .= "- " . date('d/m/Y', strtotime($u['data_andamento'])) . ": " . $u['descricao'] . "\n"; }
        }
    }

    $prompt = "Você é o assistente virtual de um escritório de advocacia. ";
    $prompt .= "Crie uma mensagem de WhatsApp amigável, humanizada e profissional para enviar ao cliente. ";
    $prompt .= "O objetivo é atualizá-lo sobre o processo dele, traduzindo o 'juridiquês' para palavras simples.\n";
    $prompt .= "Use emojis adequados. NÃO use formatação markdown (asteriscos, negrito do sistema).\n\n";
    $prompt .= "DADOS PARA A MENSAGEM:\n";
    $prompt .= "Nome do Cliente: " . $nomeCliente . "\n";
    $prompt .= "Processo (Ação): " . $processo['tipo_acao'] . " contra " . $processo['parte_contraria'] . "\n";
    $prompt .= "Últimos Andamentos que ocorreram no processo:\n" . $andamentosTexto;
    
    $respostaBruta = consultarInteligenciaArtificialDoGoogle($prompt);
    $respostaIA = str_replace(['**', '*', '####', '###', '##', '#', '`'], '', $respostaBruta);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - <?php echo $tituloPagina; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .ai-card { background-color: #f8f9fa; border-left: 5px solid #28a745; padding: 25px; border-radius: 8px; font-size: 1.1rem; white-space: pre-wrap; font-family: 'Segoe UI', sans-serif;}
        .tradutor-card { border-left-color: #6f42c1; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-white" href="painel.php">JURIDEX</a>
        <button class="btn btn-outline-light btn-sm" onclick="history.back()">Voltar ao Processo</button>
    </div>
</nav>

<div class="container mt-5 max-w-75">
    <div class="card shadow border-0">
        <div class="card-body p-5">
            <h3 class="fw-bold text-dark mb-4"><?php echo $tituloPagina; ?></h3>
            
            <?php if($acao == 'traduzir') { ?>
                <div class="alert alert-secondary"><strong>Original:</strong> <?php echo htmlspecialchars($textoOriginal); ?></div>
                <h5 class="fw-bold text-purple mt-4">🪄 Tradução da Inteligência Artificial:</h5>
                <div class="ai-card tradutor-card shadow-sm mt-3" id="textoResposta"><?php echo htmlspecialchars($respostaIA); ?></div>
            
            <?php } elseif($acao == 'resumo_whatsapp') { ?>
                <h5 class="fw-bold text-success mt-2">📱 Mensagem Pronta para Envio:</h5>
                <div class="ai-card shadow-sm mt-3" id="textoResposta"><?php echo htmlspecialchars($respostaIA); ?></div>
            <?php } ?>

            <div class="mt-4 text-center">
                <button class="btn btn-dark btn-lg fw-bold px-5 shadow" onclick="copiarTexto()">📋 Copiar Texto</button>
                <?php if($acao == 'resumo_whatsapp') { ?>
                    <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($respostaIA); ?>" target="_blank" class="btn btn-success btn-lg fw-bold px-5 shadow ms-2">💬 Abrir no WhatsApp</a>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<script>
function copiarTexto() {
    var texto = document.getElementById("textoResposta").innerText;
    navigator.clipboard.writeText(texto).then(function() {
        alert("Texto copiado com sucesso! Agora é só colar.");
    }, function(err) {
        alert("Erro ao copiar o texto.");
    });
}
</script>

</body>
</html>