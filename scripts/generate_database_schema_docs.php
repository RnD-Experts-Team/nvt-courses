<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$database = DB::connection()->getDatabaseName();

function rowValue(object $row, string $key): mixed
{
    $values = get_object_vars($row);

    if (array_key_exists($key, $values)) {
        return $values[$key];
    }

    foreach ($values as $candidateKey => $candidateValue) {
        if (strcasecmp((string) $candidateKey, $key) === 0) {
            return $candidateValue;
        }
    }

    return null;
}

/**
 * @return array<int, string>
 */
function fetchTables(string $database): array
{
    $rows = DB::select(
        'SELECT table_name AS table_name
         FROM information_schema.tables
         WHERE table_schema = ? AND table_type = ?
         ORDER BY table_name',
        [$database, 'BASE TABLE']
    );

    return array_map(static fn (object $row): string => (string) rowValue($row, 'table_name'), $rows);
}

/**
 * @return array<string, array<int, array<string, mixed>>>
 */
function fetchColumns(string $database): array
{
    $rows = DB::select(
        'SELECT table_name AS table_name,
                column_name AS column_name,
                column_type AS column_type,
                is_nullable AS is_nullable,
                column_default AS column_default,
                column_key AS column_key,
                extra AS extra
         FROM information_schema.columns
         WHERE table_schema = ?
         ORDER BY table_name, ordinal_position',
        [$database]
    );

    $grouped = [];

    foreach ($rows as $row) {
        $tableName = (string) rowValue($row, 'table_name');
        $grouped[$tableName][] = [
            'name' => (string) rowValue($row, 'column_name'),
            'type' => (string) rowValue($row, 'column_type'),
            'nullable' => (string) rowValue($row, 'is_nullable') === 'YES',
            'default' => rowValue($row, 'column_default'),
            'key' => (string) rowValue($row, 'column_key'),
            'extra' => (string) rowValue($row, 'extra'),
        ];
    }

    return $grouped;
}

/**
 * @return array<string, array<int, array<string, string>>>
 */
function fetchForeignKeys(string $database): array
{
    $rows = DB::select(
        'SELECT table_name AS table_name,
                column_name AS column_name,
                referenced_table_name AS referenced_table_name,
                referenced_column_name AS referenced_column_name,
                constraint_name AS constraint_name
         FROM information_schema.key_column_usage
         WHERE table_schema = ? AND referenced_table_name IS NOT NULL
         ORDER BY table_name, column_name',
        [$database]
    );

    $grouped = [];

    foreach ($rows as $row) {
        $tableName = (string) rowValue($row, 'table_name');
        $grouped[$tableName][] = [
            'column' => (string) rowValue($row, 'column_name'),
            'referenced_table' => (string) rowValue($row, 'referenced_table_name'),
            'referenced_column' => (string) rowValue($row, 'referenced_column_name'),
            'constraint' => (string) rowValue($row, 'constraint_name'),
        ];
    }

    return $grouped;
}

/**
 * @return array<string, array<int, array<string, mixed>>>
 */
function fetchIndexes(string $database): array
{
    $rows = DB::select(
    'SELECT table_name AS table_name,
        index_name AS index_name,
        non_unique AS non_unique,
                GROUP_CONCAT(column_name ORDER BY seq_in_index SEPARATOR ", ") AS columns_list
         FROM information_schema.statistics
         WHERE table_schema = ?
         GROUP BY table_name, index_name, non_unique
         ORDER BY table_name, index_name',
        [$database]
    );

    $grouped = [];

    foreach ($rows as $row) {
        $tableName = (string) rowValue($row, 'table_name');
        $grouped[$tableName][] = [
            'name' => (string) rowValue($row, 'index_name'),
            'unique' => (int) rowValue($row, 'non_unique') === 0,
            'columns' => (string) rowValue($row, 'columns_list'),
        ];
    }

    return $grouped;
}

function detectSection(string $table): string
{
    return match (true) {
        in_array($table, ['users', 'departments', 'user_department_roles', 'user_levels', 'user_level_tiers'], true) => 'Identity and Organization',
        str_starts_with($table, 'course_') || in_array($table, ['courses', 'clockings', 'instructions'], true) => 'Classroom Courses',
        in_array($table, ['videos', 'video_bookmarks', 'video_qualities', 'audios', 'audio_categories', 'audio_progress', 'audio_assignments', 'content_categories', 'module_content', 'module_tasks', 'user_task_completions'], true) => 'Learning Content',
        in_array($table, ['content_progress', 'user_content_progress', 'learning_sessions', 'course_analytics', 'drive_key_tracker'], true) => 'Progress and Analytics',
        str_starts_with($table, 'quiz_') || in_array($table, ['quizzes', 'module_quiz_results', 'evaluations', 'evaluation_configs', 'evaluation_types', 'evaluation_histories', 'incentives', 'notification_templates'], true) => 'Assessment and Evaluation',
        in_array($table, ['podcast_posts', 'post_comments', 'post_likes', 'employee_feedback', 'bug_reports', 'activity_logs'], true) => 'Community and Support',
        default => 'Framework and Operations',
    };
}

