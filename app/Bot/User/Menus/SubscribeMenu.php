<?php

namespace App\Bot\User\Menus;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Payment\PaymentService;
use App\Services\UserService;
use App\Services\xui\XUIDataService;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class SubscribeMenu extends InlineMenu
{
    protected string $selectedSubId;
    protected string $selectedType;
    protected int $selectedPlanId;
    protected string $subName;
    protected string $subTgId;

    /**
     * Step 1 - Show initial subscription menu
     */
    public function start(Nutgram $bot): void
    {
        try {
            $this->clearButtons();

            $userService = app(UserService::class);
            $userSubs = $userService->getUserXuiData($bot->userId(), 'active');

            if (count($userSubs) > 0) {
                $this->showUserSubscriptions($userSubs);
            } else {
                $this->selectSubscription($bot);
            }
        } catch (\Throwable $th) {
            $this->logError($th, __METHOD__);
            $bot->sendMessage('⚠️ خطایی در سیستم رخ داده است. لطفاً مجدداً تلاش کنید.');
        }
    }

    /**
     * Show user's active subscriptions
     */
    private function showUserSubscriptions(array $userSubs): void
    {
        try {
            $this->menuText("📌 لطفاً اشتراک خود را برای تمدید انتخاب کنید.\n\n➕ برای خرید اشتراک جدید، گزینه زیر را انتخاب کنید.");

            foreach ($userSubs as $subId => $subInfo) {
                $name = get_clean_name($subInfo['name']);
                $this->addButtonRow(
                    InlineKeyboardButton::make("📶 {$name}", callback_data: "{$subId}@selectSubscription")
                );
            }

            $this->addButtonRow(
                InlineKeyboardButton::make("➕ خرید اشتراک جدید", callback_data: "new@selectSubscription")
            )->showMenu();
        } catch (\Throwable $th) {
            $this->logError($th, __METHOD__);
        }
    }

    /**
     * Step 2 - Handle subscription selection
     */
    public function selectSubscription(Nutgram $bot): void
    {
        try {
            $this->selectedSubId = $bot->callbackQuery()->data ?? '';
            $this->showTypeOfPlans($bot);
        } catch (\Throwable $th) {
            $this->logError($th, __METHOD__);
        }
    }

    /**
     * Step 3 - Show plan type selection
     */
    private function showTypeOfPlans(Nutgram $bot): void
    {
        try {
            $this->clearButtons();
            $msg = $this->getSelectedSubInfoMsg($bot);

            $text = $msg . "\n\n💡 لطفاً نوع پلن را انتخاب کنید:\n\n"
                . "📦 حجمی: با محدودیت حجمی و بدون محدودیت کاربر\n"
                . "⏳ غیر حجمی: با محدودیت کاربر ولی بدون محدودیت حجمی";

            $this->menuText(escape_markdown($text), ['parse_mode' => ParseMode::MARKDOWN])
                ->addButtonRow(
                    InlineKeyboardButton::make('📦 حجمی', callback_data: 'volume@selectTypeOfPlans'),
                    InlineKeyboardButton::make('⏳ غیر حجمی', callback_data: 'time@selectTypeOfPlans')
                )
                ->addButtonRow(InlineKeyboardButton::make('🔙 بازگشت', callback_data: 'back@start'))
                ->showMenu();
        } catch (\Throwable $th) {
            $this->logError($th, __METHOD__);
        }
    }

    /**
     * Step 4 - Handle plan type selection
     */
    public function selectTypeOfPlans(Nutgram $bot): void
    {
        try {
            $this->selectedType = $bot->callbackQuery()->data ?? '';
            $this->showPlans($bot);
        } catch (\Throwable $th) {
            $this->logError($th, __METHOD__);
        }
    }

    /**
     * Step 5 - Show appropriate plans based on selected type
     */
    private function showPlans(Nutgram $bot): void
    {
        try {
            if ($this->selectedType === 'volume') {
                $this->showVolumePlans($bot);
            } elseif ($this->selectedType === 'time') {
                $this->showTimePlans($bot);
            } else {
                $bot->sendMessage("⛔️ نوع پلن انتخاب‌شده نامعتبر است.");
            }
        } catch (\Throwable $th) {
            $this->logError($th, __METHOD__);
        }
    }

    /**
     * Step 6 - Show volume-based plans
     */
    private function showVolumePlans(Nutgram $bot): void
    {
        try {
            $this->clearButtons();

            $plans = $this->getFilteredPlans(SubscriptionPlan::volPlans());

            $this->menuText(
                escape_markdown("💡 لطفاً یکی از پلن‌های زیر را انتخاب کنید:"),
                ['parse_mode' => ParseMode::MARKDOWN]
            );

            foreach ($plans as $plan) {
                $label = "{$plan->name} - " . number_format($plan->amount / 1000) . ' تومان 💰';
                $this->addButtonRow(
                    InlineKeyboardButton::make($label, callback_data: "{$plan->slug}@selectPlan")
                );
            }

            $this->addButtonRow(InlineKeyboardButton::make('🔙 بازگشت', callback_data: 'back@start'))
                ->showMenu();
        } catch (\Throwable $th) {
            $this->logError($th, __METHOD__);
        }
    }

    /**
     * Step 7 - Show time-based plans
     */
    private function showTimePlans(Nutgram $bot): void
    {
        try {
            $this->clearButtons();
            $msg = $this->getSelectedSubInfoMsg($bot);

            $plans = $this->getFilteredPlans(SubscriptionPlan::timePlans());

            $this->menuText(
                escape_markdown("{$msg}\n\n💡 لطفاً یکی از پلن‌های زیر را انتخاب کنید:"),
                ['parse_mode' => ParseMode::MARKDOWN]
            );

            foreach ($plans as $plan) {
                $label = "{$plan->name} - " . number_format($plan->amount / 1000) . ' تومان 💰';
                $this->addButtonRow(
                    InlineKeyboardButton::make($label, callback_data: "{$plan->slug}@selectPlan")
                );
            }

            $this->addButtonRow(InlineKeyboardButton::make('🔙 بازگشت', callback_data: 'back@start'))
                ->showMenu();
        } catch (\Throwable $th) {
            $this->logError($th, __METHOD__);
        }
    }

    /**
     * Get filtered plans based on user selection
     */
    private function getFilteredPlans($plansQuery)
    {
        if (!$this->userSelectedSubIsNew()) {
            $usersCount = $this->extractUserCount();
            if ($usersCount > 0) {
                $plansQuery->where('users_count', $usersCount);
            }
        }

        return $plansQuery->get()->sortBy([
            ['duration', 'asc'],
            ['users_count', 'asc'],
        ]);
    }

    /**
     * Step 8 - Handle plan selection
     */
    public function selectPlan(Nutgram $bot): void
    {
        try {
            $planSlug = $bot->callbackQuery()->data ?? '';
            $plan = SubscriptionPlan::where('slug', $planSlug)->first();

            if (!$plan) {
                $bot->sendMessage("⛔️ پلن انتخاب‌شده نامعتبر است.");
                return;
            }

            $this->selectedPlanId = $plan->id;

            if (this_id_is_admin($bot->chatId())) {
                $this->showAdminCheckout($bot);
            } else {
                $this->showCheckout($bot);
            }
        } catch (\Throwable $th) {
            $this->logError($th, __METHOD__);
        }
    }

    /**
     * Step 9 - Show checkout for regular users
     */
    private function showCheckout(Nutgram $bot): void
    {
        try {
            $msg = $this->getSelectedSubInfoMsg($bot);
            $plan = $this->getSelectedPlan();
            $gateway = $this->startGateway($bot);

            $this->clearButtons()
                ->menuText("✅ پلن شما انتخاب شد!\n\n$msg\n\n📦 {$plan->name}\n💰 مبلغ: " . number_format($plan->amount) . " تومان\n\n📌 $msg")
                ->addButtonRow(InlineKeyboardButton::make('💳 پرداخت آنلاین (فعال‌سازی آنی)', url: $gateway['url']))
                ->addButtonRow(InlineKeyboardButton::make('🔙 بازگشت', callback_data: 'back@start'))
                ->showMenu();
        } catch (\Throwable $th) {
            $this->logError($th, __METHOD__);
        }
    }

    /**
     * Step 9 (Admin) - Show admin checkout
     */
    private function showAdminCheckout(Nutgram $bot): void
    {
        try {
            $msg = $this->getSelectedSubInfoMsg($bot);
            $plan = $this->getSelectedPlan();

            $this->clearButtons()
                ->menuText("✅ پلن شما انتخاب شد!\n\n📦 {$plan->name}\n💰 مبلغ: " . number_format($plan->amount) . " تومان\n\n📌 $msg")
                ->addButtonRow(InlineKeyboardButton::make('انتخاب نام کاربر', callback_data: 'start@askSubInfoFromAdmin'))
                ->showMenu();
        } catch (\Throwable $th) {
            $this->logError($th, __METHOD__);
        }
    }

    /**
     * Step 10 (Admin) - Ask for sub info
     */
    public function askSubInfoFromAdmin(Nutgram $bot): void
    {
        $this->clearButtons()
            ->menuText("🔹 لطفاً نام و آیدی عددی تلگرام کاربر را وارد نمایید.\n\nفرمت صحیح:\nنام | آیدی عددی تلگرامی\nنمونه:\nکاربر1 | 123456789")
            ->orNext('handleAdminSubInfoInput')
            ->showMenu();
    }

    /**
     * Handle admin sub info input
     */
    public function handleAdminSubInfoInput(Nutgram $bot): void
    {
        try {
            $this->clearButtons();
            $text = $bot->message()?->text;

            if (!$text || !str_contains($text, '|')) {
                $this->clearButtons();
                $bot->menuText("❌ فرمت نامعتبر. لطفاً از فرمت زیر استفاده کنید:\nنام | آیدی عددی تلگرامی")
                    ->orNext('handleAdminSubInfoInput')
                    ->showMenu();
                return;
            }

            [$this->subName, $this->subTgId] = array_map('trim', explode('|', $text));
            $this->createAdminSubscription($bot);
        } catch (\Throwable $th) {
            $this->logError($th, __METHOD__);
        }
    }

    /**
     * Step 11 (Admin) - Create admin subscription
     */
    private function createAdminSubscription(Nutgram $bot): void
    {
        Log::info('create admin subcription');
        try {
            $plan = $this->getSelectedPlan();
            $xuiData = app(XUIDataService::class);
            $inboundIds = $xuiData->getAllInboundsId();
            $subTgId = $this->subTgId == 1 ? $bot->chatId() : $this->subTgId;
            $subID = $xuiData->createNewClient(
                $subTgId,
                $this->subName,
                $plan,
                $inboundIds
            );

            $panelBase = sprintf(
                "%s://%s:%s",
                env('XUI_SSL_ACTIVE') ? 'https' : 'http',
                env('XUI_SUB_DOMAIN'),
                env('XUI_SUB_PORT')
            );

            $subUrl         = "{$panelBase}/" . env('XUI_SUB_PATH') . "/{$subID}";
            $jsonUrl        = "{$panelBase}/" . env('XUI_SUB_JSON_PATH') . "/{$subID}";

            $text = "🎉 ادمین عزیز اشتراک با موفقیت ایجاد شد!\n\n 🔗 *لینک معمولی*:\n`{$subUrl}`\n\n🔗 *لینک حرفه‌ای*:\n`{$jsonUrl}`\n";
            $this->clearButtons();
            $bot->sendMessage(text: escape_markdown($text), chat_id: $bot->chatId(), parse_mode: ParseMode::MARKDOWN);
        } catch (\Throwable $th) {
            $this->logError($th, __METHOD__);
        }
    }

    /**
     * Create payment gateway
     */
    private function startGateway(Nutgram $bot): array
    {
        $payment = app(PaymentService::class);
        $plan = $this->getSelectedPlan();
        $user = User::where('tg_id', $bot->userId())->first();

        $gateway = $payment->createPaymentLink($plan->amount, $bot->userId());

        if ($gateway['status'] == 'error' or !isset($gateway['url'])) {
            Log::channel('bot')->error("Payment link not created", [
                'user_id' => $bot->userId(),
                'plan_id' => $plan->id,
                'gateway' => $gateway,
            ]);

            throw new \Exception("خطا در ایجاد لینک پرداخت");
        }

        Log::channel('bot')->info("Payment link created", [
            'user_id' => $bot->userId(),
            'plan_id' => $plan->id,
            'gateway' => $gateway,
        ]);

        $payment->createOrder(
            userId: $user->id,
            userSubId: $this->selectedSubId,
            planId: $plan->id,
            refId: $gateway['ref_id']
        );

        return $gateway;
    }

    /**
     * Get selected plan
     */
    private function getSelectedPlan(): SubscriptionPlan
    {
        $plan = SubscriptionPlan::find($this->selectedPlanId);

        if (!$plan) {
            throw new \Exception("پلن انتخاب‌شده نامعتبر است");
        }

        return $plan;
    }

    /**
     * Get subscription info message
     */
    protected function getSelectedSubInfoMsg(): string
    {
        if ($this->userSelectedSubIsNew()) {
            return 'شما در حال خرید اشتراک جدید هستید.';
        }

        $userCount = $this->extractUserCount();
        return "شما در حال تمدید اشتراک با کد {$this->selectedSubId} هستید.\nاین اشتراک {$userCount} کاربره است.";
    }

    /**
     * Check if selected sub is new
     */
    private function userSelectedSubIsNew(): bool
    {
        return empty($this->selectedSubId) ||
            $this->selectedSubId === 'new' ||
            !str_contains($this->selectedSubId, 'sub_');
    }

    /**
     * Extract user count from sub name
     */
    private function extractUserCount(): int
    {
        $userService = app(UserService::class);
        $userSub = $userService->getUserXuiData($this->selectedSubId);

        if (filled($userSub) && preg_match('/(\d+)user/', $userSub['name'], $matches)) {
            return (int) $matches[1];
        }

        return 1;
    }

    /**
     * Cancel subscription
     */
    public function cancel(Nutgram $bot): void
    {
        $bot->sendMessage("🚫 خرید اشتراک لغو شد.\n🤔 چه کاری می‌خواهید انجام دهید؟ از منو انتخاب کنید.");
    }

    /**
     * Log errors
     */
    private function logError(\Throwable $th, string $method): void
    {
        Log::channel('bot')->error("Error in SubscribeMenu at {$th->getLine()} in {$method}: {$th->getMessage()}");
    }

    /**
     * Menu description text
     */
    protected function text(): string
    {
        return "🔥 خرید اشتراک فیلترشکن دریچه 🔥\n\n"
            . "🚪 اشتراک دریچه، راهی برای عبور از محدودیت‌های اینترنتی 🚀\n"
            . "ویژگی‌ها:\n\n"
            . "⚡️ سرعت فوق‌العاده\n"
            . "✅ قطعی صفر درصد\n"
            . "🌍 سرورهای اختصاصی\n"
            . "😍 نیم‌بها\n"
            . "💻 پشتیبانی از تمام دستگاه‌ها\n"
            . "👨‍👩‍👧‍👦 حالت خانواده\n"
            . "🔄 بروزرسانی خودکار\n\n"
            . "🌟 اشتراک دلخواه خود را انتخاب کنید!";
    }
}
