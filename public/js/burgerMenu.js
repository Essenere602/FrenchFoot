document.addEventListener('DOMContentLoaded', function() {
    let burger = document.querySelector('.burger');
    let nav = document.querySelector('.nav-links');
    
    burger.addEventListener('click', function() {
        nav.classList.toggle('nav-active');
    });
});