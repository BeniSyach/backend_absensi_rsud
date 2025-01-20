<?php

namespace App\Http\Controllers;

use App\Models\SPT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SPTController extends Controller
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
                'sortBy' => 'string|in:tanggal_spt,created_at,updated_at',
                'sortOrder' => 'string|in:ASC,DESC',
                'search' => 'nullable|string|max:255',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'user_id' => 'nullable|integer|exists:users,id'
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
                'start_date.date' => 'Format tanggal awal tidak valid.',
                'end_date.date' => 'Format tanggal akhir tidak valid.',
                'end_date.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal awal.',
                'user_id.exists' => 'User tidak ditemukan.'
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
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $userId = $request->input('user_id');
    
            // Build query with relationships
            $query = SPT::with(['user']);
    
            // Apply search if provided
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('lokasi_spt', 'LIKE', "%{$search}%")
                      ->orWhereHas('user', function($q) use ($search) {
                          $q->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                      });
                });
            }
    
            // Apply date range filter if provided
            if ($startDate && $endDate) {
                $query->whereBetween('tanggal_spt', [$startDate, $endDate]);
            }
    
            // Apply user filter if provided
            if ($userId) {
                $query->where('id_user', $userId);
            }
    
            // Apply ordering
            $query->orderBy($sortBy, $sortOrder);
    
            // Get paginated results
            $spts = $query->paginate($limit);
    
            // Check if data exists
            if ($spts->isEmpty()) {
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
                'data' => $spts
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data SPT',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'id_user' => 'required|exists:users,id',
                'tanggal_spt' => 'required|date',
                'waktu_spt' => 'required|date_format:H:i',
                'lama_acara' => 'required|integer|min:1',
                'lokasi_spt' => 'required|string|max:255',
                'file_spt' => 'required|file|mimes:pdf|max:2048',
                'status' => 'nullable|integer',
            ]);

            // Menyimpan file jika ada
            if ($request->hasFile('file_spt')) {
                $validated['file_spt'] = $request->file('file_spt')->store('spt_files', 'public');
            }            

            // Membuat SPT baru
            $spt = SPT::create($validated);

            return response()->json([
                'message' => 'SPT created successfully',
                'data' => $spt,
            ], 201);
        } catch (ValidationException $e) {
            // Menangani kesalahan validasi
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Menangani kesalahan umum
            return response()->json([
                'error' => 'Failed to create SPT',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $spt = SPT::with('user')->findOrFail($id);
            return response()->json($spt, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'SPT not found',
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {

            $SPT = SPT::find($id);

            if (!$SPT) {
                return response()->json(['error' => 'SPT not found'], 404);
            }

            // Validasi input
            $validated = $request->validate([
                'id_user' => 'required|exists:users,id',
                'tanggal_spt' => 'required|date',
                'waktu_spt' => 'required|date_format:H:i',
                'lama_acara' => 'required|integer|min:1',
                'lokasi_spt' => 'required|string|max:255',
                'file_spt' => 'nullable|file|mimes:pdf|max:2048',
            ]);

            // Proses upload file jika ada
            if ($request->hasFile('file_spt')) {
                // Hapus file lama jika ada
                if ($SPT->file_spt) {
                    Storage::delete($SPT->file_spt);
                }
                $validated['file_spt'] = $request->file('file_spt')->store('spt_files');
            }

            // Perbarui SPT
            $SPT->update($validated);

            return response()->json([
                'message' => 'SPT updated successfully',
                'data' => $SPT,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update SPT',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $spt = SPT::findOrFail($id);

            // Hapus file jika ada
            if ($spt->file_spt) {
                Storage::delete($spt->file_spt);
            }

            // Hapus data SPT
            $spt->delete();

            return response()->json([
                'message' => 'SPT deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete SPT',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getSptByUser($user_id)
    {
        try {
            $sptsByUser = SPT::with('user')
            ->where('id_user', $user_id)
            ->orderBy('tanggal_spt', 'DESC')  // Filter berdasarkan shift_id
            ->get();
            return response()->json($sptsByUser, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch SPTs',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function setStatusDiterima($id)
    {
        $spt = Spt::find($id); 

        if (!$spt) {
            return response()->json(['error' => 'Data SPT tidak ditemukan'], 404);
        }

        $spt->status = 1;
        $spt->save();

        // Kembalikan respons sukses
        return response()->json(['message' => 'Status SPT diterima'], 200);
    }

    public function setStatusDitolak($id)
    {
        $spt = Spt::find($id); 

        if (!$spt) {
            return response()->json(['error' => 'Data SPT tidak ditemukan'], 404);
        }

        $spt->status = 0;
        $spt->save();

        // Kembalikan respons sukses
        return response()->json(['message' => 'Status SPT ditolak'], 200);
    }
}
