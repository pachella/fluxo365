# Sistema de Webhook Ticto - Versão Simplificada

## Resumo

Sistema simples que ativa/desativa o plano PRO quando o webhook da Ticto é recebido.

## Configuração

### 1. Executar Migração do Banco de Dados

Execute o SQL para adicionar apenas 1 campo:

```bash
mysql -u webformtalk_forms -p webformtalk_forms < /home/user/form_system/migrations/add_subscription_fields.sql
```

Ou execute manualmente:

```sql
ALTER TABLE users
ADD COLUMN IF NOT EXISTS pro_expires_at DATETIME DEFAULT NULL AFTER user_role;

CREATE INDEX IF NOT EXISTS idx_pro_expires ON users(pro_expires_at);
```

### 2. Configurar Webhook na Ticto

**URL do Webhook:** `https://formtalk.app/webhooks/ticto.php`

**Eventos para marcar (baseado nos nomes em português da Ticto):**

**✅ ATIVAR PRO (marcar estes):**
- ✅ Venda Realizada
- ✅ [Assinatura] - Período de Testes Iniciado
- ✅ [Assinatura] - Retomada
- ✅ [Assinatura] - Extendida

**❌ DESATIVAR PRO (marcar estes):**
- ✅ [Assinatura] - Cancelada
- ✅ [Assinatura] - Encerrada (Todas as Cobranças Finalizadas)
- ✅ Chargeback
- ✅ Reembolso

**⚠️ IMPORTANTE:** O webhook processa o campo `status` do payload:
- Status `paid`, `approved`, `active` = Ativa PRO
- Status `cancelled`, `canceled`, `refunded`, `chargeback`, `expired` = Desativa PRO
- Outros status (como `waiting_payment`) = Ignorados

**📧 Email do usuário:** O webhook busca o email em `customer.email` do payload.

### 3. Configurar CRON (expiração automática)

```bash
crontab -e
```

Adicionar:
```
0 0 * * * /usr/bin/php /home/user/form_system/cron/check_expired_subscriptions.php
```

### 4. Testar Webhook

```bash
curl -X POST https://formtalk.app/webhooks/ticto.php \
  -H "Content-Type: application/json" \
  -d '{
    "event": "subscription.created",
    "email": "seu@email.com"
  }'
```

## Como Funciona

### Quando usuário assina:
1. Ticto envia webhook com evento de ativação
2. Sistema busca usuário pelo email
3. Atualiza: `plan = 'pro'` e `pro_expires_at = +30 dias`

### Quando cancelar ou falhar:
1. Ticto envia webhook de cancelamento
2. Sistema atualiza: `plan = 'free'` e `pro_expires_at = NULL`

### Expiração automática (CRON):
1. CRON roda diariamente à meia-noite
2. Busca usuários com `pro_expires_at < NOW()` e `plan = 'pro'`
3. Atualiza para `plan = 'free'` e `pro_expires_at = NULL`

## Logs

**Webhook:** `/home/user/form_system/webhooks/ticto_webhook.log`
```bash
tail -f /home/user/form_system/webhooks/ticto_webhook.log
```

**CRON:** `/home/user/form_system/cron/subscriptions_check.log`
```bash
tail -f /home/user/form_system/cron/subscriptions_check.log
```

## Estrutura do Banco

```sql
users:
  - id
  - email
  - user_name
  - role (admin/client)
  - plan (free/pro) ← Esta coluna é atualizada
  - pro_expires_at (DATETIME) ← NOVA COLUNA
```

## Ajustes Necessários

⚠️ **IMPORTANTE:** Ajuste os campos no `webhooks/ticto.php` conforme o formato real da Ticto:

```php
$event = $payload['event'] ?? $payload['type'] ?? '';
$customerEmail = $payload['email'] ?? $payload['customer']['email'] ?? '';
```

Verifique o log `ticto_webhook.log` para ver o formato exato dos dados que a Ticto envia.
