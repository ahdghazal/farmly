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

Route::post('/adminLogin', [AuthUserController::class, 'adminLogin']);

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('/logout', [AuthUserController::class, 'logout']); //done
    Route::post('/addPlant', [PlantController::class, 'addPlant']); //done
    Route::put('/updatePlant/{id}', [PlantController::class, 'updatePlant']); //done
    Route::get('/showPlant/{id}', [PlantController::class, 'showPlant']); //done
    Route::post('/deletePlant/{id}', [PlantController::class, 'deletePlant']); //done
    

});


