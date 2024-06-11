<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Post;
use App\Models\PostImage;
use App\Models\Plant;
use App\Models\Announcement;
use App\Models\Like;
use App\Models\Reply;
use App\Models\Report;
use App\Models\Garden;
use App\Models\FavoriteList;
use App\Mail\ResetPasswordOTP;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class AdminController extends Controller
{
    function adminLogin(Request $request)
{
    $fields = $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ], [
        'required' => 'field-required',
        'email.email' => 'email-format',
    ]);

    $credentials = ['email' => $fields['email'], 'password' => $fields['password'], 'is_admin' => 1];

    if (Auth::attempt($credentials)) {
        $user = Auth::user();
        
        if ($user->email_verified_at === null) {
            $response = [
                'errors' => [
                    'message' => ['email-not-verified']
                ]
            ];
            return response($response, 402);
        }

        Auth::user()->tokens()->delete();

        $token = Auth::user()->createToken('farmlyToken')->plainTextToken;

        $response = [
            'user' => Auth::user(),
            'token' => $token
        ];

        return response($response, 201);
    } else {
        $response = [
            'errors' => [
                'message' => ['credentials-invalid']
            ]
        ];
        return response($response, 400);
    }
}

    public function logout(Request $request)
    {
        try {
            $user = auth()->user();
            if ($user) {
                $user->tokens()->delete();
                $response = ['message' => 'Logged out'];
                return response()->json($response, 200);
            } else {
                $response = ['message' => 'User not authenticated'];
                return response()->json($response, 401);
            }
        } catch (\Exception $e) {
            $response = ['message' => 'Internal server error'];
            return response()->json($response, 500);
        }
    }

    

    public function changePassword(Request $request)
    {
        $fields = $request->validate([
            'password' => 'required',
            'newPassword' => 'required|min:8|max:32|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,32}$/|confirmed',
        ], [
            'required' => 'field-required',
            'newPassword.confirmed' => 'password-not-match',
            'newPassword.min' => 'password-length',
            'newPassword.max' => 'password-length',
            'newPassword.regex' => 'password-format',
        ]);

        $user = auth()->user();

        if (Hash::check($fields['password'], $user->password)) {
            if ($fields['password'] === $fields['newPassword']) {
                return response()->json(['error' => 'Password should be different from the current one'], 400);
            }

            $user->password = bcrypt($fields['newPassword']);
            $user->save();

            return response()->json(['message' => 'Password changed successfully'], 200);
        } else {
            return response()->json(['error' => 'Current password is incorrect'], 400);
        }
    }

    public function sendResetPasswordOTP(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'required' => 'field-required',
            'email.exists' => 'email-not-found',
        ]);

        $user = User::where('email', $fields['email'])->first();

        $otp = Str::random(4);
        $user->otp = bcrypt($otp);
        $user->save();

        try {
            Mail::to($user->email)->send(new ResetPasswordOTP($otp, $user->name));

            return response()->json(['message' => 'OTP sent to your email'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send OTP'], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
            'password' => 'required|min:8|max:32|confirmed',
        ], [
            'required' => 'field-required',
            'email.exists' => 'email-not-found',
            'password.min' => 'password-length',
            'password.max' => 'password-length',
        ]);

        $user = User::where('email', $fields['email'])->first();

        if (!Hash::check($fields['otp'], $user->otp)) {
            return response()->json(['error' => 'Invalid OTP'], 400);
        }

        $user->password = Hash::make($fields['password']);
        $user->otp = null;
        $user->save();

        return response()->json(['message' => 'Password reset successfully'], 200);
    }

    public function showProfile()
    {
        $user = auth()->user();
        return response()->json(['user' => $user], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $fields = $request->validate([
            'name' => 'nullable|regex:/^[\x{0621}-\x{064a} A-Za-z]+$/u',
            'gender' => 'nullable|in:male,female',
            'city' => 'nullable',
        ], [
            'name.regex' => 'name-format',
            'gender.in' => 'invalid-gender',
        ]);

        if (isset($fields['name'])) {
            $user->name = $fields['name'];
        }
        if (isset($fields['gender'])) {
            $user->gender = $fields['gender'];
        }
        if (isset($fields['city'])) {
            $user->city = $fields['city'];
        }
        $user->save();
        return response()->json(['user' => $user, 'message' => 'Profile updated successfully'], 200);
    }

    public function uploadPicture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'picture' => 'required|string',
            'picture_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $encodedPicture = $request->picture;
        $pictureName = $request->picture_name;

        $extension = pathinfo($pictureName, PATHINFO_EXTENSION);
        if (!$extension) {
            $extension = 'jpg';
        }

        $fileName = auth()->id() . '_' . time() . '.' . $extension;

        $decodedPicture = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $encodedPicture));

        $filePath = 'userPictures/' . $fileName;
        Storage::disk('public')->put($filePath, $decodedPicture);

        auth()->user()->update(['picture' => $filePath]);

        return response()->json(['picture_path' => $filePath], 201);
    }



    public function getAllUsers()
    {
        $users = User::select('id', 'name', 'email', 'gender', 'city', 'is_admin', 'email_verified_at', 'created_at', 'updated_at')->get();
    
        return response()->json($users, 200);
    }
    
    public function deleteUser($id)
{
    $user = User::find($id);
    
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $user->delete();
    return response()->json(['message' => 'User deleted successfully'], 200);
}


