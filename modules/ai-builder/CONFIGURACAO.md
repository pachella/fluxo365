# 🔐 Configuração da API Key do Groq

Para que o AI Builder funcione, você precisa configurar a API key do Groq como **variável de ambiente**.

## 📝 Passo a Passo

### 1️⃣ Obter a API Key

1. Acesse: https://console.groq.com/
2. Faça login
3. Vá em "API Keys"
4. Crie uma nova key
5. Copie a key (começa com `gsk_...`)

### 2️⃣ Configurar no Servidor

⚠️ **IMPORTANTE:** Substitua `SUA_KEY_AQUI` pela sua key real do Groq!

Escolha UMA das opções abaixo:

#### **Opção A: Via Terminal SSH (Temporário)**
```bash
export GROQ_API_KEY="SUA_KEY_AQUI"
```

#### **Opção B: Arquivo .bashrc (Permanente)**
```bash
echo 'export GROQ_API_KEY="SUA_KEY_AQUI"' >> ~/.bashrc
source ~/.bashrc
```

#### **Opção C: Painel de Hospedagem**
Se usar cPanel, Plesk, ou similar:
1. Vá em "Variáveis de Ambiente" ou "Environment Variables"
2. Adicione:
   - Nome: `GROQ_API_KEY`
   - Valor: `SUA_KEY_AQUI` (cole sua key real)

#### **Opção D: Arquivo .env (Se usar)**
Se o seu servidor suporta `.env`:
```bash
cd /home/user/form_system/modules/ai-builder/
cp .env.example .env
# Edite o .env e cole a key
```

### 3️⃣ Testar

Após configurar, acesse:
```
https://formtalk.app/modules/ai-builder/
```

E teste criando um formulário!

## ❓ Problemas?

**Erro: "API key não configurada"**
- Verifique se a variável de ambiente está ativa: `echo $GROQ_API_KEY`
- Reinicie o servidor web: `sudo service apache2 restart` ou `sudo service nginx restart`

**Erro 401 Unauthorized**
- Verifique se a key está correta
- Verifique se não há espaços antes/depois da key

## 🔒 Segurança

✅ **Nunca** commite a API key diretamente no código
✅ **Sempre** use variáveis de ambiente
✅ A key está protegida no `.gitignore`
