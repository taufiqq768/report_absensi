<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Exception;
use App\Exports\AbsensiExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

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
        return view('dashboard', compact('username'));
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

            // \Log::info('Controller received API response', ['response' => $apiResponse]);

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

                // Get display columns filter
                $displayColumns = $request->input('display_columns');
                if ($displayColumns) {
                    $displayColumns = json_decode($displayColumns, true);
                    if (is_array($displayColumns) && count($displayColumns) > 0) {
                        // Filter each record to only include selected columns
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

                // Get display columns filter
                $displayColumns = $request->input('display_columns');
                $filteredHeaders = [];

                if ($displayColumns) {
                    $displayColumns = json_decode($displayColumns, true);
                    if (is_array($displayColumns) && count($displayColumns) > 0) {
                        // Filter each record to only include selected columns
                        $data = array_map(function ($record) use ($displayColumns) {
                            return array_intersect_key($record, array_flip($displayColumns));
                        }, $data);
                        $filteredHeaders = $displayColumns;
                    }
                }

                // Get headers from first record or use filtered headers
                $headers = !empty($filteredHeaders) ? $filteredHeaders : (!empty($data) ? array_keys($data[0]) : []);

                // Format headers
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
}
