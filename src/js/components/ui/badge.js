// This file defines the Badge component, which displays small notifications or labels.
document.addEventListener("DOMContentLoaded", function() {
    const badgeContainer = document.createElement('div');
    badgeContainer.className = 'badge-container';

    const badge = document.createElement('span');
    badge.className = 'badge bg-primary'; // Bootstrap class for primary badge
    badge.textContent = 'New'; // Example badge text

    badgeContainer.appendChild(badge);
    document.body.appendChild(badgeContainer);
});