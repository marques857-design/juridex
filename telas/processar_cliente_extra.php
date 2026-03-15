<?php
// Arquivo: telas/processar_cliente_extra.php
// Função: Salvar e Excluir Histórico e Documentos do Cliente.

session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }
$idAdvogado = $_SESSION['id_usuario_logado'];

// =========================================================================
// 1. BLOCO DE EXCLUSÃO (VIA GET)
// =========================================================================

if (isset($_GET['excluir_doc']) && isset($_GET['cliente_id'])) {
    $idDoc = $_GET['excluir_doc'];
    $idCliente = $_GET['cliente_id'];
    $arquivoDocs = '../dados/Documentos_' . $idCliente . '.json';
    
    if (file_exists($arquivoDocs)) {
        $listaDocs = json_decode(file_get_contents($arquivoDocs), true) ?? [];
        $novaLista = [];
        foreach ($listaDocs as $d) {
            if (isset($d['id_doc']) && $d['id_doc'] == $idDoc) {
                // Apaga o PDF físico da pasta
                if (file_exists($d['caminho_arquivo'])) { unlink($d['caminho_arquivo']); }
            } else {
                $novaLista[] = $d;
            }
        }
        file_put_contents($arquivoDocs, json_encode($novaLista, JSON_PRETTY_PRINT));
    }
    header("Location: perfil_cliente.php?id=" . $idCliente . "&aba=documentos");
    exit;
}

// =========================================================================
// 2. BLOCO DE CRIAÇÃO (VIA POST)
// =========================================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $acao = $_POST['acao'] ?? '';
    $idCliente = $_POST['cliente_id'] ?? '';
    
    if (empty($idCliente)) { die("Erro: ID do cliente não informado."); }

    // SALVAR HISTÓRICO DE ATENDIMENTO
    if ($acao == 'adicionar_historico') {
        $anotacao = $_POST['anotacao'];
        $arquivoHist = '../dados/Atendimentos_' . $idCliente . '.json';
        $listaHist = file_exists($arquivoHist) ? json_decode(file_get_contents($arquivoHist), true) : [];
        if(!is_array($listaHist)) $listaHist = [];
        
        $listaHist[] = [
            "id_registro" => uniqid(),
            "data_hora" => date('Y-m-d H:i:s'),
            "anotacao" => $anotacao
        ];
        file_put_contents($arquivoHist, json_encode($listaHist, JSON_PRETTY_PRINT));
        header("Location: perfil_cliente.php?id=" . $idCliente . "&aba=resumo");
        exit;
    }

    // UPLOAD DE DOCUMENTOS DO CLIENTE
    if ($acao == 'upload_documento') {
        $nomeDocumento = $_POST['nome_documento'];
        
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
            $extensao = strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION));
            $permitidas = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            
            if (in_array($extensao, $permitidas)) {
                $novoNome = uniqid() . '_cli_' . $idCliente . '.' . $extensao;
                $caminhoDestino = '../uploads/' . $novoNome;
                
                if (!is_dir('../uploads/')) { mkdir('../uploads/', 0777, true); }
                
                if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminhoDestino)) {
                    $arquivoDocs = '../dados/Documentos_' . $idCliente . '.json';
                    $listaDocs = file_exists($arquivoDocs) ? json_decode(file_get_contents($arquivoDocs), true) : [];
                    if(!is_array($listaDocs)) $listaDocs = [];
                    
                    $listaDocs[] = [
                        "id_doc" => uniqid(),
                        "nome_amigavel" => $nomeDocumento,
                        "caminho_arquivo" => $caminhoDestino,
                        "data_upload" => date('Y-m-d H:i:s'),
                        "extensao" => $extensao
                    ];
                    file_put_contents($arquivoDocs, json_encode($listaDocs, JSON_PRETTY_PRINT));
                }
            }
        }
        header("Location: perfil_cliente.php?id=" . $idCliente . "&aba=documentos");
        exit;
    }
}
?>