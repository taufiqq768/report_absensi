# 🧪 Panduan Testing Model Employee & Koneksi Database HRIS

## 📋 Daftar Isi
1. [Test Menggunakan Browser (Paling Mudah)](#test-menggunakan-browser)
2. [Test Menggunakan Tinker](#test-menggunakan-tinker)
3. [Test Menggunakan Artisan Command](#test-menggunakan-artisan-command)
4. [Troubleshooting](#troubleshooting)

---

## 🌐 Test Menggunakan Browser (Paling Mudah)

Saya sudah membuat route khusus untuk testing. Pastikan server Laravel sudah berjalan, lalu akses URL berikut di browser:

### 1. Test Koneksi Database HRIS
```
http://localhost/report_absen/public/test/db-connection
```
**Apa yang ditest:**
- Koneksi ke database HRIS
- Konfigurasi database (host, database name, username)
- Query sederhana ke database

**Response jika berhasil:**
```json
{
  "success": true,
  "message": "Koneksi database HRIS berhasil!",
  "connection": "hris_mysql",
  "database": "hris_db_live",
  "host": "127.0.0.1"
}
```

### 2. Test Tabel Employees Exists
```
http://localhost/report_absen/public/test/employee-model
```
**Apa yang ditest:**
- Apakah tabel `employees` ada di database
- Struktur kolom tabel
- Nama kolom yang tersedia

**Response jika berhasil:**
```json
{
  "success": true,
  "message": "Tabel employees ditemukan!",
  "table_name": "employees",
  "columns": [...]
}
```

**Response jika tabel tidak ada:**
```json
{
  "success": false,
  "message": "Tabel 'employees' tidak ditemukan",
  "available_tables": [...],
  "suggestion": "Sesuaikan property $table di Model Employee"
}
```

### 3. Test Query Data Menggunakan Model
```
http://localhost/report_absen/public/test/employee-query
```
**Apa yang ditest:**
- Query data menggunakan Model Employee
- Hitung total records
- Ambil sample data (5 records pertama)

**Response jika berhasil:**
```json
{
  "success": true,
  "message": "Model Employee berhasil query data!",
  "connection_used": "hris_mysql",
  "total_records": 150,
  "sample_data": [...],
  "sample_count": 5
}
```

### 4. Test Query Berdasarkan NIK
```
http://localhost/report_absen/public/test/employee-by-nik/123456
```
Ganti `123456` dengan NIK yang ada di database Anda.

**Response jika berhasil:**
```json
{
  "success": true,
  "message": "Employee ditemukan!",
  "data": {
    "nik": "123456",
    "nama": "John Doe",
    "jabatan": "Manager",
    ...
  }
}
```

### 5. Test Scope Methods
```
http://localhost/report_absen/public/test/employee-scopes
```
**Apa yang ditest:**
- Scope method `byNiks()`
- Query multiple NIKs sekaligus

### 6. List Semua Tabel di Database HRIS
```
http://localhost/report_absen/public/test/list-tables
```
**Apa yang ditest:**
- Menampilkan semua tabel yang ada di database HRIS
- Jumlah records di setiap tabel

**Berguna untuk:**
- Mengetahui nama tabel yang benar
- Melihat struktur database HRIS

---

## 💻 Test Menggunakan Tinker

Tinker adalah REPL Laravel yang sangat berguna untuk testing cepat.

### Cara Menggunakan:

1. **Buka terminal dan jalankan:**
   ```bash
   php artisan tinker
   ```

2. **Test koneksi database:**
   ```php
   DB::connection('hris_mysql')->getPdo();
   // Jika berhasil, akan return PDO object
   ```

3. **Test query sederhana:**
   ```php
   DB::connection('hris_mysql')->select('SELECT 1 as test');
   // Output: array dengan hasil query
   ```

4. **Test Model Employee - Hitung total records:**
   ```php
   \App\Models\Employee::count();
   // Output: jumlah total records
   ```

5. **Test ambil 5 data pertama:**
   ```php
   \App\Models\Employee::limit(5)->get();
   // Output: Collection dengan 5 employee pertama
   ```

6. **Test query berdasarkan NIK:**
   ```php
   \App\Models\Employee::where('nik', '123456')->first();
   // Ganti 123456 dengan NIK yang ada
   ```

7. **Test scope method:**
   ```php
   \App\Models\Employee::byNik('123456')->first();
   ```

8. **Test query multiple NIKs:**
   ```php
   \App\Models\Employee::byNiks(['123456', '789012'])->get();
   ```

9. **Cek koneksi yang digunakan:**
   ```php
   (new \App\Models\Employee)->getConnectionName();
   // Output: "hris_mysql"
   ```

10. **List semua tabel:**
    ```php
    DB::connection('hris_mysql')->select('SHOW TABLES');
    ```

11. **Lihat struktur tabel:**
    ```php
    DB::connection('hris_mysql')->select('DESCRIBE employees');
    ```

12. **Keluar dari Tinker:**
    ```php
    exit
    // atau tekan Ctrl+C
    ```

---

## 🔧 Test Menggunakan Artisan Command

Buat command khusus untuk testing (opsional):

```bash
php artisan make:command TestEmployeeConnection
```

Kemudian edit file `app/Console/Commands/TestEmployeeConnection.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class TestEmployeeConnection extends Command
{
    protected $signature = 'test:employee';
    protected $description = 'Test Employee model connection to HRIS database';

    public function handle()
    {
        $this->info('Testing Employee Model Connection...');
        
        try {
            // Test 1: Connection
            $this->info('1. Testing database connection...');
            DB::connection('hris_mysql')->getPdo();
            $this->info('   ✓ Connection successful!');
            
            // Test 2: Count records
            $this->info('2. Counting records...');
            $count = Employee::count();
            $this->info("   ✓ Total records: {$count}");
            
            // Test 3: Sample data
            $this->info('3. Fetching sample data...');
            $employees = Employee::limit(5)->get();
            $this->table(
                ['NIK', 'Nama', 'Jabatan'],
                $employees->map(fn($e) => [
                    $e->nik ?? '-',
                    $e->nama ?? '-',
                    $e->jabatan ?? '-'
                ])
            );
            
            $this->info('✓ All tests passed!');
            
        } catch (\Exception $e) {
            $this->error('✗ Test failed!');
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
```

Jalankan dengan:
```bash
php artisan test:employee
```

---

## 🔍 Troubleshooting

### ❌ Error: "SQLSTATE[HY000] [1045] Access denied"

**Penyebab:** Username atau password salah

**Solusi:**
1. Cek file `.env`:
   ```env
   HRIS_DB_USERNAME=hris_user
   HRIS_DB_PASSWORD=your_password_here
   ```
2. Pastikan password sudah benar (ganti `xx` dengan password sebenarnya)
3. Test login manual ke MySQL:
   ```bash
   mysql -u hris_user -p -h 127.0.0.1 hris_db_live
   ```

### ❌ Error: "SQLSTATE[HY000] [2002] Connection refused"

**Penyebab:** MySQL server tidak berjalan atau host/port salah

**Solusi:**
1. Pastikan MySQL server berjalan (di Laragon, start MySQL)
2. Cek konfigurasi di `.env`:
   ```env
   HRIS_DB_HOST=127.0.0.1
   HRIS_DB_PORT=3306
   ```

### ❌ Error: "Database 'hris_db_live' doesn't exist"

**Penyebab:** Database belum dibuat atau nama salah

**Solusi:**
1. Cek apakah database ada:
   ```bash
   mysql -u root -p
   SHOW DATABASES;
   ```
2. Jika tidak ada, buat database:
   ```sql
   CREATE DATABASE hris_db_live;
   ```
3. Atau sesuaikan nama di `.env` dengan database yang ada

### ❌ Error: "Table 'employees' doesn't exist"

**Penyebab:** Nama tabel salah atau tabel belum ada

**Solusi:**
1. Akses URL: `http://localhost/report_absen/public/test/list-tables`
2. Lihat daftar tabel yang tersedia
3. Sesuaikan property `$table` di `app/Models/Employee.php`:
   ```php
   protected $table = 'nama_tabel_yang_benar';
   ```

### ❌ Error: "Class 'App\Models\Employee' not found"

**Penyebab:** Autoload belum di-refresh

**Solusi:**
```bash
composer dump-autoload
```

### ❌ Route test tidak bisa diakses

**Penyebab:** Cache route

**Solusi:**
```bash
php artisan route:clear
php artisan cache:clear
php artisan config:clear
```

---

## ✅ Checklist Testing

Gunakan checklist ini untuk memastikan semua berjalan dengan baik:

- [ ] File `.env` sudah diupdate dengan konfigurasi HRIS
- [ ] Password di `.env` sudah diganti dari `xx` ke password sebenarnya
- [ ] Test koneksi database berhasil (`/test/db-connection`)
- [ ] Tabel employees ditemukan (`/test/employee-model`)
- [ ] Model Employee bisa query data (`/test/employee-query`)
- [ ] Bisa query berdasarkan NIK (`/test/employee-by-nik/{nik}`)
- [ ] Scope methods berfungsi (`/test/employee-scopes`)

---

## 📝 Catatan Penting

1. **Route test hanya untuk development!** 
   - Jangan deploy route test ke production
   - Hapus atau disable route test sebelum deploy

2. **Keamanan:**
   - Route test tidak memerlukan autentikasi
   - Hanya gunakan di local development

3. **Performance:**
   - Test query menggunakan `limit(5)` untuk menghindari load data besar
   - Untuk production, selalu gunakan pagination

---

**Dibuat pada:** 2025-12-05  
**Untuk Proyek:** Report Absensi
