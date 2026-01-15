<?php
session_start();
require_once(__DIR__ . "/../../core/db.php");
require_once(__DIR__ . "/../../core/PermissionManager.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: /auth/login");
    exit;
}

$leadId = $_GET['id'] ?? null;

if (!$leadId) {
    header("Location: index.php");
    exit;
}

$permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

// Buscar dados do lead
$sql = "SELECT fr.*, f.title as form_title, f.user_id as form_user_id
        FROM form_responses fr
        INNER JOIN forms f ON fr.form_id = f.id
        WHERE fr.id = :id";

if (!$permissionManager->canViewAllRecords()) {
    $sql .= " AND f.user_id = :user_id";
}

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $leadId, PDO::PARAM_INT);
if (!$permissionManager->canViewAllRecords()) {
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
}

$stmt->execute();
$lead = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lead) {
    header("Location: index.php");
    exit;
}

// Buscar todas as respostas individuais com os dados dos campos
$answersStmt = $pdo->prepare("
    SELECT ra.*, ff.label, ff.type, ff.order_index
    FROM response_answers ra
    INNER JOIN form_fields ff ON ra.field_id = ff.id
    WHERE ra.response_id = :response_id
    ORDER BY ff.order_index ASC
");
$answersStmt->execute([':response_id' => $leadId]);
$answers = $answersStmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../../views/layout/header.php';
require_once __DIR__ . '/../forms/builder/builder_sidebar.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Cabeçalho -->
    <div class="mb-6">
        <a href="index.php" class="text-[#4EA44B] hover:text-[#5dcf91] mb-2 inline-block">
            <i class="fas fa-arrow-left mr-2"></i> Voltar para Meus Leads
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-user text-[#4EA44B]"></i> Lead #<?= $lead['id'] ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1"><?= htmlspecialchars($lead['form_title']) ?></p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Informações Principais -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Card de Respostas -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-gray-200 dark:border-zinc-700 p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-clipboard-list text-[#4EA44B]"></i> Respostas
                </h2>

                <?php if (empty($answers)): ?>
                    <p class="text-gray-500 dark:text-gray-400">Nenhuma resposta registrada</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($answers as $answer): ?>
                            <div class="border-b border-gray-200 dark:border-zinc-700 last:border-0 pb-4 last:pb-0">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-[#4EA44B]/10 rounded-lg flex items-center justify-center flex-shrink-0 mt-1">
                                        <i class="fas fa-question text-[#4EA44B] text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-medium text-gray-900 dark:text-white mb-1">
                                            <?= htmlspecialchars($answer['label']) ?>
                                        </h3>
                                        <div class="text-gray-600 dark:text-gray-400">
                                            <?php
                                            $answerValue = $answer['answer'];

                                            // Tentar decodificar se for JSON
                                            $decoded = json_decode($answerValue, true);
                                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                echo '<ul class="list-disc list-inside">';
                                                foreach ($decoded as $item) {
                                                    echo '<li>' . htmlspecialchars($item) . '</li>';
                                                }
                                                echo '</ul>';
                                            } else {
                                                echo nl2br(htmlspecialchars($answerValue));
                                            }
                                            ?>
                                        </div>

                                        <?php if ($answer['score']): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400 mt-2">
                                                <i class="fas fa-star text-xs mr-1"></i> Pontos: <?= $answer['score'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Card de Informações -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-gray-200 dark:border-zinc-700 p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-info-circle text-[#4EA44B]"></i> Informações
                </h2>

                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data de Envio</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">
                            <?= date('d/m/Y \à\s H:i', strtotime($lead['created_at'])) ?>
                        </p>
                    </div>

                    <?php if ($lead['score']): ?>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pontuação Total</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">
                                <i class="fas fa-star text-yellow-500"></i> <?= $lead['score'] ?> pontos
                            </p>
                        </div>
                    <?php endif; ?>

                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">IP do Usuário</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">
                            <?= htmlspecialchars($lead['user_ip'] ?? 'Não disponível') ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">User Agent</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 break-words">
                            <?= htmlspecialchars($lead['user_agent'] ?? 'Não disponível') ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Ações -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-gray-200 dark:border-zinc-700 p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-bolt text-[#4EA44B]"></i> Ações
                </h2>

                <div class="space-y-2">
                    <a href="../forms/responses/view.php?id=<?= $lead['id'] ?>"
                       class="w-full flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-file-alt"></i> Ver Resposta Completa
                    </a>

                    <a href="export.php?id=<?= $lead['id'] ?>"
                       class="w-full flex items-center gap-2 px-4 py-2 bg-[#4EA44B] hover:bg-[#5dcf91] text-white rounded-lg transition-colors">
                        <i class="fas fa-download"></i> Exportar para CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/layout/footer.php'; ?>
