<?php
class PermissionManager {
    private $userRole;
    private $clientId;
    private $modulePermissions = [];
    
    public function __construct($userRole = null, $clientId = null) {
        $this->userRole = $userRole;
        $this->clientId = $clientId;
        
        // Carregar permissões dos módulos dinamicamente
        $this->loadModulePermissions();
    }
    
    /**
     * Carrega permissões de todos os módulos automaticamente
     */
    private function loadModulePermissions() {
        $modulesPath = __DIR__ . '/../modules';
        
        if (!is_dir($modulesPath)) {
            return;
        }
        
        $modules = array_diff(scandir($modulesPath), ['.', '..']);
        
        foreach ($modules as $module) {
            $configFile = "$modulesPath/$module/config.php";
            
            if (file_exists($configFile)) {
                $config = require $configFile;
                
                if (isset($config['roles']) && is_array($config['roles'])) {
                    foreach ($config['roles'] as $role) {
                        if (!isset($this->modulePermissions[$role])) {
                            $this->modulePermissions[$role] = [];
                        }
                        $this->modulePermissions[$role][] = $config['name'];
                    }
                }
            }
        }
    }
    
    /**
     * Verifica se usuário está logado
     */
    public function isLoggedIn() {
        return !empty($this->userRole);
    }
    
    /**
     * Verifica se é admin
     */
    public function isAdmin() {
        return $this->userRole === 'admin';
    }
    
    /**
     * Verifica se é cliente
     */
    public function isClient() {
        return $this->userRole === 'client';
    }
    
    /**
     * Verifica se pode acessar um módulo específico
     */
    public function canAccessModule($module) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        if (!isset($this->modulePermissions[$this->userRole])) {
            return false;
        }
        
        return in_array($module, $this->modulePermissions[$this->userRole]);
    }
    
    /**
     * Retorna lista de módulos que o usuário pode acessar
     */
    public function getAvailableModules() {
        if (!$this->isLoggedIn()) {
            return [];
        }
        
        return $this->modulePermissions[$this->userRole] ?? [];
    }
    
    /**
     * Verifica se pode visualizar todos os registros (admin)
     */
    public function canViewAllRecords() {
        return $this->isAdmin();
    }
    
    /**
     * Verifica se pode editar um registro específico
     */
    public function canEditRecord($recordUserId) {
        if ($this->isAdmin()) {
            return true; // Admin pode editar tudo
        }
        
        if ($this->isClient()) {
            return $recordUserId == $this->clientId; // Cliente só edita seus próprios
        }
        
        return false;
    }
    
    /**
     * Verifica se pode deletar um registro específico
     */
    public function canDeleteRecord($recordUserId) {
        if ($this->isAdmin()) {
            return true; // Admin pode deletar tudo
        }

        if ($this->isClient()) {
            return $recordUserId == $this->clientId; // Cliente só deleta seus próprios
        }

        return false;
    }

    /**
     * Verifica se pode editar um formulário específico
     */
    public function canEditForm($pdo, $formId) {
        if ($this->isAdmin()) {
            return true; // Admin pode editar qualquer formulário
        }

        if ($this->isClient()) {
            // Cliente só pode editar formulários que pertencem a ele
            try {
                $stmt = $pdo->prepare("SELECT user_id FROM forms WHERE id = :id");
                $stmt->execute(['id' => $formId]);
                $form = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($form && $form['user_id'] == $this->clientId) {
                    return true;
                }
            } catch (PDOException $e) {
                error_log('Erro ao verificar permissão de formulário: ' . $e->getMessage());
                return false;
            }
        }

        return false;
    }

    /**
     * Redireciona para página de acesso negado
     */
    public function denyAccess($message = 'Acesso negado') {
        http_response_code(403);
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }
        
        // Se existir uma view de erro 403, carrega ela
        $errorPage = __DIR__ . '/../views/error_403.php';
        if (file_exists($errorPage)) {
            include $errorPage;
        } else {
            echo "<h1>403 - Acesso Negado</h1><p>$message</p>";
        }
        exit;
    }
    
    /**
     * Filtro para consultas SQL baseado no role
     */
    public function getSQLFilter($table = '') {
        if ($this->isAdmin()) {
            return ''; // Admin vê tudo
        }
        
        if ($this->isClient() && $this->clientId) {
            // Cliente vê apenas seus próprios registros
            return " WHERE user_id = {$this->clientId}";
        }
        
        return ' WHERE 1=0'; // Se não for admin ou cliente válido, não mostra nada
    }
}
?>