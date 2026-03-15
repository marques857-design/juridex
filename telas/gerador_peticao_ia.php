<?php
// Arquivo: telas/gerador_peticao_ia.php
// Função: Ferramenta IA para redigir peças com cadastro de advogados assinantes.

session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

// SEGURANÇA BÁSICA
if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }

$idEscritorio = $_SESSION['id_usuario_logado']; 

// TRAVA DO PLANO
$planoUsuario = $_SESSION['plano'] ?? 'Básico (R$ 50)';
$podeUsarIA = (strpos($planoUsuario, '100') !== false || strpos($planoUsuario, '300') !== false || strpos($planoUsuario, '299') !== false || strpos($planoUsuario, '499') !== false);
if (!$podeUsarIA) { header("Location: central_ia.php"); exit; }

// =========================================================================
// O CÉREBRO DA IA (SUPER PROMPT AVANÇADO)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'gerar_peticao_ajax') {
    require_once '../ia/gemini.php';

    $advogadoSelect = $_POST['advogado'] ?? '';
    $clienteNome = $_POST['cliente'] ?? '';
    $tipoPeca = $_POST['tipo'] ?? 'Petição Inicial';
    $polo = $_POST['polo'] ?? 'Autor(a)';
    $comarca = $_POST['comarca'] ?? '[Comarca]';
    $fatos = $_POST['fatos'] ?? '';
    $pedidos = $_POST['pedidos'] ?? '';

    // Separa Advogado e OAB
    $advParts = explode('|', $advogadoSelect);
    $nomeAdv = $advParts[0] ?? $_SESSION['nome_usuario'];
    $oabAdv = $advParts[1] ?? 'OAB Pendente';

    // Monta Qualificação Automática buscando no Banco
    $dadosQualificacao = "<strong>" . strtoupper($clienteNome) . "</strong>, já qualificado(a) nos autos";
    $arqClientes = '../dados/Clientes_' . $idEscritorio . '.json';
    
    if (file_exists($arqClientes) && !empty($clienteNome)) {
        $clientesDB = json_decode(file_get_contents($arqClientes), true) ?? [];
        foreach ($clientesDB as $c) {
            if ($c['nome'] == $clienteNome) {
                $nac = !empty($c['nacionalidade']) ? $c['nacionalidade'] : 'brasileiro(a)';
                $estCivil = !empty($c['estado_civil']) ? $c['estado_civil'] : 'estado civil não informado';
                $prof = !empty($c['profissao']) ? $c['profissao'] : 'profissão não informada';
                $cpf = !empty($c['cpf_cnpj']) ? $c['cpf_cnpj'] : 'CPF não informado';
                $rg = !empty($c['rg']) ? $c['rg'] : 'RG não informado';
                
                $rua = $c['rua'] ?? ''; $num = $c['numero'] ?? ''; $bai = $c['bairro'] ?? ''; $cid = $c['cidade'] ?? ''; $est = $c['estado'] ?? '';
                $endereco = trim("$rua, $num - $bai - $cid/$est", " -/,");
                if(empty($endereco)) $endereco = 'endereço não cadastrado';

                $dadosQualificacao = "<strong>" . strtoupper($c['nome']) . "</strong>, {$nac}, {$estCivil}, {$prof}, inscrito(a) no CPF sob o nº {$cpf} e portador(a) do RG nº {$rg}, residente e domiciliado(a) à {$endereco}";
                break;
            }
        }
    }

    // =========================================================================
    // INÍCIO DA ENGENHARIA DE PROMPT (NÍVEL JURÍDICO DE ELITE)
    // =========================================================================
    $instrucao = "Assuma a persona de um Desembargador aposentado e atual Doutrinador Jurídico de renome no Brasil. A sua redação é culta, profundamente técnica, irrefutável e estruturada com lógica silogística perfeita. Juízes respeitam a sua escrita pela robustez e clareza.\n\n";
    $instrucao .= "A sua missão exclusiva é redigir a íntegra de uma '{$tipoPeca}' com qualidade de tribunal superior, baseada estritamente nos fatos relatados. NÃO escreva introduções ou conclusões sobre si mesmo, devolva APENAS a peça jurídica pronta.\n\n";
    
    $instrucao .= "DIRETRIZES TÉCNICAS INEGOCIÁVEIS:\n";
    $instrucao .= "1. Aprofundamento Jurídico: Não se limite a citar o artigo; explique o raciocínio legal. Se for cível, explore o Código Civil e o CDC com profundidade. Se for trabalhista, aplique a CLT e as Súmulas do TST exatas para o caso.\n";
    $instrucao .= "2. Jurisprudência: É OBRIGATÓRIO citar jurisprudência consolidada (Súmulas do STJ/STF/TST ou entendimentos pacificados) que reforcem a tese.\n";
    $instrucao .= "3. Linguagem: Utilize jargão jurídico adequado e polido, sem ser pedante. Evite frases genéricas e foque no nexo causal.\n\n";

    $instrucao .= "REGRAS ESTRITAS DE FORMATAÇÃO HTML (Retorne APENAS HTML limpo, SEM marcação Markdown `html`):\n";
    $instrucao .= "- Endereçamento (Ao Juízo): <div style='text-align:center; font-weight:bold; text-transform:uppercase; margin-bottom:50px;'>\n";
    $instrucao .= "- Qualificação Inicial: <div style='margin-bottom: 40px; text-indent: 40px; text-align: justify;'>\n";
    $instrucao .= "- Nome da Peça: <div style='text-align: center; font-weight: bold; text-transform: uppercase; margin-bottom: 40px; font-size: 13pt;'>\n";
    $instrucao .= "- Títulos de Seção (Numeração Romana): <div style='font-weight:bold; margin-bottom:15px; margin-top:30px; font-size:12pt; text-transform:uppercase;'>\n";
    $instrucao .= "- Parágrafos de Texto: <div style='text-align:justify; text-indent:40px; margin-bottom:15px; line-height:1.6;'>\n";
    $instrucao .= "- Citações de Jurisprudência/Artigos: <div style='text-align:justify; margin-left: 80px; margin-right: 40px; margin-bottom:15px; font-style: italic; font-size: 11pt;'>\n";
    $instrucao .= "- Lista de Pedidos: <div style='text-align:justify; margin-left: 40px; margin-bottom: 10px;'>\n\n";
    
    $instrucao .= "ESTRUTURA OBRIGATÓRIA DA PEÇA:\n";
    $instrucao .= "1. ENDEREÇAMENTO: EXCELENTÍSSIMO SENHOR DOUTOR JUIZ DE DIREITO DA ___ VARA [Cível/Trabalho/Criminal] DA COMARCA DE " . strtoupper($comarca) . ".\n";
    $instrucao .= "2. QUALIFICAÇÃO: {$dadosQualificacao}, atuando no polo de {$polo}, vem, por meio de seu advogado infra-assinado, propor a presente {$tipoPeca} em face de [NOME DA PARTE CONTRÁRIA E SUA QUALIFICAÇÃO A SER PREENCHIDA].\n";
    $instrucao .= "3. I. DA SÍNTESE FÁTICA: Narre os fatos de forma detalhada, lógica e persuasiva, destacando o dano ou a violação do direito.\n";
    $instrucao .= "4. II. DA FUNDAMENTAÇÃO JURÍDICA: O coração da peça. Crie subtópicos para cada tese (ex: II.1 Do Dano Moral, II.2 Da Inversão do Ônus). Cite a lei seca, aplique a doutrina adequada e referencie súmulas reais. Demonstre cabalmente o nexo de causalidade.\n";
    $instrucao .= "5. III. DOS PEDIDOS E REQUERIMENTOS: Inicie com 'Diante do exposto, requer a Vossa Excelência:'. Liste os pedidos em alíneas (a, b, c), englobando citação, procedência total (considerando os pedidos: {$pedidos}), condenação em custas/honorários de 20%, e deferimento da produção de todas as provas admitidas.\n";
    $instrucao .= "6. FECHAMENTO: Dá-se à causa o valor de R$ [VALOR DA CAUSA, se aplicável]. Termos em que, Pede e aguarda deferimento.<br><br><div style='text-align:center; margin-top:50px;'>" . strtoupper($comarca) . ", " . date('d/m/Y') . ".<br><br><br>_________________________________________________<br><strong>" . strtoupper($nomeAdv) . "</strong><br>" . $oabAdv . "</div>\n\n";
    
    $instrucao .= "FATOS DO CASO RELATADOS PELO ADVOGADO PARA CONSTRUIR A TESE:\n" . $fatos;
    // =========================================================================

    $respostaBruta = consultarInteligenciaArtificialDoGoogle($instrucao);
    if (!empty($respostaBruta)) {
        // Limpeza rigorosa para garantir que não vai quebrar o layout
        $respostaLimpa = preg_replace('/```html\s*/i', '', $respostaBruta);
        $respostaLimpa = preg_replace('/```\s*/', '', $respostaLimpa);
        // Remove asteriscos do markdown e substitui por bold html para manter a estética
        $respostaLimpa = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $respostaLimpa);
        $respostaLimpa = preg_replace('/\*(.*?)\*/', '<i>$1</i>', $respostaLimpa);
        echo trim($respostaLimpa);
    } else {
        echo "<div style='color:red; text-align:center; font-weight:bold; margin-top: 50px;'>Erro ao comunicar com a IA. Tente novamente.</div>";
    }
    exit; 
}

