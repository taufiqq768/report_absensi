@echo off
REM ============================================
REM SSH Tunnel untuk Database HRIS
REM ============================================
REM 
REM Script ini akan membuat SSH tunnel dari server remote
REM ke localhost untuk akses database HRIS
REM
REM ============================================

echo.
echo ============================================
echo   SSH Tunnel - Database HRIS
echo ============================================
echo.

REM ============================================
REM KONFIGURASI - EDIT BAGIAN INI
REM ============================================

REM SSH Server Configuration
set SSH_USER=your_username
set SSH_HOST=192.168.1.100
set SSH_PORT=22

REM Port Forwarding Configuration
set LOCAL_PORT=3307
set REMOTE_HOST=127.0.0.1
set REMOTE_PORT=3306

REM ============================================
REM JANGAN EDIT DIBAWAH INI
REM ============================================

echo Konfigurasi SSH Tunnel:
echo.
echo   SSH Server    : %SSH_USER%@%SSH_HOST%:%SSH_PORT%
echo   Local Port    : %LOCAL_PORT%
echo   Remote MySQL  : %REMOTE_HOST%:%REMOTE_PORT%
echo.
echo ============================================
echo.

REM Check if SSH is available
where ssh >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: SSH tidak ditemukan!
    echo.
    echo Pastikan OpenSSH sudah terinstall di Windows.
    echo Atau gunakan Git Bash / WSL untuk menjalankan SSH.
    echo.
    pause
    exit /b 1
)

echo Memulai SSH Tunnel...
echo.
echo CATATAN:
echo - Jendela ini harus tetap terbuka selama aplikasi berjalan
echo - Tekan Ctrl+C untuk menghentikan tunnel
echo - Update file .env dengan: HRIS_DB_PORT=%LOCAL_PORT%
echo.
echo ============================================
echo.

REM Start SSH Tunnel
ssh -L %LOCAL_PORT%:%REMOTE_HOST%:%REMOTE_PORT% %SSH_USER%@%SSH_HOST% -p %SSH_PORT% -N -o ServerAliveInterval=60

REM If SSH exits
echo.
echo SSH Tunnel terputus!
echo.
pause
