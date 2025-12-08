# 🚀 PANDUAN SETUP KONEKSI SSH - STEP BY STEP

## ✅ Checklist Persiapan

Sebelum mulai, pastikan Anda sudah punya informasi berikut:

- [ ] IP Address server HRIS (contoh: 192.168.1.100)
- [ ] SSH Username (contoh: admin)
- [ ] SSH Password
- [ ] SSH Port (biasanya 22)
- [ ] MySQL Database Name (contoh: hris_db_live)
- [ ] MySQL Username (contoh: hris_user)
- [ ] MySQL Password

---

## 📝 STEP 1: Edit File Konfigurasi SSH

### 1.1 Buka file `ssh-config.txt`

File ini berisi template konfigurasi yang perlu Anda edit.

### 1.2 Edit bagian berikut dengan informasi Anda:

```bash
SSH_HOST=192.168.1.100          # ← Ganti dengan IP server HRIS Anda
SSH_USER=admin                  # ← Ganti dengan username SSH Anda
SSH_PORT=22                     # ← Ganti jika port SSH berbeda

DB_DATABASE=hris_db_live        # ← Ganti dengan nama database
DB_USERNAME=hris_user           # ← Ganti dengan username MySQL
DB_PASSWORD=your_password       # ← Ganti dengan password MySQL
```

### 1.3 Simpan file

---

## 🔧 STEP 2: Edit Script SSH Tunnel

### 2.1 Buka file `start-ssh-tunnel.bat`

### 2.2 Cari bagian "KONFIGURASI" dan edit:

```batch
REM SSH Server Configuration
set SSH_USER=admin              ← Ganti dengan username SSH Anda
set SSH_HOST=192.168.1.100      ← Ganti dengan IP server HRIS
set SSH_PORT=22                 ← Ganti jika port SSH berbeda
```

**Contoh setelah diedit:**
```batch
set SSH_USER=johndoe
set SSH_HOST=10.20.30.40
set SSH_PORT=22
```

### 2.3 Simpan file

---

## 🌐 STEP 3: Test SSH Connection

Sebelum lanjut, test dulu apakah SSH connection bisa:

### 3.1 Buka PowerShell atau Command Prompt

### 3.2 Jalankan command:

```bash
ssh username@server-ip
```

**Ganti dengan informasi Anda, contoh:**
```bash
ssh admin@192.168.1.100
```

### 3.3 Masukkan password SSH

### 3.4 Jika berhasil login, ketik `exit` untuk keluar

✅ **Jika berhasil login, lanjut ke Step 4**  
❌ **Jika gagal, troubleshoot dulu (lihat bagian Troubleshooting di bawah)**

---

## 🚀 STEP 4: Jalankan SSH Tunnel

### 4.1 Double-click file `start-ssh-tunnel.bat`

**ATAU** jalankan via terminal:
```bash
cd c:\laragon\www\report_absen
start-ssh-tunnel.bat
```

### 4.2 Masukkan password SSH ketika diminta

### 4.3 Jika berhasil, akan muncul pesan:

```
============================================
  SSH Tunnel - Database HRIS
============================================

Konfigurasi SSH Tunnel:

  SSH Server    : admin@192.168.1.100:22
  Local Port    : 3307
  Remote MySQL  : 127.0.0.1:3306

============================================

Memulai SSH Tunnel...

CATATAN:
- Jendela ini harus tetap terbuka selama aplikasi berjalan
- Tekan Ctrl+C untuk menghentikan tunnel
- Update file .env dengan: HRIS_DB_PORT=3307

============================================
```

### 4.4 **JANGAN TUTUP JENDELA INI!**

Biarkan jendela command prompt tetap terbuka. SSH tunnel harus aktif selama aplikasi berjalan.

---

## ⚙️ STEP 5: Update File `.env`

### 5.1 Buka file `.env` di root project

### 5.2 Cari bagian konfigurasi database HRIS

### 5.3 Update atau tambahkan baris berikut:

```env
# Database HRIS via SSH Tunnel
HRIS_DB_HOST=127.0.0.1
HRIS_DB_PORT=3307
HRIS_DB_DATABASE=hris_db_live        # ← Sesuaikan dengan database Anda
HRIS_DB_USERNAME=hris_user           # ← Sesuaikan dengan username Anda
HRIS_DB_PASSWORD=your_password_here  # ← GANTI dengan password MySQL yang benar!
```

**⚠️ PENTING:** Ganti `your_password_here` dengan password MySQL yang sebenarnya!

### 5.4 Simpan file `.env`

---

## 🧪 STEP 6: Test Koneksi Database

### 6.1 Pastikan SSH Tunnel masih berjalan (jendela command prompt masih terbuka)

### 6.2 Pastikan Laravel development server berjalan

Jika belum, jalankan:
```bash
php artisan serve
```

### 6.3 Buka browser dan akses:

```
http://localhost:8000/test/db-connection
```

**ATAU jika menggunakan Laragon:**
```
http://localhost/report_absen/public/test/db-connection
```

### 6.4 Cek response:

**✅ Jika berhasil:**
```json
{
  "success": true,
  "message": "Koneksi database HRIS berhasil!",
  "connection": "hris_mysql",
  "database": "hris_db_live",
  "host": "127.0.0.1"
}
```

