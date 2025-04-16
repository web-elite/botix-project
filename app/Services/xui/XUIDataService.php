<?php
namespace App\Services\xui;

use App\Models\SubscriptionPlan;
use App\Models\Transactions;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
            'status'       => (bool) ($client['enable'] ?? false),
            'name'         => $this->cleanEmail($client['email'] ?? ''),
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
     * Clean email format.
     */
    private function cleanEmail(string $email): string
    {
        $patterns = [
            '/\d+--/',            // Remove numbers followed by double hyphens (e.g., "4--")
            '/\d+==/',            // Remove numbers followed by double equals (e.g., "4==")
            '/b--/',              // Remove literal "b--"
            '/(\w+)-(2user\b)/i', // Handle "2user" case (case insensitive)
            '/(\w+)-(3user\b)/i', // Handle "3user" case (case insensitive)
            '/\s+/',              // Remove all whitespace
        ];

        $replacements = [
            '', // For patterns 1-3
            '',
            '',
            '$1($2)', // For pattern 4 (wraps in parentheses)
            '$1($2)', // For pattern 5
            '',       // For pattern 6
        ];

        $cleaned = preg_replace($patterns, $replacements, $email);
        return trim(strtolower($cleaned));
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
                return $this->createNewClient($user, $plan, $inboundIds);
            }

            $meta = $user->meta['xui_data'][$subKey] ?? null;

            // اگر subKey وجود نداشت، یعنی اشتراک واقعی نداشته ولی پرداخت کرده → کلاینت جدید بساز
            if (! $meta) {
                Log::channel('xui-api')->warning("subKey not found, creating new client instead", ['user_id' => $user->id, 'subKey' => $subKey]);
                return $this->createNewClient($user, $plan, $inboundIds);
            }

            return $this->updateExistingClient($user, $plan, $inboundIds, $meta, $subKey);

        } catch (\Throwable $e) {
            Log::channel('xui-api')->error("❌ Error in updateClientAfterPurchase", [
                'transaction_id' => $transaction->id,
                'user_id'        => $transaction->user_id,
                'sub_key'        => $transaction->user_subscription_id,
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

        $clientData = $this->buildClientData($uuid, $subId, $user, $plan, $expiryTime, 0, '');

        foreach ($inboundIds as $inboundId) {
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

        $clientData = $this->buildClientData(
            $meta['id'],
            $subKey,
            $user,
            $plan,
            $expiry,
            $meta['limitIp'] ?? 1,
            $meta['flow'] ?? ''
        );

        foreach ($inboundIds as $inboundId) {
            app(XUIApiService::class)->addClient($inboundId, $clientData);
        }

        return true;
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
    protected function buildClientData(string $uuid, string $subId, User $user, SubscriptionPlan $plan, int $expiry, int $limitIp, string $flow): array
    {
        return [
            'id'         => $uuid,
            'flow'       => $flow,
            'email'      => $user->id,
            'limitIp'    => $limitIp,
            'totalGB'    => $plan->total_gb * 1024 * 1024 * 1024,
            'expiryTime' => $expiry,
            'enable'     => true,
            'tgId'       => $user->tg_id ?? '',
            'subId'      => $subId,
            'reset'      => 0,
        ];
    }

}
