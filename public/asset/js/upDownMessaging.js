// public/asset/js/upDownMessaging.js

document.addEventListener('DOMContentLoaded', function() {
    const messagesContainer = document.querySelector('.messages');
    const scrollUpBtn = document.getElementById('scroll-up-btn');
    const scrollDownBtn = document.getElementById('scroll-down-btn');

    const scrollAmount = 200;

    scrollUpBtn.addEventListener('click', function() {
        messagesContainer.scrollBy({
            top: -scrollAmount,
            behavior: 'smooth'
        });
    });

    scrollDownBtn.addEventListener('click', function() {
        messagesContainer.scrollBy({
            top: scrollAmount,
            behavior: 'smooth'
        });
    });
});
