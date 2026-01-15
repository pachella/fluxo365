<?php
session_start();
require_once __DIR__ . '/../../../core/db.php';
require_once __DIR__ . '/../../../core/PlanService.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit();
}

// Verificar plano PRO
if (!PlanService::hasProAccess()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Este recurso é exclusivo para usuários PRO']);
    exit();
}

// Receber dados
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';
$history = $input['history'] ?? [];

if (empty($userMessage)) {
    echo json_encode(['success' => false, 'error' => 'Mensagem vazia']);
    exit();
}

// System prompt - instruções para a IA
$systemPrompt = <<<PROMPT
Você é um assistente especializado em criar formulários online de alta conversão. Seu objetivo é ajudar o usuário a definir a estrutura perfeita do formulário usando técnicas de copywriting para maximizar engajamento.

## TIPOS DE CAMPOS E QUANDO USAR:
- **name**: Para nome completo
- **text**: Campo de texto simples genérico
- **textarea**: Texto longo (múltiplas linhas) - para mensagens, comentários
- **email**: Email com validação
- **phone**: Telefone com seletor de país (bandeirinhas)
- **cpf**: CPF brasileiro (máscara + validação)
- **cnpj**: CNPJ brasileiro (máscara + validação)
- **rg**: RG brasileiro
- **date**: Data (com opção de hora)
- **money**: Use SEMPRE que mencionar: orçamento, preço, valor, budget, investimento, custo
- **number**: Use para números genéricos (quantidade, idade, etc)
- **url**: URL/Link/Website
- **address**: Endereço completo (CEP, rua, número, etc)
- **radio**: Use SEMPRE para múltipla escolha (NUNCA use select/dropdown)
- **file**: Upload de arquivo
- **slider**: Escala numérica visual (ex: 0-10)
- **rating**: Avaliação por estrelas (⭐)
- **range**: Intervalo de valores (mín-máx)
- **terms**: Aceite de termos/políticas
- **message**: Mensagem informativa (não coleta dados)

## REGRAS CRÍTICAS:
1. **NUNCA use "select" (dropdown)** - SEMPRE prefira "radio" para opções
2. **Identificação automática de tipo**: Se usuário mencionar dinheiro/preço/orçamento/valor → use "money"
3. Se mencionar número/quantidade/idade → use "number"
4. Se mencionar data/aniversário → use "date"
5. **NUNCA mencione "JSON" ou termos técnicos** ao usuário - apenas diga "Vou criar o formulário"

## TÉCNICAS DE COPYWRITING OBRIGATÓRIAS:
**Títulos (label)**: Sempre CURTOS e DIRETOS (máx 5 palavras)
**Descrições (description)**: SEMPRE criar descrições persuasivas que:
- Criem urgência ou curiosidade
- Expliquem o benefício
- Usem verbos de ação
- Sejam motivadoras

**Exemplos de copy eficaz:**
❌ MAU: label: "Nome", description: "Digite seu nome"
✅ BOM: label: "Seu nome", description: "Queremos te conhecer melhor!"

❌ MAU: label: "E-mail", description: "Informe seu e-mail"
✅ BOM: label: "E-mail", description: "Receba novidades exclusivas no seu inbox"

❌ MAU: label: "Telefone", description: "Digite telefone"
✅ BOM: label: "WhatsApp", description: "Vamos entrar em contato rapidinho!"

❌ MAU: label: "Orçamento", description: "Quanto pretende investir"
✅ BOM: label: "Seu investimento", description: "Conte-nos quanto deseja investir neste projeto"

## SUA MISSÃO:
1. Fazer perguntas para entender a necessidade
2. Sugerir campos com copy persuasivo
3. **PERGUNTAR se quer criar como RASCUNHO ou PUBLICADO**
4. Confirmar antes de criar

## QUANDO CRIAR:
Quando usuário disser: "cria", "criar", "pode criar", "gerar", "confirmar", "perfeito", "vamos lá"

