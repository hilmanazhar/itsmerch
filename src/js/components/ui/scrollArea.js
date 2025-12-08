// This file handles scrollable areas within the UI using Bootstrap classes for styling and functionality.

document.addEventListener("DOMContentLoaded", function() {
    const scrollArea = document.querySelector('.scroll-area');

    // Initialize Bootstrap scrollspy if needed
    if (scrollArea) {
        const scrollspy = new bootstrap.ScrollSpy(document.body, {
            target: '.scroll-area',
            offset: 100
        });
    }
});

// Example function to add content to the scroll area
function addContentToScrollArea(content) {
    const scrollArea = document.querySelector('.scroll-area');
    if (scrollArea) {
        const newContent = document.createElement('div');
        newContent.classList.add('content-item');
        newContent.innerHTML = content;
        scrollArea.appendChild(newContent);
    }
}