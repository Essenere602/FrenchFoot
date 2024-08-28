// public/asset/js/btnNewTopic.js

document.addEventListener('DOMContentLoaded', function() {
    // Gestion de l'affichage du formulaire de création de sujet
    const toggleButton = document.getElementById('toggle-form');
    const formContainer = document.getElementById('topic-form-container');

    toggleButton.addEventListener('click', function() {
        if (formContainer.style.display === 'none' || formContainer.style.display === '') {
            formContainer.style.display = 'block';
            toggleButton.textContent = '- Annuler';
        } else {
            formContainer.style.display = 'none';
            toggleButton.textContent = '+ Créer un nouveau sujet';
        }
    }); 
});