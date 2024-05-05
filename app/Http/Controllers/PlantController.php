<?php
namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlantController extends Controller
{
    public function index(Request $request)
    {
        // Logic to fetch all plants or filter them based on request parameters
        $plants = Plant::query();

        // Filter plants by name
        if ($request->has('name')) {
            $plants->where('name', 'like', '%' . $request->name . '%');
        }

        // Filter plants by features
        // Example: soil_type, category, fertilization, etc.
        $features = ['soil_type', 'category', 'fertilization', 'pruning', 'support', 'spacing', 'season', 'water_need', 'light_needed', 'temperature'];
        foreach ($features as $feature) {
            if ($request->has($feature)) {
                $plants->where($feature, $request->$feature);
            }
        }

        // Return the filtered plants
        return response()->json($plants->get(), 200);
    }

    public function addToFavorites(Request $request, $plantId)
    {
        // Logic to add a plant to the user's favorite list
        // You can associate the plant with the authenticated user here
    }

    public function addToGarden(Request $request, $plantId)
    {
        // Logic to add a plant to the user's garden
        // You can associate the plant with the authenticated user's garden here
    }

    // Only admin can perform CRUD operations on plants
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'soil_type' => 'required|string',
            'category' => 'required|string',
            'fertilization' => 'required|string',
            'pruning' => 'required|string',
            'support' => 'required|boolean',
            'spacing' => 'required|string',
            'season' => 'required|string',
            'water_need' => 'required|string',
            'light_needed' => 'required|string',
            'temperature' => 'required|string',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create a new plant
        $plant = Plant::create($validator->validated());

        return response()->json($plant, 201);
    }

    public function show($id)
    {
        // Logic to fetch a specific plant by ID
        $plant = Plant::findOrFail($id);

        return response()->json($plant, 200);
    }

    public function update(Request $request, $id)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'soil_type' => 'required|string',
            'category' => 'required|string',
            'fertilization' => 'required|string',
            'pruning' => 'required|string',
            'support' => 'required|boolean',
            'spacing' => 'required|string',
            'season' => 'required|string',
            'water_need' => 'required|string',
            'light_needed' => 'required|string',
            'temperature' => 'required|string',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update the plant
        $plant = Plant::findOrFail($id);
        $plant->update($validator->validated());

        return response()->json($plant, 200);
    }

    public function destroy($id)
    {
        // Logic to delete a plant
        Plant::destroy($id);

        return response()->json(['message' => 'Plant deleted successfully'], 200);
    }
}
