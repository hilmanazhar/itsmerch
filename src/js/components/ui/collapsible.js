// This file manages collapsible sections for UI elements using Bootstrap.
// It initializes collapsible behavior for elements with the specified data attributes.

document.addEventListener('DOMContentLoaded', function () {
    const collapsibleElements = document.querySelectorAll('[data-toggle="collapse"]');

    collapsibleElements.forEach(element => {
        element.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const targetElement = document.querySelector(targetId);

            if (targetElement) {
                targetElement.classList.toggle('show');
            }
        });
    });
});