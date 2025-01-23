<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Shift::with('opd')->get(), 200);
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
                'nama_shift' => 'required|string|max:255',
                'opd_id' => 'required|string|exists:locations,id'
            ]);

            $shift = Shift::create($validated);
            return response()->json($shift, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create shift',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $Shift = Shift::with('opd')->find($id);

        if (!$Shift) {
            return response()->json(['error' => 'Shift not found'], 404);
        }

        return response()->json($Shift, 200);
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
            $Shift = Shift::find($id);

            if (!$Shift) {
                return response()->json(['error' => 'Shift not found'], 404);
            }

            $validated = $request->validate([
                'nama_shift' => 'required|string|max:255',
                'opd_id' => 'required|string|exists:locations,id'
            ]);

            $Shift->update($validated);

            return response()->json($Shift, 200);
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
        $Shift = Shift::find($id);

        if (!$Shift) {
            return response()->json(['error' => 'Shift not found'], 404);
        }

        $Shift->delete();

        return response()->json(['message' => 'Shift deleted successfully'], 200);
    }
}
