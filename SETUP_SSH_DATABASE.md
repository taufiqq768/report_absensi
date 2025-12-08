# 🔐 Setup Koneksi Database HRIS via SSH

## 📋 Pilihan Koneksi

Ada 2 cara untuk koneksi ke database HRIS yang berada di server remote:

1. **SSH Tunnel Manual** (Recommended - Paling Mudah)
2. **SSH Tunnel via Laravel Package** (Advanced)

---

## 🚀 Metode 1: SSH Tunnel Manual (Recommended)

Metode ini menggunakan SSH tunnel untuk forward port MySQL dari server remote ke localhost.

### Langkah-langkah:

#### 1. Buat SSH Tunnel

Buka terminal/PowerShell dan jalankan:

```bash
ssh -L 3307:127.0.0.1:3306 username@remote-server-ip -N
```

**Penjelasan:**
- `-L 3307:127.0.0.1:3306` = Forward port 3306 di remote server ke port 3307 di localhost
- `username` = Username SSH Anda di server remote
- `remote-server-ip` = IP address server HRIS
- `-N` = Tidak execute command, hanya buat tunnel

**Contoh:**
```bash
ssh -L 3307:127.0.0.1:3306 admin@192.168.1.100 -N
```

#### 2. Update File `.env`

Setelah SSH tunnel berjalan, update konfigurasi di `.env`:

```env
# Database HRIS via SSH Tunnel
HRIS_DB_HOST=127.0.0.1
HRIS_DB_PORT=3307          # Port lokal yang di-forward
HRIS_DB_DATABASE=hris_db_live
HRIS_DB_USERNAME=hris_user
HRIS_DB_PASSWORD=your_password_here
```

#### 3. Test Koneksi

Akses: `http://localhost/report_absen/public/test/db-connection`

#### 4. Keep Tunnel Running

SSH tunnel harus tetap berjalan selama aplikasi digunakan. Untuk keep alive, gunakan:

```bash
ssh -L 3307:127.0.0.1:3306 username@remote-server-ip -N -o ServerAliveInterval=60
```

### Membuat SSH Tunnel Otomatis (Windows)

Buat file batch `start-ssh-tunnel.bat`:

```batch
@echo off
echo Starting SSH Tunnel to HRIS Database...
ssh -L 3307:127.0.0.1:3306 username@remote-server-ip -N -o ServerAliveInterval=60
pause
```

Jalankan file ini setiap kali ingin mengakses database HRIS.

---

## 🔧 Metode 2: Koneksi Remote Langsung

Jika server MySQL mengizinkan koneksi remote langsung (tanpa SSH), gunakan konfigurasi ini:

### Update File `.env`

```env
# Database HRIS - Remote Connection
HRIS_DB_HOST=192.168.1.100    # IP server HRIS
HRIS_DB_PORT=3306
HRIS_DB_DATABASE=hris_db_live
HRIS_DB_USERNAME=hris_user
HRIS_DB_PASSWORD=your_password_here
```

### Pastikan Server MySQL Mengizinkan Remote Connection

Di server HRIS, pastikan:

1. **MySQL bind-address** tidak hanya localhost:
   ```bash
   # Edit file my.cnf atau my.ini
   bind-address = 0.0.0.0
   ```

2. **User punya akses remote**:
   ```sql
   -- Login ke MySQL di server
   CREATE USER 'hris_user'@'%' IDENTIFIED BY 'password';
   GRANT ALL PRIVILEGES ON hris_db_live.* TO 'hris_user'@'%';
   FLUSH PRIVILEGES;
   ```

3. **Firewall mengizinkan port 3306**

---

## 🎯 Metode 3: SSH Tunnel via Laravel Package

Untuk automasi SSH tunnel di dalam Laravel, gunakan package `larapack/ssh-tunnel`.

### 1. Install Package

```bash
composer require larapack/ssh-tunnel
```

### 2. Update `config/database.php`

```php
'hris_mysql' => [
    'driver' => 'mysql',
    'host' => env('HRIS_DB_HOST', '127.0.0.1'),
    'port' => env('HRIS_DB_PORT', '3306'),
    'database' => env('HRIS_DB_DATABASE', 'hris_db_live'),
    'username' => env('HRIS_DB_USERNAME', 'hris_user'),
    'password' => env('HRIS_DB_PASSWORD', ''),
    'unix_socket' => env('HRIS_DB_SOCKET', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => null,
    
    // SSH Tunnel Configuration
    'ssh' => [
        'host' => env('HRIS_SSH_HOST'),
        'port' => env('HRIS_SSH_PORT', 22),
        'user' => env('HRIS_SSH_USER'),
        'password' => env('HRIS_SSH_PASSWORD'),
        // atau gunakan key
        'key' => env('HRIS_SSH_KEY_PATH'),
        'keytext' => env('HRIS_SSH_KEY'),
        'keyphrase' => env('HRIS_SSH_PASSPHRASE'),
        'timeout' => 10,
    ],
],
```

