// src/js/components/ui/alertDialog.js

document.addEventListener('DOMContentLoaded', function () {
    const alertDialog = document.getElementById('alertDialog');
    const closeButton = document.getElementById('closeAlertDialog');

    function showAlertDialog(message) {
        alertDialog.querySelector('.alert-message').textContent = message;
        alertDialog.classList.add('show');
    }

    closeButton.addEventListener('click', function () {
        alertDialog.classList.remove('show');
    });

    // Example usage
    // showAlertDialog('This is an alert message!');
});