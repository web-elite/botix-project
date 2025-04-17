<?php
namespace App\Services;

use App\Models\User;
use App\Services\xui\XUIDataService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserSyncService
{
    /**
     * @var XUIDataService
     */
    private XUIDataService $xuiDataService;

    public function __construct(XUIDataService $xuiDataService)
    {
        $this->xuiDataService = $xuiDataService;
    }

    /**
     * Sync XUI users with the database.
     *
     * @return void
     */
    public function syncXuiUsers(): void
    {
        try {
            $groupedClients = $this->xuiDataService->getUsersData();
            $this->setXuiSubsStatusOff();
            collect($groupedClients)
                ->map(function ($client, $tgId) {
                    try {
                        return $this->prepareXuiUserData($client, $tgId);
                    } catch (\Exception $e) {
                        Log::channel('xui-api')->error("Failed preparing XUI user data for tg_id: $tgId", [
                            'error'  => $e->getMessage(),
                            'client' => $client,
                        ]);
                        return null;
                    }
                })
                ->filter()
                ->each(function ($userData) {
                    try {
                        $userData['meta'] = $this->mergeMetaData(
                            $userData['tg_id'],
                            $userData['meta']
                        );

                        User::updateOrCreate(
                            ['tg_id' => $userData['tg_id']],
                            $userData
                        );
                    } catch (\Exception $e) {
                        Log::channel('xui-api')->error("Failed syncing XUI user with tg_id: {$userData['tg_id']}", [
                            'File:Line' => $e->getFile() . ':' . $e->getLine(),
                            'error'     => $e->getMessage(),
                            'userData'  => $userData,
                        ]);
                    }
                });

        } catch (\Exception $e) {
            Log::channel('xui-api')->error('Failed syncing XUI users', [
                'File:Line' => $e->getFile() . ':' . $e->getLine(),
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw if you want calling code to handle it
        }
    }

    private function setXuiSubsStatusOff()
    {
        User::chunk(100, function ($users) {
            foreach ($users as $user) {
                $meta = $user->meta;

                if (isset($meta['xui_data']) && is_array($meta['xui_data'])) {
                    foreach ($meta['xui_data'] as $key => &$sub) {
                        $sub['status'] = false;
                    }

                    $user->meta = $meta;
                    $user->save();
                }
            }
        });
    }

    public function syncTelegramUser(object $telegramUser): void
    {
        try {
            $userData = $this->prepareTelegramUserData($telegramUser);

            $userData['meta'] = $this->mergeMetaData(
                $telegramUser->id,
                $userData['meta']
            );
            User::updateOrCreate(
                ['tg_id' => $telegramUser->id],
                $userData
            );
        } catch (\Exception $e) {
            Log::error("Failed syncing Telegram user with id: {$telegramUser->id}", [
                'File:Line' => $e->getFile() . ':' . $e->getLine(),
                'error'     => $e->getMessage(),
                'user'      => $telegramUser,
            ]);
            throw $e;
        }
    }

    private function prepareXuiUserData(array $client, string $tgId): ?array
    {
        try {
            $subscriptions = json_decode($client['xui_data'], true) ?? [];

            if (empty($subscriptions)) {
                return null;
            }

            $existingUser      = User::where('tg_id', $tgId)->first();
            $firstSubscription = reset($subscriptions);

            if ($existingUser) {
                $updateData = [
                    'tg_id' => (int) $tgId,
                    'meta'  => ['xui_data' => $subscriptions],
                ];
            } else {
                $updateData = [
                    'tg_id'    => (int) $tgId,
                    'meta'     => ['xui_data' => $subscriptions],
                    'name'     => $firstSubscription['name'] ?? 'Unknown',
                    'email'    => $this->generateEmail($firstSubscription['name'] ?? $tgId),
                    'password' => bcrypt($firstSubscription['subscription'] ?? Str::random(16)),
                    'phone'    => $firstSubscription['comment'] ?? null,
                ];
            }

            return $updateData;
        } catch (\Exception $e) {
            Log::error("Error preparing XUI user data for tg_id: $tgId", [
                'error'  => $e->getMessage(),
                'client' => $client,
            ]);
            throw $e;
        }
    }

    private function prepareTelegramUserData(object $telegramUser): array
    {
        try {
            return [
                'meta'     => ['telegram_data' => $telegramUser], // Changed to root level
                'name'     => $this->formatUserName($telegramUser),
                'email'    => $this->generateEmail($telegramUser->username ?? $telegramUser->id),
                'password' => Hash::make($telegramUser->id),
            ];
        } catch (\Exception $e) {
            Log::error("Error preparing Telegram user data for id: {$telegramUser->id}", [
                'File:Line' => $e->getFile() . ':' . $e->getLine(),
                'error'     => $e->getMessage(),
                'user'      => $telegramUser,
            ]);
            throw $e;
        }
    }

    private function mergeMetaData(string $tgId, array $newMeta): array
    {
        try {

            // Get existing meta data if user exists
            $existingMeta = User::where('tg_id', $tgId)
                ->value('meta') ?? [];

            // Convert existing meta to array if it's JSON string
            if (is_string($existingMeta)) {
                $existingMeta = json_decode($existingMeta, true) ?? [];
            }

            // Merge new meta with existing meta
            return array_merge(
                (array) $existingMeta,
                $newMeta
            );

        } catch (\Exception $e) {
            Log::error("Error merging meta data for tg_id: $tgId", [
                'error'   => $e->getMessage(),
                'newMeta' => $newMeta,
            ]);
            throw $e;
        }
    }

    private function formatUserName(object $telegramUser): string
    {
        try {
            return trim(
                ($telegramUser->first_name ?? '') . ' ' .
                ($telegramUser->last_name ?? '')
            ) ?: 'Telegram User';
        } catch (\Exception $e) {
            Log::error("Error formatting username for Telegram user", [
                'File:Line' => $e->getFile() . ':' . $e->getLine(),
                'error'     => $e->getMessage(),
                'user'      => $telegramUser,
            ]);
            return 'Telegram User';
        }
    }

    private function generateEmail(string $identifier): string
    {
        try {
            $cleanIdentifier = preg_replace('/[^a-z0-9]/i', '', $identifier);
            return ($cleanIdentifier ?: Str::random(7)) . '@dariche.site';
        } catch (\Exception $e) {
            Log::error("Error generating email for identifier: $identifier", [
                'File:Line' => $e->getFile() . ':' . $e->getLine(),
                'error'     => $e->getMessage(),
            ]);
            return Str::random(7) . '@dariche.site';
        }
    }
}
