<?php

namespace App\Http\Controllers;
use App\Mail\VerificationMail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\FavoriteList;
use App\Models\NotificationToken;
use Illuminate\Support\Facades\Hash; 
use Illuminate\Support\Facades\Auth;
use App\Models\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Carbon;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\DBAL\TimestampType;
use Illuminate\Support\Str;
use App\Mail\ResetPasswordOTP;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Conversation;
use App\Events\UserVerified;


class AuthUserController extends Controller

{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|unique:users,email|email',
            'name' => 'required|regex:/^[\x{0621}-\x{064a} A-Za-z]+$/u',
            'password' => 'required|min:8|max:32|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,32}$/',
            'gender' => 'required|alpha',
            'city' => 'required',
            'is_admin' => 'nullable|boolean',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
                ], [
            'required' => 'field-required',
            'password.min' => 'password-length',
            'password.max' => 'password-length',
            'password.regex' => 'password-format',
            'email.unique' => 'email-exists',
            'email.email' => 'email-format',
            'name.regex' => 'name-format',
        ]);
    
        $existingUser = User::where('email', $request->input('email'))
                            ->whereNull('email_verified_at')
                            ->first();
    
        if ($existingUser) {
            return response()->json(['message' => 'User email exists but not verified'], 402);
        }
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $user = User::create([
            'email' => $request->input('email'),
            'name' => $request->input('name'),
            'password' => Hash::make($request->input('password')),
            'gender' => $request->input('gender'),
            'city' => $request->input('city'),
            'is_admin' => $request->input('is_admin') ?? false ,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);
        
        FavoriteList::create([
            'user_id' => $user->id,
        ]);
    
        $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    
        $user->verification_token = bcrypt($code);
        $user->save();
    
        Mail::to($user)->send(new VerificationMail($code));
    
        $response = [
            'user' => $user,
        ];
        return response()->json($response, 201);
    }
    
//Done


public function verify(Request $request)
{
    // Validate request fields
    $fields = $request->validate([
        'code' => 'required|size:4|regex:/^\d{4}$/',
        'email' => 'required|email',
    ], [
        'required' => 'field-required',
        'code.size' => 'invalid-token',
        'code.regex' => 'invalid-token',
    ]);

    // Fetch the user by email
    $user = User::where('email', $request->email)->first();

    // Verify if user exists and token matches
    if (!$user || !Hash::check($request->code, $user->verification_token)) {
        $response = [
            'errors' => [
                'message' => ['invalid-token']
            ]
        ];
        return response($response, 400);
    }

    // Update email_verified_at for the user
    $user->email_verified_at = Carbon::now()->toDateTimeString();
    $user->save();

    // Generate API token for the user
    $token = $user->createToken('farmlyToken')->plainTextToken;

    // If user is not admin, create default conversation
    if (!$user->is_admin) {
        $admin = User::where('is_admin', true)->first();

        if ($admin) {
            Conversation::firstOrCreate([
                'user1_id' => $user->id,
                'user2_id' => $admin->id,
            ]);
        }
    }

    // Prepare response
    $response = [
        'message' => 'Verified code successfully',
        'token' => $token,
        'user' => $user,
    ];

    return response($response, 201);
}


public function resendCode(Request $request)
{
    $user = User::where('email', $request->email)->first();

    $code = random_int(0, 9999);
    $code = str_pad($code, 4, 0, STR_PAD_LEFT);
    $user->verification_token = bcrypt($code);
    $user->save();

    try {
        Mail::send('email.verification', ['code' => $code], function($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $message->to($user->email, $user->name)->subject('Verify Your Email');
        });

        $response = [
            'message' => 'success-email'
        ];
        return response($response, 201);
    } catch (\Exception $e) {
        return response()->json(['message' => $e->getMessage()], 400);
    }
}

//done

function login(Request $request)
{
    $fields = $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ], [
        'required' => 'field-required',
        'email.email' => 'email-format',
    ]);

    $user = User::where('email', $fields['email'])->first();

    if (!$user) {
        $response = [
            'errors' => [
                'message' => ['credentials-invalid']
            ]
        ];
        return response($response, 400);
    }

    if ($user->email_verified_at === null) {
        $response = [
            'errors' => [
                'message' => ['email-not-verified']
            ]
        ];
        return response($response, 402);
    }

    if (!Hash::check($fields['password'], $user->password)) {
        $response = [
            'errors' => [
                'message' => ['credentials-invalid']
            ]
        ];
        return response($response, 400);
    }

    $user->tokens()->delete();

    $token = $user->createToken('farmlyToken')->plainTextToken;

    $response = [
        'user' => $user,
        'token' => $token
    ];

    return response($response, 201);
}
//done









public function logout(Request $request)
{
    try {
        $user = auth()->user();
        if ($user) {
            $this->deleteFcmToken($user);

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

protected function deleteFcmToken($user)
{
    $user->fcm_token = null;
    $user->save();

    return response()->json(['message' => 'FCM token deleted successfully']);
}

//done








function changePassword(Request $request)
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
//done




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
//done



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
        'city'=>'nullable',
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
    // Validate the request data
    $validator = Validator::make($request->all(), [
        'picture' => 'required|string',
        'picture_name' => 'required|string',
    ]);

    // If validation fails, return the errors
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Get the base64 encoded picture and the authenticated user's ID
    $encodedPicture = $request->picture;
    $userId = auth()->id();

    // Save the base64 encoded picture
    try {
        $filePath = $this->saveBase64ImageUser($encodedPicture, $userId);

        // Update the user's picture attribute
        $user = auth()->user();
        $user->picture = $filePath;
        $user->save();

        // Return the file path of the uploaded picture
        return response()->json(['picture_path' => $filePath], 201);
    } catch (Exception $e) {
        // Log the error and return a JSON response with an error message
        Log::error('Failed to save image: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to save image.'], 500);
    }
}

private function saveBase64ImageUser($imageData, $userId)
{
    // Decode the base64 encoded image
    $decodedImage = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $imageData));

    // Generate a unique file name
    $fileName = $userId . '_' . time() . '_' . uniqid() . '.png';

    // Define the directory to save the image
    $directory = 'userPictures/';
    $filePath = $directory . $fileName;

    // Create the directory if it doesn't exist
    if (!file_exists($directory)) {
        mkdir($directory, 0775, true);
    }

    // Save the decoded image to the file system
    if (file_put_contents($filePath, $decodedImage) === false) {
        throw new Exception("Failed to save the file.");
    }

    // Return the file path of the saved image
    return $filePath;
}


public function saveFcmToken(Request $request)
{
    $request->validate([
        'fcm_token' => 'required|string',
    ]);

    $user = Auth::user();
    $user->fcm_token = $request->fcm_token;
    $user->save();

    return response()->json(['message' => 'FCM token saved successfully']);
}

}