public function deletePost($id)
{
    $post = Post::find($id);

    if (!$post) {
        return response()->json(['error' => 'Post not found'], 404);
    }

    foreach ($post->images as $image) {
        Storage::disk('public')->delete($image->image_path);
        $image->delete();
    }

    $post->delete();
    return response()->json(['message' => 'Post deleted successfully'], 200);
}


public function getCommunityStatistics()
{
    $totalLikes = Like::count();
    $totalReplies = Reply::count();
    $totalPosts = Post::count();

    return response()->json([
        'total_likes' => $totalLikes,
        'total_replies' => $totalReplies,
        'total_posts' => $totalPosts
    ]);
}

public function getPlantCategoriesCount()
{
    $categories = ['flowers', 'fruits', 'vegetables', 'herbs', 'decoration'];
    $counts = [];

    foreach ($categories as $category) {
        $counts[$category] = Plant::where('category', $category)->count();
    }

    return response()->json($counts);
}

public function getTotalGardens()
{
    $totalGardens = Garden::count();

    return response()->json(['total_gardens' => $totalGardens]);
}

public function getTotalUsers()
{
    $totalUsers = User::count();

    return response()->json(['total_users' => $totalUsers]);
}



public function getUsers()
{
    $users = User::select('id', 'name', 'email', 'is_admin', 'created_at', 'updated_at')->get();
    return response()->json($users);
}

public function getPlants()
{
    $plants = Plant::all();
    return response()->json($plants);
}

public function getPosts()
{
    $posts = Post::with('user')->get();
    return response()->json($posts, 200);
}

public function addPost(Request $request)
{
    $data = $request->validate([
        'title' => 'required|string',
        'content' => 'required|string',
    ]);

    $post = Post::create([
        'user_id' => auth()->id(),
        'title' => $data['title'],
        'content' => $data['content'],
    ]);

    return response()->json($post, 201);
}