**❌ Jika gagal:**
```json
{
  "success": false,
  "message": "Koneksi database HRIS gagal!",
  "error": "..."
}
```

Lihat bagian Troubleshooting untuk solusi.

---

## 🎯 STEP 7: Test Model Employee

### 7.1 Akses URL test lainnya:

**Test tabel employees:**
```
http://localhost:8000/test/employee-model
```

**Test query data:**
```
http://localhost:8000/test/employee-query
```

**List semua tabel:**
```
http://localhost:8000/test/list-tables
```

### 7.2 Jika tabel "employees" tidak ditemukan:

Response akan menampilkan daftar tabel yang tersedia. Sesuaikan nama tabel di `app/Models/Employee.php`:

```php
protected $table = 'nama_tabel_yang_benar';
```

---

## 🔍 Troubleshooting

### ❌ Error: "ssh: command not found"

**Penyebab:** OpenSSH belum terinstall

**Solusi:**

**Opsi A - Install OpenSSH di Windows:**
1. Buka Settings → Apps → Optional Features
2. Klik "Add a feature"
3. Cari "OpenSSH Client"
4. Install

**Opsi B - Gunakan Git Bash:**
1. Install Git for Windows
2. Buka Git Bash
3. Jalankan command SSH dari Git Bash

**Opsi C - Manual SSH Tunnel:**
Gunakan PuTTY atau aplikasi SSH lainnya untuk membuat tunnel.

---

### ❌ Error: "Connection refused" (SSH)

**Penyebab:** 
- IP server salah
- Port SSH salah
- Firewall memblokir
- SSH service tidak berjalan di server

**Solusi:**
1. Cek IP server sudah benar
2. Cek port SSH (biasanya 22)
3. Ping server: `ping 192.168.1.100`
4. Hubungi admin server untuk cek SSH service

---

### ❌ Error: "Access denied" (SSH)

**Penyebab:** Username atau password SSH salah

**Solusi:**
1. Cek username SSH sudah benar
2. Cek password SSH sudah benar
3. Hubungi admin server untuk reset password

---

### ❌ Error: "Access denied for user 'hris_user'@'localhost'"

**Penyebab:** Username atau password MySQL salah

**Solusi:**
1. Cek `HRIS_DB_USERNAME` di `.env`
2. Cek `HRIS_DB_PASSWORD` di `.env`
3. Pastikan password sudah diganti dari placeholder
4. Test login MySQL di server:
   ```bash
   ssh admin@server-ip
   mysql -u hris_user -p hris_db_live
   ```

---

### ❌ Error: "Database 'hris_db_live' doesn't exist"

**Penyebab:** Nama database salah

**Solusi:**
1. Akses: `http://localhost:8000/test/list-tables`
2. Atau login ke server dan cek:
   ```bash
   ssh admin@server-ip
   mysql -u root -p
   SHOW DATABASES;
   ```
3. Update `HRIS_DB_DATABASE` di `.env` dengan nama yang benar

---

### ❌ SSH Tunnel terputus sendiri

**Penyebab:** Connection timeout

**Solusi:**
Script sudah include `ServerAliveInterval=60` untuk keep alive.

Jika masih terputus, gunakan autossh (jika tersedia):
```bash
autossh -M 0 -L 3307:127.0.0.1:3306 admin@server-ip -N -o ServerAliveInterval=60
```

---

### ❌ Port 3307 sudah digunakan

**Penyebab:** Port 3307 sudah dipakai aplikasi lain

**Solusi:**
1. Edit `start-ssh-tunnel.bat`, ganti `LOCAL_PORT`:
   ```batch
   set LOCAL_PORT=3308
   ```
2. Update `.env`:
   ```env
   HRIS_DB_PORT=3308
   ```

---

## 📋 Checklist Akhir

Setelah semua step selesai, pastikan:

- [ ] SSH Tunnel berjalan (jendela command prompt terbuka)
- [ ] File `.env` sudah diupdate dengan benar
- [ ] Password MySQL sudah diganti dari placeholder
- [ ] Test koneksi berhasil (response success: true)
- [ ] Model Employee bisa query data
- [ ] Aplikasi bisa akses database HRIS

---

## 💡 Tips Penggunaan Sehari-hari

### Workflow Harian:

1. **Pagi - Mulai kerja:**
   - Jalankan `start-ssh-tunnel.bat`
   - Masukkan password SSH
   - Biarkan jendela terbuka

2. **Siang - Kerja normal:**
   - SSH tunnel tetap berjalan di background
   - Aplikasi bisa akses database HRIS

3. **Sore - Selesai kerja:**
   - Tekan Ctrl+C di jendela SSH tunnel
   - Tutup jendela

### Membuat Shortcut:

1. Klik kanan `start-ssh-tunnel.bat`
2. Pilih "Create shortcut"
3. Pindahkan shortcut ke Desktop
4. Rename menjadi "HRIS SSH Tunnel"

Sekarang tinggal double-click shortcut di desktop untuk start tunnel!

---

## 🆘 Butuh Bantuan?

Jika masih ada masalah:

1. **Cek log error** di response JSON
2. **Screenshot error** dan kirim ke developer
3. **Hubungi admin server** untuk cek konfigurasi server
4. **Baca dokumentasi lengkap** di `SETUP_SSH_DATABASE.md`

---

**Setup Date:** 2025-12-05  
**Project:** Report Absensi  
**Version:** 1.0
