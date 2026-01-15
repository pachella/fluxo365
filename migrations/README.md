# Migrações do Banco de Dados

## Funcionalidade de Redirecionamento

### Como Aplicar a Migração

**Opção 1 - Via navegador (Recomendado):**
1. Acesse: `http://seu-dominio.com/migrations/apply_redirect_migration.php`
2. A migração será aplicada automaticamente
3. Você verá uma mensagem de sucesso

**Opção 2 - Via linha de comando:**
```bash
php migrations/apply_redirect_migration.php
```

**Opção 3 - Executar SQL manualmente:**
```sql
ALTER TABLE form_customizations
ADD COLUMN success_redirect_enabled TINYINT(1) DEFAULT 0 COMMENT 'Ativa/desativa redirecionamento após sucesso',
ADD COLUMN success_redirect_url VARCHAR(500) DEFAULT NULL COMMENT 'URL de destino do redirecionamento',
ADD COLUMN success_redirect_type VARCHAR(20) DEFAULT 'automatic' COMMENT 'Tipo: automatic (automático) ou button (via botão)',
ADD COLUMN success_bt_redirect VARCHAR(255) DEFAULT 'Continuar' COMMENT 'Texto do botão de redirecionamento';
```

### O que esta migração faz?

Adiciona 4 novas colunas à tabela `form_customizations`:
- `success_redirect_enabled` - Liga/desliga o redirecionamento
- `success_redirect_url` - URL para onde redirecionar
- `success_redirect_type` - Tipo: "automatic" ou "button"
- `success_bt_redirect` - Texto do botão (quando tipo = button)

### Verificar se já foi aplicada

Execute no seu banco de dados:
```sql
SHOW COLUMNS FROM form_customizations LIKE 'success_redirect%';
```

Se retornar 3 linhas, a migração já foi aplicada.
