<?php
namespace App\Http\Controllers;

use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function paymentCallback(Request $request): JsonResponse
    {
        if ($request->header('X-SECRET-KEY') !== env('WORDPRESS_SECRET_KEY')) {
            Log::channel('payments')->error('Unauthorized access attempt', [
                'ip'         => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);
            abort(403, 'Unauthorized');
        }

        try {
            $validated = $request->validate([
                'success' => 'required|numeric',
                'status'  => 'required|numeric',
                'trackId' => 'required|numeric',
            ]);

            $result = (new PaymentService)->confirmTransaction($validated['trackId']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::channel('payments')->error('Validation failed', [
                'errors' => $e->errors(),
                'input'  => $request->all(),
            ]);
            $result = false;
        }

        return response()->json([
            'success' => $result,
        ]);
    }
}
