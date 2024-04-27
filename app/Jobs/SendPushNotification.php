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
use Throwable;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $token,
        public string $title,
        public string $body,
        public array $data = [],
    ) {
    }

    public function handle(): void
    {
        $response = Http::acceptJson()
            ->timeout(5)
            ->withToken($this->googleToken())
            ->post($this->pushNotificationUrl(), $this->payload());

        if ($response->failed()) {
            $response->throw();
        }
    }

    private function firebaseConfig(): array
    {
        return json_decode(file_get_contents(base_path("/firebase/firebase.json")), true);
    }

    private function pushNotificationUrl(): string
    {
        $firebaseConfig = $this->firebaseConfig();

        return "https://fcm.googleapis.com/v1/projects/{$firebaseConfig['project_id']}/messages:send";
    }

    private function googleToken(): string|Throwable
    {
        if (!Cache::get('firebasePushNotificationGoogleAccessToken')) {
            $credentials = new ServiceAccountCredentials(
                [
                    'https://www.googleapis.com/auth/cloud-platform'
                ],
                $this->firebaseConfig()
            );
            $response = $credentials->fetchAuthToken();

            Cache::put(
                'firebasePushNotificationGoogleAccessToken',
                $response['access_token'],
                Carbon::now()->addSeconds($response['expires_in'])->subSeconds(5)
            );
        }

        return Cache::get('firebasePushNotificationGoogleAccessToken');
    }

    private function payload(): array
    {
        return [
            'message' => [
                'token' => $this->token,
                'data' => (object) $this->data,
                'notification' => [
                    'title' => $this->title,
                    'body' => $this->body,
                ],
                // "android" => [
                //     'priority' => 'high',
                //     'data' => (object) $this->data,
                //     'notification' => [
                //         'title' => $this->title,
                //         'body' => $this->body,
                //     ],
                // ],
                // 'webpush' => [
                //     'data' => (object) $this->data,
                //     'notification' => [
                //         'title' => $this->title,
                //         'body' => $this->body,
                //     ],
                // ],
                // 'apns' => [
                //     'payload' => [
                //         'title' => $this->title,
                //         'body' => $this->body,
                //     ],
                // ]
            ]
        ];
    }
}
