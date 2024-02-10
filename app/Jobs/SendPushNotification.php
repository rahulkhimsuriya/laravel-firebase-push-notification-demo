<?php

namespace App\Jobs;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $token,
        public string $title,
        public string $body,
    ) {
    }

    public function handle(): void
    {
        $firebaseConfig = json_decode(file_get_contents(base_path("/firebase/firebase.json")), true);


        $response = Http::acceptJson()
            ->timeout(5)
            ->withToken($this->googleToken())
            ->post("https://fcm.googleapis.com/v1/projects/{$firebaseConfig['project_id']}/messages:send", $this->payload());

        if ($response->failed()) {
            $response->throw();
        }
    }

    private function googleToken()
    {
        if (!Cache::get('google_access_token')) {
            $scopes = ['https://www.googleapis.com/auth/cloud-platform'];
            $credentials = new ServiceAccountCredentials($scopes, base_path("/firebase/firebase.json"));
            $response = $credentials->fetchAuthToken();

            Cache::put('google_access_token', $response['access_token'], Carbon::now()->addSeconds($response['expires_in']));
        }

        return Cache::get('google_access_token');
    }

    private function payload()
    {
        return [
            "message" => [
                "token" => $this->token,
                "notification" => [
                    "title" => $this->title,
                    "body" => $this->body,
                ]
            ]
        ];
    }
}
