<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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
                'nomor_hp' => 'required|string|max:255|unique:users',
                'alamat' => 'nullable|string',
                'id_divisi' => 'required|exists:divisi,id',
                'id_level_akses' => 'required|exists:level_akses,id',
                'id_gender' => 'required|exists:gender,id',
                'id_status' => 'required|exists:status_pegawai,id',
                'device_token' => 'nullable|string',
                'opd_id' => 'required|string|exists:locations,id'
            ]);

            // Membuat pengguna baru
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'nik' => $validatedData['nik'],
                'nomor_hp' => $validatedData['nomor_hp'],
                'alamat' => $validatedData['alamat'],
                'id_divisi' => $validatedData['id_divisi'],
                'id_level_akses' => $validatedData['id_level_akses'],
                'id_gender' => $validatedData['id_gender'],
                'id_status' => $validatedData['id_status'],
                'device_token' => $validatedData['device_token'],
                'opd_id' => $validatedData['opd_id'],
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
        // Use custom validator instead of request->validate() for better performance
        $validator = Validator::make($request->only(['email', 'password', 'device_token']), [
            'email' => 'required|email',
            'password' => 'required|min:6',
            'device_token' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }
    
        // Optimize database query by selecting only necessary fields
        DB::enableQueryLog();
        try {
            if (!$token = JWTAuth::attempt([
                'email' => $request->email,
                'password' => $request->password
            ])) {
                return response()->json([
                    'error' => 'Username / Password Salah'
                ], 401);
            }
    
            // Get user with single query and selected fields only
            $user = auth()->user();
            
            // Use single database transaction for device token update
            if (empty($user->device_token)) {
                DB::transaction(function() use ($user, $request) {
                    $user->device_token = $request->device_token;
                    $user->save();
                }, 3);
            } elseif ($user->device_token !== $request->device_token) {
                return response()->json([
                    'error' => 'Akun Anda sudah login di perangkat lain, Mohon Hubungi Admin untuk Reset Akun'
                ], 403);
            }
    
            // Return minimal response
            return response()->json([
                'token' => $token,
                'message' => $user
            ], 200);
    
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Could not create token'
            ], 500);
        }
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

    public function resetAccount(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'email' => 'required|email',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Tangkap error validasi dan kembalikan respons dengan pesan error
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        }

        // Cari pengguna berdasarkan email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Reset device_token ke null
        $user->device_token = null;
        $user->save();

        // Kembalikan respons sukses
        return response()->json(['message' => 'Akun telah direset, silahkan login kembali'], 200);
    }

}
