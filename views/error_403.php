<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Negado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .error-container {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 500px;
        }
        .error-code {
            font-size: 72px;
            font-weight: bold;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 24px;
            margin-bottom: 15px;
            color: #333;
        }
        .error-message {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 10px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #545b62;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">403</div>
        <h1 class="error-title">Acesso Negado</h1>
        <p class="error-message">
            Você não tem permissão para acessar esta página ou recurso.
            <br>
            Entre em contato com o administrador se acredita que isso é um erro.
        </p>
        
        <div>
            <a href="javascript:history.back()" class="btn btn-secondary">Voltar</a>
            <a href="/modules/dashboard/" class="btn">Dashboard</a>
        </div>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
        
        <small style="color: #999;">
            Usuário: <?= htmlspecialchars($_SESSION['user_email'] ?? 'Desconhecido') ?><br>
            Role: <?= htmlspecialchars($_SESSION['user_role'] ?? 'Não definido') ?>
        </small>
    </div>
</body>
</html>