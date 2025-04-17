<?php
namespace App\Services\Payment;

use App\Models\SubscriptionPlan;
use App\Models\Transactions;
use App\Models\User;
use App\Services\Notifications\NotificationAdminHelperService;
use App\Services\Payment\Gateways\ZibalService;
use App\Services\xui\XUIDataService;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

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

    public function getTransactionReport(int $trackId)
    {
        if ($this->gateway instanceof ZibalService) {
            return $this->gateway->inquiry($trackId);
        }
        return $this->gateway->getTransactionReport($trackId);
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

        try {
            $transaction = Transactions::where('ref_id', $trackId)->first();
            if (! $transaction || $transaction->status === 'paid') {
                Log::channel('payments')->error('Transaction not found or already paid', [
                    'trackId' => $trackId,
                    'status'  => $transaction ? $transaction->status : 'not found',
                ]);
                return false;
            }

            $this->verifyTransaction($trackId);
            $result = $this->getTransactionReport($trackId);

            $transaction->card_number = $result['cardNumber'];
            $transaction->description = $result['description'];
            $transaction->ref_number  = $result['refNumber'];
            $transaction->paid_at     = $result['paidAt'];
            $transaction->status      = $result['status'] ? 'paid' : 'failed';
            $transaction->save();

            $user       = User::find($transaction->user_id);
            $username   = $user->telegram_data->username ?? 'NotFoundUserName';
            $bot        = new Nutgram(env('TELEGRAM_TOKEN'));
            $adminNotif = app(NotificationAdminHelperService::class);

            if (! $result['status']) {
                Log::channel('payments')->error('Transaction failed', [
                    'trackId' => $trackId,
                    'error'   => $result['message'],
                ]);
                $bot->sendSticker(
                    sticker: 'CAACAgEAAxkBAAEzpC5n-WETF--Z8cCPwNg3kMb_WMrMuwACpgEAAp-ZWEU7P-6NXtpGaTYE',
                    chat_id: $user->tg_id,
                );
                $adminNotif->sendTelegramNotification("❌ پرداخت ناموفق جدید!\n\n"
                    . "🧾 کد پیگیری: $trackId\n"
                    . "📄 خطا: {$result['message']}\n\n"
                    . "👤 توسط: @{$username}");
                $bot->sendMessage(
                    chat_id: $user->tg_id,
                    text: "❌ پرداخت ناموفق بود!\n\n"
                    . "🧾 کد پیگیری: $trackId\n"
                    . "📄 خطا: {$result['message']}\n\n"
                    . "ℹ️ لطفا مجدد تلاش کنید یا با پشتیبانی تماس بگیرید.\n\n"
                    . "🔃 همچنین درصورتی که مبلغی از حساب شما کم شده توسط بانک تا 72 ساعت آینده به حساب شما بازمی‌گردد."
                );
                return false;
            }

            $xuiData    = app(XUIDataService::class);
            $updateIsOk = $xuiData->updateClientAfterPurchase($transaction);

            if (! $updateIsOk) {
                Log::channel('payments')->error('Failed to update client in XUI', [
                    'trackId' => $trackId,
                    'userId'  => $transaction->user_id,
                ]);
                $bot->sendSticker(
                    sticker: 'CAACAgEAAxkBAAEzpC5n-WETF--Z8cCPwNg3kMb_WMrMuwACpgEAAp-ZWEU7P-6NXtpGaTYE',
                    chat_id: $user->tg_id,
                );
                $bot->sendMessage(
                    chat_id: $user->tg_id,
                    text: "❌ خطا در بروزرسانی اطلاعات کاربر در سرور!\n\n"
                    . "لطفا با پشتیبانی تماس بگیرید.",
                );
                return false;
            }

            Log::channel('payments')->info('Transaction successful', [
                'trackId' => $trackId,
                'userId'  => $transaction->user_id,
            ]);
            $bot->sendSticker(
                sticker: 'CAACAgEAAxkBAAEzpCxn-WECI_EOx2d2RreKF7AmvXZ33QACMAQAAhyYKEevQOWk5-70BjYE',
                chat_id: $user->tg_id,
            );

            $adminNotif->sendTelegramNotification("🎉 پرداخت موفق جدید!\n\n"
                . "💳 شماره کارت: {$transaction->card_number}\n"
                . "🧾 کد پیگیری: {$transaction->ref_number}\n"
                . "🕒 زمان پرداخت: {$transaction->paid_at}\n"
                . "📝 توضیحات: {$transaction->description}\n\n"
                . "👤 توسط: @{$username}");
            $bot->sendMessage(
                chat_id: $user->tg_id,
                text: "🎉 پرداخت شما با موفقیت انجام شد!\n\n"
                . "💳 شماره کارت: {$transaction->card_number}\n"
                . "🧾 کد پیگیری: {$transaction->ref_number}\n"
                . "🕒 زمان پرداخت: {$transaction->paid_at}\n"
                . "📝 توضیحات: {$transaction->description}\n\n",
                reply_markup: InlineKeyboardMarkup::make()
                    ->addRow(
                        InlineKeyboardButton::make('اشتراک‌های من 👤', callback_data: 'profile')
                    )
            );

            return true;
        } catch (\Throwable $th) {
            Log::channel('payments')->error('Error confirming transaction', [
                'trackId'   => $trackId,
                'error'     => $th->getMessage(),
                'File:line' => $th->getFile() . ':' . $th->getLine(),
            ]);
            return false;
        }
    }

}
