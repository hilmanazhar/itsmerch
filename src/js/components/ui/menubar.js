const menubar = () => {
    const menu = document.createElement('nav');
    menu.classList.add('navbar', 'navbar-expand-lg', 'navbar-light', 'bg-light');

    const brand = document.createElement('a');
    brand.classList.add('navbar-brand');
    brand.href = '#';
    brand.textContent = 'ITS Merchandise';

    const toggleButton = document.createElement('button');
    toggleButton.classList.add('navbar-toggler');
    toggleButton.type = 'button';
    toggleButton.setAttribute('data-toggle', 'collapse');
    toggleButton.setAttribute('data-target', '#navbarNav');
    toggleButton.setAttribute('aria-controls', 'navbarNav');
    toggleButton.setAttribute('aria-expanded', 'false');
    toggleButton.setAttribute('aria-label', 'Toggle navigation');

    const toggleIcon = document.createElement('span');
    toggleIcon.classList.add('navbar-toggler-icon');
    toggleButton.appendChild(toggleIcon);

    const collapseDiv = document.createElement('div');
    collapseDiv.classList.add('collapse', 'navbar-collapse');
    collapseDiv.id = 'navbarNav';

    const navList = document.createElement('ul');
    navList.classList.add('navbar-nav');

    const navItems = ['Home', 'Products', 'About', 'Contact'];
    navItems.forEach(item => {
        const navItem = document.createElement('li');
        navItem.classList.add('nav-item');

        const navLink = document.createElement('a');
        navLink.classList.add('nav-link');
        navLink.href = '#';
        navLink.textContent = item;

        navItem.appendChild(navLink);
        navList.appendChild(navItem);
    });

    collapseDiv.appendChild(navList);
    menu.appendChild(brand);
    menu.appendChild(toggleButton);
    menu.appendChild(collapseDiv);

    return menu;
};

document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('root');
    app.appendChild(menubar());
});