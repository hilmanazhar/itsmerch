// This file handles the Sheet component, typically used for modal content.

document.addEventListener('DOMContentLoaded', function () {
    const sheetToggleButton = document.getElementById('sheetToggleButton');
    const sheet = document.getElementById('sheet');

    if (sheetToggleButton && sheet) {
        sheetToggleButton.addEventListener('click', function () {
            sheet.classList.toggle('show');
        });
    }
});