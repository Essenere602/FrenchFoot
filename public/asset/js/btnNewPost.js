// public/asset/js/btnNewPost.js

document.addEventListener('DOMContentLoaded', function() {
    // Gestion de l'affichage du formulaire de création de post
    const toggleButton = document.getElementById('show-form-btn');
    const formContainer = document.getElementById('post-form-container');

    if (toggleButton && formContainer) {
        toggleButton.addEventListener('click', function() {
            if (formContainer.style.display === 'none' || formContainer.style.display === '') {
                formContainer.style.display = 'block';
                toggleButton.textContent = '- Annuler';
            } else {
                formContainer.style.display = 'none';
                toggleButton.textContent = '+ Nouveau Post';
            }
        });
    }
});
