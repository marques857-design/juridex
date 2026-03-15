<?php
// Arquivo: telas/kanban_processos.php
// Função: Kanban Compacto de Alta Densidade (Apenas Processos Ativos)

session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }
$idAdvogado = $_SESSION['id_usuario_logado'];

$arquivoProcessos = '../dados/Processos_' . $idAdvogado . '.json';
$processos = file_exists($arquivoProcessos) ? json_decode(file_get_contents($arquivoProcessos), true) : [];
if (!is_array($processos)) $processos = [];

$fasesKanban = [
    'fase_1' => ['titulo' => '📋 Em Análise', 'cor' => '#6c757d', 'bg' => '#f8f9fa'],
    'fase_2' => ['titulo' => '📝 Inicial', 'cor' => '#0d6efd', 'bg' => '#e7f1ff'],
    'fase_3' => ['titulo' => '⚖️ Audiência', 'cor' => '#fd7e14', 'bg' => '#fff4e6'],
    'fase_4' => ['titulo' => '⏳ Prazos/Recurso', 'cor' => '#dc3545', 'bg' => '#f8d7da'],
    'fase_5' => ['titulo' => '✅ Finalizado', 'cor' => '#198754', 'bg' => '#d1e7dd']
];

$colunasProcessos = ['fase_1'=>[], 'fase_2'=>[], 'fase_3'=>[], 'fase_4'=>[], 'fase_5'=>[]];

// =========================================================================
// O CÉREBRO DO FILTRO (AQUI ESTÁ A CORREÇÃO MESTRA)
// =========================================================================
foreach ($processos as $p) {
    // Lê o status e converte para minúsculas para não falhar na leitura
    $statusProcesso = mb_strtolower(trim($p['status'] ?? 'ativo'), 'UTF-8');
    
    // TRAVA: Se for Encerrado, Arquivado ou Excluído, a lida é ignorada e NÃO vai pro Kanban!
    if (in_array($statusProcesso, ['encerrado', 'arquivado', 'excluído', 'excluido'])) {
        continue; 
    }

    $faseAtual = $p['fase_kanban'] ?? 'fase_1';
    if (!isset($colunasProcessos[$faseAtual])) { $faseAtual = 'fase_1'; }
    
    $colunasProcessos[$faseAtual][] = $p;
}

