<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AbsenMasuk;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class LaporanAbsensiController extends Controller
{
    public function getlaporanByUser(Request $request)
    {
        // Define validation rules
        $rules = [
            'user_id' => 'required|integer|exists:users,id',
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
            'sortBy' => 'string|in:waktu_masuk,created_at',
            'sortOrder' => 'string|in:ASC,DESC',
            'start_date' => 'date_format:Y-m-d|required_with:end_date',
            'end_date' => [
                'date_format:Y-m-d',
                'required_with:start_date',
                'after_or_equal:start_date',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->start_date) {
                        $start = Carbon::parse($request->start_date);
                        $end = Carbon::parse($value);
                        
                        // Check if date range is not more than 1 year
                        if ($start->diffInDays($end) > 365) {
                            $fail('Date range cannot exceed 1 year.');
                        }
                    }
                }
            ]
        ];
    
        // Custom error messages
        $messages = [
            'user_id.required' => 'User ID is required.',
            'user_id.exists' => 'User ID is not valid.',
            'page.integer' => 'Page must be a number.',
            'limit.integer' => 'Limit must be a number.',
            'limit.max' => 'Maximum limit is 100 records per page.',
            'sortBy.in' => 'Invalid sort field.',
            'sortOrder.in' => 'Sort order must be ASC or DESC.',
            'start_date.date_format' => 'Start date must be in YYYY-MM-DD format.',
            'end_date.date_format' => 'End date must be in YYYY-MM-DD format.',
            'end_date.after_or_equal' => 'End date must be equal to or after start date.',
        ];
    
        // Validate request
        $validator = Validator::make($request->all(), $rules, $messages);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
    
        // Get validated data with defaults
        $validated = $validator->validated();
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $sortBy = $request->input('sortBy', 'waktu_masuk');
        $sortOrder = $request->input('sortOrder', 'DESC');
        
        try {
            $query = AbsenMasuk::with(['user', 'waktuKerja', 'shift', 'absenPulang'])
                ->where('user_id', $validated['user_id']);
            
            // Apply date range filter if dates are provided
            if (isset($validated['start_date']) && isset($validated['end_date'])) {
                $query->whereBetween('waktu_masuk', [
                    Carbon::parse($validated['start_date'])->startOfDay(),
                    Carbon::parse($validated['end_date'])->endOfDay()
                ]);
            }
            
            $AbsenMasukDanPulang = $query
                ->orderBy($sortBy, $sortOrder)
                ->paginate($limit);
    
            return response()->json([
                'status' => 'success',
                'data' => $AbsenMasukDanPulang
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching the data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getlaporanCetakByUser(Request $request): Response
    {
        try {
            // Validasi request
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'tanggal_awal' => 'required|date',
                'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
            ]);

            // Ambil data absensi
            $absensi = AbsenMasuk::with(['user', 'waktuKerja', 'shift', 'absenPulang' => function($query) {
                $query->orderBy('waktu_pulang', 'ASC');
            }])
                ->where('user_id', $request->user_id)
                ->whereBetween('waktu_masuk', [
                    $request->tanggal_awal . ' 00:00:00',
                    $request->tanggal_akhir . ' 23:59:59'
                ])
                ->orderBy('waktu_masuk', 'ASC')
                ->get();

            // Pastikan data tersedia
            if ($absensi->isEmpty()) {
                return response()->json([
                    'error' => 'Data absensi tidak ditemukan untuk periode yang dipilih'
                ], Response::HTTP_NOT_FOUND);
            }

            // dd($absensi);
            // Siapkan data untuk PDF
            $data = [
                'title' => 'Laporan Absensi Karyawan',
                'date' => "Periode: " . date('d/m/Y', strtotime($request->tanggal_awal)) . 
                         " - " . date('d/m/Y', strtotime($request->tanggal_akhir)),
                'absensi' => $absensi
            ];

            // Generate PDF
            $pdf = Pdf::loadView('reports.attendance-pdf', $data);
            
            // Atur kertas ke landscape karena banyak kolom
            $pdf->setPaper('A4', 'landscape');

            // Return PDF untuk didownload
            return $pdf->download('laporan_absensi_' . 
                                $absensi->first()->user->name . '_' . 
                                date('Y-m-d') . '.pdf');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validasi gagal',
                'messages' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Gagal membuat laporan PDF',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
