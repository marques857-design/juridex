<?php
// Arquivo: telas/gerenciar_equipe.php
// Função: Permite aos clientes VIP criarem, editarem e excluírem logins da equipe.

session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado']) || $_SESSION['perfil'] == 'master') { header("Location: login.php"); exit; }

$idEscritorio = $_SESSION['id_usuario_logado']; // O ID do escritório principal
$planoUsuario = $_SESSION['plano'] ?? 'Básico (R$ 50)';

// TRAVA DE SEGURANÇA: Só entra se for plano VIP (R$ 300)
if (strpos($planoUsuario, '300') === false && strpos($planoUsuario, '499') === false) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>
            <h2>🔒 Acesso Restrito</h2>
            <p>A Gestão de Equipe é uma funcionalidade exclusiva do <b>Plano VIP</b>.</p>
            <a href='painel.php'>Voltar ao Painel</a>
         </div>");
}

$arquivoEquipe = '../dados/Equipe_' . $idEscritorio . '.json';
$mensagem = "";

// =====================================================================
// AÇÃO 1: CADASTRAR NOVO MEMBRO
// =====================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] == 'novo_membro') {
    $equipe = file_exists($arquivoEquipe) ? json_decode(file_get_contents($arquivoEquipe), true) ?? [] : [];
    
    // Verifica se o login já existe na equipe
    $loginExiste = false;
    foreach($equipe as $m) { if($m['login'] == $_POST['login']) { $loginExiste = true; break; } }
    
    if ($loginExiste) {
        $mensagem = "<div class='alert alert-danger fw-bold'>Erro: Este usuário de login já existe na sua equipe.</div>";
    } else {
        $equipe[] = [
            "id_membro" => uniqid(),
            "nome" => $_POST['nome'],
            "cargo" => $_POST['cargo'],
            "login" => $_POST['login'],
            "senha" => $_POST['senha'],
            "data_criacao" => date('Y-m-d H:i:s')
        ];
        file_put_contents($arquivoEquipe, json_encode($equipe, JSON_PRETTY_PRINT));
        header("Location: gerenciar_equipe.php?msg=sucesso"); exit;
    }
}

// =====================================================================
// AÇÃO 2: EDITAR MEMBRO EXISTENTE
// =====================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] == 'editar_membro') {
    $equipe = file_exists($arquivoEquipe) ? json_decode(file_get_contents($arquivoEquipe), true) ?? [] : [];
    $idEditar = $_POST['id_membro'];
    
    // Verifica se o novo login não está sendo usado por OUTRA pessoa
    $loginExiste = false;
    foreach($equipe as $m) { 
        if($m['login'] == $_POST['login'] && $m['id_membro'] != $idEditar) { $loginExiste = true; break; } 
    }
    
    if ($loginExiste) {
        $mensagem = "<div class='alert alert-danger fw-bold'>Erro: Este usuário de login já está em uso por outro membro.</div>";
    } else {
        foreach($equipe as $key => $m) {
            if ($m['id_membro'] == $idEditar) {
                $equipe[$key]['nome'] = $_POST['nome'];
                $equipe[$key]['cargo'] = $_POST['cargo'];
                $equipe[$key]['login'] = $_POST['login'];
                $equipe[$key]['senha'] = $_POST['senha']; // Atualiza a senha
                break;
            }
        }
        file_put_contents($arquivoEquipe, json_encode($equipe, JSON_PRETTY_PRINT));
        header("Location: gerenciar_equipe.php?msg=editado"); exit;
    }
}

// =====================================================================
// AÇÃO 3: EXCLUIR MEMBRO
// =====================================================================
if (isset($_GET['excluir'])) {
    $idMembro = $_GET['excluir'];
    $equipe = file_exists($arquivoEquipe) ? json_decode(file_get_contents($arquivoEquipe), true) ?? [] : [];
    $novaEquipe = [];
    foreach($equipe as $m) { if($m['id_membro'] != $idMembro) { $novaEquipe[] = $m; } }
    file_put_contents($arquivoEquipe, json_encode($novaEquipe, JSON_PRETTY_PRINT));
    header("Location: gerenciar_equipe.php?msg=removido"); exit;
}

