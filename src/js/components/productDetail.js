// src/js/components/productDetail.js

document.addEventListener("DOMContentLoaded", function() {
    const productDetailContainer = document.getElementById("product-detail");

    // Sample product data
    const product = {
        id: 1,
        name: "Sample Product",
        description: "This is a detailed description of the sample product.",
        price: 29.99,
        imageUrl: "path/to/image.jpg"
    };

    // Function to render product details
    function renderProductDetail(product) {
        productDetailContainer.innerHTML = `
            <div class="card">
                <img src="${product.imageUrl}" class="card-img-top" alt="${product.name}">
                <div class="card-body">
                    <h5 class="card-title">${product.name}</h5>
                    <p class="card-text">${product.description}</p>
                    <p class="card-text"><strong>Price: $${product.price.toFixed(2)}</strong></p>
                    <button class="btn btn-primary">Add to Cart</button>
                </div>
            </div>
        `;
    }

    // Render the product details on page load
    renderProductDetail(product);
});