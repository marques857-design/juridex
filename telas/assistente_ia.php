<?php
// Ficheiro: telas/assistente_ia.php
// Onde guardar: Dentro da pasta 'telas'

session_start();
if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }

// Importamos a nossa ponte com o Google Gemini
require_once '../ia/gemini.php';

$respostaIA = "";
$perguntaAdvogado = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $perguntaAdvogado = $_POST['pergunta_usuario'];
    
    // =========================================================================
    // O PROMPT SECRETO DO SISTEMA (Atualizado sem Markdown)
    // =========================================================================
    $instrucaoDoSistema = "Você é um consultor jurídico brasileiro brilhante, direto e estratégico. ";
    $instrucaoDoSistema .= "Um advogado fará uma pergunta. Você deve responder fornecendo: \n";
    $instrucaoDoSistema .= "1) Possíveis teses a serem adotadas;\n";
    $instrucaoDoSistema .= "2) Fundamentos jurídicos (Leis, Súmulas, etc);\n";
    $instrucaoDoSistema .= "3) Estratégia inicial de ação.\n\n";
    $instrucaoDoSistema .= "REGRAS DE FORMATAÇÃO: NÃO use formatação markdown (como **). Use apenas texto puro, sem asteriscos, com quebras de linha normais.\n\n";
    $instrucaoDoSistema .= "PERGUNTA DO ADVOGADO: " . $perguntaAdvogado;

    // Chama o Google
    $respostaBruta = consultarInteligenciaArtificialDoGoogle($instrucaoDoSistema);

    // Filtro de limpeza para remover asteriscos teimosos da IA
    if (!empty($respostaBruta)) {
        $respostaIA = str_replace(['**', '*'], '', $respostaBruta);
    }

    // =========================================================================
    // A MEMÓRIA: Guardar no Histórico JSON
    // =========================================================================
    if (!empty($respostaIA) && strpos($respostaIA, 'Erro') === false && strpos($respostaIA, '⚠️') === false) {
        
        $arquivoHistorico = '../dados/Historico_IA.json';
        $historico = [];
        
        if (file_exists($arquivoHistorico)) {
            $historico = json_decode(file_get_contents($arquivoHistorico), true);
            if (!is_array($historico)) $historico = [];
        }

        $novoRegistro = [
            "id_consulta" => uniqid(),
            "id_advogado" => $_SESSION['id_usuario_logado'],
            "ferramenta" => "Assistente Jurídico",
            "pergunta" => $perguntaAdvogado,
            "resposta" => $respostaIA,
            "data_consulta" => date('Y-m-d H:i:s')
        ];

        $historico[] = $novoRegistro;
        file_put_contents($arquivoHistorico, json_encode($historico, JSON_PRETTY_PRINT));
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JURIDEX - Assistente Jurídico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .ai-header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: #00d2ff; }
        .caixa-resposta { background-color: #f8f9fa; border-left: 5px solid #0dcaf0; font-family: "Segoe UI", Arial, sans-serif; white-space: pre-wrap;}
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-info" href="central_ia.php">🧠 JURIDEX NEURAL</a>
        <a href="central_ia.php" class="btn btn-outline-light btn-sm">Voltar para a Central</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="card shadow border-info">
        <div class="card-header ai-header py-3">
            <h4 class="fw-bold mb-0">💬 Assistente Jurídico (Consultor IA)</h4>
        </div>
        <div class="card-body p-4">
            
            <form method="POST" action="assistente_ia.php">
                <div class="mb-3">
                    <label class="form-label fw-bold">Qual é a sua dúvida jurídica estratégica?</label>
                    <textarea class="form-control border-info shadow-sm" name="pergunta_usuario" rows="3" placeholder="Ex: Cliente teve aposentadoria negada pelo INSS sob a justificativa de falta de tempo de contribuição especial, o que posso fazer?" required><?php echo htmlspecialchars($perguntaAdvogado); ?></textarea>
                </div>
                <button type="submit" class="btn btn-info fw-bold text-dark w-100 shadow-sm">
                    Consultar Inteligência Artificial
                </button>
            </form>

            <?php if (!empty($respostaIA)) { ?>
                <hr class="my-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-success mb-0">✅ Resposta do Consultor:</h5>
                    <span class="badge bg-success">Salvo no Histórico</span>
                </div>

                <div class="caixa-resposta p-4 shadow-sm rounded">
                    <?php echo htmlspecialchars($respostaIA); ?>
                </div>
                
                <div class="alert alert-warning mt-3 text-center">
                    <small><strong>Aviso:</strong> Esta resposta é gerada por inteligência artificial e deve ser validada por um advogado.</small>
                </div>
            <?php } ?>

        </div>
    </div>
</div>

</body>
</html>