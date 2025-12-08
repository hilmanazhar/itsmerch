// src/js/components/ui/dialog.js

document.addEventListener('DOMContentLoaded', function () {
    const dialogTrigger = document.getElementById('dialogTrigger');
    const dialog = document.getElementById('dialog');
    const closeButton = document.getElementById('closeDialog');

    dialogTrigger.addEventListener('click', function () {
        dialog.classList.add('show');
    });

    closeButton.addEventListener('click', function () {
        dialog.classList.remove('show');
    });

    window.addEventListener('click', function (event) {
        if (event.target === dialog) {
            dialog.classList.remove('show');
        }
    });
});

// HTML structure for the dialog (to be included in the relevant HTML file)
// <button id="dialogTrigger" class="btn btn-primary">Open Dialog</button>
// <div id="dialog" class="modal" tabindex="-1">
//     <div class="modal-dialog">
//         <div class="modal-content">
//             <div class="modal-header">
//                 <h5 class="modal-title">Dialog Title</h5>
//                 <button type="button" class="btn-close" id="closeDialog" aria-label="Close"></button>
//             </div>
//             <div class="modal-body">
//                 <p>Dialog body content goes here.</p>
//             </div>
//             <div class="modal-footer">
//                 <button type="button" class="btn btn-secondary" id="closeDialog">Close</button>
//             </div>
//         </div>
//     </div>
// </div>