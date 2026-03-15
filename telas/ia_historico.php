<?php
// Arquivo: telas/ia_historico.php
// Função: O Banco de Conhecimento do Escritório (Histórico de IA Salvo Manualmente).

session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }

$idEscritorio = $_SESSION['id_usuario_logado'];
$arqHistorico = '../dados/IA_Historico_' . $idEscritorio . '.json';

// =========================================================================
// AÇÃO 1: SALVAR NOVO ITEM (Vindo do Gerador ou Analisador)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'salvar') {
    $lista = file_exists($arqHistorico) ? json_decode(file_get_contents($arqHistorico), true) : [];
    
    $lista[] = [
        'id' => uniqid(),
        'titulo' => trim($_POST['titulo']),
        'tipo' => $_POST['tipo_ia'], // Ex: 'Petição', 'Análise de Despacho'
        'conteudo' => $_POST['conteudo_ia'],
        'data_salvo' => date('Y-m-d H:i:s')
    ];
    
    file_put_contents($arqHistorico, json_encode($lista, JSON_PRETTY_PRINT));
    header("Location: ia_historico.php?msg=salvo");
    exit;
}

// =========================================================================
// AÇÃO 2: EXCLUIR ITEM DO HISTÓRICO
// =========================================================================
if (isset($_GET['excluir'])) {
    $idExcluir = $_GET['excluir'];
    $lista = file_exists($arqHistorico) ? json_decode(file_get_contents($arqHistorico), true) : [];
    $novaLista = [];
    foreach($lista as $item) { if($item['id'] != $idExcluir) { $novaLista[] = $item; } }
    file_put_contents($arqHistorico, json_encode($novaLista, JSON_PRETTY_PRINT));
    header("Location: ia_historico.php?msg=excluido");
    exit;
}

// Carrega os dados para exibir
$historico = file_exists($arqHistorico) ? json_decode(file_get_contents($arqHistorico), true) : [];
// Ordena do mais recente para o mais antigo
usort($historico, function($a, $b) { return strtotime($b['data_salvo']) - strtotime($a['data_salvo']); });
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - Banco de Conhecimento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --azul-fundo: #1c1f3b; --azul-vibrante: #0084ff; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-custom { background: var(--azul-fundo); padding: 10px 0; border-bottom: 2px solid #0dcaf0; }
        .header-banco { background: linear-gradient(135deg, #000000 0%, #434343 100%); color: white; padding: 40px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 10px 20px rgba(0,0,0,0.1);}
        
        .accordion-button:not(.collapsed) { background-color: #e9f5ff; color: var(--azul-fundo); font-weight: bold; }
        .texto-salvo { background: white; padding: 30px; border: 1px solid #ddd; border-radius: 8px; font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.6; color: #000; max-height: 500px; overflow-y: auto;}
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold text-info" href="central_ia.php">🧠 JURIDEX NEURAL</a>
        <a href="central_ia.php" class="btn btn-outline-light btn-sm fw-bold border-2">⬅ Voltar à Central IA</a>
    </div>
</nav>

<div class="container mb-5" style="max-width: 1000px;">
    
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'salvo') echo "<div class='alert alert-success fw-bold shadow-sm'>✅ Salvo com sucesso no seu Banco de Conhecimento!</div>"; ?>
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'excluido') echo "<div class='alert alert-warning fw-bold shadow-sm'>🗑️ Documento removido do histórico.</div>"; ?>

    <div class="header-banco d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold mb-1">📚 Meu Banco de Conhecimento</h2>
            <p class="mb-0 opacity-75">Sua biblioteca particular. Acesse teses e petições que você optou por salvar.</p>
        </div>
        <div style="font-size: 3rem;">🏛️</div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 p-4">
        <?php if(empty($historico)) { ?>
            <div class="text-center py-5 text-muted">
                <h1 style="font-size: 4rem; opacity: 0.3;">📂</h1>
                <h4 class="fw-bold mt-3">Seu banco está vazio</h4>
                <p>Quando você usar o Gerador de Petições ou o Analisador, clique em "Salvar no Banco de Conhecimento" para guardar as melhores respostas aqui.</p>
            </div>
        <?php } else { ?>
            <div class="accordion" id="accordionHistorico">
                <?php foreach($historico as $index => $item) { 
                    $collapseId = "collapse_" . $item['id'];
                    $headingId = "heading_" . $item['id'];
                    $badgeCor = ($item['tipo'] == 'Petição Gerada') ? 'bg-primary' : 'bg-success';
                ?>
                    <div class="accordion-item mb-3 border rounded">
                        <h2 class="accordion-header" id="<?php echo $headingId; ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>">
                                <div class="d-flex justify-content-between align-items-center w-100 pe-3">
                                    <span class="fw-bold fs-5"><?php echo htmlspecialchars($item['titulo']); ?></span>
                                    <div class="text-end">
                                        <span class="badge <?php echo $badgeCor; ?> me-2"><?php echo htmlspecialchars($item['tipo']); ?></span>
                                        <small class="text-muted fw-normal"><?php echo date('d/m/Y H:i', strtotime($item['data_salvo'])); ?></small>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse" data-bs-parent="#accordionHistorico">
                            <div class="accordion-body bg-light">
                                
                                <div class="d-flex justify-content-end mb-3 gap-2">
                                    <button class="btn btn-sm btn-outline-dark fw-bold shadow-sm" onclick="copiarHistorico('conteudo_<?php echo $item['id']; ?>')">📋 Copiar Conteúdo</button>
                                    <a href="ia_historico.php?excluir=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger fw-bold shadow-sm" onclick="return confirm('Apagar este item definitivamente?');">🗑️ Excluir</a>
                                </div>

                                <div class="texto-salvo shadow-sm" id="conteudo_<?php echo $item['id']; ?>">
                                    <?php echo $item['conteudo']; ?>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function copiarHistorico(elementId) {
    const doc = document.getElementById(elementId);
    const selecao = window.getSelection();
    const range = document.createRange();
    range.selectNodeContents(doc);
    selecao.removeAllRanges();
    selecao.addRange(range);
    
    try {
        document.execCommand('copy');
        alert("Copiado com sucesso!");
    } catch (err) {
        alert("Erro ao tentar copiar.");
    }
    selecao.removeAllRanges();
}
</script>
</body>
</html>