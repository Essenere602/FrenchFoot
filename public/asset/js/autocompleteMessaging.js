document.addEventListener('DOMContentLoaded', function() {
    const inputElement = document.getElementById('username-autocomplete');
    
    if (inputElement) {
        inputElement.addEventListener('input', function() {
            let term = this.value;
            let resultsDiv = document.getElementById('autocomplete-results');

            if (term === '') {
                resultsDiv.innerHTML = '';
                return;
            }

            fetch('/user/autocomplete?term=' + encodeURIComponent(term))
                .then(response => response.json())
                .then(data => {
                    resultsDiv.innerHTML = '';

                    data.forEach(user => {
                        let div = document.createElement('div');
                        div.textContent = user.username;
                        div.dataset.userId = user.id;

                        div.addEventListener('click', function() {
                            window.location.href = '/messages/new/' + user.username;
                        });

                        resultsDiv.appendChild(div);
                    });
                });
        });

        // Fermer les résultats d'autocomplétion lorsque l'utilisateur clique à l'extérieur
        document.addEventListener('click', function(event) {
            let resultsDiv = document.getElementById('autocomplete-results');
            if (!resultsDiv.contains(event.target) && event.target !== inputElement) {
                resultsDiv.innerHTML = '';
            }
        });
    }
});
