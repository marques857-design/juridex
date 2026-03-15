<?php
// Arquivo: telas/ia_estrategia.php
// Função: Planejamento estratégico usando a API nativa do sistema (gemini.php).

session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { 
    header("Location: login.php"); 
    exit; 
}

// 1. CHAMA O SEU ARQUIVO DE IA NATIVO
require_once '../ia/gemini.php';

$planoUsuario = $_SESSION['plano'] ?? 'Básico (R$ 50)';
$podeUsarIA = (strpos($planoUsuario, '100') !== false || strpos($planoUsuario, '300') !== false || strpos($planoUsuario, '299') !== false || strpos($planoUsuario, '499') !== false);
if (!$podeUsarIA) { header("Location: central_ia.php"); exit; }

$respostaIA = "";
$relatoDoCliente = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['relato_cliente'])) {
    
    $relatoDoCliente = trim($_POST['relato_cliente']);
    
    // =========================================================================
    // O SUPER PROMPT (Nível Advogado Sênior / Juiz)
    // =========================================================================
    $instrucaoDoSistema = "Aja como um Advogado Estrategista Sênior, Doutrinador e ex-Juiz de Direito no Brasil. ";
    $instrucaoDoSistema .= "Sua missão é fornecer um 'Caminho das Pedras' exato, profundo e técnico para o advogado que fará a ação com base nos fatos relatados.\n\n";
    $instrucaoDoSistema .= "REGRAS DE FORMATAÇÃO (ESTRITAMENTE OBRIGATÓRIO):\n";
    $instrucaoDoSistema .= "- NÃO use formatação markdown (como ```html ou ```). Responda diretamente com as tags HTML.\n";
    $instrucaoDoSistema .= "- Use APENAS as seguintes tags HTML para formatar sua resposta: <h4>, <p>, <ul>, <li>, <strong>, <br>.\n\n";
    $instrucaoDoSistema .= "Estruture o seu parecer OBRIGATORIAMENTE dividindo nestes 6 pilares exatos:\n\n";
    $instrucaoDoSistema .= "<h4>📌 1. Classificação da Ação e Competência</h4>\n";
    $instrucaoDoSistema .= "<p>(Texto explicando a natureza da ação, rito e juízo competente)</p>\n\n";
    $instrucaoDoSistema .= "<h4>⚖️ 2. Teses Jurídicas, Leis e Jurisprudência Aplicável</h4>\n";
    $instrucaoDoSistema .= "<p>(Seja denso. Cite artigos reais do Código Civil, CDC, CLT, CPC ou Penal, e súmulas do STJ/STF aplicáveis ao caso)</p>\n\n";
    $instrucaoDoSistema .= "<h4>📁 3. Checklist de Provas Essenciais</h4>\n";
    $instrucaoDoSistema .= "<ul><li>(Lista de documentos)</li></ul>\n\n";
    $instrucaoDoSistema .= "<h4>🎯 4. Estrutura de Pedidos Principais</h4>\n";
    $instrucaoDoSistema .= "<ul><li>(Lista dos pedidos que não podem faltar na inicial)</li></ul>\n\n";
    $instrucaoDoSistema .= "<h4>⚠️ 5. Riscos e Teses de Defesa da Parte Contrária</h4>\n";
    $instrucaoDoSistema .= "<p>(Alerte sobre prescrição, decadência, falta de provas ou teses que o réu vai usar)</p>\n\n";
    $instrucaoDoSistema .= "<h4>🚀 6. Próximos Passos (Plano de Ação)</h4>\n";
    $instrucaoDoSistema .= "<ul><li>(O passo a passo imediato do advogado)</li></ul>\n\n";
    $instrucaoDoSistema .= "Use linguagem jurídica de alto nível, técnica, mas estruturada.\n\n";
    $instrucaoDoSistema .= "FATOS RELATADOS PELO CLIENTE:\n" . $relatoDoCliente;

    // 2. CONSULTA A SUA IA
    $respostaBruta = consultarInteligenciaArtificialDoGoogle($instrucaoDoSistema);

    // 3. LIMPEZA DE RESÍDUOS DA IA (Garante que o HTML vai renderizar bonito)
    if (!empty($respostaBruta)) {
        $respostaLimpa = preg_replace('/```html\s*/i', '', $respostaBruta);
        $respostaLimpa = preg_replace('/```\s*/', '', $respostaLimpa);
        $respostaIA = trim($respostaLimpa);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JURIDEX - Estrategista Neural 4.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --azul-fundo: #1c1f3b; --azul-vibrante: #0084ff; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .navbar-custom { background: var(--azul-fundo); border-bottom: 2px solid var(--azul-vibrante); }
        .logo-img { max-height: 40px; object-fit: contain; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); background: #fff; }
        
        .btn-ia { background-color: var(--azul-fundo); color: white; font-weight: bold; border: none; transition: 0.3s; }
        .btn-ia:hover { background-color: #0f1225; color: white; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        
        /* Estilo do Parecer Renderizado em HTML */
        .caixa-resposta { background-color: #ffffff; border-left: 5px solid var(--azul-vibrante); padding: 30px; border-radius: 8px; border-top: 1px solid #eee; border-right: 1px solid #eee; border-bottom: 1px solid #eee; }
        .caixa-resposta h4 { color: var(--azul-fundo); font-weight: 800; font-size: 1.1rem; text-transform: uppercase; margin-top: 25px; margin-bottom: 15px; border-bottom: 1px solid #f0f0f0; padding-bottom: 5px; }
        .caixa-resposta h4:first-child { margin-top: 0; }
        .caixa-resposta p { font-size: 1.05rem; line-height: 1.6; color: #333; text-align: justify; }
        .caixa-resposta ul { margin-bottom: 20px; }
        .caixa-resposta li { margin-bottom: 8px; color: #333; font-size: 1.05rem; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="central_ia.php">
            <img src="../assets/logo.png" alt="JURIDEX" class="logo-img me-2" onerror="this.style.display='none';">
            <span class="fw-bold text-white fs-5">Estratégia de Caso <span class="badge bg-secondary ms-2">PRO</span></span>
        </a>
        <a href="central_ia.php" class="btn btn-outline-light btn-sm border-2 fw-bold">Voltar à Central IA</a>
    </div>
</nav>

<div class="container mb-5" style="max-width: 900px;">
    <div class="card card-custom shadow-sm border-secondary">
        <div class="card-body p-4 p-md-5">
            
            <div class="text-center mb-4">
                <h1 style="font-size: 3rem;">♟️</h1>
                <h3 class="fw-bold text-dark mb-1">Estrategista Jurídico Sênior</h3>
                <p class="text-muted mb-0">Análise técnica com base em legislação e jurisprudência brasileira.</p>
            </div>
            
            <form method="POST" action="ia_estrategia.php" id="formIa" onsubmit="mostrarLoading()">
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Relate os fatos do caso com detalhes (datas, valores, ações):</label>
                    <textarea class="form-control border-2 shadow-sm" name="relato_cliente" rows="5" placeholder="Ex: Cliente comprou um veículo zero km há 2 meses. O carro apresentou problema grave no motor e está na concessionária há mais de 40 dias sem solução. A empresa nega carro reserva e recusa devolver o dinheiro..." required><?php echo htmlspecialchars($relatoDoCliente); ?></textarea>
                </div>
                
                <div class="d-grid">
                    <button type="submit" id="btnSubmit" class="btn btn-ia btn-lg py-3 shadow">
                        ✨ Consultar Inteligência Neural
                    </button>
                </div>
            </form>

            <?php if (!empty($respostaIA)) { ?>
                <hr class="my-5 opacity-25">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold text-dark mb-0">Dossiê Estratégico Gerado:</h4>
                    <span class="badge bg-primary px-3 py-2 shadow-sm">Sucesso</span>
                </div>
                
                <div class="caixa-resposta shadow-sm" id="conteudoEstrategia">
                    <?php echo $respostaIA; ?>
                </div>
                
                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-outline-dark fw-bold px-4" onclick="copiarTexto()">📋 Copiar Parecer</button>
                    <button class="btn btn-success fw-bold px-4" onclick="abrirModalSalvar()">💾 Salvar no Banco de Conhecimento</button>
                </div>
            <?php } ?>

        </div>
    </div>
</div>

<div class="modal fade" id="modalSalvarHistorico" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">📚 Guardar no Banco de Conhecimento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="ia_historico.php">
                <div class="modal-body">
                    <p class="text-muted small mb-3">Dê um título para encontrar esta estratégia facilmente no futuro.</p>
                    <input type="hidden" name="acao" value="salvar">
                    <input type="hidden" name="tipo_ia" value="Estratégia de Caso">
                    <input type="hidden" name="conteudo_ia" id="inputConteudoHidden">
                    
                    <label class="form-label fw-bold">Título da Estratégia</label>
                    <input type="text" name="titulo" class="form-control border-2" placeholder="Ex: Dossiê - Carro Defeituoso (Consumidor)" required>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-bold">💾 Salvar Definitivamente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Efeito de loading ao clicar no botão
function mostrarLoading() {
    const btn = document.getElementById('btnSubmit');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processando Leis e Jurisprudência...';
    btn.classList.add('disabled');
}

// Função de copiar
function copiarTexto() {
    const content = document.getElementById('conteudoEstrategia').innerText;
    navigator.clipboard.writeText(content);
    alert('Parecer copiado com sucesso! Pode colar no seu editor.');
}

// Abre o Modal e envia o HTML para ser salvo no JSON
function abrirModalSalvar() {
    document.getElementById('inputConteudoHidden').value = document.getElementById('conteudoEstrategia').innerHTML;
    var myModal = new bootstrap.Modal(document.getElementById('modalSalvarHistorico'));
    myModal.show();
}
</script>

</body>
</html>