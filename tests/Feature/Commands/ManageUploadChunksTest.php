<?php

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManageUploadChunksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function testReportModeDoesNotDeleteFolders(): void
    {
        $this->createChunkTransfer('upload_old', 30);

        $this->artisan('uploads:chunks --older-hours=24')
            ->expectsOutputToContain('upload_old')
            ->expectsOutputToContain('Run with --delete to remove stale folders only.')
            ->assertExitCode(0);

        $this->assertTrue(Storage::disk('local')->exists('chunks/upload_old'));
    }

    public function testDeleteModeRemovesOnlyStaleFolders(): void
    {
        $this->createChunkTransfer('upload_old', 30);
        $this->createChunkTransfer('upload_recent', 1);

        $this->artisan('uploads:chunks --older-hours=24 --delete')
            ->expectsOutputToContain('Deleted: upload_old')
            ->assertExitCode(0);

        $this->assertFalse(Storage::disk('local')->exists('chunks/upload_old'));
        $this->assertTrue(Storage::disk('local')->exists('chunks/upload_recent'));
    }

    public function testCanTargetSpecificTransferId(): void
    {
        $this->createChunkTransfer('upload_old', 30);
        $this->createChunkTransfer('upload_recent', 1);

        $this->artisan('uploads:chunks --id=upload_old --older-hours=24 --delete')
            ->expectsOutputToContain('Deleted: upload_old')
            ->assertExitCode(0);

        $this->assertFalse(Storage::disk('local')->exists('chunks/upload_old'));
        $this->assertTrue(Storage::disk('local')->exists('chunks/upload_recent'));
    }

    private function createChunkTransfer(string $transferId, int $ageHours): void
    {
        $disk = Storage::disk('local');
        $directory = "chunks/{$transferId}";
        $timestamp = time() - ($ageHours * 3600);

        $disk->put("{$directory}/metadata.json", json_encode(['transfer_id' => $transferId]));
        $disk->put("{$directory}/chunk_0", str_repeat('a', 2048));
        $disk->put("{$directory}/chunk_2048", str_repeat('b', 1024));

        foreach (["{$directory}/metadata.json", "{$directory}/chunk_0", "{$directory}/chunk_2048"] as $file) {
            touch($disk->path($file), $timestamp);
        }
    }
}
