<?php

/**
 * Processador de eventos do webhook Kiwify
 */
class KiwifyProcessor {
    
    private $pdo;
    private $config;
    private $subscriptionService;
    
    public function __construct($pdo, $config) {
        $this->pdo = $pdo;
        $this->config = $config;
        $this->subscriptionService = new SubscriptionService($pdo);
    }
    
    /**
     * Processa evento baseado no tipo
     */
    public function process($eventType, $payload) {
        try {
            
            switch ($eventType) {
                case 'order_approved':
                    return $this->handleOrderApproved($payload);
                    
                case 'subscription_renewed':
                    return $this->handleSubscriptionRenewed($payload);
                    
                case 'subscription_canceled':
                    return $this->handleSubscriptionCanceled($payload);
                    
                case 'subscription_late':
                    return $this->handleSubscriptionLate($payload);
                    
                case 'payment_failed':
                    return $this->handlePaymentFailed($payload);
                    
                default:
                    return [
                        'success' => false,
                        'message' => 'Tipo de evento não implementado: ' . $eventType
                    ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao processar evento: ' . $e->getMessage(),
                'event_type' => $eventType,
                'trace' => $e->getTraceAsString()
            ];
        }
    }
    
    /**
     * Extrai subscription_id do payload (compatível com teste e produção)
     */
    private function getSubscriptionId($payload) {
        if (isset($payload['Subscription']['id']) && !empty($payload['Subscription']['id'])) {
            return $payload['Subscription']['id'];
        }
        if (isset($payload['subscription_id']) && !empty($payload['subscription_id'])) {
            return $payload['subscription_id'];
        }
        return $payload['order_id'] ?? null;
    }
    
    /**
     * Extrai product_id do payload
     */
    private function getProductId($payload) {
        if (isset($payload['Product']['product_id']) && !empty($payload['Product']['product_id'])) {
            return $payload['Product']['product_id'];
        }
        return null;
    }
    
    /**
     * Order approved - Criar nova assinatura
     */
    private function handleOrderApproved($payload) {
        
        $customer = $payload['Customer'] ?? [];
        $subscription = $payload['Subscription'] ?? [];
        $product = $payload['Product'] ?? [];
        $commissions = $payload['Commissions'] ?? [];
        
        $customerName = $customer['full_name'] ?? 'Cliente';
        $customerEmail = $customer['email'] ?? '';
        
        if (empty($customerEmail)) {
            throw new Exception('Email do cliente não encontrado');
        }
        
        $subscriptionId = $this->getSubscriptionId($payload);
        $productId = $this->getProductId($payload);
        
        $productName = $product['product_name'] ?? 'Produto Kiwify';
        $planName = isset($subscription['plan']['name']) ? $subscription['plan']['name'] : 'Plano';
        
        $grossAmount = isset($commissions['charge_amount']) ? ($commissions['charge_amount'] / 100) : 0;
        $netAmount = isset($commissions['my_commission']) ? ($commissions['my_commission'] / 100) : 0;
        $platformFee = isset($commissions['kiwify_fee']) ? ($commissions['kiwify_fee'] / 100) : 0;
        
        $hasAffiliate = false;
        $affiliateName = null;
        $affiliateEmail = null;
        $affiliatePercentage = null;
        $affiliateCommission = 0;
        
        if (isset($commissions['commissioned_stores']) && is_array($commissions['commissioned_stores'])) {
            foreach ($commissions['commissioned_stores'] as $store) {
                if (isset($store['type']) && $store['type'] === 'affiliate') {
                    $hasAffiliate = true;
                    $affiliateName = $store['custom_name'] ?? null;
                    $affiliateEmail = $store['email'] ?? null;
                    $affiliateCommission = isset($store['value']) ? ($store['value'] / 100) : 0;
                    
                    if ($grossAmount > 0 && $affiliateCommission > 0) {
                        $affiliatePercentage = round(($affiliateCommission / $grossAmount) * 100, 2);
                    }
                    break;
                }
            }
        }
        
        $startDate = null;
        $nextBilling = null;
        if (!empty($subscription['start_date'])) {
            $startDate = date('Y-m-d', strtotime($subscription['start_date']));
        }
        if (!empty($subscription['next_payment'])) {
            $nextBilling = date('Y-m-d', strtotime($subscription['next_payment']));
        }
        
        $clientData = [
            'name' => $customerName,
            'email' => $customerEmail,
            'phone' => $customer['mobile'] ?? null,
            'address' => $customer['street'] ?? null,
            'number' => $customer['number'] ?? null,
            'district' => $customer['neighborhood'] ?? null,
            'city' => $customer['city'] ?? null,
            'state' => $customer['state'] ?? null,
            'zip_code' => $customer['zipcode'] ?? null,
            'cpf_cnpj' => $customer['cnpj'] ?? ($customer['CPF'] ?? null)
        ];
        
        $subscriptionData = [
            'provider' => 'kiwify',
            'product_name' => $productName,
            'plan_name' => $planName,
            'price' => $grossAmount,
            'gross_amount' => $grossAmount,
            'net_amount' => $netAmount,
            'affiliate_commission' => $affiliateCommission,
            'platform_fee' => $platformFee,
            'has_affiliate' => $hasAffiliate ? 1 : 0,
            'affiliate_name' => $affiliateName,
            'affiliate_email' => $affiliateEmail,
            'affiliate_percentage' => $affiliatePercentage,
            'billing_cycle' => $subscription['plan']['frequency'] ?? 'mensal',
            'status' => 'ativa',
            'start_date' => $startDate,
            'next_billing_date' => $nextBilling,
            'platform_subscription_id' => $subscriptionId,
            'platform_customer_id' => $customerEmail,
            'platform_product_id' => $productId
        ];
        
        // Verificar se já existe
        $existingSubscription = $this->subscriptionService->findByExternalId($subscriptionId, 'kiwify');
        
        if ($existingSubscription) {
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
        
        $result = $this->subscriptionService->createFromWebhook(
            $clientData,
            $subscriptionData,
            'first_activation'
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Compra processada com sucesso',
                'subscription_id' => $result['subscription_id'],
                'action' => 'created'
            ];
        }
        
        return $result;
    }
    
    /**
     * Subscription late - Assinatura atrasada
     */
    private function handleSubscriptionLate($payload) {
        
        $subscriptionId = $this->getSubscriptionId($payload);
        
        if (empty($subscriptionId)) {
            throw new Exception('ID da assinatura não encontrado');
        }
        
        $existingSubscription = $this->subscriptionService->findByExternalId($subscriptionId, 'kiwify');
        
        if (!$existingSubscription) {
            throw new Exception('Assinatura não encontrada: ' . $subscriptionId);
        }
        
        $subscription = $payload['Subscription'] ?? [];
        $additionalData = [
            'overdue_date' => date('d/m/Y'),
            'subscription_status' => $subscription['status'] ?? 'waiting_payment',
            'next_attempt' => isset($subscription['next_payment']) ? 
                date('d/m/Y', strtotime($subscription['next_payment'])) : ''
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
                'action' => 'late'
            ];
        }
        
        return $result;
    }
    
