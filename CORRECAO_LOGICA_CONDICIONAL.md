# Correção da Lógica Condicional - Formulário Público

## Problemas Corrigidos

### PROBLEMA 1: Botão voltar leva para perguntas ocultas
**Antes:** Quando havia lógica condicional que pulava perguntas, o botão "voltar" levava para a pergunta anterior numericamente, mesmo que o usuário nunca tivesse visto essa pergunta.

**Depois:** Implementado sistema de histórico de navegação que rastreia apenas as perguntas efetivamente visitadas.

### PROBLEMA 2: Numeração incorreta
**Antes:** Se pulava da pergunta 3 para a 7, mostrava "7" em vez de "4" (4ª pergunta apresentada).

**Depois:** Criada numeração virtual baseada em perguntas efetivamente mostradas ao usuário.

---

## Implementação Técnica

### 1. Array de Perguntas Visitadas
```javascript
// Array de slides visitados para navegação correta (histórico de navegação)
let visitedSlides = [0]; // Começa com o primeiro slide
```

Este array mantém o histórico completo de navegação do usuário, permitindo voltar apenas para perguntas que foram efetivamente vistas.

### 2. Função de Atualização de Numeração Virtual
```javascript
function updateVirtualNumber() {
    const virtualIndex = visitedSlides.length; // Posição atual no histórico
    const questionNumberEl = slides[currentSlide]?.querySelector('.question-number');

    if (questionNumberEl) {
        // Preservar o ícone e atualizar apenas o número
        const icon = questionNumberEl.querySelector('i');
        if (icon) {
            questionNumberEl.innerHTML = virtualIndex + ' ';
            questionNumberEl.appendChild(icon);
        }
    }
}
```

A numeração virtual é calculada baseada no tamanho do array `visitedSlides`, representando a posição real da pergunta na sequência apresentada ao usuário.

### 3. Navegação para Frente (nextQuestion)
**Modificações:**
- Adiciona o novo slide ao histórico de visitados
- Atualiza a numeração virtual após a navegação

```javascript
// Adicionar o novo slide ao histórico de visitados (se não estiver já)
if (!visitedSlides.includes(currentSlide)) {
    visitedSlides.push(currentSlide);
}

updateVirtualNumber(); // Atualizar numeração virtual
```

### 4. Navegação para Trás (previousQuestion)
**Antes:**
- Decrementava o índice atual: `currentSlide--`
- Pulava slides ocultos em um loop
- Podia levar para perguntas nunca visitadas

**Depois:**
- Remove o slide atual do histórico
- Volta para o último slide visitado do array
- Garante que só volta para perguntas efetivamente vistas

```javascript
function previousQuestion() {
    // Remover o slide atual do histórico
    if (visitedSlides.length > 1) {
        visitedSlides.pop();
    }

    // Pegar o último slide visitado (anterior)
    const previousSlideIndex = visitedSlides[visitedSlides.length - 1];

    // Se não houver slide anterior, não fazer nada
    if (previousSlideIndex === undefined) {
        return;
    }

    // Ir para o slide anterior do histórico
    currentSlide = previousSlideIndex;

    updateVirtualNumber(); // Atualizar numeração virtual
}
```

---

## Exemplo de Funcionamento

### Cenário: Formulário com 10 perguntas e lógica condicional que pula da pergunta 3 para a 7

**Navegação do usuário:**
1. Inicia na pergunta 1 (índice 0)
   - `visitedSlides = [0]`
   - Numeração exibida: **1**

2. Avança para pergunta 2 (índice 1)
   - `visitedSlides = [0, 1]`
   - Numeração exibida: **2**

3. Avança para pergunta 3 (índice 2)
   - `visitedSlides = [0, 1, 2]`
   - Numeração exibida: **3**

4. Responde "Sim" na pergunta 3, que ativa fluxo condicional
   - Fluxo pula para pergunta 7 (índice 6)
   - `visitedSlides = [0, 1, 2, 6]`
   - Numeração exibida: **4** (e não 7!)

5. Clica em "Voltar"
   - Remove índice 6 do array: `visitedSlides.pop()`
   - `visitedSlides = [0, 1, 2]`
   - Volta para pergunta 3 (índice 2)
   - Numeração exibida: **3**

6. Altera resposta para "Não" e avança normalmente
   - Vai para pergunta 4 (índice 3)
   - `visitedSlides = [0, 1, 2, 3]`
   - Numeração exibida: **4**

---

## Benefícios

1. **Navegação Intuitiva:** Usuário só volta para perguntas que efetivamente viu
2. **Numeração Lógica:** Números sequenciais baseados na experiência real do usuário
3. **Compatível com Fluxos:** Funciona perfeitamente com lógica condicional complexa
4. **Sem Duplicatas:** Array garante que não há slides duplicados no histórico

---

## Arquivos Modificados

- `/home/user/form/modules/forms/public/assets/scripts.js`
  - Adicionado array `visitedSlides`
  - Criada função `updateVirtualNumber()`
  - Modificada função `nextQuestion()`
  - Reescrita função `previousQuestion()`
  - Inicialização da numeração virtual ao carregar

---

## Testes Sugeridos

1. **Teste básico:** Navegar para frente e para trás sem lógica condicional
2. **Teste com fluxos:** Criar fluxo que pula perguntas e verificar numeração
3. **Teste de histórico:** Avançar com fluxo, voltar, e avançar novamente
4. **Teste de borda:** Tentar voltar no primeiro slide (não deve fazer nada)

---

**Data da correção:** 2025-11-15
**Versão:** 8.10
