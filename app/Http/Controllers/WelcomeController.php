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
}
