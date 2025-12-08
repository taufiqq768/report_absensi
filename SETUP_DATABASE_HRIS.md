# Setup Database HRIS untuk Join Data dengan API

## 📋 Langkah-langkah Setup

### 1. Update File `.env`

Tambahkan konfigurasi database HRIS ke file `.env` Anda. Buka file `.env` dan tambahkan baris berikut setelah konfigurasi database utama:

```env
# Database Utama (report_absen)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=report_absen
DB_USERNAME=root
DB_PASSWORD=

# Database HRIS (untuk join data dengan API)
HRIS_DB_HOST=127.0.0.1
HRIS_DB_PORT=3306
HRIS_DB_DATABASE=hris_db_live
HRIS_DB_USERNAME=hris_user
HRIS_DB_PASSWORD=xx
```

**⚠️ PENTING:** Ganti `xx` dengan password yang sebenarnya untuk user `hris_user`.

### 2. Konfigurasi Database (✅ Sudah Selesai)

File `config/database.php` sudah diupdate dengan koneksi database `hris_mysql`. Tidak perlu action tambahan.

### 3. Cara Menggunakan Koneksi Database HRIS

#### A. Menggunakan Query Builder

```php
use Illuminate\Support\Facades\DB;

// Query ke database HRIS
$employees = DB::connection('hris_mysql')
    ->table('employees')
    ->where('nik', '123456')
    ->get();
```

#### B. Membuat Model untuk Database HRIS

Buat model baru untuk tabel di database HRIS:

```bash
php artisan make:model Employee
```

Kemudian edit model untuk menggunakan koneksi `hris_mysql`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $connection = 'hris_mysql'; // Koneksi ke database HRIS
    protected $table = 'employees'; // Nama tabel di database HRIS
    
    // Jika tabel tidak menggunakan timestamps (created_at, updated_at)
    public $timestamps = false;
    
    protected $fillable = [
        'nik',
        'nama',
        'jabatan',
        'departemen',
        // ... kolom lainnya
    ];
}
```

### 4. Contoh Implementasi Join Data API dengan Database

#### Contoh 1: Join Data di Controller

```php
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

public function getData(Request $request)
{
    // 1. Ambil data dari API (existing code)
    $apiData = $this->apiService->getAbsensiData($token, $filters);
    
    // 2. Ambil NIK dari data API
    $niks = collect($apiData['data'])->pluck('nik')->unique()->toArray();
    
    // 3. Query data karyawan dari database HRIS
    $employees = DB::connection('hris_mysql')
        ->table('employees')
        ->whereIn('nik', $niks)
        ->get()
        ->keyBy('nik'); // Index by NIK untuk lookup cepat
    
    // 4. Join data API dengan data database
    $mergedData = collect($apiData['data'])->map(function($item) use ($employees) {
        $employee = $employees->get($item['nik']);
        
        return array_merge($item, [
            'jabatan' => $employee->jabatan ?? '-',
            'departemen' => $employee->departemen ?? '-',
            'email' => $employee->email ?? '-',
            // ... field lainnya dari database
        ]);
    });
    
    return response()->json([
        'success' => true,
        'data' => $mergedData
    ]);
}
```

#### Contoh 2: Menggunakan Eloquent Model

```php
use App\Models\Employee;

public function getData(Request $request)
{
    // 1. Ambil data dari API
    $apiData = $this->apiService->getAbsensiData($token, $filters);
    
    // 2. Ambil NIK dari data API
    $niks = collect($apiData['data'])->pluck('nik')->unique()->toArray();
    
    // 3. Query menggunakan Eloquent Model
    $employees = Employee::whereIn('nik', $niks)->get()->keyBy('nik');
    
    // 4. Join data
    $mergedData = collect($apiData['data'])->map(function($item) use ($employees) {
        $employee = $employees->get($item['nik']);
        
        if ($employee) {
            $item['jabatan'] = $employee->jabatan;
            $item['departemen'] = $employee->departemen;
            $item['email'] = $employee->email;
        }
        
        return $item;
    });
    
    return response()->json([
        'success' => true,
        'data' => $mergedData
    ]);
}
```

### 5. Testing Koneksi Database

Untuk memastikan koneksi database HRIS berfungsi, jalankan command berikut:

```bash
php artisan tinker
```

Kemudian test koneksi:

```php
// Test koneksi
DB::connection('hris_mysql')->getPdo();

// Test query
DB::connection('hris_mysql')->table('employees')->limit(5)->get();
```

Jika tidak ada error, berarti koneksi berhasil! ✅

## 🔍 Troubleshooting

### Error: SQLSTATE[HY000] [1045] Access denied

**Solusi:** 
- Pastikan username dan password di `.env` sudah benar
- Pastikan user `hris_user` memiliki akses ke database `hris_db_live`

### Error: SQLSTATE[HY000] [2002] Connection refused

**Solusi:**
- Pastikan MySQL server sedang berjalan
- Pastikan `HRIS_DB_HOST` dan `HRIS_DB_PORT` sudah benar

### Error: Database 'hris_db_live' doesn't exist

**Solusi:**
- Pastikan database `hris_db_live` sudah dibuat di MySQL
- Periksa nama database di `.env` sudah benar

## 📝 Catatan Penting

1. **Keamanan:** Jangan commit file `.env` ke repository Git
2. **Performance:** Gunakan caching untuk data yang jarang berubah
3. **Error Handling:** Selalu tambahkan try-catch untuk query database
4. **Connection Pooling:** Laravel otomatis mengelola connection pooling

## 🚀 Next Steps

Setelah setup selesai, Anda bisa:
1. Membuat model untuk tabel-tabel di database HRIS
2. Implementasi join data di `AbsensiController`
3. Update view untuk menampilkan data tambahan dari database
4. Update export Excel/PDF untuk include data dari database

---

**Dibuat pada:** 2025-12-05
**Untuk Proyek:** Report Absensi