### 3. Update `.env`

```env
# Database HRIS
HRIS_DB_HOST=127.0.0.1
HRIS_DB_PORT=3306
HRIS_DB_DATABASE=hris_db_live
HRIS_DB_USERNAME=hris_user
HRIS_DB_PASSWORD=your_db_password

# SSH Configuration
HRIS_SSH_HOST=192.168.1.100
HRIS_SSH_PORT=22
HRIS_SSH_USER=your_ssh_username
HRIS_SSH_PASSWORD=your_ssh_password
# atau gunakan SSH key
# HRIS_SSH_KEY_PATH=/path/to/private/key
```

---

## 🧪 Testing Koneksi SSH

### Test SSH Connection

```bash
# Test SSH login
ssh username@remote-server-ip

# Test SSH dengan port forwarding
ssh -L 3307:127.0.0.1:3306 username@remote-server-ip -N
```

### Test MySQL via SSH Tunnel

Setelah SSH tunnel berjalan:

```bash
# Test koneksi MySQL via tunnel
mysql -h 127.0.0.1 -P 3307 -u hris_user -p hris_db_live
```

### Test via Laravel

Akses route test:
```
http://localhost/report_absen/public/test/db-connection
```

---

## 📝 Konfigurasi `.env` Lengkap

### Untuk SSH Tunnel Manual:

```env
# Database Utama (report_absen)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=report_absen
DB_USERNAME=root
DB_PASSWORD=

# Database HRIS via SSH Tunnel
HRIS_DB_HOST=127.0.0.1
HRIS_DB_PORT=3307              # Port yang di-forward via SSH
HRIS_DB_DATABASE=hris_db_live
HRIS_DB_USERNAME=hris_user
HRIS_DB_PASSWORD=your_password_here

# Informasi Server (untuk dokumentasi)
# SSH Command: ssh -L 3307:127.0.0.1:3306 admin@192.168.1.100 -N
```

### Untuk Remote Connection Langsung:

```env
# Database Utama (report_absen)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=report_absen
DB_USERNAME=root
DB_PASSWORD=

# Database HRIS - Remote Connection
HRIS_DB_HOST=192.168.1.100     # IP Server HRIS
HRIS_DB_PORT=3306
HRIS_DB_DATABASE=hris_db_live
HRIS_DB_USERNAME=hris_user
HRIS_DB_PASSWORD=your_password_here
```

---

## 🔍 Troubleshooting

### Error: "Connection refused" saat SSH

**Solusi:**
1. Pastikan SSH service berjalan di server
2. Cek firewall mengizinkan port 22
3. Pastikan IP server benar

### Error: "Access denied" setelah SSH tunnel

**Solusi:**
1. Pastikan user MySQL punya akses dari localhost di server
2. Test login MySQL di server:
   ```bash
   mysql -u hris_user -p hris_db_live
   ```

### SSH Tunnel terputus

**Solusi:**
Gunakan autossh untuk keep connection alive:
```bash
autossh -M 0 -L 3307:127.0.0.1:3306 username@server -N -o ServerAliveInterval=60
```

### Port 3307 sudah digunakan

**Solusi:**
Gunakan port lain, misal 3308:
```bash
ssh -L 3308:127.0.0.1:3306 username@server -N
```
Update `HRIS_DB_PORT=3308` di `.env`

---

## 💡 Tips & Best Practices

1. **Gunakan SSH Key** daripada password untuk keamanan lebih baik
2. **Keep Tunnel Alive** dengan ServerAliveInterval
3. **Dokumentasikan** SSH command di `.env` sebagai comment
4. **Test Koneksi** sebelum deploy aplikasi
5. **Monitor** SSH tunnel untuk memastikan tetap aktif

---

## 📞 Informasi yang Dibutuhkan

Untuk setup SSH connection, Anda perlu informasi berikut:

- [ ] IP Address server HRIS
- [ ] SSH Username
- [ ] SSH Password atau SSH Key
- [ ] SSH Port (default: 22)
- [ ] MySQL Database Name
- [ ] MySQL Username
- [ ] MySQL Password
- [ ] MySQL Port di server (default: 3306)

---

**Dibuat pada:** 2025-12-05  
**Untuk Proyek:** Report Absensi
