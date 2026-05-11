<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\File;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/**
 * @return array<int, array<string, mixed>>
 */
function collectRoutes(): array
{
    $router = app('router');
    $routes = $router->getRoutes()->getRoutes();

    $items = [];

    foreach ($routes as $route) {
        if (!$route instanceof Route) {
            continue;
        }

        $methods = array_values(array_filter(
            $route->methods(),
            static fn (string $method): bool => $method !== 'HEAD'
        ));

        if ($methods === []) {
            $methods = $route->methods();
        }

        $actionName = $route->getActionName();
        $action = $actionName === 'Closure' ? 'Closure' : $actionName;

        $items[] = [
            'domain' => $route->domain(),
            'methods' => $methods,
            'uri' => $route->uri(),
            'name' => $route->getName() ?? '',
            'action' => $action,
            'middleware' => $route->gatherMiddleware(),
            'section' => detectSection($route),
            'response_hint' => detectResponseHint($action, $route->uri()),
        ];
    }

    usort(
        $items,
        static function (array $a, array $b): int {
            $uriCompare = strcmp((string) $a['uri'], (string) $b['uri']);
            if ($uriCompare !== 0) {
                return $uriCompare;
            }

            return strcmp(implode('|', $a['methods']), implode('|', $b['methods']));
        }
    );

    return $items;
}

function detectSection(Route $route): string
{
    $uri = $route->uri();
    $name = $route->getName() ?? '';

    if (str_starts_with($uri, 'admin/')) {
        return 'Admin Panel';
    }

    if (str_starts_with($uri, 'api/')) {
        if (str_contains($uri, 'callback')) {
            return 'Webhooks and Integrations';
        }

        return 'API Endpoints';
    }

    if (str_starts_with($uri, 'manager/')) {
        return 'Manager Area';
    }

    if (
        str_starts_with($uri, 'login') ||
        str_starts_with($uri, 'register') ||
        str_starts_with($uri, 'forgot-password') ||
        str_starts_with($uri, 'reset-password') ||
        str_starts_with($uri, 'verify-email') ||
        $name === 'logout' ||
        str_starts_with($name, 'password.') ||
        str_starts_with($name, 'verification.')
    ) {
        return 'Authentication and Access';
    }

    if (
        $uri === 'up' ||
        str_starts_with($uri, 'docs') ||
        str_starts_with($uri, 'storage/') ||
        str_contains($uri, 'debug/')
    ) {
        return 'System and Utilities';
    }

    return 'User Experience and Learning';
}

function detectResponseHint(string $action, string $uri): string
{
    if (str_starts_with($uri, 'api/')) {
        return 'JSON/API response';
    }

    if ($action === 'Closure') {
        return 'Closure response';
    }

    if (str_contains($action, '@')) {
        [$controller, $method] = explode('@', $action, 2);
        $controllerFile = controllerToFilePath($controller);

        if ($controllerFile !== null && is_file($controllerFile)) {
            $content = file_get_contents($controllerFile);
            if ($content !== false && methodContainsInertia($content, $method)) {
                return 'Inertia page/action';
            }
        }
    }

    if (str_contains($action, 'Api\\') || str_contains($action, 'ProgressController')) {
        return 'JSON/API response';
    }

    return 'Controller response';
}

function controllerToFilePath(string $controller): ?string
{
    $prefix = 'App\\Http\\Controllers\\';
    if (!str_starts_with($controller, $prefix)) {
        return null;
    }

    $relative = substr($controller, strlen($prefix));
    if ($relative === false || $relative === '') {
        return null;
    }

    return base_path('app/Http/Controllers/' . str_replace('\\', '/', $relative) . '.php');
}

function methodContainsInertia(string $fileContent, string $method): bool
{
    $needle = 'function ' . $method;
    $position = strpos($fileContent, $needle);
    if ($position === false) {
        return strpos($fileContent, 'Inertia::render(') !== false;
    }

    $slice = substr($fileContent, $position, 2500);
    if ($slice === false) {
        return false;
    }

    return strpos($slice, 'Inertia::render(') !== false;
}

