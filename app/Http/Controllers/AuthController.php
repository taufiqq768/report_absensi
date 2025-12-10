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
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['login' => $e->getMessage()]);
        }
    }

    /**
     * Logout user
     */
    public function logout()
    {
        Session::flush();
        return redirect()->route('login')
            ->with('success', 'Anda telah logout');
    }
}
