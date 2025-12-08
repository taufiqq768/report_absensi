# 🔐 Setup Koneksi Database HRIS via SSH

## 📖 Ringkasan

Dokumentasi ini menjelaskan cara setup koneksi ke database HRIS yang berada di server remote menggunakan SSH tunnel untuk aplikasi Report Absensi.

---

## 🚀 Quick Start (5 Menit)

### 1. Edit Konfigurasi

Buka file `start-ssh-tunnel.bat` dan edit:

```batch
set SSH_USER=your_username      # ← Username SSH Anda
set SSH_HOST=192.168.1.100      # ← IP Server HRIS
```

### 2. Jalankan SSH Tunnel

Double-click `start-ssh-tunnel.bat` dan masukkan password SSH.

### 3. Update `.env`

```env
HRIS_DB_HOST=127.0.0.1
HRIS_DB_PORT=3307
HRIS_DB_DATABASE=hris_db_live
HRIS_DB_USERNAME=hris_user
HRIS_DB_PASSWORD=your_password_here  # ← GANTI!
```

### 4. Test Koneksi

Buka browser: `http://localhost/report_absen/public/test/db-connection`

✅ **Jika response `"success": true` → Setup berhasil!**

---

## 📁 File-File Penting

| File | Deskripsi |
|------|-----------|
| `start-ssh-tunnel.bat` | Script untuk membuat SSH tunnel |
| `ssh-config.txt` | Template konfigurasi SSH & Database |
| `CHECKLIST.txt` | Checklist setup yang bisa diprint |
| `QUICKSTART_SSH.md` | Panduan cepat 5 menit |
| `SETUP_STEP_BY_STEP.md` | Panduan detail step by step |
| `SETUP_SSH_DATABASE.md` | Dokumentasi lengkap semua metode |
| `TESTING_EMPLOYEE_MODEL.md` | Panduan testing koneksi |

---

## 📚 Dokumentasi

### Untuk Pemula
1. **Baca:** `CHECKLIST.txt` - Print dan ikuti step by step
2. **Baca:** `QUICKSTART_SSH.md` - Panduan cepat
3. **Baca:** `SETUP_STEP_BY_STEP.md` - Panduan detail

### Untuk Advanced User
1. **Baca:** `SETUP_SSH_DATABASE.md` - Semua metode koneksi
2. **Baca:** `TESTING_EMPLOYEE_MODEL.md` - Testing & troubleshooting

---

## 🎯 Langkah-Langkah Setup

### Step 1: Persiapan
- [ ] Kumpulkan informasi SSH (IP, username, password)
- [ ] Kumpulkan informasi MySQL (database, username, password)

### Step 2: Konfigurasi
- [ ] Edit `start-ssh-tunnel.bat`
- [ ] Edit `ssh-config.txt` (opsional)

### Step 3: Test SSH
- [ ] Test SSH connection: `ssh username@server-ip`

### Step 4: Jalankan Tunnel
- [ ] Jalankan `start-ssh-tunnel.bat`
- [ ] Masukkan password SSH
- [ ] Biarkan jendela terbuka

### Step 5: Update .env
- [ ] Edit file `.env`
- [ ] Set konfigurasi HRIS database
- [ ] **GANTI password dari placeholder!**

### Step 6: Test Koneksi
- [ ] Akses `/test/db-connection`
- [ ] Cek response `success: true`

### Step 7: Test Model
- [ ] Akses `/test/employee-model`
- [ ] Akses `/test/employee-query`
- [ ] Sesuaikan nama tabel jika perlu

---

## 🧪 URL Testing

Setelah setup selesai, test dengan URL berikut:

| URL | Fungsi |
|-----|--------|
| `/test/db-connection` | Test koneksi database HRIS |
| `/test/employee-model` | Cek tabel employees exists |
| `/test/employee-query` | Query data employee |
| `/test/employee-by-nik/{nik}` | Cari employee by NIK |
| `/test/list-tables` | List semua tabel di database |

**Base URL:**
- Development: `http://localhost:8000`
- Laragon: `http://localhost/report_absen/public`

