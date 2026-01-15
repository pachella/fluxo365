<?php
session_start();
require_once(__DIR__ . "/../../../core/db.php");
require_once(__DIR__ . "/../../../core/config.php");
require_once __DIR__ . '/../../../core/PermissionManager.php';
require_once(__DIR__ . "/../../../core/cache_helper.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: /auth/login");
    exit;
}

$responseId = $_GET['id'] ?? null;

if (!$responseId) {
    header("Location: /modules/forms/list.php");
    exit;
}

$permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

// Buscar dados da resposta
$sql = "SELECT fr.*, f.title as form_title, f.user_id as form_user_id
        FROM form_responses fr
        INNER JOIN forms f ON fr.form_id = f.id
        WHERE fr.id = :id";

if (!$permissionManager->canViewAllRecords()) {
    $sql .= " AND f.user_id = :user_id";
}

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $responseId, PDO::PARAM_INT);
if (!$permissionManager->canViewAllRecords()) {
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
}

$stmt->execute();
$response = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$response) {
    header("Location: /modules/forms/list.php");
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
$answersStmt->execute([':response_id' => $responseId]);
$answers = $answersStmt->fetchAll(PDO::FETCH_ASSOC);

// Incluir o layout
require_once __DIR__ . '/../../../views/layout/header.php';
require_once __DIR__ . '/../builder/builder_sidebar.php';
?>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- SweetAlert2 -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
</style>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <a href="/forms/<?= $response['form_id'] ?>/responses" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline mb-2 inline-block">
            <i class="fas fa-arrow-left mr-1"></i> Voltar para respostas
        </a>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-zinc-100"><?= htmlspecialchars($response['form_title']) ?></h1>
                <p class="text-sm text-gray-600 dark:text-zinc-400 mt-1">
                    <i class="fas fa-calendar mr-1"></i> 
                    Respondido em <?= date('d/m/Y', strtotime($response['created_at'])) ?> às <?= date('H:i', strtotime($response['created_at'])) ?>
                </p>
            </div>
            <?php if ($permissionManager->canDeleteRecord($response['form_user_id'])): ?>
                <button onclick="deleteResponse(<?= $responseId ?>, <?= $response['form_id'] ?>)" 
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm transition-colors">
                    <i class="fas fa-trash mr-1"></i> Excluir Resposta
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Card com as respostas -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow">
        <?php if (empty($answers)): ?>
            <div class="p-12 text-center">
                <i class="fas fa-inbox text-4xl text-gray-300 dark:text-zinc-600 mb-3"></i>
                <p class="text-gray-600 dark:text-zinc-400">Nenhuma resposta registrada.</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-200 dark:divide-zinc-700">
                <?php foreach ($answers as $index => $answer): ?>
                    <div class="p-6 hover:bg-gray-50 dark:hover:bg-zinc-700/50 transition-colors">
                        <div class="flex items-start gap-4">
                            <!-- Número da pergunta -->
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">
                                <?= $index + 1 ?>
                            </div>
                            
                            <!-- Pergunta e resposta -->
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-zinc-100 mb-2">
                                    <?= htmlspecialchars($answer['label']) ?>
                                </h3>
                                
                                <div class="bg-gray-50 dark:bg-zinc-900 rounded-lg p-4 border-l-4 border-indigo-500">
                                    <?php
                                    $answerText = htmlspecialchars($answer['answer']);
                                    
                                    // Formatar resposta baseado no tipo
                                    switch ($answer['type']) {
                                        case 'textarea':
                                            // Preservar quebras de linha
                                            echo '<p class="text-gray-800 dark:text-zinc-200 whitespace-pre-wrap">' . $answerText . '</p>';
                                            break;
                                        
                                        case 'date':
                                            // Formatar data
                                            $date = DateTime::createFromFormat('Y-m-d', $answer['answer']);
                                            if ($date) {
                                                echo '<p class="text-gray-800 dark:text-zinc-200">' . $date->format('d/m/Y') . '</p>';
                                            } else {
                                                echo '<p class="text-gray-800 dark:text-zinc-200">' . $answerText . '</p>';
                                            }
                                            break;
                                        
                                        case 'email':
                                            // Email clicável
                                            echo '<p class="text-gray-800 dark:text-zinc-200">';
                                            echo '<a href="mailto:' . $answerText . '" class="text-indigo-600 dark:text-indigo-400 hover:underline">' . $answerText . '</a>';
                                            echo '</p>';
                                            break;
                                        
                                        case 'phone':
                                            // Telefone clicável
                                            echo '<p class="text-gray-800 dark:text-zinc-200">';
                                            echo '<a href="tel:' . preg_replace('/[^0-9]/', '', $answer['answer']) . '" class="text-indigo-600 dark:text-indigo-400 hover:underline">' . $answerText . '</a>';
                                            echo '</p>';
                                            break;
                                        
                                        case 'radio':
                                        case 'select':
                                            // Resposta de escolha com badge
                                            echo '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200">';
                                            echo '<i class="fas fa-check-circle mr-2"></i>' . $answerText;
                                            echo '</span>';
                                            break;

                                        case 'file':
                                            // Arquivo enviado - link para download
                                            if (!empty($answer['answer'])) {
                                                $filePath = htmlspecialchars($answer['answer']);
                                                $fileName = basename($filePath);
                                                echo '<a href="' . $filePath . '" target="_blank" download class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-indigo-600 dark:bg-indigo-700 text-white hover:bg-indigo-700 dark:hover:bg-indigo-800 transition-colors">';
                                                echo '<i class="fas fa-download mr-2"></i> Baixar arquivo: ' . $fileName;
                                                echo '</a>';
                                            } else {
                                                echo '<p class="text-gray-500 dark:text-zinc-400 italic">Nenhum arquivo enviado</p>';
                                            }
                                            break;

                                        case 'rg':
                                            // Campo RG - pode ser JSON com subcampos ou texto simples
                                            $rgData = json_decode($answer['answer'], true);
                                            if (is_array($rgData) && isset($rgData['rg_number'])) {
                                                // RG com campos complementares (JSON)
                                                echo '<div class="space-y-2">';
                                                echo '<p class="text-gray-800 dark:text-zinc-200"><strong>RG:</strong> ' . htmlspecialchars($rgData['rg_number']) . '</p>';
                                                if (!empty($rgData['birth_date'])) {
                                                    echo '<p class="text-gray-700 dark:text-zinc-300 text-sm"><strong>Data de Nascimento:</strong> ' . htmlspecialchars($rgData['birth_date']) . '</p>';
                                                }
                                                if (!empty($rgData['nationality'])) {
                                                    echo '<p class="text-gray-700 dark:text-zinc-300 text-sm"><strong>Naturalidade:</strong> ' . htmlspecialchars($rgData['nationality']) . '</p>';
                                                }
                                                if (!empty($rgData['issuing_agency'])) {
                                                    echo '<p class="text-gray-700 dark:text-zinc-300 text-sm"><strong>Órgão Expedidor:</strong> ' . htmlspecialchars($rgData['issuing_agency']) . '</p>';
                                                }
                                                if (!empty($rgData['issuing_state'])) {
                                                    echo '<p class="text-gray-700 dark:text-zinc-300 text-sm"><strong>UF de Expedição:</strong> ' . htmlspecialchars($rgData['issuing_state']) . '</p>';
                                                }
                                                if (!empty($rgData['issue_date'])) {
                                                    echo '<p class="text-gray-700 dark:text-zinc-300 text-sm"><strong>Data de Expedição:</strong> ' . htmlspecialchars($rgData['issue_date']) . '</p>';
                                                }
                                                echo '</div>';
                                            } else {
                                                // RG simples (só o número)
                                                echo '<p class="text-gray-800 dark:text-zinc-200">' . $answerText . '</p>';
                                            }
                                            break;

                                        default:
                                            // Texto padrão
                                            echo '<p class="text-gray-800 dark:text-zinc-200">' . $answerText . '</p>';
                                            break;
                                    }
                                    ?>
                                </div>
                                
                                <!-- Tipo do campo -->
                                <p class="text-xs text-gray-500 dark:text-zinc-500 mt-2">
                                    <?php
                                    $typeLabels = [
                                        'text' => 'Texto',
                                        'textarea' => 'Texto longo',
                                        'email' => 'E-mail',
                                        'phone' => 'Telefone',
                                        'date' => 'Data',
                                        'radio' => 'Múltipla escolha',
                                        'select' => 'Seleção única',
                                        'file' => 'Anexar arquivos'
                                    ];
                                    echo $typeLabels[$answer['type']] ?? ucfirst($answer['type']);
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Resumo -->
    <div class="mt-6 bg-gray-50 dark:bg-zinc-900 rounded-lg p-6">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-zinc-300 mb-3">Informações da Resposta</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-500 dark:text-zinc-500">Total de Respostas</p>
                <p class="text-lg font-bold text-gray-900 dark:text-zinc-100"><?= count($answers) ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-zinc-500">Data de Envio</p>
                <p class="text-lg font-bold text-gray-900 dark:text-zinc-100">
                    <?= date('d/m/Y', strtotime($response['created_at'])) ?>
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-zinc-500">Horário</p>
                <p class="text-lg font-bold text-gray-900 dark:text-zinc-100">
                    <?= date('H:i:s', strtotime($response['created_at'])) ?>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Variáveis globais do PHP
const FORM_ID = <?= $response['form_id'] ?>;
const IS_PRO_USER = <?= PlanService::hasProAccess() ? 'true' : 'false' ?>;
const USER_PLAN = "<?= PlanService::getCurrentPlan() ?>";
const USER_NAME = "<?= htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES) ?>";
const USER_EMAIL = "<?= htmlspecialchars($_SESSION['user_email'] ?? '', ENT_QUOTES) ?>";
window.userRole = "<?= htmlspecialchars($_SESSION['user_role'] ?? '', ENT_QUOTES) ?>";
window.userPlan = "<?= PlanService::getCurrentPlan() ?>";
</script>
<script src="<?= assetUrl('/scripts/js/global/theme.js') ?>"></script>
<script src="<?= assetUrl('/scripts/js/global/ui.js') ?>"></script>
<script src="<?= assetUrl('/scripts/js/global/modals.js') ?>"></script>
<script src="<?= assetUrl('/scripts/js/global/helpers.js') ?>"></script>

<script>
async function deleteResponse(responseId, formId) {
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
            window.location.href = `/forms/${formId}/responses`;
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