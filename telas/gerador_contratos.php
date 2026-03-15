<?php
// Arquivo: telas/gerador_contratos.php
// Função: Gerar contratos e procurações automaticamente usando os dados do cliente.

session_start();
if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }

require_once '../ia/gemini.php';

$idAdvogado = $_SESSION['id_usuario_logado'];
$cliente = null;
$respostaIA = "";

// 1. Pega o ID do Cliente para carregar os dados dele
if (isset($_GET['cliente_id']) || isset($_POST['cliente_id'])) {
    $idCliente = $_GET['cliente_id'] ?? $_POST['cliente_id'];
    $arquivoClientes = '../dados/Clientes_' . $idAdvogado . '.json';
    
    if (file_exists($arquivoClientes)) {
        $lista = json_decode(file_get_contents($arquivoClientes), true) ?? [];
        foreach ($lista as $c) {
            if ($c['id'] == $idCliente) { $cliente = $c; break; }
        }
    }
}

// Se não achar o cliente, volta pra lista
if (!$cliente) {
    echo "<script>alert('Selecione um cliente primeiro!'); window.location.href='lista_clientes.php';</script>"; exit;
}

// 2. Se o formulário for enviado, pede para a IA gerar o documento
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tipoDocumento = $_POST['tipo_documento'];
    $detalhesAdicionais = $_POST['detalhes_adicionais'];

    // Monta a ficha de qualificação do cliente para a IA
    $qualificacao = "NOME: " . ($cliente['nome'] ?? '') . "\n";
    $qualificacao .= "CPF/CNPJ: " . ($cliente['cpf_cnpj'] ?? '') . "\n";
    $qualificacao .= "RG: " . ($cliente['rg'] ?? '') . "\n";
    $qualificacao .= "ESTADO CIVIL: " . ($cliente['estado_civil'] ?? 'Não informado') . "\n";
    $qualificacao .= "PROFISSÃO: " . ($cliente['profissao'] ?? 'Não informada') . "\n";
    $qualificacao .= "ENDEREÇO: " . ($cliente['rua'] ?? '') . ", " . ($cliente['numero'] ?? '') . " - " . ($cliente['bairro'] ?? '') . ", " . ($cliente['cidade'] ?? '') . "-" . ($cliente['estado'] ?? '') . " CEP: " . ($cliente['cep'] ?? '') . "\n";

    // O Super Prompt do Gerador de Contratos
    $instrucao = "Você é um advogado especialista em contratos e direito processual civil brasileiro. ";
    $instrucao .= "Redija o seguinte documento jurídico: " . $tipoDocumento . ".\n\n";
    $instrucao .= "REGRAS:\n";
    $instrucao .= "1. NÃO use formatação markdown (sem asteriscos ou hashtags).\n";
    $instrucao .= "2. Utilize EXATAMENTE os dados do cliente abaixo para preencher a qualificação do OUTORGANTE/CONTRATANTE.\n";
    $instrucao .= "3. Deixe os dados do Advogado (Outorgado/Contratado) em branco com linhas (________) para serem preenchidos depois.\n";
    $instrucao .= "4. Detalhes adicionais solicitados pelo advogado: " . $detalhesAdicionais . "\n\n";
    $instrucao .= "DADOS DO CLIENTE PARA PREENCHIMENTO:\n" . $qualificacao;

    // Chama a Inteligência
    $respostaIA = consultarInteligenciaArtificialDoGoogle($instrucao);

    // Salva no Histórico de IA
    if (!empty($respostaIA) && strpos($respostaIA, 'Erro') === false) {
        $arquivoHistorico = '../dados/Historico_IA.json';
        $historico = file_exists($arquivoHistorico) ? json_decode(file_get_contents($arquivoHistorico), true) : [];
        if (!is_array($historico)) $historico = [];
        
        $historico[] = [
            "id_consulta" => uniqid(),
            "id_advogado" => $idAdvogado,
            "ferramenta" => "Gerador de Contratos",
            "pergunta" => "Documento: $tipoDocumento | Cliente: {$cliente['nome']}",
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
    <title>JURIDEX - Gerador de Contratos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .ai-header { background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%); color: white; }
        .caixa-resposta { background-color: #fff; border: 1px solid #ccc; padding: 40px; font-family: "Times New Roman", Times, serif; font-size: 16px; line-height: 1.6; white-space: pre-wrap;}
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-white" href="painel.php">JURIDEX</a>
        <a href="perfil_cliente.php?id=<?php echo $cliente['id']; ?>&aba=ia" class="btn btn-outline-light btn-sm">Voltar ao Perfil do Cliente</a>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="card shadow border-dark">
        <div class="card-header ai-header py-3 text-center">
            <h4 class="fw-bold mb-0">📜 Gerador Automático de Documentos</h4>
            <p class="mt-1 mb-0 small">A IA redige o documento já preenchido com os dados do cliente.</p>
        </div>
        <div class="card-body p-4">
            
            <div class="alert alert-info border-info shadow-sm mb-4">
                <strong>Cliente Selecionado:</strong> <?php echo htmlspecialchars($cliente['nome']); ?> (CPF/CNPJ: <?php echo htmlspecialchars($cliente['cpf_cnpj']); ?>)
            </div>

            <form method="POST" action="gerador_contratos.php">
                <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">O que você deseja gerar?</label>
                    <select class="form-select border-dark" name="tipo_documento" required>
                        <option value="Procuração Ad Judicia et Extra">Procuração Ad Judicia et Extra (Poderes Gerais para o Foro)</option>
                        <option value="Contrato de Prestação de Serviços Jurídicos e Honorários Advocatícios">Contrato de Honorários Advocatícios</option>
                        <option value="Declaração de Hipossuficiência (Justiça Gratuita)">Declaração de Pobreza (Justiça Gratuita)</option>
                        <option value="Termo de Acordo Extrajudicial">Termo de Acordo Extrajudicial</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Detalhes adicionais (Opcional)</label>
                    <textarea class="form-control border-dark shadow-sm" name="detalhes_adicionais" rows="2" placeholder="Ex: Adicionar poderes específicos para receber citação, ou especificar honorários de 30% no êxito..."></textarea>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-dark btn-lg fw-bold shadow-sm">
                        ✨ Redigir Documento Oficial
                    </button>
                </div>
            </form>

            <?php if (!empty($respostaIA)) { ?>
                <hr class="my-5">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-file-earmark-text"></i> Documento Gerado:</h5>
                    <div>
                        <button class="btn btn-primary fw-bold me-2 shadow-sm" onclick="ExportarParaWord('conteudo_documento', 'Documento_<?php echo preg_replace('/[^A-Za-z0-9\-]/', '', $cliente['nome']); ?>')">
                            📄 Baixar para o Word (.doc)
                        </button>
                        <span class="badge bg-success">Salvo no Histórico</span>
                    </div>
                </div>

                <div id="conteudo_documento" class="caixa-resposta shadow-sm rounded">
                    <?php echo htmlspecialchars($respostaIA); ?>
                </div>
                
            <?php } ?>

        </div>
    </div>
</div>

<script>
function ExportarParaWord(elementId, filename = ''){
    var preHtml = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'><head><meta charset='utf-8'><title>Documento JURIDEX</title><style>body { font-family: 'Times New Roman', serif; font-size: 12pt; }</style></head><body>";
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