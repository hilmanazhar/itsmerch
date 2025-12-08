// This file defines the Toggle component, allowing for binary selections.
// It utilizes Bootstrap for styling and functionality.

document.addEventListener('DOMContentLoaded', function () {
    const toggleElements = document.querySelectorAll('.toggle');

    toggleElements.forEach(toggle => {
        toggle.addEventListener('click', function () {
            this.classList.toggle('active');
            const isActive = this.classList.contains('active');
            const toggleInput = this.querySelector('input[type="checkbox"]');
            toggleInput.checked = isActive;
        });
    });
});