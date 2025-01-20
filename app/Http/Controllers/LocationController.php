<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        $locations = Location::all();
        return response()->json($locations);
    }

    public function store(Request $request)
    {
        $request->validate([
            'place_name' => 'required|string|max:255',
            'division_name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'nullable|integer|min:0',
        ]);

        $location = Location::create($request->all());
        return response()->json($location, 201);
    }

    public function show($id)
    {
        $location = Location::find($id);

        if (!$location) {
            return response()->json(['message' => 'Location not found'], 404);
        }

        return response()->json($location);
    }

    public function update(Request $request, $id)
    {
        $location = Location::find($id);

        if (!$location) {
            return response()->json(['message' => 'Location not found'], 404);
        }

        $request->validate([
            'place_name' => 'sometimes|required|string|max:255',
            'division_name' => 'sometimes|required|string|max:255',
            'latitude' => 'sometimes|required|numeric',
            'longitude' => 'sometimes|required|numeric',
            'radius' => 'nullable|integer|min:0',
        ]);

        $location->update($request->all());
        return response()->json($location);
    }

    public function destroy($id)
    {
        $location = Location::find($id);

        if (!$location) {
            return response()->json(['message' => 'Location not found'], 404);
        }

        $location->delete();
        return response()->json(['message' => 'Location deleted successfully']);
    }
}
