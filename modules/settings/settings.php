<?php
// Detectar se já há sessão ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir dependências apenas se ainda não foram incluídas
if (!isset($pdo)) {
    require_once(__DIR__ . "/../../core/db.php");
}

if (!class_exists('PermissionManager')) {
    require_once(__DIR__ . "/../../core/PermissionManager.php");
}

if (!isset($_SESSION["user_id"])) {
    header("Location: /auth/login");
    exit;
}

// Usar PermissionManager global se existir, senão criar
if (!isset($permissionManager)) {
    $permissionManager = new PermissionManager(
        $_SESSION['user_role'],
        $_SESSION['user_id'] ?? null
    );
}

// Apenas admin pode acessar
if (!$permissionManager->isAdmin()) {
    echo '<div class="p-6">
            <div class="alert alert-error">
                <h1 class="text-xl font-bold mb-2">Acesso Negado</h1>
                <p>Apenas administradores podem acessar as configurações do sistema.</p>
            </div>
          </div>';
    exit;
}

// Buscar configurações atuais
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log('Erro ao buscar configurações: ' . $e->getMessage());
    $settings = [];
}

// Valores padrão se não existirem
$defaults = [
    'company_name' => 'Fluxo365',
    'primary_color' => '#6366f1',
    'secondary_color' => '#8b5cf6',
    'button_text_color' => '#ffffff',
    'use_gradient' => '0',
    'contact_email' => '',
    'contact_phone' => '',
    'logo_url' => 'https://fluxo365.com/wp-content/uploads/2026/01/logo_fluxo.svg'
];

$settings = array_merge($defaults, $settings);
?>

<style>
.settings-preview {
    padding: 2rem;
    border-radius: 8px;
    border: 2px dashed;
    text-align: center;
    margin-top: 1rem;
}

.preview-button {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
}

.preview-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>

<div class="w-full max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold">Configurações do Sistema</h1>
            <p class="text-sm opacity-60 mt-1">Personalize a aparência e informações da sua empresa</p>
        </div>
    </div>

    <!-- Card de Configurações -->
    <div class="card bg-base-200 shadow-xl">
        <div class="card-body">
            <form id="settingsForm" onsubmit="saveSettings(event)">
                <!-- Nome da Empresa -->
                <div class="form-control mb-4">
                    <label class="input input-bordered flex items-center gap-2">
                        <i data-feather="briefcase" class="w-5 h-5 opacity-60"></i>
                        <input
                            type="text"
                            id="company_name"
                            name="company_name"
                            placeholder="Nome da empresa"
                            class="grow"
                            value="<?= htmlspecialchars($settings['company_name']) ?>"
                            required
                        />
                    </label>
                </div>

                <!-- Logotipo -->
                <div class="form-control mb-4">
                    <label class="input input-bordered flex items-center gap-2">
                        <i data-feather="image" class="w-5 h-5 opacity-60"></i>
                        <input
                            type="url"
                            id="logo_url"
                            name="logo_url"
                            placeholder="URL do logotipo"
                            class="grow"
                            value="<?= htmlspecialchars($settings['logo_url']) ?>"
                        />
                    </label>
                </div>

                <!-- E-mail de Contato -->
                <div class="form-control mb-4">
                    <label class="input input-bordered flex items-center gap-2">
                        <i data-feather="mail" class="w-5 h-5 opacity-60"></i>
                        <input
                            type="email"
                            id="contact_email"
                            name="contact_email"
                            placeholder="E-mail de contato"
                            class="grow"
                            value="<?= htmlspecialchars($settings['contact_email']) ?>"
                        />
                    </label>
                </div>

                <!-- Telefone de Contato -->
                <div class="form-control mb-4">
                    <label class="input input-bordered flex items-center gap-2">
                        <i data-feather="phone" class="w-5 h-5 opacity-60"></i>
                        <input
                            type="tel"
                            id="contact_phone"
                            name="contact_phone"
                            placeholder="Telefone de contato"
                            class="grow"
                            value="<?= htmlspecialchars($settings['contact_phone']) ?>"
                        />
                    </label>
                </div>

                <div class="divider">Cores e Estilo</div>

                <!-- Cores em Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <!-- Cor Primária -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Cor Primária</span>
                        </label>
                        <div class="flex gap-2">
                            <input
                                type="color"
                                id="primary_color"
                                name="primary_color"
                                value="<?= htmlspecialchars($settings['primary_color']) ?>"
                                class="w-16 h-12 rounded-lg cursor-pointer"
                                onchange="updatePreview()"
                            />
                            <input
                                type="text"
                                value="<?= htmlspecialchars($settings['primary_color']) ?>"
                                class="input input-bordered flex-1"
                                readonly
                                onclick="document.getElementById('primary_color').click()"
                            />
                        </div>
                    </div>

                    <!-- Cor Secundária -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Cor Secundária</span>
                        </label>
                        <div class="flex gap-2">
                            <input
                                type="color"
                                id="secondary_color"
                                name="secondary_color"
                                value="<?= htmlspecialchars($settings['secondary_color']) ?>"
                                class="w-16 h-12 rounded-lg cursor-pointer"
                                onchange="updatePreview()"
                            />
                            <input
                                type="text"
                                value="<?= htmlspecialchars($settings['secondary_color']) ?>"
                                class="input input-bordered flex-1"
                                readonly
                                onclick="document.getElementById('secondary_color').click()"
                            />
                        </div>
                    </div>
                </div>

                <!-- Cor do Texto dos Botões -->
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text font-semibold">Cor do Texto dos Botões</span>
                    </label>
                    <div class="flex gap-2">
                        <input
                            type="color"
                            id="button_text_color"
                            name="button_text_color"
                            value="<?= htmlspecialchars($settings['button_text_color']) ?>"
                            class="w-16 h-12 rounded-lg cursor-pointer"
                            onchange="updatePreview()"
                        />
                        <input
                            type="text"
                            value="<?= htmlspecialchars($settings['button_text_color']) ?>"
                            class="input input-bordered flex-1"
                            readonly
                            onclick="document.getElementById('button_text_color').click()"
                        />
                    </div>
                </div>

                <!-- Usar Degradê -->
                <div class="form-control mb-4">
                    <label class="label cursor-pointer justify-start gap-4 p-3 rounded-lg hover:bg-base-200">
                        <input
                            type="checkbox"
                            id="use_gradient"
                            name="use_gradient"
                            value="1"
                            class="toggle toggle-primary toggle-sm"
                            <?= $settings['use_gradient'] == '1' ? 'checked' : '' ?>
                            onchange="updatePreview()"
                        />
                        <div class="flex-1">
                            <span class="font-semibold block">Usar degradê nos botões</span>
                            <span class="text-xs opacity-60">Aplica um gradiente da cor primária para a secundária</span>
                        </div>
                    </label>
                </div>

                <!-- Preview -->
                <div class="settings-preview border-base-300">
                    <p class="text-sm opacity-60 mb-4">Preview dos botões</p>
                    <div class="flex flex-wrap gap-3 justify-center">
                        <button type="button" id="previewButton" class="preview-button">
                            <i data-feather="check" class="w-4 h-4 inline mr-2"></i>
                            Botão Primário
                        </button>
                        <button type="button" id="previewButtonHover" class="preview-button opacity-80">
                            <i data-feather="heart" class="w-4 h-4 inline mr-2"></i>
                            Hover Effect
                        </button>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="card-actions justify-end mt-6">
                    <button type="button" class="btn btn-ghost" onclick="location.reload()">
                        <i data-feather="x" class="w-4 h-4"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i data-feather="save" class="w-4 h-4"></i>
                        Salvar Configurações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Renderizar ícones
