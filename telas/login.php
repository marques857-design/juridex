<?php
// Arquivo: telas/login.php
session_start();
date_default_timezone_set('America/Sao_Paulo');

if (isset($_SESSION['id_usuario_logado'])) {
    if (isset($_SESSION['perfil']) && $_SESSION['perfil'] == 'master') { header("Location: painel_master.php"); exit; } 
    else { header("Location: painel.php"); exit; }
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $senha = trim($_POST['senha']);

    // 1. CEO / MESTRE
    if ($usuario === 'Administrador' && $senha === '245445') {
        $_SESSION['id_usuario_logado'] = 'ceo_fabio';
        $_SESSION['nome_usuario'] = 'Fabio (CEO)';
        $_SESSION['perfil'] = 'master';
        header("Location: painel_master.php"); exit;
    } 
    // 2. CLIENTES SAAS E SUAS EQUIPES
    else {
        $arquivoUsuarios = '../dados/Usuarios_SaaS.json';
        $usuarioEncontrado = false;
        
        // A) VERIFICA SE É O DONO DO ESCRITÓRIO (ADVOGADO PRINCIPAL)
        if (file_exists($arquivoUsuarios)) {
            $assinantes = json_decode(file_get_contents($arquivoUsuarios), true) ?? [];
            foreach ($assinantes as $key => $ass) {
                if ($ass['login'] === $usuario && $ass['senha'] === $senha) {
                    $usuarioEncontrado = true;
                    if (isset($ass['status']) && $ass['status'] != 'Ativo') { $erro = "🚫 Acesso Suspenso. Verifique a sua assinatura."; break; }
                    
                    // Atualiza último acesso
                    $assinantes[$key]['ultimo_acesso'] = date('Y-m-d H:i:s');
                    file_put_contents($arquivoUsuarios, json_encode($assinantes, JSON_PRETTY_PRINT));
                    
                    // LOGIN DO DONO
                    $_SESSION['id_usuario_logado'] = $ass['id_escritorio'];
                    $_SESSION['nome_usuario'] = $ass['responsavel'];
                    $_SESSION['cargo'] = 'Dono';
                    $_SESSION['perfil'] = 'advogado';
                    $_SESSION['plano'] = $ass['plano'] ?? 'Pro (R$ 100)';
                    header("Location: painel.php"); exit;
                }
            }
        }
        
        // B) SE NÃO É O DONO, VERIFICA SE É UM MEMBRO DA EQUIPE (ESTAGIÁRIO, ETC)
        if (!$usuarioEncontrado) {
            foreach (glob('../dados/Equipe_*.json') as $arqEquipe) {
                $equipe = json_decode(file_get_contents($arqEquipe), true) ?? [];
                foreach ($equipe as $membro) {
                    if ($membro['login'] === $usuario && $membro['senha'] === $senha) {
                        $usuarioEncontrado = true;
                        
                        // Extrai o ID do escritório pai a partir do nome do arquivo (Equipe_ID.json)
                        $idEscritorioPai = str_replace(['../dados/Equipe_', '.json'], '', $arqEquipe);
                        
                        // Busca o plano do escritório pai para herdar os limites (ex: IA)
                        $planoHerdado = 'Pro (R$ 100)';
                        $assinantes = file_exists($arquivoUsuarios) ? json_decode(file_get_contents($arquivoUsuarios), true) ?? [] : [];
                        foreach ($assinantes as $ass) { if ($ass['id_escritorio'] == $idEscritorioPai) { $planoHerdado = $ass['plano']; break; } }
                        
                        // LOGIN DO FUNCIONÁRIO (Ele vê a mesma base de dados do dono!)
                        $_SESSION['id_usuario_logado'] = $idEscritorioPai; 
                        $_SESSION['nome_usuario'] = $membro['nome'];
                        $_SESSION['cargo'] = $membro['cargo'];
                        $_SESSION['perfil'] = 'equipe'; // Perfil Equipe para bloquear telas no futuro
                        $_SESSION['plano'] = $planoHerdado;
                        header("Location: painel.php"); exit;
                    }
                }
                if($usuarioEncontrado) break;
            }
        }

        if (!$usuarioEncontrado) { $erro = "Acesso negado: Usuário ou senha incorretos."; }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - Acesso Restrito</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --azul-fundo: #1c1f3b; --azul-vibrante: #0084ff; }
        body { background-color: var(--azul-fundo); height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif;}
        .login-card { background: white; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.5); overflow: hidden; width: 100%; max-width: 480px; border-top: 5px solid var(--azul-vibrante);}
        .login-header { background: #ffffff; padding: 40px 30px 10px 30px; text-align: center; border-bottom: 1px solid #f1f1f1;}
        .login-body { padding: 30px 40px 40px 40px;}
        .form-control { border-radius: 8px; padding: 15px; border: 1px solid #ccc; background-color: #fafafa;}
        .form-control:focus { border-color: var(--azul-vibrante); box-shadow: 0 0 0 0.2rem rgba(0, 132, 255, 0.25); background-color: #fff;}
        .btn-login { background: var(--azul-vibrante); color: white; border: none; border-radius: 8px; padding: 15px; font-weight: bold; font-size: 1.1rem; transition: 0.3s;}
        .btn-login:hover { background: #006bce; color: white; box-shadow: 0 5px 15px rgba(0, 132, 255, 0.4);}
        .logo-img { max-height: 140px; width: 100%; object-fit: contain; margin-bottom: 10px; } 
    </style>
</head>
<body>
<div class="container d-flex justify-content-center">
    <div class="login-card">
        <div class="login-header">
            <img src="../assets/logo.png" alt="JURIDEX" class="logo-img" onerror="this.style.display='none';">
        </div>
        <div class="login-body">
            <?php if ($erro) { echo "<div class='alert alert-danger text-center fw-bold py-2 shadow-sm small'>$erro</div>"; } ?>
            <form method="POST" action="login.php">
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark small">Usuário de Acesso</label>
                    <input type="text" class="form-control" name="usuario" required autocomplete="off">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark small">Senha</label>
                    <input type="password" class="form-control" name="senha" required>
                </div>
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-login">Entrar no Sistema</button>
                </div>
            </form>
            <div class="text-center mt-4 pt-3 border-top">
                <a href="../index.php" class="text-muted small text-decoration-none">⬅ Voltar para a página inicial</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>