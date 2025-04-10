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
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'success' => 'required|boolean',
            'status'  => 'required|boolean',
            'trackId' => 'required|string|max:50',
        ]);

        $result = (new PaymentService)->confirmTransaction($validated['trackId']);

        Log::info('Payment callback received', [
            'headers' => $request->headers->all(),
            'body'    => $request->all(),
            'validated' => $validated
        ]);
        return response()->json([
            'success' => $result,
        ]);
    }
}
