// src/js/components/cartDrawer.js

document.addEventListener('DOMContentLoaded', function () {
    const cartDrawer = document.getElementById('cartDrawer');
    const openCartButton = document.getElementById('openCartButton');
    const closeCartButton = document.getElementById('closeCartButton');

    openCartButton.addEventListener('click', function () {
        cartDrawer.classList.add('show');
    });

    closeCartButton.addEventListener('click', function () {
        cartDrawer.classList.remove('show');
    });
});

// HTML structure for the cart drawer
// <div id="cartDrawer" class="cart-drawer">
//     <button id="closeCartButton" class="btn btn-close">Close</button>
//     <div class="cart-content">
//         <!-- Cart items will be dynamically inserted here -->
//     </div>
// </div>
// <button id="openCartButton" class="btn btn-primary">Open Cart</button>