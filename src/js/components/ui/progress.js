const Progress = () => {
    return `
        <div class="progress">
            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    `;
};

const updateProgress = (percentage) => {
    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        progressBar.style.width = \`\${percentage}%\`;
        progressBar.setAttribute('aria-valuenow', percentage);
    }
};

export { Progress, updateProgress };