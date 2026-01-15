// Função global para criar/editar formulário
async function showFormModal(formData = null) {
    const isEdit = formData !== null;

    // Verificar se as funções auxiliares existem
    if (typeof getThemeClasses !== 'function' || typeof createFormModal !== 'function') {
        console.error('Funções auxiliares não carregadas. Certifique-se de incluir helpers.js e modals.js');
        return;
    }

    const classes = getThemeClasses();

    // Se é edição, mostrar formulário direto (sem abas)
    if (isEdit) {
        showCreateFormTab(formData, classes);
        return;
    }

    // Se é criação, mostrar modal com abas (Criar Novo | Templates)
    const modalHTML = `
        <div class="text-left">
            <!-- Botões de alternância mais clean -->
            <div class="grid grid-cols-2 gap-3 mb-6">
                <button onclick="switchTab('create')" id="tab-create"
                        style="background-color: #4EA44B;" class="tab-button px-4 py-2.5 dark:bg-zinc-600 text-white rounded-md font-medium text-sm transition-colors">
                    <i class="fas fa-plus-circle mr-2"></i>Criar Novo
                </button>
                <button onclick="switchTab('templates')" id="tab-templates"
                        class="tab-button px-4 py-2.5 bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-zinc-300 rounded-md font-medium text-sm transition-colors">
                    <i class="fas fa-th-large mr-2"></i>Templates
                </button>
            </div>

            <!-- Conteúdo da aba Criar Novo -->
            <div id="tab-content-create">
                <form id="modalFormForm" class="text-left">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-2 ${classes.text}">Título do Formulário *</label>
                            <input type="text" name="title" required
                                   placeholder="Ex: Pesquisa de Satisfação"
                                   class="w-full rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 ${classes.input}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 ${classes.text}">Descrição</label>
                            <textarea name="description" rows="3"
                                      placeholder="Descreva brevemente o objetivo deste formulário"
                                      class="w-full rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 ${classes.input}"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 ${classes.text}">Modo de Exibição</label>
                            <select name="display_mode" class="w-full rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 ${classes.input}">
                                <option value="one-by-one">Uma pergunta por vez (estilo TypeForm)</option>
                                <option value="all-at-once">Todas as perguntas (tradicional)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 ${classes.text}">Status</label>
                            <select name="status" class="w-full rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 ${classes.input}">
                                <option value="rascunho">Rascunho</option>
                                <option value="ativo">Ativo</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4" id="icon-color-fields" style="display: none;">
                            <div>
                                <label class="block text-sm font-medium mb-2 ${classes.text}">Ícone</label>
                                <select name="icon" class="w-full rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 ${classes.input}">
                                    <option value="file-alt"><i class="fas fa-file-alt"></i> Documento</option>
                                    <option value="folder"><i class="fas fa-folder"></i> Pasta</option>
                                    <option value="briefcase"><i class="fas fa-briefcase"></i> Profissional</option>
                                    <option value="building"><i class="fas fa-building"></i> Empresa</option>
                                    <option value="graduation-cap"><i class="fas fa-graduation-cap"></i> Educação</option>
                                    <option value="heart"><i class="fas fa-heart"></i> Saúde</option>
                                    <option value="star"><i class="fas fa-star"></i> Avaliação</option>
                                    <option value="bookmark"><i class="fas fa-bookmark"></i> Bookmark</option>
                                    <option value="tag"><i class="fas fa-tag"></i> Tag</option>
                                    <option value="inbox"><i class="fas fa-inbox"></i> Inbox</option>
                                    <option value="archive"><i class="fas fa-archive"></i> Arquivo</option>
                                    <option value="box"><i class="fas fa-box"></i> Caixa</option>
                                    <option value="shopping-cart"><i class="fas fa-shopping-cart"></i> Loja</option>
                                    <option value="users"><i class="fas fa-users"></i> Usuários</option>
                                    <option value="user-tie"><i class="fas fa-user-tie"></i> Cliente</option>
                                    <option value="chart-line"><i class="fas fa-chart-line"></i> Gráfico</option>
                                    <option value="rocket"><i class="fas fa-rocket"></i> Lançamento</option>
                                    <option value="lightbulb"><i class="fas fa-lightbulb"></i> Ideia</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2 ${classes.text}">Cor</label>
                                <select name="color" class="w-full rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 ${classes.input}">
                                    <option value="#4EA44B">Verde (Padrão)</option>
                                    <option value="#4F46E5">Azul</option>
                                    <option value="#EC4899">Rosa</option>
                                    <option value="#F59E0B">Laranja</option>
                                    <option value="#8B5CF6">Roxo</option>
                                    <option value="#EF4444">Vermelho</option>
                                    <option value="#06B6D4">Ciano</option>
                                    <option value="#10B981">Verde Esmeralda</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Conteúdo da aba Templates -->
            <div id="tab-content-templates" class="hidden">
                <div id="templates-loading" class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                    <p class="text-sm text-gray-500 dark:text-zinc-400 mt-2">Carregando templates...</p>
                </div>
                <div id="templates-container" class="hidden"></div>
            </div>
        </div>

        <style>
            .tab-button {
                transition: all 0.3s ease;
            }
            .template-card {
                transition: all 0.3s ease;
                cursor: pointer;
            }
            .template-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            }
            .dark .template-card:hover {
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            }
            .template-icon-preview {
                width: 60px;
                height: 60px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 12px;
                font-size: 28px;
            }
        </style>
    `;

    const footerLeft = `
        <button type="button" id="btn-create-form" onclick="saveFormFromModal(false)"
                class="inline-flex items-center px-5 py-2.5 text-white text-sm font-medium rounded-md transition-colors"
                style="background-color: #4EA44B;"
                onmouseover="this.style.backgroundColor='#3d8b40'"
                onmouseout="this.style.backgroundColor='#4EA44B'">
            Criar Formulário
        </button>
    `;

    const footerRight = `
        <button type="button" onclick="Swal.close()"
                class="text-sm ${classes.textMuted} hover:${classes.text} transition-colors">
            Cancelar
        </button>
    `;

    Swal.fire({
        html: createFormModal({
            title: 'Novo Formulário',
            content: modalHTML,
            footer: {
                left: footerLeft,
                right: footerRight
            }
        }),
        width: window.innerWidth < 640 ? '95%' : '700px',
        showConfirmButton: false,
        showCancelButton: false,
        didOpen: () => {
            // Carregar templates ao abrir o modal
            loadTemplates();

            // Mostrar campos de ícone e cor apenas para admin
            if (window.userRole === 'admin') {
                const iconColorFields = document.getElementById('icon-color-fields');
                if (iconColorFields) {
                    iconColorFields.style.display = 'grid';
                }
            }
        }
    });
}

