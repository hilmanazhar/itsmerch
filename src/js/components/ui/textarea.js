// This file manages the Textarea component, allowing for multi-line text entry.

document.addEventListener("DOMContentLoaded", function() {
    const textarea = document.createElement("textarea");
    textarea.className = "form-control";
    textarea.rows = 4;
    textarea.placeholder = "Enter your text here...";

    const container = document.createElement("div");
    container.className = "mb-3";
    container.appendChild(textarea);

    document.body.appendChild(container);
});