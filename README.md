# Sistema Base - Fluxo365

Sistema base limpo e organizado, pronto para desenvolvimento modular.

## 📋 Sobre

Este é um sistema base preparado para crescimento modular. A estrutura foi limpa e organizada para servir como fundação para novos módulos e funcionalidades, como CRM, SDR, e outros sistemas que serão desenvolvidos.

## 🎯 Funcionalidades Base

- ✅ Sistema de autenticação completo (login, registro, recuperação de senha)
- ✅ Gerenciamento de usuários com diferentes roles (admin/client)
- ✅ Dashboard personalizado por tipo de usuário
- ✅ Sistema de permissões robusto
- ✅ Layout responsivo com dark mode
- ✅ Estrutura modular escalável

## 🏗️ Estrutura do Projeto

```
fluxo365/
├── auth/                   # Sistema de autenticação
│   ├── login.php
│   ├── register.php
│   ├── forgot.php
│   ├── reset.php
│   └── logout.php
│
├── core/                   # Núcleo do sistema
│   ├── db.php             # Conexão com banco de dados
│   ├── config.php         # Configurações globais
│   ├── auth_check.php     # Verificação de autenticação
│   ├── PermissionManager.php  # Gerenciamento de permissões
│   ├── EmailService.php   # Serviço de envio de emails
│   ├── ImageProcessor.php # Processamento de imagens
│   ├── cache_helper.php   # Helper de cache
│   ├── version.php        # Versionamento
│   └── phpmailer/         # Biblioteca PHPMailer
│
├── modules/               # Módulos do sistema (estrutura modular)
│   ├── dashboard/        # Dashboard
│   │   ├── config.php
│   │   ├── home.php     # Dashboard admin
│   │   └── client.php   # Dashboard cliente
│   │
│   ├── users/           # Gerenciamento de usuários
│   │   ├── config.php
│   │   ├── list.php
│   │   ├── table.php
│   │   ├── edit.php
│   │   ├── save.php
│   │   ├── delete.php
│   │   └── search.php
│   │
│   └── dashboard.php    # Roteador principal
│
├── scripts/             # Scripts globais (CSS/JS)
│   ├── css/
│   │   └── global.css
│   └── js/
│       ├── masks.js
│       └── global/
│
├── uploads/             # Arquivos enviados
│   └── system/
│
├── views/               # Templates de visualização
│   ├── layout/
│   │   ├── header.php
│   │   ├── sidebar.php
│   │   └── footer.php
│   └── error_403.php
│
├── assets/              # Assets globais
│   └── js/
│
├── index.php           # Arquivo de entrada
├── .htaccess           # Configuração Apache
└── README.md           # Este arquivo
```

## 🚀 Como Adicionar Novos Módulos

A estrutura está preparada para crescer de forma modular. Para adicionar um novo módulo (ex: CRM):

1. **Crie a pasta do módulo** em `/modules/crm/`

2. **Crie o arquivo de configuração** `/modules/crm/config.php`:
```php
<?php
return [
    'name' => 'crm',                    // Nome interno do módulo
    'label' => 'CRM',                   // Label exibido no menu
    'icon' => 'briefcase',              // Ícone Feather Icons
    'url' => '/crm',                    // URL base
    'order' => 30,                      // Ordem no menu
    'roles' => ['admin', 'client'],     // Roles que podem acessar
];
```

3. **Desenvolva as funcionalidades** dentro da pasta do módulo

4. **O sistema automaticamente**:
   - Adiciona o módulo ao menu lateral
   - Aplica as permissões configuradas
   - Roteia as URLs corretamente

## 💻 Tecnologias

- **Backend**: PHP 8.0+
- **Banco de Dados**: MySQL com PDO
- **Frontend**: JavaScript vanilla + CSS moderno
- **Servidor**: Apache com mod_rewrite

## 🔧 Instalação

### Instalação Automática (Recomendado)

1. **Clone o repositório ou faça upload dos arquivos**
   ```bash
   git clone https://github.com/seu-usuario/fluxo365.git
   cd fluxo365
   ```

2. **Configure o servidor web** para apontar para a raiz do projeto
   - Certifique-se de que o mod_rewrite está ativado no Apache
   - PHP 8.0+ deve estar instalado

3. **Acesse o instalador**
   - Abra o navegador e acesse: `http://seu-dominio.com/`
   - Você será redirecionado automaticamente para `/install.php`

4. **Siga o wizard de instalação**
   - **Passo 1:** Configure as credenciais do MySQL
   - **Passo 2:** Crie a conta de administrador
   - **Passo 3:** Instalação concluída!

