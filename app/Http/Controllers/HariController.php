<?php

namespace App\Http\Controllers;

use App\Models\Hari;
use Illuminate\Http\Request;

class HariController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $haris = Hari::all();
        return response()->json($haris);
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
                'nama_hari' => 'required|string|max:255',
            ]);

            $hari = Hari::create($validated);

            return response()->json($hari, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create hari',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $hari = Hari::find($id);

        if (!$hari) {
            return response()->json(['error' => 'Hari not found'], 404);
        }

        return response()->json($hari, 200);
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
            $Hari = Hari::find($id);

            if (!$Hari) {
                return response()->json(['error' => 'Hari not found'], 404);
            }

            $validated = $request->validate([
                'nama_hari' => 'required|string|max:255',
            ]);

            $Hari->update($validated);

            return response()->json($Hari, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to Edit Hari',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $Hari = Hari::find($id);

        if (!$Hari) {
            return response()->json(['error' => 'Hari not found'], 404);
        }

        $Hari->delete();

        return response()->json(['message' => 'Hari deleted successfully'], 200);
    }
}
