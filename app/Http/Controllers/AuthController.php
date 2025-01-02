<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            // Validasi input dari request
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'nik' => 'required|digits:16',
                'id_divisi' => 'required|exists:divisi,id',
                'id_level_akses' => 'required|exists:level_akses,id',
                'id_gender' => 'required|exists:gender,id',
                'id_status' => 'required|exists:status_pegawai,id',
                'device_token' => 'required|string',
            ]);

            // Membuat pengguna baru
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'nik' => $validatedData['nik'],
                'id_divisi' => $validatedData['id_divisi'],
                'id_level_akses' => $validatedData['id_level_akses'],
                'id_gender' => $validatedData['id_gender'],
                'id_status' => $validatedData['id_status'],
                'device_token' => $validatedData['device_token'],
            ]);

            // Menghasilkan token JWT untuk pengguna
            $token = JWTAuth::fromUser($user);

            // Mengembalikan response sukses dengan token
            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil Daftar'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Menangkap dan mengembalikan error validasi
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Menangani kesalahan umum (misalnya database error)
            return response()->json([
                'error' => 'User creation failed',
                'message' => $e->getMessage()
            ], 500);
        } catch (JWTException $e) {
            // Menangani kesalahan dalam pembuatan token JWT
            return response()->json([
                'error' => 'Could not create token',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            // Cek apakah kredensial valid
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Username / Password Salah'], 401);
            }
        } catch (JWTException $e) {
            // Jika terjadi kesalahan dalam pembuatan token
            return response()->json(['error' => 'Could not create token'], 500);
        }

        // Kembalikan token sebagai respons
        return response()->json(compact('token'));
    }

    public function refresh()
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json(['token' => $token]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token invalid'], 401);
        }
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Successfully logged out']);
    }
}
