import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.scss';

// Gestion du thème clair / sombre
(function () {
    const STORAGE_KEY = 'theme';
    const root = document.documentElement;

    // Appliquer le thème sauvegardé ou suivre le système
    const savedTheme = localStorage.getItem(STORAGE_KEY);

    if (savedTheme === 'dark' || savedTheme === 'light') {
        root.classList.toggle('dark', savedTheme === 'dark');
    } else if (window.matchMedia &&
        window.matchMedia('(prefers-color-scheme: dark)').matches) {
        root.classList.add('dark');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.querySelector('[data-theme-toggle]');
        if (!toggle) {
            return;
        }

        const updateIcons = () => {
            const isDark = root.classList.contains('dark');
            toggle.setAttribute('aria-pressed', String(isDark));
        };

        toggle.addEventListener('click', function () {
            const isDark = root.classList.toggle('dark');
            localStorage.setItem(STORAGE_KEY, isDark ? 'dark' : 'light');
            updateIcons();
        });

        updateIcons();
    });
})();
