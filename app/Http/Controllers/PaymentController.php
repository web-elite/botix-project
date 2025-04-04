<?php
namespace App\Http\Controllers;

use App\Models\Transactions;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function paymentCallback(Request $request) :void
    {
        if ($request->header('X-SECRET-KEY') !== env('WORDPRESS_SECRET_KEY')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'success' => 'required|boolean',
            'status'  => 'required|boolean',
            'trackId' => 'required|string|max:50',
        ]);

        Transactions::updateOrCreate(
            ['track_id' => $validated['trackId']],
            [
                'is_successful' => $validated['success'] && $validated['status'],
                'raw_data'      => $request->all(),
            ]
        );
    }
}
