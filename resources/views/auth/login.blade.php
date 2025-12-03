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

            <form action="{{ route('login') }}" method="POST" class="login-form">
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

                <button type="submit" class="btn login-btn">
                    🚀 Login
                </button>
            </form>

            <div class="login-footer">
                <p>&copy; 2025 PTPN I. All rights reserved.</p>
            </div>
        </div>
    </div>
@endsection