/**
 * Enhanced Debate Editor with Emoji, Stickers, and Hashtag Support
 */

class DebateEditor {
    constructor(textareaId = 'topic_content', emojiPickerId = 'emoji-picker', stickerPickerId = null, hashtagHelperId = 'hashtag-helper') {
        this.textarea = document.getElementById(textareaId);
        this.emojiPicker = document.getElementById(emojiPickerId);
        this.stickerPicker = stickerPickerId ? document.getElementById(stickerPickerId) : null;
        this.hashtagHelper = document.getElementById(hashtagHelperId);
        this.textareaId = textareaId;
        this.emojiPickerId = emojiPickerId;
        this.stickerPickerId = stickerPickerId;
        this.hashtagHelperId = hashtagHelperId;
        
        this.counter = document.getElementById('debate-counter');
        
        this.emojis = {
            smileys: ['😊', '😂', '🤣', '😍', '🥰', '😘', '😉', '😋', '😎', '🤗', '🤔', '😐', '😑', '🙄', '😏', '😣', '😥', '😮', '🤐', '😯', '😪', '😫', '🥱', '😴', '😌', '😛', '😜', '🤪', '😝', '🤑', '🤗'],
            people: ['👋', '🤚', '🖐️', '✋', '🖖', '👌', '🤌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👍', '👎', '👊', '✊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏'],
            nature: ['🌟', '⭐', '🌙', '☀️', '🌈', '⚡', '🔥', '💧', '🌊', '🌍', '🌎', '🌏', '🌳', '🌲', '🌴', '🌵', '🌷', '🌸', '🌺', '🌻', '🌹', '🥀', '🌿', '☘️', '🍀', '🍃', '🌾', '🌱', '🌰', '🎋', '🎍'],
            objects: ['⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎱', '🪀', '🏓', '🏸', '🏒', '🏑', '🥍', '🏏', '🪃', '🥅', '⛳', '🪁', '🏹', '🎣', '🤿', '🥊', '🥋', '🎽', '🛹', '🛼', '🛷', '⛸️'],
            symbols: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮️', '✝️', '☪️', '🕉️', '☸️', '✡️', '🔯', '🕎', '☯️', '☦️', '🛐', '⛎'],
            flags: ['🇦🇷', '🏳️', '🏴', '🏁', '🚩', '🏳️‍🌈', '🏳️‍⚧️', '🇺🇳']
        };

        this.stickers = {
            reactions: [
                { name: 'thumbs_up', emoji: '👍', text: '👍' },
                { name: 'heart_eyes', emoji: '😍', text: '😍' },
                { name: 'fire', emoji: '🔥', text: '🔥' },
                { name: 'clap', emoji: '👏', text: '👏' },
                { name: 'thinking', emoji: '🤔', text: '🤔' },
                { name: 'shocked', emoji: '😱', text: '😱' }
            ],
            argentina: [
                { name: 'argentina_flag', emoji: '🇦🇷', text: '🇦🇷' },
                { name: 'mate', emoji: '🧉', text: '🧉' },
                { name: 'tango', emoji: '💃', text: '💃' },
                { name: 'football', emoji: '⚽', text: '⚽' },
                { name: 'asado', emoji: '🥩', text: '🥩' },
                { name: 'empanada', emoji: '🥟', text: '🥟' }
            ],
            debate: [
                { name: 'megaphone', emoji: '📢', text: '📢' },
                { name: 'speech', emoji: '💬', text: '💬' },
                { name: 'lightbulb', emoji: '💡', text: '💡' },
                { name: 'question', emoji: '❓', text: '❓' },
                { name: 'exclamation', emoji: '❗', text: '❗' },
                { name: 'scales', emoji: '⚖️', text: '⚖️' }
            ]
        };

        this.popularHashtags = [
            '#Tucumán', '#Política', '#Economía', '#Cultura', '#Universidad', 
            '#Deportes', '#Sociedad', '#Tecnología', '#Ambiente', '#Salud',
            '#Educación', '#Trabajo', '#Juventud', '#Familia', '#Arte'
        ];

        this.init();
    }

    init() {
        if (!this.textarea) return;
        
        this.setupEventListeners();
        this.setupCharacterCounter();
        this.setupHashtagDetection();
        this.populateEmojiPicker();
        this.populateHashtagHelper();
        
        console.log('DebateEditor initialized');
    }

