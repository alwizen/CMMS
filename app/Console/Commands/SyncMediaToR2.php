<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Salin media lama dari disk lokal (`public`) ke Cloudflare R2.
 *
 * Dipakai sekali saat pindah MEDIA_DISK dari `local` ke `r2`: path relatif
 * di database tetap sama, jadi cukup file-nya yang disalin.
 */
class SyncMediaToR2 extends Command
{
    protected $signature = 'media:sync-to-r2
        {--source=public : Disk sumber (default: public)}
        {--target=r2 : Disk tujuan (default: r2)}
        {--dry-run : Tampilkan rencana tanpa menyalin file}
        {--force : Timpa file yang sudah ada di tujuan}';

    protected $description = 'Salin media foto dari storage lokal ke Cloudflare R2 (skip file yang sudah ada).';

    public function handle(): int
    {
        $source = (string) $this->option('source');
        $target = (string) $this->option('target');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if (! config("filesystems.disks.{$source}")) {
            $this->error("Disk sumber [{$source}] tidak terdefinisi.");

            return self::FAILURE;
        }

        if (! config("filesystems.disks.{$target}")) {
            $this->error("Disk tujuan [{$target}] tidak terdefinisi. Periksa konfigurasi R2_* di .env.");

            return self::FAILURE;
        }

        if ($source === $target) {
            $this->error('Disk sumber dan tujuan tidak boleh sama.');

            return self::FAILURE;
        }

        $sourceDisk = Storage::disk($source);
        $targetDisk = Storage::disk($target);

        $files = $sourceDisk->allFiles();

        if ($files === []) {
            $this->info("Tidak ada file pada disk [{$source}].");

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d file dari disk [%s] ke [%s]%s',
            $dryRun ? 'Rencana salin' : 'Menyalin',
            count($files),
            $source,
            $target,
            $dryRun ? ' (dry-run, tidak ada perubahan)' : '',
        ));

        $copied = 0;
        $skipped = 0;
        $failed = 0;
        $bytes = 0;

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $path) {
            try {
                if (! $force && $targetDisk->exists($path)) {
                    $skipped++;
                    $bar->advance();

                    continue;
                }

                $size = $sourceDisk->size($path);

                if ($dryRun) {
                    $copied++;
                    $bytes += $size;
                    $bar->advance();

                    continue;
                }

                $stream = $sourceDisk->readStream($path);

                if ($stream === null) {
                    $failed++;
                    $bar->advance();

                    continue;
                }

                $targetDisk->writeStream($path, $stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }

                $copied++;
                $bytes += $size;
            } catch (Throwable $exception) {
                $failed++;
                $this->newLine();
                $this->error("Gagal: {$path} — {$exception->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Status', 'Jumlah'],
            [
                [$dryRun ? 'Akan disalin' : 'Disalin', $copied],
                ['Dilewati (sudah ada)', $skipped],
                ['Gagal', $failed],
                ['Total ukuran', $this->humanBytes($bytes)],
            ],
        );

        if ($failed > 0) {
            $this->warn('Sebagian file gagal. Jalankan ulang command ini; file yang sudah ada akan dilewati.');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->info('Dry-run selesai. Jalankan tanpa --dry-run untuk menyalin.');
        } else {
            $this->info('Sinkronisasi media ke R2 selesai.');
        }

        return self::SUCCESS;
    }

    protected function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return number_format($bytes / (1024 ** $power), 2).' '.$units[$power];
    }
}
