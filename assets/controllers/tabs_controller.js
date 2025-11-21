import { Controller } from "@hotwired/stimulus";

export default class TabsController extends Controller {
    static targets = ["tabButton", "tabContent"]

    connect() {
        this.showTab('profile');
    }

    showTab(tab) {
        this.tabContentTargets.forEach(c => c.dataset.tab === tab ? c.style.display = 'block' : c.style.display = 'none');
        this.tabButtonTargets.forEach(btn => btn.classList.toggle('active', btn.dataset.tab === tab));
    }

    switch(event) {
        event.preventDefault();
        const tab = event.currentTarget.dataset.tab;
        this.showTab(tab);
    }
}