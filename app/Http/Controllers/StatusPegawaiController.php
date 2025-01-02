<?php

namespace App\Http\Controllers;

use App\Models\StatusPegawai;
use Illuminate\Http\Request;

class StatusPegawaiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(StatusPegawai::all(), 200);
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
                'nama_status' => 'required|string|max:255',
            ]);

            $statusPegawai = StatusPegawai::create($validated);

            return response()->json($statusPegawai, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create status pegawai',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $statusPegawai = StatusPegawai::find($id);

        if (!$statusPegawai) {
            return response()->json(['error' => 'Status Pegawai not found'], 404);
        }

        return response()->json($statusPegawai, 200);
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
            $statusPegawai = StatusPegawai::find($id);

            if (!$statusPegawai) {
                return response()->json(['error' => 'Status Pegawai not found'], 404);
            }

            $validated = $request->validate([
                'nama_status' => 'required|string|max:255',
            ]);

            $statusPegawai->update($validated);

            return response()->json($statusPegawai, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update status pegawai',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $statusPegawai = StatusPegawai::find($id);

        if (!$statusPegawai) {
            return response()->json(['error' => 'Status Pegawai not found'], 404);
        }

        $statusPegawai->delete();

        return response()->json(['message' => 'Status Pegawai deleted successfully'], 200);
    }
}
