// Global State
let conversationHistory = [];
let currentSessionId = null;
let sessions = []; // Loaded from localStorage

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// ============================================================
// SESSION MANAGEMENT (LocalStorage)
// ============================================================
// ... (keep session management functions as they are)
// [REDACTED FOR BREVITY - TOOLS WILL HANDLE THE MERGE]

// ============================================================
// UTILITIES
// ============================================================
// ... (keep utilities)

// ============================================================
// CHATBOT API MODULE
// ============================================================
// ... (keep API logic)

// ============================================================
// UI COMPONENTS
// ============================================================
// ... (keep UI components)

// ============================================================
// MAIN APPLICATION CONTROLLER
// ============================================================

const chatContainer = document.getElementById('chat-container');
const chatInput = document.getElementById('chat-input');
const sendBtn = document.getElementById('send-btn');
const welcomeScreen = document.getElementById('welcome-screen');
const clearBtn = document.getElementById('clear-btn');
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebar-toggle');
const sidebarOverlay = document.getElementById('sidebar-overlay');
const newChatBtn = document.getElementById('new-chat-btn');

let isProcessing = false;

// ... (keep handleSend function)

function updateSendButton() { sendBtn.disabled = !chatInput.value.trim().length || isProcessing; }

function init() {
    // Only essential UI initializations
    loadSessions();
    if (sessions.length > 0) switchSession(sessions[0].id); else createNewSession();

    sendBtn.addEventListener('click', handleSend);
    chatInput.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); handleSend(); } });
    chatInput.addEventListener('input', () => { autoResizeTextarea(chatInput); updateSendButton(); });
    
    document.querySelectorAll('.suggestion-card').forEach(card => {
        card.addEventListener('click', () => { if (isProcessing) return; chatInput.value = card.dataset.prompt; handleSend(); });
    });

    sidebarToggle?.addEventListener('click', () => {
        sidebar?.classList.toggle('active');
        sidebarOverlay?.classList.toggle('active');
    });

    sidebarOverlay?.addEventListener('click', () => {
        sidebar?.classList.remove('active');
        sidebarOverlay?.classList.remove('active');
    });

    newChatBtn?.addEventListener('click', createNewSession);
    clearBtn?.addEventListener('click', () => { createNewSession(); });

    chatInput.focus(); 
    updateSendButton();
}

document.addEventListener('DOMContentLoaded', init);
