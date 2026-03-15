<?php
// Arquivo: acoes/fazer_login.php
// Onde salvar: Dentro da pasta 'acoes' do seu projeto

// A primeira regra de um login: LIGAR O SISTEMA DE CRACHÁS!
session_start();

/**
 * Função para ler os advogados registados no nosso ficheiro JSON.
 */
function buscarTodosOsUsuariosDoBancoDeDados() {
    $caminhoDoArquivo = '../dados/usuarios.json';
    
    // Se o ficheiro não existir, não há ninguém registado ainda
    if (!file_exists($caminhoDoArquivo)) {
        return [];
    }
    
    $conteudo = file_get_contents($caminhoDoArquivo);
    return json_decode($conteudo, true);
}

// =========================================================================
// INÍCIO DA LÓGICA DE LOGIN
// =========================================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Apanhamos o que o utilizador digitou na tela de login
    $emailDigitado = $_POST['email'];
    $senhaDigitada = $_POST['senha'];

    // 2. Trazemos a lista de todos os advogados
    $listaDeUsuarios = buscarTodosOsUsuariosDoBancoDeDados();
    $utilizadorEncontrado = false; // Começamos a assumir que ele não existe

    // 3. Vamos olhar para cada advogado na nossa lista, um por um
    foreach ($listaDeUsuarios as $usuario) {
        
        // Se o e-mail que estamos a ver na lista for igual ao digitado...
        if ($usuario['email'] == $emailDigitado) {
            
            // 4. Verificamos a palavra-passe! 
            // A função password_verify compara a senha digitada com o código maluco (hash) guardado.
            if (password_verify($senhaDigitada, $usuario['senha_hash'])) {
                
                // SUCESSO! A palavra-passe está correta.
                $utilizadorEncontrado = true;
                
                // 5. CRIAR O CRACHÁ (SESSÃO)! Guardamos quem ele é para o sistema se lembrar.
                $_SESSION['id_usuario_logado'] = $usuario['id_usuario'];
                $_SESSION['nome_usuario_logado'] = $usuario['nome'];
                $_SESSION['email_usuario_logado'] = $usuario['email'];
                
                // 6. Abrimos a porta e mandamos ele para o Painel!
                header("Location: ../telas/painel.php");
                exit; // Paramos de ler este ficheiro aqui
            }
        }
    }

    // 7. Se o código chegou aqui, é porque ou o e-mail não existe, ou a senha estava errada.
    if ($utilizadorEncontrado == false) {
        echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Erro</title>";
        echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'></head>";
        echo "<body class='bg-light'><div class='container mt-5 text-center'>";
        echo "<h1 class='text-danger'>Erro de Autenticação!</h1>";
        echo "<p class='lead'>O e-mail ou a palavra-passe estão incorretos.</p>";
        echo "<a href='../telas/login.php' class='btn btn-warning mt-3'>Tentar Novamente</a>";
        echo "</div></body></html>";
    }

} else {
    echo "Acesso inválido.";
}
?>