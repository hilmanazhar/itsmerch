const hoverCard = () => {
    const hoverCardElement = document.createElement('div');
    hoverCardElement.classList.add('hover-card', 'card', 'shadow-sm');

    const cardBody = document.createElement('div');
    cardBody.classList.add('card-body');

    const title = document.createElement('h5');
    title.classList.add('card-title');
    title.innerText = 'Hover Card Title';

    const text = document.createElement('p');
    text.classList.add('card-text');
    text.innerText = 'Some quick example text to build on the hover card title and make up the bulk of the card\'s content.';

    cardBody.appendChild(title);
    cardBody.appendChild(text);
    hoverCardElement.appendChild(cardBody);

    return hoverCardElement;
};

document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('#hover-card-container');
    if (container) {
        container.appendChild(hoverCard());
    }
});