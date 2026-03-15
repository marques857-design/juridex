<?php
// Arquivo: telas/ia_simulador_audiencia.php
// Função: Prepara o cliente gerando as perguntas que o Juiz fará na audiência.

session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }

require_once '../ia/gemini.php';

$respostaIA = "";
$fatosCaso = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $fatosCaso = $_POST['fatos_caso'] ?? '';
    
    $instrucaoDoSistema = "Aja como um Juiz de Direito e como um Promotor/Advogado da parte contrária numa audiência de instrução e julgamento no Brasil. ";
    $instrucaoDoSistema .= "Sua missão é ajudar o meu cliente a preparar-se para a audiência, elaborando as perguntas mais difíceis e capciosas que poderão ser feitas a ele ou às suas testemunhas com base nos fatos relatados.\n\n";
    $instrucaoDoSistema .= "REGRAS DE FORMATAÇÃO:\n";
    $instrucaoDoSistema .= "- NÃO use markdown (```html). Responda diretamente com tags HTML.\n";
    $instrucaoDoSistema .= "- Use APENAS: <h4>, <p>, <ul>, <li>, <strong>, <i>, <br>.\n\n";
    $instrucaoDoSistema .= "Estruture o seu guia OBRIGATORIAMENTE nestes 3 pilares:\n\n";
    $instrucaoDoSistema .= "<h4>👨‍⚖️ 1. O que o Juiz vai perguntar (Perguntas de Esclarecimento)</h4>\n<ul><li>(Lista de 3 a 5 perguntas diretas)</li></ul>\n\n";
    $instrucaoDoSistema .= "<h4>🦊 2. As Armadilhas da Parte Contrária (Perguntas Capciosas)</h4>\n<ul><li>(Lista de 3 a 5 perguntas feitas para o cliente cair em contradição ou confessar algo)</li></ul>\n\n";
    $instrucaoDoSistema .= "<h4>🛡️ 3. Como o Cliente deve se Comportar (Instruções do Advogado)</h4>\n<p>(Dicas de como ele deve responder a essas armadilhas de forma segura, sem mentir, mas sem se prejudicar. Ex: Responder apenas sim ou não, não prolongar, etc.)</p>\n\n";
    $instrucaoDoSistema .= "FATOS DO CASO E DO CLIENTE:\n" . $fatosCaso;

    $respostaBruta = consultarInteligenciaArtificialDoGoogle($instrucaoDoSistema);

    if (!empty($respostaBruta)) {
        $respostaLimpa = preg_replace('/```html\s*/i', '', $respostaBruta);
        $respostaLimpa = preg_replace('/```\s*/', '', $respostaLimpa);
        $respostaLimpa = str_replace(['**', '*'], '', $respostaLimpa);
        $respostaIA = trim($respostaLimpa);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Simulador de Audiência - JURIDEX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .main-content { margin-left: 280px; min-height: 100vh; display: flex; flex-direction: column; }
        .topbar { background: #fff; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; }
        
        .ai-header { background: linear-gradient(135deg, #232526 0%, #414345 100%); color: #fff; border-radius: 12px; padding: 40px; margin-bottom: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.2);}
        
        .caixa-resposta { background-color: #ffffff; border-left: 5px solid #232526; padding: 30px; border-radius: 8px; border: 1px solid #eee; box-shadow: 0 4px 15px rgba(0,0,0,0.05);}
        .caixa-resposta h4 { color: #1c1f3b; font-weight: 800; font-size: 1.1rem; text-transform: uppercase; margin-top: 25px; margin-bottom: 15px; border-bottom: 1px solid #f0f0f0; padding-bottom: 5px; }
        .caixa-resposta h4:first-child { margin-top: 0; }
        .caixa-resposta p, .caixa-resposta li { font-size: 1.05rem; line-height: 1.6; color: #333; }
        
        @media (max-width: 991px) { .main-content { margin-left: 0; width: 100%; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h4 class="mb-0 fw-bold text-dark">Simulador de Audiências</h4>
        <a href="central_ia.php" class="btn btn-outline-dark btn-sm fw-bold">Voltar à Central</a>
    </div>

    <div class="container-fluid px-4 mb-5">
        <div class="ai-header text-center">
            <h1 style="font-size: 3.5rem; margin-bottom: 10px;">🎙️</h1>
            <h3 class="fw-bold mb-1">Preparador de Clientes e Testemunhas</h3>
            <p class="text-white-50 fs-5 mb-0">Evite surpresas na hora H. A IA prevê as perguntas do Juiz e os ataques da outra parte com base no seu caso.</p>
        </div>

        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-body p-4">
                <form method="POST" action="ia_simulador_audiencia.php" onsubmit="mostrarLoading()">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">Resuma os Fatos do Processo (O que o cliente alega vs o que o réu alega):</label>
                        <textarea class="form-control border-2 bg-light" name="fatos_caso" rows="6" placeholder="O cliente foi demitido por justa causa sob alegação de furto, mas não há imagens. A empresa arrolou o gerente como testemunha..." required><?php echo htmlspecialchars($fatosCaso); ?></textarea>
                    </div>
                    <button type="submit" id="btnSubmit" class="btn btn-dark btn-lg w-100 fw-bold shadow">🔮 Gerar Guião de Audiência</button>
                </form>
            </div>
        </div>

        <?php if (!empty($respostaIA)): ?>
            <h4 class="fw-bold mt-5 mb-3 text-dark">Guião Preparatório (Imprima para o Cliente):</h4>
            <div class="caixa-resposta" id="conteudoAuditoria">
                <?php echo $respostaIA; ?>
            </div>
            <button class="btn btn-outline-dark fw-bold mt-3" onclick="window.print()">🖨️ Imprimir Guião</button>
        <?php endif; ?>

    </div>
</div>

<script>
function mostrarLoading() {
    const btn = document.getElementById('btnSubmit');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Analisando perfil de juízes...';
    btn.classList.add('disabled');
}
</script>
</body>
</html>