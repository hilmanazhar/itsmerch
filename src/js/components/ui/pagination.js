const Pagination = () => {
    const totalPages = 10; // Example total pages
    let currentPage = 1;

    const handlePageClick = (page) => {
        currentPage = page;
        render();
    };

    const render = () => {
        const paginationContainer = document.getElementById('pagination');
        paginationContainer.innerHTML = '';

        for (let i = 1; i <= totalPages; i++) {
            const pageItem = document.createElement('li');
            pageItem.className = `page-item ${currentPage === i ? 'active' : ''}`;
            pageItem.innerHTML = `<a class="page-link" href="#" onclick="handlePageClick(${i})">${i}</a>`;
            paginationContainer.appendChild(pageItem);
        }
    };

    return {
        render,
        handlePageClick,
    };
};

document.addEventListener('DOMContentLoaded', () => {
    const pagination = Pagination();
    pagination.render();
});