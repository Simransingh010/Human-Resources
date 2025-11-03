<?php

namespace App\Console\Commands;

use App\Services\EsslMonthlySyncService;
use Illuminate\Console\Command;

class EsslMonthlySync extends Command
{
    protected $signature = 'attendance:essl-monthly-sync {--firm=29} {--limit=10000} {--table=} {--month=} {--year=}';
    protected $description = 'Sync ESSL monthly device logs (DeviceLogs_M_Y) into HRMS and track statuses in iq_sync_track';

    public function handle(EsslMonthlySyncService $service)
    {
        $firmId = (int) $this->option('firm');
        $limit = (int) $this->option('limit');
        $tableOpt = (string) ($this->option('table') ?? '');
        $monthOpt = (string) ($this->option('month') ?? '');
        $yearOpt = (string) ($this->option('year') ?? '');

        $deviceLogsTableOverride = null;
        if ($tableOpt !== '') {
            $deviceLogsTableOverride = $tableOpt;
        } elseif ($monthOpt !== '' && $yearOpt !== '') {
            $deviceLogsTableOverride = "DeviceLogs_{$monthOpt}_{$yearOpt}";
        }

        $this->info("Starting ESSL monthly sync for firm {$firmId} (limit {$limit})" . ($deviceLogsTableOverride ? ", table {$deviceLogsTableOverride}" : '') . "...");
        $service->syncCurrentMonthForFirm($firmId, $limit, $deviceLogsTableOverride);
        $this->info('ESSL monthly sync completed.');
    }
}


