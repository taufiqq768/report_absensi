# 🚀 Quick Start - Koneksi Database HRIS via SSH

## ⚡ Langkah Cepat (5 Menit)

### 1️⃣ Edit File `start-ssh-tunnel.bat`

Buka file `start-ssh-tunnel.bat` dan edit bagian konfigurasi:

```batch
REM SSH Server Configuration
set SSH_USER=admin              ← Ganti dengan username SSH Anda
set SSH_HOST=192.168.1.100      ← Ganti dengan IP server HRIS
set SSH_PORT=22                 ← Port SSH (biasanya 22)

REM Port Forwarding Configuration
set LOCAL_PORT=3307             ← Port lokal (biarkan 3307)
set REMOTE_HOST=127.0.0.1       ← Biarkan 127.0.0.1
set REMOTE_PORT=3306            ← Port MySQL di server (biasanya 3306)
```

### 2️⃣ Jalankan SSH Tunnel

Double-click file `start-ssh-tunnel.bat`

**Atau** jalankan via terminal:
```bash
start-ssh-tunnel.bat
```

Masukkan password SSH ketika diminta.

### 3️⃣ Update File `.env`

Buka file `.env` dan update konfigurasi HRIS:

```env
# Database HRIS via SSH Tunnel
HRIS_DB_HOST=127.0.0.1
HRIS_DB_PORT=3307                    ← Sesuai LOCAL_PORT di script
HRIS_DB_DATABASE=hris_db_live        ← Nama database di server
HRIS_DB_USERNAME=hris_user           ← Username MySQL
HRIS_DB_PASSWORD=your_password       ← Password MySQL (GANTI INI!)
```

### 4️⃣ Test Koneksi

Buka browser dan akses:
```
http://localhost/report_absen/public/test/db-connection
```

**Response jika berhasil:**
```json
{
  "success": true,
  "message": "Koneksi database HRIS berhasil!"
}
```

---

## 🎯 Alternatif: Manual SSH Tunnel

Jika tidak ingin menggunakan script batch, jalankan command ini di terminal:

```bash
ssh -L 3307:127.0.0.1:3306 username@server-ip -N
```

**Contoh:**
```bash
ssh -L 3307:127.0.0.1:3306 admin@192.168.1.100 -N
```

Ganti:
- `admin` = username SSH Anda
- `192.168.1.100` = IP server HRIS

---

## 📋 Checklist

- [ ] Edit `start-ssh-tunnel.bat` dengan konfigurasi yang benar
- [ ] Jalankan `start-ssh-tunnel.bat`
- [ ] Masukkan password SSH
- [ ] Update file `.env` dengan konfigurasi HRIS
- [ ] Ganti password di `.env` dengan password yang benar
- [ ] Test koneksi via browser
- [ ] Koneksi berhasil! ✅

---

## ❓ Troubleshooting Cepat

### ❌ "ssh: command not found"

**Solusi:** Install OpenSSH atau gunakan Git Bash

### ❌ "Connection refused"

**Solusi:** 
- Cek IP server sudah benar
- Pastikan SSH service berjalan di server
- Cek firewall

### ❌ "Access denied" (SSH)

**Solusi:**
- Cek username SSH sudah benar
- Cek password SSH sudah benar

### ❌ "Access denied" (MySQL)

**Solusi:**
- Cek username MySQL di `.env`
- Cek password MySQL di `.env`
- Pastikan user punya akses ke database

---

## 💡 Tips

1. **Jendela SSH Tunnel harus tetap terbuka** selama aplikasi berjalan
2. **Jangan close** terminal/command prompt yang menjalankan SSH tunnel
3. **Buat shortcut** `start-ssh-tunnel.bat` di desktop untuk akses cepat
4. **Gunakan SSH Key** untuk tidak perlu input password setiap kali

---

## 📞 Informasi yang Dibutuhkan dari Admin Server

Minta informasi berikut dari admin server HRIS:

1. **IP Address Server:** `_______________`
2. **SSH Username:** `_______________`
3. **SSH Password:** `_______________`
4. **SSH Port:** `_______________` (biasanya 22)
5. **Database Name:** `_______________`
6. **MySQL Username:** `_______________`
7. **MySQL Password:** `_______________`
8. **MySQL Port:** `_______________` (biasanya 3306)

---

**Butuh bantuan?** Lihat dokumentasi lengkap di `SETUP_SSH_DATABASE.md`
