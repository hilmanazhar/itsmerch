// This file handles the Slider component, allowing for range selections using Bootstrap.

document.addEventListener('DOMContentLoaded', function () {
    const slider = document.getElementById('slider');
    const output = document.getElementById('sliderValue');

    // Initialize slider value
    output.innerHTML = slider.value;

    // Update the current slider value (each time you drag the slider handle)
    slider.oninput = function () {
        output.innerHTML = this.value;
    }
});

// Example HTML structure for the slider component
// <div class="slider-container">
//     <input type="range" class="form-range" id="slider" min="0" max="100" step="1">
//     <p>Value: <span id="sliderValue"></span></p>
// </div>