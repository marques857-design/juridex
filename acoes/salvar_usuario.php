<?php
// Arquivo: acoes/salvar_usuario.php
// Onde salvar: Dentro da pasta 'acoes' do seu projeto

/**
 * Função para ler o nosso banco de dados JSON e transformar em uma lista para o PHP.
 */
function buscarListaDeUsuariosCadastrados() {
    $caminhoDoArquivo = '../dados/usuarios.json';
    
    // Lê o texto do arquivo
    $textoDoArquivo = file_get_contents($caminhoDoArquivo);
    
    // Transforma o texto em uma lista (array) e devolve
    return json_decode($textoDoArquivo, true);
}

/**
 * Função para salvar a lista com o novo usuário de volta no arquivo.
 */
function salvarListaAtualizadaNoBancoDeDados($listaAtualizada) {
    $caminhoDoArquivo = '../dados/usuarios.json';
    
    // Transforma a lista do PHP de volta para texto JSON organizado
    $textoJson = json_encode($listaAtualizada, JSON_PRETTY_PRINT);
    
    // Salva por cima do arquivo antigo
    file_put_contents($caminhoDoArquivo, $textoJson);
}

// =========================================================================
// INÍCIO DA LÓGICA PRINCIPAL
// =========================================================================

// Verifica se os dados realmente vieram do formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Pegamos os dados que a pessoa digitou na tela
    $nomeDigitado = $_POST['nome'];
    $emailDigitado = $_POST['email'];
    $senhaDigitada = $_POST['senha'];

    // 2. REGRA DE SEGURANÇA: Criptografamos a senha!
    // Transforma "123456" em um código maluco como "$2y$10$xyz..."
    $senhaCriptografada = password_hash($senhaDigitada, PASSWORD_DEFAULT);

    // 3. Montamos o "pacote" com os dados do novo advogado
    $novoAdvogado = [
        "id_usuario" => uniqid(), // Gera um código único para ele
        "nome" => $nomeDigitado,
        "email" => $emailDigitado,
        "senha_hash" => $senhaCriptografada, // Salvamos apenas o código maluco, NUNCA a senha real
        "data_cadastro" => date('Y-m-d H:i:s')
    ];

    // 4. Puxamos a lista antiga
    $listaDeUsuarios = buscarListaDeUsuariosCadastrados();

    // 5. Adicionamos o novo advogado no final da lista
    $listaDeUsuarios[] = $novoAdvogado;

    // 6. Salvamos a lista nova no arquivo
    salvarListaAtualizadaNoBancoDeDados($listaDeUsuarios);

    // 7. Mostramos uma mensagem de sucesso na tela!
    echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Sucesso</title></head>";
    echo "<body style='font-family: Arial; text-align: center; padding: 50px; background-color: #f4f7f6;'>";
    echo "<h1 style='color: #27ae60;'>Conta Criada com Sucesso!</h1>";
    echo "<p>O(A) advogado(a) <strong>" . htmlspecialchars($nomeDigitado) . "</strong> já faz parte do JURIDEX.</p>";
    echo "<p>Volte no seu Visual Studio Code e abra a pasta <b>dados/usuarios.json</b> para ver a mágica e a senha criptografada!</p>";
    echo "</body></html>";

} else {
    echo "Acesso inválido.";
}
?>