    /**
     * Subscription renewed - Assinatura renovada
     */
    private function handleSubscriptionRenewed($payload) {
        
        $subscriptionId = $this->getSubscriptionId($payload);
        
        if (empty($subscriptionId)) {
            throw new Exception('ID da assinatura não encontrado');
        }
        
        $existingSubscription = $this->subscriptionService->findByExternalId($subscriptionId, 'kiwify');
        
        if (!$existingSubscription) {
            throw new Exception('Assinatura não encontrada: ' . $subscriptionId);
        }
        
        $subscription = $payload['Subscription'] ?? [];
        $additionalData = [
            'renewal_date' => date('d/m/Y'),
            'next_billing_date' => isset($subscription['next_payment']) ? 
                date('d/m/Y', strtotime($subscription['next_payment'])) : ''
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
     * Subscription canceled - Assinatura cancelada
     */
    private function handleSubscriptionCanceled($payload) {
        
        $subscriptionId = $this->getSubscriptionId($payload);
        
        if (empty($subscriptionId)) {
            throw new Exception('ID da assinatura não encontrado');
        }
        
        $existingSubscription = $this->subscriptionService->findByExternalId($subscriptionId, 'kiwify');
        
        if (!$existingSubscription) {
            throw new Exception('Assinatura não encontrada: ' . $subscriptionId);
        }
        
        $additionalData = [
            'cancellation_date' => date('d/m/Y'),
            'cancellation_reason' => 'Cancelamento solicitado via Kiwify'
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
     * Payment failed - Falha no pagamento
     */
    private function handlePaymentFailed($payload) {
        
        $subscriptionId = $this->getSubscriptionId($payload);
        
        if (empty($subscriptionId)) {
            throw new Exception('ID da assinatura não encontrado');
        }
        
        $existingSubscription = $this->subscriptionService->findByExternalId($subscriptionId, 'kiwify');
        
        if (!$existingSubscription) {
            throw new Exception('Assinatura não encontrada: ' . $subscriptionId);
        }
        
        $subscription = $payload['Subscription'] ?? [];
        $additionalData = [
            'failure_date' => date('d/m/Y'),
            'failure_reason' => $payload['failure_reason'] ?? 'Falha no processamento do pagamento',
            'next_attempt' => isset($subscription['next_payment']) ? 
                date('d/m/Y', strtotime($subscription['next_payment'])) : ''
        ];
        
        $result = $this->subscriptionService->updateStatus(
            $existingSubscription['id'],
            'suspensa',
            'payment_failed',
            $additionalData
        );
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Falha no pagamento processada',
                'subscription_id' => $existingSubscription['id'],
                'action' => 'payment_failed'
            ];
        }
        
        return $result;
    }
}