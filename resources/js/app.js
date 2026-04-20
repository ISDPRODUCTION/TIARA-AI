// Global State
let conversationHistory = [];
let currentSessionId = null;
let sessions = [];
let isProcessing = false;

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// ============================================================
// API MODULE
// ============================================================

async function apiCall(url, options = {}) {
    try {
        const response = await fetch(url, options);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return await response.json();
    } catch (e) {
        console.error("API Call Failed:", e);
        return null;
    }
}

// ============================================================
// UTILITIES
// ============================================================

function scrollToBottom() {
    const container = document.getElementById('chat-container');
    if (container) container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
}

function autoResizeTextarea(textarea) {
    if (!textarea) return;
    textarea.style.height = 'auto';
    textarea.style.height = (textarea.scrollHeight) + 'px';
}

function formatMessage(text) {
    if (!text) return '';
    return text
        .replace(/\n/g, '<br>')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/`(.*?)`/g, '<code>$1</code>')
        .replace(/```([\s\S]*?)```/g, '<pre class="code-block">$1</pre>');
}

// ============================================================
// UI COMPONENTS
// ============================================================

function renderMessage(role, text, animate = false) {
    const container = document.getElementById('chat-container');
    const welcomeScreen = document.getElementById('welcome-screen');
    
    if (welcomeScreen) welcomeScreen.style.display = 'none';
    if (!container) return;

    const messageDiv = document.createElement('div');
    messageDiv.className = `chat-message chat-message--${role === 'user' ? 'user' : 'ai'}`;
    
    const avatar = role === 'user' ? 'U' : '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>';
    
    messageDiv.innerHTML = `
        <div class="message-avatar message-avatar--${role === 'user' ? 'user' : 'ai'}">${avatar}</div>
        <div class="message-content">
            <div class="message-bubble message-bubble--${role === 'user' ? 'user' : 'ai'}">
                ${animate ? '<span class="typing">...</span>' : formatMessage(text)}
            </div>
            <div class="message-timestamp">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
        </div>
    `;

    container.appendChild(messageDiv);
    scrollToBottom();

    if (animate) {
        const bubble = messageDiv.querySelector('.message-bubble');
        let i = 0;
        bubble.innerHTML = '';
        const interval = setInterval(() => {
            if (i < text.length) {
                bubble.innerHTML = formatMessage(text.substring(0, i + 1)) + '<span class="cursor"></span>';
                i++;
                container.scrollTop = container.scrollHeight;
            } else {
                bubble.innerHTML = formatMessage(text);
                clearInterval(interval);
            }
        }, 8);
    }
}

function renderSidebar() {
    const list = document.getElementById('history-list');
    if (!list) return;

    if (sessions.length === 0) {
        list.innerHTML = '<div class="history-empty">Belum ada riwayat</div>';
        return;
    }

    list.innerHTML = sessions.map(s => `
        <div class="history-item ${String(s.id) === String(currentSessionId) ? 'active' : ''}" data-id="${s.id}">
            <span class="session-title">${s.title}</span>
            <button class="delete-session-btn" data-id="${s.id}">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            </button>
        </div>
    `).join('');
}

// ============================================================
// LOGIC
// ============================================================

async function loadSessions() {
    const data = await apiCall('/api/sessions');
    if (data) {
        sessions = data;
        renderSidebar();
    }
}

async function switchSession(id) {
    if (isProcessing) return;
    isProcessing = true;
    currentSessionId = id;

    const container = document.getElementById('chat-container');
    const welcomeScreen = document.getElementById('welcome-screen');
    
    // Clear only messages
    container.querySelectorAll('.chat-message').forEach(m => m.remove());
    if (welcomeScreen) welcomeScreen.style.display = 'none';

    // Show inline loader
    const loader = document.createElement('div');
    loader.className = 'chat-message chat-message--ai';
    loader.innerHTML = '<div class="message-bubble message-bubble--ai">Memuat riwayat...</div>';
    container.appendChild(loader);

    const messages = await apiCall(`/api/sessions/${id}/messages`);
    loader.remove();
    isProcessing = false;

    if (messages && messages.length > 0) {
        conversationHistory = messages;
        if (welcomeScreen) welcomeScreen.style.display = 'none';
        messages.forEach(m => renderMessage(m.role, m.parts[0].text));
    } else {
        conversationHistory = [];
        if (welcomeScreen) welcomeScreen.style.display = 'flex';
    }
    
    renderSidebar();
}