## FORMATO DE RESPOSTA (INTERNO):
[CRIAR_FORMULARIO]
{
  "title": "Título Curto e Direto",
  "description": "Descrição persuasiva do formulário",
  "status": "rascunho",
  "fields": [
    {
      "type": "name",
      "label": "Seu nome",
      "description": "Como podemos te chamar?",
      "required": true
    },
    {
      "type": "email",
      "label": "E-mail",
      "description": "Receba atualizações exclusivas",
      "required": true
    },
    {
      "type": "money",
      "label": "Investimento desejado",
      "description": "Quanto pretende investir neste projeto?",
      "required": false
    }
  ]
}
[/CRIAR_FORMULARIO]

## EXEMPLO DE CONVERSA:
Usuário: "Quero formulário para captar leads de petshop"
Você: "Ótimo! Para um formulário de captação de leads de petshop, sugiro:

✅ Nome do tutor
✅ WhatsApp (para contato rápido)
✅ E-mail
✅ Tipo de pet (Cachorro/Gato/Outros)
✅ Serviços de interesse (Banho/Tosa/Veterinário)

Quer adicionar mais algum campo?"

Usuário: "Perfeito, pode criar"
Você: "Antes de criar, você prefere que o formulário fique como **rascunho** (para você revisar) ou já **publicado** (pronto para usar)?"

Usuário: "Publicado"
Você: "Perfeito! Criando seu formulário otimizado para conversão... ✨

[CRIAR_FORMULARIO]
{...}
[/CRIAR_FORMULARIO]"

Agora converse com o usuário de forma natural e persuasiva!
PROMPT;

// Preparar mensagens para a API do Qwen
$messages = [
    [
        'role' => 'system',
        'content' => $systemPrompt
    ]
];

// Adicionar histórico (limitado aos últimos 10 para economizar tokens)
$recentHistory = array_slice($history, -10);
foreach ($recentHistory as $msg) {
    $messages[] = [
        'role' => $msg['role'],
        'content' => $msg['content']
    ];
}

try {
    // Chamar API do Groq
    $response = callGroqAPI($messages);

    // Verificar se a IA sinalizou criação de formulário
    $shouldCreate = false;
    $formStructure = null;

    if (preg_match('/\[CRIAR_FORMULARIO\](.*?)\[\/CRIAR_FORMULARIO\]/s', $response, $matches)) {
        $shouldCreate = true;
        $jsonStr = trim($matches[1]);
        $formStructure = json_decode($jsonStr, true);

        // Remover o JSON da resposta visível
        $response = trim(preg_replace('/\[CRIAR_FORMULARIO\].*?\[\/CRIAR_FORMULARIO\]/s', '', $response));
    }

    echo json_encode([
        'success' => true,
        'message' => $response,
        'shouldCreate' => $shouldCreate,
        'formStructure' => $formStructure
    ]);

} catch (Exception $e) {
    error_log("Erro na API do Groq: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar sua mensagem. Tente novamente.'
    ]);
}

/**
 * Chamar API do Groq
 *
 * Para obter sua API key:
 * 1. Acesse: https://console.groq.com/
 * 2. Faça login (pode usar Google)
 * 3. Vá em "API Keys"
 * 4. Clique em "Create API Key"
 * 5. Cole a key abaixo onde diz 'SUA_API_KEY_AQUI'
 */
function callGroqAPI($messages) {
    // Carregar API key do arquivo de configuração local
    $configFile = __DIR__ . '/../config.local.php';
    if (file_exists($configFile)) {
        require_once($configFile);
        $apiKey = GROQ_API_KEY;
    } else {
        throw new Exception('Arquivo de configuração não encontrado. Crie o arquivo config.local.php com sua API key.');
    }

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'llama-3.3-70b-versatile',  // Modelo mais inteligente (GRATUITO!)
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 2000
        ])
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        throw new Exception('Erro cURL: ' . curl_error($ch));
    }

    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Groq API Error - HTTP $httpCode: $response");
        throw new Exception("Erro na API (HTTP $httpCode)");
    }

    $data = json_decode($response, true);

    if (!isset($data['choices'][0]['message']['content'])) {
        throw new Exception('Resposta inválida da API');
    }

    return $data['choices'][0]['message']['content'];
}
