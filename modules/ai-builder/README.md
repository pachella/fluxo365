# 🤖 AI Builder - Criação de Formulários com IA

Módulo que permite criar formulários através de conversas com Inteligência Artificial.

## 📋 Funcionalidades

- **Chat interativo** com IA (Groq - Llama 3.1)
- **Criação automática** de formulários baseada em conversas
- **Suporte a todos os tipos de campos** do sistema
- **Validação inteligente** de estrutura
- **Integração perfeita** com o sistema existente
- **100% GRATUITO** para uso pessoal/desenvolvimento

## 🔧 Configuração

### 1. Obter API Key do Groq (2 minutos!)

1. Acesse: https://console.groq.com/
2. Faça login (pode usar Google - super rápido!)
3. Vá em "API Keys"
4. Clique em "Create API Key"
5. Copie a key gerada

### 2. Configurar API Key

Edite o arquivo: `/modules/ai-builder/api/chat.php`

Encontre a linha 178:

```php
$apiKey = getenv('GROQ_API_KEY') ?: 'SUA_API_KEY_AQUI';
```

**Opção 1 - Variável de Ambiente (Recomendado):**
```bash
export GROQ_API_KEY="sua_key_aqui"
```

**Opção 2 - Diretamente no código:**
```php
$apiKey = 'gsk_xxxxxxxxxxxxx';  // ← Cole sua key aqui
```

### 3. Modelos Disponíveis

No arquivo `chat.php`, você pode alterar o modelo (linha 196):

```php
'model' => 'llama-3.3-70b-versatile',  // Recomendado! (Gratuito)
// ou
'model' => 'llama-3.1-8b-instant',     // Mais rápido (Gratuito)
// ou
'model' => 'mixtral-8x7b-32768',       // Alternativa (Gratuito)
```

## 💰 Custos

✨ **100% GRATUITO!** ✨

- Sem cartão de crédito necessário
- Sem limites restritivos para desenvolvimento
- Extremamente rápido (mais rápido que GPT-4!)
- Perfeito para produção de pequena/média escala

## 🎯 Como Usar

1. Acesse o menu lateral: **"Criar com IA"**
2. Descreva o formulário que precisa
3. A IA vai fazer perguntas para entender melhor
4. Confirme quando estiver pronto
5. O formulário será criado automaticamente!

## 🔄 Fluxo de Criação

```
Usuário: "Quero um formulário para captar leads de petshop"
   ↓
IA: "Que informações você quer coletar?"
   ↓
Usuário: "Nome, email, telefone, tipo de pet"
   ↓
IA: [Sugere estrutura completa]
   ↓
Usuário: "Pode criar!"
   ↓
✅ Formulário criado!
```

## 📁 Estrutura de Arquivos

```
modules/ai-builder/
├── config.php           # Configuração do módulo (menu)
├── index.php            # Interface do chat
├── README.md            # Esta documentação
├── api/
│   ├── chat.php         # Comunicação com Qwen API
│   └── create_form.php  # Criação do formulário no banco
└── assets/
    └── chat.js          # Lógica do chat (frontend)
```

## 🎨 Tipos de Campos Suportados

A IA pode criar formulários com todos os tipos:

- **Texto:** text, textarea, name, url
- **Dados pessoais:** email, phone, cpf, cnpj, rg
- **Datas e números:** date, number, money, slider, range
- **Escolha:** radio, select
- **Outros:** address, file, rating, terms, message, welcome

## 🔒 Segurança

- ✅ Validação de autenticação
- ✅ Sanitização de inputs
- ✅ Validação de tipos de campos
- ✅ Prevenção de XSS
- ✅ Transações de banco de dados

## 🐛 Troubleshooting

### Erro: "Resposta inválida da API"
- Verifique se a API key está correta
- Verifique sua conexão com internet
- Verifique se a key foi gerada corretamente em console.groq.com

### Formulário não foi criado
- Verifique os logs do navegador (F12)
- Verifique o error_log do PHP
- Confirme que a estrutura JSON está válida

### IA não entende o pedido
- Seja mais específico
- Mencione os tipos de campos que precisa
- Peça sugestões: "Me dê sugestões de campos"

## 📝 Changelog

### v11.0.4 (2025-01-07)
- 🔄 Atualização para Llama 3.3 70B (modelo mais recente)
- ⚡ Melhor performance e precisão

### v11.0.3 (2025-01-07)
- 🎨 Chat integrado ao tema padrão do sistema
- 🎨 Cores adaptadas para verde da marca (#4EA44B)
- 📁 Configuração via arquivo local (config.local.php)

### v11.0.2 (2025-01-07)
- ⚙️ Sistema de variáveis de ambiente
- 📦 Cache busting para CSS/JS

### v11.0.1 (2025-01-07)
- 🔄 Migrado para Groq API (gratuito e mais rápido!)
- 🚀 Modelo Llama 3.1 70B (excelente para estruturação)
- ⚡ Performance melhorada

### v11.0.0 (2025-01-07)
- 🎉 Lançamento inicial
- ✨ Chat interativo com IA
- 📋 Criação automática de formulários
- 🎨 Interface moderna e responsiva

## 💡 Próximas Features

- [ ] Suporte a templates pré-definidos
- [ ] Edição de formulários via IA
- [ ] Histórico de conversas
- [ ] Sugestões inteligentes baseadas no nicho
- [ ] Multi-idioma

## 👨‍💻 Desenvolvido por

Claude AI + Pachella
