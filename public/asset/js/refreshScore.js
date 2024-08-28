// public/asset/js/refreshScore.js

document.addEventListener('DOMContentLoaded', function() {
    setInterval(function() {
        fetch(window.location.href)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                document.querySelector('.matches-section').innerHTML = doc.querySelector('.matches-section').innerHTML;
            });
    }, 60000); // Mise à jour toutes les 60 secondes
});
