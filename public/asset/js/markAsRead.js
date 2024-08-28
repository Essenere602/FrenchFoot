// Marquer les messages comme lus lors du clic sur une conversation
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.conversation-link').forEach(function(link) {
        link.addEventListener('click', function(event) {
            const conversationId = this.dataset.id;

            fetch('/messages/mark-as-read/' + conversationId, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken // Assurez-vous d'inclure un token CSRF valide
                }
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.querySelector('.notification-dot').style.display = 'none';

                    if (document.querySelectorAll('.notification-dot').length === 0) {
                        document.querySelector('.navbar .notification-dot').style.display = 'none';
                    }
                }
            }).catch(error => console.error('Erreur lors de la mise à jour des messages:', error));
        });
    });
});
