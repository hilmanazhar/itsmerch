// This file defines the Sidebar component, used for navigation.
document.addEventListener("DOMContentLoaded", function() {
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebar = document.getElementById("sidebar");

    sidebarToggle.addEventListener("click", function() {
        sidebar.classList.toggle("active");
    });
});

// HTML structure for the sidebar
const sidebarHTML = `
    <nav id="sidebar" class="bg-light border-right">
        <div class="sidebar-header">
            <h3>ITS Merchandise</h3>
        </div>
        <ul class="list-unstyled components">
            <li>
                <a href="#home">Home</a>
            </li>
            <li>
                <a href="#products">Products</a>
            </li>
            <li>
                <a href="#about">About</a>
            </li>
            <li>
                <a href="#contact">Contact</a>
            </li>
        </ul>
        <button id="sidebarToggle" class="btn btn-primary">Toggle Sidebar</button>
    </nav>
`;

// Append the sidebar to the body or a specific container
document.body.insertAdjacentHTML('afterbegin', sidebarHTML);