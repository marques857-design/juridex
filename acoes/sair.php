<?php
// Arquivo: acoes/sair.php
// Onde salvar: Dentro da pasta 'acoes' do seu projeto

/**
 * O que faz: Encerra a sessão do utilizador de forma segura.
 * Por que fazemos: Para garantir que ninguém usa o computador depois dele e aceda aos processos.
 */

session_start(); // Inicia a sessão para podermos apagar os dados dela

// Apaga todas as informações (nome, email, id) que estavam no crachá
session_unset(); 

// Destrói a sessão por completo no servidor
session_destroy(); 

// Redireciona o utilizador de volta para a porta da rua (Tela de Login)
header("Location: ../telas/login.php");
exit;
?>