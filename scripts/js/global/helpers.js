/**
 * Global Helper Functions
 * Funções auxiliares reutilizáveis em todo o sistema
 */

/**
 * Gera slug amigável a partir de um título
 */
function generateSlugFromTitle(title) {
    return title.toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-+|-+$/g, '');
}

/**
 * Retorna ícone SVG apropriado baseado no tipo MIME do arquivo
 */
function getFileIcon(mimeType) {
    const icons = {
        pdf: { 
            color: 'text-red-400', 
            path: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' 
        },
        word: { 
            color: 'text-blue-400', 
            path: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' 
        },
        excel: { 
            color: 'text-green-400', 
            path: 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h2a2 2 0 002-2z' 
        },
        zip: { 
            color: 'text-yellow-400', 
            path: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' 
        },
        image: { 
            color: 'text-purple-400', 
            path: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' 
        },
        default: { 
            color: 'text-zinc-400', 
            path: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' 
        }
    };
    
    let icon = icons.default;
    if (mimeType.includes('pdf')) icon = icons.pdf;
    else if (mimeType.includes('word') || mimeType.includes('document')) icon = icons.word;
    else if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) icon = icons.excel;
    else if (mimeType.includes('zip') || mimeType.includes('rar')) icon = icons.zip;
    else if (mimeType.includes('image')) icon = icons.image;
    
    return `<svg class="w-6 h-6 ${icon.color}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${icon.path}"></path>
            </svg>`;
}

/**
 * Formata tamanho de arquivo de bytes para formato legível
 */
function formatFileSize(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

/**
 * Formata data para padrão brasileiro
 */
function formatDateBR(date) {
    const d = new Date(date);
    return d.toLocaleDateString('pt-BR');
}

/**
 * Formata data e hora para padrão brasileiro
 */
function formatDateTimeBR(datetime) {
    const d = new Date(datetime);
    return d.toLocaleString('pt-BR', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Debounce - Atrasa execução de função
 */
function debounce(func, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}