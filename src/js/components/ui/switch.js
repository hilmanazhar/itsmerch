// This file manages the Switch component, allowing for binary toggles.

document.addEventListener('DOMContentLoaded', function () {
    const switchElement = document.querySelector('.switch-input');

    if (switchElement) {
        switchElement.addEventListener('change', function () {
            if (this.checked) {
                console.log('Switch is ON');
            } else {
                console.log('Switch is OFF');
            }
        });
    }
});

// Example HTML structure for the switch component
// <div class="form-check form-switch">
//     <input class="form-check-input switch-input" type="checkbox" id="flexSwitchCheckDefault">
//     <label class="form-check-label" for="flexSwitchCheckDefault">Toggle this switch element</label>
// </div>