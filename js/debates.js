// ===== EN DEBATE SYSTEM JAVASCRIPT =====

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize debates functionality
    initDebatesSystem();
    
    function initDebatesSystem() {
        // Search functionality
        initDebateSearch();
        
        // Sorting functionality
        initDebateSorting();
        
        // Tag filtering
        initTagFiltering();
        
        // Vote functionality
        initVoteSystem();
        
        // New debate modal
        initNewDebateModal();
        
        // Load more debates
        initLoadMoreDebates();
        
        // Share functionality
        initShareSystem();
        
        // Create debate from news
        initCreateFromNews();
        
        // Child debate creation
        initChildDebateCreation();
        
        // Live debate functionality
        initLiveDebate();
        
        // Scroll position memory
        initScrollMemory();
    }
    
    // Search functionality
    function initDebateSearch() {
        const searchInput = document.getElementById('debate-search');
        if (!searchInput) return;
        
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterDebates();
            }, 300);
        });
    }
    
    // Sorting functionality
    function initDebateSorting() {
        const sortSelect = document.getElementById('debate-sort');
        if (!sortSelect) return;
        
        sortSelect.addEventListener('change', function() {
            filterDebates();
        });
    }
    
    // Tag filtering
    function initTagFiltering() {
        const tagFilters = document.querySelectorAll('.tag-filter');
        
        tagFilters.forEach(filter => {
            filter.addEventListener('click', function() {
                // Remove active class from all filters
                tagFilters.forEach(f => f.classList.remove('active'));
                
                // Add active class to clicked filter
                this.classList.add('active');
                
                // Filter debates
                filterDebates();
            });
        });
    }
    
    // Filter debates function
    function filterDebates() {
        const search = document.getElementById('debate-search')?.value || '';
        const sort = document.getElementById('debate-sort')?.value || 'recent';
        const activeTag = document.querySelector('.tag-filter.active')?.dataset.tag || 'all';
        
        // Reset page counter
        const loadMoreBtn = document.getElementById('load-more-debates');
        if (loadMoreBtn) {
            loadMoreBtn.dataset.page = '1';
        }
        
        // Make AJAX request
        fetch(ajax_object.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'load_more_debates',
                page: 1,
                search: search,
                sort: sort,
                tag: activeTag,
                nonce: ajax_object.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const feedContainer = document.getElementById('debates-feed');
                if (feedContainer) {
                    feedContainer.innerHTML = data.data.html;
                    
                    // Update load more button
                    if (loadMoreBtn) {
                        if (data.data.has_more) {
                            loadMoreBtn.style.display = 'block';
                            loadMoreBtn.dataset.page = '2';
                        } else {
                            loadMoreBtn.style.display = 'none';
                        }
                    }
                    
                    // Reinitialize vote buttons for new content
                    initVoteButtons();
                }
            }
        })
        .catch(error => {
            console.error('Error filtering debates:', error);
        });
    }
    
    // Vote system
    function initVoteSystem() {
        initVoteButtons();
    }
    
    function initVoteButtons() {
        const voteButtons = document.querySelectorAll('.vote-btn');
        
        voteButtons.forEach(button => {
            // Remove existing listeners to prevent duplicates
            button.removeEventListener('click', handleVote);
            button.addEventListener('click', handleVote);
        });
    }
    
    function handleVote(e) {
        e.preventDefault();
        
        const button = this;
        const debateId = button.dataset.debateId;
        
        if (!debateId) return;
        
        // Disable button during request
        button.disabled = true;
        
        fetch(ajax_object.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'vote_debate',
                post_id: debateId,
                nonce: ajax_object.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update vote count
                const voteCount = button.querySelector('.vote-count');
                if (voteCount) {
                    voteCount.textContent = data.data.votes;
                }
                
                // Update button state
                if (data.data.voted) {
                    button.classList.add('voted');
                } else {
                    button.classList.remove('voted');
                }
                
                // Show notification
                showNotification(data.data.voted ? 'Voto agregado' : 'Voto removido', 'success');
            } else {
                showNotification(data.data.message || 'Error al votar', 'error');
            }
        })
        .catch(error => {
            console.error('Error voting:', error);
            showNotification('Error al procesar el voto', 'error');
        })
        .finally(() => {
            button.disabled = false;
        });
    }
    
    // New debate modal
    function initNewDebateModal() {
        const newDebateBtn = document.getElementById('new-debate-btn');
        const submitBtn = document.getElementById('submit-debate');
        
        if (newDebateBtn) {
            newDebateBtn.addEventListener('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('newDebateModal'));
                modal.show();
            });
        }
        
        if (submitBtn) {
            submitBtn.addEventListener('click', handleNewDebateSubmit);
        }
    }
    
    function handleNewDebateSubmit() {
        const title = document.getElementById('debate-title')?.value;
        const content = document.getElementById('debate-content')?.value;
        const tagsSelect = document.getElementById('debate-tags');
        const tags = tagsSelect ? Array.from(tagsSelect.selectedOptions).map(option => option.value) : [];
        
        if (!title || !content) {
            showNotification('Título y contenido son obligatorios', 'error');
            return;
        }
        
        const submitBtn = document.getElementById('submit-debate');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Publicando...';
        
        fetch(ajax_object.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'submit_new_debate',
                title: title,
                content: content,
                tags: tags,
                nonce: ajax_object.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.data.message, 'success');
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('newDebateModal'));
                modal.hide();
                
                // Clear form
                document.getElementById('new-debate-form').reset();
                
                // Redirect to new debate
                setTimeout(() => {
                    window.location.href = data.data.permalink;
                }, 1000);
            } else {
                showNotification(data.data.message || 'Error al crear el debate', 'error');
            }
        })
        .catch(error => {
            console.error('Error creating debate:', error);
            showNotification('Error al crear el debate', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Publicar Debate';
        });
    }
    
    // Load more debates
    function initLoadMoreDebates() {
        const loadMoreBtn = document.getElementById('load-more-debates');
        if (!loadMoreBtn) return;
        
        loadMoreBtn.addEventListener('click', function() {
            const page = parseInt(this.dataset.page) || 2;
            const maxPages = parseInt(this.dataset.max) || 1;
            
            if (page > maxPages) return;
            
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Cargando...';
            
            const search = document.getElementById('debate-search')?.value || '';
            const sort = document.getElementById('debate-sort')?.value || 'recent';
            const activeTag = document.querySelector('.tag-filter.active')?.dataset.tag || 'all';
            
            fetch(ajax_object.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'load_more_debates',
                    page: page,
                    search: search,
                    sort: sort,
                    tag: activeTag,
                    nonce: ajax_object.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const feedContainer = document.getElementById('debates-feed');
                    if (feedContainer) {
                        feedContainer.insertAdjacentHTML('beforeend', data.data.html);
                        
                        // Update button state
                        if (data.data.has_more) {
                            this.dataset.page = page + 1;
                        } else {
                            this.style.display = 'none';
                        }
                        
                        // Reinitialize vote buttons for new content
                        initVoteButtons();
                    }
                }
            })
            .catch(error => {
                console.error('Error loading more debates:', error);
                showNotification('Error al cargar más debates', 'error');
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-chevron-down me-2"></i> Cargar Más Debates';
            });
        });
    }
    
    // Share system
    function initShareSystem() {
        document.addEventListener('click', function(e) {
            if (e.target.matches('.share-btn') || e.target.closest('.share-btn')) {
                e.preventDefault();
                
                const button = e.target.closest('.share-btn');
                const url = button.dataset.url || window.location.href;
                
                if (navigator.share) {
                    navigator.share({
                        title: document.title,
                        url: url
                    });
                } else {
                    // Fallback to clipboard
                    navigator.clipboard.writeText(url).then(() => {
                        showNotification('Enlace copiado al portapapeles', 'success');
                    }).catch(() => {
                        // Manual fallback
                        const textArea = document.createElement('textarea');
                        textArea.value = url;
                        document.body.appendChild(textArea);
                        textArea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textArea);
                        showNotification('Enlace copiado al portapapeles', 'success');
                    });
                }
            }
        });
    }
    
    // Create debate from news
    function initCreateFromNews() {
        document.addEventListener('click', function(e) {
            if (e.target.matches('.open-debate-btn') || e.target.closest('.open-debate-btn')) {
                e.preventDefault();
                
                const button = e.target.closest('.open-debate-btn');
                const postId = button.dataset.postId;
                
                if (!postId) return;
                
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Procesando...';
                
                fetch(ajax_object.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'create_debate_from_news',
                        post_id: postId,
                        nonce: ajax_object.nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.data.exists) {
                            showNotification(data.data.message, 'info');
                        } else {
                            showNotification(data.data.message, 'success');
                        }
                        
                        // Redirect to debate
                        setTimeout(() => {
                            window.location.href = data.data.permalink;
                        }, 1000);
                    } else {
                        showNotification(data.data.message || 'Error al crear el debate', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error creating debate from news:', error);
                    showNotification('Error al crear el debate', 'error');
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-comments me-1"></i> Abrir Debate';
                });
            }
        });
    }
    
    // Child debate creation
    function initChildDebateCreation() {
        const createChildBtn = document.getElementById('create-child-debate');
        if (!createChildBtn) return;
        
        createChildBtn.addEventListener('click', function() {
            const parentId = this.dataset.parentId;
            
            // Create and show modal for child debate
            const modalHtml = `
                <div class="modal fade" id="childDebateModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-code-branch text-warning me-2"></i>
                                    Crear Debate Derivado
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Este debate estará vinculado al debate original.
                                </div>
                                <form id="child-debate-form">
                                    <div class="mb-3">
                                        <label for="child-debate-title" class="form-label">Título del Debate Derivado</label>
                                        <input type="text" class="form-control" id="child-debate-title" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="child-debate-content" class="form-label">Contenido</label>
                                        <textarea class="form-control" id="child-debate-content" rows="6" required></textarea>
                                    </div>
                                    <input type="hidden" id="parent-debate-id" value="${parentId}">
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-warning" id="submit-child-debate">
                                    <i class="fas fa-code-branch me-1"></i>
                                    Crear Debate Derivado
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('childDebateModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add modal to DOM
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('childDebateModal'));
            modal.show();
            
            // Handle submit
            document.getElementById('submit-child-debate').addEventListener('click', function() {
                const title = document.getElementById('child-debate-title').value;
                const content = document.getElementById('child-debate-content').value;
                const parentId = document.getElementById('parent-debate-id').value;
                
                if (!title || !content) {
                    showNotification('Título y contenido son obligatorios', 'error');
                    return;
                }
                
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Creando...';
                
                fetch(ajax_object.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'submit_new_debate',
                        title: title,
                        content: content,
                        parent_id: parentId,
                        nonce: ajax_object.nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.data.message, 'success');
                        modal.hide();
                        
                        // Redirect to new debate
                        setTimeout(() => {
                            window.location.href = data.data.permalink;
                        }, 1000);
                    } else {
                        showNotification(data.data.message || 'Error al crear el debate', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error creating child debate:', error);
                    showNotification('Error al crear el debate derivado', 'error');
                })
                .finally(() => {
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-code-branch me-1"></i> Crear Debate Derivado';
                });
            });
        });
    }
    
    // Live debate functionality
    function initLiveDebate() {
        const liveSection = document.getElementById('live-debate');
        if (!liveSection) return;
        
        const messagesContainer = document.getElementById('live-chat-messages');
        const messageInput = document.getElementById('live-message-input');
        const sendButton = document.getElementById('send-live-message');
        
        if (sendButton) {
            sendButton.addEventListener('click', sendLiveMessage);
        }
        
        if (messageInput) {
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendLiveMessage();
                }
            });
        }
        
        function sendLiveMessage() {
            const message = messageInput.value.trim();
            if (!message) return;
            
            // Add message to chat (simulate real-time)
            const messageElement = document.createElement('div');
            messageElement.className = 'chat-message';
            messageElement.innerHTML = `
                <strong>Tú:</strong> ${message}
                <small class="text-muted ms-2">${new Date().toLocaleTimeString()}</small>
            `;
            
            messagesContainer.appendChild(messageElement);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            
            messageInput.value = '';
            
            // Here you would implement WebSocket or polling for real-time updates
        }
    }
    
    // Scroll position memory
    function initScrollMemory() {
        // Save scroll position when leaving page
        window.addEventListener('beforeunload', function() {
            if (window.location.pathname.includes('/en-debate')) {
                sessionStorage.setItem('debatesScrollPosition', window.scrollY);
            }
        });
        
        // Restore scroll position when returning
        window.addEventListener('load', function() {
            if (window.location.pathname.includes('/en-debate')) {
                const savedPosition = sessionStorage.getItem('debatesScrollPosition');
                if (savedPosition) {
                    window.scrollTo(0, parseInt(savedPosition));
                    sessionStorage.removeItem('debatesScrollPosition');
                }
            }
        });
    }
    
    // Notification system
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} notification-toast`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
            ${message}
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideInRight 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    // Add CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
});
