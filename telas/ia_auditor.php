<?php
// Arquivo: telas/ia_auditor.php
// Função: Revisor de petições. Lê PDFs, caça brechas e sugere melhorias.

session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }

require_once '../ia/gemini.php';

$respostaIA = "";
$textoPetição = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['texto_peticao'])) {
    
    $textoPetição = $_POST['texto_peticao'];
    
    $instrucaoDoSistema = "Aja como um Desembargador rigoroso e um Auditor Estratégico Sênior. ";
    $instrucaoDoSistema .= "Sua missão é auditar a petição jurídica abaixo ANTES de ela ser protocolada, identificando brechas, contradições e fragilidades jurídicas.\n\n";
    $instrucaoDoSistema .= "REGRAS DE FORMATAÇÃO:\n";
    $instrucaoDoSistema .= "- NÃO use markdown (```html). Responda diretamente com tags HTML limpas.\n";
    $instrucaoDoSistema .= "- Use APENAS: <h4>, <p>, <ul>, <li>, <strong>, <br>.\n\n";
    $instrucaoDoSistema .= "Estruture o seu relatório OBRIGATORIAMENTE nestes 4 pilares:\n\n";
    $instrucaoDoSistema .= "<h4>🎯 1. Pontos Fortes da Peça</h4>\n<p>(O que está bem fundamentado)</p>\n\n";
    $instrucaoDoSistema .= "<h4>⚠️ 2. Brechas e Fragilidades (Riscos do processo)</h4>\n<ul><li>(Aponte falhas argumentativas, falta de provas mencionadas ou pedidos mal formulados)</li></ul>\n\n";
    $instrucaoDoSistema .= "<h4>⚖️ 3. Jurisprudência ou Doutrina Faltante</h4>\n<p>(Sugira Súmulas ou artigos do código que o advogado esqueceu de citar e que blindariam a peça)</p>\n\n";
    $instrucaoDoSistema .= "<h4>🛠️ 4. Plano de Correção Imediato</h4>\n<p>(Sugestão exata de como reescrever o trecho falho)</p>\n\n";
    $instrucaoDoSistema .= "TEXTO DA PETIÇÃO A SER AUDITADA:\n" . $textoPetição;

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
    <title>Auditor Estratégico - JURIDEX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .main-content { margin-left: 280px; min-height: 100vh; display: flex; flex-direction: column; }
        .topbar { background: #fff; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; }
        
        .ai-header { background: linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%); color: #fff; border-radius: 12px; padding: 40px; margin-bottom: 20px; box-shadow: 0 10px 20px rgba(203, 45, 62, 0.2);}
        
        .caixa-resposta { background-color: #ffffff; border-left: 5px solid #cb2d3e; padding: 30px; border-radius: 8px; border: 1px solid #eee; box-shadow: 0 4px 15px rgba(0,0,0,0.05);}
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
        <h4 class="mb-0 fw-bold text-dark">Auditor Estratégico</h4>
        <a href="central_ia.php" class="btn btn-outline-dark btn-sm fw-bold">Voltar à Central</a>
    </div>

    <div class="container-fluid px-4 mb-5">
        <div class="ai-header text-center">
            <h1 style="font-size: 3.5rem; margin-bottom: 10px;">⚖️</h1>
            <h3 class="fw-bold mb-1">Auditor de Peças Jurídicas</h3>
            <p class="text-white-50 fs-5 mb-0">Envie o PDF ou cole o rascunho da sua petição. A IA fará uma varredura procurando fragilidades antes do protocolo.</p>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                    <div class="card-body p-4">
                        
                        <div class="mb-4 p-3 bg-light border border-danger rounded text-center" style="border-style: dashed !important;">
                            <label class="form-label fw-bold text-danger mb-2">📁 Tem a petição em PDF? Selecione-a para extrair o texto automaticamente:</label>
                            <input type="file" id="pdfFileInput" class="form-control w-50 mx-auto" accept="application/pdf">
                            <small class="text-muted mt-2 d-block" id="pdfStatus">O texto será extraído e colado na caixa abaixo.</small>
                        </div>

                        <form method="POST" action="ia_auditor.php" onsubmit="mostrarLoading()">
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark">Texto da Petição para Auditoria:</label>
                                <textarea class="form-control border-2" id="textoArea" name="texto_peticao" rows="10" placeholder="Excelentíssimo Senhor Doutor Juiz de Direito..." required><?php echo htmlspecialchars($textoPetição); ?></textarea>
                            </div>
                            <button type="submit" id="btnSubmit" class="btn btn-danger btn-lg w-100 fw-bold shadow">🔍 Iniciar Auditoria Estratégica</button>
                        </form>
                    </div>
                </div>
            </div>

            <?php if (!empty($respostaIA)): ?>
                <div class="col-lg-12 mt-4">
                    <h4 class="fw-bold mb-3 text-dark">Relatório de Auditoria:</h4>
                    <div class="caixa-resposta" id="conteudoAuditoria">
                        <?php echo $respostaIA; ?>
                    </div>
                    <button class="btn btn-dark mt-3 fw-bold shadow-sm" onclick="window.print()">🖨️ Imprimir Relatório</button>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
// Motor de Extração de PDF (Roda no navegador do usuário)
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

document.getElementById('pdfFileInput').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (!file) return;

    const statusElement = document.getElementById('pdfStatus');
    statusElement.innerText = "⏳ Extraindo texto do PDF... Aguarde.";
    statusElement.classList.add("text-primary");

    const fileReader = new FileReader();
    fileReader.onload = function() {
        const typedarray = new Uint8Array(this.result);

        pdfjsLib.getDocument(typedarray).promise.then(function(pdf) {
            let numPages = pdf.numPages;
            let fullText = "";
            let pagesPromises = [];

            for (let i = 1; i <= numPages; i++) {
                pagesPromises.push(
                    pdf.getPage(i).then(function(page) {
                        return page.getTextContent().then(function(textContent) {
                            let pageText = textContent.items.map(item => item.str).join(' ');
                            return pageText;
                        });
                    })
                );
            }

            Promise.all(pagesPromises).then(function(pagesTexts) {
                fullText = pagesTexts.join('\n\n');
                document.getElementById('textoArea').value = fullText;
                statusElement.innerText = "✅ Texto extraído com sucesso! Verifique abaixo e clique em Iniciar Auditoria.";
                statusElement.classList.replace("text-primary", "text-success");
            });

        }).catch(function(error) {
            statusElement.innerText = "❌ Erro ao ler o PDF. Por favor, copie e cole o texto manualmente.";
            statusElement.classList.replace("text-primary", "text-danger");
        });
    };
    fileReader.readAsArrayBuffer(file);
});

function mostrarLoading() {
    const btn = document.getElementById('btnSubmit');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Analisando jurisprudência e teses...';
    btn.classList.add('disabled');
}
</script>
</body>
</html>