<?php
/**
 * Script de Correção de Permissões - Fluxo365
 *
 * Execute este arquivo para corrigir automaticamente as permissões
 * dos arquivos e diretórios do sistema.
 *
 * Acesse: http://seu-dominio.com/fix-permissions.php
 *
 * IMPORTANTE: Delete este arquivo após o uso por segurança!
 */

// Configurações
$dirPermissions = 0755;  // rwxr-xr-x para diretórios
$filePermissions = 0644; // rw-r--r-- para arquivos
$execPermissions = 0755; // rwxr-xr-x para arquivos executáveis

$results = [];
$errors = [];

/**
 * Corrige permissões recursivamente
 */
function fixPermissions($path, $dirPerm, $filePerm, &$results, &$errors) {
    if (!file_exists($path)) {
        $errors[] = "Caminho não encontrado: $path";
        return;
    }

    if (is_dir($path)) {
        // Corrigir permissão do diretório
        if (@chmod($path, $dirPerm)) {
            $results[] = "✓ DIR: $path → " . decoct($dirPerm);
        } else {
            $errors[] = "✗ Falha ao modificar: $path (Precisa de sudo?)";
        }

        // Processar conteúdo do diretório
        $items = @scandir($path);
        if ($items === false) {
            $errors[] = "✗ Não foi possível ler: $path";
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $fullPath = $path . '/' . $item;
            fixPermissions($fullPath, $dirPerm, $filePerm, $results, $errors);
        }
    } else {
        // Corrigir permissão do arquivo
        if (@chmod($path, $filePerm)) {
            $results[] = "✓ FILE: $path → " . decoct($filePerm);
        } else {
            $errors[] = "✗ Falha ao modificar: $path";
        }
    }
}

// Início do processamento
$startTime = microtime(true);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correção de Permissões - Fluxo365</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .log { font-family: 'Courier New', monospace; font-size: 12px; }
    </style>
</head>
<body class="min-h-screen p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">🔧 Correção de Permissões</h1>
            <p class="text-gray-600 mb-6">Sistema Base - Fluxo365</p>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix'])): ?>

                <?php
                // Diretórios a corrigir
                $directories = [
                    __DIR__ . '/auth',
                    __DIR__ . '/core',
                    __DIR__ . '/modules',
                    __DIR__ . '/scripts',
                    __DIR__ . '/views',
                    __DIR__ . '/uploads',
                    __DIR__ . '/assets',
                ];

                // Corrigir permissões
                foreach ($directories as $dir) {
                    if (file_exists($dir)) {
                        fixPermissions($dir, $dirPermissions, $filePermissions, $results, $errors);
                    }
                }

                // Arquivos na raiz
                $rootFiles = [
                    __DIR__ . '/index.php',
                    __DIR__ . '/install.php',
                    __DIR__ . '/.htaccess',
                ];

                foreach ($rootFiles as $file) {
                    if (file_exists($file)) {
                        if (@chmod($file, $filePermissions)) {
                            $results[] = "✓ FILE: $file → " . decoct($filePermissions);
                        } else {
                            $errors[] = "✗ Falha ao modificar: $file";
                        }
                    }
                }

                // Diretório uploads precisa ser gravável
                $uploadsDir = __DIR__ . '/uploads';
                if (file_exists($uploadsDir)) {
                    @chmod($uploadsDir, 0775); // rwxrwxr-x
                    $results[] = "✓ DIR ESPECIAL: $uploadsDir → 0775 (gravável)";
                }

                $endTime = microtime(true);
                $duration = round($endTime - $startTime, 2);
                ?>

                <!-- Resultados -->
                <div class="mb-6">
                    <?php if (count($errors) > 0): ?>
                        <div class="bg-red-100 border border-red-400 rounded-lg p-4 mb-4">
                            <h3 class="font-bold text-red-800 mb-2">⚠️ Erros Encontrados (<?= count($errors) ?>)</h3>
                            <div class="log text-red-700 max-h-48 overflow-y-auto">
                                <?php foreach ($errors as $error): ?>
                                    <div><?= htmlspecialchars($error) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="bg-green-100 border border-green-400 rounded-lg p-4 mb-4">
                        <h3 class="font-bold text-green-800 mb-2">✓ Arquivos Corrigidos (<?= count($results) ?>)</h3>
                        <div class="log text-green-700 max-h-64 overflow-y-auto">
                            <?php foreach (array_slice($results, 0, 50) as $result): ?>
                                <div><?= htmlspecialchars($result) ?></div>
                            <?php endforeach; ?>
                            <?php if (count($results) > 50): ?>
                                <div class="mt-2 text-xs">... e mais <?= count($results) - 50 ?> arquivos</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-sm text-blue-800">
                            <strong>Concluído em <?= $duration ?>s</strong><br>
                            Total de arquivos processados: <?= count($results) ?><br>
                            Erros: <?= count($errors) ?>
                        </p>
                    </div>
                </div>

                <!-- Próximos Passos -->
                <div class="bg-gray-50 rounded-lg p-6 mb-4">
                    <h3 class="font-semibold text-gray-800 mb-3">📋 Próximos Passos:</h3>

                    <?php if (count($errors) > 0): ?>
                        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                            <p class="text-sm text-yellow-800">
                                <strong>⚠️ Alguns erros ocorreram.</strong><br>
                                Você pode precisar executar via terminal com sudo:
                            </p>
                            <pre class="mt-2 p-2 bg-gray-800 text-green-400 rounded text-xs">sudo chmod -R 755 /caminho/para/fluxo365
