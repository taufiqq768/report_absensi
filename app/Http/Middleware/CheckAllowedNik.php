<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAllowedNik
{
    /**
     * List of allowed NIKs
     * In a real application, this might come from a database or configuration file.
     */
    protected $allowedNiks = [
        '8006045',
        '8002742'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $nik = $request->query('nik');

        if (!$nik) {
            abort(403, 'Akses ditolak. Parameter NIK diperlukan.');
        }

        if (!in_array($nik, $this->allowedNiks)) {
            abort(403, 'Akses ditolak. NIK tidak terdaftar dalam whitelist.');
        }

        return $next($request);
    }
}
