<?php
session_start();
require_once(__DIR__ . "/../../../core/db.php");
require_once(__DIR__ . "/../../../core/config.php");
require_once __DIR__ . '/../../../core/PermissionManager.php';
require_once __DIR__ . '/../../../core/PlanService.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: /auth/login");
    exit;
}

$formId = $_GET['id'] ?? null;

if (!$formId) {
    header("Location: /modules/forms/list.php");
    exit;
}

$permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

// Buscar dados do formulário
$sql = "SELECT * FROM forms WHERE id = :id";
if (!$permissionManager->canViewAllRecords()) {
    $sql .= " AND user_id = :user_id";
}

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $formId, PDO::PARAM_INT);
if (!$permissionManager->canViewAllRecords()) {
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
}

$stmt->execute();
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$form) {
    header("Location: /modules/forms/list.php");
    exit;
}

// Buscar todas as respostas com o nome do respondente
$responsesStmt = $pdo->prepare("
    SELECT fr.*,
           COUNT(ra.id) as total_answers,
           (SELECT ra2.answer
            FROM response_answers ra2
            INNER JOIN form_fields ff2 ON ra2.field_id = ff2.id
            WHERE ra2.response_id = fr.id
            AND ff2.type IN ('name', 'text', 'email')
            ORDER BY
                CASE ff2.type
                    WHEN 'name' THEN 1
                    WHEN 'text' THEN 2
                    WHEN 'email' THEN 3
                END,
                ff2.order_index ASC
            LIMIT 1) as respondent_name
    FROM form_responses fr
    LEFT JOIN response_answers ra ON ra.response_id = fr.id
    WHERE fr.form_id = :form_id
    GROUP BY fr.id
    ORDER BY fr.created_at DESC
");
$responsesStmt->execute([':form_id' => $formId]);
$responses = $responsesStmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar total de campos para calcular % de preenchimento (excluindo campos informativos)
$fieldsStmt = $pdo->prepare("SELECT COUNT(*) as total FROM form_fields WHERE form_id = :form_id AND type NOT IN ('welcome', 'message')");
$fieldsStmt->execute([':form_id' => $formId]);
$totalFields = $fieldsStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total de respostas do formulário
$totalResponses = count($responses);

// Respostas dos últimos 30 dias (para gráfico de linha)
$last30DaysStmt = $pdo->prepare("
    SELECT DATE(created_at) as date, COUNT(*) as count
    FROM form_responses
    WHERE form_id = :form_id AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$last30DaysStmt->execute([':form_id' => $formId]);
$responsesOverTime = $last30DaysStmt->fetchAll(PDO::FETCH_ASSOC);

// Distribuição de respostas para campos de múltipla escolha
$multipleChoiceStats = [];
if ($totalResponses > 0) {
    $multipleChoiceFields = $pdo->prepare("
        SELECT id, label, type, options
        FROM form_fields
        WHERE form_id = :form_id AND type IN ('radio', 'select')
        ORDER BY order_index ASC
        LIMIT 5
    ");
    $multipleChoiceFields->execute([':form_id' => $formId]);
    $choiceFields = $multipleChoiceFields->fetchAll(PDO::FETCH_ASSOC);

    foreach ($choiceFields as $field) {
        $distributionStmt = $pdo->prepare("
            SELECT answer, COUNT(*) as count
            FROM response_answers
            WHERE field_id = :field_id
            GROUP BY answer
            ORDER BY count DESC
        ");
        $distributionStmt->execute([':field_id' => $field['id']]);
        $distribution = $distributionStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($distribution)) {
            $multipleChoiceStats[] = [
                'field' => $field,
                'distribution' => $distribution
            ];
        }
    }
}

// Buscar respostas parciais (não completadas) - com tratamento de erro caso tabela não exista
$partialResponses = [];
$totalPartialResponses = 0;

try {
    $partialResponsesStmt = $pdo->prepare("
        SELECT pr.*
        FROM partial_responses pr
        WHERE pr.form_id = :form_id AND pr.completed = 0
        ORDER BY pr.last_updated DESC
        LIMIT 20
    ");
    $partialResponsesStmt->execute([':form_id' => $formId]);
    $partialResponses = $partialResponsesStmt->fetchAll(PDO::FETCH_ASSOC);
    $totalPartialResponses = count($partialResponses);
} catch (PDOException $e) {
    // Tabela ainda não existe - ignorar silenciosamente
    error_log('Tabela partial_responses não encontrada: ' . $e->getMessage());
}

// Incluir o layout
require_once __DIR__ . '/../../../views/layout/header.php';
require_once __DIR__ . '/../builder/builder_sidebar.php';
?>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- SweetAlert2 -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    .dark .swal2-popup { background: #27272a !important; color: #e4e4e7 !important; }
    .dark .swal2-title { color: #e4e4e7 !important; }
    .dark .swal2-html-container { color: #d4d4d8 !important; }
    .dark .swal2-popup input, .dark .swal2-popup textarea, .dark .swal2-popup select { 
        border: 1px solid #52525b !important; 
    }
    .dark .swal2-popup input:focus, .dark .swal2-popup textarea:focus, .dark .swal2-popup select:focus { 
        border-color: #71717a !important; 
        outline: none; 
    }
    
    .swal2-container { backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); }
    .swal2-container.swal2-backdrop-show { background: rgba(0, 0, 0, 0.5) !important; }
    .dark .swal2-container.swal2-backdrop-show { background: rgba(0, 0, 0, 0.7) !important; }
    
    /* SweetAlert com animação bounce rápida ao ABRIR */
    .swal2-popup.swal2-show {
        animation: swal2-show 0.25s;
    }
    
    @keyframes swal2-show {
        0% {
            transform: scale(0.7);
        }
        45% {
            transform: scale(1.05);
        }
        80% {
            transform: scale(0.95);
        }
        100% {
            transform: scale(1);
        }
    }
    
    /* SweetAlert com animação bounce rápida ao FECHAR */
    .swal2-popup.swal2-hide {
        animation: swal2-hide 0.2s;
    }
    
    @keyframes swal2-hide {
        0% {
            transform: scale(1);
        }
        100% {
            transform: scale(0.7);
            opacity: 0;
        }
    }
    
    /* Backdrop rápido ao ABRIR */
    .swal2-container.swal2-backdrop-show {
        animation: swal2-backdrop-show 0.15s;
    }
    
    @keyframes swal2-backdrop-show {
        0% {
            opacity: 0;
        }
        100% {
            opacity: 1;
        }
    }
    
    /* Backdrop rápido ao FECHAR */
    .swal2-container.swal2-backdrop-hide {
        animation: swal2-backdrop-hide 0.15s;
    }
    
    @keyframes swal2-backdrop-hide {
        0% {
            opacity: 1;
        }
        100% {
            opacity: 0;
        }
    }
    
    @media (max-width: 640px) {
        .swal2-popup { width: 95% !important; padding: 1rem !important; }
    }

    /* PRO Feature Restriction */
    .pro-blur-chart {
        filter: blur(8px);
        pointer-events: none;
        user-select: none;
    }

    .pro-badge-simple {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        margin-bottom: 1rem;
    }

    .pro-badge-simple i {
        font-size: 1rem;
    }

    .response-row {
        display: none;
    }

    .response-row.visible {
        display: table-row;
    }
</style>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-zinc-100"><?= htmlspecialchars($form['title']) ?></h1>
                <p class="text-sm text-gray-600 dark:text-zinc-400 mt-1">
                    <i class="fas fa-inbox mr-1"></i> <?= count($responses) ?> resposta(s) recebida(s)
                </p>
            </div>
            <div class="flex gap-2">
                <button onclick="exportResponses()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition-colors">
                    <i class="fas fa-download mr-1"></i> Exportar CSV
                </button>
            </div>
        </div>
    </div>

    <?php if (empty($responses)): ?>
        <!-- Estado vazio -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-12 text-center">
            <i class="fas fa-inbox text-6xl text-gray-300 dark:text-zinc-600 mb-4"></i>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-zinc-100 mb-2">Nenhuma resposta ainda</h2>
            <p class="text-gray-600 dark:text-zinc-400 mb-6">Compartilhe o link do formulário para começar a receber respostas.</p>
            <div class="flex items-center justify-center gap-3">
                <input type="text"
                       id="formLink"
                       value="<?= getPublicFormUrl($formId) ?>"
                       readonly
                       class="px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg bg-gray-50 dark:bg-zinc-700 text-gray-600 dark:text-zinc-300 text-sm flex-1 max-w-md">
                <button onclick="copyLink()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm transition-colors">
                    <i class="fas fa-copy mr-1"></i> Copiar Link
                </button>
            </div>
        </div>
    <?php else: ?>
        <!-- Tabela de respostas -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                    <thead class="bg-gray-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                                #
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                                Respondente
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider hidden md:table-cell">
                                Data/Hora
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider hidden lg:table-cell">
                                Respostas
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider hidden xl:table-cell">
                                Completude
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700" id="responsesTableBody">
                        <?php foreach ($responses as $index => $response): ?>
                            <?php
                            $completeness = $totalFields > 0 ? round(($response['total_answers'] / $totalFields) * 100) : 0;
                            $completeness = min($completeness, 100); // Limitar a 100%
                            $completenessColor = $completeness >= 100 ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400';
                            ?>
                            <tr class="response-row hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors" data-index="<?= $index ?>">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-zinc-100">
                                    #<?= count($responses) - $index ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-zinc-100">
                                        <?php if (!empty($response['respondent_name'])): ?>
                                            <?= htmlspecialchars($response['respondent_name']) ?>
                                        <?php else: ?>
                                            <span class="text-gray-400 dark:text-zinc-500 italic">Anônimo</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell">
                                    <div class="text-sm text-gray-900 dark:text-zinc-100">
                                        <?= date('d/m/Y', strtotime($response['created_at'])) ?>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-zinc-400">
                                        <?= date('H:i', strtotime($response['created_at'])) ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-zinc-300 hidden lg:table-cell">
                                    <?= $response['total_answers'] ?> / <?= $totalFields ?>
                                </td>
                                <td class="px-4 py-3 hidden xl:table-cell">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-gray-200 dark:bg-zinc-700 rounded-full h-2 max-w-[100px]">
                                            <div class="h-2 rounded-full" style="width: <?= $completeness ?>%; background-color: #4EA44B;"></div>
                                        </div>
                                        <span class="text-sm font-medium <?= $completenessColor ?>">
                                            <?= $completeness ?>%
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right text-sm space-x-2">
                                    <button onclick="viewResponse(<?= $response['id'] ?>)" 
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                            title="Ver resposta completa">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if ($permissionManager->canDeleteRecord($form['user_id'])): ?>
                                        <button onclick="deleteResponse(<?= $response['id'] ?>)" 
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                title="Excluir resposta">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Botão Carregar Mais -->
            <div class="p-4 bg-gray-50 dark:bg-zinc-900 border-t border-gray-200 dark:border-zinc-700 text-center">
                <button id="loadMoreBtn" onclick="loadMoreResponses()" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm transition-colors">
                    <i class="fas fa-chevron-down mr-2"></i>
                    Carregar Mais
                </button>
                <p id="responseCounter" class="text-sm text-gray-600 dark:text-zinc-400 mt-2">
                    Exibindo <span id="visibleCount">5</span> de <?= count($responses) ?> respostas
                </p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Seção de Estatísticas (PRO Feature) -->
    <?php if ($totalResponses > 0): ?>
        <div class="mt-6">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
                    <?php if (!PlanService::hasProAccess()): ?>
                        <!-- PRO Badge simples para usuários FREE -->
                        <div class="pro-badge-simple">
                            <i class="fas fa-crown"></i>
                            <span>Recurso PRO</span>
                        </div>
                    <?php endif; ?>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-zinc-100 flex items-center gap-2">
                        <i class="fas fa-chart-bar text-indigo-600"></i>
                        Estatísticas do Formulário
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-zinc-400 mt-1">
                        Análise geral de <?= $totalResponses ?> resposta<?= $totalResponses > 1 ? 's' : '' ?> recebida<?= $totalResponses > 1 ? 's' : '' ?>
                    </p>
                </div>

                <!-- Respostas ao longo do tempo -->
                <?php if (!empty($responsesOverTime)): ?>
                <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-zinc-100 mb-4">
                        Respostas nos Últimos 30 Dias
                    </h3>
                    <div style="position: relative; height: 300px;" class="<?= !PlanService::hasProAccess() ? 'pro-blur-chart' : '' ?>">
                        <canvas id="chartResponsesOverTime"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Distribuição de respostas para múltipla escolha -->
                <?php if (!empty($multipleChoiceStats)): ?>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-zinc-100 mb-6">
                        Distribuição de Respostas por Pergunta
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($multipleChoiceStats as $index => $stat): ?>
                        <div class="bg-gray-50 dark:bg-zinc-900 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-zinc-100 mb-3">
                                <?= htmlspecialchars($stat['field']['label']) ?>
                            </h4>
                            <div style="position: relative; height: 250px;" class="<?= !PlanService::hasProAccess() ? 'pro-blur-chart' : '' ?>">
                                <canvas id="chartField<?= $index ?>"></canvas>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Seção de Respostas Parciais (PRO Feature) -->
    <?php if ($totalPartialResponses > 0): ?>
        <div class="mt-6">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
                    <?php if (!PlanService::hasProAccess()): ?>
                        <!-- PRO Badge simples para usuários FREE -->
                        <div class="pro-badge-simple">
                            <i class="fas fa-crown"></i>
                            <span>Recurso PRO</span>
                        </div>
                    <?php endif; ?>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-zinc-100 flex items-center gap-2">
                        <i class="fas fa-hourglass-half text-yellow-600"></i>
                        Formulários Abandonados
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-zinc-400 mt-1">
                        Respostas parciais de <?= $totalPartialResponses ?> visitante<?= $totalPartialResponses > 1 ? 's' : '' ?> que começou<?= $totalPartialResponses > 1 ? 'aram' : 'ou' ?> mas não concluiu<?= $totalPartialResponses > 1 ? 'íram' : 'u' ?> o formulário
                    </p>
                </div>

                <div class="overflow-x-auto <?= !PlanService::hasProAccess() ? 'pro-blur-chart' : '' ?>">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                        <thead class="bg-gray-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                                    #
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                                    Primeira Resposta
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider hidden md:table-cell">
                                    Última Atualização
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider hidden lg:table-cell">
                                    Progresso
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                            <?php foreach ($partialResponses as $index => $partial): ?>
                                <?php
                                $answersData = json_decode($partial['answers_data'], true);
                                $firstAnswer = 'Sem dados';
                                if (!empty($answersData)) {
                                    $firstAnswer = reset($answersData);
                                    if (is_array($firstAnswer)) {
                                        $firstAnswer = implode(', ', $firstAnswer);
                                    }
                                    $firstAnswer = mb_strlen($firstAnswer) > 50 ? mb_substr($firstAnswer, 0, 50) . '...' : $firstAnswer;
                                }

                                $progress = intval($partial['progress']);
                                $progressColor = $progress < 30 ? 'text-red-600 dark:text-red-400' : ($progress < 70 ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400');
                                ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-zinc-100">
                                        #<?= $totalPartialResponses - $index ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-900 dark:text-zinc-100">
                                            <?= htmlspecialchars($firstAnswer) ?>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-zinc-500">
                                            <?= count($answersData) ?> campo(s) preenchido(s)
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 hidden md:table-cell">
                                        <div class="text-sm text-gray-900 dark:text-zinc-100">
                                            <?= date('d/m/Y', strtotime($partial['last_updated'])) ?>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-zinc-400">
                                            <?= date('H:i', strtotime($partial['last_updated'])) ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 hidden lg:table-cell">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-gray-200 dark:bg-zinc-700 rounded-full h-2 max-w-[100px]">
                                                <div class="h-2 rounded-full" style="width: <?= $progress ?>%; background-color: #f59e0b;"></div>
                                            </div>
                                            <span class="text-sm font-medium <?= $progressColor ?>">
                                                <?= $progress ?>%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm space-x-2">
                                        <button onclick="viewPartialResponse(<?= $partial['id'] ?>)"
                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                title="Ver detalhes">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <?php if ($permissionManager->canDeleteRecord($form['user_id'])): ?>
                                            <button onclick="deletePartialResponse(<?= $partial['id'] ?>)"
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                    title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// ============================================
// PAGINAÇÃO DE RESPOSTAS
// ============================================
let currentPage = 0;
const perPage = 5;
const totalResponses = <?= count($responses) ?>;

function showResponses() {
    const rows = document.querySelectorAll('.response-row');
    const start = 0;
    const end = (currentPage + 1) * perPage;

    rows.forEach((row, index) => {
        if (index < end) {
            row.classList.add('visible');
        }
    });

    // Atualizar contador
    const visibleCount = Math.min(end, totalResponses);
    document.getElementById('visibleCount').textContent = visibleCount;

    // Esconder botão se todas as respostas estão visíveis
    if (visibleCount >= totalResponses) {
        document.getElementById('loadMoreBtn').style.display = 'none';
    }
}

function loadMoreResponses() {
    currentPage++;
    showResponses();
}

// Inicializar mostrando as primeiras 5 respostas
document.addEventListener('DOMContentLoaded', function() {
    showResponses();
});

// ============================================
// GRÁFICOS DE ESTATÍSTICAS
// ============================================

// Detectar tema escuro
const isDarkMode = document.documentElement.classList.contains('dark');
const textColor = isDarkMode ? '#e4e4e7' : '#111827';
const gridColor = isDarkMode ? '#3f3f46' : '#e5e7eb';

// Configuração padrão dos gráficos
Chart.defaults.color = textColor;
Chart.defaults.borderColor = gridColor;
Chart.defaults.font.family = "'Inter', sans-serif";

<?php if (!empty($responsesOverTime)): ?>
// Gráfico de respostas ao longo do tempo
const ctxTimeline = document.getElementById('chartResponsesOverTime');
if (ctxTimeline) {
    new Chart(ctxTimeline.getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_map(function($item) {
                return date('d/m', strtotime($item['date']));
            }, $responsesOverTime)) ?>,
            datasets: [{
                label: 'Respostas',
                data: <?= json_encode(array_column($responsesOverTime, 'count')) ?>,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: isDarkMode ? '#18181b' : '#ffffff',
                    titleColor: textColor,
                    bodyColor: textColor,
                    borderColor: gridColor,
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        title: function(context) {
                            return 'Data: ' + context[0].label;
                        },
                        label: function(context) {
                            return context.parsed.y + ' resposta' + (context.parsed.y !== 1 ? 's' : '');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        color: textColor
                    },
                    grid: {
                        color: gridColor
                    }
                },
                x: {
                    ticks: {
                        color: textColor
                    },
                    grid: {
                        color: gridColor
                    }
                }
            }
        }
    });
}
<?php endif; ?>

<?php if (!empty($multipleChoiceStats)): ?>
// Gráficos de distribuição de respostas
const chartColors = [
    '#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981',
    '#3b82f6', '#ef4444', '#14b8a6', '#f97316', '#06b6d4'
];

<?php foreach ($multipleChoiceStats as $index => $stat): ?>
const ctxField<?= $index ?> = document.getElementById('chartField<?= $index ?>');
if (ctxField<?= $index ?>) {
    new Chart(ctxField<?= $index ?>.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($stat['distribution'], 'answer')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($stat['distribution'], 'count')) ?>,
                backgroundColor: chartColors.slice(0, <?= count($stat['distribution']) ?>),
                borderWidth: 2,
                borderColor: isDarkMode ? '#27272a' : '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        color: textColor,
                        font: {
                            size: 12
                        },
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                return data.labels.map((label, i) => {
                                    const value = data.datasets[0].data[i];
                                    const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);

                                    return {
                                        text: `${label} (${percentage}%)`,
                                        fillStyle: data.datasets[0].backgroundColor[i],
                                        hidden: false,
                                        index: i
                                    };
                                });
                            }
                            return [];
                        }
                    }
                },
                tooltip: {
                    backgroundColor: isDarkMode ? '#18181b' : '#ffffff',
                    titleColor: textColor,
                    bodyColor: textColor,
                    borderColor: gridColor,
                    borderWidth: 1,
                    padding: 12,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}
