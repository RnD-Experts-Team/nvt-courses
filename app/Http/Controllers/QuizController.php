<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Get quizzes from both regular and online courses (exclude module quizzes)
        // ✅ FIXED N+1: Added attempts eager loading filtered by current user
        $quizzes = Quiz::with([
                'course',
                'courseOnline',
                'attempts' => fn($q) => $q->where('user_id', $user->id)
            ])
            ->where('status', 'published')
            ->where(function($q) {
                $q->where('is_module_quiz', false)
                  ->orWhereNull('is_module_quiz');
            })
            ->where(function ($query) use ($user) {
                // Regular courses
                $query->whereHas('course', function ($q) use ($user) {
                    $q->whereHas('registrations', function ($reg) use ($user) {
                        $reg->where('user_id', $user->id);
                    });
                })
                    // Online courses
                    ->orWhereHas('courseOnline', function ($q) use ($user) {
                        $q->whereHas('assignments', function ($assign) use ($user) {
                            $assign->where('user_id', $user->id);
                        });
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $quizzes->getCollection()->transform(function ($quiz) use ($user) {
            // ✅ FIXED N+1: Use eager loaded attempts instead of querying
            $userAttempts = $quiz->attempts;
            $attemptCount = $userAttempts->count();
            $hasPassed = $userAttempts->contains('passed', true);

            $latestAttempt = $userAttempts->sortByDesc('created_at')->first();


            // Get associated course (already eager loaded)
            $associatedCourse = $quiz->course ?? $quiz->courseOnline;

            return [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'total_points' => $quiz->total_points,
                'pass_threshold' => $quiz->pass_threshold ?? 80.00,

                // Course information (works for both types)
                'course' => $associatedCourse ? [
                    'id' => $associatedCourse->id,
                    'name' => $associatedCourse->name,
                ] : null,
                'course_type' => $quiz->getCourseType(),

                'latest_attempt_id' => $latestAttempt?->id,

                // Attempt information
                'attempts' => $attemptCount,
                'has_passed' => $hasPassed,

                // NEW: Deadline information
                'has_deadline' => $quiz->has_deadline,
                'deadline' => $quiz->deadline?->format('Y-m-d H:i:s'),
                'deadline_formatted' => $quiz->getFormattedDeadline(),
                'time_until_deadline' => $quiz->getTimeUntilDeadline(),
                'deadline_status' => $quiz->getDeadlineStatus(),
                'is_available' => $quiz->isAvailableForTaking(),
                'enforce_deadline' => $quiz->enforce_deadline,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'max_attempts' => $quiz->max_attempts,
                'retry_delay_hours' => $quiz->retry_delay_hours,
                'show_correct_answers' => $quiz->show_correct_answers,
            ];
        });

        return Inertia::render('Quizzes/Index', [
            'quizzes' => $quizzes,
        ]);
    }

    /**
     * Display the specified quiz for the user to take.
     */
    public function show(Quiz $quiz)
    {
        $user = Auth::user();

        // Redirect module quizzes to the correct controller
        if ($quiz->isModuleQuiz()) {
            return redirect()->route('courses-online.modules.quiz.show', [
                'courseOnline' => $quiz->course_online_id,
                'courseModule' => $quiz->module_id,
            ]);
        }

        // Check enrollment for both regular and online courses
        $isEnrolled = $this->checkUserEnrollment($user, $quiz);

        if (!$isEnrolled) {
            return redirect()->back()->withErrors(['error' => 'You are not enrolled in this course.']);
        }

        // NEW: Check if quiz is available (deadline check)
        if (!$quiz->isAvailableForTaking()) {
            $message = $quiz->enforce_deadline
                ? 'This quiz deadline has passed and no longer accepts submissions.'
                : 'This quiz deadline has passed. Late submissions may be subject to penalties.';

            return redirect()->back()->withErrors(['error' => $message]);
        }

        $userAttempts = $quiz->attempts()->where('user_id', $user->id)->get();
        $attemptCount = $userAttempts->count();
        $hasPassed = $userAttempts->contains('passed', true);

        if ($hasPassed) {
            return redirect()->route('quizzes.index')
                ->with('info', 'You have already passed this quiz and cannot retake it.');
        }

        // Enforce dynamic attempt policies (max attempts, retry delay, deadline)
        $attemptPolicy = $quiz->canUserAttempt($user->id);
        if (!$attemptPolicy['can_attempt']) {
            return redirect()->back()->withErrors(['error' => $attemptPolicy['message']]);
        }

        $quiz->load(['course', 'courseOnline', 'questions']);

        // Process questions with shuffling for random order
        $questions = $quiz->questions->shuffle()->map(function ($question) {
            // Safely handle options field
            $options = [];

            if (!empty($question->options)) {
                if (is_array($question->options)) {
                    $options = $question->options;
                } elseif (is_string($question->options)) {
                    // Try to decode JSON string
                    $decoded = json_decode($question->options, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $options = $decoded;
                    }
                }
            }

            return [
                'id' => $question->id,
                'question_text' => $question->question_text ?? '',
                'type' => $question->type ?? 'radio',
                'options' => $options,
                'points' => $question->points ?? 0,
                'order' => $question->order ?? 0,
                'correct_answer_explanation' => $question->correct_answer_explanation ?? '',
            ];
        });

        // Get associated course
        $associatedCourse = $quiz->getAssociatedCourse();

        return Inertia::render('Quizzes/Show', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title ?? '',
                'description' => $quiz->description ?? '',
                'pass_threshold' => $quiz->pass_threshold ?? 80.00,
                'total_points' => $quiz->total_points ?? 0,
                'course' => $associatedCourse ? [
                    'id' => $associatedCourse->id,
                    'name' => $associatedCourse->name ?? ''
                ] : null,
                'course_type' => $quiz->getCourseType(),
                'attempt_count' => $attemptCount,
                'has_passed' => $hasPassed,

                // NEW: Deadline information
                'has_deadline' => $quiz->has_deadline,
                'deadline' => $quiz->deadline?->format('Y-m-d H:i:s'),
                'deadline_formatted' => $quiz->getFormattedDeadline(),
                'time_until_deadline' => $quiz->getTimeUntilDeadline(),
                'deadline_status' => $quiz->getDeadlineStatus(),
                'enforce_deadline' => $quiz->enforce_deadline,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'allows_extensions' => $quiz->allows_extensions,
            ],
            'questions' => $questions,
        ]);
    }

    /**
     * Store the user's quiz attempt.
     */
    public function store(Request $request, Quiz $quiz)
    {
        $request->validate([
            'answers.*.question_id' => 'required|exists:quiz_questions,id',
            'answers.*.answer' => 'required',
        ]);

        $user = auth()->user();

        // Redirect module quizzes to the correct controller (safety fallback)
        if ($quiz->isModuleQuiz()) {
            return redirect()->route('courses-online.modules.quiz.show', [
                'courseOnline' => $quiz->course_online_id,
                'courseModule' => $quiz->module_id,
            ])->with('error', 'Please take module quizzes from the course page.');
        }

        // Check enrollment
        if (!$this->checkUserEnrollment($user, $quiz)) {
            return redirect()->back()->withErrors(['error' => 'You are not enrolled in this course.']);
        }

        // NEW: Check deadline (allow soft deadline submissions but warn)
        if (!$quiz->isAvailableForTaking() && $quiz->enforce_deadline) {
            return redirect()->back()->withErrors(['error' => 'This quiz deadline has passed and no longer accepts submissions.']);
        }

        $userAttempts = $quiz->attempts()->where('user_id', $user->id)->get();
        $attemptCount = $userAttempts->count();
        $hasPassed = $userAttempts->contains('passed', true);

        if ($hasPassed) {
            return redirect()->route('quizzes.index')
                ->with('info', 'You have already passed this quiz and cannot submit another attempt.');
        }

        // Enforce dynamic attempt policies (max attempts, retry delay, deadline)
        $attemptPolicy = $quiz->canUserAttempt($user->id);
        if (!$attemptPolicy['can_attempt']) {
            return redirect()->back()->withErrors(['error' => $attemptPolicy['message']]);
        }

        DB::beginTransaction();
        try {
            $attempt = $quiz->attempts()->create([
                'user_id' => $user->id,
                'attempt_number' => $attemptCount + 1,
                'completed_at' => now(),
                // NEW: Track if submitted after deadline
                'submitted_after_deadline' => $quiz->has_deadline && $quiz->isPastDeadline(),
            ]);

            $totalAutoScore = 0;
            foreach ($request->input('answers', []) as $answerData) {
                $question = QuizQuestion::find($answerData['question_id']);
                $isCorrect = $question->isCorrect($answerData['answer']);
                $pointsEarned = $isCorrect ? ($question->type !== 'text' ? $question->points : 0) : 0;

                QuizAnswer::create([
                    'quiz_attempt_id' => $attempt->id,
                    'quiz_question_id' => $answerData['question_id'],
                    'answer' => is_array($answerData['answer']) ? json_encode($answerData['answer']) : $answerData['answer'],
                    'is_correct' => $isCorrect,
                    'points_earned' => $pointsEarned,
                ]);

                if ($question->type !== 'text' && $isCorrect) {
                    $totalAutoScore += $pointsEarned;
                }
            }

            // Calculate total score properly BEFORE checking if passed
            $manualScore = $attempt->manual_score ?? 0;
            $totalScore = $totalAutoScore + $manualScore;

            // Update attempt with scores first
            $attempt->update([
                'score' => $totalAutoScore,
                'total_score' => $totalScore,
                'manual_score' => $manualScore,
            ]);

            // Now check if passed using the updated scores
            $isPassed = $attempt->fresh()->isPassed();

            // Update the passed status
            $attempt->update([
                'passed' => $isPassed
            ]);

            DB::commit();

            // Show appropriate message for late submissions
            if ($quiz->has_deadline && $quiz->isPastDeadline() && !$quiz->enforce_deadline) {
                session()->flash('warning', 'Your submission was received after the deadline. It may be subject to penalties.');
            }

            return redirect()->route('quiz-attempts.results', $attempt->id);

        } catch (\Exception $e) {
            DB::rollBack();


            return redirect()->back()->withErrors(['error' => 'Failed to submit quiz. Please try again.']);
        }
    }

    public function results(QuizAttempt $attempt)
    {
        $attempt->load(['user', 'quiz.course', 'quiz.courseOnline', 'answers.question']);

        $responses = $attempt->answers->map(function ($answer) {
            if (!$answer->question) {
                return null;
            }

            // Safe JSON decode function
            $safeJsonDecode = function ($value) {
                if (is_string($value)) {
                    // First decode attempt
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // If it's still a string after first decode, try again (double-encoded case)
                        if (is_string($decoded)) {
                            $secondDecode = json_decode($decoded, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                return $secondDecode;
                            }
                        }
                        return $decoded;
                    }
                }

                return is_array($value) ? $value : $value;
            };

            return [
                'id' => $answer->id,
                'question' => [
                    'id' => $answer->question->id,
                    'question_text' => $answer->question->question_text,
                    'points' => $answer->question->points ?? 0,
                    'type' => $answer->question->type,
                    'correct_answer' => $safeJsonDecode($answer->question->correct_answer),
                    'correct_answer_explanation' => $answer->question->correct_answer_explanation ?? '',
                ],
                'answer' => $safeJsonDecode($answer->answer),
                'is_correct' => $answer->is_correct,
                'points_earned' => $answer->points_earned ?? 0,
            ];
        })->filter();

        // Get associated course
        $associatedCourse = $attempt->quiz->getAssociatedCourse();
        $showCorrectAnswersAllowed = $attempt->quiz->shouldShowCorrectAnswers($attempt->user_id);

        return Inertia::render('Quizzes/Results', [
            'attempt' => [
                'id' => $attempt->id,
                'score' => $attempt->score,
                'total_score' => $attempt->total_score,
                'passed' => $attempt->isPassed(),
                'completed_at' => $attempt->completed_at ? $attempt->completed_at->format('Y-m-d H:i:s') : null,
                'submitted_after_deadline' => $attempt->submitted_after_deadline ?? false, // NEW
                'quiz' => [
                    'id' => $attempt->quiz->id,
                    'title' => $attempt->quiz->title,
                    'pass_threshold' => $attempt->quiz->pass_threshold ?? 80.00,
                    'total_points' => $attempt->quiz->total_points ?? 0,
                    'course_type' => $attempt->quiz->getCourseType(), // NEW
                    'course' => $associatedCourse ? [
                        'id' => $associatedCourse->id,
                        'name' => $associatedCourse->name
                    ] : null,
                    // NEW: Deadline info
                    'has_deadline' => $attempt->quiz->has_deadline,
                    'deadline_formatted' => $attempt->quiz->getFormattedDeadline(),
                    'enforce_deadline' => $attempt->quiz->enforce_deadline,
                    'max_attempts' => $attempt->quiz->max_attempts,
                    'retry_delay_hours' => $attempt->quiz->retry_delay_hours,
                    'show_correct_answers' => $attempt->quiz->show_correct_answers,
                ],
                'attempt_number' => $attempt->attempt_number,
                'responses' => $responses,
            ],
            'showCorrectAnswersAllowed' => $showCorrectAnswersAllowed,
            'userAttempts' => QuizAttempt::where('user_id', $attempt->user_id)
                ->where('quiz_id', $attempt->quiz_id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($userAttempt) {
                    return [
                        'id' => $userAttempt->id,
                        'attempt_number' => $userAttempt->attempt_number,
                        'score' => $userAttempt->score,
                        'total_score' => $userAttempt->total_score,
                        'passed' => $userAttempt->isPassed(),
                        'completed_at' => $userAttempt->completed_at?->format('Y-m-d H:i:s'),
                        'submitted_after_deadline' => $userAttempt->submitted_after_deadline ?? false,
                    ];
                }),
        ]);
    }

    /**
     * NEW: Check if user is enrolled in the quiz's course (both regular and online)
     */
    private function checkUserEnrollment($user, Quiz $quiz): bool
    {
        if ($quiz->isRegularCourse()) {
            // Check regular course enrollment
            return $user->courses()->where('courses.id', $quiz->course_id)->exists();
        } else {
            // Check online course assignment
            return DB::table('course_online_assignments')
                ->where('user_id', $user->id)
                ->where('course_online_id', $quiz->course_online_id)
                ->whereIn('status', ['assigned', 'in_progress', 'completed'])
                ->exists();
        }
    }
}