$mensagem = "";

// =========================================================================
// MOTOR DE ADVOGADOS ASSINANTES (Adicionar / Excluir)
// =========================================================================
$arqAdvogados = '../dados/Advogados_Assinantes_' . $idEscritorio . '.json';

// CADASTRAR ADVOGADO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'cadastrar_adv') {
    $listaAdv = file_exists($arqAdvogados) ? json_decode(file_get_contents($arqAdvogados), true) : [];
    $listaAdv[] = [
        'id' => uniqid(),
        'nome' => trim($_POST['nome_advogado']),
        'oab' => trim($_POST['oab_advogado'])
    ];
    file_put_contents($arqAdvogados, json_encode($listaAdv, JSON_PRETTY_PRINT));
    header("Location: gerador_peticao_ia.php?msg=adv_salvo");
    exit;
}

// EXCLUIR ADVOGADO
if (isset($_GET['excluir_adv'])) {
    $idExcluir = $_GET['excluir_adv'];
    $listaAdv = file_exists($arqAdvogados) ? json_decode(file_get_contents($arqAdvogados), true) : [];
    $novaLista = [];
    foreach($listaAdv as $adv) { if($adv['id'] != $idExcluir) { $novaLista[] = $adv; } }
    file_put_contents($arqAdvogados, json_encode($novaLista, JSON_PRETTY_PRINT));
    header("Location: gerador_peticao_ia.php?msg=adv_excluido");
    exit;
}

