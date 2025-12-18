
import { Controller } from "@hotwired/stimulus";

export default class AdminCategoryController extends Controller {
    static targets = [
        "search",
        "list",
        "modal",
        "modalTitle",
        "form",
        "name",
        "slug",
        "icon"
    ];

    connect() {
        // Save the original list elements for search reset
        this.originalElements = Array.from(this.listTarget.children);

        // Make sure modal is hidden on load
        this.modalTarget.classList.add("hidden");
    }

    // -------------------------
    // 🔍 Live search
    // -------------------------
    filter() {
        const term = this.searchTarget.value.toLowerCase().trim();

        if (term === "") {
            this.resetList();
            return;
        }

        this.listTarget.innerHTML = "";

        this.originalElements.forEach(el => {
            const name = el.dataset.categoryName;

            if (name && name.includes(term)) {
                this.listTarget.appendChild(el);
            }
        });
    }

    resetList() {
        this.listTarget.innerHTML = "";
        this.originalElements.forEach(el => this.listTarget.appendChild(el));
    }

    // -------------------------
    // 🟦 Modal handling
    // -------------------------
    openModal() {
        this.resetForm();
        this.modalTitleTarget.textContent = "Nouvelle catégorie";
        this.modalTarget.classList.remove("hidden");
    }

    closeModal() {
        this.modalTarget.classList.add("hidden");
    }

    // Close modal on background click
    closeBackground(event) {
        if (event.target === this.modalTarget) {
            this.closeModal();
        }
    }

    // -------------------------
    // ✏️ Edit category
    // -------------------------
    edit(event) {
        const btn = event.currentTarget;

        this.nameTarget.value = btn.dataset.name;
        this.slugTarget.value = btn.dataset.slug;
        this.iconTarget.value = btn.dataset.icon || "Code";

        this.modalTitleTarget.textContent = "Modifier la catégorie";
        this.formTarget.action = `/admin/categories/edit/${btn.dataset.id}`;

        this.modalTarget.classList.remove("hidden");
    }

    // -------------------------
    // 🔤 Auto slug on name change
    // -------------------------
    updateSlug() {
        const name = this.nameTarget.value;

        const slug = name
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-z0-9]+/g, "-")
            .replace(/(^-|-$)/g, "");

        this.slugTarget.value = slug;
    }

    // -------------------------
    // 🧹 Reset form
    // -------------------------
    resetForm() {
        this.formTarget.action = "/admin/categories/new";
        this.nameTarget.value = "";
        this.slugTarget.value = "";
        this.iconTarget.value = "Code";
    }
}