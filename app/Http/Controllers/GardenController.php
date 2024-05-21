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
            'plants.*.id' => 'exists:plants,id',
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








    
    public function addPlantToGarden(Request $request, $gardenId)
    {
        $user = auth()->user();
        $garden = $user->gardens()->findOrFail($gardenId);

        $validator = Validator::make($request->all(), [
            'plant_id' => 'required|exists:plants,id',
            'spacing' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plantId = $request->input('plant_id');
        $spacing = $request->input('spacing');

        // Check if the plant is already added to the garden
        if ($garden->plants()->where('plant_id', $plantId)->exists()) {
            return response()->json(['message' => 'The plant is already added to the garden.'], 422);
        }

        // Attach the plant to the garden with spacing
        $garden->plants()->attach($plantId, ['spacing' => $spacing]);

        return response()->json(['message' => 'Plant added to the garden successfully.'], 201);
    }











    public function deletePlantFromGarden($gardenId, $plantId)
    {
        $user = auth()->user();
        $garden = $user->gardens()->findOrFail($gardenId);

        // Detach the plant from the garden
        $garden->plants()->detach($plantId);

        return response()->json(['message' => 'Plant deleted from the garden successfully.'], 200);
    }
}
