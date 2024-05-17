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
    $user = Auth::user();
    if (!$user) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $validator = Validator::make($request->all(), [
        'plant_id' => 'required|integer|exists:plants,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $favoriteList = $user->favoriteList;

    if (!$favoriteList) {
        $favoriteList = FavoriteList::create([
            'user_id' => $user->id,
        ]);
    }

    if (!$favoriteList->plants()->where('plant_id', $request->input('plant_id'))->exists()) {
        $favoriteList->plants()->attach($request->input('plant_id'));

        // Increment the favorite count of the plant
        $plant = Plant::findOrFail($request->input('plant_id'));
        $plant->increment('favorites_count');

        return response()->json(['message' => 'Plant added to favorite list'], 200);
    }

    return response()->json(['message' => 'Plant already in favorite list'], 200);
}



    public function removeFromFavoriteList(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'plant_id' => 'required|integer|exists:plants,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $favoriteList = $user->favoriteList;

        if ($favoriteList) {
            $favoriteList->plants()->detach($request->input('plant_id'));
            return response()->json(['message' => 'Plant removed from favorite list'], 200);
        }

        return response()->json(['message' => 'Favorite list not found'], 404);
    }


    public function showFavoriteList(Request $request)
    {
        $user = Auth::user();

        $favoriteList = $user->favoriteList;

        if ($favoriteList) {
            $plants = $favoriteList->plants;
            return response()->json(['plants' => $plants], 200);
        }

        return response()->json(['message' => 'Favorite list not found'], 404);
    }
}
