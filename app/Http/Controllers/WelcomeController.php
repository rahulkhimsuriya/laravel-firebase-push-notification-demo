<?php

namespace App\Http\Controllers;

use App\Jobs\SendPushNotification;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WelcomeController extends Controller
{
    public function __invoke(Request $request)
    {
        SendPushNotification::dispatch(
            "dFEX0iZBvlivLqK1ZaXTwI:APA91bFhJof3GocRgezRgwUjiWjiy0gfNceWyzs3j0TLyWxft4FJnyMV0cL63ExQ1Q_relVzG8d8CGN2DlPmCS018BmF0vBFbUTOj8C1MMufR0eVCbkgOBdIEWR8Cm39pX6ZjOvb3at",
            'Title',
            "Adipisci quidem blanditiis doloribus. Repudiandae?"
        )->delay(now()->addSecond(10));

        return view('welcome');
    }

    public function googleToken()
    {
        if (!Cache::get('google_access_token')) {
            $scopes = ['https://www.googleapis.com/auth/cloud-platform'];
            $credentials = new ServiceAccountCredentials($scopes, base_path("/firebase/firebase.json"));
            $response = $credentials->fetchAuthToken();

            Cache::put('google_access_token', $response['access_token'], Carbon::now()->addSeconds($response['expires_in']));
        }

        return Cache::get('google_access_token');
    }

    function sendPushNotification()
    {
        $firebaseConfig = json_decode(file_get_contents(base_path("/firebase/firebase.json")), true);

        $response = Http::acceptJson()
            ->timeout(5)
            ->withToken($this->googleToken())
            ->post("https://fcm.googleapis.com/v1/projects/{$firebaseConfig['project_id']}/messages:send", [
                "message" => [
                    "token" => "dFEX0iZBvlivLqK1ZaXTwI:APA91bFhJof3GocRgezRgwUjiWjiy0gfNceWyzs3j0TLyWxft4FJnyMV0cL63ExQ1Q_relVzG8d8CGN2DlPmCS018BmF0vBFbUTOj8C1MMufR0eVCbkgOBdIEWR8Cm39pX6ZjOvb3at8",
                    "notification" => [
                        "body" => "This is an FCM notification message!",
                        "title" => "FCM Message"
                    ]
                ]
            ]);

        if ($response->failed()) {
            $response->throw();
        }
    }
}
