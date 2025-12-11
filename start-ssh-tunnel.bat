@echo off
echo ========================================
echo   Starting SSH Tunnel to HRIS Database
echo ========================================
echo.

REM ===== KONFIGURASI - EDIT SESUAI SERVER ANDA =====
set SSH_USER=your_username
set SSH_HOST=hris-server-ip-or-hostname
set SSH_PORT=22
set LOCAL_PORT=3307
set REMOTE_PORT=3306
REM ==================================================

echo Starting SSH tunnel...
echo Local Port: %LOCAL_PORT%
echo Remote: %SSH_USER%@%SSH_HOST%:%REMOTE_PORT%
echo.
echo NOTE: Keep this window OPEN while using the application!
echo       Press Ctrl+C to stop the tunnel.
echo.

REM Start SSH tunnel (will ask for password)
ssh -L %LOCAL_PORT%:localhost:%REMOTE_PORT% -p %SSH_PORT% %SSH_USER%@%SSH_HOST%

REM If using SSH key (no password)
REM ssh -i C:\path\to\private_key.pem -L %LOCAL_PORT%:localhost:%REMOTE_PORT% -p %SSH_PORT% %SSH_USER%@%SSH_HOST%

echo.
echo SSH tunnel stopped.
pause
