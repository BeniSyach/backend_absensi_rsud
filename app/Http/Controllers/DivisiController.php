<?php

namespace App\Http\Controllers;

use App\Models\Divisi;
use Illuminate\Http\Request;

class DivisiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Divisi::with('atasan')->get(), 200);
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
        // Validasi input dari request\
        try {
            $validated = $request->validate([
                'nama_divisi' => 'required|string|max:255',
                'id_atasan' => 'nullable|exists:users,id',
                'id_jabatan' => 'required|exists:jabatan,id',
            ]);

            // Jika validasi berhasil, simpan data ke database

            $divisi = Divisi::create($validated);
            return response()->json($divisi, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create divisi',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $divisi = Divisi::with('atasan')->find($id);

        if (!$divisi) {
            return response()->json(['error' => 'Divisi not found'], 404);
        }

        return response()->json($divisi, 200);
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
            $divisi = Divisi::find($id);

            if (!$divisi) {
                return response()->json(['error' => 'Divisi not found'], 404);
            }

            $validated = $request->validate([
                'nama_divisi' => 'required|string|max:255',
                'id_atasan' => 'nullable|exists:users,id',
                'id_jabatan' => 'required|exists:jabatan,id',
            ]);

            $divisi->update($validated);

            return response()->json($divisi, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to Edit divisi',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $divisi = Divisi::find($id);

        if (!$divisi) {
            return response()->json(['error' => 'Divisi not found'], 404);
        }

        $divisi->delete();

        return response()->json(['message' => 'Divisi deleted successfully'], 200);
    }
}
