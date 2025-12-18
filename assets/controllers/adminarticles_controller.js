import { Controller } from "@hotwired/stimulus";

export default class AdminArticlesController extends Controller {
    static targets = ["search", "status", "category", "table", "pagination"];

    connect() {
        this.itemsPerPage = 20;
        this.currentPage = 1;
        this.refresh();

        requestAnimationFrame(() => {
            this.refresh();
        });
    }

    filter() {
        this.currentPage = 1;
        this.refresh();
    }

    refresh() {
        const search = this.searchTarget.value.toLowerCase().trim();
        const selectedStatus = String(this.statusTarget.value).trim();
        const selectedCategory = String(this.categoryTarget.value).trim();

        const rows = [...this.tableTarget.querySelectorAll("tr")];

        this.filteredRows = rows.filter(row => {
            const title = row.dataset.title.toLowerCase();
            const status = String(row.dataset.status).trim();
            const category = String(row.dataset.category).trim();

            const matchSearch = title.includes(search);
            const matchStatus = selectedStatus === "all" || selectedStatus === status;
            const matchCategory = selectedCategory === "all" || selectedCategory === category;

            return matchSearch && matchStatus && matchCategory;
        });

        this.updateTable();
        this.updatePagination();
    }

    updateTable() {
        const allRows = this.tableTarget.querySelectorAll("tr");
        allRows.forEach(r => r.style.display = "none");

        const start = (this.currentPage - 1) * this.itemsPerPage;
        const end = start + this.itemsPerPage;

        this.filteredRows.slice(start, end).forEach(row => {
            row.style.display = "";
        });
    }

    updatePagination() {
        const totalPages = Math.ceil(this.filteredRows.length / this.itemsPerPage);
        this.paginationTarget.innerHTML = "";

        if (totalPages <= 1) {
            return;
        }

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement("button");
            btn.textContent = i;

            if (i === this.currentPage) {
                btn.classList.add("active");
            }

            btn.addEventListener("click", () => {
                this.currentPage = i;
                this.updateTable();
                this.updatePagination();
            });

            this.paginationTarget.appendChild(btn);
        }
    }
}