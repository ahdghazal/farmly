<?php
namespace App\Http\Controllers;
use App\Models\FavoriteList;
use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class PlantController extends Controller{

    public function getAllPlants(Request $request)
    {
        $pageNo = $request->query('page', 1);
        $pageSize = 15;
        $plants = Plant::paginate($pageSize, ['*'], 'page', $pageNo);

        $user = Auth::user();
        $favoritePlants = [];
        if ($user) {
            $favoritePlants = $user->favoriteList ? $user->favoriteList->plants->pluck('id')->toArray() : [];
        }

        $plants->getCollection()->transform(function ($plant) use ($favoritePlants) {
            $plant->is_favorited = in_array($plant->id, $favoritePlants);
            return $plant;
        });

        $response = [
            'items' => $plants->items(),
            'page' => intval($pageNo),
            'pages' => $plants->lastPage()
        ];

        return response()->json($response, 200);
    }









    public function searchPlantsByName(Request $request)
    {
        $searchQuery = $request->query('name');
        $plants = Plant::where('name', 'like', '%' . $searchQuery . '%')->paginate(15);

        $user = Auth::user();
        $favoritePlants = [];
        if ($user) {
            $favoritePlants = $user->favoriteList ? $user->favoriteList->plants->pluck('id')->toArray() : [];
        }

        $plants->getCollection()->transform(function ($plant) use ($favoritePlants) {
            $plant->is_favorited = in_array($plant->id, $favoritePlants);
            return $plant;
        });

        $response = [
            'items' => $plants->items(),
            'page' => intval($plants->currentPage()),
            'pages' => $plants->lastPage()
        ];

        return response()->json($response, 200);
    }













 public function filterPlants(Request $request)
    {
        $request->validate([
            'soil_type' => 'sometimes|string',
            'category' => 'sometimes|string',
            'fertilization' => 'sometimes|string',
            'spacing' => 'sometimes|numeric',
            'season' => 'sometimes|string',
            'water_need' => 'sometimes|string',
            'light_needed' => 'sometimes|string',
            'min_temperature' => 'sometimes|integer',
            'max_temperature' => 'sometimes|integer',
        ]);

        $plantsQuery = Plant::query();
        $filters = $request->all();

        foreach ($filters as $attribute => $value) {
            if (empty($value) || !in_array($attribute, ['soil_type', 'category', 'fertilization', 'spacing', 'season', 'water_need', 'light_needed', 'min_temperature', 'max_temperature'])) {
                continue;
            }

            switch ($attribute) {
                case 'soil_type':
                case 'category':
                case 'fertilization':
                case 'light_needed':
                case 'water_need':
                    $plantsQuery->whereRaw("LOWER($attribute) LIKE ?", ['%' . strtolower($value) . '%']);
                    break;
                case 'spacing':
                    $plantsQuery->where($attribute, $value);
                    break;
                case 'season':
                    $plantsQuery->whereRaw("LOWER(season) LIKE ?", ['%' . strtolower($value) . '%']);
                    break;
                case 'min_temperature':
                    $plantsQuery->where('min_temperature', '>=', $value);
                    break;
                case 'max_temperature':
                    $plantsQuery->where('max_temperature', '<=', $value);
                    break;
                default:
                    break;
            }
        }

        $pageNo = $request->query('page', 1);
        $pageSize = 15;
        $plants = $plantsQuery->paginate($pageSize, ['*'], 'page', $pageNo);

        $user = Auth::user();
        $favoritePlants = [];
        if ($user) {
            $favoritePlants = $user->favoriteList ? $user->favoriteList->plants->pluck('id')->toArray() : [];
        }

        $plants->getCollection()->transform(function ($plant) use ($favoritePlants) {
            $plant->is_favorited = in_array($plant->id, $favoritePlants);
            return $plant;
        });

        $response = [
            'items' => $plants->items(),
            'page' => intval($pageNo),
            'pages' => $plants->lastPage()
        ];

        return response()->json($response, 200);
    }





    public function showPlant($id)
    {
        // Logic to fetch a specific plant by ID
        $plant = Plant::findOrFail($id);

        return response()->json($plant, 200);
    }





    
    public function getPopularPlants()
    {
        $user = Auth::user();
        $favoritePlants = [];
        if ($user) {
            $favoritePlants = $user->favoriteList ? $user->favoriteList->plants->pluck('id')->toArray() : [];
        }
    
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
    
        // Add is_favorited flag to each plant
        $popularPlants->transform(function ($plant) use ($favoritePlants) {
            $plant->is_favorited = in_array($plant->id, $favoritePlants);
            return $plant;
        });
    
        return response()->json(['popular_plants' => $popularPlants], 200);
    }
    

}
