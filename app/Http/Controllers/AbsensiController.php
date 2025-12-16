<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Exception;
use App\Exports\AbsensiExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Employee;

class AbsensiController extends Controller
{
    protected $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function index()
    {
        $username = Session::get('username');

        // Set cache control headers to prevent browser caching of authenticated pages
        $response = response()
            ->view('dashboard', compact('username'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');

        return $response;
    }

    public function getData(Request $request)
    {
        try {
            $request->validate([
                'ptpn' => 'required|string',
                'psa' => 'required|string',
                'regional' => 'required|string',
                'dari_tanggal' => 'required|date',
                'sampai_tanggal' => 'required|date',
                'user' => 'required|string',
                'divisi' => 'nullable|string', // Optional, hanya untuk HEAD_OFFICE
            ]);

            $credentials = Session::get('credentials');

            if (!$credentials) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session expired. Please login again.'
                ], 401);
            }

            $token = $this->apiService->generateToken(
                $credentials['username'],
                $credentials['password']
            );

            $filters = [
                'ptpn' => $request->input('ptpn'),
                'psa' => $request->input('psa'),
                'regional' => $request->input('regional'),
                'dari_tanggal' => $request->input('dari_tanggal'),
                'sampai_tanggal' => $request->input('sampai_tanggal'),
                'user' => $request->input('user'),
            ];

            $apiResponse = $this->apiService->getAbsensiData($token, $filters);

            // Gabungkan kolom REGIONAL_GROUP dan COSTCENTER dari HRIS (Employee) ke records API
            if (
                isset($apiResponse['status']) &&
                $apiResponse['status'] === 'success' &&
                isset($apiResponse['data']['records']) &&
                is_array($apiResponse['data']['records'])
            ) {
                $records = $apiResponse['data']['records'];


                // Normalisasi NIK: jika 7 karakter, tambahkan "0" di depan (jadi 8)
                $niks = collect($records)->pluck('NIK_SAP')
                    ->filter()
                    ->map(function ($nik) {
                        $nikStr = (string) $nik;
                        return strlen($nikStr) === 7 ? ('0' . $nikStr) : $nikStr;
                    })
                    ->unique()
                    ->values()
                    ->all();

                if (!empty($niks)) {
                    try {
                        // Split into chunks to avoid timeout on large queries
                        $chunkSize = 100; // Process 100 NIKs at a time
                        $employees = collect();

                        foreach (array_chunk($niks, $chunkSize) as $nikChunk) {
                            $chunkResult = Employee::select(['nik', 'regional_grup', 'cost_center'])
                                ->whereIn('nik', $nikChunk)
                                ->get();

                            $employees = $employees->merge($chunkResult);
                        }

                        $employees = $employees->keyBy('nik');

                        $merged = array_map(function ($row) use ($employees) {
                            $nikSap = $row['NIK_SAP'] ?? null;
                            $nikKey = null;
                            if ($nikSap !== null) {
                                $nikStr = (string) $nikSap;
                                $nikKey = strlen($nikStr) === 7 ? ('0' . $nikStr) : $nikStr;
                            }
                            $emp = $nikKey ? $employees->get($nikKey) : null;
                            $row['REGIONAL'] = $emp->regional_grup ?? null;
                            $row['DIVISI'] = $emp->cost_center ?? null;
                            return $row;
                        }, $records);

                        // Filter berdasarkan Divisi jika Regional = HEAD_OFFICE dan Divisi dipilih
                        $regional = $request->input('regional');
                        $divisi = $request->input('divisi');

                        if ($regional === 'HEAD_OFFICE' && !empty($divisi)) {
                            $merged = array_filter($merged, function ($row) use ($divisi) {
                                return isset($row['DIVISI']) && $row['DIVISI'] === $divisi;
                            });
                            // Re-index array after filtering
                            $merged = array_values($merged);
                        }

                        $apiResponse['data']['records'] = $merged;
                        $apiResponse['data']['total_records'] = count($merged);

                    } catch (\Exception $dbError) {
                        // Log database error but continue with API data only
                        \Log::error('HRIS Database Error', [
                            'error' => $dbError->getMessage(),
                            'niks_count' => count($niks)
                        ]);

                        // Return API data without REGIONAL and DIVISI columns
                        // Better than failing completely
                    }
                }
            }

            return response()->json($apiResponse);

        } catch (Exception $e) {
            // \Log::error('AbsensiController getData Error', [
            //     'message' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        try {
            $request->validate([
                'ptpn' => 'required|string',
                'psa' => 'required|string',
                'regional' => 'required|string',
                'dari_tanggal' => 'required|date',
                'sampai_tanggal' => 'required|date',
                'user' => 'required|string',
                'divisi' => 'nullable|string', // Optional, hanya untuk HEAD_OFFICE
            ]);

            $credentials = Session::get('credentials');

            if (!$credentials) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session expired. Please login again.'
                ], 401);
            }

            $token = $this->apiService->generateToken(
                $credentials['username'],
                $credentials['password']
            );

            $filters = [
                'ptpn' => $request->input('ptpn'),
                'psa' => $request->input('psa'),
                'regional' => $request->input('regional'),
                'dari_tanggal' => $request->input('dari_tanggal'),
                'sampai_tanggal' => $request->input('sampai_tanggal'),
                'user' => $request->input('user'),
            ];

            $apiResponse = $this->apiService->getAbsensiData($token, $filters);

            if ($apiResponse['status'] === 'success' && isset($apiResponse['data']['records'])) {
                $data = $apiResponse['data']['records'];

                // Gabungkan kolom REGIONAL_GROUP dan COSTCENTER dari HRIS (Employee)
                // Normalisasi NIK: jika 7 karakter, tambahkan "0" di depan (jadi 8)
                $niks = collect($data)->pluck('NIK_SAP')
                    ->filter()
                    ->map(function ($nik) {
                        $nikStr = (string) $nik;
                        return strlen($nikStr) === 7 ? ('0' . $nikStr) : $nikStr;
                    })
                    ->unique()
                    ->values()
                    ->all();

                if (!empty($niks)) {
                    $employees = Employee::select(['nik', 'regional_grup', 'cost_center'])
                        ->whereIn('nik', $niks)
                        ->get()
                        ->keyBy('nik');

                    $data = array_map(function ($row) use ($employees) {
                        $nikSap = $row['NIK_SAP'] ?? null;
                        $nikKey = null;
                        if ($nikSap !== null) {
                            $nikStr = (string) $nikSap;
                            $nikKey = strlen($nikStr) === 7 ? ('0' . $nikStr) : $nikStr;
                        }
                        $emp = $nikKey ? $employees->get($nikKey) : null;
                        $row['REGIONAL'] = $emp->regional_grup ?? null;
                        $row['DIVISI'] = $emp->cost_center ?? null;
                        return $row;
                    }, $data);

                    // Filter berdasarkan Divisi jika Regional = HEAD_OFFICE dan Divisi dipilih
                    $regional = $request->input('regional');
                    $divisi = $request->input('divisi');

                    if ($regional === 'HEAD_OFFICE' && !empty($divisi)) {
                        $data = array_filter($data, function ($row) use ($divisi) {
                            return isset($row['DIVISI']) && $row['DIVISI'] === $divisi;
                        });
                        // Re-index array after filtering
                        $data = array_values($data);
                    }
                }

                // Get display columns filter
                $displayColumns = $request->input('display_columns');
                if ($displayColumns) {
                    $displayColumns = json_decode($displayColumns, true);
                    if (is_array($displayColumns) && count($displayColumns) > 0) {
                        $data = array_map(function ($record) use ($displayColumns) {
                            return array_intersect_key($record, array_flip($displayColumns));
                        }, $data);
                    }
                }

                $filename = 'laporan-absensi-' . date('Y-m-d-His') . '.xlsx';

                return Excel::download(new AbsensiExport($data), $filename);
            }

            return response()->json([
                'success' => false,
                'message' => 'No data available to export'
            ], 404);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            $request->validate([
                'ptpn' => 'required|string',
                'psa' => 'required|string',
                'regional' => 'required|string',
                'dari_tanggal' => 'required|date',
                'sampai_tanggal' => 'required|date',
                'user' => 'required|string',
                'divisi' => 'nullable|string', // Optional, hanya untuk HEAD_OFFICE
            ]);

            $credentials = Session::get('credentials');

            if (!$credentials) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session expired. Please login again.'
                ], 401);
            }

            $token = $this->apiService->generateToken(
                $credentials['username'],
                $credentials['password']
            );

            $filters = [
                'ptpn' => $request->input('ptpn'),
                'psa' => $request->input('psa'),
                'regional' => $request->input('regional'),
                'dari_tanggal' => $request->input('dari_tanggal'),
                'sampai_tanggal' => $request->input('sampai_tanggal'),
                'user' => $request->input('user'),
            ];

            $apiResponse = $this->apiService->getAbsensiData($token, $filters);

            if ($apiResponse['status'] === 'success' && isset($apiResponse['data']['records'])) {
                $data = $apiResponse['data']['records'];

                // Normalisasi NIK: jika 7 karakter, tambahkan "0" di depan (jadi 8)
                $niks = collect($data)->pluck('NIK_SAP')
                    ->filter()
                    ->map(function ($nik) {
                        $nikStr = (string) $nik;
                        return strlen($nikStr) === 7 ? ('0' . $nikStr) : $nikStr;
                    })
                    ->unique()
                    ->values()
                    ->all();

                $employees = \App\Models\Employee::select(['nik', 'regional_grup', 'cost_center'])
                    ->whereIn('nik', $niks)
                    ->get()
                    ->keyBy('nik');

                $data = array_map(function ($row) use ($employees) {
                    $nikSap = $row['NIK_SAP'] ?? null;
                    $nikKey = null;
                    if ($nikSap !== null) {
                        $nikStr = (string) $nikSap;
                        $nikKey = strlen($nikStr) === 7 ? ('0' . $nikStr) : $nikStr;
                    }
                    $emp = $nikKey ? $employees->get($nikKey) : null;
                    $row['REGIONAL'] = $emp->regional_grup ?? null;
                    $row['DIVISI'] = $emp->cost_center ?? null;
                    return $row;
                }, $data);

                // Filter berdasarkan Divisi jika Regional = HEAD_OFFICE dan Divisi dipilih
                $regional = $request->input('regional');
                $divisi = $request->input('divisi');

                if ($regional === 'HEAD_OFFICE' && !empty($divisi)) {
                    $data = array_filter($data, function ($row) use ($divisi) {
                        return isset($row['DIVISI']) && $row['DIVISI'] === $divisi;
                    });
                    // Re-index array after filtering
                    $data = array_values($data);
                }

                // Get display columns filter
                $displayColumns = $request->input('display_columns');
                $filteredHeaders = [];

                if ($displayColumns) {
                    $displayColumns = json_decode($displayColumns, true);
                    if (is_array($displayColumns) && count($displayColumns) > 0) {
                        $data = array_map(function ($record) use ($displayColumns) {
                            return array_intersect_key($record, array_flip($displayColumns));
                        }, $data);
                        $filteredHeaders = $displayColumns;
                    }
                }

                $headers = !empty($filteredHeaders) ? $filteredHeaders : (!empty($data) ? array_keys($data[0]) : []);
                $formattedHeaders = array_map(function ($header) {
                    return ucwords(str_replace('_', ' ', $header));
                }, $headers);

                $pdf = Pdf::loadView('exports.absensi-pdf', [
                    'data' => $data,
                    'headers' => $headers,
                    'formattedHeaders' => $formattedHeaders,
                    'filters' => $filters
                ]);

                $pdf->setPaper('a4', 'landscape');

                $filename = 'laporan-absensi-' . date('Y-m-d-His') . '.pdf';

                return $pdf->download($filename);
            }

            return response()->json([
                'success' => false,
                'message' => 'No data available to export'
            ], 404);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get PSA options from Employee database (with caching)
     */
    public function getPsaOptions()
    {
        try {
            // Cache for 24 hours (1440 minutes)
            $psaOptions = \Cache::remember('psa_options', 1440 * 60, function () {
                return Employee::select('area_kode')
                    ->whereNotNull('area_kode')
                    ->where('area_kode', '!=', '')
                    ->distinct()
                    ->orderBy('area_kode', 'asc')
                    ->pluck('area_kode')
                    ->toArray();
            });

            return response()->json([
                'success' => true,
                'data' => $psaOptions
            ])
            ->header('Cache-Control', 'public, max-age=86400')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET')
            ->header('Access-Control-Allow-Headers', 'Content-Type');
        } catch (Exception $e) {
            \Log::error('PSA Options Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return 200 OK with empty data instead of 500, to prevent frontend from blocking
            return response()->json([
                'success' => true,
                'message' => 'Data PSA tidak tersedia saat ini',
                'data' => []
            ])
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'public, max-age=300');
        }
    }

    /**
     * Get Divisi options from Employee database (with caching)
     */
    public function getDivisiOptions()
    {
        try {
            // Cache for 24 hours (1440 minutes)
            $divisiOptions = \Cache::remember('divisi_options', 1440 * 60, function () {
                $query = Employee::select('cost_center')
                    ->whereNotNull('cost_center')
                    ->where('cost_center', '!=', '')
                    ->distinct()
                    ->orderBy('cost_center', 'asc');

                // Try to filter by 'div%' pattern, but catch any errors
                try {
                    // Use where with 'like' operator for case-insensitive matching
                    return $query->where('cost_center', 'like', 'div%')
                        ->pluck('cost_center')
                        ->toArray();
                } catch (\Exception $e) {
                    // If the above fails, return all cost_center values
                    \Log::warning('Divisi filter (div%) failed, returning all cost_center values', [
                        'error' => $e->getMessage()
                    ]);
                    return Employee::select('cost_center')
                        ->whereNotNull('cost_center')
                        ->where('cost_center', '!=', '')
                        ->distinct()
                        ->orderBy('cost_center', 'asc')
                        ->pluck('cost_center')
                        ->toArray();
                }
            });

            return response()->json([
                'success' => true,
                'data' => $divisiOptions
            ])
            ->header('Cache-Control', 'public, max-age=86400')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET')
            ->header('Access-Control-Allow-Headers', 'Content-Type');
        } catch (Exception $e) {
            \Log::error('Divisi Options Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return 200 OK with empty data instead of 500, to prevent frontend from blocking
            return response()->json([
                'success' => true,
                'message' => 'Data Divisi tidak tersedia saat ini',
                'data' => []
            ])
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'public, max-age=300');
        }
    }

}
