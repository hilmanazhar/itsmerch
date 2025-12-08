const ItemCard = ({ item }) => {
    return `
        <div class="card mb-4">
            <img src="${item.image}" class="card-img-top" alt="${item.title}">
            <div class="card-body">
                <h5 class="card-title">${item.title}</h5>
                <p class="card-text">${item.description}</p>
                <p class="card-text"><strong>Price: $${item.price}</strong></p>
                <a href="#" class="btn btn-primary">Add to Cart</a>
            </div>
        </div>
    `;
};

export default ItemCard;