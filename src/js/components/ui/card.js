const createCard = (title, content, imageUrl) => {
    return `
    <div class="card" style="width: 18rem;">
        <img src="${imageUrl}" class="card-img-top" alt="${title}">
        <div class="card-body">
            <h5 class="card-title">${title}</h5>
            <p class="card-text">${content}</p>
            <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
    </div>
    `;
};

export { createCard };