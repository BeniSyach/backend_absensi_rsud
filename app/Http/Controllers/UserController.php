<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Menampilkan semua pengguna.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'page' => 'integer|min:1',
                'limit' => 'integer|min:1|max:100',
                'sortBy' => 'string|in:name,email,created_at,updated_at',
                'sortOrder' => 'string|in:ASC,DESC',
                'search' => 'nullable|string|max:255',
                'divisi' => 'nullable|integer|exists:divisi,id',
                'level_akses' => 'nullable|integer|exists:level_akses,id',
                'status_pegawai' => 'nullable|integer|exists:status_pegawai,id',
                'gender' => 'nullable|integer|exists:gender,id'
            ], [
                'page.integer' => 'Halaman harus berupa angka.',
                'page.min' => 'Halaman minimal 1.',
                'limit.integer' => 'Limit harus berupa angka.',
                'limit.min' => 'Limit minimal 1.',
                'limit.max' => 'Maksimal limit adalah 100 data per halaman.',
                'sortBy.in' => 'Kolom pengurutan tidak valid.',
                'sortOrder.in' => 'Urutan pengurutan harus ASC atau DESC.',
                'search.string' => 'Parameter pencarian harus berupa teks.',
                'search.max' => 'Parameter pencarian maksimal 255 karakter.',
                'divisi.exists' => 'Divisi tidak ditemukan.',
                'level_akses.exists' => 'Level akses tidak ditemukan.',
                'status_pegawai.exists' => 'Status pegawai tidak ditemukan.',
                'gender.exists' => 'Gender tidak ditemukan.'
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
            $sortBy = $request->input('sortBy', 'created_at');
            $sortOrder = $request->input('sortOrder', 'DESC');
            $search = $request->input('search');
            
            // Build query with relationships
            $query = User::with(['divisi', 'levelAkses', 'gender', 'statusPegawai', 'opd']);
    
            // Apply filters if provided
            if ($request->has('divisi')) {
                $query->where('id_divisi', $request->divisi);
            }
            
            if ($request->has('level_akses')) {
                $query->where('level_akses_id', $request->level_akses);
            }
            
            if ($request->has('status_pegawai')) {
                $query->where('status_pegawai_id', $request->status_pegawai);
            }
            
            if ($request->has('gender')) {
                $query->where('gender_id', $request->gender);
            }
    
            // Apply search if provided
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('nomor_hp', 'LIKE', "%{$search}%")
                      ->orWhere('alamat', 'LIKE', "%{$search}%")
                      ->orWhereHas('divisi', function($q) use ($search) {
                          $q->where('nama_divisi', 'LIKE', "%{$search}%");
                      });
                });
            }
    
            // Apply ordering
            $query->orderBy($sortBy, $sortOrder);
    
            // Get paginated results
            $users = $query->paginate($limit);
    
            // Check if data exists
            if ($users->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data tidak ditemukan',
                    'data' => [
                        'current_page' => 1,
                        'data' => [],
                        'total' => 0,
                        'per_page' => $limit
                    ]
                ], 200);
            }
    
            return response()->json([
                'status' => 'success',
                'data' => $users
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan detail pengguna berdasarkan ID.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::with(['divisi', 'levelAkses', 'gender', 'statusPegawai', 'opd'])->find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Menambahkan query untuk mendapatkan status absen terakhir
            $lastAbsen = DB::selectOne("
            WITH last_absen AS (
                SELECT 
                    am.id AS absen_masuk_id,
                    am.user_id,
                    am.waktu_masuk,
                    ap.id AS absen_pulang_id,
                    ap.waktu_pulang
                FROM 
                    absen_masuk am
                LEFT JOIN 
                    absen_pulang ap ON am.id = ap.absen_masuk_id
                WHERE
                    am.user_id = :user_id
                ORDER BY 
                    am.waktu_masuk DESC
                LIMIT 1
            )
            SELECT 
                la.absen_masuk_id,
                CASE
                    WHEN la.absen_masuk_id IS NOT NULL AND la.absen_pulang_id IS NULL THEN 1
                    WHEN la.absen_masuk_id IS NOT NULL AND la.absen_pulang_id IS NOT NULL  THEN 0
                    WHEN la.absen_masuk_id IS NULL AND la.absen_pulang_id IS NOT NULL THEN 0
                    ELSE 1
                END AS status
            FROM 
                last_absen la;
        ", ['user_id' => $id]);

        // Menggabungkan status absen terakhir dengan data user
        $user->lastAbsenStatus = $lastAbsen;

        return response()->json($user, 200);
    }

    /**
     * Membuat pengguna baru.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'nik' => 'required|digits:16|unique:users',
            'id_divisi' => 'required|exists:divisi,id',
            'id_level_akses' => 'required|exists:level_akses,id',
            'id_gender' => 'required|exists:gender,id',
            'id_status' => 'required|exists:status_pegawai,id',
            'device_token' => 'nullable|string',
            'opd_id' => 'required|string|exists:locations,id'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'nik' => $validated['nik'],
            'id_divisi' => $validated['id_divisi'],
            'id_level_akses' => $validated['id_level_akses'],
            'id_gender' => $validated['id_gender'],
            'id_status' => $validated['id_status'],
            'device_token' => $validated['device_token'],
            'opd_id' => $validated['opd_id'],
        ]);

        return response()->json($user, 201);
    }

    /**
     * Memperbarui data pengguna.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'nik' => 'required|digits:16|unique:users,nik,' . $id,
            'id_divisi' => 'required|exists:divisi,id',
            'id_level_akses' => 'nullable|exists:level_akses,id',
            'id_gender' => 'required|exists:gender,id',
            'id_status' => 'required|exists:status_pegawai,id',
            'device_token' => 'nullable|string',
            'opd_id' => 'nullable|string|exists:locations,id'
        ]);

        if ($request->has('password')) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json($user, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update Users',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menghapus pengguna.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }

    public function resetPassword(Request $request)
    {
        try {
            // Validasi input
            $validatedData = $request->validate([
                'old_password' => 'required|string|min:6',
                'new_password' => 'required|string|min:6|confirmed',
            ]);

            // Mendapatkan pengguna yang sedang login
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Cek apakah password lama sesuai
            if (!Hash::check($validatedData['old_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'old_password' => ['Password lama tidak sesuai'],
                ]);
            }

            // Update password dengan password baru
            $user->password = Hash::make($validatedData['new_password']);
            $user->save();

            // Mengembalikan respons sukses
            return response()->json(['message' => 'Password berhasil direset'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to reset password',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadPhoto(Request $request)
    {
        try {
            // Validasi input foto
            $validatedData = $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validasi file gambar
            ]);

            // Mendapatkan pengguna yang sedang login
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Menyimpan foto hanya jika validasi berhasil
            $photoPath = $request->file('photo')->store('photos', 'public');

            // Update kolom photo di pengguna
            $user->photo = $photoPath;
            $user->save(); // Simpan perubahan foto

            // Mengembalikan response sukses dengan URL foto
            return response()->json([
                'message' => 'Photo uploaded successfully',
                'photo_url' => asset('storage/' . $photoPath)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to upload photo',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
