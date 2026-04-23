<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Tiara AI') }} — Your Personal Student Assistant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'cyan-neon': '#00f5ff',
                        'violet-neon': '#8b5cf6',
                        'deep': '#050510',
                        'surface': '#0a0a1a',
                    },
                    fontFamily: { orbitron: ['Orbitron', 'sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(15,15,45,0.5); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
        .neon-text { background: linear-gradient(135deg,#00f5ff,#8b5cf6); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
        .neon-border { border-color: rgba(0,245,255,0.15); }
        .neon-glow { box-shadow: 0 0 20px rgba(0,245,255,0.25), 0 0 60px rgba(0,245,255,0.08); }
        .sidebar { transition: transform .3s ease; }
        .sidebar.hidden-sidebar { transform: translateX(-100%); position: absolute; }
        .chat-scroll::-webkit-scrollbar { width: 4px; }
        .chat-scroll::-webkit-scrollbar-thumb { background: rgba(0,245,255,0.2); border-radius: 99px; }
        .grid-bg { background-image: linear-gradient(rgba(0,245,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(0,245,255,.025) 1px,transparent 1px); background-size: 80px 80px; }
        .code-block { background: rgba(0,0,0,.4); border: 1px solid rgba(0,245,255,.12); border-radius: 8px; padding: 12px 16px; margin: 8px 0; overflow-x: auto; font-family: 'JetBrains Mono', monospace; font-size: .82rem; }
        code { font-family:'JetBrains Mono',monospace; font-size:.82rem; background:rgba(0,245,255,.08); padding:2px 6px; border-radius:4px; color:#00f5ff; }
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
        @keyframes pulse-neon { 0%,100%{box-shadow:0 0 15px rgba(0,245,255,.3)} 50%{box-shadow:0 0 30px rgba(0,245,255,.6)} }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
        @keyframes slide-up { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        @keyframes bounce-dot { 0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-8px)} }
        @keyframes cursor-blink { 0%,100%{opacity:1} 50%{opacity:0} }
        .animate-float { animation: float 4s ease-in-out infinite; }
        .animate-pulse-neon { animation: pulse-neon 3s ease-in-out infinite; }
        .animate-blink { animation: blink 2s ease-in-out infinite; }
        .animate-slide-up { animation: slide-up .6s ease-out; }
        .typing-dot { width:7px;height:7px;border-radius:50%;background:#00f5ff;animation:bounce-dot 1.4s ease-in-out infinite; }
        .typing-dot:nth-child(2){animation-delay:.15s;background:#8b5cf6}
        .typing-dot:nth-child(3){animation-delay:.3s}
        .cursor-blink { display:inline-block;width:2px;height:1em;background:#00f5ff;margin-left:2px;vertical-align:middle;animation:cursor-blink .8s infinite; }
        .modal-overlay { opacity:0;visibility:hidden;transition:all .3s; }
        .modal-overlay.active { opacity:1;visibility:visible; }
        .overlay { opacity:0;visibility:hidden;transition:all .3s; }
        .overlay.active { opacity:1;visibility:visible; }
        .header-line::after { content:'';position:absolute;bottom:0;left:0;width:100%;height:1px;background:linear-gradient(90deg,#00f5ff,#8b5cf6,#f472b6,#00f5ff);background-size:200% 100%;animation:borderFlow 4s linear infinite;opacity:.4; }
        @keyframes borderFlow { 0%{background-position:0%} 100%{background-position:200%} }
    </style>
</head>
<body class="bg-deep text-gray-200 h-screen overflow-hidden">

    <!-- Grid BG -->
    <div class="grid-bg fixed inset-0 pointer-events-none z-0"></div>

    <!-- Layout -->
    <div class="flex h-screen relative z-10">

        <!-- ===== SIDEBAR ===== -->
        <aside id="sidebar" class="sidebar glass border-r neon-border flex flex-col w-72 h-full shrink-0 z-50">
            <div class="p-5 border-b neon-border">
                <button id="new-chat-btn" class="w-full py-3 px-4 glass border neon-border rounded-xl text-sm font-medium flex items-center justify-center gap-2 hover:border-cyan-neon/50 hover:bg-cyan-neon/5 transition-all duration-300">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Obrolan Baru
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4 chat-scroll">
                <p class="text-[.7rem] text-gray-500 uppercase tracking-widest mb-3 px-1">Riwayat</p>
                <div id="history-list" class="flex flex-col gap-1">
                    <p class="text-center text-gray-600 text-xs mt-6">Belum ada riwayat</p>
                </div>
            </div>
            <div class="p-5 border-t neon-border">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg overflow-hidden shrink-0">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar }}" alt="" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-cyan-neon to-violet-neon flex items-center justify-center text-white font-bold text-sm">{{ substr(auth()->user()->name,0,1) }}</div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-8 h-8 glass border neon-border rounded-lg flex items-center justify-center text-gray-500 hover:text-red-400 hover:border-red-500/30 transition-all">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        </button>
                    </form>
                </div>
                <p class="text-[.6rem] text-gray-600 tracking-widest mt-3 uppercase">Tiara v1.5.0</p>
            </div>
        </aside>

        <!-- Overlay mobile -->
        <div id="sidebar-overlay" class="overlay fixed inset-0 bg-black/50 backdrop-blur-sm z-40"></div>

        <!-- ===== MAIN ===== -->
        <div class="flex flex-col flex-1 h-full overflow-hidden">

            <!-- Header -->
            <header class="relative glass border-b neon-border px-5 py-3 flex items-center gap-4 shrink-0 header-line z-10">
                <button id="sidebar-toggle" class="w-9 h-9 glass border neon-border rounded-lg flex items-center justify-center text-gray-400 hover:border-cyan-neon/50 hover:text-cyan-neon transition-all">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-cyan-neon to-violet-neon flex items-center justify-center shrink-0 animate-pulse-neon">
                    <svg viewBox="0 0 24 24" class="w-6 h-6 fill-white"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                </div>
                <div class="flex-1">
                    <h1 class="font-orbitron text-base font-black tracking-[3px] uppercase neon-text">TIARA AI</h1>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-blink" style="box-shadow:0 0 8px rgba(74,222,128,.7)"></span>
                        <span class="text-[.7rem] text-gray-400 tracking-wide">Online — {{ $aiProvider ?? 'AI' }}</span>
                    </div>
                </div>
                <button id="clear-btn" class="w-9 h-9 glass border neon-border rounded-lg flex items-center justify-center text-gray-400 hover:border-cyan-neon/50 hover:text-cyan-neon transition-all" title="Bersihkan percakapan">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
            </header>

            <!-- Messages -->
            <main id="chat-container" class="flex-1 overflow-y-auto p-5 flex flex-col gap-4 chat-scroll">
                <!-- Welcome -->
                <div id="welcome-screen" class="flex flex-col items-center justify-center flex-1 text-center py-16 animate-slide-up">
                    <div class="animate-float w-20 h-20 rounded-2xl bg-gradient-to-br from-cyan-neon/10 to-violet-neon/10 border neon-border flex items-center justify-center mb-6">
                        <svg viewBox="0 0 24 24" class="w-10 h-10" style="fill:#00f5ff;filter:drop-shadow(0 0 10px rgba(0,245,255,.5))"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                    </div>
                    <h2 class="font-orbitron text-3xl font-black tracking-[4px] uppercase neon-text mb-2">Tiara AI</h2>
                    <p class="text-gray-400 text-sm max-w-xs leading-relaxed mb-10">Halo! Aku Tiara, teman mahasiswa kamu yang siap bantu ngerjain tugas, diskusi materi kuliah, atau sekadar nemenin kamu belajar.</p>
                    <div class="grid grid-cols-2 gap-2 w-full max-w-md">
                        <div class="suggestion-card glass border neon-border rounded-xl p-4 cursor-pointer hover:border-cyan-neon/50 hover:bg-cyan-neon/5 transition-all text-left" data-prompt="Jelaskan tentang artificial intelligence dalam bahasa sederhana">
                            <div class="text-2xl mb-2">🧠</div><div class="text-xs text-gray-400 leading-snug">Jelaskan tentang AI dalam bahasa sederhana</div>
                        </div>
                        <div class="suggestion-card glass border neon-border rounded-xl p-4 cursor-pointer hover:border-cyan-neon/50 hover:bg-cyan-neon/5 transition-all text-left" data-prompt="Buatkan contoh kode JavaScript untuk membuat animasi">
                            <div class="text-2xl mb-2">💻</div><div class="text-xs text-gray-400 leading-snug">Contoh kode JavaScript untuk animasi</div>
                        </div>
                        <div class="suggestion-card glass border neon-border rounded-xl p-4 cursor-pointer hover:border-cyan-neon/50 hover:bg-cyan-neon/5 transition-all text-left" data-prompt="Apa tren teknologi terbaru yang perlu saya ketahui?">
                            <div class="text-2xl mb-2">🚀</div><div class="text-xs text-gray-400 leading-snug">Tren teknologi terbaru</div>
                        </div>
                        <div class="suggestion-card glass border neon-border rounded-xl p-4 cursor-pointer hover:border-cyan-neon/50 hover:bg-cyan-neon/5 transition-all text-left" data-prompt="Bantu saya menulis puisi tentang masa depan">
                            <div class="text-2xl mb-2">✨</div><div class="text-xs text-gray-400 leading-snug">Tulis puisi tentang masa depan</div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Input -->
            <footer class="glass border-t neon-border px-4 py-4 shrink-0">
                <div class="glass border neon-border rounded-2xl px-4 py-2 flex items-end gap-3 max-w-3xl mx-auto hover:border-cyan-neon/40 transition-all">
                    <textarea id="chat-input" rows="1" placeholder="Tanya Tiara sesuatu..." class="flex-1 bg-transparent text-sm resize-none outline-none placeholder-gray-600 py-2 max-h-32 leading-relaxed chat-scroll" aria-label="Chat input"></textarea>
                    <button id="send-btn" disabled class="w-9 h-9 rounded-xl bg-gradient-to-br from-cyan-neon to-violet-neon flex items-center justify-center shrink-0 mb-1 disabled:opacity-25 disabled:cursor-not-allowed hover:opacity-90 transition-all neon-glow" aria-label="Send">
                        <svg viewBox="0 0 24 24" class="w-4 h-4 fill-white"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    </button>
                </div>
                <p class="text-center text-[.68rem] text-gray-600 mt-2">
                    Tekan <kbd class="px-1 py-0.5 bg-white/5 border border-white/10 rounded text-gray-500">Enter</kbd> kirim ·
                    <kbd class="px-1 py-0.5 bg-white/5 border border-white/10 rounded text-gray-500">Shift+Enter</kbd> baris baru
                </p>
            </footer>
        </div>
    </div>

    <!-- Modal -->
    <div id="modal-overlay" class="modal-overlay fixed inset-0 bg-black/70 backdrop-blur-sm z-[100] flex items-center justify-center">
        <div class="glass border border-cyan-neon/20 rounded-2xl p-6 max-w-sm w-full mx-4">
            <h3 class="text-base font-semibold mb-2">Hapus Percakapan?</h3>
            <p class="text-sm text-gray-400 mb-6">Semua riwayat percakapan akan dihapus dan tidak dapat dikembalikan.</p>
            <div class="flex gap-3">
                <button id="modal-cancel" class="flex-1 py-2.5 glass border neon-border rounded-xl text-sm hover:border-white/30 transition-all">Batal</button>
                <button id="modal-confirm" class="flex-1 py-2.5 bg-red-500/15 border border-red-500/30 rounded-xl text-sm text-red-400 hover:bg-red-500/25 transition-all">Hapus Semua</button>
            </div>
        </div>
    </div>

    <script>
    // ============================================================
    // TIARA AI — Embedded App Logic
    // ============================================================
    let conversationHistory = [];
    let currentSessionId = null;
    let sessions = [];
    let isProcessing = false;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    async function apiCall(url, options = {}) {
        try {
            const response = await fetch(url, options);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return await response.json();
        } catch (e) { console.error('API Error:', e); return null; }
    }

    function scrollToBottom() {
        const c = document.getElementById('chat-container');
        if (c) c.scrollTo({ top: c.scrollHeight, behavior: 'smooth' });
    }

    function autoResize(el) {
        if (!el) return;
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
    }

    function formatMessage(text) {
        if (!text) return '';
        return text
            .replace(/```([\s\S]*?)```/g, '<pre class="code-block">$1</pre>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/`(.*?)`/g, '<code>$1</code>')
            .replace(/\n/g, '<br>');
    }

    function renderMessage(role, text, animate = false) {
        const container = document.getElementById('chat-container');
        const welcome = document.getElementById('welcome-screen');
        if (welcome) welcome.style.display = 'none';
        if (!container) return;

        const isUser = role === 'user';
        const div = document.createElement('div');
        div.className = `flex gap-2 w-fit max-w-[85%] ${isUser ? 'self-end flex-row-reverse' : 'self-start'}`;
        div.style.animation = 'slide-up .4s ease-out';

        const avatarBg = isUser ? 'background:linear-gradient(135deg,#3b82f6,#8b5cf6)' : 'background:rgba(0,245,255,0.1);border:1px solid rgba(0,245,255,0.2)';
        const bubbleBg = isUser ? 'background:linear-gradient(135deg,#3b82f6,#8b5cf6);color:white;border-radius:18px 18px 4px 18px;' : 'background:rgba(15,15,45,0.6);border:1px solid rgba(0,245,255,0.12);border-radius:18px 18px 18px 4px;';
        const avatarContent = isUser ? '<span style="color:white;font-weight:700;font-size:.8rem">' + (auth_initial || 'U') + '</span>' : '<svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:#00f5ff"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>';
        const time = new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});

        div.innerHTML = `
            <div style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:4px;${avatarBg}">${avatarContent}</div>
            <div style="display:flex;flex-direction:column;gap:4px">
                <div class="message-bubble" style="padding:10px 14px;font-size:.88rem;line-height:1.65;word-break:break-word;backdrop-filter:blur(10px);${bubbleBg}">
                    ${animate ? '<span>...</span>' : formatMessage(text)}
                </div>
                <div style="font-size:.65rem;color:#6b7280;padding:0 4px;${isUser ? 'text-align:right' : ''}">${time}</div>
            </div>`;

        container.appendChild(div);
        scrollToBottom();

        if (animate) {
            const bubble = div.querySelector('.message-bubble');
            let i = 0;
            bubble.innerHTML = '';
            const iv = setInterval(() => {
                if (i < text.length) {
                    bubble.innerHTML = formatMessage(text.substring(0, i+1)) + '<span class="cursor-blink" style="display:inline-block;width:2px;height:1em;background:#00f5ff;margin-left:2px;vertical-align:middle"></span>';
                    i++;
                    container.scrollTop = container.scrollHeight;
                } else {
                    bubble.innerHTML = formatMessage(text);
                    clearInterval(iv);
                }
            }, 8);
        }
    }

    function renderSidebar() {
        const list = document.getElementById('history-list');
        if (!list) return;
        if (!sessions.length) { list.innerHTML = '<p style="text-align:center;color:#4b5563;font-size:.75rem;margin-top:24px">Belum ada riwayat</p>'; return; }
        list.innerHTML = sessions.map(s => `
            <div class="history-item" data-id="${s.id}" style="padding:10px 12px;border-radius:8px;color:#9ca3af;font-size:.82rem;cursor:pointer;border:1px solid transparent;display:flex;align-items:center;justify-content:space-between;gap:8px;transition:all .2s;${String(s.id)===String(currentSessionId)?'background:rgba(0,245,255,0.08);color:#00f5ff;border-color:rgba(0,245,255,0.2)':''}">
                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${s.title}</span>
                <button class="delete-session-btn" data-id="${s.id}" style="opacity:0;background:none;border:none;color:#6b7280;cursor:pointer;padding:4px;border-radius:4px;display:flex;align-items:center;transition:all .2s">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
            </div>`).join('');
        document.querySelectorAll('.history-item').forEach(item => {
            item.addEventListener('mouseenter', () => { item.querySelector('.delete-session-btn').style.opacity='1'; item.style.background='rgba(255,255,255,0.05)'; });
            item.addEventListener('mouseleave', () => { item.querySelector('.delete-session-btn').style.opacity='0'; if(String(item.dataset.id)!==String(currentSessionId)) item.style.background=''; });
        });
    }

    async function loadSessions() {
        const data = await apiCall('/api/sessions');
        if (data) { sessions = data; renderSidebar(); }
    }

    async function switchSession(id) {
        if (isProcessing) return;
        currentSessionId = id;
        const container = document.getElementById('chat-container');
        const welcome = document.getElementById('welcome-screen');
        container.querySelectorAll('[style*="align-self"], div[class*="flex gap-2"]').forEach(m => m.remove());
        if (welcome) welcome.style.display = 'none';
        const messages = await apiCall(`/api/sessions/${id}/messages`);
        if (messages && messages.length) {
            conversationHistory = messages;
            messages.forEach(m => renderMessage(m.role, m.parts[0].text));
        } else { conversationHistory = []; if (welcome) welcome.style.display = 'flex'; }
        renderSidebar();
    }

    function createNewSession() {
        currentSessionId = null; conversationHistory = [];
        const container = document.getElementById('chat-container');
        const welcome = document.getElementById('welcome-screen');
        container.querySelectorAll('div[style*="align-self"]').forEach(m => m.remove());
        if (welcome) welcome.style.display = 'flex';
        const input = document.getElementById('chat-input');
        if (input) { input.value = ''; autoResize(input); }
        renderSidebar();
    }

    async function handleSend() {
        const input = document.getElementById('chat-input');
        const btn = document.getElementById('send-btn');
        const text = input?.value.trim();
        if (!text || isProcessing) return;
        isProcessing = true;
        if (btn) btn.disabled = true;
        input.value = ''; autoResize(input);
        renderMessage('user', text);

        const response = await apiCall('/api/chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ message: text, session_id: currentSessionId, history: conversationHistory })
        });

        if (response?.reply) {
            if (!currentSessionId && response.session_id) { currentSessionId = response.session_id; await loadSessions(); }
            renderMessage('model', response.reply, true);
            conversationHistory.push({ role:'user', parts:[{text}] });
            conversationHistory.push({ role:'model', parts:[{text:response.reply}] });
        } else {
            renderMessage('model', 'Maaf, terjadi kesalahan. Silakan coba lagi.');
        }
        isProcessing = false;
        if (btn) btn.disabled = !input?.value.trim();
        input?.focus();
    }

    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('chat-input');
        const btn = document.getElementById('send-btn');

        // Send button enable/disable
        input?.addEventListener('input', () => {
            autoResize(input);
            if (btn) btn.disabled = !input.value.trim();
        });

        // Send on click
        btn?.addEventListener('click', handleSend);

        // Send on Enter
        input?.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); handleSend(); }
        });

        // Suggestions
        document.querySelectorAll('.suggestion-card').forEach(card => {
            card.addEventListener('click', () => {
                if (isProcessing) return;
                if (input) { input.value = card.dataset.prompt; autoResize(input); }
                if (btn) btn.disabled = false;
                handleSend();
            });
        });

        // Sidebar
        document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
            document.getElementById('sidebar')?.classList.toggle('hidden-sidebar');
            document.getElementById('sidebar-overlay')?.classList.toggle('active');
        });
        document.getElementById('sidebar-overlay')?.addEventListener('click', () => {
            document.getElementById('sidebar')?.classList.remove('hidden-sidebar');
            document.getElementById('sidebar-overlay')?.classList.remove('active');
        });

        // Sidebar history delegation
        document.getElementById('history-list')?.addEventListener('click', async e => {
            const item = e.target.closest('.history-item');
            if (!item) return;
            const delBtn = e.target.closest('.delete-session-btn');
            if (delBtn) {
                if (confirm('Hapus percakapan ini?')) {
                    const res = await apiCall(`/api/sessions/${delBtn.dataset.id}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':csrfToken} });
                    if (res) { await loadSessions(); if (String(currentSessionId) === String(delBtn.dataset.id)) createNewSession(); }
                }
                return;
            }
            switchSession(item.dataset.id);
        });

        // New chat btn
        document.getElementById('new-chat-btn')?.addEventListener('click', createNewSession);

        // Clear / modal
        document.getElementById('clear-btn')?.addEventListener('click', () => document.getElementById('modal-overlay')?.classList.add('active'));
        document.getElementById('modal-cancel')?.addEventListener('click', () => document.getElementById('modal-overlay')?.classList.remove('active'));
        document.getElementById('modal-confirm')?.addEventListener('click', () => { document.getElementById('modal-overlay')?.classList.remove('active'); createNewSession(); });

        // Load sessions
        loadSessions().then(() => { if (sessions.length) switchSession(sessions[0].id); });
    });
    </script>
</body>
</html>
