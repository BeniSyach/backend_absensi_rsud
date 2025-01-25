<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AbsenMasuk;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LaporanAbsensiController extends Controller
{
    public function getlaporanByUser(Request $request)
    {
        // Define validation rules
        $rules = [
            'user_id' => 'nullable|integer|exists:users,id',
            'divisi' => 'nullable|integer|exists:divisi,id',
            'status_pegawai' => 'nullable|string',
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
            'user_id.exists' => 'User ID is not valid.',
            'divisi.exists' => 'Divisi ID is not valid.',
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
            $query = AbsenMasuk::with(['user', 'waktuKerja', 'shift', 'absenPulang']);
            
            // Filter by user_id if provided
            if (isset($validated['user_id'])) {
                $query->where('user_id', $validated['user_id']);
            } 
            // If no user_id, filter by divisi and/or status_pegawai
            else {
                // Filter by divisi if provided
                if (isset($validated['divisi'])) {
                    $query->whereHas('user', function($q) use ($validated) {
                        $q->where('id_divisi', $validated['divisi']);
                    });
                }
                
                // Filter by status_pegawai if provided
                if (isset($validated['status_pegawai'])) {
                    $query->whereHas('user', function($q) use ($validated) {
                        $q->where('id_status', $validated['status_pegawai']);
                    });
                }
            }
            
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
            $validated = $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'divisi' => 'nullable|exists:divisi,id',
                'status_pegawai' => 'nullable|string',
                'tanggal_awal' => 'required|date',
                'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
            ]);
    
            // Query dasar
            $query = AbsenMasuk::with(['user', 'waktuKerja', 'shift', 'absenPulang' => function($query) {
                $query->orderBy('waktu_pulang', 'ASC');
            }]);
    
            // Filter berdasarkan user_id jika disediakan
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }
    
            // Filter berdasarkan divisi jika disediakan
            if ($request->filled('divisi')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('id_divisi', $request->divisi);
                });
            }
    
            // Filter berdasarkan status pegawai jika disediakan
            if ($request->filled('status_pegawai')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('id_status', $request->status_pegawai);
                });
            }
    
            // Filter rentang tanggal
            $query->whereBetween('waktu_masuk', [
                Carbon::parse($request->tanggal_awal)->startOfDay(),
                Carbon::parse($request->tanggal_akhir)->endOfDay()
            ]);
    
            // Urutkan
            $query->orderBy('waktu_masuk', 'ASC');
    
            // Ambil data
            $absensi = $query->get();

            // Pastikan data tersedia
            if ($absensi->isEmpty()) {
                return response()->json([
                    'error' => 'Data absensi tidak ditemukan untuk periode yang dipilih'
                ], Response::HTTP_NOT_FOUND);
            }

            // dd($absensi);
            // Siapkan data untuk PDF
            $data = [
                'title' => 'Laporan Absensi Pegawai RSUD Drs. H. Amri Tambunan',
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

    public function getLaporanAbsensiTLdanPSW(Request $request): Response
    {
        try {
            // Validasi request
            $validated = $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'tanggal_awal' => 'required|date',
                'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
                'divisi' => 'nullable|integer|exists:divisi,id',
                'status_pegawai' => 'nullable|integer|exists:status_pegawai,id',
            ]);
    
            $absensi = AbsenMasuk::select(
                'users.name as nama',
                'divisi.nama_divisi as divisi',
                DB::raw('SUM(CASE WHEN absen_masuk.keterangan = \'Tepat Waktu\' THEN 1 ELSE 0 END) AS tepat_waktu_masuk'),
                DB::raw('SUM(CASE WHEN absen_masuk.keterangan = \'Terlambat\' THEN 1 ELSE 0 END) AS terlambat_masuk'),
                DB::raw('SUM(CASE WHEN absen_pulang.keterangan = \'Tepat Waktu\' THEN 1 ELSE 0 END) AS tepat_waktu_pulang'),
                DB::raw('SUM(CASE WHEN absen_pulang.keterangan = \'Lebih Cepat Pulang\' THEN 1 ELSE 0 END) AS lebih_cepat_pulang'),
                DB::raw('SUM(CASE WHEN absen_masuk.tpp_in = \'TL 1\' THEN 1 ELSE 0 END) AS tl_1'),
                DB::raw('SUM(CASE WHEN absen_masuk.tpp_in = \'TL 2\' THEN 1 ELSE 0 END) AS tl_2'),
                DB::raw('SUM(CASE WHEN absen_masuk.tpp_in = \'TL 3\' THEN 1 ELSE 0 END) AS tl_3'),
                DB::raw('SUM(CASE WHEN absen_masuk.tpp_in = \'TL 4\' THEN 1 ELSE 0 END) AS tl_4'),
                DB::raw('SUM(CASE WHEN absen_pulang.tpp_out = \'PSW 1\' THEN 1 ELSE 0 END) AS psw_1'),
                DB::raw('SUM(CASE WHEN absen_pulang.tpp_out = \'PSW 2\' THEN 1 ELSE 0 END) AS psw_2'),
                DB::raw('SUM(CASE WHEN absen_pulang.tpp_out = \'PSW 3\' THEN 1 ELSE 0 END) AS psw_3'),
                DB::raw('SUM(CASE WHEN absen_pulang.tpp_out = \'PSW 4\' THEN 1 ELSE 0 END) AS psw_4'),
                DB::raw('COUNT(*) AS total_absensi')
            )
            ->join('users', 'absen_masuk.user_id', '=', 'users.id')
            ->leftJoin('divisi', 'users.id_divisi', '=', 'divisi.id')
            ->leftJoin('absen_pulang', function($join) {
                $join->on('absen_masuk.id', '=', 'absen_pulang.absen_masuk_id'); // Tambahkan soft delete jika diperlukan
            })
            ->when($request->filled('user_id'), function ($query) use ($request) {
                $query->where('absen_masuk.user_id', $request->user_id);
            })
            ->when($request->filled('divisi'), function ($query) use ($request) {
                $query->where('users.id_divisi', $request->divisi);
            })
            ->when($request->filled('status_pegawai'), function ($query) use ($request) {
                $query->where('users.id_status', $request->status_pegawai);
            })
            ->whereBetween('absen_masuk.waktu_masuk', [
                $request->tanggal_awal . ' 00:00:00',
                $request->tanggal_akhir . ' 23:59:59'
            ])
            ->groupBy('users.id', 'users.name', 'divisi.nama_divisi')
            ->orderBy('users.name')
            ->get();
    
            if ($absensi->isEmpty()) {
                return response()->json([
                    'error' => 'Data absensi tidak ditemukan untuk periode yang dipilih'
                ], Response::HTTP_NOT_FOUND);
            }
    
            $data = [
                'title' => 'Rekap Laporan Absensi Pegawai RSUD Drs. H. Amri Tambunan',
                'date' => "Periode: " . Carbon::parse($request->tanggal_awal)->format('d/m/Y') . 
                        " - " . Carbon::parse($request->tanggal_akhir)->format('d/m/Y'),
                'absensi' => $absensi
            ];
    
            $pdf = Pdf::loadView('reports.laporan-absensi-pdf', $data);
            $pdf->setPaper('A4', 'landscape');
    
            return $pdf->download('laporan_absensi_' . 
                Str::slug($absensi->first()->nama) . '_' . 
                now()->format('Y-m-d') . '.pdf');
        
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
