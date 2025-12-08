// This file handles command-related functionality using Bootstrap components.

document.addEventListener('DOMContentLoaded', function () {
    const commandButton = document.getElementById('commandButton');
    const commandOutput = document.getElementById('commandOutput');

    commandButton.addEventListener('click', function () {
        const command = document.getElementById('commandInput').value;
        commandOutput.innerHTML = `<div class="alert alert-info" role="alert">You entered: ${command}</div>`;
    });
});