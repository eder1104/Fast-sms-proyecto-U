<?php

namespace App\Providers;

use App\Contracts\SmsProviderInterface;
use App\Services\Sms\LogSmsProvider;
use App\Services\Sms\VonageSmsProvider;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SmsProviderInterface::class, function () {
            return match (config('services.sms.provider')) {
                'vonage' => new VonageSmsProvider(),
                default  => new LogSmsProvider(),
            };
        });
    }
}
