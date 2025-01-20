<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use Illuminate\Http\Request;

class JabatanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Jabatan::all(), 200);
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
                'nama_jabatan' => 'required|string|max:255',
            ]);

            $jabatan = Jabatan::create($validated);

            return response()->json($jabatan, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create jabatan',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $jabatan = Jabatan::find($id);

        if (!$jabatan) {
            return response()->json(['error' => 'Jabatan not found'], 404);
        }

        return response()->json($jabatan, 200);
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
            $jabatan = Jabatan::find($id);

            if (!$jabatan) {
                return response()->json(['error' => 'Jabatan not found'], 404);
            }

            $validated = $request->validate([
                'nama_jabatan' => 'required|string|max:255',
            ]);

            $jabatan->update($validated);

            return response()->json($jabatan, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to Edit jabatan',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $jabatan = Jabatan::find($id);

        if (!$jabatan) {
            return response()->json(['error' => 'Jabatan not found'], 404);
        }

        $jabatan->delete();

        return response()->json(['message' => 'Jabatan deleted successfully'], 200);
    }
}
