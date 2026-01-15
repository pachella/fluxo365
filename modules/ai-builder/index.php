<?php
session_start();
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/PermissionManager.php';
require_once __DIR__ . '/../../core/PlanService.php';
require_once __DIR__ . '/../../core/cache_helper.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}

// Verificar se tem acesso PRO
if (!PlanService::hasProAccess()) {
    $pageTitle = "Criar com IA";
    require_once __DIR__ . '/../../views/layout/header.php';
    require_once __DIR__ . '/../../views/layout/sidebar.php';

    // Obter dados do usuário para pré-preencher o checkout
    $userName = $_SESSION['user_name'] ?? '';
    $userEmail = $_SESSION['user_email'] ?? '';
    $checkoutUrl = "https://checkout.ticto.app/OEDEF53ED?name=" . urlencode($userName) . "&email=" . urlencode($userEmail);
    ?>

    <div class="max-w-5xl mx-auto">
        <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-2xl p-12 text-center text-white">
            <div class="mb-6">
                <i data-feather="zap" class="w-20 h-20 mx-auto mb-4 stroke-1"></i>
                <h1 class="text-4xl font-bold mb-3">🤖 Criar com IA</h1>
                <p class="text-xl opacity-90">Recurso exclusivo para usuários PRO</p>
            </div>

            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-8 mb-8 max-w-2xl mx-auto">
                <h2 class="text-2xl font-semibold mb-4">✨ O que você ganha com a IA:</h2>
                <ul class="text-left space-y-3 text-lg">
                    <li class="flex items-start">
                        <span class="mr-3">✅</span>
                        <span>Crie formulários completos apenas descrevendo o que precisa</span>
                    </li>
                    <li class="flex items-start">
                        <span class="mr-3">✅</span>
                        <span>Copy persuasivo profissional em cada campo</span>
                    </li>
                    <li class="flex items-start">
                        <span class="mr-3">✅</span>
                        <span>Sugestões inteligentes de campos e validações</span>
                    </li>
                    <li class="flex items-start">
                        <span class="mr-3">✅</span>
                        <span>Economize horas de trabalho criando formulários</span>
                    </li>
                </ul>
            </div>

            <a href="<?= htmlspecialchars($checkoutUrl) ?>" target="_blank" class="inline-flex items-center gap-2 px-8 py-4 bg-white text-purple-600 font-bold rounded-xl hover:bg-gray-100 transition-all transform hover:scale-105 shadow-xl text-lg">
                <i data-feather="star" class="w-5 h-5"></i>
                Fazer Upgrade para PRO
            </a>
        </div>
    </div>

    <script>
        feather.replace();
    </script>

    <?php
    require_once __DIR__ . '/../../views/layout/footer.php';
    exit();
}

$pageTitle = "Criar com IA";
require_once __DIR__ . '/../../views/layout/header.php';
require_once __DIR__ . '/../../views/layout/sidebar.php';
?>

<div class="max-w-5xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-zinc-100 flex items-center gap-2">
                    🤖 Criar Formulário com IA
                </h1>
                <p class="text-sm text-gray-600 dark:text-zinc-400 mt-1">
                    Descreva o formulário que você precisa e deixe a IA criar para você
                </p>
            </div>
            <button onclick="resetChat()" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg text-sm transition-colors flex items-center gap-2">
                <i class="fas fa-redo"></i>
                Nova Conversa
            </button>
        </div>
    </div>

    <!-- Chat Container -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-lg overflow-hidden flex flex-col" style="height: calc(100vh - 220px);">
        <!-- Messages Area -->
        <div id="chatMessages" class="flex-1 overflow-y-auto p-6 space-y-4">
            <!-- Mensagem inicial da IA -->
            <div class="flex gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-[#4EA44B] flex items-center justify-center text-white font-semibold">
                    AI
                </div>
                <div class="flex-1">
                    <div class="bg-gray-100 dark:bg-zinc-700 rounded-lg p-4">
                        <p class="text-gray-900 dark:text-zinc-100">
                            Olá! 👋 Sou seu assistente de criação de formulários.
                        </p>
                        <p class="text-gray-900 dark:text-zinc-100 mt-2">
                            Descreva o tipo de formulário que você precisa e vou te ajudar a criar a estrutura perfeita!
                        </p>
                        <p class="text-sm text-gray-600 dark:text-zinc-400 mt-2">
                            <strong>Exemplo:</strong> "Preciso de um formulário para captar leads de uma loja de roupas"
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="border-t border-gray-200 dark:border-zinc-700 p-4">
            <form id="chatForm" class="flex gap-2">
                <input
                    type="text"
                    id="userInput"
                    placeholder="Digite sua mensagem aqui..."
                    class="flex-1 px-4 py-3 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#4EA44B] dark:bg-zinc-700 dark:text-zinc-100"
                    autocomplete="off"
                >
                <button
                    type="submit"
                    id="sendBtn"
                    class="px-6 py-3 bg-[#4EA44B] hover:bg-[#45943f] text-white rounded-lg font-semibold transition-all flex items-center gap-2"
                >
                    <i class="fas fa-paper-plane"></i>
                    Enviar
                </button>
            </form>
            <div class="mt-2 text-xs text-gray-500 dark:text-zinc-400">
                💡 Dica: Seja específico sobre o tipo de informação que precisa coletar
            </div>
        </div>
    </div>
</div>

<script src="<?= assetUrl('/modules/ai-builder/assets/chat.js') ?>"></script>

<?php
require_once __DIR__ . '/../../views/layout/footer.php';
?>
