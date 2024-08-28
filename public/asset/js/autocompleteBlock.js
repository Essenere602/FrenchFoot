document.addEventListener('DOMContentLoaded', function() {
    const blockInput = document.getElementById('block_username');
    if (blockInput) {
        blockInput.addEventListener('input', function() {
            let query = this.value;
            let results = document.getElementById('autocomplete-results');

            if (query === '') {
                results.innerHTML = '';
                return;
            }

            fetch('/profile/user-autocomplete?q=' + query)
                .then(response => response.json())
                .then(data => {
                    results.innerHTML = '';
                    data.forEach(user => {
                        let div = document.createElement('div');
                        div.textContent = user.username;
                        div.dataset.userId = user.id;
                        div.classList.add('autocomplete-item');
                        div.addEventListener('click', function() {
                            blockInput.value = this.textContent;
                            results.innerHTML = '';
                        });
                        results.appendChild(div);
                    });
                });
        });

        blockInput.addEventListener('blur', function() {
            if (this.value === '') {
                document.getElementById('autocomplete-results').innerHTML = '';
            }
        });
    }
});
