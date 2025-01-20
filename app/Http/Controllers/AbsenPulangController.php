<?php

namespace App\Http\Controllers;

use App\Models\AbsenPulang;
use App\Models\WaktuKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AbsenPulangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'page' => 'integer|min:1',
                'limit' => 'integer|min:1|max:100',
                'sortBy' => 'string|in:waktu_pulang,created_at,user_id',
                'sortOrder' => 'string|in:ASC,DESC',
                'search' => 'nullable|string|max:255',
            ], [
                'page.integer' => 'Halaman harus berupa angka.',
                'page.min' => 'Halaman minimal 1.',
                'limit.integer' => 'Limit harus berupa angka.',
                'limit.min' => 'Limit minimal 1.',
                'limit.max' => 'Maksimal limit adalah 100 data per halaman.',
                'sortBy.in' => 'Kolom pengurutan tidak valid.',
                'sortOrder.in' => 'Urutan pengurutan harus ASC atau DESC.',
                'search.string' => 'Parameter pencarian harus berupa teks.',
                'search.max' => 'Parameter pencarian maksimal 255 karakter.'
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }
    
            // Get parameters with defaults
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 10);
            $sortBy = $request->input('sortBy', 'waktu_pulang');
            $sortOrder = $request->input('sortOrder', 'DESC');
            $search = $request->input('search');

            $query = AbsenPulang::with(['user']);

            if ($search) {
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                })
                ->orWhere('waktu_pulang', 'LIKE', "%{$search}%")
                ->orWhere('keterangan', 'LIKE', "%{$search}%");
            }

            $query->orderBy($sortBy, $sortOrder);

            $absen_pulang = $query->paginate($limit);

             // Check if data exists
            if ($absen_pulang->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data tidak ditemukan',
                    'data' => [
                        'current_page' => 1,
                        'data' => [],
                        'total' => 0,
                        'per_page' => $limit
                    ]
                ]);
            }

            return response()->json([
                'status' => 'success',
                'data' => $absen_pulang
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validasi request
            $validated = $request->validate([
                'absen_masuk_id' => 'required|exists:absen_masuk,id',
                'user_id' => 'required|exists:users,id',
                'shift_id' => 'required|exists:shifts,id',
                'waktu_kerja_id' => 'required|exists:waktu_kerjas,id',
                'longitude' => 'required|string',
                'latitude' => 'required|string',
                'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($request->shift_id == 2) {
                // Cek apakah sudah absen pulang hari ini
                $existingAbsenPulang = AbsenPulang::where('user_id', $request->user_id)
                    ->where('shift_id', 2)
                    ->where('absen_masuk_id', $request->absen_masuk_id)
                    ->whereDate('waktu_pulang', Carbon::now()->toDateString())
                    ->first();
    
                if ($existingAbsenPulang) {
                    return response()->json([
                        'error' => 'Anda sudah melakukan absen pulang hari ini',
                        'last_absen' => $existingAbsenPulang->waktu_pulang
                    ], 400);
                }
            }
        
            // Ambil waktu sekarang sebagai waktu pulang
            $waktuPulang = Carbon::now();
        
            // Ambil data waktu kerja berdasarkan waktu_kerja_id
            $waktuKerja = WaktuKerja::findOrFail($request->waktu_kerja_id);
        
            // Hitung selisih waktu antara waktu_pulang dan jam_selesai
            $jamSelesai = Carbon::parse($waktuKerja->jam_selesai);
            $selisihMenit = abs($waktuPulang->diffInMinutes($jamSelesai));

            $tppStatus = function($selisihMenit, $isPulangCepat) {
                if ($isPulangCepat) {
                    if ($selisihMenit <= 1) {
                        return 'Tepat Waktu';
                    } elseif ($selisihMenit <= 31) {
                        return 'PSW1';
                    } elseif ($selisihMenit <= 61) {
                        return 'PSW2';
                    } elseif ($selisihMenit <= 91) {
                        return 'PSW3';
                    } else {
                        return 'PSW4';
                    }
                }
                return 'Tepat Waktu';
            };
        
            $isPulangCepat = $waktuPulang->lessThan($jamSelesai);
            if ($isPulangCepat) {
                $statusPulang = 'Lebih Cepat Pulang';
                $tpp_out = $tppStatus($selisihMenit, true);
            } else {
                $statusPulang = 'Tepat Waktu';
                $tpp_out = 'Tepat Waktu';
            }
        
            // Tambahkan waktu_pulang, selisih, dan status ke data yang divalidasi
            $validated['waktu_pulang'] = $waktuPulang->toDateTimeString();
            $validated['selish'] = $jamSelesai->diff($waktuPulang)->format('%H:%I:%S');
            $validated['keterangan'] = $statusPulang;
            $validated['tpp_out'] = $tpp_out;
        
            // Menyimpan foto ke direktori yang diinginkan
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                // Menyimpan foto dan mendapatkan path-nya
                $photoPath = $request->file('photo')->store('photos_absen_pulang', 'public'); 
        
                // Menambahkan path foto ke data yang akan disimpan
                $validated['photo'] = $photoPath;
            } else {
                return response()->json([
                    'error' => 'Invalid photo file or file not provided'
                ], 400);
            }
        
            // Simpan data absen pulang ke dalam database
            $absen_pulang = AbsenPulang::create($validated);
        
            // Mengembalikan response sukses
            return response()->json([
                'message' => 'Absen pulang berhasil disimpan',
                'data' => $absen_pulang,
                'status_pulang' => $statusPulang,
                'selisih_waktu' => $jamSelesai->diff($waktuPulang)->format('%H:%I:%S')
            ], 201);
        
        } catch (\Exception $e) {
            // Menangani error dan mengembalikan response error
            return response()->json([
                'error' => 'Failed to create Absen Pulang',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $absen_pulang = AbsenPulang::find($id);

        if (!$absen_pulang) {
            return response()->json(['error' => 'absen pulang not found'], 404);
        }

        return response()->json($absen_pulang, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            // Validasi data yang akan diperbarui
            $validated = $request->validate([
                'absen_masuk_id' => 'nullable|exists:absen_masuk,id',
                'user_id' => 'nullable|exists:users,id',
                'shift_id' => 'nullable|exists:shifts,id',
                'waktu_kerja_id' => 'nullable|exists:waktu_kerjas,id',
                'longitude' => 'nullable|string',
                'latitude' => 'nullable|string',
                'waktu_pulang' => 'nullable|date',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'tpp_out' => 'nullable|string',
                'keterangan' => 'nullable|string',
            ]);
    
            // Cari data absensi pulang berdasarkan ID
            $absenPulang = AbsenPulang::findOrFail($id);
    
            // Perbarui foto jika ada
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                // Simpan foto baru dan dapatkan path
                $photoPath = $request->file('photo')->store('photos_absen_pulang', 'public');
                $validated['photo'] = $photoPath;
            }
    
            // Perbarui waktu jika diperlukan
            if ($request->has('waktu_pulang')) {
                $validated['waktu_pulang'] = Carbon::parse($request->waktu_pulang)->toDateTimeString();
            }
    
            // Ambil data waktu kerja untuk menghitung selisih waktu
            if ($request->has('waktu_kerja_id')) {
                $waktuKerja = WaktuKerja::findOrFail($request->waktu_kerja_id);
                $jamSelesai = Carbon::parse($waktuKerja->jam_selesai);
                $waktuPulang = Carbon::parse($validated['waktu_pulang']);
                $selisih = $jamSelesai->diff($waktuPulang)->format('%H:%I:%S');
                $validated['selish'] = $selisih;
            }
    
            // Lakukan update pada data absen pulang
            $absenPulang->update($validated);
    
            // Kembalikan respons sukses
            return response()->json([
                'message' => 'Data absen pulang berhasil diperbarui',
                'data' => $absenPulang,
            ], 200);
            
        } catch (\Exception $e) {
            // Tangani error
            return response()->json([
                'error' => 'Gagal memperbarui data absen pulang',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $absen_pulang = AbsenPulang::find($id);

        if (!$absen_pulang) {
            return response()->json(['error' => 'absen pulang not found'], 404);
        }

        $absen_pulang->delete();

        return response()->json(['message' => 'absen pulang deleted successfully'], 200);
    }
}
