// assets/controllers/theme_controller.js
import { Controller } from '@hotwired/stimulus';

export default class ThemeController extends Controller {
    connect() {
        this.updateIcon();
    }

    switch() {
        const isDark = document.documentElement.classList.toggle('dark');
        
        // Sauvegarder
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        
        this.updateIcon();
    }

    updateIcon() {
        const isDark = document.documentElement.classList.contains('dark');
        this.element.setAttribute('aria-pressed', isDark.toString());
        
        // Optionnel : Si vous voulez cacher/montrer des icônes spécifiques via CSS
        // Vous pouvez ajouter des classes 'hidden' ici si besoin
    }
}