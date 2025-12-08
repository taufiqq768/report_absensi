<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model {
    /**
     * Koneksi database yang digunakan oleh model ini.
     * Menggunakan koneksi 'hris_mysql' yang terhubung ke database hris_db_live
     */
    protected $connection = 'hris_mysql';

    /**
     * Nama tabel di database HRIS
     * Sesuaikan dengan nama tabel yang sebenarnya di database Anda
     */
    protected $table = 'pegawai';

    /**
     * Primary key untuk tabel
     * Sesuaikan jika primary key bukan 'id'
     */
    protected $primaryKey = 'id';

    /**
     * Jika tabel tidak menggunakan timestamps (created_at, updated_at)
     * Set ke false
     */
    public $timestamps = false;

    /**
     * Kolom-kolom yang bisa diisi (mass assignment)
     * Sesuaikan dengan kolom yang ada di tabel Anda
     */
    protected $fillable = [
        'nik',
        'nama',
        'jabatan',
        'departemen',
        'email',
        'phone',
        'regional',
        'psa',
        // Tambahkan kolom lainnya sesuai kebutuhan
    ];

    /**
     * Kolom-kolom yang di-cast ke tipe data tertentu
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope untuk filter berdasarkan NIK
     */
    public function scopeByNik($query, $nik)
    {
        return $query->where('nik', $nik);
    }

    /**
     * Scope untuk filter berdasarkan regional
     */
    public function scopeByRegional($query, $regional)
    {
        return $query->where('regional', $regional);
    }

    /**
     * Scope untuk filter berdasarkan PSA
     */
    public function scopeByPsa($query, $psa)
    {
        return $query->where('psa', $psa);
    }

    /**
     * Scope untuk filter berdasarkan multiple NIKs
     */
    public function scopeByNiks($query, array $niks)
    {
        return $query->whereIn('nik', $niks);
    }
}
