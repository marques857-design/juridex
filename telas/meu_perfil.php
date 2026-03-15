<?php
// Arquivo: telas/meu_perfil.php
// Função: Gestão de Conta e Foto de Perfil (Salva em Base64 diretamente no JSON, sem falhas de permissão de pasta)

session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }

$idUsuario = $_SESSION['id_usuario_logado'];
$cargoAtual = $_SESSION['cargo'] ?? 'Advogado';

// Tenta carregar os dados salvos permanentemente no JSON
$arqConfig = '../dados/Usuario_Config_' . $idUsuario . '.json';
$configUser = file_exists($arqConfig) ? json_decode(file_get_contents($arqConfig), true) : [];

$nomeAtual = $configUser['nome'] ?? $_SESSION['nome_usuario'] ?? 'Advogado';
$fotoAtual = $configUser['foto'] ?? $_SESSION['foto_perfil'] ?? '../assets/logo.png';

$mensagem = '';
$tipoMsg = '';

// =========================================================================
// PROCESSAR FORMULÁRIO DE ATUALIZAÇÃO
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $salvouAlgo = false;
    
    // 1. Atualizar Nome
    if (!empty($_POST['nome'])) {
        $nomeAtual = trim($_POST['nome']);
        $_SESSION['nome_usuario'] = $nomeAtual;
        $configUser['nome'] = $nomeAtual;
        $salvouAlgo = true;
    }

    // 2. Upload da Foto de Perfil (Convertendo para Base64 para evitar erros de pasta)
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == 0) {
        $tipoArquivo = $_FILES['foto_perfil']['type'];
        $tamanhoArquivo = $_FILES['foto_perfil']['size'];
        
        // Verifica se é imagem e se não é gigante (limite aprox 2MB)
        if (in_array($tipoArquivo, ['image/jpeg', 'image/png', 'image/jpg']) && $tamanhoArquivo <= 2097152) {
            $conteudo = file_get_contents($_FILES['foto_perfil']['tmp_name']);
            $base64 = 'data:' . $tipoArquivo . ';base64,' . base64_encode($conteudo);
            
            $fotoAtual = $base64;
            $_SESSION['foto_perfil'] = $fotoAtual;
            $configUser['foto'] = $fotoAtual;
            $salvouAlgo = true;
        } else {
            $mensagem = "Formato inválido ou arquivo muito grande. Use JPG ou PNG até 2MB.";
            $tipoMsg = "warning";
        }
    } elseif (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] != 0 && $_FILES['foto_perfil']['error'] != 4) {
        $mensagem = "Erro ao processar a foto. Tente uma imagem mais leve.";
        $tipoMsg = "danger";
    }

    // Salva permanentemente no JSON
    if ($salvouAlgo) {
        // Cria a pasta dados se por acaso não existir
        if (!is_dir('../dados/')) { mkdir('../dados/', 0777, true); }
        file_put_contents($arqConfig, json_encode($configUser, JSON_PRETTY_PRINT));
        $mensagem = "Perfil atualizado e foto salva permanentemente com sucesso!";
        $tipoMsg = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - Meu Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --bg-body: #f3f4f6; --text-dark: #0f172a; --primary: #2563eb; --card-bg: #ffffff; --border-light: #e2e8f0; }
        body { font-family: 'Segoe UI', Inter, Tahoma, sans-serif; background-color: var(--bg-body); margin: 0; padding: 0; color: var(--text-dark);}
        
        .main-content { margin-left: 280px; min-height: 100vh; display: flex; flex-direction: column; }
        .topbar { background: var(--card-bg); padding: 25px 40px; border-bottom: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center; }
        
        .premium-card { background: var(--card-bg); border-radius: 16px; border: 1px solid var(--border-light); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 40px; margin-top: 30px; max-width: 800px; margin-left: auto; margin-right: auto; width: 100%; }
        
        .foto-container { position: relative; width: 150px; height: 150px; margin: 0 auto 30px auto; }
        .foto-perfil { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid white; box-shadow: 0 10px 25px rgba(0,0,0,0.1); background: #fff;}
        .btn-alterar-foto { position: absolute; bottom: 5px; right: 5px; background: var(--primary); color: white; border: none; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3); transition: 0.2s;}
        .btn-alterar-foto:hover { transform: scale(1.1); background: #1d4ed8; }

        .form-label { font-weight: 700; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control { border-radius: 10px; padding: 12px 15px; border: 1px solid #cbd5e1; background: #f8fafc; transition: 0.2s; }
        .form-control:focus { background: white; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(37,99,235,0.1); }

        @media (max-width: 991px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <div>
            <h3 class="mb-1 fw-bold text-dark" style="letter-spacing: -0.5px;">Configurações de Conta</h3>
            <p class="mb-0 text-muted small">Personalize a sua experiência no JURIDEX.</p>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-md-block">
                <span class="fw-bold text-dark d-block" style="line-height: 1;">Olá, <?php echo htmlspecialchars($nomeAtual); ?></span>
                <small class="text-muted"><?php echo htmlspecialchars($cargoAtual); ?></small>
            </div>
            <img src="<?php echo htmlspecialchars($fotoAtual); ?>" alt="Perfil" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; box-shadow: 0 2px 5px rgba(0,0,0,0.05); background: #fff;" onerror="this.src='../assets/logo.png';">
        </div>
    </div>

    <div class="container-fluid px-4 pb-5">
        
        <div class="premium-card">
            
            <?php if($mensagem): ?>
                <div class="alert alert-<?php echo $tipoMsg; ?> fw-bold rounded-3 mb-4 border-0 shadow-sm">
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>

            <form action="meu_perfil.php" method="POST" enctype="multipart/form-data">
                
                <div class="text-center">
                    <div class="foto-container">
                        <img src="<?php echo htmlspecialchars($fotoAtual); ?>" class="foto-perfil" id="previewFoto" onerror="this.src='../assets/logo.png';">
                        <label for="uploadFoto" class="btn-alterar-foto" title="Alterar Foto">📷</label>
                        <input type="file" id="uploadFoto" name="foto_perfil" accept="image/png, image/jpeg" style="display: none;" onchange="previewImagem(event)">
                    </div>
                    <h4 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($nomeAtual); ?></h4>
                    <p class="text-muted mb-5"><?php echo htmlspecialchars($cargoAtual); ?></p>
                </div>

                <div class="row g-4">
                    <div class="col-md-12">
                        <label class="form-label">Nome de Exibição</label>
                        <input type="text" name="nome" class="form-control fw-bold" value="<?php echo htmlspecialchars($nomeAtual); ?>" required>
                    </div>
                </div>

                <div class="mt-5 text-end pt-3 border-top">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold px-5" style="border-radius: 10px; box-shadow: 0 4px 15px rgba(37,99,235,0.3);">
                        💾 Salvar Alterações
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewImagem(event) {
    var reader = new FileReader();
    reader.onload = function(){
        var output = document.getElementById('previewFoto');
        output.src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
}
</script>
</body>
</html>