// Função para mostrar formulário de edição (sem abas)
function showCreateFormTab(formData, classes) {
    const formHTML = `
        <form id="modalFormForm" class="text-left">
            <input type="hidden" name="id" value="${formData.id}">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2 ${classes.text}">Título do Formulário *</label>
                    <input type="text" name="title" required
                           value="${formData.title}"
                           placeholder="Ex: Pesquisa de Satisfação"
                           class="w-full rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 ${classes.input}">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2 ${classes.text}">Descrição</label>
                    <textarea name="description" rows="3"
                              placeholder="Descreva brevemente o objetivo deste formulário"
                              class="w-full rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 ${classes.input}">${formData.description || ''}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2 ${classes.text}">Modo de Exibição</label>
                    <select name="display_mode" class="w-full rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 ${classes.input}">
                        <option value="one-by-one" ${formData.display_mode === 'one-by-one' ? 'selected' : ''}>
                            Uma pergunta por vez (estilo TypeForm)
                        </option>
                        <option value="all-at-once" ${formData.display_mode === 'all-at-once' ? 'selected' : ''}>
                            Todas as perguntas (tradicional)
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2 ${classes.text}">Status</label>
                    <select name="status" class="w-full rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 ${classes.input}">
                        <option value="rascunho" ${formData.status === 'rascunho' ? 'selected' : ''}>Rascunho</option>
                        <option value="ativo" ${formData.status === 'ativo' ? 'selected' : ''}>Ativo</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4" id="icon-color-fields-edit" style="display: none;">
                    <div>
                        <label class="block text-sm font-medium mb-2 ${classes.text}">Ícone</label>
                        <select name="icon" class="w-full rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 ${classes.input}">
                            <option value="file-alt" ${formData.icon === 'file-alt' ? 'selected' : ''}><i class="fas fa-file-alt"></i> Documento</option>
                            <option value="folder" ${formData.icon === 'folder' ? 'selected' : ''}><i class="fas fa-folder"></i> Pasta</option>
                            <option value="briefcase" ${formData.icon === 'briefcase' ? 'selected' : ''}><i class="fas fa-briefcase"></i> Profissional</option>
                            <option value="building" ${formData.icon === 'building' ? 'selected' : ''}><i class="fas fa-building"></i> Empresa</option>
                            <option value="graduation-cap" ${formData.icon === 'graduation-cap' ? 'selected' : ''}><i class="fas fa-graduation-cap"></i> Educação</option>
                            <option value="heart" ${formData.icon === 'heart' ? 'selected' : ''}><i class="fas fa-heart"></i> Saúde</option>
                            <option value="star" ${formData.icon === 'star' ? 'selected' : ''}><i class="fas fa-star"></i> Avaliação</option>
                            <option value="bookmark" ${formData.icon === 'bookmark' ? 'selected' : ''}><i class="fas fa-bookmark"></i> Bookmark</option>
                            <option value="tag" ${formData.icon === 'tag' ? 'selected' : ''}><i class="fas fa-tag"></i> Tag</option>
                            <option value="inbox" ${formData.icon === 'inbox' ? 'selected' : ''}><i class="fas fa-inbox"></i> Inbox</option>
                            <option value="archive" ${formData.icon === 'archive' ? 'selected' : ''}><i class="fas fa-archive"></i> Arquivo</option>
                            <option value="box" ${formData.icon === 'box' ? 'selected' : ''}><i class="fas fa-box"></i> Caixa</option>
                            <option value="shopping-cart" ${formData.icon === 'shopping-cart' ? 'selected' : ''}><i class="fas fa-shopping-cart"></i> Loja</option>
                            <option value="users" ${formData.icon === 'users' ? 'selected' : ''}><i class="fas fa-users"></i> Usuários</option>
                            <option value="user-tie" ${formData.icon === 'user-tie' ? 'selected' : ''}><i class="fas fa-user-tie"></i> Cliente</option>
                            <option value="chart-line" ${formData.icon === 'chart-line' ? 'selected' : ''}><i class="fas fa-chart-line"></i> Gráfico</option>
                            <option value="rocket" ${formData.icon === 'rocket' ? 'selected' : ''}><i class="fas fa-rocket"></i> Lançamento</option>
                            <option value="lightbulb" ${formData.icon === 'lightbulb' ? 'selected' : ''}><i class="fas fa-lightbulb"></i> Ideia</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2 ${classes.text}">Cor</label>
                        <select name="color" class="w-full rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 ${classes.input}">
                            <option value="#4EA44B" ${formData.color === '#4EA44B' ? 'selected' : ''}>Verde (Padrão)</option>
                            <option value="#4F46E5" ${formData.color === '#4F46E5' ? 'selected' : ''}>Azul</option>
                            <option value="#EC4899" ${formData.color === '#EC4899' ? 'selected' : ''}>Rosa</option>
                            <option value="#F59E0B" ${formData.color === '#F59E0B' ? 'selected' : ''}>Laranja</option>
                            <option value="#8B5CF6" ${formData.color === '#8B5CF6' ? 'selected' : ''}>Roxo</option>
                            <option value="#EF4444" ${formData.color === '#EF4444' ? 'selected' : ''}>Vermelho</option>
                            <option value="#06B6D4" ${formData.color === '#06B6D4' ? 'selected' : ''}>Ciano</option>
                            <option value="#10B981" ${formData.color === '#10B981' ? 'selected' : ''}>Verde Esmeralda</option>
                        </select>
                    </div>
                </div>
            </div>
        </form>
    `;

    const footerLeft = `
        <button type="button" onclick="saveFormFromModal(true)"
                class="inline-flex items-center px-5 py-2.5 text-white text-sm font-medium rounded-md transition-colors"
                style="background-color: #4EA44B;"
                onmouseover="this.style.backgroundColor='#3d8b40'"
                onmouseout="this.style.backgroundColor='#4EA44B'">
            Salvar
        </button>
    `;

    const footerRight = `
        <button type="button" onclick="Swal.close()"
                class="text-sm text-gray-600 dark:text-zinc-400 hover:text-gray-900 dark:hover:text-zinc-100 transition-colors">
            Cancelar
        </button>
    `;

    Swal.fire({
        html: createFormModal({
            title: 'Editar Formulário',
            content: formHTML,
            footer: {
                left: footerLeft,
                right: footerRight
            }
        }),
        width: window.innerWidth < 640 ? '95%' : '600px',
        showConfirmButton: false,
        showCancelButton: false,
        didOpen: () => {
            // Mostrar campos de ícone e cor apenas para admin (edit mode)
            if (window.userRole === 'admin') {
                const iconColorFieldsEdit = document.getElementById('icon-color-fields-edit');
                if (iconColorFieldsEdit) {
                    iconColorFieldsEdit.style.display = 'grid';
                }
            }
        }
    });
}

