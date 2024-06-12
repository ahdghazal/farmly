<?php

namespace App\Models;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\AuthUserController;
use App\Http\Controllers\PlantController;
use App\Http\Controllers\FavoriteListController;
use App\Http\Controllers\GardenController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;

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
Route::post('/admin/send-reset-password-otp', [AdminController::class, 'sendResetPasswordOTP']); //done
Route::post('/admin/login', [AdminController::class, 'adminLogin']); //done
Route::post('/admin/reset-password', [AdminController::class, 'resetPassword']); //done


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
    Route::post('report/{postId}', [CommunityController::class, 'reportPost']);


    Route::get('/notifications', [NotificationsController::class, 'getNotifications']);
    Route::post('/notifications/{id}/read', [NotificationsController::class, 'markAsRead']);


    //for app user side chat implementation

    Route::get('conversations', [ConversationController::class, 'index']);
    Route::post('conversations', [ConversationController::class, 'store']);
    Route::get('conversations/{id}', [ConversationController::class, 'show']);
    Route::delete('conversations/{id}', [ConversationController::class, 'destroy']);

    Route::post('conversations/{conversationId}/messages', [MessageController::class, 'store']);
    Route::patch('conversations/{conversationId}/messages/{messageId}', [MessageController::class, 'update']);
    Route::delete('conversations/{conversationId}/messages/{messageId}', [MessageController::class, 'destroy']);
    Route::patch('conversations/{conversationId}/messages/{messageId}/read', [MessageController::class, 'markAsRead']);
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Authentication routes
    Route::post('/admin/logout', [AdminController::class, 'logout']); //done
    
    // Profile routes
    Route::post('/admin/change-password', [AdminController::class, 'changePassword']); //done
    Route::get('/admin/showProfile', [AdminController::class, 'showProfile']); //done
    Route::put('/admin/updateProfile', [AdminController::class, 'updateProfile']); //done
    Route::post('/admin/upload-picture', [AdminController::class, 'uploadPicture']); //done
    
    // User and post management routes
    Route::delete('/admin/deleteUser/{id}', [AdminController::class, 'deleteUser']); //done
    Route::delete('/admin/posts/{id}', [AdminController::class, 'deletePost']); //done
    
    // Insights and statistics routes
    Route::get('/admin/statistics/community', [AdminController::class, 'getCommunityStatistics']); //dome
    Route::get('/admin/statistics/plant-categories', [AdminController::class, 'getPlantCategoriesCount']); //done
    Route::get('/admin/statistics/total-gardens', [AdminController::class, 'getTotalGardens']); //done
    Route::get('/admin/statistics/total-users', [AdminController::class, 'getTotalUsers']); //done
    Route::get('/admin/top-favorited-plants', [AdminController::class, 'getTopFavoritedPlants']);//done
    Route::get('/admin/top-garden-locations', [AdminController::class, 'getTopLocations']); //done

    // Retrieve tables routes
    Route::get('/admin/plants', [AdminController::class, 'getPlants']); //done
    Route::get('/admin/posts', [AdminController::class, 'getPosts']); //done
    Route::get('/admin/users', [AdminController::class, 'getAllUsers']); //done

    // Plants operations
    Route::post('/admin/addPlant', [AdminController::class, 'addPlant']); //done
    Route::put('/admin/updatePlant/{id}', [AdminController::class, 'updatePlant']); //done
    Route::get('/admin/showPlant/{id}', [AdminController::class, 'showPlant']); //done
    Route::post('/admin/deletePlant/{id}', [AdminController::class, 'deletePlant']); //done
    Route::post('/admin/uploadPlantPicture', [AdminController::class, 'uploadPlantPicture']);//done

    // Post and announcement routes
    Route::post('/admin/adminAddPost', [AdminController::class, 'createAdminPost']);//done
    Route::get('/admin/getAllAdminPosts', [AdminController::class, 'getAllAdminPosts']);//done
    Route::put('/admin/updateAdminPosts/{id}', [AdminController::class, 'updateAdminPost']);//done

    Route::post('/admin/addAnnouncement', [AdminController::class, 'addAnnouncement']);//done
    Route::get('/admin/getAnnouncements', [AdminController::class, 'getAllAnnouncements']);//done
    Route::put('/admin/updateAnnouncement/{id}', [AdminController::class, 'updateAnnouncement']);//done
    Route::delete('/admin/deleteAnnouncement/{id}', [AdminController::class, 'deleteAnnouncement']);//done

    Route::get('/admin/reports', [AdminController::class, 'viewReports']);//done
    //for web admin dashboard chat implementation
    Route::get('admin/conversations', [ConversationController::class, 'index']);
    Route::post('admin/conversation', [ConversationController::class, 'store']);
    Route::get('admin/conversations/{id}', [ConversationController::class, 'show']);
    Route::delete('admin/conversations/{id}', [ConversationController::class, 'destroy']);


    Route::post('admin/conversations/{conversationId}/messages', [MessageController::class, 'store']);
    Route::patch('admin/conversations/{conversationId}/messages/{messageId}', [MessageController::class, 'update']);
    Route::delete('admin/conversations/{conversationId}/messages/{messageId}', [MessageController::class, 'destroy']);
    Route::patch('admin/conversations/{conversationId}/messages/{messageId}/read', [MessageController::class, 'markAsRead']);


});
