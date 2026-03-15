<?php
// Arquivo: telas/processar_processo_extra.php
// Função: Salvar e Excluir andamentos, prazos e documentos dentro da Ficha do Processo.

session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

if (!isset($_SESSION['id_usuario_logado'])) { header("Location: login.php"); exit; }
$idAdvogado = $_SESSION['id_usuario_logado'];

// =========================================================================
// BLOCO DE EXCLUSÃO (VIA GET)
// =========================================================================

// 1. EXCLUIR DOCUMENTO (E apagar o arquivo físico)
if (isset($_GET['excluir_doc']) && isset($_GET['processo_id'])) {
    $idDoc = $_GET['excluir_doc'];
    $idProcesso = $_GET['processo_id'];
    $arquivoDocs = '../dados/Documentos_Processo_' . $idProcesso . '.json';
    
    if (file_exists($arquivoDocs)) {
        $listaDocs = json_decode(file_get_contents($arquivoDocs), true) ?? [];
        $novaLista = [];
        foreach ($listaDocs as $d) {
            if (isset($d['id_doc']) && $d['id_doc'] == $idDoc) {
                // Tenta apagar o PDF real da pasta uploads para liberar espaço
                if (file_exists($d['caminho_arquivo'])) { unlink($d['caminho_arquivo']); }
            } else {
                $novaLista[] = $d;
            }
        }
        file_put_contents($arquivoDocs, json_encode($novaLista, JSON_PRETTY_PRINT));
    }
    header("Location: perfil_processo.php?id=" . $idProcesso . "&aba=documentos");
    exit;
}

// 2. EXCLUIR ANDAMENTO
if (isset($_GET['excluir_andamento']) && isset($_GET['processo_id'])) {
    $idAndamento = $_GET['excluir_andamento'];
    $idProcesso = $_GET['processo_id'];
    $arqAndamentos = '../dados/Andamentos_Processo_' . $idProcesso . '.json';
    
    if (file_exists($arqAndamentos)) {
        $lista = json_decode(file_get_contents($arqAndamentos), true) ?? [];
        $novaLista = [];
        foreach ($lista as $a) {
            if (isset($a['id_andamento']) && $a['id_andamento'] != $idAndamento) { $novaLista[] = $a; }
        }
        file_put_contents($arqAndamentos, json_encode($novaLista, JSON_PRETTY_PRINT));
    }
    header("Location: perfil_processo.php?id=" . $idProcesso . "&aba=andamentos");
    exit;
}

// 3. EXCLUIR PRAZO/AUDIÊNCIA
if (isset($_GET['excluir_prazo']) && isset($_GET['processo_id'])) {
    $idPrazo = $_GET['excluir_prazo'];
    $idProcesso = $_GET['processo_id'];
    
    // Procura em todas as agendas para apagar o evento
    foreach (glob('../dados/Agenda_*.json') as $arq) {
        $listaAg = json_decode(file_get_contents($arq), true) ?? [];
        $novaLista = [];
        $alterou = false;
        foreach ($listaAg as $ag) {
            if (isset($ag['id_evento']) && $ag['id_evento'] == $idPrazo) {
                $alterou = true; // Achou e vai pular (excluir)
            } else {
                $novaLista[] = $ag;
            }
        }
        if ($alterou) { file_put_contents($arq, json_encode($novaLista, JSON_PRETTY_PRINT)); }
    }
    header("Location: perfil_processo.php?id=" . $idProcesso . "&aba=prazos");
    exit;
}


// =========================================================================
// BLOCO DE CRIAÇÃO / UPLOAD (VIA POST)
// =========================================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $acao = $_POST['acao'] ?? '';
    $idProcesso = $_POST['processo_id'] ?? '';
    if (empty($idProcesso)) { die("Erro: ID do processo não informado."); }

    // SALVAR NOVO ANDAMENTO PROCESSUAL
    if ($acao == 'novo_andamento') {
        $dataAndamento = $_POST['data_andamento'];
        $descricao = $_POST['descricao_andamento'];
        
        $arquivoAndamentos = '../dados/Andamentos_Processo_' . $idProcesso . '.json';
        $lista = file_exists($arquivoAndamentos) ? json_decode(file_get_contents($arquivoAndamentos), true) : [];
        if(!is_array($lista)) $lista = [];
        
        $lista[] = [
            "id_andamento" => uniqid(),
            "data_andamento" => $dataAndamento,
            "descricao" => $descricao,
            "data_registro" => date('Y-m-d H:i:s')
        ];
        file_put_contents($arquivoAndamentos, json_encode($lista, JSON_PRETTY_PRINT));
        header("Location: perfil_processo.php?id=" . $idProcesso . "&aba=andamentos");
        exit;
    }

    // SALVAR NOVO PRAZO
    if ($acao == 'novo_prazo') {
        $arquivoAgenda = '../dados/Agenda_' . date('Y_m') . '.json';
        $listaAgenda = file_exists($arquivoAgenda) ? json_decode(file_get_contents($arquivoAgenda), true) : [];
        if(!is_array($listaAgenda)) $listaAgenda = [];
        
        $listaAgenda[] = [
            "id_evento" => uniqid(),
            "id_advogado_responsavel" => $idAdvogado,
            "processo_id" => $idProcesso,
            "titulo" => $_POST['titulo_prazo'],
            "data_evento" => $_POST['data_limite'],
            "hora_evento" => $_POST['hora_limite'] ?? '23:59',
            "tipo_evento" => $_POST['tipo_prazo'],
            "descricao" => $_POST['descricao_prazo'] ?? '',
            "status" => "Pendente",
            "data_cadastro" => date('Y-m-d H:i:s')
        ];
        file_put_contents($arquivoAgenda, json_encode($listaAgenda, JSON_PRETTY_PRINT));
        header("Location: perfil_processo.php?id=" . $idProcesso . "&aba=prazos");
        exit;
    }

    // UPLOAD DE DOCUMENTOS DO PROCESSO
    if ($acao == 'upload_documento') {
        $nomeDocumento = $_POST['nome_documento'];
        
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
            $extensao = strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION));
            $permitidas = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            
            if (in_array($extensao, $permitidas)) {
                $novoNome = uniqid() . '_proc_' . $idProcesso . '.' . $extensao;
                $caminhoDestino = '../uploads/' . $novoNome;
                if (!is_dir('../uploads/')) { mkdir('../uploads/', 0777, true); }
                
                if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminhoDestino)) {
                    $arquivoDocs = '../dados/Documentos_Processo_' . $idProcesso . '.json';
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
        header("Location: perfil_processo.php?id=" . $idProcesso . "&aba=documentos");
        exit;
    }
}
header("Location: lista_processos.php");
exit;
?>