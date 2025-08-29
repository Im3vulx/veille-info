import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["textarea"];

    connect() {
        this.textareaTargets.forEach(textarea => {
            const adjustHeight = () => {
                textarea.style.height = "auto";
                textarea.style.height = textarea.scrollHeight + "px";
            };

            textarea.addEventListener("input", adjustHeight);
            adjustHeight();
        });
    }
}