public function addAnnouncement(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $announcement = Announcement::create([
            'title' => $request->title,
            'message' => $request->message,
            'admin_id' => Auth::id(),
        ]);

        return response()->json($announcement, 201);
    }


    public function createAdminPost(Request $request)
    {
        $request->validate([
            'content' => 'nullable|string',
            'images' => 'nullable|array|max:4',
            'images.*' => 'nullable|string',
        ]);

        if (!$request->filled('content') && !$request->has('images')) {
            return response()->json(['error' => 'Post cannot be empty.'], 422);
        }

        $userId = Auth::id();

        $postData = [
            'user_id' => $userId,
            'content' => $request->content,
        ];

        $post = Post::create($postData);

        if ($request->has('images')) {
            foreach ($request->images as $imageData) {
                $imagePath = $this->saveBase64Image($imageData, $userId);
                PostImage::create([
                    'post_id' => $post->id,
                    'image_path' => $imagePath,
                ]);
            }
        }

        return response()->json($post->load('images'), 201);
    }

    private function saveBase64Image($imageData, $userId)
    {
        $decodedImage = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $imageData));
        $fileName = $userId . '_' . time() . '_' . uniqid() . '.png';
        $filePath = 'postPictures/' . $fileName;
        file_put_contents(public_path($filePath), $decodedImage);
        return $filePath;
    }
    
    public function getAllAnnouncements()
    {
        $announcements = Announcement::all();
        return response()->json($announcements, 200);
    }

    public function updateAnnouncement(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $announcement = Announcement::findOrFail($id);
        $announcement->update([
            'title' => $request->title,
            'message' => $request->message,
        ]);

        return response()->json($announcement, 200);
    }

    public function deleteAnnouncement($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        return response()->json(null, 204);
    }
    public function getAllAdminPosts()
    {
        $adminPosts = Post::with('user', 'images')
                            ->whereHas('user', function ($query) {
                                $query->where('is_admin', 1);
                            })
                            ->get();
        return response()->json($adminPosts, 200);
    }
    

    public function updateAdminPost(Request $request, $id)
    {
        $request->validate([
            'content' => 'nullable|string',
            'images' => 'nullable|array|max:4',
            'images.*' => 'nullable|string',
        ]);

        $post = Post::findOrFail($id);
        $post->update([
            'content' => $request->content,
        ]);

        if ($request->has('images')) {
            foreach ($post->images as $image) {
                $image->delete();
                Storage::disk('public')->delete($image->image_path);
            }

            foreach ($request->images as $imageData) {
                $imagePath = $this->saveBase64Image($imageData, $post->user_id);
                PostImage::create([
                    'post_id' => $post->id,
                    'image_path' => $imagePath,
                ]);
            }
        }

        return response()->json($post->load('images'), 200);
    }

    public function deleteAdminPost($id)
    {
        $post = Post::findOrFail($id);

        foreach ($post->images as $image) {
            $image->delete();
            Storage::disk('public')->delete($image->image_path);
        }

        $post->delete();

        return response()->json(null, 204);
    }

    public function getTopFavoritedPlants()
    {
        $popularPlants = Plant::orderBy('favorites_count', 'desc')
                             ->take(15)
                             ->get();

        if ($popularPlants->count() < 15) {
            $remainingCount = 15 - $popularPlants->count();
            $randomPlants =Plant::where('favorites_count', '=', 0)->inRandomOrder()->take($remainingCount)->get();
            $popularPlants = $popularPlants->merge($randomPlants);
        }

        return response()->json(['popular_plants' => $popularPlants], 200);
    }
    public function getTopLocations()
    {
        $topLocations = Garden::select('location', DB::raw('COUNT(*) as garden_count'))
            ->groupBy('location')
            ->orderByDesc('garden_count')
            ->limit(3)
            ->get();

        return response()->json(['top_locations' => $topLocations], 200);
    }

    public function viewReports()
    {
        $reports = Report::with('post')->get();
        return response()->json(['reports' => $reports], 200);
    }



    public function addPlant(Request $request)
{
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
        $plant = Plant::create($plantData);

        $createdPlants[] = $plant;
    }

    return response()->json($createdPlants, 201);
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

        $plant = Plant::findOrFail($id);
        $plant->update($validator->validated());

        return response()->json($plant, 200);
    }


    public function deletePlant($id)
    {
        Plant::destroy($id);

        return response()->json(['message' => 'Plant deleted successfully'], 200);
    }

    public function showPlant($id)
    {
        $plant = Plant::findOrFail($id);

        return response()->json($plant, 200);
    }


    public function uploadPlantPicture(Request $request)
{
    $validator = Validator::make($request->all(), [
        'picture' => 'required|string',
        'picture_name' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $encodedPicture = $request->picture;
    $pictureName = $request->picture_name;

    $extension = pathinfo($pictureName, PATHINFO_EXTENSION);
    if (!$extension) {
    
        $extension = 'jpg';
    }

    
    $fileName = auth()->id() . '_' . time() . '.' . $extension;

  
    $decodedPicture = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $encodedPicture));

 
    $filePath = 'plantPictures/' . $fileName;
    Storage::disk('public')->put($filePath, $decodedPicture);

  
    auth()->user()->update(['picture' => $filePath]);

    return response()->json(['picture_path' => $filePath], 201);
}

}
