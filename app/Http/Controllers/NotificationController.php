<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use GuzzleHttp\Client;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{

   /* public static function notify($title, $body, $device_key){
        $url="";
        $serverkey="";

        $dataArr = [
            "click_action" => "FLUTTER_NOTIFICATION_CLICK",
            "status" => "done"
        ];
        $data = [
            "registration_ids" => [$device_key],
            "notification" => [
            "title" => $title,
            "body" => $body,
            "sound" => "default"
        ],
            "data"=> $dataArr,
            "priority" => "high"
    ];

    $encodedData = json_encode ($data);

$headers = [
"Authorization:key=" . $serverKey,
"Content-Type: application/json",
];

$ch = curl_init();
curl_setopt ($ch, CURLOPT_URL, $url) ;
curl_setopt ($ch, CURLOPT_POST, true);
curl_setopt ($ch,CURLOPT_HTTPHEADER, $headers);
curl_setopt ($ch,CURLOPT_RETURNTRANSFER, true);
curl_setopt ($ch,CURLOPT_SSL_VERIFYHOST, ®);
curl_setopt ($ch, CURLOPT_HTT_VERSION, CURL_HTTP_VERSION_1_1);
// Disabling SSL Certificate support temporarly
curl_setopt ($ch,CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);
// Execute post
$result = curl_exec($ch) ;
if($result === FALSE){
return [
'message' =>'failed',
'r' => $result,
'success' => false,
];
        
}
// Close connection
curL_close($ch) ;

return [
'message' => 'success',
'r' => $result,
'success' => true,
];
}



public function testqueues (Request $request) {
    $users = User::whereNotNull('device_key')->whereNotNull('delay')->get();
    foreach($users as $user){
        dispatch(new NotificationScheduleJob($user->name, $user->email, $user->device_key))->delay(now()->addMinutes($user->delay));
    }
}*/



    protected $firebaseUrl;

    public function __construct()
    {
      $this->firebaseUrl = env('FIREBASE_DATABASE_URL'); 
    }


    public function getNotifications()
    {
        $user = Auth::user();
        $notifications = Notification::where('user_id', $user->id)->get();

        return response()->json($notifications, 200);
    }


    public function createNotification(Request $request)
    {
        $notification = Notification::create($request->all());

        // Send Firebase notification
        $this->sendFirebaseNotification($notification);

        return response()->json($notification, 201);
    }


    
    public function sendNotificationToUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        $user = User::findOrFail($request->user_id);

        if (!$user->fcm_token) {
            return response()->json(['message' => 'User does not have an FCM token'], 404);
        }

        $firebase = (new Factory)
            ->withServiceAccount(config('firebase.projects.app.credentials.file'))
            ->withDatabaseUri(config('firebase.projects.app.database.url'));

        $messaging = $firebase->createMessaging();

        $message = [
            'token' => $user->fcm_token,
            'notification' => [
                'title' => $request->title,
                'body' => $request->body,
            ],
            'data' => [
                'key' => 'value', // Additional data if needed
            ],
        ];

        try {
            $messaging->send($message);
            return response()->json(['message' => 'Notification sent successfully']);
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            return response()->json(['message' => 'Failed to send notification', 'error' => $e->getMessage()], 500);
        }
    }

 
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['read' => true]);

        return response()->json(['message' => 'Notification marked as read'], 200);
    }

    
    public function getUnreadCount()
    {
        $user = Auth::user();
        $unreadCount = Notification::where('user_id', $user->id)->where('read', false)->count();

        return response()->json(['unread_count' => $unreadCount], 200);
    }
}

