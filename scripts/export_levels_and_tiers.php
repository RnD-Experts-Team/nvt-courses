<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/**
 * @return array<int, array<string, mixed>>
 */
function fetchLevels(): array
{
    return DB::table('user_levels')
        ->select(['id', 'code', 'name', 'hierarchy_level', 'can_manage_levels', 'created_at', 'updated_at'])
        ->orderBy('hierarchy_level')
        ->orderBy('id')
        ->get()
        ->map(static function (object $row): array {
            $item = (array) $row;

            if (isset($item['can_manage_levels']) && is_string($item['can_manage_levels']) && $item['can_manage_levels'] !== '') {
                $decoded = json_decode($item['can_manage_levels'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $item['can_manage_levels'] = $decoded;
                }
            }

            return $item;
        })
        ->all();
}

/**
 * @return array<int, array<string, mixed>>
 */
function fetchTiers(): array
{
    return DB::table('user_level_tiers')
        ->select(['id', 'user_level_id', 'tier_name', 'tier_order', 'created_at', 'updated_at'])
        ->orderBy('user_level_id')
        ->orderBy('tier_order')
        ->orderBy('id')
        ->get()
        ->map(static fn (object $row): array => (array) $row)
        ->all();
}

$levels = fetchLevels();
$tiers = fetchTiers();

$tiersByLevel = [];

foreach ($tiers as $tier) {
    $levelId = (int) $tier['user_level_id'];
    $tiersByLevel[$levelId] ??= [];
    $tiersByLevel[$levelId][] = $tier;
}

$levelsWithTiers = [];

foreach ($levels as $level) {
    $levelId = (int) $level['id'];
    $level['tiers'] = $tiersByLevel[$levelId] ?? [];
    $levelsWithTiers[] = $level;
}

$exportDir = storage_path('app/exports');
File::ensureDirectoryExists($exportDir);

$exportPath = $exportDir . '/levels_tiers_export.json';

$payload = [
    'generated_at' => now()->toIso8601String(),
    'source_database' => DB::connection()->getDatabaseName(),
    'total_levels' => count($levels),
    'total_tiers' => count($tiers),
    'levels' => $levels,
    'tiers' => $tiers,
    'levels_with_tiers' => $levelsWithTiers,
];

file_put_contents(
    $exportPath,
    json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

echo "Export completed: {$exportPath}" . PHP_EOL;
echo 'Levels: ' . count($levels) . PHP_EOL;
echo 'Tiers: ' . count($tiers) . PHP_EOL;