    setupEventListeners() {
        // Emoji picker toggle - support both regular and modal versions
        const emojiBtn = document.getElementById('emoji-picker-btn') || document.getElementById('emoji-picker-btn-modal');
        if (emojiBtn) {
            emojiBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.togglePicker('emoji');
            });
        }

        // Sticker picker removed - no longer needed

        // Hashtag helper toggle - support both regular and modal versions
        const hashtagBtn = document.getElementById('hashtag-helper-btn') || document.getElementById('hashtag-helper-btn-modal');
        if (hashtagBtn) {
            hashtagBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.togglePicker('hashtag');
            });
        }

        // Close pickers when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.debate-editor-container')) {
                this.closeAllPickers();
            }
        });

        // Emoji category buttons
        document.querySelectorAll('.emoji-cat-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchEmojiCategory(btn.dataset.category);
                document.querySelectorAll('.emoji-cat-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });

        // Sticker category buttons
        document.querySelectorAll('.sticker-cat-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchStickerCategory(btn.dataset.category);
                document.querySelectorAll('.sticker-cat-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });
    }

    setupCharacterCounter() {
        this.textarea.addEventListener('input', () => {
            const length = this.textarea.value.length;
            this.counter.textContent = `${length} / 400`;
            
            if (length > 350) {
                this.counter.style.color = 'var(--danger-color)';
            } else if (length > 300) {
                this.counter.style.color = 'var(--warning-color)';
            } else {
                this.counter.style.color = 'var(--text-muted)';
            }
        });
    }

    setupHashtagDetection() {
        this.textarea.addEventListener('input', (e) => {
            const text = e.target.value;
            const cursorPos = e.target.selectionStart;
            
            // Detect if user is typing a hashtag
            const beforeCursor = text.substring(0, cursorPos);
            const hashtagMatch = beforeCursor.match(/#(\w*)$/);
            
            if (hashtagMatch) {
                const partial = hashtagMatch[1];
                this.showHashtagSuggestions(partial);
            } else {
                this.hideHashtagSuggestions();
            }
        });
    }

    togglePicker(type) {
        this.closeAllPickers();
        
        const picker = type === 'emoji' ? this.emojiPicker : this.hashtagHelper;
        
        if (picker && picker.style.display === 'none') {
            picker.style.display = 'block';
        } else if (picker) {
            picker.style.display = 'none';
        }
    }

    closeAllPickers() {
        if (this.emojiPicker) this.emojiPicker.style.display = 'none';
        if (this.stickerPicker) this.stickerPicker.style.display = 'none';
        if (this.hashtagHelper) this.hashtagHelper.style.display = 'none';
    }

    populateEmojiPicker() {
        this.switchEmojiCategory('smileys');
    }

    switchEmojiCategory(category) {
        const grid = document.getElementById('emoji-grid') || document.getElementById('emoji-grid-modal');
        if (!grid) return;
        
        grid.innerHTML = '';
        
        this.emojis[category].forEach(emoji => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'emoji-btn';
            btn.textContent = emoji;
            btn.title = emoji;
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.insertAtCursor(emoji);
                this.closeAllPickers();
            });
            grid.appendChild(btn);
        });
    }

    populateStickerPicker() {
        this.switchStickerCategory('reactions');
    }

    switchStickerCategory(category) {
        const grid = document.getElementById('sticker-grid');
        grid.innerHTML = '';
        
        this.stickers[category].forEach(sticker => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'sticker-btn';
            btn.innerHTML = `<span class="sticker-emoji">${sticker.emoji}</span><span class="sticker-name">${sticker.name}</span>`;
            btn.title = sticker.name;
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.insertAtCursor(sticker.text);
                this.closeAllPickers();
            });
            grid.appendChild(btn);
        });
    }

    populateHashtagHelper() {
        const container = document.getElementById('hashtag-suggestions');
        container.innerHTML = '<div class="hashtag-title">Hashtags Populares:</div>';
        
        this.popularHashtags.forEach(hashtag => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'hashtag-suggestion';
            btn.textContent = hashtag;
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.insertAtCursor(hashtag + ' ');
                this.closeAllPickers();
            });
            container.appendChild(btn);
        });
    }

    showHashtagSuggestions(partial) {
        const container = document.getElementById('hashtag-suggestions');
        const filtered = this.popularHashtags.filter(tag => 
            tag.toLowerCase().includes(partial.toLowerCase())
        );
        
        if (filtered.length > 0) {
            container.innerHTML = '<div class="hashtag-title">Sugerencias:</div>';
            filtered.forEach(hashtag => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'hashtag-suggestion';
                btn.textContent = hashtag;
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.replaceHashtagAtCursor(hashtag);
                    this.hideHashtagSuggestions();
                });
                container.appendChild(btn);
            });
            this.hashtagHelper.style.display = 'block';
        }
    }

    hideHashtagSuggestions() {
        this.hashtagHelper.style.display = 'none';
    }

    insertAtCursor(text) {
        const start = this.textarea.selectionStart;
        const end = this.textarea.selectionEnd;
        const value = this.textarea.value;
        
        this.textarea.value = value.substring(0, start) + text + value.substring(end);
        this.textarea.selectionStart = this.textarea.selectionEnd = start + text.length;
        this.textarea.focus();
        
        // Trigger input event for character counter
        this.textarea.dispatchEvent(new Event('input'));
    }

    replaceHashtagAtCursor(hashtag) {
        const text = this.textarea.value;
        const cursorPos = this.textarea.selectionStart;
        const beforeCursor = text.substring(0, cursorPos);
        const afterCursor = text.substring(cursorPos);
        
        // Find the start of the current hashtag
        const hashtagStart = beforeCursor.lastIndexOf('#');
        
        if (hashtagStart !== -1) {
            const newText = text.substring(0, hashtagStart) + hashtag + ' ' + afterCursor;
            this.textarea.value = newText;
            this.textarea.selectionStart = this.textarea.selectionEnd = hashtagStart + hashtag.length + 1;
            this.textarea.focus();
            
            // Trigger input event for character counter
            this.textarea.dispatchEvent(new Event('input'));
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, checking for DebateEditor');
    if (typeof DebateEditor !== 'undefined') {
        console.log('DebateEditor found, initializing...');
        new DebateEditor();
    } else {
        console.log('DebateEditor not found');
    }
});

// Also try with jQuery ready (fallback)
jQuery(document).ready(function($) {
    console.log('jQuery ready, checking for DebateEditor');
    if (typeof DebateEditor !== 'undefined') {
        console.log('DebateEditor found via jQuery, initializing...');
        new DebateEditor();
    } else {
        console.log('DebateEditor not found via jQuery');
    }
});