<?php endforeach; ?>
<?php endif; ?>

function copyLink() {
    const input = document.getElementById('formLink');
    input.select();
    document.execCommand('copy');
    
    const btn = event.target.closest('button');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check mr-1"></i> Copiado!';
    
    setTimeout(() => {
        btn.innerHTML = originalHTML;
    }, 2000);
}

function viewResponse(responseId) {
    window.location.href = `/forms/<?= $formId ?>/responses/${responseId}`;
}

async function deleteResponse(responseId) {
    const result = await Swal.fire(getConfirmModalConfig(
        'Tem certeza?',
        'Deseja realmente excluir esta resposta? Esta ação não pode ser desfeita.',
        'Sim, excluir!'
    ));
    
    if (!result.isConfirmed) return;
    
    try {
        const res = await fetch(`/modules/forms/responses/delete.php?id=${responseId}`, {
            method: 'POST'
        });
        
        const resultText = await res.text();
        
        if (res.ok && resultText === 'success') {
            await Swal.fire({
                title: 'Excluída!',
                text: 'Resposta excluída com sucesso.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            window.location.reload();
        } else {
            Swal.fire({
                title: 'Erro!',
                text: 'Erro ao excluir: ' + resultText,
                icon: 'error'
            });
        }
    } catch (error) {
        Swal.fire({
            title: 'Erro!',
            text: 'Erro de conexão',
            icon: 'error'
        });
    }
}

function exportResponses() {
    window.location.href = `/forms/<?= $formId ?>/responses/export`;
}

// ============================================
// RESPOSTAS PARCIAIS
// ============================================

function viewPartialResponse(partialId) {
    // Buscar detalhes da resposta parcial
    fetch(`/modules/forms/responses/get_partial.php?id=${partialId}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                Swal.fire('Erro', data.error || 'Erro ao carregar resposta', 'error');
                return;
            }

            const partial = data.partial;
            const answers = JSON.parse(partial.answers_data);

            let answersHtml = '<div class="text-left space-y-3">';
            for (const [field, answer] of Object.entries(answers)) {
                const displayValue = Array.isArray(answer) ? answer.join(', ') : answer;
                answersHtml += `
                    <div class="border-b border-gray-200 dark:border-zinc-700 pb-2">
                        <div class="text-xs text-gray-500 dark:text-zinc-500 mb-1">${field}</div>
                        <div class="text-sm font-medium text-gray-900 dark:text-zinc-100">${displayValue || '<em>Sem resposta</em>'}</div>
                    </div>
                `;
            }
            answersHtml += '</div>';

            Swal.fire({
                title: 'Resposta Parcial',
                html: `
                    <div class="mb-4 text-sm text-gray-600 dark:text-zinc-400">
                        Última atualização: ${new Date(partial.last_updated).toLocaleString('pt-BR')}
                    </div>
                    ${answersHtml}
                `,
                width: '600px',
                showCloseButton: true
            });
        })
        .catch(error => {
            Swal.fire('Erro', 'Erro de conexão', 'error');
        });
}

async function deletePartialResponse(partialId) {
    const result = await Swal.fire(getConfirmModalConfig(
        'Tem certeza?',
        'Deseja realmente excluir esta resposta parcial? Esta ação não pode ser desfeita.',
        'Sim, excluir!'
    ));

    if (!result.isConfirmed) return;

    try {
        const res = await fetch(`/modules/forms/responses/delete_partial.php?id=${partialId}`, {
            method: 'POST'
        });

        const resultText = await res.text();

        if (res.ok && resultText === 'success') {
            await Swal.fire({
                title: 'Excluída!',
                text: 'Resposta parcial excluída com sucesso.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            window.location.reload();
        } else {
            Swal.fire({
                title: 'Erro!',
                text: 'Erro ao excluir: ' + resultText,
                icon: 'error'
            });
        }
    } catch (error) {
        Swal.fire({
            title: 'Erro!',
            text: 'Erro de conexão',
            icon: 'error'
        });
    }
}
</script>

<?php
require_once __DIR__ . '/../../../views/layout/footer.php';
?>