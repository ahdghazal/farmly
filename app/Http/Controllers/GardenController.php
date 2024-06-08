<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Garden;
use App\Models\Plant;
use App\Models\GardenPlantEntry;
use Validator;

class GardenController extends Controller
{
    public function showGardens()
{
    $gardens = Garden::withCount('plantEntries')->get(['name', 'location', 'area', 'is_inside', 'plant_entries_count']);

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
                $garden->plantEntries()->create([
                    'plant_id' => $plant['id'],
                    'quantity' => 1,
                ]);
            }
        }

        return response()->json($garden->load('plantEntries.plant'), 201);
    }








    public function showGardenPlants($gardenId)
    {
        $garden = Garden::with('plantEntries.plant')->findOrFail($gardenId);
    
        $groupedEntries = [];
    
        foreach ($garden->plantEntries as $plantEntry) {
            $plantName = $plantEntry->plant->name;
            $quantity = $plantEntry->quantity;
    
            if (!array_key_exists($plantName, $groupedEntries)) {
                $groupedEntries[$plantName] = [
                    'type' => $plantName,
                    'quantity' => 0,
                    'plants' => []
                ];
            }
    
            $groupedEntries[$plantName]['quantity'] += $quantity;
    
            $groupedEntries[$plantName]['plants'][] = $plantEntry;
        }
    
        $groupedEntries = array_values($groupedEntries);
    
        return response()->json($groupedEntries, 200);
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
            $garden->plantEntries()->delete();
            foreach ($request->plants as $plant) {
                $garden->plantEntries()->create([
                    'plant_id' => $plant['id'],
                    'quantity' => 1,
                ]);
            }
        }

        return response()->json($garden->load('plantEntries.plant'), 200);
    }






    public function deleteGarden($id)
    {
        $garden = auth()->user()->gardens()->findOrFail($id);
        $garden->delete();
        return response()->json(['message' => 'Garden deleted successfully.'], 200);
    }





    public function addPlantToGarden(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'garden_id' => 'required|exists:gardens,id',
            'plant_id' => 'required|exists:plants,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $garden = Garden::findOrFail($request->input('garden_id'));
        $plant = Plant::findOrFail($request->input('plant_id'));

        $newPlantSpacing = $plant->spacing;
        $gardenArea = $garden->area;
        $totalArea = $garden->plantEntries()->join('plants', 'garden_plant_entries.plant_id', '=', 'plants.id')
        ->selectRaw('sum(plants.spacing * garden_plant_entries.quantity) as aggregate')
        ->where('garden_plant_entries.garden_id', $garden->id)
        ->first()
        ->aggregate;
        $availableSpace = $gardenArea - $totalArea;

        if ($availableSpace >= $newPlantSpacing) {
            $garden->plantEntries()->create([
                'plant_id' => $request->input('plant_id'),
                'quantity' => 1,
            ]);
            return response()->json(['message' => 'Plant added to the garden successfully.'], 201);
        } else {
            return response()->json(['message' => 'Garden is full. Cannot add more plants.'], 422);
        }
    }






    public function deletePlantFromGarden(Request $request)
    {
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

        $plantEntry = $garden->plantEntries()->where('plant_id', $request->input('plant_id'))->first();

        if (!$plantEntry) {
            return response()->json(['message' => 'Plant not found in the garden.'], 404);
        }

        if ($plantEntry->quantity > 1) {
            $plantEntry->quantity -= 1;
            $plantEntry->save();
        } else {
            $plantEntry->delete();
        }

        return response()->json(['message' => 'Plant removed from the garden successfully.'], 200);
    }
}
