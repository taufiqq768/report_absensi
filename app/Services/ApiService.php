<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ApiService
{
    private const TOKEN_URL = 'http://hcis.holding-perkebunan.com/api/generate_token_api';
    private const ABSENSI_URL = 'http://hcis.holding-perkebunan.com/api/absensi/get-n1';

    public function generateToken(string $username, string $password): string
    {
        try {
            $response = Http::timeout(30)->post(self::TOKEN_URL, [
                'username' => $username,
                'password' => $password,
            ]);

            if (!$response->successful()) {
                if ($response->status() === 401) {
                    throw new Exception('Username atau password salah');
                }
                throw new Exception('Gagal generate token: ' . $response->status());
            }

            $data = $response->json();
            $token = $data['token'] ?? $data['access_token'] ?? null;

            if (!$token) {
                throw new Exception('Token tidak ditemukan dalam response API');
            }

            return $token;
        } catch (Exception $e) {
            Log::error('API Token Generation Error', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getAbsensiData(string $token, array $filters): array
    {
        try {
            // Map filter dari frontend ke format yang diharapkan API eksternal
            $payload = [
                'regional' => $filters['regional'] ?? null,
                'psa' => $filters['psa'] ?? null,
                'dari' => $filters['dari_tanggal'] ?? null,
                'sampai' => $filters['sampai_tanggal'] ?? null,
                'user' => $filters['user'] ?? null,
            ];

            // Kirim sebagai application/x-www-form-urlencoded tanpa nesting "filter"
            $response = Http::timeout(30)
                ->asForm()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])
                ->post(self::ABSENSI_URL, $payload);

            if (!$response->successful()) {
                throw new Exception('Gagal mengambil data absensi: ' . $response->status());
            }

            $data = $response->json();
            Log::info('API Response', ['payload' => $payload, 'data' => $data]);

            // Return raw API response
            return $data;
        } catch (Exception $e) {
            Log::error('API Absensi Data Error', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
$rows = \DB::connection('hris_mysql')->table('pegawai')->limit(10)->get();
