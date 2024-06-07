<?php

namespace App\Models;
use App\Http\Controllers\AuthUserController;
use App\Http\Controllers\PlantController;
use App\Mail\VerificationMail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Http\Controllers\AdminController;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::post('/admin/login', [AdminController::class, 'adminLogin']); //done

Route::middleware(['auth', 'admin'])->group(function () {
    // Authentication routes
    Route::post('/admin/logout', [AdminController::class, 'logout']); //done
    
    // Profile routes
    Route::post('/admin/change-password', [AdminController::class, 'changePassword']); //done
    Route::post('/admin/send-reset-password-otp', [AdminController::class, 'sendResetPasswordOTP']); //done
    Route::post('/admin/reset-password', [AdminController::class, 'resetPassword']); //done
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
    Route::post('/addPlant', [AdminController::class, 'addPlant']); //done
    Route::put('/updatePlant/{id}', [AdminController::class, 'updatePlant']); //done
    Route::get('/showPlant/{id}', [AdminController::class, 'showPlant']); //done
    Route::post('/deletePlant/{id}', [AdminController::class, 'deletePlant']); //done

    // Post and announcement routes
    Route::post('/admin/adminAddPost', [AdminController::class, 'createAdminPost']);//done
    Route::get('/admin/posts', [AdminController::class, 'getAllAdminPosts']);//done
    Route::put('/admin/updateAdminPosts/{id}', [AdminController::class, 'updateAdminPost']);//done

    Route::post('/admin/addAnnouncement', [AdminController::class, 'addAnnouncement']);//done
    Route::get('/admin/getAnnouncements', [AdminController::class, 'getAllAnnouncements']);//done
    Route::put('/admin/updateAnnouncement/{id}', [AdminController::class, 'updateAnnouncement']);//done
    Route::delete('/admin/deleteAnnouncement/{id}', [AdminController::class, 'deleteAnnouncement']);//done

    Route::get('/admin/reports', [AdminController::class, 'viewReports']);//done


});





