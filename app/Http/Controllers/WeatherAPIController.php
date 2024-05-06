<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WeatherAPIController extends Controller
{
    public function getWeather(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Get the latitude and longitude from the request
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        // Call the weather API (for example, OpenWeatherMap API)
        $response = Http::get('https://api.openweathermap.org/data/2.5/weather', [
            'lat' => $latitude,
            'lon' => $longitude,
            'appid' => env('OPENWEATHERMAP_API_KEY'),
            'units' => 'metric', // Change units as needed
        ]);

        // Check if the request was successful
        if ($response->successful()) {
            $weatherData = $response->json();
            return response()->json($weatherData, 200);
        } else {
            return response()->json(['error' => 'Failed to fetch weather data'], 500);
        }
    }
}
