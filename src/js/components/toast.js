// src/js/components/toast.js
document.addEventListener('DOMContentLoaded', function () {
    const toastTrigger = document.getElementById('liveToastBtn');
    const toastLiveExample = document.getElementById('liveToast');

    if (toastTrigger) {
        toastTrigger.addEventListener('click', function () {
            const toast = new bootstrap.Toast(toastLiveExample);
            toast.show();
        });
    }
});

// HTML structure for the toast (to be included in your index.html or relevant component)
// <button id="liveToastBtn" class="btn btn-primary">Show Toast</button>
// <div class="toast" id="liveToast" role="alert" aria-live="assertive" aria-atomic="true">
//     <div class="toast-header">
//         <strong class="me-auto">Bootstrap Toast</strong>
//         <small>Just now</small>
//         <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
//     </div>
//     <div class="toast-body">
//         Hello, world! This is a toast message.
//     </div>
// </div>