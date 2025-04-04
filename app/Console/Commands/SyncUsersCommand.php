<?php
namespace App\Console\Commands;

use App\Services\UserSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync users with Telegram and Other Panels every 6 hours';

    public function __construct(UserSyncService $userSyncService)
    {
        parent::__construct();
        $this->userSyncService = $userSyncService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            Log::channel('cron')->info('🔄 Starting user sync...');
            $this->userSyncService->syncXuiUsers();
            Log::channel('cron')->info('✅ User sync completed successfully.');
        } catch (\Exception $e) {
            Log::channel('cron')->error('❌ User sync failed: ' . $e->getMessage());
        }
    }
}
