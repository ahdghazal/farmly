<?php

namespace App\Http\Controllers;

use App\Models\Garden;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GardenController extends Controller
{
    public function index()
    {
        // Get all gardens belonging to the authenticated user
        $gardens = Auth::user()->gardens;

        return response()->json($gardens, 200);
    }

    public function store(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'size' => 'required|numeric',
            'location' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create a new garden for the authenticated user
        $garden = Garden::create([
            'user_id' => Auth::id(),
            'size' => $request->size,
            'location' => $request->location,
        ]);

        return response()->json($garden, 201);
    }

    public function show($id)
    {
        // Fetch a specific garden by ID
        $garden = Garden::findOrFail($id);

        // Ensure the authenticated user owns this garden
        if ($garden->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($garden, 200);
    }

    public function update(Request $request, $id)
    {
        // Fetch the garden to update
        $garden = Garden::findOrFail($id);

        // Ensure the authenticated user owns this garden
        if ($garden->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'size' => 'required|numeric',
            'location' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update the garden
        $garden->update([
            'size' => $request->size,
            'location' => $request->location,
        ]);

        return response()->json($garden, 200);
    }

    public function destroy($id)
    {
        // Fetch the garden to delete
        $garden = Garden::findOrFail($id);

        // Ensure the authenticated user owns this garden
        if ($garden->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Delete the garden
        $garden->delete();

        return response()->json(['message' => 'Garden deleted successfully'], 200);
    }
}
