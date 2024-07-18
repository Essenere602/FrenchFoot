document.getElementById('username-autocomplete').addEventListener('input', function() {
    let term = this.value;
    let resultsDiv = document.getElementById('autocomplete-results');
    
    if (term === '') {
        resultsDiv.innerHTML = '';
        return;
    }

    fetch('/user/autocomplete?term=' + term)
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