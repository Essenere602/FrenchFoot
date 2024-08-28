document.addEventListener('DOMContentLoaded', function() {
    const clubSearchInput = document.getElementById('clubSearch');
    const clubIdInput = document.getElementById('clubId');
    const clubSearchResults = document.getElementById('clubSearchResults');

    clubSearchInput.addEventListener('input', function() {
        const query = this.value.trim();

        if (query !== '') {
            fetch(`/profile/clubs/search?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    clubSearchResults.innerHTML = '';

                    data.forEach(club => {
                        const item = document.createElement('div');
                        item.classList.add('autocomplete-item');

                        const clubName = document.createElement('span');
                        clubName.textContent = club.name;

                        const logo = document.createElement('img');
                        logo.src = club.logo;
                        logo.classList.add('club-logo');

                        item.appendChild(logo);
                        item.appendChild(clubName);

                        item.addEventListener('click', function() {
                            clubSearchInput.value = club.name;
                            clubIdInput.value = club.id;
                            clubSearchResults.innerHTML = '';
                        });

                        clubSearchResults.appendChild(item);
                    });
                })
                .catch(error => {
                    console.error('Error fetching clubs:', error);
                });
        } else {
            clubSearchResults.innerHTML = '';
        }
    });

    clubSearchInput.addEventListener('keyup', function(event) {
        if (event.key === 'Backspace' || event.key === 'Delete') {
            if (clubSearchInput.value === '') {
                clubSearchResults.innerHTML = '';
            }
        }
    });

    clubSearchInput.addEventListener('blur', function() {
        if (clubSearchInput.value === '') {
            clubSearchResults.innerHTML = '';
        }
    });
});
