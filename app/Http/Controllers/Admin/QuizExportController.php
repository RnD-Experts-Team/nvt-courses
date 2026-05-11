<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CsvExportService;
use App\Services\QuizDetailedExportService;
use App\Services\XlsxExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuizExportController extends Controller
{
    public function __construct(
        private readonly QuizDetailedExportService $quizDetailedExportService,
        private readonly CsvExportService $csvExportService,
        private readonly XlsxExportService $xlsxExportService,
    ) {
    }

    public function exportDetailed(Request $request)
    {
        $validated = $request->validate([
            'quiz_id' => 'nullable|integer|exists:quizzes,id',
            'course_type' => 'nullable|in:traditional,online,module_online',
            'department_id' => 'nullable|integer|exists:departments,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'module_id' => 'nullable|integer|exists:course_modules,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'passed' => 'nullable|in:passed,failed,all',
            'attempt_status' => 'nullable|in:completed,pending',
            'include_users_without_attempts' => 'nullable|boolean',
            'format' => 'nullable|in:csv,xlsx',
        ]);

        $format = $validated['format'] ?? 'csv';
        $includeUsersWithoutAttempts = (bool) ($validated['include_users_without_attempts'] ?? false);
        $filters = array_merge(['passed' => 'all'], $validated);

        $rows = $this->quizDetailedExportService->buildRows($filters, $includeUsersWithoutAttempts, $request->user());
        $headers = $this->quizDetailedExportService->headers();

        Log::info('Quiz detailed export generated', [
            'exported_by' => $request->user()?->id,
            'format' => $format,
            'filters' => $filters,
            'rows_count' => count($rows),
        ]);

        $baseFilename = 'quiz_detailed_report_' . now()->format('Y-m-d_His');

        if ($format === 'xlsx') {
            return $this->xlsxExportService->export($baseFilename . '.xlsx', $headers, $rows);
        }

        return $this->csvExportService->export($baseFilename . '.csv', $headers, $rows);
    }
}
