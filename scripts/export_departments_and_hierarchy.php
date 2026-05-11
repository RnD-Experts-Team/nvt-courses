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
function fetchDepartments(): array
{
    $schema = DB::getSchemaBuilder();

    $columns = ['id', 'name', 'parent_id'];

    foreach (['slug', 'sort_order', 'created_at', 'updated_at', 'deleted_at'] as $optionalColumn) {
        if ($schema->hasColumn('departments', $optionalColumn)) {
            $columns[] = $optionalColumn;
        }
    }

    $query = DB::table('departments')->select($columns);

    if ($schema->hasColumn('departments', 'sort_order')) {
        $query->orderBy('sort_order');
    }

    return $query
        ->orderBy('id')
        ->get()
        ->map(static fn (object $row): array => (array) $row)
        ->all();
}

/**
 * @param array<int, array<string, mixed>> $departments
 * @return array<int, array<string, mixed>>
 */
function buildDepartmentTree(array $departments): array
{
    $byId = [];

    foreach ($departments as $department) {
        $department['children'] = [];
        $byId[(int) $department['id']] = $department;
    }

    $roots = [];

    foreach ($byId as $id => $department) {
        $parentId = $department['parent_id'];

        if ($parentId === null || !isset($byId[(int) $parentId])) {
            $roots[] = $id;
            continue;
        }

        $byId[(int) $parentId]['children'][] = $id;
    }

    $resolver = static function (int $id, array $store, callable $resolver) {
        $node = $store[$id];
        $childIds = $node['children'];
        $node['children'] = [];

        foreach ($childIds as $childId) {
            $node['children'][] = $resolver((int) $childId, $store, $resolver);
        }

        return $node;
    };

    $tree = [];

    foreach ($roots as $rootId) {
        $tree[] = $resolver((int) $rootId, $byId, $resolver);
    }

    return $tree;
}

$departments = fetchDepartments();
$tree = buildDepartmentTree($departments);

$exportDir = storage_path('app/exports');
File::ensureDirectoryExists($exportDir);

$exportPath = $exportDir . '/departments_hierarchy_export.json';

$payload = [
    'generated_at' => now()->toIso8601String(),
    'source_database' => DB::connection()->getDatabaseName(),
    'total_departments' => count($departments),
    'root_departments' => count($tree),
    'departments_flat' => $departments,
    'departments_tree' => $tree,
];

file_put_contents(
    $exportPath,
    json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

echo "Export completed: {$exportPath}" . PHP_EOL;
echo 'Departments: ' . count($departments) . PHP_EOL;
echo 'Root nodes: ' . count($tree) . PHP_EOL;
