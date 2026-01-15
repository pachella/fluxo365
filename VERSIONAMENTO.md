# Sistema de Versionamento

## Regras de Versão

**Formato**: `X.Y` (duas casas decimais)

### Como incrementar

1. **Feature nova ou fix importante**: Incrementa `.1`
   - Exemplo: `11.7` → `11.8`

2. **Ao chegar em `.9`**: Pula para próximo major
   - Exemplo: `11.9` → `12.0`

### Arquivos importantes

- **`/core/version.php`**: Define `APP_VERSION` (versão atual)
- **`/core/cache_helper.php`**: Usa `APP_VERSION` para cache busting

## Como atualizar a versão

### 1. Editar `/core/version.php`

```php
define('APP_VERSION', '11.8'); // Nova versão
```

### 2. Commitar com a mesma versão

```bash
git commit -m "Feature: Nova funcionalidade v11.8"
```

### 3. Assets são atualizados automaticamente

Todos os CSS/JS usam `?v=11.8` automaticamente via `assetUrl()`.

## Histórico de Versões

| Versão | Data | Descrição |
|--------|------|-----------|
| 11.7   | 2026-01-09 | Sistema de pontuação + Melhorias módulo Leads |

## Exemplos de Mensagens de Commit

✅ Correto:
- `Feature: Campo VSL customizável v11.8`
- `Fix: Correção de SQL no módulo Leads v11.8`

❌ Errado:
- `Feature: Nova funcionalidade v11.7.0` (3 casas)
- `Fix: Correção` (sem versão)
- `Feature: Novo recurso v11.6` (versão anterior)

## Notas

- A versão é **global** para todo o sistema
- Não usar versionamento por arquivo individual
- Cache de assets reseta automaticamente ao mudar versão
- Commits devem sempre ter a versão atual
