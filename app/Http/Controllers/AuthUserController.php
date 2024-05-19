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
use Illuminate\Support\Facades\Validator;


class AuthUserController extends Controller

{
    public function register(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'email' => 'required|unique:users,email|email',
            'name' => 'required|regex:/^[\x{0621}-\x{064a} A-Za-z]+$/u',
            'password' => 'required|min:8|max:32|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,32}$/',
            'gender' => 'required|alpha',
            'city' => 'required',
            'is_admin' => 'nullable|boolean'
                ], [
            'required' => 'field-required',
            'password.min' => 'password-length',
            'password.max' => 'password-length',
            'password.regex' => 'password-format',
            'email.unique' => 'email-exists',
            'email.email' => 'email-format',
            'name.regex' => 'name-format',
        ]);
    
        // Check if the email is registered but not yet verified
        $existingUser = User::where('email', $request->input('email'))
                            ->whereNull('email_verified_at')
                            ->first();
    
        if ($existingUser) {
            // User email exists in the database but email_verified_at is null
            return response()->json(['message' => 'User email exists but not verified'], 402);
        }
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Create user
        $user = User::create([
            'email' => $request->input('email'),
            'name' => $request->input('name'),
            'password' => Hash::make($request->input('password')),
            'gender' => $request->input('gender'),
            'city' => $request->input('city'),
            'is_admin' => $request->input('is_admin') ?? false 
        ]);
        
        FavoriteList::create([
            'user_id' => $user->id,
        ]);
    
        // Create a verification code
        $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    
        // Save the verification code to the user
        $user->verification_token = bcrypt($code);
        $user->save();
    
        // Send verification email
        Mail::to($user)->send(new VerificationMail($code));
    
        // Response on success
        $response = [
            'user' => $user,
        ];
        return response()->json($response, 201);
    }
    
//Done


public function verify(Request $request)
{
    // Validation
    $fields = $request->validate([
        'code' => 'required|size:4|regex:/^\d{4}$/',
        'email' => 'required|email',
    ], [
        'required' => 'field-required',
        'code.size' => 'invalid-token',
        'code.regex' => 'invalid-token',
    ]);

    // Fetch user
    $user = User::where('email', $request->email)->first();

    // Check verification code
    if (!Hash::check($request->code, $user->verification_token)) {
        $response = [
            'errors' => [
                'message' => ['invalid-token']
            ]
        ];
        return response($response, 400);
    }

    // Update user verification status
    $user->email_verified_at = Carbon::now()->toDateTimeString();
    $user->save();

    // Generate and return token
    $token = $user->createToken('farmlyToken')->plainTextToken;
    $response = [
        'message' => 'Verified code successfully',
        'token' => $token,
        'user' => $user,
    ];
    return response($response, 201);
}
//Done

public function resendCode(Request $request)
{
    // Fetch user
    $user = User::where('email', $request->email)->first();

    // Generate new verification code
    $code = random_int(0, 9999);
    $code = str_pad($code, 4, 0, STR_PAD_LEFT);
    $user->verification_token = bcrypt($code);
    $user->save();

    // Send verification email using Gmail SMTP
    try {
        // Use the Gmail SMTP settings
        Mail::send('email.verification', ['code' => $code], function($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $message->to($user->email, $user->name)->subject('Verify Your Email');
        });

        // Response on success
        $response = [
            'message' => 'success-email'
        ];
        return response($response, 201);
    } catch (\Exception $e) {
        // Error handling
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

    // Check if user exists
    if (!$user) {
        $response = [
            'errors' => [
                'message' => ['credentials-invalid']
            ]
        ];
        return response($response, 400);
    }

    // Check if email is verified
    if ($user->email_verified_at === null) {
        $response = [
            'errors' => [
                'message' => ['email-not-verified']
            ]
        ];
        return response($response, 402);
    }

    // Check password
    if (!Hash::check($fields['password'], $user->password)) {
        $response = [
            'errors' => [
                'message' => ['credentials-invalid']
            ]
        ];
        return response($response, 400);
    }

    // Delete existing tokens
    $user->tokens()->delete();

    // Create token
    $token = $user->createToken('farmlyToken')->plainTextToken;

    $response = [
        'user' => $user,
        'token' => $token
    ];

    return response($response, 201);
}
//done









function logout(Request $request)
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

    // Generate and save OTP
    $otp = Str::random(4);
    $user->otp = bcrypt($otp);
    $user->save();

    // Send the OTP to the user's email
    try {
        Mail::to($user->email)->send(new ResetPasswordOTP($otp, $user->name));

        return response()->json(['message' => 'OTP sent to your email'], 200);
    } catch (\Exception $e) {
        // Error handling
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

    // Check if OTP matches
    if (!Hash::check($fields['otp'], $user->otp)) {
        return response()->json(['error' => 'Invalid OTP'], 400);
    }

    // Reset password
    $user->password = Hash::make($fields['password']);
    $user->otp = null; // Clear OTP after successful reset
    $user->save();

    return response()->json(['message' => 'Password reset successfully'], 200);
}
//done



public function showProfile()
{
    $user = auth()->user(); // Get the authenticated user

    return response()->json(['user' => $user], 200);
}





public function updateProfile(Request $request)
{
    $user = auth()->user(); // Get the authenticated user

    $fields = $request->validate([
        'name' => 'nullable|regex:/^[\x{0621}-\x{064a} A-Za-z]+$/u',
        'gender' => 'nullable|in:male,female',
        'city'=>'nullable',
    ], [
        'name.regex' => 'name-format',
        'gender.in' => 'invalid-gender',
    ]);

    // Update user's profile
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
    // Return updated user data
    return response()->json(['user' => $user, 'message' => 'Profile updated successfully'], 200);
}




public function uploadPicture(Request $request)
{
    // Validate the request
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





function adminLogin(Request $request)
{
    // Validate request data
    $fields = $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ], [
        'required' => 'field-required',
        'email.email' => 'email-format',
    ]);

    // Attempt to authenticate the user
    if (Auth::attempt(['email' => $fields['email'], 'password' => $fields['password'], 'is_admin' => 1])) {
        // Check if the user's email is verified
        if (Auth::user()->email_verified_at === null) {
            $response = [
                'errors' => [
                    'message' => ['email-not-verified']
                ]
            ];
            return response($response, 402);
        }

        // Delete existing tokens
        Auth::user()->tokens()->delete();

        // Create token
        $token = Auth::user()->createToken('farmlyToken')->plainTextToken;

        $response = [
            'user' => Auth::user(),
            'token' => $token
        ];

        return response($response, 201);
    } else {
        // Authentication failed
        $response = [
            'errors' => [
                'message' => ['credentials-invalid']
            ]
        ];
        return response($response, 400);
    }
}

}