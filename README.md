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

## 🔧 Configuração

1. Configure o banco de dados em `/core/db.php`
2. Ajuste as configurações globais em `/core/config.php`
3. Configure o servidor web para apontar para a raiz do projeto
4. Certifique-se de que o mod_rewrite está ativado no Apache

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
