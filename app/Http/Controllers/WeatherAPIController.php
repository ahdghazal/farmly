<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Garden;
use App\Models\User;

class WeatherController extends Controller
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.weather.api_key'); // Store API key in config/services.php
    }

    public function getUserWeather(Request $request)
    {
        $user = $request->user();
        $latitude = $user->latitude;
        $longitude = $user->longitude;

        $weather = $this->fetchWeather($latitude, $longitude);

        return response()->json($weather);
    }

    public function getGardenWeather(Request $request)
    {
        $user = $request->user();
        $gardens = Garden::where('user_id', $user->id)->get();
        $weatherData = [];

        foreach ($gardens as $garden) {
            $weatherData[$garden->id] = $this->fetchWeather($garden->latitude, $garden->longitude);
        }

        return response()->json($weatherData);
    }

    private function fetchWeather($latitude, $longitude)
    {
        $response = Http::get("http://api.openweathermap.org/data/2.5/weather", [
            'lat' => $latitude,
            'lon' => $longitude,
            'appid' => $this->apiKey,
            'units' => 'metric' // Change units as necessary
        ]);

        if ($response->successful()) {
            return $response->json();
        } else {
            return ['error' => 'Unable to fetch weather data'];
        }
    }
}
