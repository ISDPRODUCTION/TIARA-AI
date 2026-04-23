<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tiara AI — Teman belajar mahasiswi yang cerdas, powered by {{ $aiProvider ?? 'AI' }}">
    <meta name="theme-color" content="#050510">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Tiara AI') }} — Your Personal Student Assistant</title>

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body>

    <noscript>
        <div style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:#050510;color:#e8e8f0;font-family:sans-serif;padding:2rem;text-align:center;">
            <div>
                <h1 style="margin-bottom:1rem;color:#00f5ff;">NEXUS AI</h1>
                <p>JavaScript harus diaktifkan untuk menggunakan aplikasi ini.</p>
            </div>
        </div>
    </noscript>

    <!-- ===== Background Layers (Clean & Minimal) ===== -->
    <div class="bg-gradient-overlay" aria-hidden="true"></div>
    <div class="grid-overlay" aria-hidden="true"></div>

    <!-- ===== Main Layout ===== -->
    <div class="main-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <button class="new-chat-btn" id="new-chat-btn">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Obrolan Baru
                </button>
            </div>
            <div class="sidebar-content">
                <div class="history-label">Riwayat Percakapan</div>
                <div class="history-list" id="history-list">
                    <!-- History items will be injected here -->
                    <div class="history-empty">Belum ada riwayat</div>
                </div>
            </div>
            <div class="sidebar-footer">
                <div class="user-profile">
                    <div class="user-avatar-small">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}">
                        @else
                            <div class="avatar-placeholder">{{ substr(auth()->user()->name, 0, 1) }}</div>
                        @endif
                    </div>
                    <div class="user-info">
                        <div class="user-name-wrapper">
                            <span class="user-name">{{ auth()->user()->name }}</span>
                            <span class="user-status-online"></span>
                        </div>
                        <div class="user-email">{{ auth()->user()->email }}</div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="logout-form">
                        @csrf
                        <button type="submit" class="logout-btn-new" title="Keluar">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        </button>
                    </form>
                </div>
                <div class="version-badge">TIARA v1.5.0</div>
            </div>
        </aside>

        <!-- Sidebar Overlay (mobile) -->
        <div class="sidebar-overlay" id="sidebar-overlay"></div>

        <!-- Main App Content -->
        <div class="app-container">
            <!-- Header -->
            <header class="chat-header" id="chat-header">
                <button class="header-btn" id="sidebar-toggle" aria-label="Toggle sidebar">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <div class="header-avatar">
                    @if(auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                    @else
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                        </svg>
                    @endif
                </div>
                <div class="header-info">
                    <h1 class="header-title">TIARA AI</h1>
                    <div class="header-status">
                        <div class="status-dot"></div>
                        <span class="status-text">Online — {{ $aiProvider ?? 'AI' }}</span>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="header-btn" id="clear-btn" title="Bersihkan percakapan" aria-label="Clear chat">
                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                </div>
            </header>

            <!-- Chat Messages Container -->
            <main class="chat-container" id="chat-container">
                <!-- Welcome Screen -->
                <div class="welcome-screen" id="welcome-screen">
                    <div class="welcome-logo">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                        </svg>
                    </div>
                    <h2 class="welcome-title">Tiara AI</h2>
                    <p class="welcome-subtitle">
                        Halo! Aku Tiara, teman mahasiswa kamu yang siap bantu ngerjain tugas, 
                        diskusi materi kuliah, atau sekadar nemenin kamu belajar.
                    </p>
                    <div class="welcome-suggestions">
                        <div class="suggestion-card" data-prompt="Jelaskan tentang artificial intelligence dalam bahasa sederhana">
                            <div class="suggestion-icon">🧠</div>
                            <div class="suggestion-text">Jelaskan tentang AI dalam bahasa sederhana</div>
                        </div>
                        <div class="suggestion-card" data-prompt="Buatkan contoh kode JavaScript untuk membuat animasi">
                            <div class="suggestion-icon">💻</div>
                            <div class="suggestion-text">Contoh kode JavaScript untuk animasi</div>
                        </div>
                        <div class="suggestion-card" data-prompt="Apa tren teknologi terbaru yang perlu saya ketahui?">
                            <div class="suggestion-icon">🚀</div>
                            <div class="suggestion-text">Tren teknologi terbaru</div>
                        </div>
                        <div class="suggestion-card" data-prompt="Bantu saya menulis puisi tentang masa depan">
                            <div class="suggestion-icon">✨</div>
                            <div class="suggestion-text">Tulis puisi tentang masa depan</div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Input Area -->
            <footer class="input-area" id="input-area">
                <div class="input-wrapper">
                    <textarea 
                        class="chat-input" 
                        id="chat-input" 
                        placeholder="Tanya Tiara sesuatu..." 
                        rows="1"
                        aria-label="Chat input"
                    ></textarea>
                    <button class="send-btn" id="send-btn" title="Kirim pesan" aria-label="Send message" disabled>
                        <svg viewBox="0 0 24 24">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>
                <div class="input-hint">
                    Tekan <kbd>Enter</kbd> untuk kirim · <kbd>Shift+Enter</kbd> baris baru
                </div>
            </footer>
        </div>
    </div>

    <!-- Clear Chat Modal -->
    <div class="modal-overlay" id="modal-overlay">
        <div class="modal-card">
            <div class="modal-title">Hapus Percakapan?</div>
            <p class="modal-text">Semua riwayat percakapan akan dihapus dan tidak dapat dikembalikan.</p>
            <div class="modal-actions">
                <button class="modal-btn modal-btn--cancel" id="modal-cancel">Batal</button>
                <button class="modal-btn modal-btn--danger" id="modal-confirm">Hapus Semua</button>
            </div>
        </div>
    </div>

</body>
</html>
