<?php
// Arquivo: telas/central_ia.php
// Função: Hub de acesso às ferramentas de JURIDEX Intelligence.

session_start();
if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }

$nomeAdvogado = $_SESSION['nome_usuario'] ?? 'Doutor(a)';
$cargoUsuario = $_SESSION['cargo'] ?? 'Advogado';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JURIDEX Intelligence</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .main-content { margin-left: 280px; min-height: 100vh; display: flex; flex-direction: column; }
        .topbar { background: #fff; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; }
        
        .ai-card { border: none; border-radius: 12px; padding: 30px; transition: 0.3s; color: white; cursor: pointer; text-decoration: none; display: flex; flex-direction: column; justify-content: center; box-shadow: 0 5px 15px rgba(0,0,0,0.1); height: 100%; position: relative; overflow: hidden; }
        .ai-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.15); color: white; }
        .ai-card::after { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: rgba(255,255,255,0.05); transform: rotate(45deg); pointer-events: none; }
        
        .bg-peticao { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); }
        .bg-estrategia { background: linear-gradient(135deg, #8A2387 0%, #E94057 100%); }
        .bg-analise { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        
        /* Novas Cores Premium */
        .bg-auditor { background: linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%); }
        .bg-audiencia { background: linear-gradient(135deg, #232526 0%, #414345 100%); }
        
        .ai-icon { font-size: 3rem; margin-bottom: 15px; }
        .ai-title { font-weight: 800; font-size: 1.3rem; margin-bottom: 10px; line-height: 1.2; }
        .ai-desc { font-size: 0.9rem; opacity: 0.85; line-height: 1.4; }
        
        .badge-pro { position: absolute; top: 15px; right: 15px; background: rgba(0,0,0,0.3); padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; letter-spacing: 1px; border: 1px solid rgba(255,255,255,0.2); }
        
        @media (max-width: 991px) { .main-content { margin-left: 0; width: 100%; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h4 class="mb-0 fw-bold text-dark">JURIDEX Intelligence <span class="badge bg-warning text-dark ms-2 align-middle" style="font-size: 0.6em;">PRO</span></h4>
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold text-dark">Olá, <?php echo htmlspecialchars($nomeAdvogado); ?></span>
        </div>
    </div>

    <div class="container-fluid px-4 mb-5">
        
        <div class="mb-5 text-center">
            <h2 class="fw-bold text-dark mb-2">Poder Analítico e Estratégico ao seu dispor.</h2>
            <p class="text-muted fs-5">Aumente a sua produtividade e blinde o seu escritório contra erros utilizando a nossa IA treinada com Doutrina e Jurisprudência.</p>
        </div>

        <div class="row g-4 mb-4">
            
            <div class="col-md-6 col-lg-4">
                <a href="gerador_peticao_ia.php" class="ai-card bg-peticao">
                    <span class="badge-pro">BÁSICO</span>
                    <div class="ai-icon">📄</div>
                    <div class="ai-title">Gerador de Petições</div>
                    <div class="ai-desc">Cruza os fatos do cliente com a legislação e gera a peça inicial ou defesa pré-preenchida em segundos.</div>
                </a>
            </div>

            <div class="col-md-6 col-lg-4">
                <a href="ia_estrategia.php" class="ai-card bg-estrategia">
                    <span class="badge-pro">CORE</span>
                    <div class="ai-icon">♟️</div>
                    <div class="ai-title">Arquiteto Estratégico</div>
                    <div class="ai-desc">Fornece o Caminho das Pedras: teses principais, checklist de provas e estruturação de pedidos.</div>
                </a>
            </div>

            <div class="col-md-6 col-lg-4">
                <a href="ia_analisador.php" class="ai-card bg-analise">
                    <div class="ai-icon">🔎</div>
                    <div class="ai-title">Tradutor de Sentenças</div>
                    <div class="ai-desc">Cole despachos confusos e a IA resume o que o juiz determinou em linguagem acessível para o cliente.</div>
                </a>
            </div>

            <div class="col-md-6">
                <a href="ia_auditor.php" class="ai-card bg-auditor">
                    <span class="badge-pro">NOVO 🔥</span>
                    <div class="ai-icon">⚖️</div>
                    <div class="ai-title">Auditor de Peças</div>
                    <div class="ai-desc">Vai protocolar uma petição? Cole-a aqui primeiro. A IA assumirá a postura do advogado adversário, caçando brechas, teses fracas e falhas argumentativas na sua peça antes do juiz ver.</div>
                </a>
            </div>

            <div class="col-md-6">
                <a href="ia_simulador_audiencia.php" class="ai-card bg-audiencia">
                    <span class="badge-pro">NOVO 💎</span>
                    <div class="ai-icon">🎙️</div>
                    <div class="ai-title">Simulador de Audiências</div>
                    <div class="ai-desc">Descreva o caso e a IA irá prever as perguntas mais difíceis que o Juiz ou o Ministério Público farão ao seu cliente, orientando a melhor forma de responder.</div>
                </a>
            </div>

        </div>
        
        <div class="alert alert-light border shadow-sm text-center p-4 rounded-3 mt-4">
            <h5 class="fw-bold text-dark">🔐 Sigilo Absoluto Garantido</h5>
            <p class="text-muted mb-0">Os dados submetidos à JURIDEX Intelligence são processados via API restrita e <b>nunca</b> são utilizados para treinar modelos públicos. O privilégio cliente-advogado está assegurado.</p>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>