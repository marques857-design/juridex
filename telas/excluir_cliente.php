<?php
// Ficheiro: telas/excluir_cliente.php
// Onde guardar: Dentro da pasta 'telas'

session_start();
if (!isset($_SESSION['id_usuario_logado'])) { 
    header("Location: login.php"); 
    exit; 
}

if (isset($_GET['id'])) {
    $idAdvogado = $_SESSION['id_usuario_logado'];
    $arquivoClientes = '../dados/Clientes_' . $idAdvogado . '.json';
    
    if (file_exists($arquivoClientes)) {
        $lista = json_decode(file_get_contents($arquivoClientes), true) ?? [];
        $novaLista = [];
        
        // Copia todos os clientes, MENOS o que queremos excluir
        foreach ($lista as $c) {
            if ($c['id'] != $_GET['id']) {
                $novaLista[] = $c;
            }
        }
        
        // Guarda o ficheiro atualizado
        file_put_contents($arquivoClientes, json_encode($novaLista, JSON_PRETTY_PRINT));
    }
}

// Volta para a lista com uma mensagem de sucesso
header("Location: lista_clientes.php?msg=excluido");
exit;
?>