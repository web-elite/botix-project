<?php
namespace App\Http\Controllers;

use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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

        return response()->json([
            'success' => $result,
        ]);
    }
}
