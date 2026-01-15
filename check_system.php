#!/usr/bin/env php
<?php
/**
 * Script de Verificação do Sistema TalkForm
 *
 * Execute: php check_system.php
 */

echo "\033[1m=== Verificação do Sistema TalkForm ===\033[0m\n\n";

$errors = [];
$warnings = [];

// 1. Versão PHP
echo "1. \033[1mPHP Version:\033[0m " . PHP_VERSION;
if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
    echo " \033[32m✓\033[0m\n";
} else {
    echo " \033[31m✗ (Requer PHP 8.0+)\033[0m\n";
    $errors[] = "PHP 8.0+ é obrigatório";
}
echo "\n";

// 2. Extensões necessárias
echo "2. \033[1mExtensões PHP:\033[0m\n";
$required = [
    'pdo' => 'Obrigatória para banco de dados',
    'pdo_mysql' => 'Obrigatória para MySQL',
    'mbstring' => 'Obrigatória para strings multibyte',
    'json' => 'Obrigatória para JSON',
    'curl' => 'Obrigatória para requisições HTTP',
    'openssl' => 'Obrigatória para HTTPS',
    'fileinfo' => 'Obrigatória para upload de arquivos',
    'gd' => 'Recomendada para manipulação de imagens'
];

foreach ($required as $ext => $desc) {
    $loaded = extension_loaded($ext);
    echo "   - $ext: ";
    if ($loaded) {
        echo "\033[32m✓\033[0m\n";
    } else {
        echo "\033[31m✗\033[0m ($desc)\n";
        if ($ext === 'gd') {
            $warnings[] = "Extensão $ext não instalada - $desc";
        } else {
            $errors[] = "Extensão $ext não instalada - $desc";
        }
    }
}
echo "\n";

// 3. Conexão com banco
echo "3. \033[1mBanco de Dados:\033[0m ";
try {
    require __DIR__ . '/core/db.php';
    echo "\033[32m✓ Conectado\033[0m\n";

    // Listar tabelas
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   - Tabelas encontradas: " . count($tables) . "\n";

    if (count($tables) > 0) {
        echo "   - Principais tabelas:\n";
        $important_tables = ['users', 'forms', 'form_submissions', 'form_customizations'];
        foreach ($important_tables as $table) {
            $exists = in_array($table, $tables);
            echo "     • $table: " . ($exists ? "\033[32m✓\033[0m" : "\033[33m✗ (não encontrada)\033[0m") . "\n";
            if (!$exists) {
                $warnings[] = "Tabela $table não encontrada - pode precisar executar migrations";
            }
        }
    } else {
        $warnings[] = "Nenhuma tabela encontrada - execute as migrations";
    }
} catch (Exception $e) {
    echo "\033[31m✗ Erro\033[0m\n";
    echo "   - Mensagem: " . $e->getMessage() . "\n";
    $errors[] = "Não foi possível conectar ao banco de dados";
}
echo "\n";

// 4. Diretórios com permissão de escrita
echo "4. \033[1mPermissões de Diretórios:\033[0m\n";
$dirs = [
    'uploads' => 'Diretório principal de uploads',
    'uploads/forms' => 'Uploads de formulários',
    'uploads/system' => 'Arquivos do sistema'
];

foreach ($dirs as $dir => $desc) {
    echo "   - $dir: ";
    if (!file_exists($dir)) {
        echo "\033[33m⚠ não existe\033[0m\n";
        $warnings[] = "Diretório $dir não existe";
        continue;
    }

    $perms = substr(sprintf('%o', fileperms($dir)), -3);
    $writable = is_writable($dir);

    if ($writable) {
        echo "\033[32m✓\033[0m (permissão: $perms)\n";
    } else {
        echo "\033[31m✗\033[0m (permissão: $perms - sem escrita)\n";
        $errors[] = "Diretório $dir não tem permissão de escrita";
    }
}
echo "\n";

// 5. Arquivos de configuração
echo "5. \033[1mArquivos de Configuração:\033[0m\n";
$files = [
    'core/db.php' => 'Configuração do banco',
    'core/config.php' => 'Configurações globais',
    '.htaccess' => 'Regras de reescrita Apache',
    'index.php' => 'Arquivo principal',
    'migrations/README.md' => 'Documentação de migrations'
];

foreach ($files as $file => $desc) {
    echo "   - $file: ";
    if (file_exists($file)) {
        echo "\033[32m✓\033[0m\n";
    } else {
        echo "\033[31m✗\033[0m ($desc não encontrado)\n";
        $errors[] = "Arquivo $file não encontrado";
    }
}
echo "\n";

