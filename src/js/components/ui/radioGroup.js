// This file defines the Radio Group component, allowing for single selections using Bootstrap styles.

document.addEventListener("DOMContentLoaded", function () {
    const radioGroup = document.querySelector('.radio-group');

    radioGroup.addEventListener('change', function (event) {
        const selectedValue = event.target.value;
        console.log('Selected Radio Value:', selectedValue);
    });
});

// Example HTML structure for the Radio Group component
// <div class="radio-group">
//     <div class="form-check">
//         <input class="form-check-input" type="radio" name="options" id="option1" value="Option 1">
//         <label class="form-check-label" for="option1">
//             Option 1
//         </label>
//     </div>
//     <div class="form-check">
//         <input class="form-check-input" type="radio" name="options" id="option2" value="Option 2">
//         <label class="form-check-label" for="option2">
//             Option 2
//         </label>
//     </div>
// </div>