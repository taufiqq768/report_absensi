@extends('layouts.app')

@section('title', 'Login')

@push('styles')
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-lg);
        }

        .login-card {
            max-width: 450px;
            width: 100%;
            background: rgba(253, 253, 253, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(247, 242, 242, 1);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-lg);
            animation: slideUp 0.6s ease;
            position: relative;
        }

        .login-header {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }

        .login-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: var(--spacing-sm);
        }

        .login-header p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }

        .login-btn {
            margin-top: var(--spacing-sm);
            width: 100%;
            transition: all 0.3s ease;
        }

        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .login-footer {
            text-align: center;
            margin-top: var(--spacing-lg);
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: var(--spacing-md);
            animation: slideDown 0.3s ease;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }

        /* Loading Overlay Styles */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-lg);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-spinner {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            position: relative;
        }

        .loading-spinner::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border: 4px solid rgba(34, 197, 94, 0.1);
            border-radius: 50%;
        }

        .loading-spinner::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border: 4px solid transparent;
            border-top-color: #22c55e;
            border-right-color: #0ea5e9;
            border-radius: 50%;
            animation: spin 1s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .loading-content {
            text-align: center;
            max-width: 350px;
            padding: 0 1rem;
        }

        .loading-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .loading-message {
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            min-height: 24px;
            transition: all 0.3s ease;
        }

        .loading-progress {
            width: 100%;
            height: 6px;
            background: rgba(34, 197, 94, 0.1);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .loading-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #22c55e, #0ea5e9);
            border-radius: 10px;
            width: 0%;
            transition: width 0.5s ease;
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }

            100% {
                opacity: 1;
            }
        }

        .loading-time {
            font-size: 0.875rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .loading-dots {
            display: inline-flex;
            gap: 4px;
        }

        .loading-dots span {
            width: 6px;
            height: 6px;
            background: #22c55e;
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out both;
        }

        .loading-dots span:nth-child(1) {
            animation-delay: -0.32s;
        }

        .loading-dots span:nth-child(2) {
            animation-delay: -0.16s;
        }

        @keyframes bounce {

            0%,
            80%,
            100% {
                transform: scale(0);
            }

            40% {
                transform: scale(1);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endpush

@section('content')
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>🔐 Login</h1>
                <p>Sistem Pelaporan Absensi Karyawan</p>
            </div>

            @if($errors->has('login'))
                <div class="alert alert-error">
                    <strong>❌ Login Gagal</strong>
                    <p>{{ $errors->first('login') }}</p>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">
                    <strong>✅ Berhasil</strong>
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error">
                    <strong>❌ Error</strong>
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="login-form" id="loginForm">
                @csrf

                <div class="form-group">
                    <label for="username">NIK</label>
                    <input type="text" id="username" name="username" value="{{ old('username') }}"
                        placeholder="Masukkan username" required autocomplete="username">
                    @error('username')
                        <span style="color: #fca5a5; font-size: 0.875rem;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required
                        autocomplete="current-password">
                    @error('password')
                        <span style="color: #fca5a5; font-size: 0.875rem;">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn login-btn" id="loginBtn">
                    🚀 Login
                </button>
            </form>

            <!-- Loading Overlay -->
            <div class="loading-overlay" id="loadingOverlay">
                <div class="loading-spinner"></div>
                <div class="loading-content">
                    <div class="loading-title">Memproses Login</div>
                    <div class="loading-message" id="loadingMessage">Menghubungi server...</div>
                    <div class="loading-progress">
                        <div class="loading-progress-bar" id="progressBar"></div>
                    </div>
                    <div class="loading-time">
                        <span id="timeElapsed">0</span> detik
                        <div class="loading-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="login-footer">
                <p>&copy; 2025 PTPN I. All rights reserved.</p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Loading messages that change over time
        const loadingMessages = [
            { time: 0, message: 'Menghubungi server HCIS...', progress: 10 },
            { time: 5, message: 'Membuka koneksi ke API...', progress: 25 },
            { time: 10, message: 'Mengirim kredensial...', progress: 40 },
            { time: 15, message: 'Server sedang memproses...', progress: 55 },
            { time: 20, message: 'Memverifikasi data...', progress: 70 },
            { time: 30, message: 'Hampir selesai, mohon tunggu...', progress: 85 },
            { time: 40, message: 'Server sedang lambat, harap bersabar...', progress: 90 },
            { time: 50, message: 'Masih memproses, terima kasih atas kesabaran Anda...', progress: 95 }
        ];

        let loadingInterval = null;
        let startTime = null;

        // Get DOM elements
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const loadingMessage = document.getElementById('loadingMessage');
        const progressBar = document.getElementById('progressBar');
        const timeElapsed = document.getElementById('timeElapsed');

        // Handle form submission
        loginForm.addEventListener('submit', function (e) {
            // Show loading overlay
            showLoading();
        });

        function showLoading() {
            // Show overlay
            loadingOverlay.classList.add('active');
            loginBtn.disabled = true;

            // Start timer
            startTime = Date.now();
            let elapsedSeconds = 0;

            // Update progress and messages
            loadingInterval = setInterval(() => {
                elapsedSeconds = Math.floor((Date.now() - startTime) / 1000);
                timeElapsed.textContent = elapsedSeconds;

                // Update message and progress based on elapsed time
                for (let i = loadingMessages.length - 1; i >= 0; i--) {
                    if (elapsedSeconds >= loadingMessages[i].time) {
                        loadingMessage.textContent = loadingMessages[i].message;
                        progressBar.style.width = loadingMessages[i].progress + '%';
                        break;
                    }
                }
            }, 100);
        }

        function hideLoading() {
            loadingOverlay.classList.remove('active');
            loginBtn.disabled = false;

            if (loadingInterval) {
                clearInterval(loadingInterval);
                loadingInterval = null;
            }
        }

        // Hide loading on page load (in case of error redirect)
        window.addEventListener('load', function () {
            // If there are errors, hide the loading
            @if($errors->any() || session('error'))
                hideLoading();
            @endif
            });
    </script>
@endpush