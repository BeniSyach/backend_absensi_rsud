<?php

namespace App\Http\Controllers;

use App\Models\WaktuKerja;
use Illuminate\Http\Request;

class WaktuKerjaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $waktuKerjas = WaktuKerja::with(['hari', 'shift'])->get();
        return response()->json($waktuKerjas);
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
                'hari_id' => 'required|exists:haris,id',
                'shift_id' => 'required|exists:shifts,id',
                'jam_mulai' => 'required|date_format:H:i',
                'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            ]);

            $waktuKerja = WaktuKerja::create($validated);
            return response()->json($waktuKerja, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create waktu kerja',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $waktu_kerja = WaktuKerja::find($id);

        if (!$waktu_kerja) {
            return response()->json(['error' => 'Waktu Kerja not found'], 404);
        }

        return response()->json($waktu_kerja, 200);
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
            $waktu_kerja = WaktuKerja::find($id);

            if (!$waktu_kerja) {
                return response()->json(['error' => 'Waktu Kerja not found'], 404);
            }

            $validated = $request->validate([
                'hari_id' => 'required|exists:haris,id',
                'shift_id' => 'required|exists:shifts,id',
                'jam_mulai' => 'required|date_format:H:i',
                'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            ]);

            $waktu_kerja->update($validated);

            return response()->json($waktu_kerja, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to Edit Shift',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $waktuKerja = WaktuKerja::find($id);

        if (!$waktuKerja) {
            return response()->json(['error' => 'Waktu Kerja not found'], 404);
        }

        $waktuKerja->delete();

        return response()->json(['message' => 'Shift deleted successfully'], 200);
    }

    public function getByShift($shiftId)
    {
        // Ambil data WaktuKerja yang memiliki shift_id sesuai dengan parameter
        $waktuKerjas = WaktuKerja::with(['hari', 'shift'])
            ->where('shift_id', $shiftId)  // Filter berdasarkan shift_id
            ->get();

        return response()->json($waktuKerjas);
    }

}
