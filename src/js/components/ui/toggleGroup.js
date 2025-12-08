// This file handles the Toggle Group component, allowing for grouped toggles.

document.addEventListener("DOMContentLoaded", function () {
    const toggleGroup = document.querySelector(".toggle-group");
    const toggles = toggleGroup.querySelectorAll(".toggle");

    toggles.forEach(toggle => {
        toggle.addEventListener("click", function () {
            toggles.forEach(t => t.classList.remove("active"));
            this.classList.add("active");
        });
    });
});