<?php
// Arquivo: ia/gemini.php
// Pasta: ia

/**
 * Função de Conexão com o Google Gemini 2.5 Flash
 * ATUALIZAÇÃO: Filtro Global Antibug MAX (Remove asteriscos, hashtags e crases).
 */
function consultarInteligenciaArtificialDoGoogle($textoDoPedido) {
    
    // 1. Sua Chave API
    $chaveApi = ''; 
    $chaveApi = ''; 
    
    // 2. URL configurada para o modelo Gemini 2.5 Flash
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $chaveApi;

    $pacoteDeDados = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $textoDoPedido]
                ]
            ]
        ]
    ];

    $conexao = curl_init($url);
    curl_setopt($conexao, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($conexao, CURLOPT_POST, true);
    curl_setopt($conexao, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($conexao, CURLOPT_POSTFIELDS, json_encode($pacoteDeDados));

    $respostaDoGoogle = curl_exec($conexao);
    $codigoStatusHttp = curl_getinfo($conexao, CURLINFO_HTTP_CODE);
    curl_close($conexao);

    // --- TRATAMENTO DE ERROS E LIMPEZA GLOBAL MÁXIMA ---
    
    if ($codigoStatusHttp == 200) {
        $resultado = json_decode($respostaDoGoogle, true);
        if (isset($resultado['candidates'][0]['content']['parts'][0]['text'])) {
            
            $textoBruto = $resultado['candidates'][0]['content']['parts'][0]['text'];
            
            // =========================================================================
            // A FAXINA COMPLETA: Apaga asteriscos, hashtags de título e crases
            // =========================================================================
            $textoLimpo = str_replace(['**', '*', '####', '###', '##', '#', '`'], '', $textoBruto);
            
            // Dá uma limpada em espaços em branco sobrando no começo ou no fim
            return trim($textoLimpo);
        }
    }

    if ($codigoStatusHttp == 429) {
        return "⚠️ LIMITE ATINGIDO: Muitas petições geradas em pouco tempo. Aguarde 60 segundos.";
    }

    if ($codigoStatusHttp == 400) {
        return "❌ ERRO 400: Verifique se a Chave API está ativa no Google Cloud Console.";
    }

    return "Erro de Conexão. Código: " . $codigoStatusHttp . " - Detalhes: " . $respostaDoGoogle;
}
?>