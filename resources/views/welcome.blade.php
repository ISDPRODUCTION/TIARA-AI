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
        body { font-family: 'Inter', sans-serif; background-color: #050510; }
        
        /* ===== Futuristic Background ===== */
        .bg-nebula {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 20% 30%, rgba(0, 245, 255, 0.05) 0%, transparent 40%),
                        radial-gradient(circle at 80% 70%, rgba(139, 92, 246, 0.08) 0%, transparent 40%);
            filter: blur(80px);
            z-index: -1;
            animation: nebulaMove 15s ease-in-out infinite alternate;
        }

        @keyframes nebulaMove {
            0% { transform: scale(1) translate(0, 0); }
            100% { transform: scale(1.1) translate(2%, 2%); }
        }

        .grid-bg { 
            background-image: linear-gradient(rgba(0, 245, 255, 0.03) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(0, 245, 255, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(circle at center, black, transparent 80%);
        }

        /* ===== UI Elements ===== */
        .glass { background: rgba(10, 10, 26, 0.6); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px); }
        .neon-border { border-color: rgba(0, 245, 255, 0.1); }
        .neon-text { background: linear-gradient(135deg, #00f5ff, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .neon-glow-btn { box-shadow: 0 0 15px rgba(0, 245, 255, 0.2); }
        .neon-glow-btn:hover { box-shadow: 0 0 25px rgba(0, 245, 255, 0.4); }

        /* ===== Animations ===== */
        @keyframes slideUp { from { opacity: 0; transform: translateY(15px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .animate-message { animation: slideUp 0.4s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; }
        
        @keyframes thinking { 0%, 100% { transform: translateY(0); opacity: 0.4; } 50% { transform: translateY(-4px); opacity: 1; } }
        .dot { width: 6px; height: 6px; border-radius: 50%; background: #00f5ff; animation: thinking 1.2s infinite; }
        .dot:nth-child(2) { animation-delay: 0.2s; background: #8b5cf6; }
        .dot:nth-child(3) { animation-delay: 0.4s; }

        .chat-scroll::-webkit-scrollbar { width: 4px; }
        .chat-scroll::-webkit-scrollbar-thumb { background: rgba(0, 245, 255, 0.1); border-radius: 10px; }
        
        .code-block { background: rgba(0,0,0,0.4); border: 1px solid rgba(0,245,255,0.12); border-radius: 8px; padding: 12px; margin: 8px 0; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; }
        code { font-family: 'JetBrains Mono', monospace; background: rgba(0,245,255,0.1); padding: 2px 5px; border-radius: 4px; color: #00f5ff; }

        .sidebar { 
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
            width: 280px;
            max-width: 85vw;
        }
        @media (max-width: 1023px) {
            .sidebar { 
                position: fixed;
                left: 0; top: 0; bottom: 0;
                z-index: 50;
                transform: translateX(-100%);
            }
            .sidebar.active { transform: translateX(0); }
        }
    </style>
</head>
<body class="text-gray-200 h-screen overflow-hidden">

    <!-- Advanced Background -->
    <div class="bg-nebula"></div>
    <div class="grid-bg fixed inset-0 pointer-events-none z-0"></div>

    <div class="flex h-screen relative z-10">

        <!-- ===== SIDEBAR ===== -->
        <aside id="sidebar" class="sidebar glass border-r neon-border flex flex-col h-full shrink-0">
            <div class="p-6 border-b neon-border">
                <button id="new-chat-btn" class="w-full py-3 px-4 glass border neon-border rounded-xl text-sm font-medium flex items-center justify-center gap-2 hover:border-cyan-neon/50 hover:bg-cyan-neon/5 transition-all duration-300 neon-glow-btn">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Obrolan Baru
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4 chat-scroll">
                <p class="text-[0.65rem] text-gray-500 uppercase tracking-widest mb-4 px-2">RIWAYAT</p>
                <div id="history-list" class="flex flex-col gap-1.5">
                    <!-- Loaded via JS -->
                </div>
            </div>
            <div class="p-6 border-t neon-border">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-cyan-neon to-violet-neon flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-cyan-neon/20">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[0.65rem] text-gray-500 truncate">Standard Protocol</p>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="p-2 glass border neon-border rounded-lg text-gray-500 hover:text-red-400 hover:border-red-500/30 transition-all">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        </button>
                    </form>
                </div>
                <p class="text-[0.6rem] text-gray-600 tracking-widest text-center uppercase">V 1.5.2 // TIARA PROTOCOL</p>
            </div>
        </aside>

        <div id="sidebar-overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 opacity-0 invisible transition-all duration-300"></div>

        <!-- ===== MAIN CONTENT ===== -->
        <div class="flex flex-col flex-1 h-full overflow-hidden">

            <!-- Header -->
            <header class="glass border-b neon-border px-6 py-4 flex items-center gap-5 shrink-0 z-20">
                <button id="sidebar-toggle" class="p-2 glass border neon-border rounded-lg text-gray-400 hover:text-cyan-neon hover:border-cyan-neon/50 transition-all">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="flex-1 min-w-0">
                    <h1 class="font-orbitron text-sm sm:text-base font-black tracking-[2px] sm:tracking-[4px] uppercase neon-text truncate">TIARA AI</h1>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-neon animate-pulse shrink-0" style="box-shadow: 0 0 8px #00f5ff"></span>
                        <span class="text-[0.6rem] sm:text-[0.65rem] text-gray-400 uppercase tracking-widest font-medium truncate">Online // {{ $aiProvider ?? 'Mistral' }}</span>
                    </div>
                </div>
                <button id="clear-btn" class="p-2.5 glass border neon-border rounded-xl text-gray-400 hover:text-cyan-neon hover:border-cyan-neon/50 transition-all">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
            </header>

            <!-- Chat Area -->
            <main id="chat-container" class="flex-1 overflow-y-auto p-6 flex flex-col gap-6 chat-scroll relative">
                <!-- Welcome Screen -->
                <div id="welcome-screen" class="flex flex-col items-center justify-center flex-1 text-center py-12">
                    <div class="w-24 h-24 rounded-3xl bg-gradient-to-br from-cyan-neon/10 to-violet-neon/10 border neon-border flex items-center justify-center mb-8 relative group">
                        <div class="absolute inset-0 bg-cyan-neon/5 blur-xl group-hover:bg-cyan-neon/10 transition-all"></div>
                        <svg viewBox="0 0 24 24" class="w-12 h-12 relative z-10" style="fill:#00f5ff; filter: drop-shadow(0 0 10px #00f5ff)"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                    </div>
                    <h2 class="font-orbitron text-2xl sm:text-4xl font-black tracking-[4px] sm:tracking-[6px] uppercase neon-text mb-3">TIARA AI</h2>
                    <p class="text-gray-400 text-xs sm:text-sm max-w-sm leading-relaxed mb-8 sm:mb-12 px-6">Halo! Aku Tiara, teman mahasiswa kamu yang siap bantu ngerjain tugas, diskusi materi kuliah, atau sekadar nemenin kamu belajar.</p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full max-w-lg px-4">
                        <button class="suggestion-card glass border neon-border rounded-2xl p-5 text-left hover:border-cyan-neon/40 hover:bg-cyan-neon/5 transition-all group" data-prompt="Jelaskan tentang AI dalam bahasa sederhana">
                            <span class="text-2xl mb-3 block group-hover:scale-110 transition-transform">🧠</span>
                            <p class="text-xs text-gray-400 leading-relaxed font-medium">Jelaskan tentang AI dalam bahasa sederhana</p>
                        </button>
                        <button class="suggestion-card glass border neon-border rounded-2xl p-5 text-left hover:border-cyan-neon/40 hover:bg-cyan-neon/5 transition-all group" data-prompt="Contoh kode JavaScript untuk animasi">
                            <span class="text-2xl mb-3 block group-hover:scale-110 transition-transform">💻</span>
                            <p class="text-xs text-gray-400 leading-relaxed font-medium">Contoh kode JavaScript untuk animasi</p>
                        </button>
                        <button class="suggestion-card glass border neon-border rounded-2xl p-5 text-left hover:border-cyan-neon/40 hover:bg-cyan-neon/5 transition-all group" data-prompt="Tren teknologi terbaru">
                            <span class="text-2xl mb-3 block group-hover:scale-110 transition-transform">🚀</span>
                            <p class="text-xs text-gray-400 leading-relaxed font-medium">Tren teknologi terbaru</p>
                        </button>
                        <button class="suggestion-card glass border neon-border rounded-2xl p-5 text-left hover:border-cyan-neon/40 hover:bg-cyan-neon/5 transition-all group" data-prompt="Tulis puisi tentang masa depan">
                            <span class="text-2xl mb-3 block group-hover:scale-110 transition-transform">✨</span>
                            <p class="text-xs text-gray-400 leading-relaxed font-medium">Tulis puisi tentang masa depan</p>
                        </button>
                    </div>
                </div>
            </main>

            <!-- Input Area -->
            <footer class="p-6 shrink-0 z-20">
                <div class="max-w-3xl mx-auto relative group">
                    <div class="absolute -inset-1 bg-gradient-to-r from-cyan-neon/20 to-violet-neon/20 rounded-2xl blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
                    <div class="relative glass border neon-border rounded-2xl p-2 flex items-end gap-3 shadow-2xl">
                        <textarea id="chat-input" rows="1" placeholder="Masukkan pesan..." class="flex-1 bg-transparent text-sm resize-none outline-none placeholder-gray-600 px-4 py-3 max-h-32 leading-relaxed chat-scroll" aria-label="Query input"></textarea>
                        <button id="send-btn" disabled class="w-11 h-11 rounded-xl bg-gradient-to-br from-cyan-neon to-violet-neon flex items-center justify-center shrink-0 mb-1 disabled:opacity-20 disabled:cursor-not-allowed hover:scale-105 transition-all shadow-lg shadow-cyan-neon/20">
                            <svg viewBox="0 0 24 24" class="w-5 h-5 fill-white"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                        </button>
                    </div>
                    <p class="text-center text-[0.65rem] text-gray-600 mt-3 font-medium tracking-wider uppercase">
                        Tekan <span class="text-gray-400">Enter</span> untuk mengirim // <span class="text-gray-400">Shift+Enter</span> untuk baris baru
                    </p>
                </div>
            </footer>
        </div>
    </div>

    <!-- Modals -->
    <div id="modal-overlay" class="fixed inset-0 bg-black/80 backdrop-blur-md z-[100] flex items-center justify-center opacity-0 invisible transition-all duration-300">
        <div class="glass border neon-border rounded-2xl p-8 max-w-sm w-full mx-4 text-center transform scale-95 transition-all duration-300" id="modal-content">
            <div class="w-16 h-16 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-6 text-red-500">
                <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            </div>
            <h3 class="text-lg font-bold mb-2">Hapus Riwayat?</h3>
            <p class="text-sm text-gray-400 mb-8 leading-relaxed">Tindakan ini akan menghapus seluruh percakapan secara permanen.</p>
            <div class="flex gap-3">
                <button id="modal-cancel" class="flex-1 py-3 glass border neon-border rounded-xl text-sm font-semibold hover:bg-white/5 transition-all">BATAL</button>
                <button id="modal-confirm" class="flex-1 py-3 bg-red-500/80 hover:bg-red-500 rounded-xl text-sm font-bold text-white transition-all">HAPUS</button>
            </div>
        </div>
    </div>

    <script>
    // ============================================================
    // CORE LOGIC // TIARA AI
    // ============================================================
    const AUTH_INITIAL = '{{ substr(auth()->user()->name, 0, 1) }}';
    let conversationHistory = [];
    let currentSessionId = null;
    let sessions = [];
    let isProcessing = false;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const apiCall = async (url, options = {}) => {
        try {
            const response = await fetch(url, options);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return await response.json();
        } catch (e) { console.error('TIARA_SYS_ERROR:', e); return null; }
    };

    const scrollToBottom = () => {
        const c = document.getElementById('chat-container');
        if (c) c.scrollTo({ top: c.scrollHeight, behavior: 'smooth' });
    };

    const autoResize = (el) => {
        if (!el) return;
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
    };

    const formatMessage = (text) => {
        if (!text) return '';
        return text
            .replace(/```([\s\S]*?)```/g, '<div class="code-block">$1</div>')
            .replace(/\*\*(.*?)\*\*/g, '<strong class="text-cyan-neon">$1</strong>')
            .replace(/`(.*?)`/g, '<code>$1</code>')
            .replace(/\n/g, '<br>');
    };

    const renderThinking = () => {
        const container = document.getElementById('chat-container');
        const div = document.createElement('div');
        div.id = 'thinking-indicator';
        div.className = 'flex gap-3 self-start animate-message';
        div.innerHTML = `
            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style="background:rgba(0,245,255,0.05); border:1px solid rgba(0,245,255,0.1)">
                <svg viewBox="0 0 24 24" class="w-4 h-4 fill-cyan-neon animate-pulse"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
            </div>
            <div class="glass border neon-border px-5 py-3 rounded-2xl rounded-tl-none flex items-center gap-1.5 shadow-xl">
                <div class="dot"></div><div class="dot"></div><div class="dot"></div>
            </div>`;
        container.appendChild(div);
        scrollToBottom();
    };

    const removeThinking = () => document.getElementById('thinking-indicator')?.remove();

    const renderMessage = (role, text, animate = false) => {
        const container = document.getElementById('chat-container');
        const welcome = document.getElementById('welcome-screen');
        if (welcome) welcome.style.display = 'none';
        
        const isUser = role === 'user';
        const div = document.createElement('div');
        div.className = `flex gap-3 max-w-[85%] w-fit animate-message ${isUser ? 'self-end flex-row-reverse' : 'self-start'}`;

        const avatarStyle = isUser 
            ? 'background:linear-gradient(135deg,#3b82f6,#8b5cf6); box-shadow: 0 0 10px rgba(139,92,246,0.2)' 
            : 'background:rgba(0,245,255,0.05); border:1px solid rgba(0,245,255,0.1)';
            
        const bubbleStyle = isUser 
            ? 'background:linear-gradient(135deg,#3b82f6,#8b5cf6); color:white; border-radius:20px; border-bottom-right-radius:4px; box-shadow: 0 4px 15px rgba(59,130,246,0.1)' 
            : 'background:rgba(15,15,45,0.7); border:1px solid rgba(0,245,255,0.1); border-radius:20px; border-bottom-left-radius:4px; box-shadow: 0 4px 20px rgba(0,0,0,0.2)';

        const avatarIcon = isUser 
            ? `<span class="text-white font-bold text-xs uppercase">${AUTH_INITIAL}</span>` 
            : '<svg viewBox="0 0 24 24" class="w-4 h-4 fill-cyan-neon"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>';

        div.innerHTML = `
            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-1" style="${avatarStyle}">${avatarIcon}</div>
            <div class="flex flex-col gap-1.5">
                <div class="message-bubble px-5 py-3.5 text-sm leading-relaxed glass" style="${bubbleStyle}">
                    ${animate ? '<span class="opacity-50">Mengirim...</span>' : formatMessage(text)}
                </div>
                <span class="text-[0.6rem] text-gray-600 px-2 uppercase tracking-tighter ${isUser ? 'text-right' : ''}">${new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</span>
            </div>`;

        container.appendChild(div);
        scrollToBottom();

        if (animate) {
            const bubble = div.querySelector('.message-bubble');
            let i = 0;
            bubble.innerHTML = '';
            const timer = setInterval(() => {
                if (i < text.length) {
                    bubble.innerHTML = formatMessage(text.substring(0, i+1)) + '<span class="inline-block w-1.5 h-4 bg-cyan-neon ml-1 align-middle animate-pulse"></span>';
                    i++;
                    if (i % 5 === 0) container.scrollTop = container.scrollHeight;
                } else {
                    bubble.innerHTML = formatMessage(text);
                    clearInterval(timer);
                    scrollToBottom();
                }
            }, 12);
        }
    };

    const renderSidebar = () => {
        const list = document.getElementById('history-list');
        if (!list) return;
        if (!sessions.length) { list.innerHTML = '<div class="py-12 text-center"><p class="text-[0.65rem] text-gray-600 uppercase tracking-widest">Belum ada riwayat</p></div>'; return; }
        
        list.innerHTML = sessions.map(s => `
            <div class="group history-item p-3.5 rounded-xl border border-transparent hover:border-cyan-neon/20 hover:bg-cyan-neon/5 cursor-pointer flex items-center justify-between gap-3 transition-all ${String(s.id) === String(currentSessionId) ? 'bg-cyan-neon/5 border-cyan-neon/30 text-cyan-neon' : 'text-gray-400'}" data-id="${s.id}">
                <div class="flex items-center gap-3 overflow-hidden">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 opacity-40"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <span class="text-xs truncate font-medium tracking-wide">${s.title}</span>
                </div>
                <button class="delete-session-btn opacity-0 group-hover:opacity-100 p-1.5 rounded-lg hover:bg-red-500/10 hover:text-red-400 transition-all" data-id="${s.id}">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
            </div>`).join('');
    };

    const loadSessions = async () => {
        const data = await apiCall('/api/sessions');
        if (data) { sessions = data; renderSidebar(); }
    };

    const switchSession = async (id) => {
        if (isProcessing) return;
        currentSessionId = id;
        const container = document.getElementById('chat-container');
        const welcome = document.getElementById('welcome-screen');
        
        // Cleanup messages
        Array.from(container.children).forEach(child => { if(child.id !== 'welcome-screen') child.remove(); });
        if (welcome) welcome.style.display = 'none';

        renderThinking();
        const messages = await apiCall(`/api/sessions/${id}/messages`);
        removeThinking();

        if (messages && messages.length) {
            conversationHistory = messages;
            messages.forEach(m => renderMessage(m.role, m.parts[0].text));
        } else {
            conversationHistory = [];
            if (welcome) welcome.style.display = 'flex';
        }
        renderSidebar();
    };

    const createNewSession = () => {
        currentSessionId = null;
        conversationHistory = [];
        const container = document.getElementById('chat-container');
        const welcome = document.getElementById('welcome-screen');
        Array.from(container.children).forEach(child => { if(child.id !== 'welcome-screen') child.remove(); });
        if (welcome) welcome.style.display = 'flex';
        const input = document.getElementById('chat-input');
        if (input) { input.value = ''; autoResize(input); }
        renderSidebar();
    };

    const handleSend = async () => {
        const input = document.getElementById('chat-input');
        const btn = document.getElementById('send-btn');
        const text = input?.value.trim();
        
        if (!text || isProcessing) return;
        
        isProcessing = true;
        if (btn) btn.disabled = true;
        input.value = ''; autoResize(input);
        
        renderMessage('user', text);
        renderThinking();

        const response = await apiCall('/api/chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ message: text, session_id: currentSessionId, history: conversationHistory })
        });

        removeThinking();

        if (response?.reply) {
            if (!currentSessionId && response.session_id) { currentSessionId = response.session_id; await loadSessions(); }
            renderMessage('model', response.reply, true);
            conversationHistory.push({ role:'user', parts:[{text}] });
            conversationHistory.push({ role:'model', parts:[{text:response.reply}] });
        } else {
            renderMessage('model', 'PROTOCOL_ERROR: Connection failed. Please re-initialize.');
        }
        
        isProcessing = false;
        if (btn) btn.disabled = !input?.value.trim();
        input?.focus();
    };

    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('chat-input');
        const btn = document.getElementById('send-btn');

        input?.addEventListener('input', () => {
            autoResize(input);
            if (btn) btn.disabled = !input.value.trim();
        });

        btn?.addEventListener('click', handleSend);
        input?.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); handleSend(); }
        });

        document.querySelectorAll('.suggestion-card').forEach(card => {
            card.addEventListener('click', () => {
                if (isProcessing) return;
                if (input) { input.value = card.dataset.prompt; autoResize(input); }
                if (btn) btn.disabled = false;
                handleSend();
            });
        });

        document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
            const sb = document.getElementById('sidebar');
            const ov = document.getElementById('sidebar-overlay');
            sb.classList.toggle('active');
            ov.classList.toggle('opacity-100');
            ov.classList.toggle('visible');
        });

        const closeSidebar = () => {
            const sb = document.getElementById('sidebar');
            const ov = document.getElementById('sidebar-overlay');
            sb.classList.remove('active');
            ov.classList.remove('opacity-100', 'visible');
        };

        document.getElementById('sidebar-overlay')?.addEventListener('click', closeSidebar);

        document.getElementById('history-list')?.addEventListener('click', async e => {
            const item = e.target.closest('.history-item');
            if (!item) return;
            const dBtn = e.target.closest('.delete-session-btn');
            if (dBtn) {
                if (confirm('Hapus arsip sesi ini?')) {
                    const res = await apiCall(`/api/sessions/${dBtn.dataset.id}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':csrfToken} });
                    if (res) { await loadSessions(); if (String(currentSessionId) === String(dBtn.dataset.id)) createNewSession(); }
                }
                return;
            }
            if (window.innerWidth < 1024) closeSidebar();
            switchSession(item.dataset.id);
        });

        document.getElementById('new-chat-btn')?.addEventListener('click', () => {
            if (window.innerWidth < 1024) closeSidebar();
            createNewSession();
        });

        // Modals
        const ov = document.getElementById('modal-overlay');
        const ct = document.getElementById('modal-content');
        
        document.getElementById('clear-btn')?.addEventListener('click', () => {
            ov.classList.remove('invisible');
            ov.classList.add('opacity-100');
            ct.classList.remove('scale-95');
            ct.classList.add('scale-100');
        });

        const closeMod = () => {
            ov.classList.add('invisible');
            ov.classList.remove('opacity-100');
            ct.classList.add('scale-95');
            ct.classList.remove('scale-100');
        };

        document.getElementById('modal-cancel')?.addEventListener('click', closeMod);
        document.getElementById('modal-confirm')?.addEventListener('click', () => { closeMod(); createNewSession(); });

        // Always start with a fresh state and welcome screen
        createNewSession();
        loadSessions();
    });
    </script>
</body>
</html>