function shortAction(string $action): string
{
    return str_replace('App\\Http\\Controllers\\', '', $action);
}

/**
 * @param array<int, array<string, mixed>> $routes
 * @return array<string, array<int, array<string, mixed>>>
 */
function groupBySection(array $routes): array
{
    $grouped = [];

    foreach ($routes as $route) {
        $grouped[$route['section']][] = $route;
    }

    ksort($grouped);

    return $grouped;
}

/**
 * @param array<int, array<string, mixed>> $routes
 * @return array<int, array<string, string>>
 */
function analyzeIssues(array $routes): array
{
    $issues = [];

    $nameCount = [];
    $signatureCount = [];

    foreach ($routes as $route) {
        $name = (string) $route['name'];
        if ($name !== '') {
            $nameCount[$name] = ($nameCount[$name] ?? 0) + 1;
        }

        foreach ($route['methods'] as $method) {
            $signature = $method . ' ' . $route['uri'];
            $signatureCount[$signature] = ($signatureCount[$signature] ?? 0) + 1;
        }
    }

    foreach ($routes as $route) {
        $name = (string) $route['name'];
        $methods = implode('|', $route['methods']);
        $uri = (string) $route['uri'];
        $middleware = $route['middleware'];

        if ($name === '') {
            $issues[] = [
                'severity' => 'Medium',
                'route' => $methods . ' ' . $uri,
                'reason' => 'Route has no name; harder to refactor and reference safely.',
            ];
        }

        if ($name !== '' && ($nameCount[$name] ?? 0) > 1) {
            $issues[] = [
                'severity' => 'High',
                'route' => $methods . ' ' . $uri,
                'reason' => 'Duplicate route name `' . $name . '` can break URL generation and tests.',
            ];
        }

        foreach ($route['methods'] as $method) {
            $signature = $method . ' ' . $uri;
            if (($signatureCount[$signature] ?? 0) > 1) {
                $issues[] = [
                    'severity' => 'High',
                    'route' => $signature,
                    'reason' => 'Duplicate method+URI registration; last definition wins and may hide earlier route.',
                ];
            }
        }

        if (str_starts_with($uri, 'api/') && in_array('web', $middleware, true)) {
            $issues[] = [
                'severity' => 'Medium',
                'route' => $methods . ' ' . $uri,
                'reason' => 'API path runs through web middleware stack; consider dedicated API middleware for stateless endpoints.',
            ];
        }

        if (
            in_array('POST', $route['methods'], true) &&
            (str_contains($uri, '/update') || str_ends_with($name, '.update'))
        ) {
            $issues[] = [
                'severity' => 'Low',
                'route' => $methods . ' ' . $uri,
                'reason' => 'Update-like operation uses POST; consider PUT/PATCH for consistency.',
            ];
        }

        if (str_contains($uri, 'debug/')) {
            $issues[] = [
                'severity' => 'Medium',
                'route' => $methods . ' ' . $uri,
                'reason' => 'Debug endpoint is exposed in routes.',
            ];
        }
    }

    $unique = [];
    foreach ($issues as $issue) {
        $key = $issue['severity'] . '|' . $issue['route'] . '|' . $issue['reason'];
        $unique[$key] = $issue;
    }

    $issues = array_values($unique);

    usort($issues, static function (array $a, array $b): int {
        $rank = ['High' => 1, 'Medium' => 2, 'Low' => 3];
        $severityCompare = ($rank[$a['severity']] ?? 99) <=> ($rank[$b['severity']] ?? 99);
        if ($severityCompare !== 0) {
            return $severityCompare;
        }

        return strcmp($a['route'], $b['route']);
    });

    return $issues;
}

/**
 * @param array<int, string> $lines
 */
function writeMarkdown(string $path, array $lines): void
{
    file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);
}

/**
 * @param array<int, array<string, mixed>> $routes
 */
