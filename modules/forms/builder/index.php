<?php
session_start();

// Headers anti-cache para evitar problemas com JavaScript desatualizado
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once(__DIR__ . "/../../../core/db.php");
require_once(__DIR__ . "/../../../core/config.php");
require_once __DIR__ . '/../../../core/PermissionManager.php';
require_once __DIR__ . '/../../../core/PlanService.php';
require_once __DIR__ . '/../../../core/cache_helper.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: /auth/login");
    exit;
}

$formId = $_GET['id'] ?? null;

if (!$formId) {
    header("Location: /modules/forms/list.php");
    exit;
}

// Buscar dados do formulário
$permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

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

// Buscar campos existentes
$fieldsStmt = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = :form_id ORDER BY order_index ASC");
$fieldsStmt->execute([':form_id' => $formId]);
$fields = $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar fluxos existentes
$flowsStmt = $pdo->prepare("SELECT * FROM form_flows WHERE form_id = :form_id ORDER BY order_index ASC");
$flowsStmt->execute([':form_id' => $formId]);
$flows = $flowsStmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar mensagem de sucesso e configurações de redirecionamento
$customizationStmt = $pdo->prepare("SELECT success_message_title, success_message_description, success_redirect_enabled, success_redirect_url, success_redirect_type, success_bt_redirect, hide_formtalk_branding, show_score FROM form_customizations WHERE form_id = :form_id");
$customizationStmt->execute([':form_id' => $formId]);
$customization = $customizationStmt->fetch(PDO::FETCH_ASSOC);

// Valores padrão se não existir customização
$successMessageTitle = $customization['success_message_title'] ?? 'Tudo certo!';
$successMessageDescription = $customization['success_message_description'] ?? 'Obrigado por responder nosso formulário.';
$successRedirectEnabled = $customization['success_redirect_enabled'] ?? 0;
$successRedirectUrl = $customization['success_redirect_url'] ?? '';
$successRedirectType = $customization['success_redirect_type'] ?? 'automatic';
$successBtRedirect = $customization['success_bt_redirect'] ?? 'Continuar';
$hideBranding = $customization['hide_formtalk_branding'] ?? 0;
$showScore = $customization['show_score'] ?? 0;

// Incluir o layout
require_once __DIR__ . '/../../../views/layout/header.php';
require_once __DIR__ . '/builder_sidebar.php';
?>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- SweetAlert2 -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- SortableJS para drag and drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<link rel="stylesheet" href="<?= assetUrl('/modules/forms/builder/assets/builder.css') ?>">