$clientesMap = [];
if (file_exists('../dados/Clientes_' . $idAdvogado . '.json')) {
    $listaC = json_decode(file_get_contents('../dados/Clientes_' . $idAdvogado . '.json'), true) ?? [];
    foreach($listaC as $c) { if(isset($c['id']) && isset($c['nome'])) { $clientesMap[$c['id']] = $c['nome']; } }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JURIDEX - Kanban Tático</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; overflow-y: hidden; }
        
        .main-content { margin-left: 280px; height: 100vh; display: flex; flex-direction: column; transition: margin-left 0.3s ease; }
        .topbar { background: #fff; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; flex-shrink: 0; gap: 15px; }
        
        /* Barra de Busca Exclusiva do Kanban */
        .kanban-search { flex-grow: 1; max-width: 500px; }
        .kanban-search input { border-radius: 20px; padding: 8px 20px; border: 1px solid #ddd; background: #f8f9fa; font-size: 0.9rem; transition: 0.3s; }
        .kanban-search input:focus { background: white; border-color: #0084ff; box-shadow: 0 0 0 3px rgba(0,132,255,0.1); outline: none; }

        /* Estrutura do Kanban */
        .kanban-wrapper { padding: 20px 30px; overflow-x: auto; display: flex; gap: 15px; flex-grow: 1; align-items: flex-start; }
        
        /* Colunas */
        .kanban-col { background: #e9ecef; border-radius: 8px; min-width: 290px; max-width: 290px; display: flex; flex-direction: column; max-height: 100%; border: 1px solid #dee2e6; flex-shrink: 0; }
        .kanban-header { padding: 12px 15px; border-radius: 8px 8px 0 0; font-weight: 800; font-size: 0.95rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid rgba(0,0,0,0.05); }
        .kanban-body { padding: 8px; overflow-y: auto; flex-grow: 1; min-height: 100px; }
        
        /* Scrollbar Interna das Colunas */
        .kanban-body::-webkit-scrollbar { width: 6px; }
        .kanban-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .kanban-body::-webkit-scrollbar-track { background: transparent; }

        /* CARTÃO COMPACTO (HIGH-DENSITY) */
        .processo-card { 
            background: white; border-radius: 6px; padding: 10px 12px; margin-bottom: 8px; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.08); cursor: grab; 
            border-left: 4px solid var(--azul-vibrante); display: flex; flex-direction: column; gap: 5px; 
            transition: transform 0.1s;
        }
        .processo-card:hover { box-shadow: 0 3px 8px rgba(0,0,0,0.1); }
        .processo-card:active { cursor: grabbing; transform: scale(0.98); }
        
        /* Linha 1: Cliente e Botão */
        .card-top { display: flex; justify-content: space-between; align-items: center; }
        .card-cliente { font-size: 0.85rem; font-weight: 800; color: #1c1f3b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 190px; }
        .btn-abrir { font-size: 0.7rem; text-decoration: none; padding: 2px 8px; background: #f0f4f8; border-radius: 4px; color: #0084ff; font-weight: bold; border: 1px solid transparent; transition: 0.2s;}
        .btn-abrir:hover { background: #0084ff; color: white; }

        /* Linha 2: Número e Ação */
        .card-bottom { display: flex; justify-content: space-between; align-items: center; }
        .card-numero { font-size: 0.7rem; font-weight: bold; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px;}
        .card-acao { font-size: 0.65rem; color: #475569; font-weight: bold; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100px; border: 1px solid #e2e8f0; }

        @media (max-width: 991px) {
            .main-content { margin-left: 0; width: 100%; }
            .topbar { flex-wrap: wrap; }
            .kanban-search { width: 100%; max-width: 100%; order: 3; margin-top: 10px;}
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h4 class="mb-0 fw-bold text-dark d-none d-md-block">Kanban Tático</h4>
        
        <div class="kanban-search">
            <input type="text" id="buscaKanban" class="form-control w-100" placeholder="🔍 Buscar por Cliente, Ação ou Nº do Processo...">
        </div>

        <a href="lista_processos.php" class="btn btn-dark btn-sm fw-bold">Ver em Lista</a>
    </div>

    <div class="kanban-wrapper">
        <?php foreach($fasesKanban as $idFase => $dadosFase): ?>
            <div class="kanban-col">
                <div class="kanban-header" style="background-color: <?php echo $dadosFase['bg']; ?>;">
                    <span style="color: <?php echo $dadosFase['cor']; ?>;"><?php echo $dadosFase['titulo']; ?></span>
                    <span class="badge bg-secondary rounded-pill badge-contador"><?php echo count($colunasProcessos[$idFase]); ?></span>
                </div>
                <div class="kanban-body sortable-col" data-fase="<?php echo $idFase; ?>">
                    <?php foreach($colunasProcessos[$idFase] as $proc): 
                        $idRealProcesso = $proc['id'] ?? ($proc['id_processo'] ?? '');
                        $nomeCli = 'S/ Cliente';
                        $idCli = $proc['cliente_id'] ?? '';
                        if(!empty($idCli) && isset($clientesMap[$idCli])) { $nomeCli = $clientesMap[$idCli]; }
                        elseif(!empty($proc['nome_cliente'])) { $nomeCli = $proc['nome_cliente']; }
                        
                        $numeroProc = !empty($proc['numero_processo']) ? $proc['numero_processo'] : 'S/ Número';
                        $tipoAcao = !empty($proc['tipo_acao']) ? $proc['tipo_acao'] : 'Ação';
                    ?>
                        <div class="processo-card" data-id="<?php echo htmlspecialchars($idRealProcesso); ?>" style="border-left-color: <?php echo $dadosFase['cor']; ?>;">
                            
                            <div class="card-top">
                                <div class="card-cliente" title="<?php echo htmlspecialchars($nomeCli); ?>">
                                    👤 <?php echo htmlspecialchars($nomeCli); ?>
                                </div>
                                <a href="perfil_processo.php?id=<?php echo htmlspecialchars($idRealProcesso); ?>" class="btn-abrir" title="Abrir Processo">Abrir ↗</a>
                            </div>

                            <div class="card-bottom">
                                <div class="card-numero" title="<?php echo htmlspecialchars($numeroProc); ?>">
                                    <?php echo htmlspecialchars($numeroProc); ?>
                                </div>
                                <div class="card-acao" title="<?php echo htmlspecialchars($tipoAcao); ?>">
                                    <?php echo htmlspecialchars($tipoAcao); ?>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // 1. MOTOR DE ARRASTAR E SOLTAR (SORTABLE.JS)
    const colunas = document.querySelectorAll('.sortable-col');
    colunas.forEach(coluna => {
        new Sortable(coluna, {
            group: 'processos', 
            animation: 150, 
            delay: 50, 
            delayOnTouchOnly: true, 
            ghostClass: 'bg-light',
            onEnd: function (evt) {
                const itemEl = evt.item;
                const idProcesso = itemEl.getAttribute('data-id');
                const novaFase = evt.to.getAttribute('data-fase');
                const corNovaColuna = evt.to.parentElement.querySelector('.kanban-header span').style.color;
                
                // Muda a cor da bordinha do cartão
                itemEl.style.borderLeftColor = corNovaColuna;

                // Envia para o servidor invisivelmente
                fetch('atualizar_kanban.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id_processo=' + encodeURIComponent(idProcesso) + '&nova_fase=' + encodeURIComponent(novaFase)
                }).then(() => {
                    atualizarContadores();
                });
            }
        });
    });

    // 2. MOTOR DE BUSCA EM TEMPO REAL
    const inputBusca = document.getElementById('buscaKanban');
    inputBusca.addEventListener('keyup', function() {
        let termoPesquisa = this.value.toLowerCase().trim();
        let cartoes = document.querySelectorAll('.processo-card');
        
        cartoes.forEach(cartao => {
            let conteudoCartao = cartao.innerText.toLowerCase();
            if (conteudoCartao.includes(termoPesquisa)) {
                cartao.style.display = 'flex'; 
            } else {
                cartao.style.display = 'none'; 
            }
        });
        
        atualizarContadores();
    });

    // 3. ATUALIZA AS BOLINHAS DE CONTAGEM
    function atualizarContadores() {
        document.querySelectorAll('.kanban-col').forEach(col => {
            let visiveis = Array.from(col.querySelectorAll('.processo-card')).filter(el => el.style.display !== 'none').length;
            col.querySelector('.badge-contador').textContent = visiveis;
        });
    }
});
</script>
</body>
</html>