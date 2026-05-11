<?php

namespace App\Services;

use App\Models\CourseOnlineAssignment;
use App\Models\CourseRegistration;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Collection;

class QuizDetailedExportService
{
    public function headers(): array
    {
        return [
            'user_id',
            'user_name',
            'user_email',
            'employee_code',
            'department_name',
            'course_type',
            'course_id',
            'course_name',
            'module_id',
            'module_name',
            'quiz_id',
            'quiz_title',
            'quiz_status',
            'pass_threshold',
            'total_points',
            'attempt_id',
            'attempt_number',
            'started_at',
            'completed_at',
            'attempt_status',
            'auto_score',
            'manual_score',
            'total_score',
            'passed',
            'question_id',
            'question_order',
            'question_type',
            'question_text',
            'question_points',
            'question_options_json',
            'correct_answer_json',
            'user_answer_json',
            'user_answer_text_normalized',
            'is_correct',
            'points_earned',
            'correct_answer_explanation',
            'exported_at',
            'exported_by_admin_id',
            'exported_by_admin_name',
        ];
    }

    public function buildRows(array $filters, bool $includeUsersWithoutAttempts, ?User $exportedBy): array
    {
        $rows = [];
        $attempts = $this->fetchAttempts($filters);

        foreach ($attempts as $attempt) {
            $quiz = $attempt->quiz;
            $user = $attempt->user;

            if (!$quiz || !$user) {
                continue;
            }

            [$courseType, $courseId, $courseName, $moduleId, $moduleName] = $this->resolveCourseContext($quiz);

            $questions = $quiz->questions->sortBy('order')->values();
            $answersByQuestion = $attempt->answers->keyBy('quiz_question_id');

            if ($questions->isEmpty()) {
                $rows[] = $this->buildRow(
                    user: $user,
                    courseType: $courseType,
                    courseId: $courseId,
                    courseName: $courseName,
                    moduleId: $moduleId,
                    moduleName: $moduleName,
                    quiz: $quiz,
                    attempt: $attempt,
                    question: null,
                    answer: null,
                    exportedBy: $exportedBy,
                );
                continue;
            }

            foreach ($questions as $question) {
                $answer = $answersByQuestion->get($question->id);
                $rows[] = $this->buildRow(
                    user: $user,
                    courseType: $courseType,
                    courseId: $courseId,
                    courseName: $courseName,
                    moduleId: $moduleId,
                    moduleName: $moduleName,
                    quiz: $quiz,
                    attempt: $attempt,
                    question: $question,
                    answer: $answer,
                    exportedBy: $exportedBy,
                );
            }
        }

        if ($includeUsersWithoutAttempts) {
            $rows = array_merge($rows, $this->buildNoAttemptRows($filters, $exportedBy));
        }

        return $rows;
    }