<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex flex-col gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-zinc-100"><?= htmlspecialchars($form['title']) ?></h1>
                <?php if ($form['description']): ?>
                    <p class="text-sm text-gray-600 dark:text-zinc-400 mt-1"><?= htmlspecialchars($form['description']) ?></p>
                <?php endif; ?>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                <div class="flex items-center gap-2 flex-1 min-w-0 w-full sm:w-auto">
                    <input type="text"
                           id="formUrl"
                           value="<?= getPublicFormUrl($formId) ?>"
                           readonly
                           class="flex-1 px-3 py-2 text-sm bg-gray-50 dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded-lg text-gray-700 dark:text-zinc-300 font-mono focus:outline-none">
                    <button onclick="copyFormUrl()"
                            class="px-3 py-2 text-gray-600 dark:text-zinc-400 hover:text-gray-900 dark:hover:text-zinc-100 transition-colors"
                            title="Copiar URL">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <div class="flex gap-2 w-full sm:w-auto items-center">
                    <div class="relative flex-1 sm:flex-none">
                        <button onclick="toggleShareDropdown()" id="shareButton" class="w-full px-4 py-2 bg-[#4EA44B] hover:bg-[#5dcf91] text-white rounded-lg text-sm transition-colors whitespace-nowrap flex items-center justify-center gap-2">
                            <i class="fas fa-share-nodes"></i> Compartilhar <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div id="shareDropdown" class="hidden absolute right-0 mt-2 w-56 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border border-gray-200 dark:border-zinc-700 z-50">
                            <button onclick="showEmbedModal()" class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-zinc-700 text-gray-700 dark:text-zinc-300 text-sm transition-colors flex items-center gap-3 rounded-t-lg">
                                <i class="fas fa-code w-4"></i> Incorporar
                            </button>
                            <button onclick="copyFormLink()" class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-zinc-700 text-gray-700 dark:text-zinc-300 text-sm transition-colors flex items-center gap-3 border-t border-gray-100 dark:border-zinc-700">
                                <i class="fas fa-link w-4"></i> Copiar link
                            </button>
                            <button onclick="previewForm()" class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-zinc-700 text-gray-700 dark:text-zinc-300 text-sm transition-colors flex items-center gap-3 border-t border-gray-100 dark:border-zinc-700">
                                <i class="fas fa-external-link-alt w-4"></i> Ver formulário
                            </button>
                            <button onclick="downloadQRCode()" class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-zinc-700 text-gray-700 dark:text-zinc-300 text-sm transition-colors flex items-center gap-3 border-t border-gray-100 dark:border-zinc-700 rounded-b-lg">
                                <i class="fas fa-qrcode w-4"></i> Baixar QR Code
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 bg-gray-50 dark:bg-zinc-700 px-3 py-2 rounded-lg" style="min-width: 130px;">
                        <span class="text-xs font-medium text-gray-700 dark:text-zinc-300" id="statusLabel" style="min-width: 58px;">
                            <?= $form['status'] === 'ativo' ? 'Ativo' : 'Rascunho' ?>
                        </span>
                        <label class="switch">
                            <input type="checkbox" id="statusToggle" <?= $form['status'] === 'ativo' ? 'checked' : '' ?> onchange="toggleFormStatus()">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Lista de campos -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-zinc-100">Perguntas do Formulário</h2>
                        <button onclick="<?= PlanService::hasProAccess() ? 'addFlow()' : 'showProFeature()' ?>"
                                class="text-xs px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-md transition-colors flex items-center gap-1.5 <?= !PlanService::hasProAccess() ? 'opacity-60' : '' ?>"
                                title="<?= !PlanService::hasProAccess() ? 'Recurso PRO' : 'Adicionar divisor de fluxo condicional' ?>">
                            <i class="fas fa-code-branch text-xs"></i> Adicionar Fluxo
                            <?php if (!PlanService::hasProAccess()): ?>
                                <span class="text-xs">✨</span>
                            <?php endif; ?>
                        </button>
                    </div>
                    <span class="text-sm text-gray-500 dark:text-zinc-400" id="fieldCount"><?= count($fields) ?> pergunta(s)</span>
                </div>

                <div id="fieldsList" class="space-y-3 min-h-[200px]">
                    <?php if (empty($fields) && empty($flows)): ?>
                        <div id="emptyState" class="text-center py-12 text-gray-500 dark:text-zinc-400">
                            <i class="fas fa-clipboard-question text-5xl mb-3 opacity-50"></i>
                            <p>Nenhuma pergunta adicionada ainda.</p>
                            <p class="text-sm mt-1">Use o painel ao lado para adicionar perguntas.</p>
                        </div>
                    <?php else: ?>
                        <?php
                        // Separar campos por flow_id
                        $fieldsWithoutFlow = []; // Campos que não pertencem a nenhum fluxo
                        $fieldsByFlow = []; // Campos agrupados por flow_id

                        foreach ($fields as $field) {
                            if (empty($field['flow_id'])) {
                                $fieldsWithoutFlow[] = $field;
                            } else {
                                if (!isset($fieldsByFlow[$field['flow_id']])) {
                                    $fieldsByFlow[$field['flow_id']] = [];
                                }
                                $fieldsByFlow[$field['flow_id']][] = $field;
                            }
                        }

                        // Mesclar campos livres e fluxos para renderizar ordenadamente
                        $items = [];
                        foreach ($fieldsWithoutFlow as $field) {
                            $items[] = ['type' => 'field', 'data' => $field, 'order' => $field['order_index']];
                        }
                        foreach ($flows as $flow) {
                            $items[] = ['type' => 'flow', 'data' => $flow, 'order' => $flow['order_index']];
                        }

                        // Ordenar por order_index
                        usort($items, function($a, $b) {
                            return $a['order'] - $b['order'];
                        });

                        foreach ($items as $item):
                            if ($item['type'] === 'flow'):
                                $flow = $item['data'];
                                $flowId = $flow['id'];
                                $flowFields = $fieldsByFlow[$flowId] ?? [];
                                include __DIR__ . '/render/flow_divider.php';
                            else:
                                $field = $item['data'];
                            ?>
                            <?php
                            // ============================================
                            // RENDERIZAÇÃO MODULAR DE CAMPOS
                            // Cada tipo de campo tem seu próprio arquivo
                            // ============================================
                            $field_rendered = false;

                            // Carregar renderização de campos de texto básicos
                            include __DIR__ . '/render/text_fields.php';

                            // Carregar renderização de campos de documentos (CPF/CNPJ)
                            if (!$field_rendered) {
                                include __DIR__ . '/render/document_fields.php';
                            }

                            // Carregar renderização de campos de múltipla escolha
                            if (!$field_rendered) {
                                include __DIR__ . '/render/radio_fields.php';
                            }

                            // Carregar renderização de campos de seleção (dropdown)
                            if (!$field_rendered) {
                                include __DIR__ . '/render/select_fields.php';
                            }

                            // Carregar renderização de campos especiais
                            if (!$field_rendered) {
                                include __DIR__ . '/render/special_fields.php';
                            }

                            // Carregar renderização de campos de arquivo e termos
                            if (!$field_rendered) {
                                include __DIR__ . '/render/file_terms_fields.php';
                            }

                            // Se chegou aqui, tipo de campo desconhecido
                            if (!$field_rendered):
                            ?>
                                <div class="field-item bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4" data-field-id="<?= $field['id'] ?>">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                                        <span class="text-red-600 dark:text-red-400">Tipo de campo desconhecido: <?= htmlspecialchars($field['type']) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php endif; // fim do if type === field ?>
                        <?php endforeach; ?>

                        <!-- Card da Mensagem Final -->
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4" id="successMessageCard">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start gap-3 flex-1">
                                    <div class="text-green-600 dark:text-green-400 mt-1">
                                        <i class="fas fa-check-circle text-xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <h3 class="font-medium text-green-900 dark:text-green-100">Mensagem de Sucesso</h3>
                                        </div>
                                        <p class="text-sm text-green-800 dark:text-green-200 font-medium mb-1" id="successMessageTitle"><?= htmlspecialchars($successMessageTitle) ?></p>
                                        <p class="text-sm text-green-700 dark:text-green-300" id="successMessageDescription"><?= htmlspecialchars($successMessageDescription) ?></p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="editSuccessMessage()" style="color: #4EA44B;" class="hover:opacity-80" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Painel adicionar campo -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 sticky top-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-zinc-100 mb-4" id="formTitle">Adicionar Pergunta</h2>

                <form id="fieldForm" accept-charset="UTF-8" class="space-y-4">
                    <input type="hidden" name="form_id" value="<?= $formId ?>">
                    <input type="hidden" name="field_id" id="fieldId" value="">
                    <input type="hidden" name="media" id="fieldMedia" value="">

                    <div id="fieldTypeContainer">
                        <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Tipo de Campo</label>
                        <select name="type" id="fieldType" required class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#4EA44B] dark:bg-zinc-700 dark:text-zinc-100">
                            <option value="welcome">Boas-vindas</option>
                            <option value="message">Mensagem</option>
                            <option value="loading">Carregamento</option>
                            <option value="vsl" <?= !PlanService::hasProAccess() ? 'disabled' : '' ?>>VSL (Video Sales Letter) <?= !PlanService::hasProAccess() ? '✨ PRO' : '' ?></option>
                            <option value="audio_message" <?= !PlanService::hasProAccess() ? 'disabled' : '' ?>>Mensagem de Áudio <?= !PlanService::hasProAccess() ? '✨ PRO' : '' ?></option>
                            <option value="name">Nome Completo</option>
                            <option value="text">Texto Curto</option>
                            <option value="textarea">Texto Longo</option>
                            <option value="email">E-mail</option>
                            <option value="url">URL/Website</option>
                            <option value="phone">Telefone</option>
                            <option value="date">Data</option>
                            <option value="cpf" <?= !PlanService::hasProAccess() ? 'disabled' : '' ?>>CPF <?= !PlanService::hasProAccess() ? '✨ PRO' : '' ?></option>
                            <option value="cnpj" <?= !PlanService::hasProAccess() ? 'disabled' : '' ?>>CNPJ <?= !PlanService::hasProAccess() ? '✨ PRO' : '' ?></option>
                            <option value="rg" <?= !PlanService::hasProAccess() ? 'disabled' : '' ?>>RG <?= !PlanService::hasProAccess() ? '✨ PRO' : '' ?></option>
                            <option value="money">Valor Monetário</option>
                            <option value="slider">Escala (Slider)</option>
                            <option value="rating">Avaliação (Estrelas)</option>
                            <option value="address">Endereço Completo</option>
                            <option value="file" <?= PlanService::isFree() ? 'disabled' : '' ?>>Anexo de Arquivo <?= PlanService::isFree() ? '✨ PRO' : '' ?></option>
                            <option value="terms">Termos de Uso</option>
                            <option value="radio">Múltipla Escolha</option>
                            <option value="image_choice">Múltipla Escolha com Imagem</option>
                            <option value="select">Dropdown/Lista Suspensa</option>
                            <option value="number">Número</option>
                        </select>
                        <?php if (PlanService::isFree()): ?>
                            <p class="text-xs mt-1 flex items-center gap-1" style="color: #4EA44B;">
                                <span>✨</span> Alguns campos requerem plano PRO
                            </p>
                        <?php endif; ?>
                    </div>

                    <div id="fieldLabelContainer">
                        <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1" id="fieldLabelLabel">Pergunta/Label *</label>
                        <input type="text" name="label" id="fieldLabel" required placeholder="Ex: Qual seu nome completo?"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#4EA44B] dark:bg-zinc-700 dark:text-zinc-100">
                    </div>

                    <div id="fieldDescriptionContainer">
                        <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1" id="fieldDescriptionLabel">Descrição</label>
                        <textarea name="description" id="fieldDescription" rows="2" placeholder="Adicione uma descrição opcional para ajudar o usuário..."
                               class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#4EA44B] dark:bg-zinc-700 dark:text-zinc-100"></textarea>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1" id="fieldDescriptionHint">Aparecerá abaixo do título da pergunta</p>
                    </div>

                    <div id="optionsContainer" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Opções (uma por linha)</label>
                        <textarea name="options" id="fieldOptions" rows="4" placeholder="Opção 1&#10;Opção 2&#10;Opção 3"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#4EA44B] dark:bg-zinc-700 dark:text-zinc-100"></textarea>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Digite cada opção em uma linha separada</p>
                    </div>

                    <!-- Container para configurações dinâmicas de campos -->
                    <div id="dynamicFieldConfig"></div>

                    <!-- Lógica Condicional -->
                    <div id="conditionalLogicSection" class="border border-gray-200 dark:border-zinc-700 rounded-lg p-4" style="display: none;">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-code-branch text-purple-600"></i>
                                <label class="text-sm font-medium text-gray-700 dark:text-zinc-300">Lógica Condicional</label>
                            </div>
                            <label class="switch">
                                <input type="checkbox" id="conditionalLogicEnabled" onchange="toggleConditionalLogic()">
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div id="conditionalLogicContent" style="display: none;" class="space-y-3">
                            <p class="text-xs text-gray-600 dark:text-zinc-400">
                                <i class="fas fa-info-circle mr-1"></i>
                                Mostre este campo apenas quando determinadas condições forem atendidas
                            </p>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                    Mostrar este campo quando:
                                </label>
                                <select id="conditionalLogicType" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 dark:bg-zinc-700 dark:text-zinc-100">
                                    <option value="all">Todas as condições forem atendidas (AND)</option>
                                    <option value="any">Pelo menos uma condição for atendida (OR)</option>
                                </select>
                            </div>

                            <!-- Condições -->
                            <div id="conditionalRulesContainer" class="space-y-2">
                                <!-- Condições serão adicionadas aqui -->
                            </div>

                            <button type="button" onclick="addConditionalRule()" class="w-full px-3 py-2 border-2 border-dashed border-gray-300 dark:border-zinc-600 hover:border-purple-500 dark:hover:border-purple-500 text-gray-600 dark:text-zinc-400 hover:text-purple-600 dark:hover:text-purple-400 rounded-lg text-sm transition-colors">
                                <i class="fas fa-plus mr-1"></i> Adicionar condição
                            </button>
                        </div>
                    </div>

                    <div id="fieldRequiredContainer" class="flex items-center justify-between">
                        <label class="text-sm text-gray-700 dark:text-zinc-300">Campo obrigatório</label>
                        <label class="switch">
                            <input type="checkbox" name="required" id="fieldRequired">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="flex gap-2">
                        <button type="button" onclick="openMediaModal()" id="mediaBtn" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm transition-colors">
                            <i class="fas fa-image mr-1"></i> <span id="mediaBtnText">Inserir mídia</span>
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition-colors">
                            <i class="fas fa-plus mr-1"></i> <span id="btnText">Adicionar</span>
                        </button>
                        <button type="button" onclick="cancelEdit()" id="btnCancel" style="display: none;" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg text-sm transition-colors">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Variáveis globais do PHP
const FORM_ID = <?= $formId ?>;
const FORM_PUBLIC_URL = <?= json_encode(getPublicFormUrl($formId)) ?>;
const IS_PRO_USER = <?= PlanService::hasProAccess() ? 'true' : 'false' ?>;
const USER_PLAN = "<?= PlanService::getCurrentPlan() ?>";
const USER_NAME = "<?= htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES) ?>";
const USER_EMAIL = "<?= htmlspecialchars($_SESSION['user_email'] ?? '', ENT_QUOTES) ?>";
let currentRedirectEnabled = <?= $successRedirectEnabled ?>;
let currentRedirectUrl = <?= json_encode($successRedirectUrl) ?>;
let currentRedirectType = <?= json_encode($successRedirectType) ?>;
let currentRedirectButtonText = <?= json_encode($successBtRedirect) ?>;
let currentHideBranding = <?= $hideBranding ?>;
let currentShowScore = <?= $showScore ?>;
let currentSuccessMessageMedia = <?= json_encode($customization['success_message_media'] ?? '') ?>;
</script>
<script src="<?= assetUrl('/modules/forms/builder/assets/builder.js') ?>"></script>

<?php
require_once __DIR__ . '/../../../views/layout/footer.php';
?>