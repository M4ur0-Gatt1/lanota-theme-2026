/**
 * Debate Likes Functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Handle like button clicks
    document.addEventListener('click', function(e) {
        if (e.target.closest('.like-btn')) {
            e.preventDefault();
            
            const button = e.target.closest('.like-btn');
            const debateId = button.getAttribute('data-debate-id');
            
            if (!debateId) return;
            
            // Disable button during request
            button.disabled = true;
            
            // Send AJAX request
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'toggle_debate_like',
                    debate_id: debateId,
                    nonce: typeof debateEditor !== 'undefined' ? debateEditor.nonce : ''
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update button appearance
                    const icon = button.querySelector('i');
                    const count = button.querySelector('span');
                    
                    if (data.data.user_liked) {
                        button.classList.add('liked');
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                    } else {
                        button.classList.remove('liked');
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                    }
                    
                    count.textContent = data.data.count;
                } else {
                    console.error('Error:', data.data);
                    alert('Error al procesar el me gusta. Intenta nuevamente.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión. Intenta nuevamente.');
            })
            .finally(() => {
                button.disabled = false;
            });
        }
    });
    
    // Initialize like button states
    initializeLikeButtons();
});

function initializeLikeButtons() {
    const likeButtons = document.querySelectorAll('.like-btn');
    
    likeButtons.forEach(button => {
        const debateId = button.getAttribute('data-debate-id');
        
        // Check if user has liked this debate
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'check_debate_like',
                debate_id: debateId,
                nonce: typeof debateEditor !== 'undefined' ? debateEditor.nonce : ''
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.user_liked) {
                button.classList.add('liked');
                const icon = button.querySelector('i');
                icon.classList.remove('far');
                icon.classList.add('fas');
            }
        })
        .catch(error => {
            console.error('Error checking like status:', error);
        });
    });
}
