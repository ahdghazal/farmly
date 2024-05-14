<?php

namespace App\Models;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthUserController;
use App\Http\Controllers\PlantController;
use App\Http\Controllers\FavoriteListController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/register', [AuthUserController::class, 'register']); //done
Route::post('/login', [AuthUserController::class, 'login']); //done
Route::post('/verify', [AuthUserController::class, 'verify']); //done
Route::post('/resendCode', [AuthUserController::class, 'resendCode']); //done
Route::post('/sendResetPasswordOTP', [AuthUserController::class, 'sendResetPasswordOTP']);//done
Route::post('/resetPassword', [AuthUserController::class, 'resetPassword']);//done

Route::group(['middleware' => ['auth:sanctum']], function() {
    Route::post('/logout', [AuthUserController::class, 'logout']); //done
    Route::get('/showProfile', [AuthUserController::class, 'showProfile']); //done
    Route::post('/updateProfile', [AuthUserController::class, 'updateProfile']);//done
    Route::post('/changePassword', [AuthUserController::class, 'changePassword']);//done
    Route::post('/uploadPicture', [AuthUserController::class, 'uploadPicture']);//done

    Route::get('/showPlant/{id}', [PlantController::class, 'showPlant']); //done
    Route::get('/filterPlants', [PlantController::class, 'filterPlants']); //done
    Route::get('/searchPlantsByName', [PlantController::class, 'searchPlantsByName']); //done
    Route::get('/getAllPlants', [PlantController::class, 'getAllPlants']); //done

    Route::post('/addToFavoriteList', [FavoriteListController::class, 'addToFavoriteList']);
    Route::delete('/removeFromFavoriteList', [FavoriteListController::class, 'removeFromFavoriteList']);

});