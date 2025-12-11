<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Exception;

class AuthController extends Controller
{
    protected $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Show login form
     */
    public function showLoginForm()
    {
        // Redirect to dashboard if already logged in
        if (Session::has('authenticated')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Process login
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Whitelist NIK yang diizinkan untuk login
        $allowedNiks = [
            '8006045',
            '8002742',
            '8200003',
            '12020749',
            '12020633',
            '12020705',
            '9000007',
            '7002893',
            '2000065'
        ];

        $username = $request->input('username');

        // Validasi apakah NIK ada dalam whitelist
        if (!in_array($username, $allowedNiks)) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['login' => 'Akses ditolak. NIK Anda tidak diizinkan untuk login.']);
        }

        try {
            $password = $request->input('password');

            // Validate credentials by generating token
            $token = $this->apiService->generateToken($username, $password);

            // Store credentials in session
            Session::put('authenticated', true);
            Session::put('username', $username);
            Session::put('credentials', [
                'username' => $username,
                'password' => $password, // In production, consider encryption
            ]);

            return redirect()->route('dashboard')
                ->with('success', 'Login berhasil!');

        } catch (Exception $e) {
            // Log the full error for debugging
            \Log::error('Login failed', [
                'username' => $username,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Provide user-friendly error message
            $errorMessage = $e->getMessage();

            // Add helpful tips for common errors
            if (str_contains($errorMessage, 'timeout') || str_contains($errorMessage, 'timed out')) {
                $errorMessage .= ' Silakan coba lagi dalam beberapa saat.';
            }

            return back()
                ->withInput($request->only('username'))
                ->withErrors(['login' => '❌ Login Gagal: ' . $errorMessage]);
        }
    }

    /**
     * Logout user (optimized)
     */
    public function logout()
    {
        // Only remove authentication-related session data
        // This is faster than Session::flush()
        Session::forget(['authenticated', 'username', 'credentials']);

        return redirect()->route('login')
            ->with('success', 'Anda telah logout');
    }
}
