<?php

declare(strict_types=1);

namespace helpers;

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use helpers\AppStatic as App;

class PushNotification
{
    protected array $data;
    protected static ?WebPush $pusher = null;

    public function __construct(array $data, ?string $sender = null)
    {
        if (
            !App::get('PUSH_NOTIFICATIONS') ||
            !is_file(App::get('STORAGE_KEYS') . '/push_public.txt') ||
            !is_file(App::get('STORAGE_KEYS') . '/push_private.txt') ||
            !is_file(App::get('STORAGE_KEYS') . '/push_private.pem')
        ) {
            throw new \Exception('Push notifications not enabled or not configured');
        }

        $this->data = $data;
        if (!$sender) {
            $sender = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }
        if (!isset(static::$pusher)) {
            static::$pusher = new WebPush(["VAPID" => [
                "subject" => $sender,
                "publicKey" => file_get_contents(App::get('STORAGE_KEYS') . "/push_public.txt"),
                "privateKey" => file_get_contents(App::get('STORAGE_KEYS') . "/push_private.txt"),
                "privateKeyPEM" => file_get_contents(App::get('STORAGE_KEYS') . "/push_private.pem")
            ]]);
        }
    }
    public function send(array $subscription): void
    {
        $sub = Subscription::create($subscription);
        if (isset(static::$pusher)) {
            static::$pusher->sendOneNotification($sub, json_encode($this->data) ?: '{}');
        }
    }
    public static function push(array $data, array $subscription, ?string $sender = null): void
    {
        (new self($data, $sender))->send($subscription);
    }
}
/*
SAMPLE CODE:
\helpers\PushNotification::push(
    [
        "title" => "Welcome!",
        "body" => "Yes, it works!",
        "tag" => "/system/webadmin/public/settings"
    ],
    array_values(json_decode($dbc->one("SELECT push FROM users WHERE usr = 1"), true))[0]
);
*/
