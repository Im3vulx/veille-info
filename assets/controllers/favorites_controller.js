import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["article", "search", "category", "sort"];

  connect() {
    // Filtre dès le chargement
    this.filter();

    // Ajoute des listeners pour les inputs
    if (this.hasSearchTarget) {
      this.searchTarget.addEventListener("input", () => this.filter());
    }
    if (this.hasCategoryTarget) {
      this.categoryTarget.addEventListener("change", () => this.filter());
    }
    if (this.hasSortTarget) {
      this.sortTarget.addEventListener("change", () => this.filter());
    }
  }

  filter() {
    const searchTerm = this.hasSearchTarget
      ? this.searchTarget.value.toLowerCase()
      : "";
    const category = this.hasCategoryTarget ? this.categoryTarget.value : "all";
    const sortBy = this.hasSortTarget ? this.sortTarget.value : "recent";

    // Transforme la NodeList en array pour manipuler facilement
    const articles = Array.from(this.articleTargets);

    // Filtrer les articles
    articles.forEach((article) => {
      const title = article
        .querySelector(".article-title a")
        .textContent.toLowerCase();
      const articleCategory = article.dataset.categoryId;

      const matchesSearch = title.includes(searchTerm);
      const matchesCategory =
        category === "all" || category === articleCategory;

      article.style.display =
        matchesSearch && matchesCategory ? "block" : "none";
    });

    // Tri
    const visibleArticles = articles.filter((a) => a.style.display !== "none");

    visibleArticles.sort((a, b) => {
      if (sortBy === "recent") {
        return new Date(b.dataset.createdAt) - new Date(a.dataset.createdAt);
      } else if (sortBy === "oldest") {
        return new Date(a.dataset.createdAt) - new Date(b.dataset.createdAt);
      } else if (sortBy === "title") {
        return a
          .querySelector(".article-title a")
          .textContent.localeCompare(
            b.querySelector(".article-title a").textContent
          );
      }
      return 0;
    });

    // Réordonner uniquement les articles visibles
    const container = this.articleTargets[0]?.parentNode;
    if (container) {
      visibleArticles.forEach((article) => container.appendChild(article));
    }
  }
}
