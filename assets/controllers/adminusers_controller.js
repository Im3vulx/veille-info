import { Controller } from "@hotwired/stimulus";

export default class AdminUsersController extends Controller {
    static targets = ["table", "search", "role", "pagination"];

    connect() {
        this.pageSize = 10;
        this.currentPage = 1;
        this.originalRows = Array.from(this.tableTarget.querySelectorAll("tr"));
        this.filter();
    }

    filter() {
        const search = this.searchTarget.value.toLowerCase();
        const role = this.roleTarget.value; // NEW

        this.filtered = this.originalRows.filter(row => {
            const textMatch = row.innerText.toLowerCase().includes(search);

            const roleMatch =
                role === "all" ||
                row.innerHTML.includes(role);

            return textMatch && roleMatch;
        });

        this.currentPage = 1;
        this.render();
    }

    render() {
        const start = (this.currentPage - 1) * this.pageSize;
        const end = start + this.pageSize;

        this.tableTarget.innerHTML = "";
        this.filtered.slice(start, end).forEach(r => {
            this.tableTarget.appendChild(r.cloneNode(true));
        });

        this.renderPagination();
    }

    renderPagination() {
        const total = this.filtered.length;
        const pages = Math.ceil(total / this.pageSize);

        if (pages <= 1) {
            this.paginationTarget.innerHTML = "";
            return;
        }

        let html = "";
        for (let p = 1; p <= pages; p++) {
            html += `<button 
                        data-action="click->adminusers#goTo" 
                        data-page="${p}" 
                        class="${p === this.currentPage ? "active" : ""}">
                        ${p}
                    </button>`;
        }

        this.paginationTarget.innerHTML = html;
    }

    goTo(event) {
        this.currentPage = Number.parseInt(event.target.dataset.page);
        this.render();
    }
}