// This file defines the Context Menu component, providing additional options on right-click.

document.addEventListener('DOMContentLoaded', function () {
    const contextMenu = document.getElementById('context-menu');

    document.addEventListener('contextmenu', function (event) {
        event.preventDefault();
        contextMenu.style.display = 'block';
        contextMenu.style.left = `${event.pageX}px`;
        contextMenu.style.top = `${event.pageY}px`;
    });

    document.addEventListener('click', function () {
        contextMenu.style.display = 'none';
    });

    // Add event listeners for context menu items
    const menuItems = contextMenu.querySelectorAll('li');
    menuItems.forEach(item => {
        item.addEventListener('click', function () {
            alert(`You clicked on ${this.textContent}`);
            contextMenu.style.display = 'none';
        });
    });
});