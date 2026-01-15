# 📦 Instalação Manual - Fluxo365

Instruções simples para instalar o sistema.

## 1️⃣ Criar o Banco de Dados

1. Acesse o **phpMyAdmin**
2. Crie um novo banco de dados (ex: `fluxo365`)
3. Selecione o banco criado
4. Vá em **"Importar"** ou **"SQL"**
5. Cole o conteúdo do arquivo `database.sql`
6. Execute

✅ **Pronto!** A tabela `users` foi criada com um usuário admin padrão.

## 2️⃣ Configurar a Conexão

1. Abra o arquivo: `/core/db.php`
2. Edite as seguintes linhas:

```php
$host = "localhost";              // Seu host MySQL
$db   = "fluxo365";               // Nome do banco que você criou
$user = "root";                   // Seu usuário MySQL
$pass = "sua_senha";              // Sua senha MySQL
```

3. Salve o arquivo

## 3️⃣ Acessar o Sistema

1. Abra o navegador
2. Acesse: `http://seu-dominio.com/`
3. Você será redirecionado para o login
4. Use as credenciais padrão:

```
Email: admin@fluxo365.com
Senha: admin123
```

## ⚠️ Importante

**Altere a senha do admin após o primeiro login!**

Vá em: Dashboard → Usuários → Editar Admin → Nova Senha

## 🔧 Problemas?

### Erro de conexão com banco

- Verifique se o MySQL está rodando
- Confirme usuário e senha no `/core/db.php`
- Tente trocar `localhost` por `127.0.0.1`

### Erro 403 Forbidden

Execute via SSH:
```bash
chmod -R 755 .
chmod -R 775 uploads/
```

### Página em branco

- Verifique os logs do PHP
- Confirme que o PHP 8.0+ está instalado
- Verifique se o `mod_rewrite` está ativado

---

**Sistema pronto! 🚀**
