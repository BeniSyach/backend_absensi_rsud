<?php

namespace App\Http\Controllers;

use App\Models\AbsenMasuk;
use App\Models\WaktuKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class AbsenMasukController extends Controller
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
                'sortBy' => 'string|in:waktu_masuk,created_at,user_id',
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
            $sortBy = $request->input('sortBy', 'waktu_masuk');
            $sortOrder = $request->input('sortOrder', 'DESC');
            $search = $request->input('search');
    
            // Build query
            $query = AbsenMasuk::with(['user.divisi', 'absenPulang' => function($query) {
                $query->select('*')
                  ->latest()
                  ->take(1);
            }]);
    
            // Apply search if provided
            if ($search) {
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                })
                ->orWhere('waktu_masuk', 'LIKE', "%{$search}%")
                ->orWhere('keterangan', 'LIKE', "%{$search}%");
            }
    
            // Apply ordering
            $query->orderBy($sortBy, $sortOrder);
    
            // Get paginated results
            $absen_masuk = $query->paginate($limit);

            $transformed_data = $absen_masuk->through(function ($item) {
                // Convert absenPulang from array to object by taking first item
                if ($item->absenPulang->count() > 0) {
                    $item->absenPulang = $item->absenPulang->first();
                } else {
                    $item->absenPulang = null;
                }
                return $item;
            });
    
            // Check if data exists
            if ($absen_masuk->isEmpty()) {
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
                'data' => $transformed_data
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
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'shift_id' => 'required|exists:shifts,id',
                'waktu_kerja_id' => 'required|exists:waktu_kerjas,id',
                'longitude' => 'required|string',
                'latitude' => 'required|string',
                'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Validasi foto
                // 'tpp_in' => 'required|string',
                // 'keterangan' => 'nullable|string',
            ]);

            if ($request->shift_id == 2) {
                // Cek apakah sudah ada absen hari ini untuk user tersebut
                $existingAbsen = AbsenMasuk::where('user_id', $request->user_id)
                    ->whereDate('waktu_masuk', Carbon::today())
                    ->first();
    
                if ($existingAbsen) {
                    return response()->json([
                        'error' => 'Anda sudah melakukan absen hari ini',
                        'last_absen' => $existingAbsen->waktu_masuk
                    ], 400);
                }
            }
        
            // Ambil waktu sekarang sebagai waktu masuk
            $waktuMasuk = Carbon::now();
        
            // Ambil data waktu kerja berdasarkan waktu_kerja_id
            $waktuKerja = WaktuKerja::findOrFail($request->waktu_kerja_id);
        
            // Hitung selisih waktu antara waktu_masuk dan jam_mulai
            $jamMulai = Carbon::parse($waktuKerja->jam_mulai);
            $selisih = $jamMulai->diff($waktuMasuk)->format('%H:%I:%S');

            $selisihMenit = abs($waktuMasuk->diffInMinutes($jamMulai));

            // Fungsi untuk menentukan status TPP
            $tppStatus = function($selisihMenit, $isTerlambat) {
                // Jika datang lebih awal (tidak terlambat)
                if (!$isTerlambat) {
                    return 'Tepat Waktu';
                }
                
                // Jika terlambat, cek berapa menit keterlambatannya
                if ($selisihMenit <= 1) {
                    return 'Tepat Waktu';
                } elseif ($selisihMenit <= 31) {
                    return 'TL 1';
                } elseif ($selisihMenit <= 61) {
                    return 'TL 2';
                } elseif ($selisihMenit <= 91) {
                    return 'TL 3';
                } else {
                    return 'TL 4';
                }
            };

            $isTerlambat = $waktuMasuk->greaterThan($jamMulai);

            if ($isTerlambat) {
                $statusPulang = 'Terlambat';
                $tpp_in = $tppStatus($selisihMenit, true);
            } else {
                $statusPulang = 'Tepat Waktu';
                $tpp_in = $tppStatus($selisihMenit, false);
            }
        
            // Tambahkan waktu_masuk dan selisih ke data yang divalidasi
            $validated['waktu_masuk'] = $waktuMasuk->toDateTimeString();
            $validated['selish'] = $selisih;
            $validated['tpp_in'] = $tpp_in;
            $validated['keterangan'] = $statusPulang;
        
            // Menyimpan foto ke direktori yang diinginkan
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                // Menyimpan foto dan mendapatkan path-nya
                $photoPath = $request->file('photo')->store('photos_absen_masuk', 'public'); 
        
                // Menambahkan path foto ke data yang akan disimpan
                $validated['photo'] = $photoPath;
            } else {
                return response()->json([
                    'error' => 'Invalid photo file or file not provided'
                ], 400);
            }
        
            // dd($validated);
            // Simpan data absen masuk ke dalam database
            $absen_masuk = AbsenMasuk::create($validated);
        
            // Mengembalikan response sukses
            return response()->json([
                'message' => 'Absen masuk berhasil disimpan',
                'data' => $absen_masuk,
                'selisih_waktu' => $selisih,
            ], 201);
        
        } catch (\Exception $e) {
            // Menangani error dan mengembalikan response error
            return response()->json([
                'error' => 'Failed to create Absen Masuk',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $absen_masuk = AbsenMasuk::find($id);

        if (!$absen_masuk) {
            return response()->json(['error' => 'absen masuk not found'], 404);
        }

        return response()->json($absen_masuk, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            // Validasi data yang akan diperbarui
            $validated = $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'shift_id' => 'nullable|exists:shifts,id',
                'waktu_kerja_id' => 'nullable|exists:waktu_kerjas,id',
                'longitude' => 'nullable|string',
                'latitude' => 'nullable|string',
                'waktu_masuk' => 'nullable|date',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'tpp_in' => 'nullable|string',
                'keterangan' => 'nullable|string',
            ]);
    
            // Cari data absensi berdasarkan ID
            $absenMasuk = AbsenMasuk::findOrFail($id);
    
            // Perbarui foto jika ada
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                // Simpan foto baru dan dapatkan path
                $photoPath = $request->file('photo')->store('photos_absen_masuk', 'public');
                $validated['photo'] = $photoPath;
            }
    
            // Perbarui waktu jika diperlukan
            if ($request->has('waktu_masuk')) {
                $validated['waktu_masuk'] = Carbon::parse($request->waktu_masuk)->toDateTimeString();
            }
    
            // Lakukan update pada data absensi
            $absenMasuk->update($validated);
    
            // Kembalikan respons sukses
            return response()->json([
                'message' => 'Data absensi berhasil diperbarui',
                'data' => $absenMasuk,
            ], 200);
        } catch (\Exception $e) {
            // Tangani error
            return response()->json([
                'error' => 'Gagal memperbarui data absensi',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $absen_masuk = AbsenMasuk::find($id);

        if (!$absen_masuk) {
            return response()->json(['error' => 'absen masuk not found'], 404);
        }

        $absen_masuk->delete();

        return response()->json(['message' => 'absen masuk deleted successfully'], 200);
    }

    public function getAbsenPulangByUser(Request $request, $idUser)
    {
        $rules = [
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
            'sortBy' => 'string|in:waktu_masuk,created_at,waktu_pulang',
            'sortOrder' => 'string|in:ASC,DESC',
        ];
    
        // Additional validation for route parameter
        $routeRules = [
            'idUser' => 'required|integer|exists:users,id'
        ];
    
        // Custom error messages
        $messages = [
            'page.integer' => 'Halaman harus berupa angka.',
            'page.min' => 'Halaman minimal 1.',
            'limit.integer' => 'Limit harus berupa angka.',
            'limit.min' => 'Limit minimal 1.',
            'limit.max' => 'Maksimal limit adalah 100 data per halaman.',
            'sortBy.in' => 'Kolom pengurutan tidak valid.',
            'sortOrder.in' => 'Urutan pengurutan harus ASC atau DESC.',
            'idUser.required' => 'ID user diperlukan.',
            'idUser.integer' => 'ID user harus berupa angka.',
            'idUser.exists' => 'User tidak ditemukan.',
        ];

        try {
            $validator = Validator::make($request->all(), $rules, $messages);
        
            // Validate route parameter
            $routeValidator = Validator::make(['idUser' => $idUser], $routeRules, $messages);
    
            // Check if any validation fails
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
    
            if ($routeValidator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $routeValidator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
    
            $page = $request->input('page', 1); 
            $limit = $request->input('limit', 10); 
            $sortBy = $request->input('sortBy', 'waktu_masuk');
            $sortOrder = $request->input('sortOrder', 'DESC'); 
        
            $AbsenMasukDanPulang = AbsenMasuk::with(['user', 'absenPulang'])
                ->where('user_id', $idUser) 
                ->orderBy($sortBy, $sortOrder) 
                ->paginate($limit); 

                   // Check if data exists
            if ($AbsenMasukDanPulang->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data tidak ditemukan',
                    'data' => []
                ], Response::HTTP_OK);
            }
        
            // Kembalikan hasil paginasi dalam format JSON
            return response()->json($AbsenMasukDanPulang);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
}
