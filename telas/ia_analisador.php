<?php
// Arquivo: telas/ia_analisador.php
session_start();
if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }

require_once '../ia/gemini.php';

$respostaIA = "";
$textoColado = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $textoColado = $_POST['texto_processo'];
    
    // O Super Prompt do Analisador
    $instrucao = "Você é um advogado sênior especialista em análise processual. ";
    $instrucao .= "Analise o documento/texto abaixo e forneça um resumo estratégico para o advogado responsável.\n\n";
    $instrucao .= "REGRAS DE FORMATAÇÃO: NÃO use formatação markdown (sem asteriscos **, sem hashtags #). Use texto puro.\n\n";
    $instrucao .= "ESTRUTURE A RESPOSTA COM OS SEGUINTES TÓPICOS:\n";
    $instrucao .= "1. RESUMO DO DOCUMENTO (O que é isso? Uma petição inicial, uma sentença, um despacho? O que diz?)\n";
    $instrucao .= "2. PONTOS CRÍTICOS / RISCOS (O que o advogado precisa tomar cuidado aqui?)\n";
    $instrucao .= "3. PRÓXIMO PRAZO / AÇÃO IMEDIATA (O que o advogado deve fazer agora?)\n\n";
    $instrucao .= "TEXTO DO DOCUMENTO A SER ANALISADO:\n" . $textoColado;

    // Chama a Inteligência Artificial
    $respostaBruta = consultarInteligenciaArtificialDoGoogle($instrucao);

    // Filtro de limpeza
    if (!empty($respostaBruta)) {
        $respostaIA = str_replace(['**', '*', '####', '###', '##', '#', '`'], '', $respostaBruta);
    }

    // Salva no Histórico
    if (!empty($respostaIA) && strpos($respostaIA, 'Erro') === false) {
        $arquivoHistorico = '../dados/Historico_IA.json';
        $historico = file_exists($arquivoHistorico) ? json_decode(file_get_contents($arquivoHistorico), true) : [];
        if (!is_array($historico)) $historico = [];
        
        $historico[] = [
            "id_consulta" => uniqid(),
            "id_advogado" => $_SESSION['id_usuario_logado'],
            "ferramenta" => "Analisador de Processo",
            "pergunta" => mb_substr($textoColado, 0, 100) . "... [Arquivo Lido]",
            "resposta" => $respostaIA,
            "data_consulta" => date('Y-m-d H:i:s')
        ];
        file_put_contents($arquivoHistorico, json_encode($historico, JSON_PRETTY_PRINT));
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - Analisador de Processos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>
    
    <style>
        .ai-header { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
        .caixa-resposta { background-color: #f8f9fa; border-left: 5px solid #11998e; padding: 30px; font-family: "Segoe UI", Arial, sans-serif; font-size: 15px; white-space: pre-wrap;}
        .drop-zone { border: 2px dashed #11998e; border-radius: 10px; padding: 20px; text-align: center; background-color: #eafaf1; cursor: pointer; transition: 0.3s; }
        .drop-zone:hover { background-color: #d4f5e3; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-info" href="central_ia.php">🧠 JURIDEX NEURAL</a>
        <a href="central_ia.php" class="btn btn-outline-light btn-sm">Voltar para a Central</a>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="card shadow border-success">
        <div class="card-header ai-header py-3 text-center">
            <h4 class="fw-bold mb-0">🔎 Analisador de Documentos e Processos</h4>
            <p class="mt-1 mb-0 small">Anexe um PDF/Word ou cole o texto. A IA vai ler tudo e te dar um resumo com os próximos prazos.</p>
        </div>
        <div class="card-body p-4">
            
            <form method="POST" action="ia_analisador.php">
                
                <div class="mb-4">
                    <label class="form-label fw-bold">1. Importar Arquivo (Opcional - Extrai o texto automaticamente)</label>
                    <div class="drop-zone" onclick="document.getElementById('leitorArquivo').click();">
                        <h1 class="mb-2">📄</h1>
                        <h6 class="text-success fw-bold">Clique aqui para selecionar um arquivo PDF ou Word (.docx)</h6>
                        <small class="text-muted">O sistema vai ler o arquivo e preencher a caixa abaixo sozinho!</small>
                        <input type="file" id="leitorArquivo" accept=".pdf,.docx" style="display: none;">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">2. Texto do Documento (Pode colar direto aqui também)</label>
                    <textarea class="form-control border-success shadow-sm" id="texto_processo" name="texto_processo" rows="6" placeholder="O texto do arquivo aparecerá aqui..." required></textarea>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg fw-bold shadow-sm">
                        🧠 Analisar Documento com Inteligência Artificial
                    </button>
                </div>
            </form>

            <?php if (!empty($respostaIA)) { ?>
                <hr class="my-5">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-success mb-0">✅ Resultado da Análise:</h5>
                    <div>
                        <button class="btn btn-outline-dark fw-bold me-2 shadow-sm" onclick="ExportarParaWord('conteudo_analise', 'Analise_Processual')">
                            📄 Baixar Resumo (.doc)
                        </button>
                        <span class="badge bg-success">Salvo no Histórico</span>
                    </div>
                </div>

                <div id="conteudo_analise" class="caixa-resposta shadow-sm rounded border">
                    <?php echo htmlspecialchars($respostaIA); ?>
                </div>
                
            <?php } ?>

        </div>
    </div>
</div>

<script>
// Configura o PDF.js
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

document.getElementById('leitorArquivo').addEventListener('change', function(e) {
    let file = e.target.files[0];
    if(!file) return;

    let textArea = document.getElementById('texto_processo');
    textArea.value = "Lendo arquivo, aguarde um instante...";

    let reader = new FileReader();

    // SE FOR WORD (.docx)
    if(file.name.endsWith('.docx')) {
        reader.onload = function(event) {
            mammoth.extractRawText({arrayBuffer: event.target.result})
                .then(function(result){
                    textArea.value = result.value;
                }).catch(function(err){
                    textArea.value = "Erro ao ler o Word. Tente colar o texto manualmente.";
                    alert("Erro ao ler DOCX: " + err.message);
                });
        };
        reader.readAsArrayBuffer(file);
    } 
    // SE FOR PDF (.pdf)
    else if(file.name.endsWith('.pdf')) {
        reader.onload = function(event) {
            let typedarray = new Uint8Array(event.target.result);
            pdfjsLib.getDocument(typedarray).promise.then(function(pdf) {
                let maxPages = pdf.numPages;
                // Limita a 15 páginas para não travar o navegador
                if (maxPages > 15) maxPages = 15; 
                
                let promises = [];
                for (let j = 1; j <= maxPages; j++) {
                    let page = pdf.getPage(j).then(function(page) {
                        return page.getTextContent().then(function(text) {
                            return text.items.map(function(s) { return s.str; }).join(' ');
                        });
                    });
                    promises.push(page);
                }
                Promise.all(promises).then(function(texts) {
                    textArea.value = texts.join('\n\n');
                });
            }).catch(function(err){
                textArea.value = "Erro ao ler o PDF. Ele pode estar bloqueado ou ser uma imagem escaneada. Tente colar o texto manualmente.";
            });
        };
        reader.readAsArrayBuffer(file);
    } else {
        alert("Formato não suportado. Por favor, use arquivos PDF ou DOCX.");
        textArea.value = "";
    }
});

// SCRIPT DE EXPORTAÇÃO PARA WORD
function ExportarParaWord(elementId, filename = ''){
    var preHtml = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'><head><meta charset='utf-8'><title>Documento JURIDEX</title><style>body { font-family: 'Arial', sans-serif; font-size: 11pt; }</style></head><body>";
    var postHtml = "</body></html>";
    var textoOriginal = document.getElementById(elementId).innerText;
    var textoComQuebras = textoOriginal.replace(/\n/g, "<br>");
    var html = preHtml + textoComQuebras + postHtml;

    var blob = new Blob(['\ufeff', html], { type: 'application/msword' });
    var url = 'data:application/vnd.ms-word;charset=utf-8,' + encodeURIComponent(html);
    filename = filename?filename+'.doc':'document.doc';
    
    var downloadLink = document.createElement("a");
    document.body.appendChild(downloadLink);
    
    if(navigator.msSaveOrOpenBlob ){
        navigator.msSaveOrOpenBlob(blob, filename);
    }else{
        downloadLink.href = url;
        downloadLink.download = filename;
        downloadLink.click();
    }
    document.body.removeChild(downloadLink);
}
</script>

</body>
</html>