/**
 * Gerenciador de itens dinâmicos para formulários
 * Supersites - Sistema de gestão
 * 
 * Função genérica para adicionar/remover itens dinâmicos (links, cards, depoimentos, etc)
 */

function setupDynamicItems(config) {
    const { 
        containerSelector,      // Seletor do container principal (ex: '#linksContainer')
        itemSelector,           // Seletor dos itens (ex: '.link-item')
        addButtonSelector,      // Seletor do botão adicionar (ex: '#btnAddLink')
        removeButtonSelector,   // Seletor dos botões remover (ex: '.remove-link-btn')
        maxItems,              // Número máximo de itens permitidos
        itemName,              // Nome do item no singular (ex: 'link', 'card')
        createItemHTML,        // Função que retorna o HTML do novo item
        afterCreate,           // Callback opcional após criar novo item
        onExistingItems        // Callback opcional para itens existentes
    } = config;
    
    const container = document.querySelector(containerSelector);
    const addButton = document.querySelector(addButtonSelector);
    
    if (!container || !addButton) return;
    
    // Função para adicionar novo item
    addButton.addEventListener('click', function() {
        const currentCount = container.querySelectorAll(itemSelector).length;
        
        if (currentCount >= maxItems) {
            alert(`Você pode adicionar no máximo ${maxItems} ${itemName}${maxItems > 1 ? 's' : ''}.`);
            return;
        }
        
        const newIndex = currentCount;
        const itemDiv = document.createElement('div');
        itemDiv.innerHTML = createItemHTML(newIndex);
        
        // Pegar o primeiro elemento filho (o div principal)
        const newItem = itemDiv.firstElementChild;
        container.appendChild(newItem);
        
        // Atualizar botão
        const newCount = currentCount + 1;
        const itemNameCapitalized = itemName.charAt(0).toUpperCase() + itemName.slice(1);
        addButton.innerHTML = `+ Adicionar ${itemNameCapitalized} (${newCount}/${maxItems})`;
        
        if (newCount >= maxItems) {
            addButton.style.display = 'none';
        }
        
        // Listener no botão remover do novo item
        const removeBtn = newItem.querySelector(removeButtonSelector);
        if (removeBtn) {
            removeBtn.addEventListener('click', () => removeItem(newItem));
        }
        
        // Callbacks específicos após criar
        if (afterCreate && typeof afterCreate === 'function') {
            afterCreate(newItem, newIndex);
        }
    });
    
    // Função para remover item
    function removeItem(item) {
        item.remove();
        const newCount = container.querySelectorAll(itemSelector).length;
        const itemNameCapitalized = itemName.charAt(0).toUpperCase() + itemName.slice(1);
        addButton.innerHTML = `+ Adicionar ${itemNameCapitalized} (${newCount}/${maxItems})`;
        addButton.style.display = 'block';
    }
    
    // Listeners nos botões remover existentes
    container.querySelectorAll(removeButtonSelector).forEach(btn => {
        btn.addEventListener('click', function() {
            const item = this.closest(itemSelector);
            if (item) removeItem(item);
        });
    });
    
    // Callbacks específicos para itens existentes
    if (onExistingItems && typeof onExistingItems === 'function') {
        onExistingItems(container);
    }
}

// Exportar para uso global
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { setupDynamicItems };
}