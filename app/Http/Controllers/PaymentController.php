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
            Log::error('Unauthorized access attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'success' => 'required|boolean',
            'status'  => 'required|boolean',
            'trackId' => 'required|string|max:50',
        ]);

        $result = (new PaymentService)->confirmTransaction($validated['trackId']);

        return response()->json([
            'success' => $result,
        ]);
    }
}
