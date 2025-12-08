// This file defines the Sonner component, possibly for notifications using Bootstrap.

document.addEventListener('DOMContentLoaded', function () {
    const sonnerContainer = document.createElement('div');
    sonnerContainer.className = 'sonner-container';

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show`;
        notification.role = 'alert';
        notification.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        `;
        sonnerContainer.appendChild(notification);
        document.body.appendChild(sonnerContainer);

        setTimeout(() => {
            notification.classList.remove('show');
            notification.addEventListener('transitionend', () => {
                notification.remove();
            });
        }, 3000);
    }

    // Example usage
    showNotification('Welcome to the ITS Merchandise Web!', 'success');
});