<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login - Laporan Absensi HCIS">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Laporan Absensi</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Login-specific styles */
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
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 1);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-lg);
            animation: slideUp 0.6s ease;
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
            display: none;
            animation: slideDown 0.3s ease;
        }

        .alert.active {
            display: block;
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
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>🔐 Login</h1>
                <p>Sistem Pelaporan Absensi Karyawan HCIS</p>
            </div>

            <!-- Alert Messages -->
            <div id="alertError" class="alert alert-error">
                <strong>❌ Login Gagal</strong>
                <p id="errorText"></p>
            </div>

            <div id="alertSuccess" class="alert alert-success">
                <strong>✅ Login Berhasil</strong>
                <p>Mengalihkan ke dashboard...</p>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Masukkan username" required
                        autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required
                        autocomplete="current-password">
                </div>

                <button type="submit" id="loginBtn" class="btn login-btn">
                    🚀 Login
                </button>
            </form>

            <!-- Loading State -->
            <div id="loading" class="loading">
                <div class="spinner"></div>
                <p>Memverifikasi credentials...</p>
            </div>

            <div class="login-footer">
                <p>&copy; 2025 HCIS Holding Perkebunan</p>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/login.js') }}"></script>
</body>

</html>