// Função para alternar entre abas (estilo clean)
window.switchTab = function(tab) {
    // Atualizar botões das abas
    const createTabBtn = document.getElementById('tab-create');
    const templatesTabBtn = document.getElementById('tab-templates');
    const createContent = document.getElementById('tab-content-create');
    const templatesContent = document.getElementById('tab-content-templates');
    const btnCreateForm = document.getElementById('btn-create-form');

    if (tab === 'create') {
        // Ativar aba Criar (botão destacado - verde ou zinc no dark)
        const isDark = document.documentElement.classList.contains('dark');
        createTabBtn.style.backgroundColor = isDark ? '' : '#4EA44B';
        createTabBtn.classList.add('text-white');
        if (isDark) createTabBtn.classList.add('bg-zinc-600');
        createTabBtn.classList.remove('bg-gray-100', 'dark:bg-zinc-700', 'text-gray-700', 'dark:text-zinc-300');

        // Desativar aba Templates (botão normal)
        templatesTabBtn.style.backgroundColor = '';
        templatesTabBtn.classList.remove('text-white', 'bg-zinc-600');
        templatesTabBtn.classList.add('bg-gray-100', 'dark:bg-zinc-700', 'text-gray-700', 'dark:text-zinc-300');

        createContent.classList.remove('hidden');
        templatesContent.classList.add('hidden');

        // Mostrar botão de criar
        btnCreateForm.classList.remove('hidden');

    } else if (tab === 'templates') {
        // Ativar aba Templates (botão destacado - verde ou zinc no dark)
        const isDark = document.documentElement.classList.contains('dark');
        templatesTabBtn.style.backgroundColor = isDark ? '' : '#4EA44B';
        templatesTabBtn.classList.add('text-white');
        if (isDark) templatesTabBtn.classList.add('bg-zinc-600');
        templatesTabBtn.classList.remove('bg-gray-100', 'dark:bg-zinc-700', 'text-gray-700', 'dark:text-zinc-300');

        // Desativar aba Criar (botão normal)
        createTabBtn.style.backgroundColor = '';
        createTabBtn.classList.remove('text-white', 'bg-zinc-600');
        createTabBtn.classList.add('bg-gray-100', 'dark:bg-zinc-700', 'text-gray-700', 'dark:text-zinc-300');

        templatesContent.classList.remove('hidden');
        createContent.classList.add('hidden');

        // Esconder botão de criar
        btnCreateForm.classList.add('hidden');
    }
};