// 6. Limites PHP
echo "6. \033[1mLimites PHP:\033[0m\n";
$limits = [
    'upload_max_filesize' => ['2M', 'Tamanho máximo de upload'],
    'post_max_size' => ['8M', 'Tamanho máximo de POST'],
    'max_execution_time' => ['30', 'Tempo máximo de execução'],
    'memory_limit' => ['128M', 'Limite de memória']
];

foreach ($limits as $key => $info) {
    $value = ini_get($key);
    $recommended = $info[0];
    echo "   - $key: $value";

    // Verificar se é adequado (simplificado)
    if ($key === 'memory_limit' && $value === '-1') {
        echo " \033[32m✓\033[0m (ilimitado)\n";
    } elseif ($key === 'max_execution_time' && ($value === '0' || (int)$value >= 30)) {
        echo " \033[32m✓\033[0m\n";
    } else {
        echo " \033[33m⚠\033[0m (recomendado: $recommended)\n";
    }
}
echo "\n";

// 7. Migrações pendentes
echo "7. \033[1mMigrações:\033[0m\n";
$migrations = glob(__DIR__ . '/migrations/*.php');
echo "   - Scripts de migração encontrados: " . count($migrations) . "\n";
if (count($migrations) > 0) {
    echo "   - Para aplicar: php migrations/<nome_do_arquivo>.php\n";
    echo "   - Ou via navegador: http://seu-dominio.com/migrations/<nome_do_arquivo>.php\n";
}
echo "\n";

// Resumo
echo "\033[1m=== RESUMO ===\033[0m\n\n";

if (count($errors) === 0 && count($warnings) === 0) {
    echo "\033[42m\033[30m TUDO OK! \033[0m Sistema pronto para rodar!\n\n";
    echo "✓ Todas as verificações passaram\n";
    echo "✓ Banco de dados conectado\n";
    echo "✓ Permissões corretas\n\n";
    echo "Para rodar o sistema:\n";
    echo "  1. Configure seu servidor web (Apache/Nginx)\n";
    echo "  2. Ou use o servidor embutido: \033[36mphp -S localhost:8000\033[0m\n";
    echo "  3. Acesse: \033[36mhttp://localhost:8000\033[0m\n\n";
} else {
    if (count($errors) > 0) {
        echo "\033[41m\033[37m ERROS ENCONTRADOS \033[0m\n\n";
        foreach ($errors as $i => $error) {
            echo ($i + 1) . ". \033[31m✗\033[0m $error\n";
        }
        echo "\n";
    }

    if (count($warnings) > 0) {
        echo "\033[43m\033[30m AVISOS \033[0m\n\n";
        foreach ($warnings as $i => $warning) {
            echo ($i + 1) . ". \033[33m⚠\033[0m $warning\n";
        }
        echo "\n";
    }

    echo "Consulte o arquivo \033[36mSETUP.md\033[0m para instruções detalhadas.\n\n";
}

// Sugestões
if (count($errors) > 0 || count($warnings) > 0) {
    echo "\033[1m=== PRÓXIMOS PASSOS ===\033[0m\n\n";

    if (in_array("Não foi possível conectar ao banco de dados", $errors)) {
        echo "1. \033[1mIniciar MySQL/MariaDB:\033[0m\n";
        echo "   sudo systemctl start mysql\n";
        echo "   # ou\n";
        echo "   sudo systemctl start mariadb\n\n";

        echo "2. \033[1mCriar banco e usuário:\033[0m\n";
        echo "   mysql -u root -p\n";
        echo "   CREATE DATABASE webformtalk_forms;\n";
        echo "   CREATE USER 'webformtalk_forms'@'localhost' IDENTIFIED BY 'fDdyA*E]Qq1hFPzM*)_Y';\n";
        echo "   GRANT ALL PRIVILEGES ON webformtalk_forms.* TO 'webformtalk_forms'@'localhost';\n\n";
    }

    if (count(array_filter($warnings, fn($w) => strpos($w, 'Tabela') !== false)) > 0) {
        echo "3. \033[1mImportar schema do banco:\033[0m\n";
        echo "   mysql -u webformtalk_forms -p webformtalk_forms < schema.sql\n";
        echo "   # ou aplicar migrations\n\n";
    }
}

exit(count($errors) > 0 ? 1 : 0);
