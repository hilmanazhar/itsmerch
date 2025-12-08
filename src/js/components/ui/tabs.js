// src/js/components/ui/tabs.js
document.addEventListener("DOMContentLoaded", function () {
    const tabLinks = document.querySelectorAll('.nav-tabs .nav-link');
    const tabContents = document.querySelectorAll('.tab-content .tab-pane');

    tabLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const target = this.getAttribute('href');

            tabLinks.forEach(link => link.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('show', 'active'));

            this.classList.add('active');
            document.querySelector(target).classList.add('show', 'active');
        });
    });
});