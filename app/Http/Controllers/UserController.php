<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Menampilkan semua pengguna.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::with(['divisi', 'levelAkses', 'gender', 'statusPegawai'])->get();
        return response()->json($users, 200);
    }

    /**
     * Menampilkan detail pengguna berdasarkan ID.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::with(['divisi', 'levelAkses', 'gender', 'statusPegawai'])->find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

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
            'id_level_akses' => 'required|exists:level_akses,id',
            'id_gender' => 'required|exists:gender,id',
            'id_status' => 'required|exists:status_pegawai,id',
            'device_token' => 'nullable|string',
        ]);

        if ($request->has('password')) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json($user, 200);
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
