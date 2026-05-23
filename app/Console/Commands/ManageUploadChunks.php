<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ManageUploadChunks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:chunks
                            {--delete : Delete stale chunk folders}
                            {--older-hours=24 : Stale threshold in hours}
                            {--id= : Process a specific transfer id only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Report or clean stale chunk upload folders under storage/app/chunks';

    public function handle(): int
    {
        $olderHours = max((int) $this->option('older-hours'), 0);
        $deleteMode = (bool) $this->option('delete');
        $targetId = trim((string) $this->option('id'));

        $disk = Storage::disk('local');
        $chunksRoot = 'chunks';

        if (!$disk->exists($chunksRoot)) {
            $this->info('No chunks directory found at storage/app/chunks.');
            return self::SUCCESS;
        }

        $directories = $targetId !== ''
            ? ["{$chunksRoot}/{$targetId}"]
            : $disk->directories($chunksRoot);

        if (empty($directories)) {
            $this->info('No chunk transfer folders found.');
            return self::SUCCESS;
        }

        $rows = [];
        $staleDirectories = [];
        $totalSizeBytes = 0;
        $staleSizeBytes = 0;

        foreach ($directories as $directory) {
            if (!$disk->exists($directory)) {
                continue;
            }

            $summary = $this->buildDirectorySummary($directory, $olderHours);
            $rows[] = [
                $summary['transfer_id'],
                $summary['age_hours'],
                $summary['chunk_count'],
                number_format($summary['size_mb'], 2),
                $summary['status'],
            ];

            $totalSizeBytes += $summary['size_bytes'];

            if ($summary['status'] === 'stale') {
                $staleDirectories[] = $directory;
                $staleSizeBytes += $summary['size_bytes'];
            }
        }

        if (empty($rows)) {
            $this->info('No matching chunk transfer folders found.');
            return self::SUCCESS;
        }

        $this->table(
            ['transfer_id', 'age_hours', 'chunk_count', 'size_mb', 'status'],
            $rows
        );

        $this->line('');
        $this->info('Summary:');
        $this->line('Total folders: ' . count($rows));
        $this->line('Stale folders: ' . count($staleDirectories));
        $this->line('Total size: ' . number_format($this->toMb($totalSizeBytes), 2) . ' MB');
        $this->line('Stale size: ' . number_format($this->toMb($staleSizeBytes), 2) . ' MB');

        if (!$deleteMode) {
            $this->line('Run with --delete to remove stale folders only.');
            return self::SUCCESS;
        }

        if (empty($staleDirectories)) {
            $this->info('Nothing to delete.');
            return self::SUCCESS;
        }

        foreach ($staleDirectories as $directory) {
            $disk->deleteDirectory($directory);
            $this->line('Deleted: ' . basename($directory));
        }

        $this->info('Cleanup complete.');

        return self::SUCCESS;
    }

    /**
     * @return array{transfer_id:string,age_hours:int,chunk_count:int,size_bytes:int,size_mb:float,status:string}
     */
    private function buildDirectorySummary(string $directory, int $olderHours): array
    {
        $disk = Storage::disk('local');
        $files = $disk->allFiles($directory);

        $sizeBytes = 0;
        $chunkCount = 0;
        $latestModified = 0;

        foreach ($files as $file) {
            $sizeBytes += $disk->size($file);

            if (basename($file) !== 'metadata.json') {
                $chunkCount++;
            }

            $modified = $disk->lastModified($file);
            if ($modified > $latestModified) {
                $latestModified = $modified;
            }
        }

        if ($latestModified === 0) {
            $latestModified = time();
        }

        $ageHours = Carbon::createFromTimestamp($latestModified)->diffInHours(now());

        return [
            'transfer_id' => basename($directory),
            'age_hours' => $ageHours,
            'chunk_count' => $chunkCount,
            'size_bytes' => $sizeBytes,
            'size_mb' => $this->toMb($sizeBytes),
            'status' => $ageHours >= $olderHours ? 'stale' : 'active_recent',
        ];
    }

    private function toMb(int $bytes): float
    {
        return $bytes / 1024 / 1024;
    }
}
