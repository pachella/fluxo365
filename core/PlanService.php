<?php
/**
 * PlanService - Gerencia validação de planos (FREE/PRO/FULL)
 * Supersites - Sistema de gestão
 */

class PlanService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Verifica se o usuário atual é PRO
     * @return bool
     */
    public static function isPro() {
        $plan = self::getCurrentPlan();
        return $plan === 'pro';
    }

    /**
     * Verifica se o usuário atual é FULL
     * @return bool
     */
    public static function isFull() {
        $plan = self::getCurrentPlan();
        return $plan === 'full';
    }

    /**
     * Verifica se o usuário atual é FREE
     * @return bool
     */
    public static function isFree() {
        $plan = self::getCurrentPlan();
        return $plan === 'free';
    }

    /**
     * Verifica se o usuário tem plano PRO ou superior (PRO ou FULL)
     * @return bool
     */
    public static function hasProAccess() {
        return self::isPro() || self::isFull();
    }

    /**
     * Retorna o plano do usuário atual (busca do banco)
     * @return string 'free', 'pro' ou 'full'
     */
    public static function getCurrentPlan() {
        if (!isset($_SESSION['user_id'])) {
            return 'free';
        }

        global $pdo;

        try {
            $stmt = $pdo->prepare("SELECT plan FROM users WHERE id = :id");
            $stmt->execute(['id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $plan = $user['plan'] ?? 'free';

            // Validar que o plano é válido
            if (!in_array($plan, ['free', 'pro', 'full'])) {
                return 'free';
            }

            return $plan;

        } catch (Exception $e) {
            error_log("Erro ao buscar plano: " . $e->getMessage());
            return 'free';
        }
    }

    /**
     * Bloqueia acesso se não for PRO ou superior (redireciona)
     * @param string $redirectUrl - URL para redirecionar
     */
    public static function requirePro($redirectUrl = '/upgrade') {
        if (!self::hasProAccess()) {
            header("Location: $redirectUrl");
            exit;
        }
    }

    /**
     * Verifica se pode acessar um campo específico
     * @param string $fieldType - Tipo do campo
     * @return bool
     */
    public static function canUseField($fieldType) {
        // Campos PRO (apenas PRO e FULL)
        $proFields = ['rg', 'cpf', 'cnpj'];

        if (in_array($fieldType, $proFields)) {
            return self::hasProAccess();
        }

        return true; // Campos não-PRO são liberados para todos
    }

    /**
     * Verifica se pode acessar uma feature específica
     * @param string $feature - Nome da feature
     * @return bool
     */
    public static function canAccess($feature) {
        // Features que requerem PRO ou FULL
        $proFeatures = [
            'blocking_system',        // Sistema de bloqueio
            'email_cc',              // E-mails Cópia (CC)
            'statistics',            // Estatísticas
            'conditional_flows',     // Fluxos condicionais
            'hide_branding',         // Remover marca
            'final_redirect',        // Redirecionamento final
            // Integrações (exceto Google Sheets)
            'integration_webhook',
            'integration_email',
            'integration_zapier',
            // Rastreamento (exceto UTM)
            'tracking_pixel',
            'tracking_gtm',
            'tracking_ga4',
        ];

        // Features FREE (todos têm acesso)
        $freeFeatures = [
            'integration_google_sheets', // Google Sheets é free
            'tracking_utm',              // UTM é free
        ];

        // Se é feature free, libera para todos
        if (in_array($feature, $freeFeatures)) {
            return true;
        }

        // Se é feature PRO, verifica se tem PRO ou FULL
        if (in_array($feature, $proFeatures)) {
            return self::hasProAccess();
        }

        // Features não listadas são liberadas por padrão
        return true;
    }

    /**
     * Retorna resposta JSON de bloqueio (para AJAX)
     * @param string $message - Mensagem customizada
     * @return string JSON
     */
    public static function blockResponse($message = null) {
        $defaultMessage = "Esta funcionalidade é exclusiva para planos PRO.";

        return json_encode([
            'success' => false,
            'error' => $message ?? $defaultMessage,
            'upgrade_required' => true,
            'current_plan' => self::getCurrentPlan()
        ]);
    }

    /**
     * Retorna HTML de badge do plano
     * @param string $class - Classes CSS adicionais
     * @return string HTML
     */
    public static function getBadge($class = '') {
        $plan = self::getCurrentPlan();

        $badges = [
            'full' => '<span class="badge-full ' . $class . '">FULL</span>',
            'pro' => '<span class="badge-pro ' . $class . '">PRO</span>',
            'free' => '<span class="badge-free ' . $class . '">FREE</span>',
        ];

        return $badges[$plan] ?? $badges['free'];
    }

    /**
     * Atualiza o plano de um usuário no banco
     * @param int $userId
     * @param string $newPlan - 'free', 'pro' ou 'full'
     * @return bool
     */
    public function updatePlan($userId, $newPlan) {
        try {
            // Validar plano
            if (!in_array($newPlan, ['free', 'pro', 'full'])) {
                return false;
            }

            $stmt = $this->pdo->prepare("
                UPDATE users
                SET plan = :plan
                WHERE id = :id
            ");

            return $stmt->execute([
                'plan' => $newPlan,
                'id' => $userId
            ]);

        } catch (Exception $e) {
            error_log("Erro ao atualizar plano: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retorna limites do plano
     * @return array
     */
    public static function getLimits() {
        $limits = [
            'free' => [
                'max_forms' => 2,
                'max_responses' => 150,
                'max_folders' => 3,
            ],
            'pro' => [
                'max_forms' => -1,      // ilimitado
                'max_responses' => 1000,
                'max_folders' => 10,
            ],
            'full' => [
                'max_forms' => -1,      // ilimitado
                'max_responses' => -1,  // ilimitado
                'max_folders' => -1,    // ilimitado
            ]
        ];

        $currentPlan = self::getCurrentPlan();
        return $limits[$currentPlan] ?? $limits['free'];
    }

    /**
     * Verifica se atingiu o limite de algo
     * @param string $limitType - 'forms', 'responses', 'folders'
     * @param int $currentValue - Valor atual
     * @return bool
     */
    public static function hasReachedLimit($limitType, $currentValue) {
        $limits = self::getLimits();

        $limitKey = 'max_' . $limitType;

        if (!isset($limits[$limitKey])) {
            return false;
        }

        $limit = $limits[$limitKey];

        // -1 significa ilimitado
        if ($limit === -1) {
            return false;
        }

        return $currentValue >= $limit;
    }

    /**
     * Retorna contagem atual de um recurso do usuário
     * @param string $resource - 'forms', 'responses', 'folders'
     * @param int $userId - ID do usuário (opcional, usa sessão se não informado)
     * @return int
     */
    public static function getCount($resource, $userId = null) {
        if ($userId === null && !isset($_SESSION['user_id'])) {
            return 0;
        }

        $userId = $userId ?? $_SESSION['user_id'];
        global $pdo;

        try {
            switch ($resource) {
                case 'forms':
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM forms WHERE user_id = :user_id");
                    $stmt->execute(['user_id' => $userId]);
                    break;

                case 'responses':
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as count
                        FROM form_responses fr
                        INNER JOIN forms f ON fr.form_id = f.id
                        WHERE f.user_id = :user_id
                    ");
                    $stmt->execute(['user_id' => $userId]);
                    break;

                case 'folders':
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM folders WHERE user_id = :user_id");
                    $stmt->execute(['user_id' => $userId]);
                    break;

                default:
                    return 0;
            }

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);

        } catch (Exception $e) {
            error_log("Erro ao contar $resource: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Verifica se pode criar mais um recurso (formulário, pasta, etc)
     * @param string $resource - 'forms', 'responses', 'folders'
     * @return bool
     */
    public static function canCreate($resource) {
        $currentCount = self::getCount($resource);
        return !self::hasReachedLimit($resource, $currentCount);
    }

    /**
     * Retorna mensagem de limite atingido
     * @param string $resource - 'forms', 'responses', 'folders'
     * @return string
     */
    public static function getLimitMessage($resource) {
        $limits = self::getLimits();
        $limitKey = 'max_' . $resource;
        $limit = $limits[$limitKey] ?? 0;

        $messages = [
            'forms' => "Você atingiu o limite de $limit formulários do plano FREE. Faça upgrade para criar mais!",
            'responses' => "Você atingiu o limite de $limit respostas do plano FREE. Faça upgrade para receber mais respostas!",
            'folders' => "Você atingiu o limite de $limit pastas do plano FREE. Faça upgrade para criar mais!",
        ];

        return $messages[$resource] ?? "Limite atingido. Faça upgrade para continuar!";
    }
}
