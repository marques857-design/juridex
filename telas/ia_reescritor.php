<?php
// Arquivo: telas/ia_reescritor.php
// Onde salvar: Dentro da pasta 'telas'

session_start();

// Proteção de segurança
if (!isset($_SESSION['id_usuario_logado'])) { 
    header("Location: login.php"); 
    exit; 
}

// Importa a nossa ponte de comunicação com o Google Gemini
require_once '../ia/gemini.php';

$respostaIA = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Pegamos o que o usuário digitou na tela
    $textoOriginal = $_POST['texto_original'];
    $tomDesejado = $_POST['tom_desejado'];
    
    // =========================================================================
    // O PROMPT DO REESCRITOR: A mágica da lapidação de texto
    // =========================================================================
    $instrucaoDoSistema = "Você é um Revisor e Redator Jurídico Sênior com vasta experiência nos tribunais brasileiros. ";
    $instrucaoDoSistema .= "Sua tarefa é reescrever o texto fornecido pelo advogado, aplicando o seguinte tom ou objetivo: [" . mb_strtoupper($tomDesejado, 'UTF-8') . "].\n\n";
    $instrucaoDoSistema .= "REGRAS OBRIGATÓRIAS:\n";
    $instrucaoDoSistema .= "- Não adicione fatos novos que não estejam no texto original.\n";
    $instrucaoDoSistema .= "- Corrija qualquer erro gramatical ou de concordância.\n";
    $instrucaoDoSistema .= "- Use vocabulário jurídico adequado ao tom solicitado.\n";
    $instrucaoDoSistema .= "- Entregue APENAS o texto reescrito pronto para uso, sem introduções como 'Aqui está o texto'.\n";
    $instrucaoDoSistema .= "- Não use formatação em markdown (como ** ou #), apenas texto limpo.\n\n";
    $instrucaoDoSistema .= "TEXTO ORIGINAL PARA REESCREVER:\n" . $textoOriginal;

    // Manda para o Gemini e aguarda o texto lapidado
    $respostaIA = consultarInteligenciaArtificialDoGoogle($instrucaoDoSistema);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JURIDEX - Reescritor Jurídico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .ai-header { background: linear-gradient(135deg, #f5af19 0%, #f12711 100%); color: #fff; }
        .caixa-resposta { font-family: "Times New Roman", Times, serif; font-size: 16px; line-height: 1.6; background-color: #fff; }
        textarea { resize: vertical; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-warning" href="central_ia.php">🧠 JURIDEX NEURAL</a>
        <div class="d-flex">
            <a href="central_ia.php" class="btn btn-outline-light btn-sm">Voltar para a Central</a>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="card shadow-lg border-warning">
        <div class="card-header ai-header py-3 text-center">
            <h4 class="fw-bold mb-0 text-dark">✍️ Reescritor e Revisor Jurídico</h4>
            <p class="text-dark mt-1 mb-0 small fw-bold">Transforme rascunhos ou relatos de clientes em textos jurídicos impecáveis.</p>
        </div>
        <div class="card-body p-4">
            
            <form method="POST" action="ia_reescritor.php">
                
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">O que você deseja que a IA faça com o texto?</label>
                    <select class="form-select border-warning fw-bold" name="tom_desejado" required>
                        <option value="Tornar o texto mais formal e com jargões jurídicos adequados">Deixar Mais Formal e Culto</option>
                        <option value="Tornar o texto mais firme, incisivo e persuasivo">Deixar Mais Firme/Agressivo (Para Contestações/Notificações)</option>
                        <option value="Resumir o texto, mantendo apenas a essência jurídica e cortando excessos">Deixar Mais Conciso e Direto ao Ponto</option>
                        <option value="Apenas corrigir erros de gramática e pontuação, mantendo o tom original">Apenas Corrigir Gramática</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Cole o seu rascunho ou texto original aqui:</label>
                    <textarea class="form-control border-warning shadow-sm" name="texto_original" rows="6" placeholder="Ex: O cara bateu no meu carro no cruzamento e não quer pagar. Eu tava na preferencial e ele furou o pare. Quero processar ele por danos materiais." required></textarea>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-warning btn-lg fw-bold text-dark shadow-sm">
                        ✨ Melhorar Texto Agora
                    </button>
                </div>
            </form>

            <?php 
            // Se o Google devolveu o texto novo, nós mostramos!
            if (!empty($respostaIA)) { 
            ?>
                <hr class="my-5">
                <h5 class="fw-bold text-warning text-dark mb-3"><i class="bi bi-stars"></i> Seu texto lapidado:</h5>
                
                <textarea class="form-control caixa-resposta p-4 shadow-sm border-warning" rows="10"><?php echo htmlspecialchars($respostaIA); ?></textarea>
                
                <div class="d-grid mt-3">
                    <button class="btn btn-dark fw-bold shadow-sm" onclick="alert('Texto copiado! Pronto para colar no Word ou sistema do Tribunal.')">
                        📋 Copiar Texto Melhorado
                    </button>
                </div>

                <div class="alert alert-secondary mt-4 text-center">
                    <small><strong>Aviso:</strong> Revise o texto gerado pela IA antes de anexá-lo aos autos oficiais.</small>
                </div>
            <?php } ?>

        </div>
    </div>
</div>

</body>
</html>