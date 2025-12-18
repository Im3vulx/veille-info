import { Controller } from '@hotwired/stimulus';

export default class SettingtabsController extends Controller {
    static targets = ["tab", "panel"];

    connect() {
        if (!this.hasActiveTab) {
            this.showTab('general');
        }
        // console log des targets pour debug
        console.log("Tab Targets:", this.tabTargets);
        console.log("Panel Targets:", this.panelTargets);
    }

    switch(event) {
        event.preventDefault();
        const selectedTabId = event.currentTarget.dataset.tabId;
        this.showTab(selectedTabId);
    }

    showTab(tabId) {
        // 1. Gestion des boutons (Tabs)
        this.tabTargets.forEach(tab => {
            if (tab.dataset.tabId === tabId) {
                tab.classList.add('active');
            } else {
                tab.classList.remove('active');
            }
        });

        // 2. Gestion des contenus (Panels)
        this.panelTargets.forEach(panel => {
            if (panel.dataset.panelId === tabId) {
                panel.classList.add('active');
            } else {
                panel.classList.remove('active');
            }
        });
    }

    get hasActiveTab() {
        return this.tabTargets.some(tab => tab.classList.contains('active'));
    }
}