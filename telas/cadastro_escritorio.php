<?php
// Arquivo: telas/cadastro_escritorio.php
session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

// Blindagem: Só o Master entra aqui
if (!isset($_SESSION['id_usuario_logado']) || $_SESSION['perfil'] != 'master') { header("Location: login.php"); exit; }

$arquivoUsuarios = '../dados/Usuarios_SaaS.json';
$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $assinantes = file_exists($arquivoUsuarios) ? json_decode(file_get_contents($arquivoUsuarios), true) ?? [] : [];
    
    $loginExiste = false;
    foreach($assinantes as $a) {
        if(isset($a['login']) && $a['login'] == $_POST['login']) { $loginExiste = true; break; }
    }

    if ($loginExiste) {
        $mensagem = "<div class='alert alert-danger fw-bold'>Erro: Este usuário de login já está em uso! Escolha outro.</div>";
    } else {
        $novoEscritorio = [
            "id_escritorio" => "esc_" . uniqid(),
            "nome_escritorio" => $_POST['nome_escritorio'],
            "responsavel" => $_POST['responsavel'],
            "telefone" => $_POST['telefone'],
            "login" => $_POST['login'],
            "senha" => $_POST['senha'], 
            "plano" => $_POST['plano'], // Aqui salvamos qual plano ele comprou!
            "status" => "Ativo",
            "data_cadastro" => date('Y-m-d H:i:s')
        ];
        
        $assinantes[] = $novoEscritorio;
        file_put_contents($arquivoUsuarios, json_encode($assinantes, JSON_PRETTY_PRINT));
        $mensagem = "<div class='alert alert-success fw-bold'>🚀 Sucesso! Novo escritório criado. O advogado já pode fazer login.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - Nova Assinatura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #1a1a2e; color: #fff; font-family: 'Segoe UI', sans-serif; }
        .navbar-master { background-color: #0f3460; border-bottom: 2px solid #e94560; }
        .card-dark { background-color: #16213e; border: 1px solid #0f3460; border-radius: 10px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-master py-3 mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold fs-4 text-white" href="painel_master.php">🚀 JURIDEX | ADMIN SAAS</a>
        <a href="painel_master.php" class="btn btn-outline-light btn-sm fw-bold">Voltar ao Dashboard</a>
    </div>
</nav>

<div class="container mt-5" style="max-width: 800px;">
    <?php echo $mensagem; ?>
    
    <div class="card card-dark shadow-lg">
        <div class="card-header border-bottom-0 pt-4 pb-2 px-4">
            <h4 class="fw-bold text-white mb-0">➕ Cadastrar Nova Licença (Escritório)</h4>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="cadastro_escritorio.php">
                
                <h6 class="text-info fw-bold mb-3 border-bottom border-secondary pb-2">1. Dados do Contratante</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nome do Escritório</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" name="nome_escritorio" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nome do Responsável</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" name="responsavel" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">WhatsApp / Contato</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" name="telefone" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-warning">Plano Contratado</label>
                        <select class="form-select bg-dark text-warning border-warning fw-bold" name="plano" required>
                            <option value="Básico (R$ 50)">Plano Básico (R$ 50/mês) - Sem IA</option>
                            <option value="Pro (R$ 100)">Plano Pro (R$ 100/mês) - Com IA</option>
                            <option value="VIP (R$ 300)">Plano VIP (R$ 300/mês) - Com IA e Equipe</option>
                        </select>
                    </div>
                </div>

                <h6 class="text-warning fw-bold mb-3 border-bottom border-secondary pb-2">2. Credenciais de Acesso ao Sistema</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6"><label class="form-label small fw-bold">Usuário de Login</label><input type="text" class="form-control bg-dark text-white border-warning" name="login" required></div>
                    <div class="col-md-6"><label class="form-label small fw-bold">Senha de Acesso</label><input type="text" class="form-control bg-dark text-white border-warning" name="senha" required></div>
                </div>
                <div class="d-grid mt-4"><button type="submit" class="btn btn-danger btn-lg fw-bold shadow">✅ Liberar Acesso e Criar Conta</button></div>
            </form>
        </div>
    </div>
</div>
</body>
</html>