if (typeof feather !== 'undefined') {
    feather.replace();
}

// Atualizar preview ao mudar cores
function updatePreview() {
    const primaryColor = document.getElementById('primary_color').value;
    const secondaryColor = document.getElementById('secondary_color').value;
    const buttonTextColor = document.getElementById('button_text_color').value;
    const useGradient = document.getElementById('use_gradient').checked;

    const previewButtons = document.querySelectorAll('.preview-button');

    previewButtons.forEach(btn => {
        btn.style.color = buttonTextColor;

        if (useGradient) {
            btn.style.background = `linear-gradient(135deg, ${primaryColor} 0%, ${secondaryColor} 100%)`;
        } else {
            btn.style.background = primaryColor;
        }
    });

    // Atualizar displays de texto dos color pickers
    document.querySelector('input[readonly][value][onclick*="primary_color"]').value = primaryColor;
    document.querySelector('input[readonly][value][onclick*="secondary_color"]').value = secondaryColor;
    document.querySelector('input[readonly][value][onclick*="button_text_color"]').value = buttonTextColor;
}

// Atualizar preview ao carregar
updatePreview();

// Salvar configurações
async function saveSettings(event) {
    event.preventDefault();

    try {
        const formData = new FormData(event.target);

        // Converter checkbox para 0/1
        formData.set('use_gradient', document.getElementById('use_gradient').checked ? '1' : '0');

        const res = await fetch('/modules/settings/save.php', {
            method: 'POST',
            body: formData
        });

        const result = await res.text();

        if (res.ok && result === "success") {
            await Swal.fire({
                title: 'Sucesso!',
                text: 'Configurações salvas! A página será recarregada para aplicar as mudanças.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            // Recarregar após 2 segundos para aplicar as cores
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            Swal.fire('Erro!', result || 'Erro ao salvar configurações', 'error');
        }
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao salvar configurações', 'error');
    }
}
</script>
