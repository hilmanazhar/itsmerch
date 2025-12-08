// This file handles the Drawer component, typically used for side navigation.

document.addEventListener('DOMContentLoaded', function () {
    const drawerToggle = document.getElementById('drawer-toggle');
    const drawer = document.getElementById('drawer');

    drawerToggle.addEventListener('click', function () {
        drawer.classList.toggle('open');
    });
});

// HTML structure for the drawer component
const drawerHTML = `
<div id="drawer" class="drawer">
    <div class="drawer-header">
        <h2>Menu</h2>
        <button id="drawer-toggle" class="btn-close">Close</button>
    </div>
    <ul class="drawer-menu">
        <li><a href="#home">Home</a></li>
        <li><a href="#products">Products</a></li>
        <li><a href="#about">About</a></li>
        <li><a href="#contact">Contact</a></li>
    </ul>
</div>
`;

document.body.insertAdjacentHTML('beforeend', drawerHTML);