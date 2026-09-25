<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OdooSyncService;

class SyncRentalStarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:sync-rental-starts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync latest vehicle rental start dates and KM (stock.move.line /OUT/) from Odoo to local mobil table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Odoo Rental Starts Sync...');

        $service = new OdooSyncService();
        $result = $service->syncRentalStarts('CLI');

        if (!$result['success']) {
            $this->error('Failed: ' . $result['message']);
            return 1;
        }

        $this->info($result['message']);
        $this->info("Total unique plates found in Odoo: " . ($result['total_unique_plates'] ?? 0));
        $this->info("Total vehicle rows updated: " . ($result['updated_vehicles'] ?? 0));

        return 0;
    }
}