// Função para carregar templates
async function loadTemplates() {
    const loadingDiv = document.getElementById('templates-loading');
    const containerDiv = document.getElementById('templates-container');

    try {
        const res = await fetch('/modules/forms/get_templates.php');
        const data = await res.json();

        if (!data.success) {
            throw new Error(data.error || 'Erro ao carregar templates');
        }

        loadingDiv.classList.add('hidden');
        containerDiv.classList.remove('hidden');

        if (data.templates.length === 0) {
            containerDiv.innerHTML = `
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-5xl text-gray-300 dark:text-zinc-600 mb-4"></i>
                    <p class="text-gray-600 dark:text-zinc-400">Nenhum template disponível no momento</p>
                </div>
            `;
            return;
        }

        // Renderizar templates em grid (estilo folder cards)
        const templatesHTML = data.templates.map(template => `
            <div class="template-card bg-white dark:bg-zinc-800 rounded-md shadow-md p-6 border border-gray-200 dark:border-zinc-700">
                <div class="flex items-start justify-between mb-4">
                    <div class="template-icon-preview" style="background-color: ${template.color}20; color: ${template.color};">
                        <i class="fas fa-${template.icon}"></i>
                    </div>
                </div>
                <h3 class="font-semibold text-lg text-gray-900 dark:text-zinc-100 mb-2">
                    ${template.title}
                </h3>
                <p class="text-sm text-gray-500 dark:text-zinc-400 mb-3">
                    Template
                </p>
                ${template.description ? `
                    <p class="text-xs text-gray-600 dark:text-zinc-500 line-clamp-2">
                        ${template.description}
                    </p>
                ` : ''}
                <button onclick="useTemplate(${template.id})"
                        style="background-color: #4EA44B;" class="w-full mt-4 px-4 py-2 hover:opacity-90 dark:bg-zinc-600 dark:hover:bg-zinc-500 text-white text-sm font-medium rounded-md transition-colors">
                    <i class="fas fa-check mr-2"></i>Usar este template
                </button>
            </div>
        `).join('');

        containerDiv.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-h-[500px] overflow-y-auto pr-2">
                ${templatesHTML}
            </div>
        `;

    } catch (error) {
        loadingDiv.classList.add('hidden');
        containerDiv.classList.remove('hidden');
        containerDiv.innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-exclamation-triangle text-5xl text-red-400 mb-4"></i>
                <p class="text-red-600 dark:text-red-400">${error.message}</p>
            </div>
        `;
    }
}