sudo chmod -R 775 /caminho/para/fluxo365/uploads</pre>
                        </div>
                    <?php else: ?>
                        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded">
                            <p class="text-sm text-green-800">
                                ✓ Todas as permissões foram corrigidas com sucesso!
                            </p>
                        </div>
                    <?php endif; ?>

                    <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700">
                        <li>Teste o sistema acessando: <a href="/" class="text-blue-600 hover:underline">página inicial</a></li>
                        <li>Se ainda houver problemas, verifique os logs do Apache</li>
                        <li><strong>DELETE este arquivo (fix-permissions.php) por segurança!</strong></li>
                    </ol>
                </div>

                <div class="flex gap-3">
                    <a href="/" class="flex-1 bg-blue-600 text-white py-3 rounded-lg text-center font-medium hover:bg-blue-700 transition-colors">
                        Acessar o Sistema
                    </a>
                    <button onclick="location.reload()" class="px-6 bg-gray-600 text-white py-3 rounded-lg font-medium hover:bg-gray-700 transition-colors">
                        Executar Novamente
                    </button>
                </div>

            <?php else: ?>

                <!-- Formulário Inicial -->
                <div class="space-y-4">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h3 class="font-semibold text-yellow-800 mb-2">⚠️ Quando usar este script?</h3>
                        <ul class="text-sm text-yellow-700 space-y-1">
                            <li>• Você está recebendo erro <strong>403 Forbidden</strong></li>
                            <li>• Após fazer upload dos arquivos para o servidor</li>
                            <li>• Arquivos/pastas não tem as permissões corretas</li>
                        </ul>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="font-semibold text-blue-800 mb-2">📝 O que este script fará:</h3>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li>• Diretórios: <code class="bg-blue-100 px-2 py-0.5 rounded">0755</code> (rwxr-xr-x)</li>
                            <li>• Arquivos: <code class="bg-blue-100 px-2 py-0.5 rounded">0644</code> (rw-r--r--)</li>
                            <li>• Pasta uploads: <code class="bg-blue-100 px-2 py-0.5 rounded">0775</code> (gravável)</li>
                        </ul>
                    </div>

                    <form method="POST" class="space-y-4">
                        <button type="submit" name="fix" value="1"
                                class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white py-4 rounded-lg font-medium hover:from-purple-700 hover:to-blue-700 transition-all shadow-lg text-lg">
                            🔧 Corrigir Permissões Agora
                        </button>
                    </form>

                    <p class="text-xs text-gray-500 text-center">
                        Este processo é seguro e pode ser executado quantas vezes necessário
                    </p>
                </div>

            <?php endif; ?>

        </div>

        <div class="text-center mt-6 text-white text-sm opacity-75">
            <p>Fluxo365 - Sistema Base Modular</p>
        </div>
    </div>
</body>
</html>