function formatValue(mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    if ($value === '') {
        return "''";
    }

    $stringValue = (string) $value;
    $stringValue = str_replace(["\r", "\n"], [' ', ' '], $stringValue);

    return '`' . $stringValue . '`';
}

function formatKey(string $key): string
{
    return match ($key) {
        'PRI' => 'PK',
        'UNI' => 'UNIQUE',
        'MUL' => 'INDEX',
        default => '',
    };
}

function anchorForTable(string $table): string
{
    return '#table-' . str_replace('_', '-', $table);
}

function sectionFileName(string $sectionName): string
{
    $slug = strtolower($sectionName);
    $slug = str_replace(' and ', ' ', $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '_', $slug) ?? $slug;
    $slug = trim($slug, '_');

    return 'SECTION_' . strtoupper($slug) . '.md';
}

/**
 * @param array<string, array<int, array<string, mixed>>> $columnsByTable
 * @param array<string, array<int, array<string, string>>> $foreignKeysByTable
 * @param array<string, array<int, array<string, string>>> $incomingByTable
 * @param array<string, array<int, array<string, mixed>>> $indexesByTable
 */
function appendTableDocumentation(
    array &$output,
    string $table,
    array $columnsByTable,
    array $foreignKeysByTable,
    array $incomingByTable,
    array $indexesByTable
): void {
    $output[] = '### Table: ' . $table;
    $output[] = '<a id="table-' . str_replace('_', '-', $table) . '"></a>';
    $output[] = '';

    $columnRows = $columnsByTable[$table] ?? [];
    $foreignKeys = $foreignKeysByTable[$table] ?? [];
    $incoming = $incomingByTable[$table] ?? [];
    $indexes = $indexesByTable[$table] ?? [];
    $foreignKeyColumns = array_map(static fn (array $foreignKey): string => $foreignKey['column'], $foreignKeys);

    $output[] = 'Fields:';
    $output[] = '';
    $output[] = '| Field | Type | Nullable | Default | Key | Extra |';
    $output[] = '| --- | --- | --- | --- | --- | --- |';
    foreach ($columnRows as $column) {
        $output[] = sprintf(
            '| %s | %s | %s | %s | %s | %s |',
            '`' . $column['name'] . '`',
            '`' . $column['type'] . '`',
            $column['nullable'] ? 'YES' : 'NO',
            formatValue($column['default']),
            formatValue(formatKey((string) $column['key'])),
            formatValue((string) $column['extra'])
        );
    }
    $output[] = '';

    $output[] = 'Relationships:';
    if ($foreignKeys === [] && $incoming === []) {
        $output[] = '- No database foreign keys found.';
    } else {
        foreach ($foreignKeys as $foreignKey) {
            $output[] = '- Outbound: `' . $table . '.' . $foreignKey['column'] . '` -> `' . $foreignKey['referenced_table'] . '.' . $foreignKey['referenced_column'] . '`';
        }
        foreach ($incoming as $reference) {
            $output[] = '- Inbound: `' . $reference['source_table'] . '.' . $reference['source_column'] . '` -> `' . $table . '.' . $reference['referenced_column'] . '`';
        }
    }
    $output[] = '';

    $implicitRelations = [];
    foreach ($columnRows as $column) {
        $columnName = $column['name'];
        if ($columnName === 'id' || in_array($columnName, $foreignKeyColumns, true)) {
            continue;
        }

        if (str_ends_with($columnName, '_id')) {
            $implicitRelations[] = '`' . $columnName . '` looks like a relation column but has no foreign key constraint.';
        }

        if (str_ends_with($columnName, '_type')) {
            $baseName = substr($columnName, 0, -5);
            $pairedColumn = $baseName . '_id';
            $pairedExists = false;
            foreach ($columnRows as $candidateColumn) {
                if ($candidateColumn['name'] === $pairedColumn) {
                    $pairedExists = true;
                    break;
                }
            }
            if ($pairedExists) {
                $implicitRelations[] = '`' . $pairedColumn . '` + `' . $columnName . '` form a polymorphic relation pair.';
            }
        }
    }

    if ($implicitRelations !== []) {
        $output[] = 'Implicit relation hints:';
        foreach (array_unique($implicitRelations) as $hint) {
            $output[] = '- ' . $hint;
        }
        $output[] = '';
    }

    if ($indexes !== []) {
        $output[] = 'Indexes:';
        foreach ($indexes as $index) {
            $label = $index['name'] === 'PRIMARY' ? 'PRIMARY' : $index['name'];
            $uniqueness = $index['unique'] ? 'unique' : 'non-unique';
            $output[] = '- `' . $label . '` (' . $uniqueness . '): ' . $index['columns'];
        }
        $output[] = '';
    }
}

