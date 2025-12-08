// src/js/components/ui/carousel.js

document.addEventListener("DOMContentLoaded", function () {
    const carouselItems = document.querySelectorAll('.carousel-item');
    let currentIndex = 0;

    function showNextSlide() {
        carouselItems[currentIndex].classList.remove('active');
        currentIndex = (currentIndex + 1) % carouselItems.length;
        carouselItems[currentIndex].classList.add('active');
    }

    function showPrevSlide() {
        carouselItems[currentIndex].classList.remove('active');
        currentIndex = (currentIndex - 1 + carouselItems.length) % carouselItems.length;
        carouselItems[currentIndex].classList.add('active');
    }

    document.querySelector('.carousel-control-next').addEventListener('click', showNextSlide);
    document.querySelector('.carousel-control-prev').addEventListener('click', showPrevSlide);

    setInterval(showNextSlide, 5000); // Change slide every 5 seconds
});