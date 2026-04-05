<?php

namespace App\Services\Sms;

use App\Contracts\SmsProviderInterface;
use Vonage\Client;
use Vonage\Client\Credentials\Basic;
use Vonage\SMS\Message\SMS;

class VonageSmsProvider implements SmsProviderInterface
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client(
            new Basic(
                config('services.vonage.api_key'),
                config('services.vonage.api_secret')
            )
        );
    }

    public function send(string $phone, string $message): void
    {
        $this->client->sms()->send(
            new SMS($phone, config('services.vonage.from'), $message)
        );
    }
}
