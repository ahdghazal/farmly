<?php

namespace App\Http\Controllers;

use App\Models\FavoriteList;
use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FavoriteListController extends Controller
{
    public function addToFavoriteList(Request $request)
    {
        // Ensure user is authenticated
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'plant_id' => 'required|integer|exists:plants,id',
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the favorite list for the user
        $favoriteList = $user->favoriteList;

        // If the favorite list doesn't exist (shouldn't happen, but just in case), create one
        if (!$favoriteList) {
            $favoriteList = FavoriteList::create([
                'user_id' => $user->id,
            ]);
        }

        // Add the plant to the favorite list if it's not already added
        if (!$favoriteList->plants()->where('plant_id', $request->input('plant_id'))->exists()) {
            $favoriteList->plants()->attach($request->input('plant_id'));
            return response()->json(['message' => 'Plant added to favorite list'], 200);
        }

        return response()->json(['message' => 'Plant already in favorite list'], 200);
    }

    public function removeFromFavoriteList(Request $request)
    {
        // Ensure user is authenticated
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'plant_id' => 'required|integer|exists:plants,id',
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the favorite list for the user
        $favoriteList = $user->favoriteList;

        // If the favorite list exists, remove the plant from it
        if ($favoriteList) {
            $favoriteList->plants()->detach($request->input('plant_id'));
            return response()->json(['message' => 'Plant removed from favorite list'], 200);
        }

        return response()->json(['message' => 'Favorite list not found'], 404);
    }
}
