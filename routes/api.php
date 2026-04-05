<?php

use App\Http\Controllers\SmsController;
use Illuminate\Support\Facades\Route;

Route::post('/send-sms', [SmsController::class, 'send']);
