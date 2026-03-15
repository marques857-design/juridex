<?php
// Arquivo: telas/ferramenta_mesclar_pdf.php
// Função: Juntar dezenas de PDFs em um único arquivo de forma cronológica no navegador.

session_start();
if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - Mesclador de PDFs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js"></script>
    
    <style>
        .header-ferramenta { background: linear-gradient(135deg, #e52d27 0%, #b31217 100%); color: white; }
        .drop-zone { border: 3px dashed #b31217; border-radius: 12px; padding: 40px; text-align: center; background-color: #fdf2f2; cursor: pointer; transition: 0.3s; }
        .drop-zone:hover { background-color: #fadada; }
        .file-list { max-height: 400px; overflow-y: auto; }
        .file-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #ddd; background: white; }
        .file-item:nth-child(odd) { background: #f8f9fa; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-white" href="painel.php">JURIDEX</a>
        <a href="lista_processos.php" class="btn btn-outline-light btn-sm">Voltar para Processos</a>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="card shadow border-danger">
        <div class="card-header header-ferramenta py-3 text-center">
            <h4 class="fw-bold mb-0">📕 Organizador e Mesclador de PDFs</h4>
            <p class="mt-1 mb-0 small">Junte dezenas de contracheques, recibos e documentos em um único arquivo PDF para protocolar no Tribunal.</p>
        </div>
        <div class="card-body p-4">
            
            <div class="row">
                <div class="col-md-5">
                    <h5 class="fw-bold text-danger mb-3">1. Selecione os PDFs</h5>
                    <div class="drop-zone" onclick="document.getElementById('fileInput').click();">
                        <h1 class="mb-3 text-danger">📥</h1>
                        <h5 class="fw-bold text-dark">Clique aqui para selecionar vários PDFs</h5>
                        <small class="text-muted">Você pode selecionar 50 arquivos de uma vez segurando o CTRL.</small>
                        <input type="file" id="fileInput" accept=".pdf" multiple style="display: none;">
                    </div>
                    
                    <div class="mt-4">
                        <button class="btn btn-outline-secondary w-100 fw-bold mb-2" onclick="ordenarPorNome()">🔤 Ordenar arquivos por Nome/Data</button>
                        <button class="btn btn-danger w-100 fw-bold btn-lg shadow" id="btnMesclar" onclick="mesclarPDFs()" disabled>
                            ✨ JUNTAR TUDO EM UM ÚNICO PDF
                        </button>
                    </div>
                </div>
                
                <div class="col-md-7">
                    <h5 class="fw-bold text-dark mb-3">2. Ordem dos Documentos (<span id="contador">0</span> arquivos)</h5>
                    <div class="alert alert-info py-2 small">A ordem abaixo é a ordem exata em que as páginas vão aparecer no arquivo final.</div>
                    
                    <div class="border rounded shadow-sm file-list" id="listaArquivos">
                        <div class="p-4 text-center text-muted" id="placeholderLista">
                            Os arquivos selecionados aparecerão aqui...
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center" id="areaDownload" style="display: none;">
                <hr>
                <h4 class="fw-bold text-success mb-3">✅ Arquivo Mesclado com Sucesso!</h4>
                <a id="linkDownload" class="btn btn-success btn-lg fw-bold px-5 shadow" download="Documentos_Mesclados_JURIDEX.pdf">
                    📥 BAIXAR PDF FINAL UNIFICADO
                </a>
                <p class="text-muted small mt-2">Pronto para ser anexado na Ficha do Processo ou protocolado no PJe.</p>
            </div>

        </div>
    </div>
</div>

<script>
let arquivosSelecionados = [];

// Quando o usuário escolhe os arquivos
document.getElementById('fileInput').addEventListener('change', function(e) {
    let novosArquivos = Array.from(e.target.files);
    
    // Adiciona à lista global
    for(let file of novosArquivos) {
        if(file.type === "application/pdf") {
            arquivosSelecionados.push(file);
        }
    }
    
    atualizarListaNaTela();
});

// Atualiza o visual da lista
function atualizarListaNaTela() {
    const lista = document.getElementById('listaArquivos');
    document.getElementById('contador').innerText = arquivosSelecionados.length;
    
    if(arquivosSelecionados.length === 0) {
        lista.innerHTML = '<div class="p-4 text-center text-muted">Os arquivos selecionados aparecerão aqui...</div>';
        document.getElementById('btnMesclar').disabled = true;
        return;
    }

    document.getElementById('btnMesclar').disabled = false;
    lista.innerHTML = '';
    
    arquivosSelecionados.forEach((arquivo, index) => {
        let div = document.createElement('div');
        div.className = 'file-item';
        div.innerHTML = `
            <div class="text-truncate" style="max-width: 80%;"><strong class="text-danger">PDF</strong> | ${arquivo.name}</div>
            <button class="btn btn-sm btn-outline-danger" onclick="removerArquivo(${index})">❌</button>
        `;
        lista.appendChild(div);
    });
}

// Remove um arquivo que o usuário colocou sem querer
function removerArquivo(index) {
    arquivosSelecionados.splice(index, 1);
    atualizarListaNaTela();
}

// Ordena os arquivos pelo nome (útil se o cliente mandar "01-holerite.pdf", "02-holerite.pdf")
function ordenarPorNome() {
    arquivosSelecionados.sort((a, b) => a.name.localeCompare(b.name));
    atualizarListaNaTela();
}

// A MÁGICA: Juntar todos os PDFs usando o processador do navegador!
async function mesclarPDFs() {
    const btn = document.getElementById('btnMesclar');
    btn.innerHTML = '⏳ Processando... Por favor, aguarde.';
    btn.disabled = true;

    try {
        const { PDFDocument } = PDFLib;
        // Cria um documento em branco
        const pdfFinal = await PDFDocument.create();

        // Loop por todos os arquivos selecionados
        for (let i = 0; i < arquivosSelecionados.length; i++) {
            let arquivo = arquivosSelecionados[i];
            
            // Transforma o arquivo em dados brutos
            let arrayBuffer = await arquivo.arrayBuffer();
            
            // Carrega o PDF individual
            let pdfIndividual = await PDFDocument.load(arrayBuffer);
            
            // Copia todas as páginas do PDF individual
            let paginasCopiadas = await pdfFinal.copyPages(pdfIndividual, pdfIndividual.getPageIndices());
            
            // Cola as páginas no PDF Final
            paginasCopiadas.forEach((pagina) => {
                pdfFinal.addPage(pagina);
            });
        }

        // Salva o PDF final em memória
        const pdfBytes = await pdfFinal.save();
        
        // Cria o link de download mágico
        const blob = new Blob([pdfBytes], { type: 'application/pdf' });
        const url = URL.createObjectURL(blob);
        
        const link = document.getElementById('linkDownload');
        link.href = url;
        
        document.getElementById('areaDownload').style.display = 'block';
        btn.innerHTML = '✨ JUNTAR TUDO EM UM ÚNICO PDF';
        btn.disabled = false;
        
        alert("Sucesso! Os PDFs foram unidos. Clique no botão verde para baixar.");

    } catch (erro) {
        console.error(erro);
        alert("Ocorreu um erro ao tentar juntar os arquivos. Verifique se algum PDF está corrompido ou com senha.");
        btn.innerHTML = '✨ JUNTAR TUDO EM UM ÚNICO PDF';
        btn.disabled = false;
    }
}
</script>

</body>
</html>