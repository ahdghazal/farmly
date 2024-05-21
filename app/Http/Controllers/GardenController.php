<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Garden;
use App\Models\Plant;
use Validator;

class GardenController extends Controller
{
    public function showGardens()
    {
        $gardens = auth()->user()->gardens()->with('plants')->get();
        return response()->json($gardens, 200);
    }









    public function addGarden(Request $request)
{
    $user = auth()->user();

    if ($user->gardens()->count() >= 5) {
        return response()->json(['message' => 'You can only have up to 5 gardens.'], 403);
    }

    $validator = Validator::make($request->all(), [
        'name' => 'required|string|unique:gardens',
        'location' => 'required|string',
        'area' => 'required|integer',
        'is_inside' => 'required|boolean',
        'plants' => 'array',
        'plants.*.id' => 'required|exists:plants,id',
        'plants.*.spacing' => 'required|integer',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $garden = $user->gardens()->create($request->only(['name', 'location', 'area', 'is_inside']));

    if ($request->has('plants')) {
        foreach ($request->plants as $plant) {
            $garden->plants()->attach($plant['id'], ['spacing' => $plant['spacing']]);
        }
    }

    return response()->json($garden->load('plants'), 201);
}









    public function showGardenPlants($id)
    {
        $garden = auth()->user()->gardens()->with('plants')->findOrFail($id);
        return response()->json($garden, 200);
    }

    public function updateGarden(Request $request, $id)
    {
        $garden = auth()->user()->gardens()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:gardens,name,' . $id,
            'location' => 'required|string',
            'area' => 'required|integer',
            'is_inside' => 'required|boolean',
            'plants' => 'array',
            'plants.*.id' => 'exists:plants,id',
            'plants.*.spacing' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $garden->update($request->only(['name', 'location', 'area', 'is_inside']));

        if ($request->has('plants')) {
            $garden->plants()->detach();
            foreach ($request->plants as $plant) {
                $garden->plants()->attach($plant['id'], ['spacing' => $plant['spacing']]);
            }
        }

        return response()->json($garden->load('plants'), 200);
    }








    public function deleteGarden($id)
    {
        $garden = auth()->user()->gardens()->findOrFail($id);
        $garden->delete();
        return response()->json(['message' => 'Garden deleted successfully.'], 200);
    }









    public function addPlantToGarden(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'garden_id' => 'required|exists:gardens,id',
            'plant_id' => 'required|exists:plants,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Retrieve garden and plant
        $garden = Garden::findOrFail($request->input('garden_id'));
        $plant = Plant::findOrFail($request->input('plant_id'));
    
        // Retrieve spacing of the plant
        $newPlantSpacing = $plant->spacing;
    
        // Retrieve area of the garden
        $gardenArea = $garden->area;
    
        // Calculate the total area consumed by all plants in the garden
        $totalArea = $garden->plants()->sum('plants.spacing');
    
        // Calculate available space in the garden
        $availableSpace = $gardenArea - $totalArea;
    
        // Check if adding the new plant exceeds the total area limit
        if ($availableSpace >= $newPlantSpacing) {
            // Attach the plant to the garden
            $garden->plants()->attach($request->input('plant_id'), ['spacing' => $newPlantSpacing]);
            return response()->json(['message' => 'Plant added to the garden successfully.'], 201);
        } else {
            return response()->json(['message' => 'Garden is full. Cannot add more plants.'], 422);
        }
    }
    
    
    

    











    public function deletePlantFromGarden(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'garden_id' => 'required|exists:gardens,id',
            'plant_id' => 'required|exists:plants,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $user = auth()->user();
        $garden = $user->gardens()->find($request->input('garden_id'));
    
        if (!$garden) {
            return response()->json(['message' => 'Garden not found.'], 404);
        }
    
        // Check if the plant exists in the garden
        if (!$garden->plants()->where('plant_id', $request->input('plant_id'))->exists()) {
            return response()->json(['message' => 'Plant not found in the garden.'], 404);
        }
    
        // Detach the plant from the garden
        $garden->plants()->detach($request->input('plant_id'));
    
        return response()->json(['message' => 'Plant deleted from the garden successfully.'], 200);
    }
    
    
}