function appendRouteTable(array &$lines, array $routes): void
{
    $lines[] = '| Method | URI | Name | Action | Middleware | Response hint |';
    $lines[] = '| --- | --- | --- | --- | --- | --- |';

    foreach ($routes as $route) {
        $lines[] = sprintf(
            '| %s | `%s` | `%s` | `%s` | `%s` | %s |',
            implode('|', $route['methods']),
            $route['uri'],
            $route['name'] !== '' ? $route['name'] : '(unnamed)',
            shortAction((string) $route['action']),
            implode(', ', $route['middleware']),
            $route['response_hint']
        );
    }

    $lines[] = '';
}

$routes = collectRoutes();
$grouped = groupBySection($routes);
$issues = analyzeIssues($routes);

$docsDir = base_path('docs/routes');
if (!is_dir($docsDir)) {
    mkdir($docsDir, 0777, true);
}

$now = date('Y-m-d H:i:s');

$reference = [];
$reference[] = '# Routes Reference';
$reference[] = '';
$reference[] = '- Source: live Laravel route collection';
$reference[] = '- Generated at: ' . $now;
$reference[] = '- Total routes: ' . count($routes);
$reference[] = '';
$reference[] = '## Sections';
$reference[] = '';

foreach ($grouped as $sectionName => $sectionRoutes) {
    $fileName = 'SECTION_' . strtoupper(str_replace(' ', '_', str_replace('&', 'AND', $sectionName))) . '.md';
    $reference[] = '- [' . $sectionName . ' (' . count($sectionRoutes) . ' routes)](' . $fileName . ')';
}

$reference[] = '';
$reference[] = '## Quick Risk Signals';
$reference[] = '';
$reference[] = '- See [ROUTE_QUALITY_REPORT.md](ROUTE_QUALITY_REPORT.md) for duplicate routes, unnamed routes, and API/web middleware concerns.';
$reference[] = '- See [REQUEST_LIFECYCLE.md](REQUEST_LIFECYCLE.md) to track request flow and middleware pipeline.';
$reference[] = '';

foreach ($grouped as $sectionName => $sectionRoutes) {
    $reference[] = '## ' . $sectionName;
    $reference[] = '';
    appendRouteTable($reference, $sectionRoutes);
}

writeMarkdown($docsDir . '/ROUTES_REFERENCE.md', $reference);

foreach ($grouped as $sectionName => $sectionRoutes) {
    $fileName = 'SECTION_' . strtoupper(str_replace(' ', '_', str_replace('&', 'AND', $sectionName))) . '.md';
    $sectionDoc = [];
    $sectionDoc[] = '# ' . $sectionName;
    $sectionDoc[] = '';
    $sectionDoc[] = '- Generated at: ' . $now;
    $sectionDoc[] = '- Routes in this section: ' . count($sectionRoutes);
    $sectionDoc[] = '';
    appendRouteTable($sectionDoc, $sectionRoutes);
    writeMarkdown($docsDir . '/' . $fileName, $sectionDoc);
}

$quality = [];
$quality[] = '# Route Quality Report';
$quality[] = '';
$quality[] = '- Generated at: ' . $now;
$quality[] = '- Routes analyzed: ' . count($routes);
$quality[] = '- Findings: ' . count($issues);
$quality[] = '';
$quality[] = '## Findings';
$quality[] = '';
$quality[] = '| Severity | Route | Why it matters |';
$quality[] = '| --- | --- | --- |';

foreach ($issues as $issue) {
    $quality[] = '| ' . $issue['severity'] . ' | `' . $issue['route'] . '` | ' . $issue['reason'] . ' |';
}

if ($issues === []) {
    $quality[] = '| Info | n/a | No issues detected by current static checks. |';
}

$quality[] = '';
$quality[] = '## Suggested Direction For API Migration';
$quality[] = '';
$quality[] = '1. Move API endpoints to `routes/api.php` with API middleware and versioning (`/api/v1`).';
$quality[] = '2. Keep Inertia page routes in web routes and avoid mixing page and API responsibilities in the same controller where possible.';
$quality[] = '3. Standardize mutation verbs (`PUT`/`PATCH` for update) before or during migration.';
$quality[] = '4. Remove or gate debug routes outside local/test environments.';
$quality[] = '';