// =========================================================================
// PREPARAR LISTAS PARA OS DROPDOWNS (SELECTS)
// =========================================================================

// 1. LISTA DE ADVOGADOS (Value = Nome|OAB)
$advogadosOptions = "<option value=''>Selecione quem vai assinar a peça...</option>";
$listaAdvogados = file_exists($arqAdvogados) ? json_decode(file_get_contents($arqAdvogados), true) : [];

if (empty($listaAdvogados)) {
    $nomeTitular = $_SESSION['nome_usuario'];
    $advogadosOptions .= "<option value='" . htmlspecialchars($nomeTitular) . "|OAB Pendente'>⚠️ " . htmlspecialchars($nomeTitular) . " (Cadastre a OAB no menu acima)</option>";
} else {
    foreach($listaAdvogados as $adv) {
        $valorOculto = htmlspecialchars($adv['nome']) . '|' . htmlspecialchars($adv['oab']);
        $advogadosOptions .= "<option value='{$valorOculto}'>⚖️ " . htmlspecialchars($adv['nome']) . " (" . htmlspecialchars($adv['oab']) . ")</option>";
    }
}

// 2. LISTA DE CLIENTES (Nome + CPF/CNPJ)
$clientesOptions = "<option value=''>Selecione o Cliente na base de dados...</option>";
$arqClientes = '../dados/Clientes_' . $idEscritorio . '.json';
if (file_exists($arqClientes)) {
    $listaC = json_decode(file_get_contents($arqClientes), true) ?? [];
    usort($listaC, function($a, $b) { return strcmp($a['nome'], $b['nome']); });
    
    foreach($listaC as $c) {
        $documento = !empty($c['cpf_cnpj']) ? $c['cpf_cnpj'] : (!empty($c['cpf']) ? $c['cpf'] : 'Sem documento');
        $nomeDoc = htmlspecialchars($c['nome']) . ' | Doc: ' . htmlspecialchars($documento);
        $clientesOptions .= "<option value='" . htmlspecialchars($c['nome']) . "'>👥 {$nomeDoc}</option>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - Gerador de Petições IA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --azul-fundo: #1c1f3b; --azul-vibrante: #0084ff; --cinza-fundo: #f7f9fc; }
        body { background-color: var(--cinza-fundo); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; height: 100vh; display: flex; flex-direction: column; overflow: hidden; margin: 0;}
        
        .navbar-custom { background: var(--azul-fundo); padding: 12px 20px; border-bottom: 3px solid var(--azul-vibrante); z-index: 1000;}
        .logo-img { max-height: 40px; object-fit: contain; }
        
        .main-container { flex: 1; display: flex; overflow: hidden; }
        
        /* PAINEL ESQUERDO */
        .panel-form { width: 35%; background: white; border-right: 1px solid #e1e5eb; overflow-y: auto; padding: 0; display: flex; flex-direction: column; box-shadow: 5px 0 15px rgba(0,0,0,0.03); z-index: 10;}
        .form-section { padding: 30px; border-bottom: 1px solid #f0f2f5; }
        .form-section-title { font-size: 0.9rem; font-weight: 800; color: #8a94a6; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;}
        
        .form-label { font-weight: 600; color: #334155; font-size: 0.9rem; }
        .form-control, .form-select { border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; font-size: 0.95rem; background-color: #f8fafc; transition: 0.2s;}
        .form-control:focus, .form-select:focus { border-color: var(--azul-vibrante); background-color: white; box-shadow: 0 0 0 3px rgba(0, 132, 255, 0.1); }
        
        .panel-form-footer { padding: 20px 30px; background: white; border-top: 1px solid #e1e5eb; position: sticky; bottom: 0;}
        .btn-gerar { background-color: var(--azul-vibrante); color: white; font-weight: bold; padding: 16px; border-radius: 8px; border: none; transition: 0.3s; width: 100%; font-size: 1.1rem; display: flex; justify-content: center; align-items: center; gap: 10px;}
        .btn-gerar:hover { background-color: #006bce; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,132,255,0.3); }

        /* PAINEL DIREITO */
        .panel-doc { width: 65%; background: #e2e8f0; overflow-y: auto; padding: 50px; display: flex; flex-direction: column; align-items: center; position: relative;}
        
        /* CORREÇÃO DO BUG DO PAPEL: Removido o display: none padrão */
        .doc-paper { background: white; width: 100%; max-width: 850px; min-height: 1100px; padding: 80px 90px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); border-radius: 2px; font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.6; color: #000; margin-bottom: 50px;}
        
        .placeholder-doc { text-align: center; color: #94a3b8; margin-top: 25vh; }
        .spinner-border { color: var(--azul-vibrante); width: 3.5rem; height: 3.5rem; border-width: 4px;}
        .doc-toolbar { width: 100%; max-width: 850px; display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 20px; }
        
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="central_ia.php">
            <img src="../assets/logo.png" alt="JURIDEX" class="logo-img me-3" onerror="this.style.display='none';">
            <span class="fw-bold text-white fs-5" style="letter-spacing: 0.5px;">Gerador de Petições <span class="badge bg-primary ms-2 rounded-pill">PRO</span></span>
        </a>
        <a href="central_ia.php" class="btn btn-outline-light btn-sm fw-bold px-4 py-2" style="border-radius: 6px;">⬅ Voltar à Central</a>
    </div>
</nav>

<div class="main-container">
    
    <div class="panel-form">
        
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'adv_salvo') echo "<div class='alert alert-success m-3 fw-bold py-2'>✅ Advogado cadastrado na base!</div>"; ?>
        
        <div class="form-section bg-white">
            <div class="form-section-title"><span>1</span> Identificação das Partes</div>
            
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-end mb-1">
                    <label class="form-label text-primary mb-0">👨‍⚖️ Advogado Assinante</label>
                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none fw-bold" data-bs-toggle="modal" data-bs-target="#modalAdvogados">➕ Gerenciar Assinaturas</button>
                </div>
                <select id="advogadoAcao" class="form-select border-primary" style="background-color: #f0f7ff;">
                    <?php echo $advogadosOptions; ?>
                </select>
                <small class="text-muted mt-1 d-block">Os dados da OAB sairão no rodapé da peça.</small>
            </div>

            <div class="mb-2">
                <label class="form-label">👥 Cliente Vinculado (Autor ou Réu)</label>
                <select id="clienteAcao" class="form-select">
                    <?php echo $clientesOptions; ?>
                </select>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><span>2</span> Parâmetros do Processo</div>
            
            <div class="mb-3">
                <label class="form-label">Qual o tipo de Ação?</label>
                <input type="text" id="tipoAcao" class="form-control" placeholder="Ex: Ação de Indenização por Danos Morais">
            </div>

            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label">Polo do Cliente</label>
                    <select id="poloAcao" class="form-select">
                        <option value="Autor(a)">Autor / Requerente</option>
                        <option value="Réu/Ré">Réu / Requerido</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">Comarca / Vara</label>
                    <input type="text" id="comarcaAcao" class="form-control" placeholder="Ex: São Paulo/SP">
                </div>
            </div>
        </div>

        <div class="form-section border-0">
            <div class="form-section-title text-primary"><span>3</span> Inteligência Artificial</div>
            
            <div class="mb-4">
                <label class="form-label">Descreva os Fatos (O que aconteceu?)</label>
                <textarea id="fatosAcao" class="form-control" rows="5" placeholder="Escreva como o cliente te contou. A IA vai converter isso para linguagem jurídica técnica e robusta."></textarea>
            </div>

            <div class="mb-2">
                <label class="form-label">Pedidos Principais</label>
                <textarea id="pedidosAcao" class="form-control" rows="3" placeholder="Ex: Condenação em R$ 10.000, justiça gratuita..."></textarea>
            </div>
        </div>

        <div class="panel-form-footer mt-auto">
            <button onclick="gerarPeticaoIA()" class="btn-gerar" id="btnSubmit">
                <span style="font-size: 1.3rem;">✨</span> Redigir Petição Automática
            </button>
        </div>
    </div>

    <div class="panel-doc">
        
        <div id="estadoInicial" class="placeholder-doc">
            <h1 style="font-size: 4.5rem; margin-bottom: 20px; opacity: 0.5;">📄</h1>
            <h3 class="fw-bold" style="color: #64748b;">Nenhuma petição gerada</h3>
            <p class="fs-6">Preencha os dados à esquerda e peça para a IA redigir.<br>O documento aparecerá formatado aqui.</p>
        </div>

        <div id="estadoLoading" class="placeholder-doc d-none">
            <div class="spinner-border mb-4" role="status"></div>
            <h3 class="fw-bold" style="color: var(--azul-vibrante);">O JURIDEX Neural está escrevendo...</h3>
            <p class="fs-6">Buscando fundamentação jurídica, formatando a peça e inserindo dados.</p>
        </div>

        <div id="estadoPronto" class="w-100 d-none flex-column align-items-center">
            <div class="doc-toolbar">
                <button class="btn btn-light fw-bold shadow-sm border text-primary" onclick="exportarWord()">📥 Baixar Word</button>
                <button class="btn btn-light fw-bold shadow-sm border text-danger" onclick="imprimirPDF()">🖨️ Salvar PDF</button>
                <button class="btn btn-light fw-bold shadow-sm border text-dark" onclick="copiarDocumento()">📋 Copiar Tudo</button>
            </div>
            <div class="doc-paper" id="documentoGerado" contenteditable="true" spellcheck="false"></div>
        </div>

    </div>
</div>

<div class="modal fade" id="modalAdvogados" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Gestão de Advogados Assinantes</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                
                <form method="POST" action="gerador_peticao_ia.php" class="card p-3 shadow-sm border-0 mb-4">
                    <h6 class="fw-bold mb-3 text-primary">➕ Novo Advogado</h6>
                    <input type="hidden" name="acao" value="cadastrar_adv">
                    <div class="row g-2">
                        <div class="col-md-7">
                            <input type="text" class="form-control" name="nome_advogado" placeholder="Nome Completo do Advogado" required>
                        </div>
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="oab_advogado" placeholder="Nº da OAB (Ex: OAB/SP 12345)" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary fw-bold mt-3">Salvar Assinatura na Base</button>
                </form>

                <h6 class="fw-bold mb-2">Advogados Cadastrados</h6>
                <div class="table-responsive bg-white border rounded">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            <?php if(empty($listaAdvogados)) { echo "<tr><td class='text-muted p-3'>Nenhum advogado cadastrado.</td></tr>"; } ?>
                            <?php foreach($listaAdvogados as $adv) { ?>
                                <tr>
                                    <td class="fw-bold">⚖️ <?php echo htmlspecialchars($adv['nome']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($adv['oab']); ?></span></td>
                                    <td class="text-end">
                                        <a href="gerador_peticao_ia.php?excluir_adv=<?php echo $adv['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir este advogado?');">Excluir</a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function gerarPeticaoIA() {
    const advogadoSelect = document.getElementById('advogadoAcao').value;
    const cliente = document.getElementById('clienteAcao').value;
    const tipo = document.getElementById('tipoAcao').value;
    const polo = document.getElementById('poloAcao').value;
    const comarca = document.getElementById('comarcaAcao').value;
    const fatos = document.getElementById('fatosAcao').value;
    const pedidos = document.getElementById('pedidosAcao').value;
    
    if(!advogadoSelect || !tipo || !fatos) {
        alert("⚠️ Por favor, selecione o Advogado, informe o Tipo de Ação e os Fatos.");
        return;
    }

    document.getElementById('estadoInicial').classList.add('d-none');
    document.getElementById('estadoPronto').classList.add('d-none');
    document.getElementById('estadoPronto').classList.remove('d-flex');
    document.getElementById('estadoLoading').classList.remove('d-none');

    // Desativa botão e mostra o spinner
    const btn = document.getElementById('btnSubmit');
    const textoBotaoOriginal = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processando Leis...';
    btn.disabled = true;

    // Envia os dados para a IA de forma invisível via AJAX
    let formData = new FormData();
    formData.append('acao', 'gerar_peticao_ajax');
    formData.append('advogado', advogadoSelect);
    formData.append('cliente', cliente);
    formData.append('tipo', tipo);
    formData.append('polo', polo);
    formData.append('comarca', comarca);
    formData.append('fatos', fatos);
    formData.append('pedidos', pedidos);

    fetch('gerador_peticao_ia.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(textoFinalHTML => {
        btn.innerHTML = textoBotaoOriginal;
        btn.disabled = false;

        document.getElementById('estadoLoading').classList.add('d-none');
        document.getElementById('documentoGerado').innerHTML = textoFinalHTML;
        document.getElementById('estadoPronto').classList.remove('d-none');
        document.getElementById('estadoPronto').classList.add('d-flex');
    })
    .catch(error => {
        btn.innerHTML = textoBotaoOriginal;
        btn.disabled = false;
        alert("❌ Erro ao conectar com o JURIDEX Neural. Tente novamente.");
        document.getElementById('estadoLoading').classList.add('d-none');
        document.getElementById('estadoInicial').classList.remove('d-none');
    });
}

function copiarDocumento() {
    const doc = document.getElementById('documentoGerado');
    const selecao = window.getSelection();
    const range = document.createRange();
    
    range.selectNodeContents(doc);
    selecao.removeAllRanges();
    selecao.addRange(range);
    
    try {
        document.execCommand('copy');
        alert("✅ Petição copiada com sucesso! Cole no Word (Ctrl + V).");
    } catch (err) {
        alert("❌ Erro ao tentar copiar.");
    }
    selecao.removeAllRanges();
}

function imprimirPDF() {
    const conteudoOriginal = document.body.innerHTML;
    const conteudoPeca = document.getElementById('documentoGerado').innerHTML;
    
    document.body.innerHTML = "<div style='padding:40px; font-family: \"Times New Roman\", Times, serif; font-size:12pt; line-height:1.6; max-width:210mm; margin:0 auto;'>" + conteudoPeca + "</div>";
    window.print();
    document.body.innerHTML = conteudoOriginal;
    location.reload(); 
}

function exportarWord() {
    const conteudoHtml = document.getElementById('documentoGerado').innerHTML;
    
    const header = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'><head><meta charset='utf-8'><title>Peticao_JURIDEX</title><style>body { font-family: 'Times New Roman'; font-size: 12pt; line-height: 1.5; } p { text-align: justify; text-indent: 1.25cm; }</style></head><body>";
    const footer = "</body></html>";
    const sourceHTML = header + conteudoHtml + footer;
    
    const blob = new Blob(['\ufeff', sourceHTML], { type: 'application/msword' });
    const url = URL.createObjectURL(blob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = 'Peticao_Gerada.doc';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
</body>
</html>