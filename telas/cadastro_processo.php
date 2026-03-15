<?php
// Arquivo: telas/cadastro_processo.php
session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }

$idAdvogado = $_SESSION['id_usuario_logado'];
$mensagem = "";
$processo = null;
$modoEdicao = false;

$arquivoProcessos = '../dados/Processos_' . $idAdvogado . '.json';
$arquivoClientes = '../dados/Clientes_' . $idAdvogado . '.json';

// Carrega os clientes para o select (Vínculo)
$clientes = [];
if (file_exists($arquivoClientes)) {
    $clientes = json_decode(file_get_contents($arquivoClientes), true) ?? [];
}

// Verifica se é edição
if (isset($_GET['id'])) {
    $modoEdicao = true;
    if (file_exists($arquivoProcessos)) {
        $lista = json_decode(file_get_contents($arquivoProcessos), true) ?? [];
        foreach ($lista as $p) {
            if ($p['id'] == $_GET['id']) { $processo = $p; break; }
        }
    }
}

// Salvar ou Atualizar
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lista = file_exists($arquivoProcessos) ? json_decode(file_get_contents($arquivoProcessos), true) ?? [] : [];

    $dadosProcesso = [
        "id" => $_POST['id_processo'] ?: uniqid(),
        "id_advogado_responsavel" => $idAdvogado,
        "cliente_id" => $_POST['cliente_id'],
        "numero_processo" => $_POST['numero_processo'],
        "parte_contraria" => $_POST['parte_contraria'],
        "tipo_acao" => $_POST['tipo_acao'], // Classe Processual
        "area_direito" => $_POST['area_direito'],
        "tribunal" => $_POST['tribunal'],
        "vara_comarca" => $_POST['vara_comarca'],
        "estado" => $_POST['estado'],
        "instancia" => $_POST['instancia'],
        "fase_processual" => $_POST['fase_processual'],
        "data_abertura" => $_POST['data_abertura'],
        "valor_causa" => $_POST['valor_causa'],
        "status" => $_POST['status'],
        "prioridade" => $_POST['prioridade'],
        "observacoes" => $_POST['observacoes'],
        "data_cadastro" => $_POST['data_cadastro'] ?: date('Y-m-d H:i:s')
    ];

    if ($modoEdicao || !empty($_POST['id_processo'])) {
        foreach ($lista as $key => $p) {
            if ($p['id'] == $dadosProcesso['id']) { $lista[$key] = $dadosProcesso; break; }
        }
        $mensagem = "✅ Processo atualizado com sucesso!";
        $processo = $dadosProcesso;
    } else {
        $lista[] = $dadosProcesso;
        $mensagem = "✅ Novo processo cadastrado com sucesso!";
    }
    file_put_contents($arquivoProcessos, json_encode($lista, JSON_PRETTY_PRINT));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - Cadastro de Processo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-white" href="painel.php">JURIDEX</a>
        <a href="lista_processos.php" class="btn btn-outline-light btn-sm">Voltar para a Central</a>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <h3 class="fw-bold text-dark mb-4"><?php echo $modoEdicao ? '✏️ Editar Processo' : '⚖️ Cadastrar Novo Processo'; ?></h3>
    <?php if ($mensagem) echo "<div class='alert alert-success fw-bold'>$mensagem</div>"; ?>

    <form method="POST" action="cadastro_processo.php" class="bg-white p-4 shadow-sm rounded border border-primary border-top-0 border-end-0 border-bottom-0" style="border-left-width: 5px;">
        <input type="hidden" name="id_processo" value="<?php echo $processo['id'] ?? ''; ?>">
        <input type="hidden" name="data_cadastro" value="<?php echo $processo['data_cadastro'] ?? ''; ?>">

        <h5 class="fw-bold text-primary border-bottom pb-2 mb-3">1. Vínculo e Partes</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label fw-bold">Cliente Vinculado (Nosso Cliente)</label>
                <select class="form-select border-primary bg-light fw-bold" name="cliente_id" required>
                    <option value="">Selecione o cliente na base...</option>
                    <?php 
                    foreach ($clientes as $c) {
                        $selecionado = (isset($processo['cliente_id']) && $processo['cliente_id'] == $c['id']) ? 'selected' : '';
                        // Se o usuário veio da Ficha do Cliente com o ID na URL
                        if(isset($_GET['cliente_id']) && $_GET['cliente_id'] == $c['id']) $selecionado = 'selected';
                        echo "<option value='{$c['id']}' {$selecionado}>" . htmlspecialchars($c['nome']) . " (" . htmlspecialchars($c['cpf_cnpj']) . ")</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Parte Contrária (Adverso)</label>
                <input type="text" class="form-control" name="parte_contraria" placeholder="Ex: Banco do Brasil S/A" required value="<?php echo htmlspecialchars($processo['parte_contraria'] ?? ''); ?>">
            </div>
        </div>

        <h5 class="fw-bold text-primary border-bottom pb-2 mb-3">2. Identificação do Processo</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label fw-bold">Número do Processo (CNJ)</label>
                <input type="text" class="form-control fw-bold" name="numero_processo" placeholder="0000000-00.0000.0.00.0000" required value="<?php echo htmlspecialchars($processo['numero_processo'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Classe Processual / Ação</label>
                <input type="text" class="form-control" name="tipo_acao" placeholder="Ex: Ação Indenizatória por Danos Morais" required value="<?php echo htmlspecialchars($processo['tipo_acao'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Área do Direito</label>
                <select class="form-select" name="area_direito">
                    <option value="Cível" <?php echo (isset($processo['area_direito']) && $processo['area_direito'] == 'Cível') ? 'selected' : ''; ?>>Cível</option>
                    <option value="Trabalhista" <?php echo (isset($processo['area_direito']) && $processo['area_direito'] == 'Trabalhista') ? 'selected' : ''; ?>>Trabalhista</option>
                    <option value="Previdenciário" <?php echo (isset($processo['area_direito']) && $processo['area_direito'] == 'Previdenciário') ? 'selected' : ''; ?>>Previdenciário</option>
                    <option value="Penal" <?php echo (isset($processo['area_direito']) && $processo['area_direito'] == 'Penal') ? 'selected' : ''; ?>>Penal</option>
                    <option value="Tributário" <?php echo (isset($processo['area_direito']) && $processo['area_direito'] == 'Tributário') ? 'selected' : ''; ?>>Tributário</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Tribunal</label>
                <input type="text" class="form-control" name="tribunal" placeholder="Ex: TJSP, TRT2, TRF3" value="<?php echo htmlspecialchars($processo['tribunal'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Vara / Comarca</label>
                <input type="text" class="form-control" name="vara_comarca" placeholder="Ex: 2ª Vara Cível de São Paulo" value="<?php echo htmlspecialchars($processo['vara_comarca'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Estado (UF)</label>
                <input type="text" class="form-control" name="estado" placeholder="Ex: SP" value="<?php echo htmlspecialchars($processo['estado'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Valor da Causa (R$)</label>
                <input type="number" step="0.01" class="form-control" name="valor_causa" placeholder="0.00" value="<?php echo htmlspecialchars($processo['valor_causa'] ?? ''); ?>">
            </div>
        </div>

        <h5 class="fw-bold text-primary border-bottom pb-2 mb-3">3. Tramitação e Status</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label fw-bold">Status do Processo</label>
                <select class="form-select border-primary" name="status">
                    <option value="Ativo" <?php echo (isset($processo['status']) && $processo['status'] == 'Ativo') ? 'selected' : ''; ?>>🟢 Ativo (Em andamento)</option>
                    <option value="Suspenso" <?php echo (isset($processo['status']) && $processo['status'] == 'Suspenso') ? 'selected' : ''; ?>>🟡 Suspenso / Sobrestado</option>
                    <option value="Encerrado" <?php echo (isset($processo['status']) && $processo['status'] == 'Encerrado') ? 'selected' : ''; ?>>⚫ Encerrado / Trânsito</option>
                    <option value="Arquivado" <?php echo (isset($processo['status']) && $processo['status'] == 'Arquivado') ? 'selected' : ''; ?>>📁 Arquivado</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Instância</label>
                <select class="form-select" name="instancia">
                    <option value="1ª Instância" <?php echo (isset($processo['instancia']) && $processo['instancia'] == '1ª Instância') ? 'selected' : ''; ?>>1ª Instância</option>
                    <option value="2ª Instância" <?php echo (isset($processo['instancia']) && $processo['instancia'] == '2ª Instância') ? 'selected' : ''; ?>>2ª Instância (Recurso)</option>
                    <option value="Instância Superior" <?php echo (isset($processo['instancia']) && $processo['instancia'] == 'Instância Superior') ? 'selected' : ''; ?>>Tribunal Superior (STJ/STF)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Fase Processual</label>
                <input type="text" class="form-control" name="fase_processual" placeholder="Ex: Inicial, Instrução, Execução..." value="<?php echo htmlspecialchars($processo['fase_processual'] ?? 'Inicial'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Prioridade</label>
                <select class="form-select" name="prioridade">
                    <option value="Normal" <?php echo (isset($processo['prioridade']) && $processo['prioridade'] == 'Normal') ? 'selected' : ''; ?>>Normal</option>
                    <option value="Urgente / Liminar" <?php echo (isset($processo['prioridade']) && $processo['prioridade'] == 'Urgente / Liminar') ? 'selected' : ''; ?>>🚨 Urgente / Liminar</option>
                    <option value="Idoso / Doença" <?php echo (isset($processo['prioridade']) && $processo['prioridade'] == 'Idoso / Doença') ? 'selected' : ''; ?>>👴 Prioridade Legal (Idoso)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Data de Abertura / Distribuição</label>
                <input type="date" class="form-control" name="data_abertura" value="<?php echo htmlspecialchars($processo['data_abertura'] ?? date('Y-m-d')); ?>">
            </div>
            <div class="col-md-9">
                <label class="form-label fw-bold">Observações / Resumo Estratégico</label>
                <textarea class="form-control" name="observacoes" rows="2" placeholder="Ex: Processo com risco de revelia. Precisamos da testemunha chave."><?php echo htmlspecialchars($processo['observacoes'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="text-end mt-4">
            <a href="lista_processos.php" class="btn btn-secondary fw-bold px-4">Cancelar</a>
            <button type="submit" class="btn btn-primary fw-bold px-5"><?php echo $modoEdicao ? '💾 Salvar Alterações' : '✅ Cadastrar Processo'; ?></button>
        </div>
    </form>
</div>
</body>
</html>