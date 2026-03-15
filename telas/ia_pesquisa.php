<?php
// Arquivo: telas/ia_pesquisa.php
// Onde salvar: Dentro da pasta 'telas'

session_start();

// Proteção do sistema
if (!isset($_SESSION['id_usuario_logado'])) { 
    header("Location: login.php"); 
    exit; 
}

// Importa a inteligência do Gemini
require_once '../ia/gemini.php';

$respostaIA = "";
$termoPesquisado = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $termoPesquisado = $_POST['termo_pesquisa'];
    
    // =========================================================================
    // O PROMPT DA PESQUISA: Como a IA deve atuar como um buscador jurídico
    // =========================================================================
    $instrucaoDoSistema = "Você é um motor de busca jurídico avançado, especializado em Direito Brasileiro (estilo Jusbrasil/STJ). ";
    $instrucaoDoSistema .= "O advogado vai digitar um tema. Você deve realizar uma pesquisa profunda e devolver os resultados estruturados da seguinte forma:\n\n";
    $instrucaoDoSistema .= "1) TESE JURÍDICA PRINCIPAL (Explique o direito envolvido de forma clara);\n";
    $instrucaoDoSistema .= "2) LEGISLAÇÃO E SÚMULAS APLICÁVEIS (Cite artigos de lei, Súmulas do STJ/STF aplicáveis ao tema);\n";
    $instrucaoDoSistema .= "3) TENDÊNCIA JURISPRUDENCIAL (Explique como os tribunais brasileiros têm decidido casos semelhantes atualmente);\n";
    $instrucaoDoSistema .= "4) ARGUMENTO DE OURO (Uma dica de mestre sobre o que não pode faltar na petição para ganhar o caso).\n\n";
    $instrucaoDoSistema .= "TEMA PESQUISADO PELO ADVOGADO:\n" . $termoPesquisado;

    // Envia para o Google e aguarda
    $respostaIA = consultarInteligenciaArtificialDoGoogle($instrucaoDoSistema);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JURIDEX - Pesquisa Jurídica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .ai-header { background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); color: #fff; }
        .caixa-resposta { background-color: #fff; border: 1px solid #e0e0e0; border-left: 5px solid #6a11cb; font-family: "Segoe UI", Arial, sans-serif; white-space: pre-wrap; font-size: 15px;}
        .search-box { background-color: #f8f9fa; border-radius: 10px; padding: 30px; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-info" href="central_ia.php">🧠 JURIDEX NEURAL</a>
        <div class="d-flex">
            <a href="central_ia.php" class="btn btn-outline-light btn-sm">Voltar para a Central</a>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    
    <div class="text-center mb-4">
        <h2 class="fw-bold" style="color: #6a11cb;">⚖️ Pesquisa Jurídica Inteligente</h2>
        <p class="text-muted">Encontre teses, súmulas e tendências dos tribunais em segundos.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-10">
            
            <div class="search-box shadow-sm mb-5 border">
                <form method="POST" action="ia_pesquisa.php">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-primary"><h4 class="mb-0">🔎</h4></span>
                        <input type="text" class="form-control border-primary" name="termo_pesquisa" placeholder="Ex: Revisão de aposentadoria rural por idade..." value="<?php echo htmlspecialchars($termoPesquisado); ?>" required>
                        <button type="submit" class="btn btn-primary fw-bold px-4" style="background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); border: none;">
                            Pesquisar
                        </button>
                    </div>
                </form>
            </div>

            <?php if (!empty($respostaIA)) { ?>
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="fw-bold mb-0" style="color: #2575fc;">Resultados encontrados para: "<em><?php echo htmlspecialchars($termoPesquisado); ?></em>"</h5>
                    </div>
                    <div class="card-body p-4 bg-light">
                        <div class="caixa-resposta p-4 shadow-sm rounded">
                            <?php echo htmlspecialchars($respostaIA); ?>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button class="btn btn-outline-primary fw-bold shadow-sm" onclick="alert('Pesquisa copiada! Cole no seu banco de teses.')">
                                📋 Copiar Resultado da Pesquisa
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-secondary mt-4 text-center">
                    <small><strong>Aviso Legal:</strong> A IA fornece resumos baseados em dados de treinamento. Sempre confira a vigência das leis e súmulas nos sites oficiais do planalto ou tribunais antes de protocolar a ação.</small>
                </div>
            <?php } ?>

        </div>
    </div>
</div>

</body>
</html>