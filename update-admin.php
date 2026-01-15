<?php
/**
 * Script de Atualização de Senha do Admin
 *
 * INSTRUÇÕES:
 * 1. Acesse este arquivo pelo navegador: http://seu-dominio.com/update-admin.php
 * 2. Aguarde a mensagem de sucesso
 * 3. DELETE este arquivo imediatamente por segurança!
 */

// Incluir conexão com banco
require_once __DIR__ . '/core/db.php';

// ==================================
// CONFIGURE AQUI AS NOVAS CREDENCIAIS
// ==================================
$novoNome = "webmaster";
$novoEmail = "luiz@pachella.com.br";
$novaSenha = "Pach1020$";
// ==================================

try {
    // Gerar hash correto da senha
    $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

    // Deletar admin antigo (se existir)
    $pdo->exec("DELETE FROM users WHERE role = 'admin'");

    // Criar novo admin com hash correto
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role, plan, status, created_at)
        VALUES (:name, :email, :password, 'admin', 'FULL', 'active', NOW())
    ");

    $stmt->execute([
        'name' => $novoNome,
        'email' => $novoEmail,
        'password' => $senhaHash
    ]);

    $sucesso = true;

} catch (PDOException $e) {
    $erro = $e->getMessage();
    $sucesso = false;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualização de Admin - Fluxo365</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-purple-600 to-blue-600 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full">

        <?php if ($sucesso): ?>
            <!-- SUCESSO -->
            <div class="text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-800 mb-2">✓ Admin Atualizado!</h1>
                <p class="text-gray-600 mb-6">As credenciais foram atualizadas com sucesso.</p>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 text-left">
                    <h3 class="font-semibold text-blue-800 mb-2">Novas Credenciais:</h3>
                    <div class="space-y-1 text-sm">
                        <p><strong>Nome:</strong> <?= htmlspecialchars($novoNome) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($novoEmail) ?></p>
                        <p><strong>Senha:</strong> <?= htmlspecialchars($novaSenha) ?></p>
                    </div>
                </div>

                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <p class="text-sm text-red-800">
                        <strong>⚠️ IMPORTANTE:</strong><br>
                        Delete este arquivo AGORA por segurança!<br>
                        <code class="bg-red-100 px-2 py-1 rounded mt-1 inline-block">update-admin.php</code>
                    </p>
                </div>

                <a href="/auth/login.php" class="block w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white py-3 rounded-lg font-medium hover:from-purple-700 hover:to-blue-700 transition-all">
                    Fazer Login Agora
                </a>
            </div>

        <?php else: ?>
            <!-- ERRO -->
            <div class="text-center">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-800 mb-2">✗ Erro na Atualização</h1>
                <p class="text-gray-600 mb-4">Não foi possível atualizar as credenciais.</p>

                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-left">
                    <h3 class="font-semibold text-red-800 mb-2">Erro:</h3>
                    <p class="text-sm text-red-700 font-mono"><?= htmlspecialchars($erro) ?></p>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-left">
                    <h3 class="font-semibold text-yellow-800 mb-2">Possíveis soluções:</h3>
                    <ul class="text-sm text-yellow-700 space-y-1">
                        <li>• Verifique se o banco de dados existe</li>
                        <li>• Confirme as credenciais em /core/db.php</li>
                        <li>• Verifique se a tabela 'users' foi criada</li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