$equipe = file_exists($arquivoEquipe) ? json_decode(file_get_contents($arquivoEquipe), true) ?? [] : [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX - Gestão de Equipe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-custom { background: #1c1f3b; padding: 15px 0; border-bottom: 2px solid #0084ff; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .bg-vip { background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); color: #000; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold fs-4 d-flex align-items-center" href="painel.php">
            <img src="../assets/logo.png" alt="JURIDEX" height="40" class="me-2" onerror="this.style.display='none';"> 
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="painel.php" class="btn btn-outline-light btn-sm fw-bold">Voltar ao Painel</a>
        </div>
    </div>
</nav>

<div class="container mb-5">
    
    <?php echo $mensagem; ?>
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'sucesso') echo "<div class='alert alert-success fw-bold shadow-sm'>✅ Novo membro da equipe cadastrado com sucesso!</div>"; ?>
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'editado') echo "<div class='alert alert-info fw-bold shadow-sm'>✏️ Dados do membro atualizados com sucesso!</div>"; ?>
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'removido') echo "<div class='alert alert-warning fw-bold shadow-sm'>🗑️ Membro removido da equipe.</div>"; ?>

    <div class="card card-custom bg-vip text-dark p-4 mb-4 d-flex flex-row justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold mb-1">👑 Módulo VIP: Gestão de Equipe</h3>
            <p class="mb-0">Crie e edite logins para seus estagiários, secretárias e sócios acessarem o sistema.</p>
        </div>
        <div>
            <button class="btn btn-dark fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovoMembro">➕ Cadastrar Funcionário</button>
        </div>
    </div>

    <div class="card card-custom">
        <div class="card-header bg-white pt-4 pb-2 border-0">
            <h5 class="fw-bold text-dark">👥 Membros do Escritório</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Nome do Funcionário</th>
                            <th>Cargo / Permissão</th>
                            <th>Usuário de Login</th>
                            <th class="text-end pe-4">Ações do Administrador</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($equipe)) { echo "<tr><td colspan='4' class='text-center p-4 text-muted'>Nenhum funcionário cadastrado. Você é um exército de um homem só!</td></tr>"; } ?>
                        <?php foreach($equipe as $m) { ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><?php echo htmlspecialchars($m['nome']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($m['cargo']); ?></span></td>
                                <td><code class="text-primary fw-bold fs-6"><?php echo htmlspecialchars($m['login']); ?></code></td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-primary fw-bold me-2" onclick="abrirModalEditar('<?php echo $m['id_membro']; ?>', '<?php echo addslashes(htmlspecialchars($m['nome'])); ?>', '<?php echo htmlspecialchars($m['cargo']); ?>', '<?php echo addslashes(htmlspecialchars($m['login'])); ?>', '<?php echo addslashes(htmlspecialchars($m['senha'])); ?>')">✏️ Editar</button>
                                    
                                    <a href="gerenciar_equipe.php?excluir=<?php echo $m['id_membro']; ?>" class="btn btn-sm btn-outline-danger fw-bold" onclick="return confirm('Deseja excluir este acesso? O funcionário não conseguirá mais entrar no sistema.');">🗑️ Remover</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNovoMembro" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Novo Membro da Equipe</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="gerenciar_equipe.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="novo_membro">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nome Completo</label>
                        <input type="text" class="form-control" name="nome" placeholder="Ex: João Estagiário" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nível de Acesso (Cargo)</label>
                        <select class="form-select" name="cargo" required>
                            <option value="Sócio Advogado">Sócio / Advogado (Acesso Total)</option>
                            <option value="Estagiário">Estagiário (Não vê Financeiro)</option>
                            <option value="Secretária">Secretária / Administrativo</option>
                        </select>
                    </div>
                    <hr>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-primary">Usuário para Login</label>
                            <input type="text" class="form-control" name="login" placeholder="Ex: joao.estagio" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-primary">Senha Pessoal</label>
                            <input type="text" class="form-control" name="senha" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold">Criar Acesso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarMembro" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Editar Dados do Funcionário</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="gerenciar_equipe.php">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar_membro">
                    <input type="hidden" name="id_membro" id="edit_id_membro">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nome Completo</label>
                        <input type="text" class="form-control" name="nome" id="edit_nome" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nível de Acesso (Cargo)</label>
                        <select class="form-select" name="cargo" id="edit_cargo" required>
                            <option value="Sócio Advogado">Sócio / Advogado (Acesso Total)</option>
                            <option value="Estagiário">Estagiário (Não vê Financeiro)</option>
                            <option value="Secretária">Secretária / Administrativo</option>
                        </select>
                    </div>
                    <hr>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-primary">Usuário para Login</label>
                            <input type="text" class="form-control" name="login" id="edit_login" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-primary">Senha Pessoal</label>
                            <input type="text" class="form-control" name="senha" id="edit_senha" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold">💾 Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Função Javascript para pegar os dados da tabela e jogar dentro do Modal de Edição
function abrirModalEditar(id, nome, cargo, login, senha) {
    document.getElementById('edit_id_membro').value = id;
    document.getElementById('edit_nome').value = nome;
    document.getElementById('edit_login').value = login;
    document.getElementById('edit_senha').value = senha;
    
    let select = document.getElementById('edit_cargo');
    for (let i = 0; i < select.options.length; i++) {
        if (select.options[i].value === cargo) { 
            select.selectedIndex = i; 
            break; 
        }
    }
    
    var modalEditar = new bootstrap.Modal(document.getElementById('modalEditarMembro'));
    modalEditar.show();
}
</script>
</body>
</html>