<?php

namespace App\Models;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthUserController;
use App\Http\Controllers\PlantController;
use App\Http\Controllers\FavoriteListController;
use App\Http\Controllers\GardenController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\NotificationsController;

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
    Route::post('/filterPlants', [PlantController::class, 'filterPlants']); //done
    Route::get('/searchPlantsByName', [PlantController::class, 'searchPlantsByName']); //done
    Route::get('/getAllPlants', [PlantController::class, 'getAllPlants']); //done

    Route::post('/addToFavoriteList', [FavoriteListController::class, 'addToFavoriteList']); //done
    Route::delete('/removeFromFavoriteList', [FavoriteListController::class, 'removeFromFavoriteList']); //done
    Route::get('/showFavoriteList', [FavoriteListController::class, 'showFavoriteList']); //done
    Route::get('getPopularPlants', [PlantController::class, 'getPopularPlants']); //done


    Route::get('/showGardens', [GardenController::class, 'showGardens']); //done
    Route::post('/addGarden', [GardenController::class, 'addGarden']); //done
    Route::get('/showGardenPlants/{id}', [GardenController::class, 'showGardenPlants']); //done
    Route::put('/updateGarden/{id}', [GardenController::class, 'updateGarden']); //done
    Route::delete('/deleteGarden/{id}', [GardenController::class, 'deleteGarden']); //done
    Route::post('/addPlantToGarden', [GardenController::class, 'addPlantToGarden']); //done
    Route::delete('/deletePlantFromGarden', [GardenController::class, 'deletePlantFromGarden']);//done

    Route::get('/getGardenWeather', [WeatherController::class, 'getGardenWeather']); //done
    Route::get('/getUserWeather', [WeatherController::class, 'getUserWeather']); //done



    Route::get('/getPosts', [CommunityController::class, 'getPosts']);
    Route::get('/getMyPosts', [CommunityController::class, 'getMyPosts']);
    Route::post('/createPost', [CommunityController::class, 'createPost']);
    Route::post('/likePost/{postId}', [CommunityController::class, 'likePost']);
    Route::post('/unlikePost/{postId}', [CommunityController::class, 'unlikePost']);
    Route::post('/savePost/{postId}', [CommunityController::class, 'savePost']);
    Route::post('/unsavePost/{postId}', [CommunityController::class, 'unsavePost']);
    Route::post('/replyToPost/{postId}', [CommunityController::class, 'replyToPost']);
    Route::delete('/deleteReply/{replyId}', [CommunityController::class, 'deleteReply']);
    Route::get('/getSavedPosts', [CommunityController::class, 'getSavedPosts']);
    Route::get('/searchPosts', [CommunityController::class, 'searchPosts']);
    Route::delete('/deletePost/{postId}', [CommunityController::class, 'deletePost']);


    Route::get('/notifications', [NotificationsController::class, 'getNotifications']);
    Route::post('/notifications/{id}/read', [NotificationsController::class, 'markAsRead']);


    
    Route::post('/messages', [ChatController::class, 'sendMessage']);
    Route::get('/messages/{userId}', [ChatController::class, 'getMessages']);


});