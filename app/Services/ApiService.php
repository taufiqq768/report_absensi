<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Exception;

class ApiService
{
    private const TOKEN_URL = 'http://hcis.holding-perkebunan.com/api/generate_token_api';
    private const ABSENSI_URL = 'http://hcis.holding-perkebunan.com/api/absensi/get-n1';
    private const TIMEOUT = 60; // Increased to 60 seconds
    private const CONNECTION_TIMEOUT = 30; // Connection timeout
    private const MAX_RETRIES = 2; // Number of retry attempts

    /**
     * Generate authentication token with retry logic
     */
    public function generateToken(string $username, string $password): string
    {
        $lastException = null;

        // Retry logic
        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                Log::info("Token generation attempt {$attempt} for user: {$username}");

                $response = Http::timeout(self::TIMEOUT)
                    ->connectTimeout(self::CONNECTION_TIMEOUT)
                    ->retry(2, 1000) // Retry 2 times with 1 second delay
                    ->post(self::TOKEN_URL, [
                        'username' => $username,
                        'password' => $password,
                    ]);

                if (!$response->successful()) {
                    if ($response->status() === 401) {
                        throw new Exception('Username atau password salah');
                    }
                    throw new Exception('Gagal generate token: HTTP ' . $response->status());
                }

                $data = $response->json();
                $token = $data['token'] ?? $data['access_token'] ?? null;

                if (!$token) {
                    throw new Exception('Token tidak ditemukan dalam response API');
                }

                Log::info("Token generated successfully for user: {$username}");
                return $token;

            } catch (ConnectionException $e) {
                $lastException = $e;
                $errorMsg = 'Koneksi ke server API gagal. ';

                if (str_contains($e->getMessage(), 'timed out')) {
                    $errorMsg .= 'Server tidak merespon dalam waktu yang ditentukan (timeout).';
                } elseif (str_contains($e->getMessage(), 'Could not resolve host')) {
                    $errorMsg .= 'Tidak dapat menemukan server API. Periksa koneksi internet Anda.';
                } else {
                    $errorMsg .= 'Periksa koneksi internet atau hubungi administrator.';
                }

                Log::error("API Connection Error (Attempt {$attempt})", [
                    'username' => $username,
                    'error' => $e->getMessage(),
                    'url' => self::TOKEN_URL
                ]);

                // If this is the last attempt, throw the error
                if ($attempt === self::MAX_RETRIES) {
                    throw new Exception($errorMsg . " (Percobaan {$attempt}/{$attempt})");
                }

                // Wait before retrying (exponential backoff)
                sleep($attempt);

            } catch (Exception $e) {
                $lastException = $e;
                Log::error("API Token Generation Error (Attempt {$attempt})", [
                    'username' => $username,
                    'error' => $e->getMessage()
                ]);

                // Don't retry for authentication errors
                if (str_contains($e->getMessage(), 'password salah')) {
                    throw $e;
                }

                // If this is the last attempt, throw the error
                if ($attempt === self::MAX_RETRIES) {
                    throw $e;
                }

                // Wait before retrying
                sleep($attempt);
            }
        }

        // This should never be reached, but just in case
        throw $lastException ?? new Exception('Gagal generate token setelah beberapa percobaan');
    }

    /**
     * Get attendance data with retry logic
     */
    public function getAbsensiData(string $token, array $filters): array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                // Map filter dari frontend ke format yang diharapkan API eksternal
                $payload = [
                    'regional' => $filters['regional'] ?? null,
                    'psa' => $filters['psa'] ?? null,
                    'dari' => $filters['dari_tanggal'] ?? null,
                    'sampai' => $filters['sampai_tanggal'] ?? null,
                    'user' => $filters['user'] ?? null,
                ];

                Log::info("Fetching absensi data (Attempt {$attempt})", ['payload' => $payload]);

                // Kirim sebagai application/x-www-form-urlencoded tanpa nesting "filter"
                $response = Http::timeout(self::TIMEOUT)
                    ->connectTimeout(self::CONNECTION_TIMEOUT)
                    ->retry(2, 1000)
                    ->asForm()
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $token,
                        'Accept' => 'application/json',
                    ])
                    ->post(self::ABSENSI_URL, $payload);

                if (!$response->successful()) {
                    throw new Exception('Gagal mengambil data absensi: HTTP ' . $response->status());
                }

                $data = $response->json();
                Log::info('API Response received successfully', ['payload' => $payload]);

                // Return raw API response
                return $data;

            } catch (ConnectionException $e) {
                $lastException = $e;
                $errorMsg = 'Koneksi ke server API gagal saat mengambil data absensi. ';

                if (str_contains($e->getMessage(), 'timed out')) {
                    $errorMsg .= 'Server tidak merespon dalam waktu yang ditentukan.';
                } else {
                    $errorMsg .= 'Periksa koneksi internet Anda.';
                }

                Log::error("API Connection Error (Attempt {$attempt})", [
                    'filters' => $filters,
                    'error' => $e->getMessage()
                ]);

                if ($attempt === self::MAX_RETRIES) {
                    throw new Exception($errorMsg);
                }

                sleep($attempt);

            } catch (Exception $e) {
                $lastException = $e;
                Log::error("API Absensi Data Error (Attempt {$attempt})", [
                    'filters' => $filters,
                    'error' => $e->getMessage()
                ]);

                if ($attempt === self::MAX_RETRIES) {
                    throw $e;
                }

                sleep($attempt);
            }
        }

        throw $lastException ?? new Exception('Gagal mengambil data absensi setelah beberapa percobaan');
    }
}
