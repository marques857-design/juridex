<?php
// Arquivo: telas/cadastro_financeiro.php
session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }

$idAdvogado = $_SESSION['id_usuario_logado'];
$mensagem = "";
$lancamento = null;
$modoEdicao = false;

$arquivoFinanceiro = '../dados/Financeiro_' . $idAdvogado . '.json';

$clientes = [];
if (file_exists('../dados/Clientes_' . $idAdvogado . '.json')) {
    $clientes = json_decode(file_get_contents('../dados/Clientes_' . $idAdvogado . '.json'), true) ?? [];
}

$processos = [];
if (file_exists('../dados/Processos_' . $idAdvogado . '.json')) {
    $processos = json_decode(file_get_contents('../dados/Processos_' . $idAdvogado . '.json'), true) ?? [];
}

if (isset($_GET['id'])) {
    $modoEdicao = true;
    if (file_exists($arquivoFinanceiro)) {
        $lista = json_decode(file_get_contents($arquivoFinanceiro), true) ?? [];
        foreach ($lista as $f) {
            if (isset($f['id_lancamento']) && $f['id_lancamento'] == $_GET['id']) { $lancamento = $f; break; }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lista = file_exists($arquivoFinanceiro) ? json_decode(file_get_contents($arquivoFinanceiro), true) ?? [] : [];

    $id_lancamento = $_POST['id_lancamento'] ?? '';
    $qtd_parcelas = isset($_POST['parcelas']) && !empty($_POST['parcelas']) ? (int)$_POST['parcelas'] : 1;
    $valor_total = (float)$_POST['valor'];
    
    if ($modoEdicao || !empty($id_lancamento)) {
        $dadosLancamento = [
            "id_lancamento" => $id_lancamento,
            "id_advogado_responsavel" => $idAdvogado,
            "tipo" => $_POST['tipo'], 
            "categoria" => $_POST['categoria'],
            "descricao" => $_POST['descricao'],
            "valor" => $valor_total,
            "data_vencimento" => $_POST['data_vencimento'],
            "status" => $_POST['status'], 
            "cliente_id" => $_POST['cliente_id'],
            "processo_id" => $_POST['processo_id'],
            "data_registro" => date('Y-m-d H:i:s'),
            "comprovante" => $lancamento['comprovante'] ?? ''
        ];
        
        foreach ($lista as $key => $f) {
            if ($f['id_lancamento'] == $id_lancamento) { $lista[$key] = $dadosLancamento; break; }
        }
        $mensagem = "✅ Lançamento atualizado com sucesso!";
        $lancamento = $dadosLancamento;
        
    } else {
        $valor_parcela = $valor_total / $qtd_parcelas;
        $data_base = new DateTime($_POST['data_vencimento']);
        
        for ($i = 1; $i <= $qtd_parcelas; $i++) {
            $desc_parcela = $_POST['descricao'];
            if ($qtd_parcelas > 1) { $desc_parcela .= " (Parcela $i de $qtd_parcelas)"; }
            
            $lista[] = [
                "id_lancamento" => uniqid() . "_p" . $i,
                "id_advogado_responsavel" => $idAdvogado,
                "tipo" => $_POST['tipo'],
                "categoria" => $_POST['categoria'],
                "descricao" => $desc_parcela,
                "valor" => round($valor_parcela, 2),
                "data_vencimento" => $data_base->format('Y-m-d'),
                "status" => $_POST['status'],
                "cliente_id" => $_POST['cliente_id'],
                "processo_id" => $_POST['processo_id'],
                "data_registro" => date('Y-m-d H:i:s')
            ];
            $data_base->modify('+1 month');
        }
        $mensagem = $qtd_parcelas > 1 ? "✅ $qtd_parcelas parcelas geradas com sucesso!" : "✅ Lançamento registado com sucesso!";
    }
    
    file_put_contents($arquivoFinanceiro, json_encode($lista, JSON_PRETTY_PRINT), LOCK_EX);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JURIDEX - Lançamento Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .main-content { margin-left: 280px; min-height: 100vh; display: flex; flex-direction: column; transition: margin-left 0.3s ease; }
        .topbar { background: #fff; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; }
        
        .form-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: none; }
        
        @media (max-width: 991px) { .main-content { margin-left: 0; width: 100%; } }
    </style>
    <script>
        function mudarCorTipo() {
            var tipo = document.getElementById("tipo").value;
            var box = document.getElementById("formBox");
            if(tipo === 'Receita') { box.style.borderLeft = "5px solid #198754"; } 
            else { box.style.borderLeft = "5px solid #dc3545"; }
        }
    </script>
</head>
<body onload="mudarCorTipo()">

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h4 class="mb-0 fw-bold text-dark"><?php echo $modoEdicao ? 'Editar Lançamento' : 'Novo Lançamento Financeiro'; ?></h4>
        <a href="lista_financeiro.php" class="btn btn-outline-dark btn-sm fw-bold">Voltar ao Caixa</a>
    </div>

    <div class="container px-4 mb-5" style="max-width: 800px; margin: 0 auto;">
        
        <?php if ($mensagem) echo "<div class='alert alert-success fw-bold shadow-sm'>$mensagem</div>"; ?>

        <div class="card form-card" id="formBox" style="border-left: 5px solid #198754;">
            <div class="card-body p-4 p-md-5">
                <form method="POST" action="cadastro_financeiro.php">
                    <input type="hidden" name="id_lancamento" value="<?php echo $lancamento['id_lancamento'] ?? ''; ?>">

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tipo de Movimentação</label>
                            <select class="form-select fw-bold form-control-lg" id="tipo" name="tipo" onchange="mudarCorTipo()" required>
                                <option value="Receita" class="text-success" <?php echo (isset($lancamento['tipo']) && $lancamento['tipo'] == 'Receita') ? 'selected' : ''; ?>>🟢 RECEITA (Entrada)</option>
                                <option value="Despesa" class="text-danger" <?php echo (isset($lancamento['tipo']) && $lancamento['tipo'] == 'Despesa') ? 'selected' : ''; ?>>🔴 DESPESA (Saída)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Categoria</label>
                            <select class="form-select form-control-lg" name="categoria" required>
                                <optgroup label="Entradas">
                                    <option value="Honorários Iniciais" <?php echo (isset($lancamento['categoria']) && $lancamento['categoria'] == 'Honorários Iniciais') ? 'selected' : ''; ?>>Honorários Iniciais</option>
                                    <option value="Honorários Sucumbenciais" <?php echo (isset($lancamento['categoria']) && $lancamento['categoria'] == 'Honorários Sucumbenciais') ? 'selected' : ''; ?>>Honorários Sucumbenciais</option>
                                    <option value="Consultoria" <?php echo (isset($lancamento['categoria']) && $lancamento['categoria'] == 'Consultoria') ? 'selected' : ''; ?>>Consultoria / Parecer</option>
                                </optgroup>
                                <optgroup label="Saídas">
                                    <option value="Custas Processuais" <?php echo (isset($lancamento['categoria']) && $lancamento['categoria'] == 'Custas Processuais') ? 'selected' : ''; ?>>Custas Processuais / Guias</option>
                                    <option value="Impostos" <?php echo (isset($lancamento['categoria']) && $lancamento['categoria'] == 'Impostos') ? 'selected' : ''; ?>>Impostos / DAS</option>
                                    <option value="Despesas de Escritório" <?php echo (isset($lancamento['categoria']) && $lancamento['categoria'] == 'Despesas de Escritório') ? 'selected' : ''; ?>>Despesas de Escritório</option>
                                    <option value="Divisão de Parceria" <?php echo (isset($lancamento['categoria']) && $lancamento['categoria'] == 'Divisão de Parceria') ? 'selected' : ''; ?>>Repasse para Parceiro</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Descrição do Lançamento</label>
                        <input type="text" class="form-control form-control-lg" name="descricao" placeholder="Ex: Pagamento Honorários Trabalhista" required value="<?php echo htmlspecialchars($lancamento['descricao'] ?? ''); ?>">
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Valor (R$)</label>
                            <input type="number" step="0.01" class="form-control form-control-lg fw-bold text-dark" name="valor" placeholder="0.00" required value="<?php echo htmlspecialchars($lancamento['valor'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Data de Vencimento</label>
                            <input type="date" class="form-control form-control-lg" name="data_vencimento" required value="<?php echo htmlspecialchars($lancamento['data_vencimento'] ?? date('Y-m-d')); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Status</label>
                            <select class="form-select form-control-lg" name="status" required>
                                <option value="Pendente" <?php echo (isset($lancamento['status']) && $lancamento['status'] == 'Pendente') ? 'selected' : ''; ?>>⏳ Pendente</option>
                                <option value="Pago" <?php echo (isset($lancamento['status']) && $lancamento['status'] == 'Pago') ? 'selected' : ''; ?>>✅ Pago / Concluído</option>
                            </select>
                        </div>
                    </div>

                    <?php if(!$modoEdicao) { ?>
                    <div class="row mb-5">
                        <div class="col-md-5">
                            <label class="form-label fw-bold text-primary">🔄 Parcelar em quantas vezes?</label>
                            <input type="number" class="form-control form-control-lg border-primary" name="parcelas" value="1" min="1" max="60">
                            <small class="text-muted">Deixe 1 para pagamento à vista.</small>
                        </div>
                    </div>
                    <?php } ?>

                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">🔗 Vínculos (Opcional - Para Relatórios)</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Cliente Vinculado</label>
                            <select class="form-select" name="cliente_id">
                                <option value="">Sem vínculo...</option>
                                <?php 
                                foreach($clientes as $c) {
                                    $sel = (isset($lancamento['cliente_id']) && $lancamento['cliente_id'] == $c['id']) ? 'selected' : '';
                                    echo "<option value='{$c['id']}' $sel>{$c['nome']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Processo Vinculado</label>
                            <select class="form-select" name="processo_id">
                                <option value="">Sem vínculo...</option>
                                <?php 
                                foreach($processos as $p) {
                                    $sel = (isset($lancamento['processo_id']) && $lancamento['processo_id'] == $p['id']) ? 'selected' : '';
                                    echo "<option value='{$p['id']}' $sel>{$p['numero_processo']} - {$p['tipo_acao']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="text-end mt-4 pt-3 border-top">
                        <a href="lista_financeiro.php" class="btn btn-secondary fw-bold px-4 me-2">Cancelar</a>
                        <button type="submit" class="btn btn-dark fw-bold px-5 btn-lg shadow">
                            <?php echo $modoEdicao ? '💾 Guardar Alterações' : '✅ Gravar Lançamento'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>