import { Controller } from '@hotwired/stimulus';

export default class SidebarController extends Controller {
    static targets = ["link"];

    connect() {
        // Optionnel : On peut vérifier l'URL au chargement si besoin,
        // mais Twig fait déjà le travail pour le premier affichage.
    }

    activate(event) {
        // 1. On enlève la classe 'active' de tous les liens
        this.linkTargets.forEach(link => {
            link.classList.remove('active');
        });

        // 2. On l'ajoute sur le lien sur lequel on vient de cliquer
        event.currentTarget.classList.add('active');
    }
}