<?php
namespace App\Services\xui;

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
            return $item + $lookup[$item['email']];
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
}
