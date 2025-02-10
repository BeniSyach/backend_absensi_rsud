<?php

namespace App\Http\Controllers;

use App\Models\AbsenPulang;
use App\Models\WaktuKerja;
use App\Models\AbsenMasuk;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
                'photo' => 'required|image|mimes:jpeg,png,jpg|max:15120',
            ]);

            // Ambil latitude dan longitude dari request
            $latitude = $request->latitude;
            $longitude = $request->longitude;

            // Query untuk mendapatkan lokasi terdekat dalam radius
            $nearestLocation = DB::selectOne("
                SELECT id, place_name, latitude, longitude, radius,
                    earth_distance(
                        ll_to_earth(latitude::double precision, longitude::double precision), 
                        ll_to_earth(?, ?)
                    ) AS distance
                FROM locations
                WHERE earth_distance(
                        ll_to_earth(latitude::double precision, longitude::double precision), 
                        ll_to_earth(?, ?)
                    ) <= radius
                ORDER BY distance ASC
                LIMIT 1
            ", [$latitude, $longitude, $latitude, $longitude]);

            // Jika tidak ada lokasi dalam radius, berikan respon error
            if (!$nearestLocation) {
                return response()->json([
                    'status' => 'Anda berada di luar lokasi',
                    'message' => 'Anda berada di luar lokasi'
                ], 403);
            }

            $user = User::find($request->user_id);
            if (!$user) {
                return response()->json([
                    'error' => 'User tidak ditemukan',
                    'message' => 'User dengan ID tersebut tidak ditemukan'
                ], 404);
            }

            $shiftId = $user->shift_id;

            if ($shiftId == 2) { // ini adalah 1 shift yaitu dari jam 8 pagi sampai jam 4 sore
                // Cek apakah sudah absen pulang hari ini
                $existingAbsenPulang = AbsenPulang::where('user_id', $request->user_id)
                    ->where('shift_id', 2)
                    ->where('absen_masuk_id', $request->absen_masuk_id)
                    ->whereDate('waktu_pulang', Carbon::now()->toDateString())
                    ->first();
    
                if ($existingAbsenPulang) {
                    return response()->json([
                        'error' => 'Anda sudah melakukan absen pulang hari ini',
                        'message' => 'Anda sudah melakukan absen pulang hari ini',
                        'last_absen' => $existingAbsenPulang->waktu_pulang
                    ], 400);
                }
            }

            // Ambil data absen masuk
            $absenMasuk = AbsenMasuk::findOrFail($request->absen_masuk_id);
            $tanggalMasuk = Carbon::parse($absenMasuk->waktu_masuk);
        
            // Ambil waktu sekarang sebagai waktu pulang
            $waktuPulang = Carbon::now();
        
            // Ambil data waktu kerja berdasarkan waktu_kerja_id
            $waktuKerja = WaktuKerja::findOrFail($request->waktu_kerja_id);
        
            // Hitung selisih waktu antara waktu_pulang dan jam_selesai
            $jamSelesai = Carbon::parse($tanggalMasuk->format('Y-m-d') . ' ' . $waktuKerja->jam_selesai);
            $selisihMenit = abs($waktuPulang->diffInMinutes($jamSelesai));

            $tppStatus = function($waktuPulang, $jamSelesai, $isPulangCepat) {
                // Selisih dalam menit
                $selisihMenit = $waktuPulang->diffInMinutes($jamSelesai);
                
                // Jika lebih dari 1 jam dari waktu selesai (60 menit)
                if ($waktuPulang->greaterThan($jamSelesai) && $selisihMenit > 60) {
                    return 'Lebih Lambat Pulang';
                }
                
                if ($isPulangCepat) {
                    if ($selisihMenit <= 1) {
                        return 'Tepat Waktu';
                    } elseif ($selisihMenit <= 31) {
                        return 'PSW 1';
                    } elseif ($selisihMenit <= 61) {
                        return 'PSW 2';
                    } elseif ($selisihMenit <= 91) {
                        return 'PSW 3';
                    } else {
                        return 'PSW 4';
                    }
                }
                return 'Tepat Waktu';
            };
            $jam_start = Carbon::parse($waktuKerja->jam_mulai);
            // Cek apakah absen pulang di hari yang berbeda
            if ($jam_start->hour >= 20) { // Jika jam mulai di atas jam 8 malam
                $isHariBerbeda = false; // Abaikan perbedaan hari karena memang shift malam
            } else {
                $isHariBerbeda = $waktuPulang->format('Y-m-d') !== $tanggalMasuk->format('Y-m-d');
            }

            // Cek apakah pulang lebih cepat
            $isPulangCepat = $waktuPulang->lessThan($jamSelesai);

            // Cek apakah terlambat lebih dari 1 jam
            $isLebihLambat = $waktuPulang->greaterThan($jamSelesai) && $selisihMenit > 60;

            // Tentukan status
            if ($isHariBerbeda || $isLebihLambat) {
                $statusPulang = 'Lebih Lambat Pulang';
                $tpp_out = 'Tepat Waktu';
            } elseif ($isPulangCepat) {
                $statusPulang = 'Lebih Cepat Pulang';
                $tpp_out = $tppStatus($waktuPulang, $jamSelesai, true);
            } else {
                $statusPulang = 'Tepat Waktu';
                $tpp_out = 'Tepat Waktu';
            }
        
            // Tambahkan waktu_pulang, selisih, dan status ke data yang divalidasi
            $validated['shift_id'] = $shiftId;
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
                'selisih_waktu' => $jamSelesai->diff($waktuPulang)->format('%H:%I:%S'),
                'lokasi' => $nearestLocation
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
