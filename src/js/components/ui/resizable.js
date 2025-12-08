// This file manages resizable UI elements using Bootstrap's utilities and JavaScript.
// It allows elements to be resized by dragging their edges.

document.addEventListener('DOMContentLoaded', function () {
    const resizableElements = document.querySelectorAll('.resizable');

    resizableElements.forEach(element => {
        const resizer = document.createElement('div');
        resizer.classList.add('resizer', 'border', 'border-primary', 'd-inline-block', 'position-absolute', 'bottom-0', 'end-0', 'cursor-se-resize');
        element.appendChild(resizer);

        resizer.addEventListener('mousedown', initResize);
    });

    function initResize(e) {
        window.addEventListener('mousemove', startResize);
        window.addEventListener('mouseup', stopResize);
    }

    function startResize(e) {
        const element = e.target.parentElement;
        element.style.width = (e.clientX - element.getBoundingClientRect().left) + 'px';
        element.style.height = (e.clientY - element.getBoundingClientRect().top) + 'px';
    }

    function stopResize() {
        window.removeEventListener('mousemove', startResize);
        window.removeEventListener('mouseup', stopResize);
    }
});