    private function fetchAttempts(array $filters): Collection
    {
        $query = QuizAttempt::query()->with([
            'user.department',
            'quiz.course',
            'quiz.courseOnline',
            'quiz.module',
            'quiz.questions',
            'answers.question',
        ]);

        if (!empty($filters['quiz_id'])) {
            $query->where('quiz_id', (int) $filters['quiz_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (!empty($filters['department_id'])) {
            $departmentId = (int) $filters['department_id'];
            $query->whereHas('user', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('completed_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('completed_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['passed']) && in_array($filters['passed'], ['passed', 'failed'], true)) {
            $query->where('passed', $filters['passed'] === 'passed');
        }

        if (!empty($filters['course_type']) && in_array($filters['course_type'], ['traditional', 'online', 'module_online'], true)) {
            $courseType = $filters['course_type'];
            $query->whereHas('quiz', function ($q) use ($courseType) {
                if ($courseType === 'traditional') {
                    $q->whereNotNull('course_id')->whereNull('course_online_id');
                }

                if ($courseType === 'online') {
                    $q->whereNotNull('course_online_id')->where('is_module_quiz', false);
                }

                if ($courseType === 'module_online') {
                    $q->where('is_module_quiz', true)->whereNotNull('module_id');
                }
            });
        }

        if (!empty($filters['module_id'])) {
            $query->whereHas('quiz', function ($q) use ($filters) {
                $q->where('module_id', (int) $filters['module_id']);
            });
        }

        if (!empty($filters['attempt_status']) && in_array($filters['attempt_status'], ['completed', 'pending'], true)) {
            if ($filters['attempt_status'] === 'completed') {
                $query->whereNotNull('completed_at');
            } else {
                $query->whereNull('completed_at');
            }
        }

        return $query->orderByDesc('id')->get();
    }

    private function buildNoAttemptRows(array $filters, ?User $exportedBy): array
    {
        $rows = [];
        $quizzes = $this->fetchQuizzesForCoverage($filters);

        foreach ($quizzes as $quiz) {
            [$courseType, $courseId, $courseName, $moduleId, $moduleName] = $this->resolveCourseContext($quiz);
            $candidateUsers = $this->fetchCandidateUsersForQuiz($quiz, $filters);

            if ($candidateUsers->isEmpty()) {
                continue;
            }

            $attemptedUserIds = QuizAttempt::query()
                ->where('quiz_id', $quiz->id)
                ->pluck('user_id')
                ->all();

            $noAttemptUsers = $candidateUsers->whereNotIn('id', $attemptedUserIds);

            foreach ($noAttemptUsers as $user) {
                $rows[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'employee_code' => $user->employee_code,
                    'department_name' => $user->department?->name,
                    'course_type' => $courseType,
                    'course_id' => $courseId,
                    'course_name' => $courseName,
                    'module_id' => $moduleId,
                    'module_name' => $moduleName,
                    'quiz_id' => $quiz->id,
                    'quiz_title' => $quiz->title,
                    'quiz_status' => $quiz->status,
                    'pass_threshold' => $quiz->pass_threshold,
                    'total_points' => $quiz->total_points,
                    'attempt_id' => null,
                    'attempt_number' => null,
                    'started_at' => null,
                    'completed_at' => null,
                    'attempt_status' => 'no_attempt',
                    'auto_score' => null,
                    'manual_score' => null,
                    'total_score' => null,
                    'passed' => null,
                    'question_id' => null,
                    'question_order' => null,
                    'question_type' => null,
                    'question_text' => null,
                    'question_points' => null,
                    'question_options_json' => null,
                    'correct_answer_json' => null,
                    'user_answer_json' => null,
                    'user_answer_text_normalized' => null,
                    'is_correct' => null,
                    'points_earned' => null,
                    'correct_answer_explanation' => null,
                    'exported_at' => now()->toDateTimeString(),
                    'exported_by_admin_id' => $exportedBy?->id,
                    'exported_by_admin_name' => $exportedBy?->name,
                ];
            }
        }

        return $rows;
    }

    private function fetchQuizzesForCoverage(array $filters): Collection
    {
        $query = Quiz::query()->with(['course', 'courseOnline', 'module']);

        if (!empty($filters['quiz_id'])) {
            $query->where('id', (int) $filters['quiz_id']);
        }

        if (!empty($filters['course_type']) && in_array($filters['course_type'], ['traditional', 'online', 'module_online'], true)) {
            if ($filters['course_type'] === 'traditional') {
                $query->whereNotNull('course_id')->whereNull('course_online_id');
            }

            if ($filters['course_type'] === 'online') {
                $query->whereNotNull('course_online_id')->where('is_module_quiz', false);
            }

            if ($filters['course_type'] === 'module_online') {
                $query->where('is_module_quiz', true)->whereNotNull('module_id');
            }
        }

        if (!empty($filters['module_id'])) {
            $query->where('module_id', (int) $filters['module_id']);
        }

        return $query->get();
    }

    private function fetchCandidateUsersForQuiz(Quiz $quiz, array $filters): Collection
    {
        $query = User::query()->with('department');

        if (!empty($filters['user_id'])) {
            $query->where('id', (int) $filters['user_id']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', (int) $filters['department_id']);
        }

        if (!empty($quiz->course_id)) {
            $courseId = (int) $quiz->course_id;
            $query->whereIn('id', CourseRegistration::query()->where('course_id', $courseId)->pluck('user_id'));
        } elseif (!empty($quiz->course_online_id)) {
            $courseOnlineId = (int) $quiz->course_online_id;
            $query->whereIn('id', CourseOnlineAssignment::query()->where('course_online_id', $courseOnlineId)->pluck('user_id'));
        } else {
            return collect();
        }

        return $query->get();
    }

    private function resolveCourseContext(Quiz $quiz): array
    {
        if ($quiz->isModuleQuiz()) {
            return [
                'module_online',
                $quiz->courseOnline?->id,
                $quiz->courseOnline?->name,
                $quiz->module?->id,
                $quiz->module?->name,
            ];
        }

        if (!empty($quiz->course_online_id)) {
            return [
                'online',
                $quiz->courseOnline?->id,
                $quiz->courseOnline?->name,
                null,
                null,
            ];
        }

        return [
            'traditional',
            $quiz->course?->id,
            $quiz->course?->name,
            null,
            null,
        ];
    }

    private function buildRow(
        User $user,
        string $courseType,
        ?int $courseId,
        ?string $courseName,
        ?int $moduleId,
        ?string $moduleName,
        Quiz $quiz,
        QuizAttempt $attempt,
        mixed $question,
        mixed $answer,
        ?User $exportedBy,
    ): array {
        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'employee_code' => $user->employee_code,
            'department_name' => $user->department?->name,
            'course_type' => $courseType,
            'course_id' => $courseId,
            'course_name' => $courseName,
            'module_id' => $moduleId,
            'module_name' => $moduleName,
            'quiz_id' => $quiz->id,
            'quiz_title' => $quiz->title,
            'quiz_status' => $quiz->status,
            'pass_threshold' => $quiz->pass_threshold,
            'total_points' => $quiz->total_points,
            'attempt_id' => $attempt->id,
            'attempt_number' => $attempt->attempt_number,
            'started_at' => $attempt->started_at?->toDateTimeString(),
            'completed_at' => $attempt->completed_at?->toDateTimeString(),
            'attempt_status' => $attempt->completed_at ? 'completed' : 'pending',
            'auto_score' => $attempt->score,
            'manual_score' => $attempt->manual_score,
            'total_score' => $attempt->total_score,
            'passed' => $attempt->passed,
            'question_id' => $question?->id,
            'question_order' => $question?->order,
            'question_type' => $question?->type,
            'question_text' => $question?->question_text,
            'question_points' => $question?->points,
            'question_options_json' => $this->normalizeToJson($question?->options),
            'correct_answer_json' => $this->normalizeToJson($question?->correct_answer),
            'user_answer_json' => $this->normalizeToJson($answer?->answer),
            'user_answer_text_normalized' => $this->normalizeToText($answer?->answer),
            'is_correct' => $answer?->is_correct,
            'points_earned' => $answer?->points_earned,
            'correct_answer_explanation' => $question?->correct_answer_explanation,
            'exported_at' => now()->toDateTimeString(),
            'exported_by_admin_id' => $exportedBy?->id,
            'exported_by_admin_name' => $exportedBy?->name,
        ];
    }

    private function normalizeToJson(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if (is_bool($value) || is_int($value) || is_float($value)) {
            return json_encode($value);
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }

            return json_encode([$value], JSON_UNESCAPED_UNICODE);
        }

        return json_encode([(string) $value], JSON_UNESCAPED_UNICODE);
    }

    private function normalizeToText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return implode(' | ', array_map(fn ($item) => (string) $item, $value));
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return implode(' | ', array_map(fn ($item) => (string) $item, $decoded));
            }

            return $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
