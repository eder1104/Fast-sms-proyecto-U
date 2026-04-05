<?php

namespace App\Jobs;

use App\Contracts\SmsProviderInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $phone,
        private readonly string $message
    ) {}

    public function handle(SmsProviderInterface $provider): void
    {
        Log::info('Worker procesando SMS', [
            'worker_pid' => getmypid(),
            'provider'   => get_class($provider),
            'phone'      => $this->phone,
        ]);

        $provider->send($this->phone, $this->message);
    }
}
