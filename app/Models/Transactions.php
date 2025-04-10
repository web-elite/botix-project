<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    use HasFactory;
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'user_subscription_id',
        'amount',
        'gateway',
        'ref_id',
        'ref_number',
        'card_number',
        'status',
        'paid_at',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

}
