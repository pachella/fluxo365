/**
 * Global Theme Functions
 * Sistema unificado de classes para Dark Mode
 */
/**
 * Retorna objeto com todas as classes necessárias baseado no tema atual
 * @returns {Object} Objeto com classes CSS para cada elemento
 */
const getThemeClasses = () => {
    const isDark = document.documentElement.classList.contains('dark');

    return {
        // Textos
        title: isDark ? 'text-zinc-100' : 'text-gray-900',
        text: isDark ? 'text-zinc-300' : 'text-gray-700',
        textMuted: isDark ? 'text-zinc-400' : 'text-gray-500',

        // Separadores
        hr: isDark ? 'border-zinc-700' : 'border-gray-200',
        border: isDark ? 'border-zinc-700' : 'border-gray-200',

        // Inputs e Selects
        input: isDark ? 'bg-zinc-800 border-zinc-700 text-zinc-100 placeholder-zinc-400 focus:ring-zinc-500' : 'border focus:ring-blue-500',
        select: isDark ? 'bg-zinc-800 border-zinc-700 text-zinc-100 focus:ring-zinc-500' : 'border focus:ring-blue-500',
        checkbox: isDark ? 'bg-zinc-700 border-zinc-600 text-zinc-500 focus:ring-zinc-500' : 'focus:ring-indigo-500',

        // Backgrounds
        bg: isDark ? 'bg-zinc-800' : 'bg-gray-50',
        bgHover: isDark ? 'bg-zinc-800 hover:bg-zinc-700' : 'bg-gray-50 hover:bg-gray-100',

        // Elementos específicos
        excerptBg: isDark ? 'bg-zinc-800 border-l-4 border-zinc-600' : 'bg-blue-50 border-l-4 border-blue-400',
        excerptText: isDark ? 'text-zinc-300' : 'text-blue-800',
        prose: isDark ? 'prose-invert' : '',

        // Ícones e links
        iconColor: isDark ? 'text-zinc-400' : 'text-gray-600',
        linkColor: isDark ? 'text-zinc-400 hover:text-zinc-200' : 'text-blue-600 hover:text-blue-800',
        btnRemove: isDark ? 'text-red-400 hover:text-red-300' : 'text-red-500 hover:text-red-700'
    };
};