// This file defines the Input component, allowing for text entry using Bootstrap styles.

document.addEventListener("DOMContentLoaded", function () {
    const inputContainer = document.createElement("div");
    inputContainer.className = "mb-3";

    const label = document.createElement("label");
    label.className = "form-label";
    label.setAttribute("for", "inputField");
    label.textContent = "Input Label"; // Change this to your desired label text

    const inputField = document.createElement("input");
    inputField.type = "text";
    inputField.className = "form-control";
    inputField.id = "inputField";
    inputField.placeholder = "Enter text here"; // Change this to your desired placeholder

    inputContainer.appendChild(label);
    inputContainer.appendChild(inputField);

    // Append the input container to a specific part of your application
    document.body.appendChild(inputContainer); // Adjust this to append to the correct parent element
});