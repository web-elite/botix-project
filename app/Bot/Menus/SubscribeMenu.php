<?php
namespace App\Bot\Menus;

use App\Models\SubscriptionPlan;
use App\Models\Transactions;
use App\Services\Payment\Gateways\ZibalService;
use App\Services\Payment\PaymentService;
use App\Services\UserService;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class SubscribeMenu extends InlineMenu
{
    public function start(Nutgram $bot)
    {
        $plans = SubscriptionPlan::active()->get();

        $this->clearButtons();
        $this->menuText(escape_markdown($this->text()), ['parse_mode' => ParseMode::MARKDOWN]);

        foreach ($plans as $plan) {
            $label = $plan->name . ' - ' . number_format($plan->amount / 1000) . ' تومان 💰';
            $this->addButtonRow(
                InlineKeyboardButton::make($label, callback_data: $plan->slug . '@confirm')
            );
        }

        $this->showMenu();
    }

    protected function text(): string
    {
        return "🔥 خرید اشتراک فیلترشکن دریچه 🔥\n\n"
            . "🚪 اشتراک دریچه، همون راهیه که از محدودیت های اینترنتی راحت میشی 🚀 \n"
            . "ویژگی‌هایی که تجربه می‌کنی:\n\n"
            . "⚡️ سرعت فوق‌العاده سرورها برای دانلود و آپلود\n"
            . "✅ قطعی صفر درصد! همیشه آنلاین و بدون مشکل\n"
            . "🌍 سرورهای اختصاصی برای تمام نیازهای اینترنتی\n"
            . "😍 *نیم‌بها، هرچقدر دانلود کنی نصفش از اینترنتت کم میشه* 😍\n"
            . "💻 مناسب برای تمام سیستم‌عامل‌ها (کامپیوتر، موبایل، تبلت، تلویزیون های هوشمند و حتی لینوکس!)\n"
            . "👨‍👩‍👧‍👦 حالت خانواده برای فیلتر کردن سایت‌های غیرمجاز\n"
            . "🔄 بروزرسانی خودکار فقط با یک کلیک\n\n"
            . "🌟 اشتراک دلخواهت رو انتخاب کن و وارد دنیای آزاد شو!";
    }

    public function confirm(Nutgram $bot)
    {
        $data = str_replace('@confirm', '', $bot->callbackQuery()->data);
        $plan = SubscriptionPlan::where('slug', $data)->first();

        if (! $plan) {
            $bot->sendMessage("⛔️ پلن انتخاب‌شده نامعتبر است.");
            return;
        }

        $payment      = new PaymentService();
        $gatewayUrl = $payment->createPaymentLink($plan->amount, $bot->userId());

        if (! $gatewayUrl) {
            $bot->sendMessage("⛔️ خطا در ایجاد لینک پرداخت. لطفاً دوباره تلاش کنید.");
            return;
        }

        $userService = new UserService;
        $userSubs    = $userService->getUserXuiData($bot->userId());

        if (count($userSubs) <= 1) {
            $subId   = key($userSubs);
            $subName = reset($userSubs)['name'] ?? 'جدید';

            $msg = count($userSubs)
            ? "شما درحال خرید اشتراک برای $subName با کد $subId هستید"
            : 'شما درحال خرید اشتراک جدید هستید';

            $this->clearButtons()
                ->menuText("✅ پلن شما انتخاب شد!\n\n📦 {$plan['label']}\n💰 مبلغ: " . number_format($plan['amount']) . " تومان\n\n📌 $msg")
                ->addButtonRow(InlineKeyboardButton::make('💳 پرداخت آنلاین (فعال‌سازی آنی)', url: $gateway_url))
                ->addButtonRow(InlineKeyboardButton::make('🔙 بازگشت', callback_data: 'back@start'))
                ->orNext('cancel')
                ->showMenu();

        } else {
            // چندتا اشتراک داره => منویی با گزینه‌ها
            $this->clearButtons()->menuText("📌 انتخاب کنید برای کدام اشتراک می‌خواهید این پلن را بخرید:");

            foreach ($userSubs as $subId => $subInfo) {
                $this->addButtonRow(
                    InlineKeyboardButton::make("📶 {$subInfo['name']}", callback_data: "pay_for_{$subId}@payment")
                );
            }

            $this->addButtonRow(InlineKeyboardButton::make('🔙 بازگشت', callback_data: 'back@start'))
                ->orNext('cancel')
                ->showMenu();

            // ذخیره‌سازی پلن انتخاب‌شده برای ادامه مسیر در حافظه (Conversation State)
            $this->setData('pending_plan', $data);
        }
    }

    public function payment(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $user = auth()->user();
        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        // تراکنش موقت بساز
        $transaction = Transactions::create([
            'user_id'              => $user->id,
            'subscription_plan_id' => $plan->id,
            'amount'               => $plan->price,
            'status'               => 'pending',
        ]);

        // ساخت درگاه پرداخت
        $zibal = new ZibalService();

        $callbackUrl = route('payment.callback'); // همون URLی که وردپرس بهش POST میزنه
        $paymentUrl  = $zibal->createPaymentLink(
            amount: $plan->price,
            mobile: $user->mobile ?? null,
            callbackUrl: $callbackUrl
        );

        // ref_id رو در تراکنش ذخیره کن
        if ($paymentUrl && $zibal->getTrackId()) {
            $transaction->update(['ref_id' => $zibal->getTrackId()]);
        }

        // اگه خواستی ریدایرکت کنی:
        return response()->json([
            'url' => $paymentUrl,
        ]);
    }

    public function cancel(Nutgram $bot)
    {
        $bot->sendMessage("🚫 خرید اشتراک لغو شد.\n🤔 چه کاری میخوای انجام بدی؟ از منو ربات انتخاب کن");
    }
}
