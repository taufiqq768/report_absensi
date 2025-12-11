@echo off
echo ========================================
echo   SSH Tunnel Status Checker
echo ========================================
echo.

REM Check if SSH tunnel is running on port 3307
echo [1] Checking if port 3307 is listening...
netstat -an | findstr ":3307" > nul
if %errorlevel% equ 0 (
    echo    [OK] Port 3307 is OPEN - SSH tunnel is running
    echo.
) else (
    echo    [ERROR] Port 3307 is CLOSED - SSH tunnel is NOT running!
    echo.
    echo    Please start SSH tunnel first:
    echo    1. Run: ssh -L 3307:localhost:3306 user@hris-server
    echo    2. Or check your SSH tunnel batch file
    echo.
    goto :end
)

REM Test MySQL connection
echo [2] Testing MySQL connection to localhost:3307...
mysql -h 127.0.0.1 -P 3307 -u root -e "SELECT 1;" 2>nul
if %errorlevel% equ 0 (
    echo    [OK] MySQL connection successful
    echo.
) else (
    echo    [WARNING] MySQL connection failed
    echo    This might be normal if MySQL client is not installed
    echo.
)

REM Test with PHP
echo [3] Testing connection with PHP PDO...
php -r "$pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=HRIS_db', 'root', ''); echo '[OK] PHP PDO connection successful';" 2>nul
if %errorlevel% equ 0 (
    echo.
) else (
    echo    [ERROR] PHP PDO connection failed!
    echo    Check database credentials in .env file
    echo.
)

echo ========================================
echo   Connection Test Complete
echo ========================================
echo.

:end
pause
