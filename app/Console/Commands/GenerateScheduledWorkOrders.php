<?php

namespace App\Console\Commands;

use App\Support\WorkOrderGenerator;
use App\Support\WorkOrderSettings;
use Illuminate\Console\Command;

class GenerateScheduledWorkOrders extends Command
{
    protected $signature = 'work-orders:generate-scheduled
        {--days= : Override jumlah hari ke depan (default dari Site Setting)}
        {--backfill= : Override jumlah hari backfill (default dari Site Setting)}
        {--force : Tetap jalankan meski toggle Work Order Otomatis nonaktif}';

    protected $description = 'Buat Work Order preventif otomatis sesuai jadwal (Equipment Type + interval checklist).';

    public function handle(WorkOrderGenerator $generator): int
    {
        if (! WorkOrderSettings::autoGenerate() && ! $this->option('force')) {
            $this->info('Work Order otomatis sedang nonaktif di Site Setting. Tidak ada WO dibuat.');

            return self::SUCCESS;
        }

        $leadDays = $this->option('days') !== null
            ? max(0, (int) $this->option('days'))
            : WorkOrderSettings::leadDays();

        $backfillDays = $this->option('backfill') !== null
            ? max(0, (int) $this->option('backfill'))
            : WorkOrderSettings::backfillDays();

        $result = $generator->generateScheduled(
            now()->subDays($backfillDays)->startOfDay(),
            now()->addDays($leadDays)->endOfDay(),
        );

        $this->info(sprintf(
            'Work Order otomatis: %d dibuat, %d periode sudah ada (%d tipe, %d equipment).',
            $result['created'],
            $result['existing'],
            $result['types'],
            $result['equipment'],
        ));

        return self::SUCCESS;
    }
}
