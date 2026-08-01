<?php

namespace App\Http\Controllers;

use App\Services\GeminiCitizenIdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitizenIdScanController extends Controller
{
    public function __invoke(Request $request, GeminiCitizenIdService $service): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'required_fields' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            return response()->json([
                'ok' => true,
                'provider' => 'gemini',
                'data' => $service->scan(
                    $validated['image'],
                    array_values(array_filter(array_map('trim', explode(',', (string) ($validated['required_fields'] ?? '')))))
                ),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'ok' => false,
                'provider' => 'gemini',
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
