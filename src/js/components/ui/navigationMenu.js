const navigationMenu = () => {
    const navMenu = document.createElement('nav');
    navMenu.classList.add('navbar', 'navbar-expand-lg', 'navbar-light', 'bg-light');

    const container = document.createElement('div');
    container.classList.add('container-fluid');

    const brand = document.createElement('a');
    brand.classList.add('navbar-brand');
    brand.href = '#';
    brand.textContent = 'ITS Merchandise';

    const toggleButton = document.createElement('button');
    toggleButton.classList.add('navbar-toggler');
    toggleButton.type = 'button';
    toggleButton.setAttribute('data-bs-toggle', 'collapse');
    toggleButton.setAttribute('data-bs-target', '#navbarNav');
    toggleButton.setAttribute('aria-controls', 'navbarNav');
    toggleButton.setAttribute('aria-expanded', 'false');
    toggleButton.setAttribute('aria-label', 'Toggle navigation');

    const icon = document.createElement('span');
    icon.classList.add('navbar-toggler-icon');
    toggleButton.appendChild(icon);

    const collapseDiv = document.createElement('div');
    collapseDiv.classList.add('collapse', 'navbar-collapse');
    collapseDiv.id = 'navbarNav';

    const ul = document.createElement('ul');
    ul.classList.add('navbar-nav');

    const menuItems = [
        { name: 'Home', href: '#' },
        { name: 'Products', href: '#' },
        { name: 'About', href: '#' },
        { name: 'Contact', href: '#' }
    ];

    menuItems.forEach(item => {
        const li = document.createElement('li');
        li.classList.add('nav-item');

        const link = document.createElement('a');
        link.classList.add('nav-link');
        link.href = item.href;
        link.textContent = item.name;

        li.appendChild(link);
        ul.appendChild(li);
    });

    collapseDiv.appendChild(ul);
    container.appendChild(brand);
    container.appendChild(toggleButton);
    container.appendChild(collapseDiv);
    navMenu.appendChild(container);

    return navMenu;
};

document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('app');
    app.appendChild(navigationMenu());
});