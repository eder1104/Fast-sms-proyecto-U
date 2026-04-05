<?php

namespace App\Services\Sms;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Log;

class LogSmsProvider implements SmsProviderInterface
{
    public function send(string $phone, string $message): void
    {
        Log::channel('single')->info('[LogSmsProvider] SMS simulado', [
            'phone'   => $phone,
            'message' => $message,
        ]);
    }
}
