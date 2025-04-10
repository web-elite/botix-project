<?php
namespace App\Services\Payment;

use App\Models\SubscriptionPlan;
use App\Models\Transactions;
use App\Models\User;
use App\Services\Payment\Gateways\ZibalService;
use App\Services\xui\XUIDataService;
use SergiX44\Nutgram\Nutgram;

class PaymentService
{
    protected $gateway;

    public function __construct($gateway = null)
    {
        $this->gateway = $gateway ?? new ZibalService();
    }

    public function createPaymentLink(int $amount, ?int $mobile = null, ?string $callbackUrl = null): array
    {
        return $this->gateway->createPaymentLink($amount, $mobile, $callbackUrl);
    }

    public function verifyTransaction(int $trackId)
    {
        return $this->gateway->verify($trackId);
    }

    public function createOrder(int $userId, int $planId, string $userSubId, int $refId): void
    {
        $plan        = SubscriptionPlan::find($planId);
        $transaction = Transactions::create([
            'user_subscription_id' => $userSubId,
            'user_id'              => $userId,
            'ref_id'               => $refId,
            'subscription_plan_id' => $plan->id,
            'amount'               => $plan->amount,
            'status'               => 'pending',
        ]);
    }

    public function confirmTransaction(int $trackId)
    {
        $transaction = Transactions::where('ref_id', $trackId)->first();
        if (! $transaction || $transaction->status === 'paid') {
            return false;
        }

        $result = $this->verifyTransaction($trackId);

        $transaction->card_number = $result['cardNumber'];
        $transaction->description = $result['description'];
        $transaction->ref_number  = $result['refNumber'];
        $transaction->paid_at     = $result['paidAt'];
        $transaction->status      = $result['status'] ? 'paid' : 'failed';
        $transaction->save();

        if (! $result['status']) {
            return false;
        }

        $xuiData = app(XUIDataService::class);
        $updateIsOk = $xuiData->updateClientAfterPurchase($transaction);

        $bot = new Nutgram($_ENV['TOKEN']);
        $user = User::find($transaction->user_id);

        if(! $updateIsOk) {
            $message = $bot->sendMessage(
                text: 'خطا در بروزرسانی اشتراک، لطفا با پشتیبانی تماس بگیرید.',
                chat_id: $user->tg_id,
            );
        }

        $message = $bot->sendMessage(
            text: 'اشتراک شما با موفقیت فعال شد. از طریق گزینه اشتراک من اطلاعات اشتراک خود را مشاهده کنید.',
            chat_id: $user->tg_id,
        );

        return true;
    }

}