// Função para usar um template
window.useTemplate = async function(templateId) {
    Swal.showLoading();

    try {
        const formData = new FormData();
        formData.append('template_id', templateId);

        const res = await fetch('/modules/forms/use_template.php', {
            method: 'POST',
            body: formData
        });

        const result = await res.json();

        if (!result.success) {
            throw new Error(result.error || 'Erro ao usar template');
        }

        Swal.close();

        await Swal.fire({
            title: 'Sucesso!',
            text: 'Template aplicado! Você será redirecionado para o editor.',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
        });

        // Redirecionar para o builder
        window.location.href = `/forms/builder/${result.form_id}`;

    } catch (error) {
        Swal.fire({
            title: 'Erro!',
            text: error.message,
            icon: 'error'
        });
    }
};


// Função para salvar formulário do modal
window.saveFormFromModal = async function(isEdit) {
    const form = document.getElementById('modalFormForm');
    const formData = new FormData(form);
    
    const title = formData.get('title').trim();
    
    if (!title) {
        Swal.showValidationMessage('Título é obrigatório');
        return;
    }
    
    Swal.showLoading();
    
    try {
        const res = await fetch("/modules/forms/save.php", {
            method: "POST",
            body: formData
        });
        
        const result = await res.text();
        
        if (res.ok && result.startsWith("success")) {
            const formId = result.split(':')[1];
            Swal.close();
            
            await Swal.fire({
                title: 'Sucesso!',
                text: isEdit ? 'Formulário atualizado com sucesso!' : 'Formulário criado! Agora adicione as perguntas.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
            
            // Redirecionar para o builder
            window.location.href = `/forms/builder/${formId}`;
            
        } else {
            Swal.fire({ 
                title: 'Erro!', 
                text: "Erro ao salvar: " + result, 
                icon: 'error' 
            });
        }
    } catch (error) {
        Swal.fire({ 
            title: 'Erro!', 
            text: "Erro de conexão", 
            icon: 'error' 
        });
    }
};