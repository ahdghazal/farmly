<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteListController extends Controller
{
    public function index()
    {
        // Get the authenticated user's favorite plants
        $favoritePlants = Auth::user()->favoritePlants;

        return response()->json($favoritePlants, 200);
    }

    public function store(Request $request, $plantId)
    {
        // Find the plant by ID
        $plant = Plant::findOrFail($plantId);

        // Add the plant to the authenticated user's favorite list
        Auth::user()->favoritePlants()->attach($plant);

        return response()->json(['message' => 'Plant added to favorites successfully'], 201);
    }

    public function destroy($plantId)
    {
        // Find the plant by ID
        $plant = Plant::findOrFail($plantId);

        // Remove the plant from the authenticated user's favorite list
        Auth::user()->favoritePlants()->detach($plant);

        return response()->json(['message' => 'Plant removed from favorites successfully'], 200);
    }
}
