<?php
// Arquivo: telas/sidebar.php
// Função: Menu Lateral Responsivo (Celular, Tablet, PC) com Scroll

$paginaAtual = basename($_SERVER['PHP_SELF']);
$cargoUsuario = $_SESSION['cargo'] ?? '';
$isEstag = (strpos(strtolower($cargoUsuario), 'estagiário') !== false || strpos(strtolower($cargoUsuario), 'estagiaria') !== false);
?>
<style>
    .sidebar { width: 280px; height: 100vh; background-color: #1c1f3b; color: #fff; display: flex; flex-direction: column; position: fixed; left: 0; top: 0; z-index: 1050; box-shadow: 4px 0 15px rgba(0,0,0,0.05); transition: transform 0.3s ease; }
    
    /* AQUI ESTÁ A CORREÇÃO DO LOGOTIPO MAIOR */
    .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); background: #15172b; flex-shrink: 0; display: flex; align-items: center; justify-content: center; min-height: 90px;}
    .sidebar-header img { max-height: 70px; max-width: 100%; object-fit: contain; }
    
    .sidebar-menu { padding: 15px 0; flex-grow: 1; overflow-y: auto; overflow-x: hidden; height: calc(100vh - 160px); }
    
    .menu-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.4); padding: 10px 25px; font-weight: 800; margin-top: 5px; display: block; }
    
    .menu-item { padding: 12px 25px; display: flex; align-items: center; gap: 15px; color: rgba(255,255,255,0.7); text-decoration: none; font-weight: 500; font-size: 0.95rem; transition: 0.2s; border-left: 4px solid transparent; }
    .menu-item:hover { color: #fff; background-color: rgba(255,255,255,0.05); }
    .menu-item.active { color: #fff; background-color: rgba(0, 132, 255, 0.1); border-left-color: #0084ff; font-weight: 700; }
    
    .menu-icon { font-style: normal; font-size: 1.2rem; width: 24px; text-align: center; }
    .badge-ia { background: linear-gradient(135deg, #d4af37, #f3e5ab); color: #1c1f3b; font-size: 0.65rem; padding: 3px 6px; border-radius: 4px; margin-left: auto; font-weight: bold; }
    
    .sidebar-footer { padding: 15px 0; border-top: 1px solid rgba(255,255,255,0.05); background: #15172b; flex-shrink: 0; }
    
    .main-content { margin-left: 280px; min-height: 100vh; background-color: #f4f7f6; display: flex; flex-direction: column; transition: margin-left 0.3s ease; width: calc(100% - 280px); }
    .topbar { background: #fff; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 10px; }
    
    .sidebar-menu::-webkit-scrollbar { width: 4px; }
    .sidebar-menu::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
    .sidebar-menu::-webkit-scrollbar-track { background: transparent; }

    @media (max-width: 991px) {
        .sidebar { transform: translateX(-100%); }
        .sidebar.show { transform: translateX(0); }
        .main-content { margin-left: 0; width: 100%; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 1040; }
        .sidebar-overlay.show { display: block; }
    }
</style>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<nav class="sidebar" id="sidebarMain">
    <div class="sidebar-header">
        <a href="painel.php" class="w-100">
            <img src="../assets/logo.png" alt="JURIDEX" onerror="this.outerHTML='<h3 class=\'text-white fw-bold mb-0\'>⚖️ JURIDEX</h3>';">
        </a>
    </div>

    <div class="sidebar-menu">
        <span class="menu-label">Dashboard</span>
        <a href="painel.php" class="menu-item <?php echo $paginaAtual == 'painel.php' ? 'active' : ''; ?>">
            <i class="menu-icon">📊</i> <span>Visão Geral</span>
        </a>

        <span class="menu-label">Gestão do Escritório</span>
        <a href="lista_clientes.php" class="menu-item <?php echo strpos($paginaAtual, 'cliente') !== false ? 'active' : ''; ?>">
            <i class="menu-icon">👥</i> <span>Meus Clientes</span>
        </a>
        <a href="lista_processos.php" class="menu-item <?php echo strpos($paginaAtual, 'processo') !== false && strpos($paginaAtual, 'kanban') === false ? 'active' : ''; ?>">
            <i class="menu-icon">⚖️</i> <span>Processos (Lista)</span>
        </a>
        <a href="kanban_processos.php" class="menu-item <?php echo $paginaAtual == 'kanban_processos.php' ? 'active' : ''; ?>">
            <i class="menu-icon">🗂️</i> <span>Kanban (Trello)</span>
        </a>
        <a href="agenda.php" class="menu-item <?php echo $paginaAtual == 'agenda.php' ? 'active' : ''; ?>">
            <i class="menu-icon">📅</i> <span>Agenda Global</span>
        </a>

        <span class="menu-label">Inteligência Jurídica</span>
        <a href="central_ia.php" class="menu-item <?php echo strpos($paginaAtual, 'ia') !== false ? 'active' : ''; ?>">
            <i class="menu-icon">🧠</i> <span>JURIDEX Intelligence</span> <span class="badge-ia">PRO</span>
        </a>
        <a href="relatorio_sexta.php" class="menu-item <?php echo $paginaAtual == 'relatorio_sexta.php' ? 'active' : ''; ?>">
            <i class="menu-icon">📨</i> <span>Comunicação Ativa</span>
        </a>

        <?php if(!$isEstag): ?>
        <span class="menu-label">Administração</span>
        <a href="lista_financeiro.php" class="menu-item <?php echo strpos($paginaAtual, 'financeiro') !== false ? 'active' : ''; ?>">
            <i class="menu-icon">💰</i> <span>Hub Financeiro</span>
        </a>
        <a href="gerenciar_equipe.php" class="menu-item <?php echo $paginaAtual == 'gerenciar_equipe.php' ? 'active' : ''; ?>">
            <i class="menu-icon">👨‍⚖️</i> <span>Equipe / Sócios</span>
        </a>
        <?php endif; ?>
    </div>

    <div class="sidebar-footer">
        <a href="backup.php" class="menu-item text-warning" style="padding: 10px 25px;">
            <i class="menu-icon">🛡️</i> <span>Backup Criptografado</span>
        </a>
        <a href="logout.php" class="menu-item text-danger" style="padding: 10px 25px;">
            <i class="menu-icon">🚪</i> <span>Sair da Conta</span>
        </a>
    </div>
</nav>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let topbar = document.querySelector(".topbar");
    if (topbar) {
        let titleElement = topbar.querySelector("h4");
        if(titleElement) {
            let wrapper = document.createElement("div");
            wrapper.className = "d-flex align-items-center";
            
            let btn = document.createElement("button");
            btn.innerHTML = "☰";
            btn.className = "d-lg-none border-0 bg-transparent text-dark me-3";
            btn.style.fontSize = "1.8rem";
            btn.style.cursor = "pointer";
            btn.onclick = toggleSidebar;
            
            topbar.insertBefore(wrapper, topbar.firstChild);
            wrapper.appendChild(btn);
            wrapper.appendChild(titleElement);
        }
    }
});

function toggleSidebar() {
    document.getElementById('sidebarMain').classList.toggle('show');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
</script>