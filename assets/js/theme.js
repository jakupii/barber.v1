(() => {
    'use strict';
    const root = document.documentElement;
    let theme = 'light';
    try {
        const saved = localStorage.getItem('gentlemanbarber_theme');
        theme = saved === 'dark' || saved === 'light'
            ? saved
            : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    } catch (_) {}
    root.dataset.theme = theme;

    const labels = {
        sq: { dark: 'Aktivizo modalitetin e errët', light: 'Aktivizo modalitetin e çelët', darkTitle: 'Modaliteti i errët', lightTitle: 'Modaliteti i çelët' },
        mk: { dark: 'Вклучи темен режим', light: 'Вклучи светол режим', darkTitle: 'Темен режим', lightTitle: 'Светол режим' },
        en: { dark: 'Enable dark mode', light: 'Enable light mode', darkTitle: 'Dark mode', lightTitle: 'Light mode' }
    };

    const syncButtons = () => {
        const language = labels[root.lang] ? root.lang : 'sq';
        document.querySelectorAll('.theme-toggle').forEach((button) => {
            const dark = root.dataset.theme === 'dark';
            button.setAttribute('aria-pressed', dark ? 'true' : 'false');
            button.setAttribute('aria-label', dark ? labels[language].light : labels[language].dark);
            button.title = dark ? labels[language].lightTitle : labels[language].darkTitle;
        });
    };

    document.addEventListener('gentlemanbarber:languagechange', syncButtons);

    document.addEventListener('DOMContentLoaded', () => {
        syncButtons();
        document.querySelectorAll('.theme-toggle').forEach((button) => {
            button.addEventListener('click', () => {
                root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
                try { localStorage.setItem('gentlemanbarber_theme', root.dataset.theme); } catch (_) {}
                syncButtons();
            });
        });
    });
})();
