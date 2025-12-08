const Hero = () => {
    return `
        <div class="hero bg-primary text-white text-center py-5">
            <div class="container">
                <h1 class="display-4">Welcome to ITS Merchandise</h1>
                <p class="lead">Your one-stop shop for all ITS branded merchandise.</p>
                <a href="#products" class="btn btn-light btn-lg">Shop Now</a>
            </div>
        </div>
    `;
};

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('root');
    root.innerHTML += Hero();
});