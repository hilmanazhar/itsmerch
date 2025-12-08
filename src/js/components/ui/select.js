// This file defines the Select component, allowing for dropdown selections using Bootstrap.

document.addEventListener('DOMContentLoaded', function () {
    const selectElement = document.getElementById('customSelect');

    selectElement.addEventListener('change', function () {
        const selectedValue = selectElement.value;
        console.log('Selected value:', selectedValue);
    });
});

// Example HTML structure for the select component
// <div class="form-group">
//     <label for="customSelect">Select an option</label>
//     <select class="form-control" id="customSelect">
//         <option value="option1">Option 1</option>
//         <option value="option2">Option 2</option>
//         <option value="option3">Option 3</option>
//     </select>
// </div>