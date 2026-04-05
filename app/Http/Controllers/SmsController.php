<?php

namespace App\Http\Controllers;

use App\Jobs\SendSmsJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone'   => ['required', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:160'],
        ]);

        SendSmsJob::dispatch($validated['phone'], $validated['message']);

        return response()->json([
            'status'  => 'queued',
            'phone'   => $validated['phone'],
            'message' => $validated['message'],
        ], 202);
    }
}
