<?php
namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlantController extends Controller{

    public function getAllPlants(Request $request)
    {   
        $pageNo = $request->query('page', 1);
    
        $pageSize = 15;
    
        $plants = Plant::paginate($pageSize, ['*'], 'page', $pageNo);
    
        $pageCount = $plants->lastPage();
    
        $response = [
            'items' => $plants->items(),
            'page' => intval($pageNo),
            'pages' => $pageCount 
        ];
    
        return response()->json($response, 200);
    }


    public function searchPlantsByName(Request $request)
    {
        $searchQuery = $request->query('name');
    
        $plants = Plant::where('name', 'like', '%' . $searchQuery . '%')->paginate(15);
    
        $response = [
            'items' => $plants->items(), 
            'page' => intval($plants->currentPage()),
            'pages' => $plants->lastPage()
        ];
    

    return response()->json($response, 200);
}


public function filterPlants(Request $request)
{
    $filters = $request->all();

    $plantsQuery = Plant::query();

    foreach ($filters as $attribute => $value) {
        // Skip empty or non-filterable attributes
        if (empty($value) || !in_array($attribute, ['soil_type', 'category', 'fertilization', 'spacing', 'season', 'water_need', 'light_needed', 'temperature'])) {
            continue;
        }

        // Apply the filter
        $plantsQuery->where($attribute, $value);
    }

    $pageNo = $request->query('page', 1);
    $pageSize = 15;
    $plants = $plantsQuery->paginate($pageSize, ['*'], 'page', $pageNo);

    $response = [
        'items' => $plants->items(),
        'page' => intval($pageNo),
        'pages' => $plants->lastPage()
    ];

    return response()->json($response, 200);
}

    






public function addPlant(Request $request)
{
    // Validate the request
    $validator = Validator::make($request->all(), [
        '*.name' => 'unique:plants|required|string',
        '*.soil_type' => 'required|string',
        '*.category' => 'required|string',
        '*.fertilization' => 'required|string',
        '*.pruning' => 'required|string',
        '*.support' => 'required|string',
        '*.spacing' => 'required|string',
        '*.season' => 'required|string',
        '*.water_need' => 'required|string',
        '*.light_needed' => 'required|string',
        '*.temperature' => 'required|string',
        '*.description' => 'required|string',
        '*.picture' => 'nullable|string',

    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $createdPlants = [];
    $plantsData = $validator->validated();
    foreach ($plantsData as $plantData) {
        // Create a new plant
        $plant = Plant::create($plantData);

        // Add the created plant to the array
        $createdPlants[] = $plant;
    }

    return response()->json($createdPlants, 201);
}






    public function showPlant($id)
    {
        // Logic to fetch a specific plant by ID
        $plant = Plant::findOrFail($id);

        return response()->json($plant, 200);
    }





    public function updatePlant(Request $request, $id)
    {
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


    


    public function deletePlant($id)
    {
        // Logic to delete a plant
        Plant::destroy($id);

        return response()->json(['message' => 'Plant deleted successfully'], 200);
    }
}
