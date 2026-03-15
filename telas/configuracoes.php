<?php
// Arquivo: telas/configuracoes.php
// Função: Salvar os dados do escritório e a Chave PIX para cobranças automatizadas.

session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }
$idAdvogado = $_SESSION['id_usuario_logado'];

$arquivoConfig = '../dados/Configuracoes_' . $idAdvogado . '.json';
$config = [];
$mensagem = "";

// Carrega as configurações atuais
if (file_exists($arquivoConfig)) {
    $config = json_decode(file_get_contents($arquivoConfig), true) ?? [];
}

// Salva as novas configurações
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $config = [
        "nome_escritorio" => $_POST['nome_escritorio'],
        "cnpj_cpf_escritorio" => $_POST['cnpj_cpf_escritorio'],
        "chave_pix" => $_POST['chave_pix'],
        "tipo_chave_pix" => $_POST['tipo_chave_pix'],
        "titular_pix" => $_POST['titular_pix'],
        "banco_recebimento" => $_POST['banco_recebimento'],
        "ultima_atualizacao" => date('Y-m-d H:i:s')
    ];
    
    file_put_contents($arquivoConfig, json_encode($config, JSON_PRETTY_PRINT));
    $mensagem = "✅ Configurações financeiras e PIX salvas com sucesso!";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - Configurações do Escritório</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .header-config { background: linear-gradient(135deg, #2b5876 0%, #4e4376 100%); color: white; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-white" href="painel.php">JURIDEX</a>
        <a href="lista_financeiro.php" class="btn btn-outline-light btn-sm">Voltar ao Financeiro</a>
    </div>
</nav>

<div class="container mt-5" style="max-width: 800px;">
    
    <?php if ($mensagem) echo "<div class='alert alert-success fw-bold shadow-sm'>$mensagem</div>"; ?>

    <div class="card shadow border-0">
        <div class="card-header header-config py-3 text-center">
            <h4 class="fw-bold mb-0">⚙️ Configurações do Escritório</h4>
            <p class="mt-1 mb-0 small">Cadastre sua conta para automatizar as cobranças via WhatsApp.</p>
        </div>
        <div class="card-body p-4">
            
            <form method="POST" action="configuracoes.php">
                
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">🏢 Dados do Escritório</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="form-label fw-bold small">Nome do Escritório / Profissional</label>
                        <input type="text" class="form-control" name="nome_escritorio" placeholder="Ex: Silva Advocacia" required value="<?php echo htmlspecialchars($config['nome_escritorio'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">CNPJ ou CPF</label>
                        <input type="text" class="form-control" name="cnpj_cpf_escritorio" value="<?php echo htmlspecialchars($config['cnpj_cpf_escritorio'] ?? ''); ?>">
                    </div>
                </div>

                <h5 class="fw-bold text-success border-bottom pb-2 mb-3">💳 Conta de Recebimento Padrão (PIX)</h5>
                <div class="alert alert-success bg-opacity-10 border-success small mb-4">
                    <strong>Dica JURIDEX:</strong> Ao preencher estes dados, o robô de WhatsApp incluirá a sua chave PIX automaticamente em todas as mensagens de cobrança para os clientes.
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Tipo de Chave</label>
                        <select class="form-select" name="tipo_chave_pix">
                            <option value="CNPJ" <?php echo (isset($config['tipo_chave_pix']) && $config['tipo_chave_pix'] == 'CNPJ') ? 'selected' : ''; ?>>CNPJ</option>
                            <option value="CPF" <?php echo (isset($config['tipo_chave_pix']) && $config['tipo_chave_pix'] == 'CPF') ? 'selected' : ''; ?>>CPF</option>
                            <option value="Celular" <?php echo (isset($config['tipo_chave_pix']) && $config['tipo_chave_pix'] == 'Celular') ? 'selected' : ''; ?>>Celular</option>
                            <option value="E-mail" <?php echo (isset($config['tipo_chave_pix']) && $config['tipo_chave_pix'] == 'E-mail') ? 'selected' : ''; ?>>E-mail</option>
                            <option value="Aleatória" <?php echo (isset($config['tipo_chave_pix']) && $config['tipo_chave_pix'] == 'Aleatória') ? 'selected' : ''; ?>>Chave Aleatória</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold small">Sua Chave PIX</label>
                        <input type="text" class="form-control border-success fw-bold text-success" name="chave_pix" placeholder="Digite a chave PIX exata" required value="<?php echo htmlspecialchars($config['chave_pix'] ?? ''); ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold small">Nome do Titular da Conta</label>
                        <input type="text" class="form-control" name="titular_pix" placeholder="Nome que aparece no banco" required value="<?php echo htmlspecialchars($config['titular_pix'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Banco</label>
                        <input type="text" class="form-control" name="banco_recebimento" placeholder="Ex: Nubank, Inter..." required value="<?php echo htmlspecialchars($config['banco_recebimento'] ?? ''); ?>">
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-dark btn-lg fw-bold shadow">💾 Salvar Configurações</button>
                </div>
            </form>

        </div>
    </div>
</div>

</body>
</html>