$tables = fetchTables($database);
$columnsByTable = fetchColumns($database);
$foreignKeysByTable = fetchForeignKeys($database);
$indexesByTable = fetchIndexes($database);

$incomingByTable = [];
foreach ($foreignKeysByTable as $sourceTable => $foreignKeys) {
    foreach ($foreignKeys as $foreignKey) {
        $incomingByTable[$foreignKey['referenced_table']][] = [
            'source_table' => $sourceTable,
            'source_column' => $foreignKey['column'],
            'referenced_column' => $foreignKey['referenced_column'],
        ];
    }
}

$sections = [];
foreach ($tables as $table) {
    $sections[detectSection($table)][] = $table;
}
ksort($sections);

$date = date('Y-m-d H:i:s');
$docsDir = __DIR__ . '/../docs/database';
if (!is_dir($docsDir)) {
    mkdir($docsDir, 0777, true);
}

$output = [];
$output[] = '# Database Schema Reference';
$output[] = '';
$output[] = '- Source: live MySQL schema from database `' . $database . '`';
$output[] = '- Generated at: ' . $date;
$output[] = '- Purpose: baseline reference for database restructuring';
$output[] = '';
$output[] = '## How To Use This Document';
$output[] = '';
$output[] = 'This file is grouped by domain so you can review the schema in chunks. Each table includes fields, database-enforced foreign keys, incoming references, and non-enforced relation hints that may matter during restructuring.';
$output[] = '';
$output[] = '## Section Files';
$output[] = '';
foreach ($sections as $sectionName => $sectionTables) {
    $output[] = '- [' . $sectionName . '](' . sectionFileName($sectionName) . ') (' . count($sectionTables) . ' tables)';
}
$output[] = '';
$output[] = '## Table Index';
$output[] = '';
foreach ($sections as $sectionName => $sectionTables) {
    $output[] = '- ' . $sectionName;
    foreach ($sectionTables as $table) {
        $output[] = '  - [' . $table . '](' . anchorForTable($table) . ')';
    }
}
$output[] = '';

foreach ($sections as $sectionName => $sectionTables) {
    $output[] = '## ' . $sectionName;
    $output[] = '';

    foreach ($sectionTables as $table) {
        appendTableDocumentation($output, $table, $columnsByTable, $foreignKeysByTable, $incomingByTable, $indexesByTable);
    }
}

$targetPath = $docsDir . '/SCHEMA_REFERENCE.md';
file_put_contents($targetPath, implode(PHP_EOL, $output) . PHP_EOL);

foreach ($sections as $sectionName => $sectionTables) {
    $sectionOutput = [];
    $sectionOutput[] = '# ' . $sectionName;
    $sectionOutput[] = '';
    $sectionOutput[] = '- Source: live MySQL schema from database `' . $database . '`';
    $sectionOutput[] = '- Generated at: ' . $date;
    $sectionOutput[] = '- Tables in this section: ' . count($sectionTables);
    $sectionOutput[] = '';
    $sectionOutput[] = '## Tables';
    $sectionOutput[] = '';
    foreach ($sectionTables as $table) {
        $sectionOutput[] = '- [' . $table . '](' . anchorForTable($table) . ')';
    }
    $sectionOutput[] = '';

    foreach ($sectionTables as $table) {
        appendTableDocumentation($sectionOutput, $table, $columnsByTable, $foreignKeysByTable, $incomingByTable, $indexesByTable);
    }

    file_put_contents($docsDir . '/' . sectionFileName($sectionName), implode(PHP_EOL, $sectionOutput) . PHP_EOL);
}

$readme = [];
$readme[] = '# Database Documentation';
$readme[] = '';
$readme[] = '- Source database: `' . $database . '`';
$readme[] = '- Generated at: ' . $date;
$readme[] = '';
$readme[] = '## Files';
$readme[] = '';
$readme[] = '- [Full schema reference](SCHEMA_REFERENCE.md)';
foreach ($sections as $sectionName => $sectionTables) {
    $readme[] = '- [' . $sectionName . ' (' . count($sectionTables) . ' tables)](' . sectionFileName($sectionName) . ')';
}
$readme[] = '';
$readme[] = '## Regenerate';
$readme[] = '';
$readme[] = '```bash';
$readme[] = 'php scripts/generate_database_schema_docs.php';
$readme[] = '```';
$readme[] = '';

file_put_contents($docsDir . '/README.md', implode(PHP_EOL, $readme) . PHP_EOL);

echo 'Generated ' . $targetPath . PHP_EOL;