5. **Faça login**
   - Use as credenciais criadas durante a instalação
   - Acesse: `http://seu-dominio.com/auth/login.php`

### Configuração Manual (Avançado)

Se preferir configurar manualmente:

1. Importe o SQL: `/install/schema.sql` no seu banco de dados
2. Configure as credenciais em `/core/db.php`
3. Crie um usuário admin manualmente no banco
4. Acesse o sistema pelo navegador

### Após a Instalação

Por segurança, recomendamos:
- ✅ Remover o arquivo `install.php`
- ✅ Remover o arquivo `fix-permissions.php` (se usado)
- ✅ Verificar as permissões da pasta `/uploads`
- ✅ Configurar backup automático do banco de dados

## 🐛 Resolução de Problemas

### Erro 403 Forbidden

Se você receber o erro **403 Forbidden** ao acessar o sistema, o problema geralmente está nas permissões dos arquivos.

**Solução Rápida:**

1. **Via navegador** (mais fácil):
   ```
   Acesse: http://seu-dominio.com/fix-permissions.php
   → Clique em "Corrigir Permissões Agora"
   → Aguarde a conclusão
   → Teste o acesso novamente
   ```

2. **Via terminal SSH** (se tiver acesso):
   ```bash
   # Navegar até o diretório
   cd /caminho/para/fluxo365

   # Corrigir permissões de diretórios
   find . -type d -exec chmod 755 {} \;

   # Corrigir permissões de arquivos
   find . -type f -exec chmod 644 {} \;

   # Tornar uploads gravável
   chmod -R 775 uploads/
   ```

**Permissões Recomendadas:**
- Diretórios: `755` (rwxr-xr-x)
- Arquivos: `644` (rw-r--r--)
- Pasta uploads: `775` (rwxrwxr-x)

### Erro de Conexão com Banco de Dados

Se aparecer erro de conexão durante a instalação:

1. Verifique se o MySQL está rodando
2. Confirme usuário e senha do MySQL
3. Verifique se o usuário tem permissão para criar bancos
4. Tente: `host: 127.0.0.1` ao invés de `localhost`

### Problema com HTTPS

Se o sistema ficar em loop ou não carregar:

1. Edite o arquivo `.htaccess`
2. Comente as linhas de HTTPS forçado (linhas 37-38):
   ```apache
   # RewriteCond %{HTTPS} off
   # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

### Outros Problemas

- **Página em branco**: Verifique os logs do PHP (`/var/log/apache2/error.log` ou `/var/log/httpd/error_log`)
- **CSS não carrega**: Verifique se o `mod_rewrite` está ativado no Apache
- **Upload não funciona**: Verifique permissões da pasta `/uploads/`
- **Erro 500**: Geralmente é erro de sintaxe no PHP ou permissões incorretas

## 📦 Banco de Dados

O sistema possui as seguintes tabelas base:

- `users` - Usuários do sistema

Para criar novas tabelas relacionadas a módulos específicos, mantenha-as organizadas e documentadas.

## 🎨 Personalização

### Dark Mode
O sistema possui suporte nativo a dark mode, que alterna automaticamente baseado nas preferências do usuário.

### Layout
Os arquivos de layout estão em `/views/layout/`:
- `header.php` - Cabeçalho e navegação mobile
- `sidebar.php` - Menu lateral (carrega módulos automaticamente)
- `footer.php` - Rodapé e scripts

## 🔐 Sistema de Permissões

O sistema utiliza o `PermissionManager` para controlar acessos:

```php
// Verificar se é admin
$permissionManager->isAdmin()

// Verificar se pode acessar um módulo
$permissionManager->canAccessModule('users')

// Verificar se pode editar um registro
$permissionManager->canEditRecord($recordUserId)

// Obter filtro SQL baseado no role
$permissionManager->getSQLFilter()
```

## 📝 Próximos Passos

Este sistema base está pronto para receber:
- Módulo CRM
- Módulo SDR
- Integrações
- Relatórios
- E qualquer outro módulo necessário

A estrutura modular permite desenvolvimento independente e organizado de cada funcionalidade.

## 👨‍💻 Desenvolvimento

Para desenvolver novos módulos:
1. Siga a estrutura modular estabelecida
2. Use o PermissionManager para controle de acesso
3. Mantenha o código organizado dentro da pasta do módulo
4. Documente as funcionalidades adicionadas

---

**Versão:** 1.0.0 - Sistema Base Limpo
**Última Atualização:** Janeiro 2025
