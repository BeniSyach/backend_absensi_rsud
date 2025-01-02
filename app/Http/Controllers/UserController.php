<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Divisi;
use App\Models\LevelAkses;
use App\Models\Gender;
use App\Models\StatusPegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
}
