<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;

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
        ];
    }

    usort(
        $items,
        static function (array $a, array $b): int {
            $sectionCompare = strcmp((string) $a['section'], (string) $b['section']);
            if ($sectionCompare !== 0) {
                return $sectionCompare;
            }

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

function shortClass(string $class): string
{
    $parts = explode('\\', $class);

    return end($parts) ?: $class;
}

function shortAction(string $action): string
{
    return str_replace('App\\Http\\Controllers\\', '', $action);
}

/**
 * @return array<string, string>
 */
function parseUseMap(string $content): array
{
    $map = [];
    if (preg_match_all('/^use\\s+([^;]+);/m', $content, $matches)) {
        foreach ($matches[1] as $useLine) {
            $fqcn = trim($useLine);
            $alias = shortClass($fqcn);
            $map[$alias] = $fqcn;
        }
    }

    return $map;
}

/**
 * @return array{signature: string, body: string}|null
 */
function extractMethodSignatureAndBody(string $content, string $method): ?array
{
    $pattern = '/function\\s+' . preg_quote($method, '/') . '\\s*\\((.*?)\\)\\s*(?::\\s*[^\\{]+)?\\s*\\{/s';
    if (!preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        return null;
    }

    $fullMatch = $matches[0][0];
    $start = $matches[0][1];
    $signatureParams = $matches[1][0];

    $bracePos = strpos($fullMatch, '{');
    if ($bracePos === false) {
        return null;
    }

    $bodyStart = $start + $bracePos + 1;
    $depth = 1;
    $i = $bodyStart;
    $len = strlen($content);

    while ($i < $len && $depth > 0) {
        $ch = $content[$i];
        if ($ch === '{') {
            $depth++;
        } elseif ($ch === '}') {
            $depth--;
        }
        $i++;
    }

    if ($depth !== 0) {
        return null;
    }

    $body = substr($content, $bodyStart, $i - $bodyStart - 1);

    return [
        'signature' => $signatureParams,
        'body' => $body === false ? '' : $body,
    ];
}

/**
 * @param array<string, string> $useMap
 * @return array<string, string>
 */
function resolveParameterTypes(string $signature, array $useMap): array
{
    $vars = [];

    if (preg_match_all('/([A-Za-z_\\\\][A-Za-z0-9_\\\\]*)\\s+\\$([A-Za-z_][A-Za-z0-9_]*)/', $signature, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $rawType = $match[1];
            $varName = $match[2];

            if (str_contains($rawType, '\\')) {
                $fqcn = ltrim($rawType, '\\');
            } else {
                $fqcn = $useMap[$rawType] ?? $rawType;
            }

            $vars[$varName] = $fqcn;
        }
    }

    return $vars;
}

/**
 * @param array<string, string> $useMap
 * @return array<int, string>
 */
function collectFormRequests(string $signature, array $useMap): array
{
    $requests = [];

    foreach ($useMap as $alias => $fqcn) {
        if (!str_starts_with($fqcn, 'App\\Http\\Requests\\')) {
            continue;
        }

        if (preg_match('/\\b' . preg_quote($alias, '/') . '\\s+\\$[A-Za-z_]/', $signature)) {
            $requests[] = $alias;
        }
    }

    sort($requests);

    return $requests;
}

/**
 * @param array<string, string> $useMap
 * @param array<string, string> $paramTypes
 * @return array{models: array<int, array{name: string, table: string, reads: bool, writes: bool}>, services: array<int, string>, events: array<int, string>, jobs: array<int, string>, notifications: array<int, string>}
 */
function analyzeDependencies(string $body, array $useMap, array $paramTypes): array
{
    $models = [];
    $services = [];
    $events = [];
    $jobs = [];
    $notifications = [];

    foreach ($useMap as $alias => $fqcn) {
        if (str_starts_with($fqcn, 'App\\Models\\')) {
            $mentioned =
                preg_match('/\\b' . preg_quote($alias, '/') . '::\\b/', $body) === 1 ||
                preg_match('/new\\s+' . preg_quote($alias, '/') . '\\b/', $body) === 1;

            $reads = preg_match('/\\b' . preg_quote($alias, '/') . '::(find|first|where|with|get|paginate|query|all|pluck|count)\\b/', $body) === 1;
            $writes = preg_match('/\\b' . preg_quote($alias, '/') . '::(create|update|delete|destroy|insert|upsert|truncate)\\b/', $body) === 1;

            if (!$mentioned && !$reads && !$writes) {
                continue;
            }

            $table = inferModelTable($fqcn);
            $models[$fqcn] = [
                'name' => $alias,
                'table' => $table,
                'reads' => $reads,
                'writes' => $writes,
            ];
            continue;
        }

        if (str_starts_with($fqcn, 'App\\Services\\') || str_ends_with($alias, 'Service')) {
            $propertyName = lcfirst($alias);
            if (
                preg_match('/\\b' . preg_quote($alias, '/') . '\\b/', $body) === 1 ||
                preg_match('/\\$this->' . preg_quote($propertyName, '/') . '\\b/', $body) === 1
            ) {
                $services[$alias] = $alias;
            }
            continue;
        }

        if (str_starts_with($fqcn, 'App\\Events\\')) {
            if (
                preg_match('/new\\s+' . preg_quote($alias, '/') . '\\b/', $body) === 1 ||
                preg_match('/\\b' . preg_quote($alias, '/') . '::dispatch\\s*\\(/', $body) === 1
            ) {
                $events[$alias] = $alias;
            }
            continue;
        }

        if (str_starts_with($fqcn, 'App\\Jobs\\')) {
            if (
                preg_match('/\\b' . preg_quote($alias, '/') . '::dispatch\\s*\\(/', $body) === 1 ||
                preg_match('/new\\s+' . preg_quote($alias, '/') . '\\b/', $body) === 1
            ) {
                $jobs[$alias] = $alias;
            }
            continue;
        }

        if (str_starts_with($fqcn, 'App\\Notifications\\')) {
            if (
                preg_match('/new\\s+' . preg_quote($alias, '/') . '\\b/', $body) === 1 ||
                preg_match('/->notify\\s*\\(/', $body) === 1
            ) {
                $notifications[$alias] = $alias;
            }
        }
    }

    foreach ($paramTypes as $var => $fqcn) {
        if (!str_starts_with($fqcn, 'App\\Models\\')) {
            continue;
        }

        $alias = shortClass($fqcn);
        $existing = $models[$fqcn] ?? [
            'name' => $alias,
            'table' => inferModelTable($fqcn),
            'reads' => false,
            'writes' => false,
        ];

        if (preg_match('/\\$' . preg_quote($var, '/') . '->(load|refresh|relationLoaded|get|toArray|jsonSerialize)\\b/', $body) === 1) {
            $existing['reads'] = true;
        }

        if (preg_match('/\\$' . preg_quote($var, '/') . '->(update|delete|save|forceDelete|restore|increment|decrement)\\b/', $body) === 1) {
            $existing['writes'] = true;
        }

        if ($existing['reads'] === false && $existing['writes'] === false) {
            $existing['reads'] = true;
        }

        $models[$fqcn] = $existing;
    }

    $modelList = array_values($models);
    usort($modelList, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

    sort($services);
    sort($events);
    sort($jobs);
    sort($notifications);

    return [
        'models' => $modelList,
        'services' => array_values($services),
        'events' => array_values($events),
        'jobs' => array_values($jobs),
        'notifications' => array_values($notifications),
    ];
}

function inferModelTable(string $modelClass): string
{
    if (!class_exists($modelClass)) {
        return '(unknown)';
    }

    try {
        $instance = new $modelClass();
        if ($instance instanceof Model) {
            return $instance->getTable();
        }
    } catch (Throwable $e) {
        return '(unknown)';
    }

    return '(unknown)';
}

/**
 * @return array<int, string>
 */
function detectValidationKeys(string $body): array
{
    $keys = [];

    if (preg_match_all('/->validate\\s*\\(\\s*\\[(.*?)\\]\\s*\\)/s', $body, $matches)) {
        foreach ($matches[1] as $inlineRules) {
            if (preg_match_all('/[\'\"]([A-Za-z0-9_\\.\-]+)[\'\"]\\s*=>/', $inlineRules, $ruleKeys)) {
                foreach ($ruleKeys[1] as $key) {
                    $keys[$key] = $key;
                }
            }
        }
    }

    return array_values($keys);
}

/**
 * @return array<int, string>
 */
function inferStepByStep(string $body, array $formRequests, array $validationKeys, array $models): array
{
    $steps = [];
    $steps[] = 'Request reaches controller action.';

    if ($formRequests !== []) {
        $steps[] = 'Validates input via FormRequest: ' . implode(', ', $formRequests) . '.';
    } elseif ($validationKeys !== []) {
        $steps[] = 'Validates input in controller for: ' . implode(', ', array_slice($validationKeys, 0, 8)) . (count($validationKeys) > 8 ? ', ...' : '') . '.';
    }

    if (preg_match('/Hash::make\\s*\\(/', $body) === 1) {
        $steps[] = 'Transforms sensitive values (password hashing).';
    }

    $writeModels = [];
    foreach ($models as $model) {
        if ($model['writes']) {
            $writeModels[] = $model['name'];
        }
    }

    if ($writeModels !== []) {
        $steps[] = 'Writes data using model(s): ' . implode(', ', $writeModels) . '.';
    }

    if (preg_match('/Inertia::render\\s*\\(/', $body) === 1) {
        $steps[] = 'Builds page props and returns Inertia view.';
    } elseif (preg_match('/response\\s*\\(\\)\\s*->json\\s*\\(/', $body) === 1) {
        $steps[] = 'Returns JSON payload.';
    } elseif (preg_match('/redirect\\s*\\(\\)\\s*->/', $body) === 1 || preg_match('/return\\s+back\\s*\\(/', $body) === 1) {
        $steps[] = 'Returns redirect response with flash/errors.';
    } else {
        $steps[] = 'Returns controller response.';
    }

    return $steps;
}

function inferResponseStyle(string $body, string $uri): string
{
    if (preg_match('/Inertia::render\\s*\\(/', $body) === 1) {
        return 'Inertia page render';
    }

    if (preg_match('/response\\s*\\(\\)\\s*->json\\s*\\(/', $body) === 1 || str_starts_with($uri, 'api/')) {
        return 'JSON/API response';
    }

    if (preg_match('/redirect\\s*\\(\\)\\s*->/', $body) === 1 || preg_match('/return\\s+back\\s*\\(/', $body) === 1) {
        return 'Redirect response';
    }

    if (preg_match('/return\\s+view\\s*\\(/', $body) === 1) {
        return 'Blade view response';
    }

    return 'Controller response';
}

/**
 * @param array<int, array{name: string, table: string, reads: bool, writes: bool}> $models
 * @return array{reads: array<int, string>, writes: array<int, string>}
 */
function inferDatabaseImpact(array $models): array
{
    $reads = [];
    $writes = [];

    foreach ($models as $model) {
        $label = $model['table'] !== '(unknown)'
            ? $model['table'] . ' (' . $model['name'] . ')'
            : $model['name'];

        if ($model['reads']) {
            $reads[] = $label;
        }

        if ($model['writes']) {
            $writes[] = $label;
        }
    }

    sort($reads);
    sort($writes);

    return ['reads' => $reads, 'writes' => $writes];
}

/**
 * @return array<string, mixed>
 */
function analyzeRouteLifecycle(array $route): array
{
    $action = (string) $route['action'];

    if ($action === 'Closure') {
        return [
            'controller' => 'Closure',
            'method' => '(closure)',
            'response_style' => str_starts_with((string) $route['uri'], 'api/') ? 'JSON/API response' : 'Closure response',
            'form_requests' => [],
            'validation_keys' => [],
            'models' => [],
            'services' => [],
            'events' => [],
            'jobs' => [],
            'notifications' => [],
            'steps' => [
                'Request reaches route closure.',
                'Closure handles logic directly.',
                'Closure returns response.',
            ],
            'database' => ['reads' => [], 'writes' => []],
            'notes' => ['Closure route: consider moving to controller for easier long-term maintenance.'],
        ];
    }

    if (!str_contains($action, '@')) {
        return [
            'controller' => $action,
            'method' => '(invokable)',
            'response_style' => 'Controller response',
            'form_requests' => [],
            'validation_keys' => [],
            'models' => [],
            'services' => [],
            'events' => [],
            'jobs' => [],
            'notifications' => [],
            'steps' => ['Request reaches controller and returns response.'],
            'database' => ['reads' => [], 'writes' => []],
            'notes' => ['Invokable action detected; method-level parser currently targets Controller@method format.'],
        ];
    }

    [$controller, $method] = explode('@', $action, 2);
    $file = controllerToFilePath($controller);

    if ($file === null || !is_file($file)) {
        return [
            'controller' => shortAction($controller),
            'method' => $method,
            'response_style' => 'Controller response',
            'form_requests' => [],
            'validation_keys' => [],
            'models' => [],
            'services' => [],
            'events' => [],
            'jobs' => [],
            'notifications' => [],
            'steps' => ['Controller file not found in workspace; verify action binding.'],
            'database' => ['reads' => [], 'writes' => []],
            'notes' => ['Could not parse controller source for this route.'],
        ];
    }

    $content = file_get_contents($file);
    if ($content === false) {
        return [
            'controller' => shortAction($controller),
            'method' => $method,
            'response_style' => 'Controller response',
            'form_requests' => [],
            'validation_keys' => [],
            'models' => [],
            'services' => [],
            'events' => [],
            'jobs' => [],
            'notifications' => [],
            'steps' => ['Controller file read failed.'],
            'database' => ['reads' => [], 'writes' => []],
            'notes' => ['Could not parse controller source for this route.'],
        ];
    }

    $useMap = parseUseMap($content);
    $methodParts = extractMethodSignatureAndBody($content, $method);

    if ($methodParts === null) {
        return [
            'controller' => shortAction($controller),
            'method' => $method,
            'response_style' => 'Controller response',
            'form_requests' => [],
            'validation_keys' => [],
            'models' => [],
            'services' => [],
            'events' => [],
            'jobs' => [],
            'notifications' => [],
            'steps' => ['Method body not found; verify method exists and uses standard syntax.'],
            'database' => ['reads' => [], 'writes' => []],
            'notes' => ['Parser could not isolate method body for this action.'],
        ];
    }

    $signature = $methodParts['signature'];
    $body = $methodParts['body'];

    $paramTypes = resolveParameterTypes($signature, $useMap);
    $formRequests = collectFormRequests($signature, $useMap);
    $validationKeys = detectValidationKeys($body);
    $deps = analyzeDependencies($body, $useMap, $paramTypes);
    $databaseImpact = inferDatabaseImpact($deps['models']);
    $steps = inferStepByStep($body, $formRequests, $validationKeys, $deps['models']);

    return [
        'controller' => shortAction($controller),
        'method' => $method,
        'response_style' => inferResponseStyle($body, (string) $route['uri']),
        'form_requests' => $formRequests,
        'validation_keys' => $validationKeys,
        'models' => $deps['models'],
        'services' => $deps['services'],
        'events' => $deps['events'],
        'jobs' => $deps['jobs'],
        'notifications' => $deps['notifications'],
        'steps' => $steps,
        'database' => $databaseImpact,
        'notes' => [],
    ];
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
 * @param array<int, string> $lines
 */
function writeMarkdown(string $path, array $lines): void
{
    file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);
}

/**
 * @param array<int, string> $lines
 * @param array<int, string> $items
 */
function writeList(array &$lines, string $title, array $items, string $empty = 'None detected'): void
{
    $lines[] = '**' . $title . '**:';
    if ($items === []) {
        $lines[] = '- ' . $empty;
    } else {
        foreach ($items as $item) {
            $lines[] = '- ' . $item;
        }
    }
    $lines[] = '';
}

/**
 * @param array<int, array<string, mixed>> $routes
 */
function buildSectionDoc(string $sectionName, array $routes, string $generatedAt): array
{
    $lines = [];
    $lines[] = '# ' . $sectionName . ' Route Lifecycles';
    $lines[] = '';
    $lines[] = '- Generated at: ' . $generatedAt;
    $lines[] = '- Routes in this section: ' . count($routes);
    $lines[] = '';
    $lines[] = 'This file is generated from current code. It documents current behavior, dependencies, and detected database impact for each route.';
    $lines[] = '';

    foreach ($routes as $route) {
        $analysis = analyzeRouteLifecycle($route);

        $title = implode('|', $route['methods']) . ' /' . $route['uri'];
        $lines[] = '## Route: ' . $title;
        $lines[] = '';
        $lines[] = '**Route Name**: `' . ($route['name'] !== '' ? $route['name'] : '(unnamed)') . '`';
        $lines[] = '**Controller**: `' . $analysis['controller'] . '@' . $analysis['method'] . '`';
        $lines[] = '**Middleware**: `' . implode(' -> ', $route['middleware']) . '`';
        $lines[] = '**Current Response Type**: ' . $analysis['response_style'];
        $lines[] = '';

        $lines[] = '**Step-by-Step Flow**:';
        $i = 1;
        foreach ($analysis['steps'] as $step) {
            $lines[] = $i . '. ' . $step;
            $i++;
        }
        $lines[] = '';

        writeList(
            $lines,
            'Validation',
            $analysis['form_requests'] !== []
                ? array_map(static fn (string $r): string => 'FormRequest: ' . $r, $analysis['form_requests'])
                : ($analysis['validation_keys'] !== []
                    ? ['Inline keys: ' . implode(', ', array_slice($analysis['validation_keys'], 0, 12)) . (count($analysis['validation_keys']) > 12 ? ', ...' : '')]
                    : []),
            'No explicit validation detected in controller method.'
        );

        $modelNames = [];
        foreach ($analysis['models'] as $model) {
            $rw = [];
            if ($model['reads']) {
                $rw[] = 'read';
            }
            if ($model['writes']) {
                $rw[] = 'write';
            }
            if ($rw === []) {
                $rw[] = 'used';
            }
            $modelNames[] = $model['name'] . ' [' . implode('/', $rw) . ']';
        }

        writeList($lines, 'Models Used', $modelNames);
        writeList($lines, 'Services Used', $analysis['services']);
        writeList($lines, 'Events Used', $analysis['events']);
        writeList($lines, 'Jobs Used', $analysis['jobs']);
        writeList($lines, 'Notifications Used', $analysis['notifications']);

        $lines[] = '**Database Impact**:';
        if ($analysis['database']['reads'] === [] && $analysis['database']['writes'] === []) {
            $lines[] = '- No direct model-based DB access detected in this method.';
        } else {
            if ($analysis['database']['reads'] !== []) {
                $lines[] = '- Reads from: ' . implode(', ', $analysis['database']['reads']);
            }
            if ($analysis['database']['writes'] !== []) {
                $lines[] = '- Writes to: ' . implode(', ', $analysis['database']['writes']);
            }
        }
        $lines[] = '';

        $lines[] = '**Returned Output Shape (Current)**:';
        if ($analysis['response_style'] === 'JSON/API response') {
            $lines[] = '- JSON payload (exact keys depend on method return body).';
        } elseif ($analysis['response_style'] === 'Inertia page render') {
            $lines[] = '- Inertia page object with component + props.';
        } elseif ($analysis['response_style'] === 'Redirect response') {
            $lines[] = '- Redirect response (often with flash success/error).';
        } else {
            $lines[] = '- Controller-defined response.';
        }
        $lines[] = '';

        if ($analysis['notes'] !== []) {
            writeList($lines, 'Notes', $analysis['notes']);
        }

        $lines[] = '---';
        $lines[] = '';
    }

    return $lines;
}

$routes = collectRoutes();
$grouped = groupBySection($routes);
$generatedAt = date('Y-m-d H:i:s');

$docsDir = base_path('docs/routes/lifecycle');
if (!is_dir($docsDir)) {
    mkdir($docsDir, 0777, true);
}

$readme = [];
$readme[] = '# Route Lifecycle Documentation';
$readme[] = '';
$readme[] = '- Generated at: ' . $generatedAt;
$readme[] = '- Total routes documented: ' . count($routes);
$readme[] = '';
$readme[] = '## Sections';
$readme[] = '';

foreach ($grouped as $sectionName => $sectionRoutes) {
    $fileName = 'SECTION_' . strtoupper(str_replace(' ', '_', str_replace('&', 'AND', $sectionName))) . '.md';
    $readme[] = '- [' . $sectionName . ' (' . count($sectionRoutes) . ' routes)](' . $fileName . ')';

    $sectionDoc = buildSectionDoc($sectionName, $sectionRoutes, $generatedAt);
    writeMarkdown($docsDir . '/' . $fileName, $sectionDoc);
}

$readme[] = '';
$readme[] = '## Regenerate';
$readme[] = '';
$readme[] = '```bash';
$readme[] = 'php scripts/generate_route_lifecycle_docs.php';
$readme[] = '```';
$readme[] = '';
$readme[] = '## Important Notes';
$readme[] = '';
$readme[] = '- This generator documents current behavior from static code inspection.';
$readme[] = '- For complex methods, validate with tests/runtime traces where needed.';
$readme[] = '- Use this as baseline before API migration refactors.';
$readme[] = '';

writeMarkdown($docsDir . '/README.md', $readme);

echo 'Generated lifecycle route docs in ' . $docsDir . PHP_EOL;
