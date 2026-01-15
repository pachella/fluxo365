<?php

/**
 * Processador de eventos do webhook Ticto
 */
class TictoProcessor {
    
    private $pdo;
    private $config;
    private $subscriptionService;
    
    public function __construct($pdo, $config) {
        $this->pdo = $pdo;
        $this->config = $config;
        $this->subscriptionService = new SubscriptionService($pdo);
    }
    
    /**
     * Processa evento baseado no status ou tipo de evento
     */
    public function process($status, $payload) {
        try {
            
            // Verificar se é um evento de afiliação (campo 'event')
            if (isset($payload['event'])) {
                $event = $payload['event'];
                
                if ($event === 'AFFILIATION_CREATED') {
                    return $this->handleAffiliationCreated($payload);
                }
            }
            
            // Processar eventos de assinatura (campo 'status')
            switch ($status) {
                case 'authorized':
                    return $this->handleAuthorized($payload);
                
                case 'overdue':
                case 'late':
                    return $this->handleOverdue($payload);
                
                case 'canceled':
                case 'cancelled':
                    return $this->handleCanceled($payload);
                
                case 'charged':
                case 'subscription_renewed':
                    return $this->handleRenewed($payload);
                    
                default:
                    return [
                        'success' => false,
                        'message' => 'Status não implementado: ' . $status
                    ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao processar evento: ' . $e->getMessage(),
                'status' => $status,
                'trace' => $e->getTraceAsString()
            ];
        }
    }
    
    /**
     * Pagamento aprovado - criar nova assinatura
     */
    private function handleAuthorized($payload) {
        
        $customer = $payload['customer'] ?? [];
        $order = $payload['order'] ?? [];
        $item = $payload['item'] ?? [];
        $subscriptions = $payload['subscriptions'] ?? [];
        $producer = $payload['producer'] ?? [];
        $affiliates = $payload['affiliates'] ?? [];
        
        // Dados do cliente
        $customerName = $customer['name'] ?? 'Cliente Ticto';
        $customerEmail = $customer['email'] ?? '';
        
        if (empty($customerEmail)) {
            throw new Exception('Email do cliente não encontrado');
        }
        
        // IDs e valores
        $orderHash = $order['hash'] ?? null;
        $subscriptionId = !empty($subscriptions) ? $subscriptions[0]['id'] : $orderHash;
        $productId = $item['product_id'] ?? null;
        $productName = $item['product_name'] ?? 'Produto Ticto';
        $offerName = $item['offer_name'] ?? 'Oferta';
        
        // Valores em centavos - converter para reais
        $paidAmount = isset($order['paid_amount']) ? ($order['paid_amount'] / 100) : 0;
        $itemAmount = isset($item['amount']) ? ($item['amount'] / 100) : 0;
        
        // Comissões
        $producerAmount = isset($producer['amount']) ? ($producer['amount'] / 100) : 0;
        $platformFee = isset($payload['marketplace_commission']) ? ($payload['marketplace_commission'] / 100) : 0;
        
        // Afiliado
        $hasAffiliate = !empty($affiliates);
        $affiliateName = null;
        $affiliateEmail = null;
        $affiliateCommission = 0;
        $affiliatePercentage = null;
        
        if ($hasAffiliate) {
            $affiliate = $affiliates[0];
            $affiliateName = $affiliate['name'] ?? null;
            $affiliateEmail = $affiliate['email'] ?? null;
            $affiliateCommission = isset($affiliate['amount']) ? ($affiliate['amount'] / 100) : 0;
            
            if ($paidAmount > 0 && $affiliateCommission > 0) {
                $affiliatePercentage = round(($affiliateCommission / $paidAmount) * 100, 2);
            }
        }
        
        // Dados da assinatura (se existir)
        $nextBilling = null;
        $billingCycle = 'unico';
        
        if (!empty($subscriptions)) {
            $subscription = $subscriptions[0];
            $nextBilling = isset($subscription['next_charge']) ? 
                date('Y-m-d', strtotime($subscription['next_charge'])) : null;
            
            $interval = $subscription['interval'] ?? 1;
            $billingCycle = $interval == 1 ? 'mensal' : $interval . ' meses';
        }
        
        $startDate = isset($order['order_date']) ? 
            date('Y-m-d', strtotime($order['order_date'])) : date('Y-m-d');
        
        // Montar dados do cliente
        $clientData = [
            'name' => $customerName,
            'email' => $customerEmail,
            'phone' => isset($customer['phone']) ? 
                ($customer['phone']['ddi'] . $customer['phone']['ddd'] . $customer['phone']['number']) : null,
            'address' => $customer['address']['street'] ?? null,
            'number' => $customer['address']['street_number'] ?? null,
            'district' => $customer['address']['neighborhood'] ?? null,
            'city' => $customer['address']['city'] ?? null,
            'state' => $customer['address']['state'] ?? null,
            'zip_code' => $customer['address']['zip_code'] ?? null,
            'cpf_cnpj' => $customer['cpf'] ?? $customer['cnpj'] ?? null
        ];
        
        // Montar dados da assinatura com campos genéricos platform_*
        $subscriptionData = [
            'provider' => 'ticto',
            'product_name' => $productName,
            'plan_name' => $offerName,
            'price' => $paidAmount,
            'gross_amount' => $paidAmount,
            'net_amount' => $producerAmount,
            'affiliate_commission' => $affiliateCommission,
            'platform_fee' => $platformFee,
            'has_affiliate' => $hasAffiliate ? 1 : 0,
            'affiliate_name' => $affiliateName,
            'affiliate_email' => $affiliateEmail,
            'affiliate_percentage' => $affiliatePercentage,
            'billing_cycle' => $billingCycle,
            'status' => 'ativa',
            'start_date' => $startDate,
            'next_billing_date' => $nextBilling,
            'platform_subscription_id' => $subscriptionId,
            'platform_customer_id' => $customer['code'] ?? $customerEmail,
            'platform_product_id' => $productId,
            'platform_order_hash' => $orderHash
        ];
        
        // Verificar se assinatura já existe
        $existingSubscription = $this->subscriptionService->findByExternalId(
            $subscriptionId, 
            'ticto'
        );
        
        if ($existingSubscription) {
            // Atualizar assinatura existente
            $result = $this->subscriptionService->updateStatus(
                $existingSubscription['id'],
                'ativa',
                'first_activation',
                $subscriptionData
            );
            
            return [
                'success' => true,
                'message' => 'Assinatura reativada',
                'subscription_id' => $existingSubscription['id'],
                'action' => 'reactivated'
            ];
        }
        
        // Criar nova assinatura
        $result = $this->subscriptionService->createFromWebhook(
            $clientData,
            $subscriptionData,
            'first_activation'
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Assinatura criada com sucesso',
                'subscription_id' => $result['subscription_id'],
                'action' => 'created'
            ];
        }
        
        return $result;
    }
    