writeMarkdown($docsDir . '/ROUTE_QUALITY_REPORT.md', $quality);

$lifecycle = [];
$lifecycle[] = '# Request Lifecycle Map';
$lifecycle[] = '';
$lifecycle[] = '- Generated at: ' . $now;
$lifecycle[] = '- Goal: track how requests travel through middleware to controllers/Inertia/API responses.';
$lifecycle[] = '';
$lifecycle[] = '## Global Routing Setup';
$lifecycle[] = '';
$lifecycle[] = '- All current app routes are loaded through `routes/web.php` via `bootstrap/app.php`.';
$lifecycle[] = '- Web middleware stack appends:';
$lifecycle[] = '  - `App\\Http\\Middleware\\HandleAppearance`';
$lifecycle[] = '  - `App\\Http\\Middleware\\HandleInertiaRequests`';
$lifecycle[] = '  - `Illuminate\\Http\\Middleware\\AddLinkHeadersForPreloadedAssets`';
$lifecycle[] = '  - `App\\Http\\Middleware\\LogUserActivity`';
$lifecycle[] = '- Custom middleware alias:';
$lifecycle[] = '  - `admin` -> `App\\Http\\Middleware\\AdminMiddleware`';
$lifecycle[] = '';
$lifecycle[] = '## CSRF Exceptions';
$lifecycle[] = '';
$lifecycle[] = '- `content/*/session`';
$lifecycle[] = '- `api/transcode/callback`';
$lifecycle[] = '- `api/subtitle/callback`';
$lifecycle[] = '';
$lifecycle[] = '## Request Flow Patterns';
$lifecycle[] = '';
$lifecycle[] = '1. Inertia page flow';
$lifecycle[] = '   - Browser request -> web middleware -> auth/admin middleware (if applied) -> controller action -> `Inertia::render(...)` -> SPA page payload.';
$lifecycle[] = '2. Authenticated API-like flow (currently under web routes)';
$lifecycle[] = '   - Client request to `/api/...` -> web middleware -> auth/admin middleware -> controller JSON response.';
$lifecycle[] = '3. External callback flow';
$lifecycle[] = '   - VPS webhook request -> web middleware (no auth) -> callback controller -> status response.';
$lifecycle[] = '';
$lifecycle[] = '## Current Route Distribution';
$lifecycle[] = '';

foreach ($grouped as $sectionName => $sectionRoutes) {
    $lifecycle[] = '- ' . $sectionName . ': ' . count($sectionRoutes) . ' routes';
}

$lifecycle[] = '';
$lifecycle[] = '## Tracking Tip';
$lifecycle[] = '';
$lifecycle[] = '- Use route name + middleware + response hint in section files to map each endpoint when you split to dedicated API controllers.';
$lifecycle[] = '';

writeMarkdown($docsDir . '/REQUEST_LIFECYCLE.md', $lifecycle);

$readme = [];
$readme[] = '# Route Documentation';
$readme[] = '';
$readme[] = '- Generated at: ' . $now;
$readme[] = '';
$readme[] = '## Files';
$readme[] = '';
$readme[] = '- [Full route reference](ROUTES_REFERENCE.md)';
$readme[] = '- [Request lifecycle map](REQUEST_LIFECYCLE.md)';
$readme[] = '- [Route quality report](ROUTE_QUALITY_REPORT.md)';

foreach ($grouped as $sectionName => $sectionRoutes) {
    $fileName = 'SECTION_' . strtoupper(str_replace(' ', '_', str_replace('&', 'AND', $sectionName))) . '.md';
    $readme[] = '- [' . $sectionName . ' (' . count($sectionRoutes) . ' routes)](' . $fileName . ')';
}

$readme[] = '';
$readme[] = '## Regenerate';
$readme[] = '';
$readme[] = '```bash';
$readme[] = 'php scripts/generate_route_docs.php';
$readme[] = '```';
$readme[] = '';

writeMarkdown($docsDir . '/README.md', $readme);

echo 'Generated route docs in ' . $docsDir . PHP_EOL;
