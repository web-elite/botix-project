<?php
namespace App\Bot\Menus;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Payment\PaymentService;
use App\Services\UserService;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class SubscribeMenu extends InlineMenu
{
    /**
     * !Step 1 - Show the user's subscriptions.
     * @param Nutgram $bot
     * @return void
     */
    public function start(Nutgram $bot)
    {
        try {
            $userService = new UserService;
            $userSubs    = $userService->getUserXuiData($bot->userId());

            if (count($userSubs) > 0) {
                $this->clearButtons()->menuText("📌 لطفا اشتراک خود را برای تمدید انتخاب کنید.\n\n ➕ همچنین اگر قصد خرید اشتراک جدید دارید گزینه (خرید اشتراک جدید) را انتخاب کنید.");
                $this->show_user_subscriptions($userSubs);
            } else {
                $this->select_subscription($bot);
            }

        } catch (\Throwable $th) {
            Log::channel('bot')->error("Error in SubscribeMenu at {$th->getLine()} on start method: " . $th->getMessage());
        }
    }

    /**
     * !Step 1 (if user have sub) - Show the user's subscriptions.
     *
     * @param array $userSubs
     * @return void
     */
    private function show_user_subscriptions(array $userSubs)
    {
        try {
            foreach ($userSubs as $subId => $subInfo) {
                $name = get_clean_name($subInfo['name']);
                $this->addButtonRow(
                    InlineKeyboardButton::make("📶 {$name}", callback_data: "{$subId}@select_subscription")
                );
            }

            $this->addButtonRow(InlineKeyboardButton::make("➕ خرید اشتراک جدید", callback_data: "new@select_subscription"))
                ->addButtonRow(InlineKeyboardButton::make('🔙 بازگشت', callback_data: 'back@start'))
                ->orNext('cancel')
                ->showMenu();
        } catch (\Throwable $th) {
            Log::channel('bot')->error("Error in SubscribeMenu at {$th->getLine()} on show_user_subscriptions method: " . $th->getMessage());
        }
    }

    /**
     * !Step 2 - User selects a subscription.
     * @param Nutgram $bot
     * @return void
     */
    public function select_subscription(Nutgram $bot)
    {
        try {
            $subId = $bot->callbackQuery()->data;

            if (is_null($subId) or $subId == 'new' or str_contains($subId, 'sub_')) {
                $msg = 'شما درحال خرید اشتراک جدید هستید.';
            } else {
                $msg = "شما درحال خرید برای اشتراک با کد {$subId} هستید.";
            }

            $bot->setUserData('selected_sub_id', $subId, $bot->chatId());
            $this->show_plans($bot, $msg);

        } catch (\Throwable $th) {
            Log::channel('bot')->error("Error in SubscribeMenu at {$th->getLine()} on select_subscription method: " . $th->getMessage());
        }
    }

    /**
     * !Step 3 - Show the subscription plans.
     * @param Nutgram $bot
     * @return void
     */
    private function show_plans(Nutgram $bot, string $header = '')
    {
        try {
            $plans = SubscriptionPlan::active()->get();

            $this->clearButtons();

            $text = $header . "\n\n💡 لطفاً یکی از پلن‌های زیر را انتخاب کنید:";
            $this->menuText(escape_markdown($text), ['parse_mode' => ParseMode::MARKDOWN]);

            foreach ($plans as $plan) {
                $label = $plan->name . ' - ' . number_format($plan->amount / 1000) . ' تومان 💰';
                $this->addButtonRow(
                    InlineKeyboardButton::make($label, callback_data: $plan->slug . '@select_plan')
                );
            }

            $this->addButtonRow(InlineKeyboardButton::make('🔙 بازگشت', callback_data: 'back@start'))
                ->showMenu();

        } catch (\Throwable $th) {
            Log::channel('bot')->error("Error in SubscribeMenu at {$th->getLine()} on show_plans method: " . $th->getMessage());
        }
    }

    /**
     * !Step 3 - User selects a plan.
     * @param Nutgram $bot
     * @return void
     */
    public function select_plan(Nutgram $bot)
    {
        try {
            $planSlug = $bot->callbackQuery()->data;
            $plan     = SubscriptionPlan::where('slug', $planSlug)->first();

            if (! $plan) {
                $bot->sendMessage("⛔️ پلن انتخاب‌شده نامعتبر است.");
                return;
            }

            $bot->setUserData('selected_plan_id', $plan->id, $bot->chatId());
            $this->show_checkout($bot, $msg);

        } catch (\Throwable $th) {
            Log::channel('bot')->error("Error in SubscribeMenu at {$th->getLine()} on select_plan method: " . $th->getMessage());
        }
    }

    /**
     * !Step 5 - Show the checkout menu with payment options.
     *
     * @param Nutgram $bot
     * @param string  $msg
     * @return void
     */
    private function show_checkout(Nutgram $bot, string $msg)
    {
        try {
            $plan    = $this->get_selected_plan($bot);
            $gateway = $this->start_gateway($bot);
            $this->clearButtons()
                ->menuText("✅ پلن شما انتخاب شد!\n\n📦 {$plan['name']}\n💰 مبلغ: " . number_format($plan['amount']) . " تومان\n\n📌 $msg")
                ->addButtonRow(InlineKeyboardButton::make('💳 پرداخت آنلاین (فعال‌سازی آنی)', url: $gateway['url']))
                ->addButtonRow(InlineKeyboardButton::make('🔙 بازگشت', callback_data: 'back@start'))
                ->orNext('cancel')
                ->showMenu();
        } catch (\Throwable $th) {
            Log::channel('bot')->error("Error in SubscribeMenu at {$th->getLine()} on show_checkout method: " . $th->getMessage());
        }
    }

    public function cancel(Nutgram $bot)
    {
        $bot->sendMessage("🚫 خرید اشتراک لغو شد.\n🤔 چه کاری میخوای انجام بدی؟ از منو ربات انتخاب کن");
    }

    private function start_gateway(Nutgram $bot)
    {
        $payment = new PaymentService;
        $plan    = $this->get_selected_plan($bot);

        $gateway = $payment->createPaymentLink($plan->amount, $bot->userId());

        if (! isset($gateway['url'])) {
            $bot->sendMessage("⛔️ خطا در ایجاد لینک پرداخت. لطفاً دوباره تلاش کنید.");
            return;
        }

        $user  = User::where('tg_id', $bot->userId())->first();
        $subId = $bot->getUserData('selected_sub_id', $bot->chatId());
        $payment->createOrder(
            userId: $user->id,
            userSubId: $subId,
            planId: $plan->id,
            refId: $gateway['ref_id']
        );

        return $gateway;
    }

    /**
     * Get the selected subscription plan.
     *
     * @return SubscriptionPlan|null
     */
    private function get_selected_plan(Nutgram $bot): ?SubscriptionPlan
    {
        $planId = $bot->getUserData('selected_plan_id', $bot->chatId());
        $plan   = SubscriptionPlan::find($planId);
        if (! $plan) {
            $bot->sendMessage("⛔️ پلن انتخاب‌شده نامعتبر است.");
            return null;
        }

        return $plan;
    }

    /**
     * @return string
     */
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
}