    /**
     * Pagamento atrasado
     */
    private function handleOverdue($payload) {
        
        $subscriptions = $payload['subscriptions'] ?? [];
        
        if (empty($subscriptions)) {
            throw new Exception('Dados de assinatura não encontrados');
        }
        
        $subscription = $subscriptions[0];
        $subscriptionId = $subscription['id'] ?? null;
        
        if (!$subscriptionId) {
            throw new Exception('ID da assinatura não encontrado');
        }
        
        // Buscar assinatura interna
        $existingSubscription = $this->subscriptionService->findByExternalId(
            $subscriptionId, 
            'ticto'
        );
        
        if (!$existingSubscription) {
            throw new Exception('Assinatura não encontrada: ' . $subscriptionId);
        }
        
        $additionalData = [
            'overdue_date' => date('d/m/Y'),
            'next_attempt' => isset($subscription['next_charge']) ? 
                date('d/m/Y', strtotime($subscription['next_charge'])) : null
        ];
        
        $result = $this->subscriptionService->updateStatus(
            $existingSubscription['id'],
            'vencida',
            'subscription_late',
            $additionalData
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Atraso processado',
                'subscription_id' => $existingSubscription['id'],
                'action' => 'overdue'
            ];
        }
        
        return $result;
    }
    
    /**
     * Assinatura cancelada
     */
    private function handleCanceled($payload) {
        
        $subscriptions = $payload['subscriptions'] ?? [];
        
        if (empty($subscriptions)) {
            throw new Exception('Dados de assinatura não encontrados');
        }
        
        $subscription = $subscriptions[0];
        $subscriptionId = $subscription['id'] ?? null;
        
        if (!$subscriptionId) {
            throw new Exception('ID da assinatura não encontrado');
        }
        
        // Buscar assinatura interna
        $existingSubscription = $this->subscriptionService->findByExternalId(
            $subscriptionId, 
            'ticto'
        );
        
        if (!$existingSubscription) {
            throw new Exception('Assinatura não encontrada: ' . $subscriptionId);
        }
        
        $additionalData = [
            'cancellation_date' => date('d/m/Y'),
            'cancellation_reason' => 'Cancelamento via Ticto',
            'canceled_at' => isset($subscription['canceled_at']) ? 
                date('d/m/Y', strtotime($subscription['canceled_at'])) : date('d/m/Y')
        ];
        
        $result = $this->subscriptionService->updateStatus(
            $existingSubscription['id'],
            'cancelada',
            'subscription_cancelled',
            $additionalData
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Cancelamento processado',
                'subscription_id' => $existingSubscription['id'],
                'action' => 'canceled'
            ];
        }
        
        return $result;
    }
    
    /**
     * Assinatura renovada
     */
    private function handleRenewed($payload) {
        
        $subscriptions = $payload['subscriptions'] ?? [];
        $order = $payload['order'] ?? [];
        
        if (empty($subscriptions)) {
            throw new Exception('Dados de assinatura não encontrados');
        }
        
        $subscription = $subscriptions[0];
        $subscriptionId = $subscription['id'] ?? null;
        
        if (!$subscriptionId) {
            throw new Exception('ID da assinatura não encontrado');
        }
        
        // Buscar assinatura interna
        $existingSubscription = $this->subscriptionService->findByExternalId(
            $subscriptionId, 
            'ticto'
        );
        
        if (!$existingSubscription) {
            throw new Exception('Assinatura não encontrada: ' . $subscriptionId);
        }
        
        $paidAmount = isset($order['paid_amount']) ? ($order['paid_amount'] / 100) : 0;
        
        $additionalData = [
            'renewal_date' => date('d/m/Y'),
            'amount_paid' => $paidAmount,
            'next_billing_date' => isset($subscription['next_charge']) ? 
                date('d/m/Y', strtotime($subscription['next_charge'])) : null
        ];
        
        $result = $this->subscriptionService->updateStatus(
            $existingSubscription['id'],
            'ativa',
            'renewal_payment',
            $additionalData
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Renovação processada',
                'subscription_id' => $existingSubscription['id'],
                'action' => 'renewed'
            ];
        }
        
        return $result;
    }
    
    /**
     * Afiliação criada/aprovada
     */
    private function handleAffiliationCreated($payload) {
        
        error_log("=== INICIO handleAffiliationCreated ===");
        error_log("Payload recebido: " . json_encode($payload));
        
        $affiliateName = $payload['name'] ?? '';
        $affiliateEmail = $payload['email'] ?? '';
        $affiliateId = $payload['affiliate_id'] ?? null;
        $phone = $payload['phone'] ?? null;
        $document = $payload['document'] ?? null;
        $code = $payload['code'] ?? null;
        $commissionPercentage = $payload['commission_percentage'] ?? 0;
        $productId = $payload['product_id'] ?? null;
        $productName = $payload['product_name'] ?? null;
        $affiliationDate = $payload['affiliation_date'] ?? date('Y-m-d H:i:s');
        $status = $payload['status'] ?? 'pending';
        
        if (empty($affiliateEmail)) {
            throw new Exception('Email do afiliado não encontrado');
        }
        
        if (empty($affiliateName)) {
            throw new Exception('Nome do afiliado não encontrado');
        }
        
        error_log("Verificando se afiliado existe: email=$affiliateEmail, affiliate_id=$affiliateId");
        
        // Verificar se afiliado já existe por email ou affiliate_id
        $checkStmt = $this->pdo->prepare("
            SELECT id, name, email FROM affiliates 
            WHERE email = :email OR (ticto_affiliate_id = :affiliate_id AND ticto_affiliate_id IS NOT NULL)
        ");
        
        $checkStmt->execute([
            ':email' => $affiliateEmail,
            ':affiliate_id' => $affiliateId
        ]);
        
        $existingAffiliate = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingAffiliate) {
            error_log("Afiliado já existe, atualizando ID: " . $existingAffiliate['id']);
            
            // Atualizar afiliado existente
            $updateStmt = $this->pdo->prepare("
                UPDATE affiliates SET
                    name = :name,
                    email = :email,
                    cpf_cnpj = :cpf_cnpj,
                    phone = :phone,
                    status = :status,
                    ticto_affiliate_id = :ticto_affiliate_id,
                    ticto_code = :ticto_code,
                    ticto_commission_percentage = :ticto_commission_percentage,
                    ticto_product_id = :ticto_product_id,
                    ticto_product_name = :ticto_product_name,
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            $updateStmt->execute([
                ':name' => $affiliateName,
                ':email' => $affiliateEmail,
                ':cpf_cnpj' => $document,
                ':phone' => $phone,
                ':status' => $status === 'approved' ? 'active' : 'pending',
                ':ticto_affiliate_id' => $affiliateId,
                ':ticto_code' => $code,
                ':ticto_commission_percentage' => $commissionPercentage,
                ':ticto_product_id' => $productId,
                ':ticto_product_name' => $productName,
                ':id' => $existingAffiliate['id']
            ]);
            
            return [
                'success' => true,
                'message' => 'Afiliado atualizado',
                'affiliate_id' => $existingAffiliate['id'],
                'action' => 'updated'
            ];
        }
        
        error_log("Criando novo afiliado...");
        
        // Criar novo afiliado
        $insertStmt = $this->pdo->prepare("
            INSERT INTO affiliates (
                name, email, cpf_cnpj, phone, status,
                ticto_affiliate_id, ticto_code, ticto_commission_percentage,
                ticto_product_id, ticto_product_name, created_at
            ) VALUES (
                :name, :email, :cpf_cnpj, :phone, :status,
                :ticto_affiliate_id, :ticto_code, :ticto_commission_percentage,
                :ticto_product_id, :ticto_product_name, NOW()
            )
        ");
        
        $insertStmt->execute([
            ':name' => $affiliateName,
            ':email' => $affiliateEmail,
            ':cpf_cnpj' => $document,
            ':phone' => $phone,
            ':status' => $status === 'approved' ? 'active' : 'pending',
            ':ticto_affiliate_id' => $affiliateId,
            ':ticto_code' => $code,
            ':ticto_commission_percentage' => $commissionPercentage,
            ':ticto_product_id' => $productId,
            ':ticto_product_name' => $productName
        ]);
        
        $newAffiliateId = $this->pdo->lastInsertId();
        error_log("Afiliado criado com ID: $newAffiliateId");
        
        // Criar usuário para o afiliado
        error_log("Chamando createUserForAffiliate...");
        $tempPassword = $this->createUserForAffiliate($newAffiliateId, $affiliateName, $affiliateEmail);
        
        if ($tempPassword === false) {
            error_log("ERRO: Falha ao criar usuário para o afiliado $newAffiliateId");
            return [
                'success' => true,
                'message' => 'Afiliado criado, mas houve erro ao criar o usuário',
                'affiliate_id' => $newAffiliateId,
                'action' => 'created',
                'user_created' => false,
                'warning' => 'Usuário não foi criado - verificar logs'
            ];
        }
        
        error_log("Usuario criado com sucesso! Senha: $tempPassword");
        
        // Enviar email de boas-vindas
        $emailSent = false;
        if ($tempPassword) {
            error_log("Enviando email de boas-vindas...");
            $emailSent = $this->subscriptionService->sendAffiliateWelcomeEmail(
                $affiliateEmail,
                $affiliateName,
                $tempPassword,
                [
                    'product_name' => $productName,
                    'commission_percentage' => $commissionPercentage,
                    'code' => $code
                ]
            );
            
            if ($emailSent) {
                error_log("Email enviado com sucesso!");
            } else {
                error_log("AVISO: Falha ao enviar email");
            }
        }
        
        error_log("=== FIM handleAffiliationCreated ===");
        
        return [
            'success' => true,
            'message' => 'Afiliado e usuário criados com sucesso',
            'affiliate_id' => $newAffiliateId,
            'action' => 'created',
            'user_created' => true,
            'email_sent' => $emailSent
        ];
    }
    
    /**
     * Criar usuário para o afiliado
     */
    private function createUserForAffiliate($affiliateId, $name, $email) {
        try {
            error_log("--- Iniciando createUserForAffiliate ---");
            error_log("Parametros: affiliateId=$affiliateId, name=$name, email=$email");
            
            // Verificar se usuário já existe
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            
            if ($stmt->fetch()) {
                error_log("Usuario já existe com email: $email");
                return null;
            }
            
            error_log("Usuario não existe, criando novo...");
            
            // Gerar senha temporária
            $tempPassword = $this->generateTempPassword();
            error_log("Senha temporária gerada: $tempPassword");
            
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
            error_log("Hash da senha gerado");
            
            // Criar usuário usando client_id em vez de affiliate_id
            $stmt = $this->pdo->prepare("
                INSERT INTO users (
                    name, email, password, role, client_id, status
                ) VALUES (
                    :name, :email, :password, :role, :client_id, :status
                )
            ");
            
            error_log("Executando INSERT na tabela users...");
            
            $result = $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':role' => 'affiliate',
                ':client_id' => $affiliateId,
                ':status' => 'active'
            ]);
            
            if ($result) {
                $userId = $this->pdo->lastInsertId();
                error_log("Usuario criado com sucesso! ID: $userId");
                return $tempPassword;
            } else {
                error_log("ERRO: INSERT retornou false");
                error_log("ErrorInfo: " . print_r($stmt->errorInfo(), true));
                return false;
            }
            
        } catch (Exception $e) {
            error_log("EXCEÇÃO ao criar usuario para afiliado {$affiliateId}:");
            error_log("Mensagem: " . $e->getMessage());
            error_log("Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Gerar senha temporária
     */
    private function generateTempPassword() {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return substr(str_shuffle($chars), 0, 12);
    }
}