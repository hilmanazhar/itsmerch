// This file defines the Button component, providing various button styles and actions using Bootstrap.

function createButton(label, type = 'button', additionalClasses = '') {
    const button = document.createElement('button');
    button.type = type;
    button.className = `btn ${additionalClasses}`;
    button.textContent = label;
    return button;
}

// Example usage
const primaryButton = createButton('Click Me', 'button', 'btn-primary');
const secondaryButton = createButton('Cancel', 'button', 'btn-secondary');

// Exporting the button creation function for use in other components
export { createButton, primaryButton, secondaryButton };