<?php
session_start();
if (!isset($_SESSION['id_usuario_logado'])) { die("Acesso negado"); }

$idAdvogado = $_SESSION['id_usuario_logado'];
$arquivoProcessos = '../dados/Processos_' . $idAdvogado . '.json';

if (isset($_POST['id_processo']) && isset($_POST['nova_fase'])) {
    $processos = file_exists($arquivoProcessos) ? json_decode(file_get_contents($arquivoProcessos), true) : [];
    if (!is_array($processos)) $processos = [];
    
    $modificado = false;
    foreach ($processos as $key => $p) {
        $idAtual = $p['id'] ?? ($p['id_processo'] ?? '');
        if ($idAtual == $_POST['id_processo']) {
            $processos[$key]['fase_kanban'] = $_POST['nova_fase'];
            $modificado = true; break;
        }
    }
    
    if ($modificado) { file_put_contents($arquivoProcessos, json_encode(array_values($processos), JSON_PRETTY_PRINT)); echo "Salvo"; }
}
?>