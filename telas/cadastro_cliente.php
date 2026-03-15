<?php
// Arquivo: telas/cadastro_cliente.php
session_start();
if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }

$idAdvogado = $_SESSION['id_usuario_logado'];
$mensagem = "";
$cliente = null;
$modoEdicao = false;

$arquivoClientes = '../dados/Clientes_' . $idAdvogado . '.json';

if (isset($_GET['id'])) {
    $modoEdicao = true;
    if (file_exists($arquivoClientes)) {
        $lista = json_decode(file_get_contents($arquivoClientes), true) ?? [];
        foreach ($lista as $c) {
            if ($c['id'] == $_GET['id']) { $cliente = $c; break; }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lista = file_exists($arquivoClientes) ? json_decode(file_get_contents($arquivoClientes), true) ?? [] : [];

    $dadosCliente = [
        "id" => $_POST['id_cliente'] ?: uniqid(),
        "id_advogado_responsavel" => $idAdvogado,
        // Informações Básicas
        "tipo_pessoa" => $_POST['tipo_pessoa'],
        "nome" => $_POST['nome'],
        "cpf_cnpj" => $_POST['cpf_cnpj'],
        "rg" => $_POST['rg'],
        "data_nascimento" => $_POST['data_nascimento'],
        "estado_civil" => $_POST['estado_civil'],
        "profissao" => $_POST['profissao'],
        // Contato e Endereço
        "telefone" => $_POST['telefone'],
        "email" => $_POST['email'],
        "cep" => $_POST['cep'],
        "rua" => $_POST['rua'],
        "numero" => $_POST['numero'],
        "bairro" => $_POST['bairro'],
        "cidade" => $_POST['cidade'],
        "estado" => $_POST['estado'],
        // Jurídico, Status, Score e Tags (NOVIDADE)
        "tipo_cliente_juridico" => $_POST['tipo_cliente_juridico'],
        "area_juridica" => $_POST['area_juridica'],
        "status_cliente" => $_POST['status_cliente'],
        "score_cliente" => $_POST['score_cliente'],
        "tags" => $_POST['tags'],
        "observacoes" => $_POST['observacoes'],
        // Financeiro Base (NOVIDADE)
        "valor_contrato" => $_POST['valor_contrato'],
        "forma_pagamento" => $_POST['forma_pagamento'],
        "numero_parcelas" => $_POST['numero_parcelas'],
        "data_cadastro" => $_POST['data_cadastro'] ?: date('Y-m-d H:i:s')
    ];

    if ($modoEdicao || !empty($_POST['id_cliente'])) {
        foreach ($lista as $key => $c) {
            if ($c['id'] == $dadosCliente['id']) { $lista[$key] = $dadosCliente; break; }
        }
        $mensagem = "✅ Cliente atualizado com sucesso!";
        $cliente = $dadosCliente;
    } else {
        $lista[] = $dadosCliente;
        $mensagem = "✅ Novo cliente registrado com sucesso!";
    }
    file_put_contents($arquivoClientes, json_encode($lista, JSON_PRETTY_PRINT));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - Ficha do Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-white" href="painel.php">JURIDEX</a>
        <a href="lista_clientes.php" class="btn btn-outline-light btn-sm">Voltar para a Lista</a>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <h3 class="fw-bold text-dark mb-4"><?php echo $modoEdicao ? '✏️ Editar Cliente' : '➕ Novo Registro de Cliente'; ?></h3>
    <?php if ($mensagem) echo "<div class='alert alert-success fw-bold'>$mensagem</div>"; ?>

    <form method="POST" action="cadastro_cliente.php" class="bg-white p-4 shadow-sm rounded border">
        <input type="hidden" name="id_cliente" value="<?php echo $cliente['id'] ?? ''; ?>">
        <input type="hidden" name="data_cadastro" value="<?php echo $cliente['data_cadastro'] ?? ''; ?>">

        <h5 class="fw-bold text-primary border-bottom pb-2 mb-3">1. Informações Básicas</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-2">
                <label class="form-label fw-bold">Tipo</label>
                <select class="form-select" name="tipo_pessoa" required>
                    <option value="Física" <?php echo (isset($cliente['tipo_pessoa']) && $cliente['tipo_pessoa'] == 'Física') ? 'selected' : ''; ?>>Física</option>
                    <option value="Jurídica" <?php echo (isset($cliente['tipo_pessoa']) && $cliente['tipo_pessoa'] == 'Jurídica') ? 'selected' : ''; ?>>Jurídica</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Nome Completo / Razão Social</label>
                <input type="text" class="form-control" name="nome" required value="<?php echo htmlspecialchars($cliente['nome'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">CPF / CNPJ</label>
                <input type="text" class="form-control" name="cpf_cnpj" required value="<?php echo htmlspecialchars($cliente['cpf_cnpj'] ?? ''); ?>" <?php echo $modoEdicao ? 'readonly' : ''; ?>>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">RG / IE</label>
                <input type="text" class="form-control" name="rg" value="<?php echo htmlspecialchars($cliente['rg'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Nascimento / Fundação</label>
                <input type="date" class="form-control" name="data_nascimento" value="<?php echo htmlspecialchars($cliente['data_nascimento'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Estado Civil</label>
                <input type="text" class="form-control" name="estado_civil" value="<?php echo htmlspecialchars($cliente['estado_civil'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Profissão</label>
                <input type="text" class="form-control" name="profissao" value="<?php echo htmlspecialchars($cliente['profissao'] ?? ''); ?>">
            </div>
        </div>

        <h5 class="fw-bold text-primary border-bottom pb-2 mb-3">2. Contato e Endereço</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label fw-bold">Telefone / WhatsApp</label>
                <input type="text" class="form-control" name="telefone" required value="<?php echo htmlspecialchars($cliente['telefone'] ?? ''); ?>">
            </div>
            <div class="col-md-8">
                <label class="form-label fw-bold">E-mail</label>
                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($cliente['email'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">CEP</label>
                <input type="text" class="form-control" name="cep" value="<?php echo htmlspecialchars($cliente['cep'] ?? ''); ?>">
            </div>
            <div class="col-md-8">
                <label class="form-label fw-bold">Rua / Avenida</label>
                <input type="text" class="form-control" name="rua" value="<?php echo htmlspecialchars($cliente['rua'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Número</label>
                <input type="text" class="form-control" name="numero" value="<?php echo htmlspecialchars($cliente['numero'] ?? ''); ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label fw-bold">Bairro</label>
                <input type="text" class="form-control" name="bairro" value="<?php echo htmlspecialchars($cliente['bairro'] ?? ''); ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label fw-bold">Cidade</label>
                <input type="text" class="form-control" name="cidade" value="<?php echo htmlspecialchars($cliente['cidade'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Estado</label>
                <input type="text" class="form-control" name="estado" value="<?php echo htmlspecialchars($cliente['estado'] ?? ''); ?>">
            </div>
        </div>

        <h5 class="fw-bold text-primary border-bottom pb-2 mb-3">3. Status, Tags e Perfil (CRM)</h5>
        <div class="row g-3 mb-4 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold">Status do Cliente</label>
                <select class="form-select border-primary" name="status_cliente">
                    <option value="Ativo" <?php echo (isset($cliente['status_cliente']) && $cliente['status_cliente'] == 'Ativo') ? 'selected' : ''; ?>>🟢 Ativo</option>
                    <option value="Em negociação" <?php echo (isset($cliente['status_cliente']) && $cliente['status_cliente'] == 'Em negociação') ? 'selected' : ''; ?>>🟡 Em negociação</option>
                    <option value="Potencial" <?php echo (isset($cliente['status_cliente']) && $cliente['status_cliente'] == 'Potencial') ? 'selected' : ''; ?>>🔵 Potencial (Lead)</option>
                    <option value="Inadimplente" <?php echo (isset($cliente['status_cliente']) && $cliente['status_cliente'] == 'Inadimplente') ? 'selected' : ''; ?>>🔴 Inadimplente</option>
                    <option value="Arquivado" <?php echo (isset($cliente['status_cliente']) && $cliente['status_cliente'] == 'Arquivado') ? 'selected' : ''; ?>>⚫ Arquivado</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Score / Nível</label>
                <select class="form-select" name="score_cliente">
                    <option value="Padrão" <?php echo (isset($cliente['score_cliente']) && $cliente['score_cliente'] == 'Padrão') ? 'selected' : ''; ?>>Padrão</option>
                    <option value="VIP" <?php echo (isset($cliente['score_cliente']) && $cliente['score_cliente'] == 'VIP') ? 'selected' : ''; ?>>⭐ VIP</option>
                    <option value="Risco" <?php echo (isset($cliente['score_cliente']) && $cliente['score_cliente'] == 'Risco') ? 'selected' : ''; ?>>⚠️ Risco</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Tags Inteligentes (Separe por vírgula)</label>
                <input type="text" class="form-control" name="tags" placeholder="Ex: INSS, Divórcio, Urgente..." value="<?php echo htmlspecialchars($cliente['tags'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Tipo de Relacionamento</label>
                <select class="form-select" name="tipo_cliente_juridico">
                    <option value="Autor" <?php echo (isset($cliente['tipo_cliente_juridico']) && $cliente['tipo_cliente_juridico'] == 'Autor') ? 'selected' : ''; ?>>Polo Ativo (Autor)</option>
                    <option value="Réu" <?php echo (isset($cliente['tipo_cliente_juridico']) && $cliente['tipo_cliente_juridico'] == 'Réu') ? 'selected' : ''; ?>>Polo Passivo (Réu)</option>
                    <option value="Consultoria" <?php echo (isset($cliente['tipo_cliente_juridico']) && $cliente['tipo_cliente_juridico'] == 'Consultoria') ? 'selected' : ''; ?>>Consultoria Preventiva</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Área Principal</label>
                <select class="form-select" name="area_juridica">
                    <option value="Cível" <?php echo (isset($cliente['area_juridica']) && $cliente['area_juridica'] == 'Cível') ? 'selected' : ''; ?>>Cível / Família</option>
                    <option value="Trabalhista" <?php echo (isset($cliente['area_juridica']) && $cliente['area_juridica'] == 'Trabalhista') ? 'selected' : ''; ?>>Trabalhista</option>
                    <option value="Previdenciário" <?php echo (isset($cliente['area_juridica']) && $cliente['area_juridica'] == 'Previdenciário') ? 'selected' : ''; ?>>Previdenciário</option>
                    <option value="Penal" <?php echo (isset($cliente['area_juridica']) && $cliente['area_juridica'] == 'Penal') ? 'selected' : ''; ?>>Penal</option>
                </select>
            </div>
        </div>

        <h5 class="fw-bold text-primary border-bottom pb-2 mb-3">4. Base do Contrato / Honorários</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label fw-bold">Valor Base do Contrato (R$)</label>
                <input type="number" step="0.01" class="form-control text-success fw-bold" name="valor_contrato" placeholder="Ex: 4500.00" value="<?php echo htmlspecialchars($cliente['valor_contrato'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Forma de Pagamento</label>
                <input type="text" class="form-control" name="forma_pagamento" placeholder="Ex: Pix, Boleto, Êxito 30%" value="<?php echo htmlspecialchars($cliente['forma_pagamento'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Qtd. Parcelas</label>
                <input type="number" class="form-control" name="numero_parcelas" placeholder="Ex: 6" value="<?php echo htmlspecialchars($cliente['numero_parcelas'] ?? ''); ?>">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">Observações Gerais</label>
            <textarea class="form-control" name="observacoes" rows="3"><?php echo htmlspecialchars($cliente['observacoes'] ?? ''); ?></textarea>
        </div>

        <div class="text-end">
            <a href="lista_clientes.php" class="btn btn-secondary fw-bold px-4">Cancelar</a>
            <button type="submit" class="btn btn-primary fw-bold px-5"><?php echo $modoEdicao ? '💾 Guardar Alterações' : '✅ Registrar Novo Cliente'; ?></button>
        </div>
    </form>
</div>
</body>
</html>