---

## 🔧 Konfigurasi

### File `.env`

```env
# Database HRIS via SSH Tunnel
HRIS_DB_HOST=127.0.0.1
HRIS_DB_PORT=3307
HRIS_DB_DATABASE=hris_db_live
HRIS_DB_USERNAME=hris_user
HRIS_DB_PASSWORD=your_password_here
```

### SSH Tunnel Command

```bash
ssh -L 3307:127.0.0.1:3306 username@server-ip -N -o ServerAliveInterval=60
```

**Penjelasan:**
- `-L 3307:127.0.0.1:3306` = Forward port MySQL
- `username@server-ip` = Kredensial SSH
- `-N` = Tidak execute command
- `-o ServerAliveInterval=60` = Keep connection alive

---

## 🔍 Troubleshooting

### ❌ "ssh: command not found"
**Solusi:** Install OpenSSH atau gunakan Git Bash

### ❌ "Connection refused"
**Solusi:** Cek IP server, port SSH, firewall

### ❌ "Access denied" (SSH)
**Solusi:** Cek username/password SSH

### ❌ "Access denied" (MySQL)
**Solusi:** Cek username/password MySQL di `.env`

### ❌ "Database doesn't exist"
**Solusi:** Cek nama database di `.env`, akses `/test/list-tables`

**Troubleshooting lengkap:** Lihat `SETUP_STEP_BY_STEP.md`

---

## 💡 Tips Penggunaan

### Workflow Harian

**Pagi (Mulai Kerja):**
1. Jalankan `start-ssh-tunnel.bat`
2. Masukkan password SSH
3. Minimize jendela (jangan tutup!)

**Siang (Kerja Normal):**
- SSH tunnel berjalan di background
- Aplikasi bisa akses database HRIS

**Sore (Selesai Kerja):**
1. Tekan Ctrl+C di jendela SSH tunnel
2. Tutup jendela

### Membuat Shortcut

1. Klik kanan `start-ssh-tunnel.bat`
2. Create shortcut
3. Pindahkan ke Desktop
4. Rename: "HRIS SSH Tunnel"

---

## 📝 Catatan Penting

⚠️ **Jendela SSH Tunnel harus tetap terbuka** selama aplikasi berjalan

⚠️ **Password di `.env` harus diganti** dari placeholder

⚠️ **Jangan commit file `.env`** ke Git repository

💡 **Gunakan SSH Key** untuk tidak perlu input password setiap kali

💡 **Buat shortcut** untuk akses cepat

---

## 🎯 Next Steps

Setelah setup berhasil:

1. **Sesuaikan Model Employee**
   - Edit `app/Models/Employee.php`
   - Sesuaikan nama tabel dan kolom

2. **Implementasi Join Data**
   - Lihat contoh di `app/Http/Controllers/AbsensiControllerExample.php`
   - Implementasi di `AbsensiController.php`

3. **Update View & Export**
   - Tambahkan kolom dari database ke view
   - Update export Excel/PDF

---

## 📞 Bantuan

Jika masih ada masalah:

1. Cek `CHECKLIST.txt` - Pastikan semua step sudah dilakukan
2. Baca `SETUP_STEP_BY_STEP.md` - Troubleshooting detail
3. Cek error message di response JSON
4. Hubungi admin server untuk cek konfigurasi server

---

## 📄 Lisensi & Info

**Project:** Report Absensi  
**Version:** 1.0  
**Setup Date:** 2025-12-05  
**Framework:** Laravel 11  

---

## ✅ Checklist Akhir

Setup berhasil jika:

- [x] SSH Tunnel berjalan tanpa error
- [x] File `.env` sudah diupdate dengan benar
- [x] Password MySQL sudah diganti dari placeholder
- [x] Test `/test/db-connection` return `success: true`
- [x] Model Employee bisa query data
- [x] Aplikasi bisa akses database HRIS

**🎉 Selamat! Setup SSH Database HRIS berhasil!**

---

**Dokumentasi dibuat oleh:** Antigravity AI  
**Untuk:** Report Absensi - Database HRIS Integration