function createNewSession() {
    currentSessionId = null;
    conversationHistory = [];
    const container = document.getElementById('chat-container');
    const welcomeScreen = document.getElementById('welcome-screen');
    
    container.querySelectorAll('.chat-message').forEach(m => m.remove());
    if (welcomeScreen) welcomeScreen.style.display = 'flex';
    document.getElementById('chat-input').value = '';
    renderSidebar();
}

async function handleSend() {
    const input = document.getElementById('chat-input');
    const btn = document.getElementById('send-btn');
    const text = input.value.trim();

    if (!text || isProcessing) return;

    isProcessing = true;
    btn.disabled = true;
    input.value = '';
    autoResizeTextarea(input);

    renderMessage('user', text);
    
    const response = await apiCall('/api/chat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ 
            message: text, 
            session_id: currentSessionId, 
            history: conversationHistory 
        })
    });

    if (response && response.reply) {
        if (!currentSessionId && response.session_id) {
            currentSessionId = response.session_id;
            await loadSessions();
        }
        
        renderMessage('model', response.reply, true);
        conversationHistory.push({ role: 'user', parts: [{ text }] });
        conversationHistory.push({ role: 'model', parts: [{ text: response.reply }] });
    } else {
        renderMessage('model', 'Maaf, terjadi kesalahan saat menghubungi AI.');
    }
    
    isProcessing = false;
    btn.disabled = false;
    input.focus();
}

// ============================================================
// INITIALIZATION
// ============================================================

function init() {
    const chatInput = document.getElementById('chat-input');
    const sendBtn = document.getElementById('send-btn');
    const historyList = document.getElementById('history-list');
    
    // Delegated Events for Sidebar
    historyList?.addEventListener('click', async (e) => {
        const item = e.target.closest('.history-item');
        if (!item) return;

        const sessionId = item.dataset.id;
        const deleteBtn = e.target.closest('.delete-session-btn');

        if (deleteBtn) {
            if (confirm('Hapus percakapan ini?')) {
                const result = await apiCall(`/api/sessions/${sessionId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                });
                if (result) {
                    await loadSessions();
                    if (String(currentSessionId) === String(sessionId)) createNewSession();
                }
            }
            return;
        }

        switchSession(sessionId);
    });

    // Other Buttons
    document.getElementById('new-chat-btn')?.addEventListener('click', createNewSession);
    document.getElementById('clear-btn')?.addEventListener('click', createNewSession);
    document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar?.classList.toggle('active');
        overlay?.classList.toggle('active');
    });

    document.getElementById('sidebar-overlay')?.addEventListener('click', () => {
        document.getElementById('sidebar')?.classList.remove('active');
        document.getElementById('sidebar-overlay')?.classList.remove('active');
    });

    sendBtn?.addEventListener('click', handleSend);
    chatInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSend();
        }
    });

    chatInput?.addEventListener('input', () => {
        autoResizeTextarea(chatInput);
        if (sendBtn) sendBtn.disabled = !chatInput.value.trim();
    });

    // Suggestions
    document.querySelectorAll('.suggestion-card').forEach(card => {
        card.addEventListener('click', () => {
            if (isProcessing) return;
            chatInput.value = card.dataset.prompt;
            autoResizeTextarea(chatInput);
            handleSend();
        });
    });

    // Initial Load
    loadSessions().then(() => {
        if (sessions.length > 0) switchSession(sessions[0].id);
    });
}

document.addEventListener('DOMContentLoaded', init);
