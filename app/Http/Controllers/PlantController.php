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
    // Retrieve all request filters
    $filters = $request->all();

    // Initialize the query builder for the Plant model
    $plantsQuery = Plant::query();

    // Loop through each filter
    foreach ($filters as $attribute => $value) {
        // Skip empty or non-filterable attributes
        if (empty($value) || !in_array($attribute, ['soil_type', 'category', 'fertilization', 'spacing', 'season', 'water_need', 'light_needed', 'min_temperature', 'max_temperature'])) {
            continue;
        }

        // Apply the filter based on the attribute
        switch ($attribute) {
            case 'soil_type':
            case 'category':
            case 'fertilization':
            case 'light_needed':
                // Case-insensitive search for string attributes
                $plantsQuery->where($attribute, 'ILIKE', '%' . $value . '%');
                break;
            case 'spacing':
                // Filter based on exact value for spacing
                $plantsQuery->where($attribute, $value);
                break;
            case 'season':
                // Case-insensitive search for any plants that contain the provided season in the list
                $plantsQuery->whereRaw("LOWER(season) LIKE ?", ['%' . strtolower($value) . '%']);
                break;
            case 'water_need':
                // Case-insensitive search for water_need values
                $plantsQuery->where('water_need', 'ILIKE', '%' . $value . '%');
                break;
            case 'min_temperature':
                // Filter out plants with min_temperature less than provided value
                $plantsQuery->where('min_temperature', '>=', $value);
                break;
            case 'max_temperature':
                // Filter out plants with max_temperature more than provided value
                $plantsQuery->where('max_temperature', '<=', $value);
                break;
            default:
                break;
        }
    }

    // Paginate the results
    $pageNo = $request->query('page', 1);
    $pageSize = 15;
    $plants = $plantsQuery->paginate($pageSize, ['*'], 'page', $pageNo);

    // Prepare the response
    $response = [
        'items' => $plants->items(),
        'page' => intval($pageNo),
        'pages' => $plants->lastPage()
    ];

    // Return the response
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
        '*.spacing' => 'required|numeric',
        '*.season' => 'required|string',
        '*.water_need' => 'required|string',
        '*.light_needed' => 'required|string',
        '*.min_temperature' => 'required|integer',
        '*.max_temperature' => 'required|integer',
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
            'spacing' => 'required|numeric',
            'season' => 'required|string',
            'water_need' => 'required|string',
            'light_needed' => 'required|string',
            'min_temperature' => 'required|integer',
            'max_temperature' => 'required|integer',
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


    public function getPopularPlants()
{
    // Get 15 plants with the highest favorite count
    $popularPlants = Plant::orderBy('favorites_count', 'desc')
                         ->take(15)
                         ->get();

    // If there are less than 15 popular plants, fill the remaining with random plants
    if ($popularPlants->count() < 15) {
        $remainingCount = 15 - $popularPlants->count();
        $randomPlants = Plant::where('favorites_count', '=', 0)->inRandomOrder()->take($remainingCount)->get();
        $popularPlants = $popularPlants->merge($randomPlants);
    }

    return response()->json(['popular_plants' => $popularPlants], 200);
}



    public function deletePlant($id)
    {
        // Logic to delete a plant
        Plant::destroy($id);

        return response()->json(['message' => 'Plant deleted successfully'], 200);
    }



}
