<?php

namespace App\Http\Controllers;
use App\Models\AbsenMasuk;
use App\Models\AbsenPulang;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

use Illuminate\Http\Request;

class DashboardController extends Controller
{

    public function totalUsers()
    {
        $total = User::getTotalUsers();
        $totalAndroidUsers = User::getTotalAndroidUsers();
        $totalWebUsers = User::getTotalWebUsers();
        $totalNeverLoggedIn = User::getTotalUsersNeverLoggedIn();

        return response()->json([
            'total_pegawai' => $total,
            'total_android_users' => $totalAndroidUsers,
            'total_web_users' => $totalWebUsers,
            'total_users_tidak_pernah_login' => $totalNeverLoggedIn
        ]);
    }

    public function laporanKehadiran(Request $request)
    {

        // Validasi input
        $validator = Validator::make($request->all(), [
            'tanggal_awal' => ['nullable', 'date', 'before_or_equal:tanggal_akhir'],
            'tanggal_akhir' => ['nullable', 'date', 'after_or_equal:tanggal_awal']
        ], [
            // 'tanggal_awal.required' => 'Tanggal awal wajib diisi.',
            'tanggal_awal.date' => 'Tanggal awal harus dalam format tanggal yang benar.',
            'tanggal_awal.before_or_equal' => 'Tanggal awal tidak boleh lebih besar dari tanggal akhir.',
            // 'tanggal_akhir.required' => 'Tanggal akhir wajib diisi.',
            'tanggal_akhir.date' => 'Tanggal akhir harus dalam format tanggal yang benar.',
            'tanggal_akhir.after_or_equal' => 'Tanggal akhir tidak boleh lebih kecil dari tanggal awal.'
        ]);

        // Jika validasi gagal, kembalikan error
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 400);
        }

        // Ambil input tanggal, defaultnya hari ini
        $tanggal_awal = $request->input('tanggal_awal', now()->startOfMonth()->toDateString());
        $tanggal_akhir = $request->input('tanggal_akhir', now()->toDateString());

        // Ambil data dari model
        $data = AbsenMasuk::getTotalKehadiran($tanggal_awal, $tanggal_akhir);

        return response()->json([
            'tanggal_awal' => $tanggal_awal,
            'tanggal_akhir' => $tanggal_akhir,
            'total_terlambat' => $data->total_terlambat ?? 0,
            'total_tepat_waktu' => $data->total_tepat_waktu ?? 0
        ]);
    }

    public function laporanPulang(Request $request)
    {

        // Validasi input
        $validator = Validator::make($request->all(), [
            'tanggal_awal' => ['nullable', 'date', 'before_or_equal:tanggal_akhir'],
            'tanggal_akhir' => ['nullable', 'date', 'after_or_equal:tanggal_awal']
        ], [
            // 'tanggal_awal.required' => 'Tanggal awal wajib diisi.',
            'tanggal_awal.date' => 'Tanggal awal harus dalam format tanggal yang benar.',
            'tanggal_awal.before_or_equal' => 'Tanggal awal tidak boleh lebih besar dari tanggal akhir.',
            // 'tanggal_akhir.required' => 'Tanggal akhir wajib diisi.',
            'tanggal_akhir.date' => 'Tanggal akhir harus dalam format tanggal yang benar.',
            'tanggal_akhir.after_or_equal' => 'Tanggal akhir tidak boleh lebih kecil dari tanggal awal.'
        ]);

        // Jika validasi gagal, kembalikan error
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 400);
        }

        // Ambil input tanggal, defaultnya hari ini
        $tanggal_awal = $request->input('tanggal_awal', now()->startOfMonth()->toDateString());
        $tanggal_akhir = $request->input('tanggal_akhir', now()->toDateString());

        // Ambil data dari model
        $data = AbsenPulang::getTotalPulang($tanggal_awal, $tanggal_akhir);

        return response()->json([
            'tanggal_awal' => $tanggal_awal,
            'tanggal_akhir' => $tanggal_akhir,
            'total_lebih_cepat_pulang' => $data->total_lebih_cepat_pulang ?? 0,
            'total_tepat_waktu' => $data->total_tepat_waktu ?? 0,
            'total_lebih_lambat_pulang' => $data->total_lebih_lambat_pulang
        ]);
    }
}