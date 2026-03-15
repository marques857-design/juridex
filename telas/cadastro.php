<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JURIDEX - Cadastro de Advogado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-header bg-dark text-white text-center py-3">
                    <h2>JURIDEX</h2>
                    <small>Cadastro de Novo Advogado</small>
                </div>
                <div class="card-body p-4">
                    
                    <form action="../acoes/salvar_usuario.php" method="POST">
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="nome" name="nome" required placeholder="Ex: Dra. Maria Silva">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail Profissional</label>
                            <input type="email" class="form-control" id="email" name="email" required placeholder="advogado@escritorio.com">
                        </div>

                        <div class="mb-4">
                            <label for="senha" class="form-label">Senha de Acesso (Mínimo 6 caracteres)</label>
                            <input type="password" class="form-control" id="senha" name="senha" minlength="6" required placeholder="******">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Criar Conta no JURIDEX</button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>