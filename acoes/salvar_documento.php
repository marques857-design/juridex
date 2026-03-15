<?php
// Arquivo: acoes/salvar_documento.php
// Pasta: acoes

session_start();

if (!isset($_SESSION['id_usuario_logado'])) {
    die("Acesso negado.");
}

function gerarNomeDoArquivoDeRegistroDeDocumentos() {
    return "../dados/Documentos_" . date('Y') . "_" . date('m') . ".json";
}

function buscarListaDeDocumentosRegistrados($caminho) {
    if (!file_exists($caminho)) return [];
    return json_decode(file_get_contents($caminho), true);
}

function salvarRegistroDeDocumentoNoJson($caminho, $lista) {
    file_put_contents($caminho, json_encode($lista, JSON_PRETTY_PRINT));
}

// =========================================================================
// PROCESSAMENTO DO UPLOAD DO ARQUIVO
// =========================================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $idDoAdvogado = $_SESSION['id_usuario_logado'];
    $idProcesso = $_POST['id_processo_vinculado'];
    $tituloDocumento = $_POST['titulo_documento'];

    // 1. O PHP guarda os arquivos recebidos numa variável especial chamada $_FILES
    $arquivoRecebido = $_FILES['arquivo_pdf'];

    // 2. Segurança: Verificar se o arquivo chegou sem erros
    if ($arquivoRecebido['error'] === UPLOAD_ERR_OK) {
        
        // Pega a extensão real do arquivo (ex: "pdf")
        $extensao = strtolower(pathinfo($arquivoRecebido['name'], PATHINFO_EXTENSION));

        // Segurança 2: Bloquear hackers que tentem enviar arquivos .exe ou .php disfarçados
        if ($extensao != "pdf") {
            die("Erro de Segurança: O sistema aceita apenas arquivos no formato PDF.");
        }

        // 3. Gerar um nome único para o arquivo físico (para não sobreescrever um com o mesmo nome)
        // Ex: documento_65f1a2b3c4.pdf
        $nomeUnicoDoArquivo = "doc_" . uniqid() . ".pdf";
        
        // 4. Onde a gaveta está?
        $caminhoDaGavetaFisica = "../uploads/" . $nomeUnicoDoArquivo;

        // 5. O GRANDE COMANDO DE UPLOAD: Move o arquivo do pacote temporário do PHP para a nossa Gaveta
        if (move_uploaded_file($arquivoRecebido['tmp_name'], $caminhoDaGavetaFisica)) {
            
            // SE O UPLOAD DEU CERTO, ANOTAMOS NO JSON O NOME DO ARQUIVO!
            $novoRegistroDeDocumento = [
                "id_documento" => uniqid(),
                "id_advogado_responsavel" => $idDoAdvogado,
                "id_processo_vinculado" => $idProcesso,
                "titulo" => $tituloDocumento,
                "nome_arquivo_fisico" => $nomeUnicoDoArquivo, // Guardamos apenas o mapa (nome do arquivo)
                "data_upload" => date('Y-m-d H:i:s')
            ];

            $arquivoJson = gerarNomeDoArquivoDeRegistroDeDocumentos();
            $listaDeDocumentos = buscarListaDeDocumentosRegistrados($arquivoJson);
            $listaDeDocumentos[] = $novoRegistroDeDocumento;
            salvarRegistroDeDocumentoNoJson($arquivoJson, $listaDeDocumentos);

            echo "<script>
                    alert('Upload concluído! O PDF foi guardado em segurança no JURIDEX.');
                    window.location.href = '../telas/lista_processos.php';
                  </script>";

        } else {
            echo "Erro interno: Falha ao mover o arquivo para a pasta uploads.";
        }
    } else {
        echo "Erro no envio do arquivo. Tente novamente.";
    }
}
?>