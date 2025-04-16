<?php
namespace App\Services\xui;

use App\Models\SubscriptionPlan;
use App\Models\Transactions;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class XUIDataService
{
    protected XUIApiService $xuiApi;

    public function __construct(XUIApiService $xuiApi)
    {
        $this->api = $xuiApi;
    }

    /**
     * Fetch and process user data from XUI API.
     */
    public function getUsersData(): array | false
    {
        try {
            return $this->dataFormatAsContract(
                $this->processInbounds($this->api->getInbounds())
            );
        } catch (\Exception $e) {
            Log::channel('xui-api')->error('Sync Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace'     => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Format data into a structured contract.
     */
    private function dataFormatAsContract(Collection $groupedClients): array
    {
        return $groupedClients->map(fn($clients) => [
            'xui_data' => $this->prepareXuiData($clients),
        ])->toArray();
    }

    /**
     * Process inbound data into a structured collection.
     */
    private function processInbounds(array $inbounds): Collection
    {
        return collect($inbounds)
            ->flatMap(fn($inbound) => $this->extractClientsFromInbound($inbound))
            ->filter(fn($client) => isset($client['tgId']) && is_numeric($client['tgId']) && $client['tgId'] > 0)
            ->groupBy('tgId')
            ->map(fn($clients) => $clients->unique('subId')->values());
    }

    /**
     * Extract clients from inbound settings and statistics.
     */
    private function extractClientsFromInbound(array $inbound): array
    {
        return $this->mergeClients(
            $this->parseJson($inbound['settings'] ?? '', 'clients', []),
            $inbound['clientStats'] ?? []
        );
    }

    /**
     * Merge settings and stats clients.
     */
    private function mergeClients(array $settingsClients, array $statsClients): array
    {
        $lookup = array_column($settingsClients, null, 'email');
        return array_map(function ($item) use ($lookup) {
            $email = $item['email'];
            if (isset($lookup[$email]) && isset($lookup[$email]['enable'])) {
                $item['enable'] = $lookup[$email]['enable'];
            }
            return $item + $lookup[$email];
        }, $statsClients);
    }

    /**
     * Prepare XUI data for output.
     */
    private function prepareXuiData(Collection $clients): string
    {
        return json_encode($clients->mapWithKeys(fn($client) => [
            $client['subId'] => $this->formatClientData($client),
        ])->toArray());
    }

    /**
     * Format individual client data.
     */
    private function formatClientData(array $client): array
    {
        return [
            'status'       => (bool) ($client['enable']),
            'name'         => $client['email'] ?? '',
            'id'           => (string) ($client['id'] ?? ''),
            'subscription' => (string) ($client['subId'] ?? ''),
            'time_limit'   => (int) ($client['expiryTime'] ?? 0),
            'value_limit'  => (float) ($client['total'] ?? 0),
            'upload'       => (int) ($client['up'] ?? 0),
            'download'     => (int) ($client['down'] ?? 0),
            'usage'        => $this->calculateUsagePercentage($client),
            'reset'        => (int) ($client['reset'] ?? 0),
            'flow'         => (string) ($client['flow'] ?? ''),
            'totalGB'      => (int) ($client['totalGB'] ?? 0),
            'comment'      => (string) ($client['comment'] ?? ''),
            'limitIp'      => (int) ($client['limitIp'] ?? 0),
        ];
    }

    /**
     * Calculate usage percentage.
     */
    private function calculateUsagePercentage(array $client): ?float
    {
        return isset($client['total']) && $client['total'] > 0
        ? min(100, round((($client['up'] ?? 0) + ($client['down'] ?? 0)) / $client['total'] * 100, 2))
        : null;
    }

    /**
     * Safe JSON parsing helper.
     */
    private function parseJson(string $json, string $key, $default = null)
    {
        return optional(json_decode($json, true))[$key] ?? $default;
    }

    /**
     * Get client inbound ID by subscription ID and Telegram ID.
     *
     * @param string $subId
     * @param int $tgId
     * @return int|null
     */
    public function getClientInboundId($subId, $tgId): ?int
    {
        $inbounds = $this->api->getInbounds();

        foreach ($inbounds as $inbound) {
            $clients = $this->parseJson($inbound['settings'] ?? '', 'clients', []);

            foreach ($clients as $client) {
                if ($client['tgId'] == $tgId && $client['subId'] === $subId) {
                    return (int) $inbound['id'];
                }
            }
        }

        return null;
    }

    /**
     * Get all inbound IDs.
     *
     * @return array
     */
    public function getAllInboundsId(): array
    {
        $excludedIds = [27, 29];

        $inbounds = $this->api->getInbounds();

        $filtered = array_filter($inbounds, fn($inbound) => ! in_array((int) $inbound['id'], $excludedIds));

        return array_map(fn($inbound) => (int) $inbound['id'], $filtered);
    }

    /**
     * Update client data after purchase.
     *
     * @param Transactions $transaction
     * @return bool
     */
    public function updateClientAfterPurchase(Transactions $transaction): bool
    {
        try {
            $plan   = SubscriptionPlan::find($transaction->subscription_plan_id);
            $user   = User::find($transaction->user_id);
            $xui    = app(XUIApiService::class);
            $subKey = $transaction->user_subscription_id;

            $inboundIds = $this->getAllInboundsId();

            if ($subKey === 'new') {
                Log::channel('xui-api')->info("Creating new client for user", ['user_id' => $user->id, 'subKey' => $subKey]);
                return $this->createNewClient($user, $plan, $inboundIds);
            }

            $meta = $user->xui_data[$subKey] ?? null;

            // اگر subKey وجود نداشت، یعنی اشتراک واقعی نداشته ولی پرداخت کرده → کلاینت جدید بساز
            if (! $meta) {
                Log::channel('xui-api')->warning("subKey not found, creating new client instead", ['user_id' => $user->id, 'subKey' => $subKey]);
                return $this->createNewClient($user, $plan, $inboundIds);
            }

            Log::channel('xui-api')->info("Updating existing client", [
                'user_id'        => $user->id,
                'sub_key'        => $subKey,
                'transaction_id' => $transaction->id,
            ]);
            return $this->updateExistingClient($user, $plan, $inboundIds, $meta, $subKey);

        } catch (\Throwable $e) {
            Log::channel('xui-api')->error("❌ Error in updateClientAfterPurchase", [
                'transaction_id' => $transaction->id,
                'user_id'        => $transaction->user_id,
                'sub_key'        => $transaction->user_subscription_id,
                'where'          => $e->getFile() . ':' . $e->getLine(),
                'error'          => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Create a new client in XUI.
     *
     * @param User $user
     * @param SubscriptionPlan $plan
     * @param array $inboundIds
     * @return bool
     */
    protected function createNewClient(User $user, SubscriptionPlan $plan, array $inboundIds): bool
    {
        $uuid       = Str::uuid()->toString();
        $subId      = Str::random(16);
        $expiryTime = now()->addMonths($plan->duration)->timestamp * 1000;

        foreach ($inboundIds as $inboundId) {
            $clientData = $this->buildNewClientData($uuid, $subId, $user, $plan, $expiryTime, 0, '', $inboundId);
            app(XUIApiService::class)->addClient($inboundId, $clientData);
        }

        return true;
    }

    /**
     * Update existing client data.
     *
     * @param User $user
     * @param SubscriptionPlan $plan
     * @param array $inboundIds
     * @param array $meta
     * @param string $subKey
     * @return bool
     */
    protected function updateExistingClient(User $user, SubscriptionPlan $plan, array $inboundIds, array $meta, string $subKey): bool
    {
        $nowMs    = now()->timestamp * 1000;
        $baseTime = $meta['time_limit'] > $nowMs ? $meta['time_limit'] : $nowMs;
        $expiry   = Carbon::createFromTimestampMs($baseTime)->addMonths($plan->duration)->timestamp * 1000;

        $xuiApi = app(XUIApiService::class);

        foreach ($inboundIds as $inboundId) {
            $uuid = $this->getClientUuidBySubId($subKey, $inboundId);

            if (! $uuid) {
                Log::channel('xui-api')->warning("UUID not found for subId", [
                    'user_id'    => $user->id,
                    'sub_key'    => $subKey,
                    'inbound_id' => $inboundId,
                ]);
                continue;
            }

            $existingClient = $this->getClientByUuid($uuid, $inboundId);

            if (! $existingClient) {
                Log::channel('xui-api')->warning("Client not found for UUID", [
                    'user_id'    => $user->id,
                    'uuid'       => $uuid,
                    'inbound_id' => $inboundId,
                ]);
                continue;
            }

            // فقط این فیلدها رو تغییر بده
            $existingClient['expiryTime'] = $expiry;
            $existingClient['totalGB']    = $plan->volume * 1024 * 1024 * 1024;

            // حالا ارسال اطلاعات جدید
            $xuiApi->updateClient($inboundId, $uuid, $existingClient);
        }

        return true;
    }

    /**
     * Get client data by UUID and inbound ID.
     *
     * @param string $uuid
     * @param int $inboundId
     * @return array|null
     */
    public function getClientByUuid(string $uuid, int $inboundId): ?array
    {
        $inbounds = $this->api->getInbounds();

        foreach ($inbounds as $inbound) {
            if ($inbound['id'] !== $inboundId) {
                continue;
            }

            $settings = json_decode($inbound['settings'] ?? '{}', true);
            $clients  = $settings['clients'] ?? [];

            foreach ($clients as $client) {
                if (($client['id'] ?? null) === $uuid) {
                    return $client;
                }
            }
        }

        return null;
    }

    /**
     * Get client UUID by subscription ID and inbound ID.
     *
     * @param string $subId
     * @param int $inboundId
     * @return string|null
     */
    public function getClientUuidBySubId(string $subId, int $inboundId): ?string
    {
        $inbounds = $this->api->getInbounds();

        foreach ($inbounds as $inbound) {
            if ($inbound['id'] !== $inboundId) {
                continue;
            }

            $settings = json_decode($inbound['settings'] ?? '{}', true);
            $clients  = $settings['clients'] ?? [];

            foreach ($clients as $client) {
                if (($client['subId'] ?? null) === $subId) {
                    return $client['id']; // این همون uuid هست
                }
            }
        }

        return null;
    }

    /**
     * Build client data for XUI API.
     *
     * @param string $uuid
     * @param string $subId
     * @param User $user
     * @param SubscriptionPlan $plan
     * @param int $expiry
     * @param int $limitIp
     * @param string $flow
     * @return array
     */
    protected function buildNewClientData(string $uuid, string $subId, User $user, SubscriptionPlan $plan, int $expiry, int $limitIp, string $flow, int $inboundId): array
    {
        return [
            'id'         => $uuid,
            'flow'       => $flow,
            'email'      => substr($subId, -5) . '--' . $inboundId . "((({$user->name} - {$plan->users_count}user)))",
            'limitIp'    => $limitIp,
            'totalGB'    => $plan->total_gb * 1024 * 1024 * 1024,
            'expiryTime' => $expiry,
            'enable'     => true,
            'tgId'       => $user->tg_id ?? '',
            'subId'      => $subId,
            'reset'      => 0,
        ];
    }

    /**
     * Create a test client for a user.
     *
     * @param User $user
     * @return bool
     */
    public function createTestClient(User $user): array
    {
        try {
            $uuid       = Str::uuid()->toString();
            $subId      = 'test-' . Str::random(10);
            $expiryTime = now()->addDay()->timestamp * 1000; // 24 ساعت
            $inboundIds = $this->getAllInboundsId();

            foreach ($inboundIds as $inboundId) {
                $clientData = [
                    'id'         => $uuid,
                    'flow'       => '',
                    'email'      => substr($subId, -5) . '--' . $inboundId . "((({$user->name} - Test)))",
                    'limitIp'    => 1,
                    'totalGB'    => 5 * 1024 * 1024 * 1024, // 5 گیگ
                    'expiryTime' => $expiryTime,
                    'enable'     => true,
                    'tgId'       => $user->tg_id ?? '',
                    'subId'      => $subId,
                    'reset'      => 0,
                ];
                $this->api->addClient($inboundId, $clientData);
            }

            return $clientData;
        } catch (\Throwable $e) {
            Log::channel('xui-api')->error('❌ Error in createTestClient', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            return [
                'status'  => false,
                'message' => 'خطا در ایجاد اشتراک تستی',
            ];
        }
    }

    /**
     * Clean email format.
     */
    private function parseEmailNameParts(string $email): array
    {
        // تمیز کردن برای اینکه مطمئن باشیم از چیزهای اضافی راحت شدیم
        $email = trim($email);

        $patterns = [
            '/\d+--/', // Remove numbers followed by double hyphens (e.g., "4--")
            '/\d+==/', // Remove numbers followed by double equals (e.g., "4==")
            '/b--/',   // Remove literal "b--"
        ];

        $replacements = [
            '', // For patterns 1-3
            '',
            '',
        ];

        $email = preg_replace($patterns, $replacements, $email);

        // اگر ساختار چیزی مثل "AminAbdi-3user" باشه:
        if (preg_match('/^(.*?)(?:-(\d+user))?$/', $email, $matches)) {
            return [
                'real_name'  => trim($matches[1]),
                'user_count' => $matches[2] ?? null,
            ];
        }

        // fallback:
        return [
            'real_name'  => $email,
            'user_count' => null,
        ];
    }

    /**
     * Convert Client Email in XUI to New Structure
     * convert this this -> AminMohammadi - 3user
     * to this           -> 8ujwi--33(((AminMohammadi - 3user)))
     */
    public function convertEmailToNewStructure()
    {
        $inbounds = $this->api->getInbounds();
        foreach ($inbounds as $inbound) {

            $inboundId = $inbound['id'];

            $settings = json_decode($inbound['settings'], true);

            $clients = $settings['clients'] ?? [];
            $stats   = &$inbound['clientStats'];

            foreach ($clients as &$client) {
                $oldEmail = $client['email'];

                $parts       = $this->parseEmailNameParts($oldEmail);
                $realName    = $parts['real_name'];
                $users_count = $parts['user_count'];

                $subId    = $client['subId'] ?? '';
                $newEmail = substr($subId, -5) . '--' . $inboundId . "((({$realName}" . ($users_count ? " - {$users_count}" : '') . ")))";

                $client['email'] = $newEmail;

                foreach ($stats as &$stat) {
                    if ($stat['email'] === $oldEmail) {
                        $stat['email'] = $newEmail;
                    }
                }
            }

            $settings['clients'] = $clients;

            $inbound['settings'] = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $this->api->updateInbound($inbound, $inboundId);
        }
    }

}
