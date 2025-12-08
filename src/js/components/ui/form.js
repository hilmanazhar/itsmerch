// This file manages form-related components and validation.

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('myForm');
    
    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            // Perform form validation and submission logic here
            const formData = new FormData(form);
            // Example: Log form data to console
            for (const [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }
            // You can also use AJAX to submit the form data to a server
        });
    }
});

// Example function to reset the form
function resetForm() {
    const form = document.getElementById('myForm');
    if (form) {
        form.reset();
    }
}