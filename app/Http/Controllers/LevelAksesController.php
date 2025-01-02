<?php

namespace App\Http\Controllers;

use App\Models\LevelAkses;
use Illuminate\Http\Request;

class LevelAksesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $levelAkses = LevelAkses::with('user')->get();
        return response()->json($levelAkses, 200);
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
                'nama_level' => 'required|string|max:255',
            ]);

            $levelAkses = LevelAkses::create($validated);

            return response()->json($levelAkses, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create level akses',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $levelAkses = LevelAkses::with('user')->find($id);

        if (!$levelAkses) {
            return response()->json(['error' => 'Level Akses not found'], 404);
        }

        return response()->json($levelAkses, 200);
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
            $levelAkses = LevelAkses::find($id);

            if (!$levelAkses) {
                return response()->json(['error' => 'Level Akses not found'], 404);
            }

            $validated = $request->validate([
                'nama_level' => 'required|string|max:255',
            ]);

            $levelAkses->update($validated);

            return response()->json($levelAkses, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to Edit level akses',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $levelAkses = LevelAkses::find($id);

        if (!$levelAkses) {
            return response()->json(['error' => 'Level Akses not found'], 404);
        }

        $levelAkses->delete();

        return response()->json(['message' => 'Level Akses deleted successfully'], 200);
    }
}
