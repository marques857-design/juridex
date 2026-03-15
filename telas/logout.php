<?php
// Arquivo: telas/logout.php
// Função: Encerrar a sessão do usuário com segurança e voltar para o login.

session_start();

// Limpa todas as variáveis de sessão
session_unset();

// Destrói a sessão completamente
session_destroy();

// Redireciona de volta para a porta de entrada
header("Location: login.php");
exit;
?>