<?php

namespace App\Http\Controllers;

use App\Models\Gender;
use Illuminate\Http\Request;

class GenderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Gender::all(), 200);
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
                'nama_gender' => 'required|string|max:255',
            ]);

            $gender = Gender::create($validated);

            return response()->json($gender, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to Edit divisi',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $gender = Gender::find($id);

        if (!$gender) {
            return response()->json(['error' => 'Gender not found'], 404);
        }

        return response()->json($gender, 200);
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
            $gender = Gender::find($id);

            if (!$gender) {
                return response()->json(['error' => 'Gender not found'], 404);
            }

            $validated = $request->validate([
                'nama_gender' => 'required|string|max:255',
            ]);

            $gender->update($validated);

            return response()->json($gender, 200);
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
        $gender = Gender::find($id);

        if (!$gender) {
            return response()->json(['error' => 'Gender not found'], 404);
        }

        $gender->delete();

        return response()->json(['message' => 'Gender deleted successfully'], 200);
    }
}
