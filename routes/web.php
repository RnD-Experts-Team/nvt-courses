<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\ChunkUploadController;
use App\Http\Controllers\Admin\CourseAssignmentController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\CourseOnlineReportController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EvaluationAssignmentController;
use App\Http\Controllers\Admin\EvaluationController;
use App\Http\Controllers\Admin\EvaluationNotificationController;
use App\Http\Controllers\Admin\HistoryController;
use App\Http\Controllers\Admin\OnlineCourseEvaluationController;
use App\Http\Controllers\Admin\OrganizationalController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ResendLoginController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserDepartmentRoleController;
use App\Http\Controllers\Admin\UserEvaluationController;
use App\Http\Controllers\Admin\UserLevelController;
use App\Http\Controllers\Admin\VideoBookmarkController;
use App\Http\Controllers\Admin\VideoCategoryController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\AudioController;
use App\Http\Controllers\AuthVaiEmailController;
use App\Http\Controllers\BugReportController;
use App\Http\Controllers\ClockingController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeFeedbackController;
use App\Http\Controllers\GeminiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\User\ContentViewController;
use App\Http\Controllers\User\CourseOnlineController;
use App\Http\Controllers\User\ModuleQuizController as UserModuleQuizController;
use App\Http\Controllers\UserTeamController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\VideoStreamController;
use App\Http\Controllers\TranscodeCallbackController;
use App\Models\Course;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubtitleCallbackController;

// ==========================================
// API ROUTES (JSON responses)
// ==========================================

Route::middleware(['auth', 'admin'])->prefix('api')->group(function () {
    Route::get('/audio-assignments/users', [App\Http\Controllers\Admin\AudioAssignmentController::class, 'filterUsers'])
        ->name('api.audio-assignments.users');
});

// ==========================================
// VPS TRANSCODING CALLBACK (No auth - VPS calls this)
// ==========================================

Route::post('/api/transcode/callback', [TranscodeCallbackController::class, 'handle'])
    ->name('transcode.callback');

// ==========================================
// ROOT & AUTHENTICATION ROUTES
// ==========================================


// VPS SUBTITLE CALLBACK (No auth - VPS calls this)
Route::post('/api/subtitle/callback', [SubtitleCallbackController::class, 'handle'])
    ->name('subtitle.callback');


// Replace the previous test route with this corrected one



Route::get('/docs/{path?}', function ($path = 'index') {
    $filePath = public_path('docs/' . str_replace('/', DIRECTORY_SEPARATOR, $path) . '.html');
    if (file_exists($filePath)) {
        return response()->file($filePath);
    }
    abort(404);
})->where('path', '.*')->name('docs');

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Token-based login for email notifications
Route::get('/login/token/{user}/{course}', [AuthVaiEmailController::class, 'tokenLogin'])
    ->name('auth.token-login')
    ->middleware('signed');

// Token-based login for audio assignments
Route::get('/login/audio-token/{user}/{audio}', [AuthVaiEmailController::class, 'audioTokenLogin'])
    ->name('auth.audio-token-login')
    ->middleware('signed');

// ==========================================
// BASIC AUTHENTICATED USER ROUTES
// ==========================================

Route::middleware(['auth'])->group(function () {



    Route::get('/video/stream/{video}/{quality?}', [VideoStreamController::class, 'stream'])
        ->name('video.stream');

    // ===== ATTENDANCE SYSTEM =====
    Route::get('/attendance', [ClockingController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/clock', [ClockingController::class, 'clockPage'])->name('attendance.clock');
    Route::post('/clock-in', [ClockingController::class, 'clockIn'])->name('clock.in');
    Route::post('/clock-out', [ClockingController::class, 'clockOut'])->name('clock.out');

    // ===== QUIZ SYSTEM =====
    Route::get('quizzes', [\App\Http\Controllers\QuizController::class, 'index'])->name('quizzes.index');
    Route::get('quizzes/{quiz}', [\App\Http\Controllers\QuizController::class, 'show'])->name('quizzes.show');
    Route::post('quizzes/{quiz}', [\App\Http\Controllers\QuizController::class, 'store'])->name('quizzes.store');
    Route::get('quiz-attempts/{attempt}/results', [\App\Http\Controllers\QuizController::class, 'results'])->name('quiz-attempts.results');

    // ===== ASSIGNMENTS =====
    Route::get('my-assignments', [App\Http\Controllers\AssignmentController::class, 'index'])->name('assignments.index');

    // ===== AUDIO SYSTEM =====
    Route::get('/audio', [AudioController::class, 'index'])->name('audio.index');
    Route::get('/audio/{audio}', [AudioController::class, 'show'])->name('audio.show');
    Route::get('/audio/{audio}/stream', [AudioController::class, 'stream'])->name('audio.stream');
    Route::post('/audio/{audio}/progress', [AudioController::class, 'updateProgress'])->name('audio.progress');
    Route::post('/audio/{audio}/complete', [AudioController::class, 'markCompleted'])->name('audio.complete');
});

// ==========================================
// USER COURSE ROUTES (TRADITIONAL COURSES)
// ==========================================

Route::middleware('auth')->group(function () {
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('/courses/{course}/enroll', [CourseController::class, 'enroll'])->name('courses.enroll');
    Route::post('/courses/{course}/mark-completed', [CourseController::class, 'markCompleted'])->name('courses.markCompleted');
    Route::post('/courses/{course}/rating', [CourseController::class, 'submitRating'])->name('courses.submitRating');
    Route::get('/courses/{id}/completion', [CourseController::class, 'showCompletionPage'])->name('courses.completion');
    Route::post('/courses/{id}/rating', [CourseController::class, 'submitRating'])->name('courses.rating.submit');




    // Debug route (can be removed in production)
    Route::get('/debug/course/{course}', function (Course $course) {
        return [
            'course' => $course,
            'route_works' => true
        ];
    });
});

// ==========================================
// USER ONLINE COURSE ROUTES (NEW SYSTEM)
// ==========================================

Route::middleware(['auth'])->group(function () {

    Route::get('/feedback', [EmployeeFeedbackController::class, 'create'])->name('feedback.create');
    Route::post('/feedback', [EmployeeFeedbackController::class, 'store'])->name('feedback.store');
    Route::get('/my-feedback', [EmployeeFeedbackController::class, 'myFeedback'])->name('feedback.my');

    // Content session management
    Route::post('/content/{content}/session', [ContentViewController::class, 'manageSession'])->name('content.session');

    // Content progress tracking
    Route::post('/content/{content}/progress', [ContentViewController::class, 'updateProgress'])->name('content.progress');

    // Mark content as complete
    Route::post('/content/{content}/complete', [ContentViewController::class, 'complete'])->name('content.complete');

    // ===== COURSE ONLINE LEARNING DASHBOARD =====
    Route::prefix('courses-online')->name('courses-online.')->group(function () {

        // User learning dashboard
        Route::get('/', [CourseOnlineController::class, 'index'])->name('index');

        // View specific course (course viewer with modules)
        Route::get('/{courseOnline}', [CourseOnlineController::class, 'show'])->name('show');

        // Start course (marks as in_progress)
        Route::post('/{courseOnline}/start', [CourseOnlineController::class, 'startCourse'])->name('start');

        // Mark course as completed
        Route::post('/{courseOnline}/complete', [CourseOnlineController::class, 'completeCourse'])->name('complete');

        // Update course progress
        Route::post('/{courseOnline}/progress', [CourseOnlineController::class, 'updateProgress'])->name('progress');

        // Get next unlocked content
        Route::get('/{courseOnline}/next-content', [CourseOnlineController::class, 'getNextContent'])->name('next-content');

        // Module-specific routes
        Route::prefix('{courseOnline}/modules')->name('modules.')->group(function () {
            // Unlock next module
            Route::post('/{courseModule}/unlock', [CourseOnlineController::class, 'unlockModule'])->name('unlock');

            // Mark module as completed
            Route::post('/{courseModule}/complete', [CourseOnlineController::class, 'completeModule'])->name('complete');

            // ===== MODULE QUIZ ROUTES (USER) =====
            Route::prefix('{courseModule}/quiz')->name('quiz.')->group(function () {
                // View quiz info before starting
                Route::get('/', [UserModuleQuizController::class, 'show'])->name('show');
                
                // Start a new quiz attempt
                Route::post('/start', [UserModuleQuizController::class, 'start'])->name('start');
                
                // Take the quiz (active attempt)
                Route::get('/take/{attempt}', [UserModuleQuizController::class, 'take'])->name('take');
                
                // Save answer during quiz (auto-save)
                Route::post('/save-answer/{attempt}', [UserModuleQuizController::class, 'saveAnswer'])->name('save-answer');
                
                // Submit quiz for grading
                Route::post('/submit/{attempt}', [UserModuleQuizController::class, 'submit'])->name('submit');
                
                // View quiz result
                Route::get('/result/{attempt}', [UserModuleQuizController::class, 'result'])->name('result');
                
                // View quiz history (all attempts)
                Route::get('/history', [UserModuleQuizController::class, 'history'])->name('history');
                
                // Auto-submit when time expires
                Route::post('/auto-submit/{attempt}', [UserModuleQuizController::class, 'autoSubmit'])->name('auto-submit');
            });
        });
    });

    // ===== CONTENT VIEWER & PROGRESS TRACKING =====
    Route::prefix('content')->name('content.')->group(function () {

        // View content (video/PDF viewer)
        Route::get('/{content}', [ContentViewController::class, 'show'])->name('show');


        // Get content streaming URL (for videos)
        Route::get('/{content}/stream-url', [ContentViewController::class, 'getStreamingUrl'])->name('stream-url');
    });

    // ===== USER LEARNING ANALYTICS =====
    Route::prefix('my-learning')->name('my-learning.')->group(function () {

        // Personal learning analytics
        Route::get('/analytics', [CourseOnlineController::class, 'myAnalytics'])->name('analytics');

        // Learning history
        Route::get('/history', [CourseOnlineController::class, 'learningHistory'])->name('history');

        // Certificates
        Route::get('/certificates', [CourseOnlineController::class, 'myCertificates'])->name('certificates');

        // Download certificate
        Route::get('/certificates/{assignment}/download', [CourseOnlineController::class, 'downloadCertificate'])->name('certificates.download');

        // Learning sessions
        Route::get('/sessions', [CourseOnlineController::class, 'mySessions'])->name('sessions');
    });
});

// ==========================================
// VIDEO SYSTEM ROUTES
// ==========================================

Route::middleware(['auth'])->group(function () {

    // Video listing and player
    Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
    Route::get('/videos/{video}', [VideoController::class, 'show'])->name('videos.show');

    // Video progress tracking
    Route::post('/videos/{video}/progress', [VideoController::class, 'updateProgress'])->name('videos.progress');

    // Mark video as completed
    Route::post('/videos/{video}/complete', [VideoController::class, 'markCompleted'])->name('videos.complete');

    // Video bookmarks management
    Route::get('/videos/{video}/bookmarks', [VideoBookmarkController::class, 'index'])->name('videos.bookmarks.index');
    Route::post('/videos/{video}/bookmarks', [VideoBookmarkController::class, 'store'])->name('videos.bookmarks.store');
    Route::put('/video-bookmarks/{bookmark}', [VideoBookmarkController::class, 'update'])->name('video-bookmarks.update');
    Route::delete('/video-bookmarks/{bookmark}', [VideoBookmarkController::class, 'destroy'])->name('video-bookmarks.destroy');
});
// ===== BLOG (User) =====
Route::middleware(['auth'])->group(function () {
    Route::get('/blog', [App\Http\Controllers\User\PodcastController::class, 'index'])->name('blog.index');
    Route::get('/blog/{slug}', [App\Http\Controllers\User\PodcastController::class, 'show'])->name('blog.show');
});

// ===== BLOG API (AJAX — likes & comments) =====
Route::middleware(['auth'])->prefix('api/blog')->name('api.blog.')->group(function () {
    Route::post('/{podcast}/like',           [App\Http\Controllers\User\PodcastController::class, 'toggleLike'])->name('like');
    Route::post('/{podcast}/comments',       [App\Http\Controllers\User\PodcastController::class, 'storeComment'])->name('comments.store');
    Route::delete('/comments/{comment}',     [App\Http\Controllers\User\PodcastController::class, 'destroyComment'])->name('comments.destroy');
});
// ==========================================
// USER PROFILE & ORGANIZATIONAL INFO
// ==========================================

Route::middleware(['auth'])->group(function () {

    // Profile management
    Route::get('/profile', [ProfileController::class, 'index'])->name('user.profile.index');

    // Evaluations
    Route::get('/evaluations', [App\Http\Controllers\User\UserEvaluationController::class, 'index'])->name('user.evaluations.index');
    Route::get('/evaluations/{id}', [App\Http\Controllers\User\UserEvaluationController::class, 'show'])->name('user.evaluations.show');

    // Team management
    Route::get('/my-team', [UserTeamController::class, 'index'])->name('user.team.index');

    // User organizational info
    Route::prefix('my')->name('my.')->group(function () {
        Route::get('/organizational-info', [UserController::class, 'myOrganizationalInfo'])->name('organizational-info');
        Route::get('/manager', [UserController::class, 'myManager'])->name('manager');
        Route::get('/team', [UserController::class, 'myTeam'])->name('team');
        Route::get('/department', [UserController::class, 'myDepartment'])->name('department');
    });
});

// ==========================================
// ACTIVITY & TRACKING ROUTES
// ==========================================

Route::middleware(['auth', 'verified'])->group(function () {

    // All activities view (for both admin and regular users)
    Route::get('/activities', [ActivityController::class, 'allActivities'])->name('activities.all');

    // Admin activity routes
    Route::get('/admin/activity', [ActivityController::class, 'index'])
        ->middleware('can:viewAny,App\Models\ActivityLog')
        ->name('admin.activity.index');

    // User activity routes
    Route::get('/activity', [ActivityController::class, 'userActivity'])->name('user.activity');
});

// ==========================================
// GEMINI AI ROUTES
// ==========================================

Route::middleware(['auth'])->group(function () {
    Route::get('/gemini', [GeminiController::class, 'index'])->name('gemini.index');
    Route::post('/gemini/generate', [GeminiController::class, 'generate'])->name('gemini.generate');
    Route::get('/gemini/instructions', [GeminiController::class, 'getInstructions'])->name('gemini.instructions');
});

// ==========================================
// MANAGER-SPECIFIC ROUTES
// ==========================================

Route::middleware(['auth'])->prefix('manager')->name('manager.')->group(function () {

    // Manager dashboard
    Route::get('/dashboard', [UserDepartmentRoleController::class, 'managerDashboard'])->name('dashboard');

    // Team management
    Route::get('/team', [UserDepartmentRoleController::class, 'myTeam'])->name('team');
    Route::get('/team/{user}', [UserDepartmentRoleController::class, 'teamMember'])->name('team.member');

    // Team reports
    Route::get('/reports/team-performance', [UserDepartmentRoleController::class, 'teamPerformance'])->name('reports.team-performance');
});

// ==========================================
// ADMIN ROUTES
// ==========================================

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {


    // Employee Feedback Management
    Route::get('/feedback', [EmployeeFeedbackController::class, 'index'])->name('feedback.index');
    Route::get('/feedback/{feedback}', [EmployeeFeedbackController::class, 'show'])->name('feedback.show');
    Route::patch('/feedback/{feedback}/respond', [EmployeeFeedbackController::class, 'respond'])->name('feedback.respond');
    Route::patch('/feedback/{feedback}/status', [EmployeeFeedbackController::class, 'updateStatus'])->name('feedback.status');

    // Bug Reports Management
    Route::resource('bug-reports', BugReportController::class);
    Route::patch('/bug-reports/{bugReport}/assign', [BugReportController::class, 'assign'])->name('bug-reports.assign');
    Route::patch('/bug-reports/{bugReport}/resolve', [BugReportController::class, 'resolve'])->name('bug-reports.resolve');




    // Course Online Reports
    Route::prefix('reports/course-online')->name('reports.course-online.')->group(function () {
        Route::get('/progress', [CourseOnlineReportController::class, 'progressReport'])->name('progress');
        Route::get('/learning-sessions', [CourseOnlineReportController::class, 'learningSessionsReport'])->name('learning-sessions');
        Route::get('/user-performance', [CourseOnlineReportController::class, 'userPerformanceReport'])->name('user-performance');
        Route::get('/department-performance', [CourseOnlineReportController::class, 'departmentPerformanceReport'])->name('department-performance'); // ✅ ADD THIS

        // Exports

        Route::get('/export/learning-sessions', [CourseOnlineReportController::class, 'exportLearningSessionsReport'])->name('export.learning-sessions'); // ✅ ADD THIS
        Route::get('/export/user-performance', [CourseOnlineReportController::class, 'exportUserPerformanceReport'])->name('export.user-performance');
        Route::get('/export/department-performance', [CourseOnlineReportController::class, 'exportDepartmentPerformanceReport'])->name('export.department-performance'); // ✅ ADD THIS
        Route::get('/export/progress', [CourseOnlineReportController::class, 'exportProgressReport'])->name('export.progress');
    });

    // User Course Progress Report
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/user-course-progress', [\App\Http\Controllers\Admin\UserCourseProgressReportController::class, 'index'])->name('user-course-progress');
        Route::get('/user-course-progress/export', [\App\Http\Controllers\Admin\UserCourseProgressReportController::class, 'export'])->name('user-course-progress.export');
    });

    // Debug route for course progress data (remove in production)
    Route::get('/debug-course-progress', [\App\Http\Controllers\Admin\DebugCourseProgressController::class, 'index'])->name('debug-course-progress');


    // ✅ Analytics Routes
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'dashboard'])->name('dashboard');
        Route::get('/cheating-detection', [AnalyticsController::class, 'cheatingDetection'])->name('cheating-detection');
        Route::get('/course-analytics', [AnalyticsController::class, 'courseAnalytics'])->name('course-analytics');

        // Additional analytics routes
        Route::post('send-warning/{user}', [AnalyticsController::class, 'sendWarningEmail'])
            ->name('send-warning');
        Route::get('/user-progress', [AnalyticsController::class, 'userProgress'])->name('user-progress');
        Route::get('/performance', [AnalyticsController::class, 'performance'])->name('performance');
        Route::get('/reports', [AnalyticsController::class, 'reports'])->name('reports');
        // Session Details Route
        Route::get('/session/{session}/details', [AnalyticsController::class, 'sessionDetails'])
            ->name('session-details');
        Route::get('/export', [AnalyticsController::class, 'export'])
            ->name('export');

    });


    // ===== USER MANAGEMENT =====
    Route::get('/users/assignment', [UserController::class, 'assignment'])->name('users.assignment');
    Route::post('/users/bulk-assign', [UserController::class, 'bulkAssign'])->name('users.bulk-assign');
    Route::post('/users/{user}/assign-level', [UserController::class, 'assignLevel'])->name('users.assign-level');
    Route::post('/users/{user}/assign-department', [UserController::class, 'assignDepartment'])->name('users.assign-department');
    Route::get('/users/available', [UserController::class, 'getAvailableUsers'])->name('users.available');
    Route::resource('users', AdminUserController::class);

    // User assignment routes
    Route::get('/users/{user}/assign', [UserController::class, 'assignForm'])->name('users.assign-form');
    Route::post('/users/{user}/assign-department', [UserController::class, 'assignDepartment'])->name('users.assign-department');
    Route::post('/users/{user}/assign-level', [UserController::class, 'assignLevel'])->name('users.assign-level');
    Route::post('/users/{user}/assign-manager', [UserController::class, 'assignManager'])->name('users.assign-manager');

    // Bulk user operations
    Route::post('/users/bulk-assign-department', [UserController::class, 'bulkAssignDepartment'])->name('users.bulk-assign-department');
    Route::post('/users/bulk-assign-level', [UserController::class, 'bulkAssignLevel'])->name('users.bulk-assign-level');
    Route::get('/users/import', [UserController::class, 'importForm'])->name('users.import');
    Route::post('/users/import', [UserController::class, 'import'])->name('users.import.process');
    Route::get('/users/export', [UserController::class, 'export'])->name('users.export');

    // User organizational info
    Route::get('/users/{user}/organizational', [UserController::class, 'organizationalInfo'])->name('users.organizational');
    Route::get('/users/{user}/reporting-chain', [UserController::class, 'reportingChain'])->name('users.reporting-chain');
    Route::get('/users/{user}/direct-reports', [UserController::class, 'directReports'])->name('users.direct-reports');

    // Resend login links
    Route::get('/resend-login-links', [ResendLoginController::class, 'index'])->name('resend-login-links.index');
    Route::post('/resend-login-links/{user}', [ResendLoginController::class, 'resend'])->name('resend-login-links.resend');
    Route::post('/resend-login-links/bulk', [ResendLoginController::class, 'bulkResend'])->name('resend-login-links.bulk');

    // ===== COURSE MANAGEMENT (TRADITIONAL) =====
    Route::resource('courses', AdminCourseController::class)->except('update');
    Route::post('courses/{course}/update', [AdminCourseController::class, 'update'])->name('courses.update');


   Route::post('videos/upload-chunk', [ChunkUploadController::class, 'upload']);
Route::patch('videos/upload-chunk', [ChunkUploadController::class, 'uploadChunk']);

        Route::delete('videos/upload-chunk/revert', [ChunkUploadController::class, 'revert'])
    ->name('videos.upload-chunk.revert');

    // ===== COURSE ONLINE MANAGEMENT (NEW SYSTEM) =====
    Route::resource('course-online', App\Http\Controllers\Admin\CourseOnlineController::class);
    Route::post('course-online/{courseOnline}/update', [App\Http\Controllers\Admin\CourseOnlineController::class, 'update'])->name('course-online.update-file');

    Route::patch('course-online/{courseOnline}/toggle-active', [App\Http\Controllers\Admin\CourseOnlineController::class, 'toggleActive'])->name('course-online.toggle-active');
    Route::post('course-online/{courseOnline}/refresh-video-urls', [App\Http\Controllers\Admin\CourseOnlineController::class, 'refreshVideoUrls'])->name('course-online.refresh-video-urls');
    Route::get('course-online/{courseOnline}/statistics', [App\Http\Controllers\Admin\CourseOnlineController::class, 'statistics'])->name('course-online.statistics');

    // Course Modules Management
    Route::get('course-online/{courseOnline}/modules', [App\Http\Controllers\Admin\CourseModuleController::class, 'index'])->name('course-modules.index');
    Route::get('course-online/{courseOnline}/modules/create', [App\Http\Controllers\Admin\CourseModuleController::class, 'create'])->name('course-modules.create');
    Route::post('course-online/{courseOnline}/modules', [App\Http\Controllers\Admin\CourseModuleController::class, 'store'])->name('course-modules.store');
    Route::get('course-online/{courseOnline}/modules/{courseModule}', [App\Http\Controllers\Admin\CourseModuleController::class, 'show'])->name('course-modules.show');
    Route::get('course-online/{courseOnline}/modules/{courseModule}/edit', [App\Http\Controllers\Admin\CourseModuleController::class, 'edit'])->name('course-modules.edit');
    Route::patch('course-online/{courseOnline}/modules/{courseModule}', [App\Http\Controllers\Admin\CourseModuleController::class, 'update'])->name('course-modules.update');
    Route::delete('course-online/{courseOnline}/modules/{courseModule}', [App\Http\Controllers\Admin\CourseModuleController::class, 'destroy'])->name('course-modules.destroy');
    Route::patch('course-online/{courseOnline}/modules/update-order', [App\Http\Controllers\Admin\CourseModuleController::class, 'updateOrder'])->name('course-modules.update-order');

    // Course Assignments Management
    Route::resource('course-assignments', CourseAssignmentController::class)->except(['edit', 'update']);
    Route::get('course-assignments/{courseAssignment}/users', [CourseAssignmentController::class, 'showUsers'])->name('course-assignments.users');
    Route::post('course-assignments/bulk-assign', [CourseAssignmentController::class, 'bulkAssign'])->name('course-assignments.bulk-assign');
    Route::get('course-assignments/statistics', [CourseAssignmentController::class, 'statistics'])->name('course-assignments.statistics');
    Route::patch('course-assignments/{courseAssignment}/toggle-status', [CourseAssignmentController::class, 'toggleStatus'])->name('course-assignments.toggle-status');

    // ===== AUDIO MANAGEMENT =====
    Route::resource('audio-categories', App\Http\Controllers\Admin\AudioCategoryController::class);
    Route::post('audio-categories/{audioCategory}/toggle-active', [App\Http\Controllers\Admin\AudioCategoryController::class, 'toggleActive'])->name('audio-categories.toggle-active');
    Route::resource('audio', App\Http\Controllers\Admin\AudioController::class);
    Route::post('/audio/{audio}/toggle-active', [App\Http\Controllers\Admin\AudioController::class, 'toggleActive'])->name('audio.toggle-active');

    // ===== AUDIO ASSIGNMENT MANAGEMENT =====
    Route::resource('audio-assignments', App\Http\Controllers\Admin\AudioAssignmentController::class)->except(['edit', 'update', 'show']);

   // ===== VIDEO MANAGEMENT =====
Route::resource('video-categories', VideoCategoryController::class);
Route::post('video-categories/{videoCategory}/toggle-active', [VideoCategoryController::class, 'toggleActive'])->name('video-categories.toggle-active');

Route::post('videos/{video}/update', [App\Http\Controllers\Admin\VideoController::class, 'update'])->name('videos.update');

Route::resource('videos', App\Http\Controllers\Admin\VideoController::class)->except('update');
Route::post('videos/{video}/toggle-active', [App\Http\Controllers\Admin\VideoController::class, 'toggleActive'])->name('videos.toggle-active');
Route::get('videos/{video}/streaming-url', [App\Http\Controllers\Admin\VideoController::class, 'getStreamingUrl'])->name('videos.streaming-url');
Route::post('videos/batch-refresh-urls', [App\Http\Controllers\Admin\VideoController::class, 'batchRefreshUrls'])->name('videos.batch-refresh-urls');
Route::post('videos/{video}/migrate-to-local', [App\Http\Controllers\Admin\VideoController::class, 'migrateToLocal'])->name('videos.migrate-to-local');
Route::post('videos/{video}/retry-transcode', [App\Http\Controllers\Admin\VideoController::class, 'retryTranscode'])->name('videos.retry-transcode');
Route::get('videos/{video}/subtitle/edit', [App\Http\Controllers\Admin\VideoController::class, 'editSubtitle'])->name('videos.subtitle.edit');
Route::post('videos/{video}/subtitle/update', [App\Http\Controllers\Admin\VideoController::class, 'updateSubtitle'])->name('videos.subtitle.update');
Route::post('videos/{video}/retry-subtitle', [App\Http\Controllers\Admin\VideoController::class, 'retrySubtitle'])->name('videos.retry-subtitle');

// ===== BLOG / PODCAST MANAGEMENT =====
Route::prefix('podcasts')->name('podcasts.')->group(function () {
    Route::get('/',                [App\Http\Controllers\Admin\PodcastController::class, 'index'])->name('index');
    Route::get('/create',          [App\Http\Controllers\Admin\PodcastController::class, 'create'])->name('create');
    Route::post('/',               [App\Http\Controllers\Admin\PodcastController::class, 'store'])->name('store');
    Route::get('/{podcast}/edit',  [App\Http\Controllers\Admin\PodcastController::class, 'edit'])->name('edit');
    Route::post('/{podcast}',      [App\Http\Controllers\Admin\PodcastController::class, 'update'])->name('update');
    Route::delete('/{podcast}',    [App\Http\Controllers\Admin\PodcastController::class, 'destroy'])->name('destroy');
    Route::post('/{podcast}/toggle-status', [App\Http\Controllers\Admin\PodcastController::class, 'toggleStatus'])->name('toggle-status');
});



    // ===== ASSIGNMENT MANAGEMENT =====
    Route::get('assignments', [App\Http\Controllers\Admin\AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('assignments/create', [App\Http\Controllers\Admin\AssignmentController::class, 'create'])->name('assignments.create');
    Route::post('assignments', [App\Http\Controllers\Admin\AssignmentController::class, 'store'])->name('assignments.store');
    Route::get('assignments/{assignment}', [App\Http\Controllers\Admin\AssignmentController::class, 'show'])->name('assignments.show');
    Route::get('assignments/{assignment}/edit', [App\Http\Controllers\Admin\AssignmentController::class, 'edit'])->name('assignments.edit');
    Route::put('assignments/{assignment}', [App\Http\Controllers\Admin\AssignmentController::class, 'update'])->name('assignments.update');
    Route::delete('assignments/{assignment}', [App\Http\Controllers\Admin\AssignmentController::class, 'destroy'])->name('assignments.destroy');
    Route::post('assignments/bulk', [App\Http\Controllers\Admin\AssignmentController::class, 'bulkAssign'])->name('assignments.bulk');

    // ===== QUIZ MANAGEMENT =====
    Route::resource('quizzes', \App\Http\Controllers\Admin\QuizController::class);
    Route::get('quiz-attempts', [\App\Http\Controllers\Admin\QuizAttemptController::class, 'index'])->name('quiz-attempts.index');
    Route::get('quiz-attempts/{attempt}', [\App\Http\Controllers\Admin\QuizAttemptController::class, 'show'])->name('quiz-attempts.show');
    Route::put('quiz-attempts/{attempt}', [\App\Http\Controllers\Admin\QuizAttemptController::class, 'update'])->name('quiz-attempts.update');
    Route::post('quiz-attempts/reset', [\App\Http\Controllers\Admin\QuizAttemptController::class, 'resetUserAttempts'])->name('quiz-attempts.reset');

    // ===== MODULE QUIZ MANAGEMENT =====
    Route::prefix('course-online/{courseOnline}/modules/{courseModule}/quiz')->name('module-quiz.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ModuleQuizController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\ModuleQuizController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\ModuleQuizController::class, 'store'])->name('store');
        Route::get('/{quiz}', [\App\Http\Controllers\Admin\ModuleQuizController::class, 'show'])->name('show');
        Route::get('/{quiz}/edit', [\App\Http\Controllers\Admin\ModuleQuizController::class, 'edit'])->name('edit');
        Route::put('/{quiz}', [\App\Http\Controllers\Admin\ModuleQuizController::class, 'update'])->name('update');
        Route::delete('/{quiz}', [\App\Http\Controllers\Admin\ModuleQuizController::class, 'destroy'])->name('destroy');
        Route::get('/{quiz}/attempts', [\App\Http\Controllers\Admin\ModuleQuizController::class, 'attempts'])->name('attempts');
        Route::get('/{quiz}/attempts/{attempt}', [\App\Http\Controllers\Admin\ModuleQuizController::class, 'showAttempt'])->name('attempts.show');
        Route::put('/{quiz}/attempts/{attempt}/grade', [\App\Http\Controllers\Admin\ModuleQuizController::class, 'gradeAttempt'])->name('grade-attempt');
        Route::post('/{quiz}/reset-attempts', [\App\Http\Controllers\Admin\ModuleQuizController::class, 'resetUserAttempts'])->name('reset-attempts');
        Route::get('/{quiz}/statistics', [\App\Http\Controllers\Admin\ModuleQuizController::class, 'statistics'])->name('statistics');
        Route::patch('/{quiz}/toggle-status', [\App\Http\Controllers\Admin\ModuleQuizController::class, 'toggleStatus'])->name('toggle-status');
    });

    // ===== QUIZ ASSIGNMENT =====
    Route::get('quiz-assignments/create', [\App\Http\Controllers\Admin\QuizAssignmentController::class, 'create'])->name('quiz-assignments.create');
    Route::post('quiz-assignments', [\App\Http\Controllers\Admin\QuizAssignmentController::class, 'store'])->name('quiz-assignments.store');
    Route::post('quiz-assignments/notify', [\App\Http\Controllers\Admin\QuizAssignmentController::class, 'notify'])->name('quiz-assignments.notify');

    // ===== ATTENDANCE MANAGEMENT =====
    Route::get('attendance', [AdminAttendanceController::class, 'index'])->name('attendance.index');
    Route::put('/attendance/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');
    Route::delete('/attendance/{attendance}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');

    // ===== INSTRUCTION MANAGEMENT =====
    Route::resource('instructions', \App\Http\Controllers\Admin\InstructionController::class);

    // ===== DEPARTMENT MANAGEMENT =====
    Route::resource('departments', DepartmentController::class);
    Route::get('/departments/{department}/managers', [DepartmentController::class, 'getManagerCandidates'])->name('departments.manager-candidates');
    Route::post('/departments/{department}/assign-manager', [DepartmentController::class, 'assignManager'])->name('departments.assign-manager');
    Route::delete('/departments/{department}/remove-manager/{role}', [DepartmentController::class, 'removeManager'])->name('departments.remove-manager');
    Route::get('/departments/{department}/hierarchy', [DepartmentController::class, 'getHierarchy'])->name('departments.hierarchy');
    Route::get('/departments/{department}/employees', [UserDepartmentRoleController::class, 'getEmployees'])->name('api.departments.employees');


    // ===== USER LEVELS MANAGEMENT =====
    Route::resource('user-levels', UserLevelController::class);
    Route::post('/user-levels/bulk-assign', [UserLevelController::class, 'bulkAssign'])->name('user-levels.bulk-assign');
    Route::get('/user-levels/{userLevel}/users', [UserLevelController::class, 'getUsers'])->name('user-levels.users');
    Route::post('/user-levels/remove-user', [UserLevelController::class, 'removeUserFromLevel'])->name('user-levels.remove-user');

    // ===== USER LEVEL TIERS MANAGEMENT =====
    Route::prefix('user-levels/{userLevel}/tiers')->name('user-level-tiers.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\UserLevelTierController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\UserLevelTierController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\UserLevelTierController::class, 'store'])->name('store');
        Route::get('/{tier}', [App\Http\Controllers\Admin\UserLevelTierController::class, 'show'])->name('show');
        Route::get('/{tier}/edit', [App\Http\Controllers\Admin\UserLevelTierController::class, 'edit'])->name('edit');
        Route::put('/{tier}', [App\Http\Controllers\Admin\UserLevelTierController::class, 'update'])->name('update');
        Route::delete('/{tier}', [App\Http\Controllers\Admin\UserLevelTierController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-create-default', [App\Http\Controllers\Admin\UserLevelTierController::class, 'bulkCreateDefault'])->name('bulk-create-default');
    });

    // ===== MANAGER ROLES MANAGEMENT =====
    Route::get('/manager-roles', [UserDepartmentRoleController::class, 'index'])->name('manager-roles.index');
    Route::get('/manager-roles/create', [UserDepartmentRoleController::class, 'create'])->name('manager-roles.create');
    Route::post('/manager-roles', [UserDepartmentRoleController::class, 'store'])->name('manager-roles.store');
    Route::get('/manager-roles/{userDepartmentRole}', [UserDepartmentRoleController::class, 'show'])->name('manager-roles.show');
    Route::get('/manager-roles/{userDepartmentRole}/edit', [UserDepartmentRoleController::class, 'edit'])->name('manager-roles.edit');
    Route::put('/manager-roles/{userDepartmentRole}', [UserDepartmentRoleController::class, 'update'])->name('manager-roles.update');
    Route::delete('/manager-roles/{userDepartmentRole}', [UserDepartmentRoleController::class, 'destroy'])->name('manager-roles.destroy');
    Route::get('/manager-roles/matrix/view', [UserDepartmentRoleController::class, 'matrix'])->name('manager-roles.matrix');
    Route::get('/departments/{department}/employees', [UserDepartmentRoleController::class, 'getDepartmentEmployees'])->name('departments.employees');
    Route::post('/manager-roles/{userDepartmentRole}/extend', [UserDepartmentRoleController::class, 'extend'])->name('manager-roles.extend');
    Route::post('/manager-roles/{userDepartmentRole}/terminate', [UserDepartmentRoleController::class, 'terminate'])->name('manager-roles.terminate');
    Route::get('/manager-roles/user/{user}/roles', [UserDepartmentRoleController::class, 'getUserRoles'])->name('manager-roles.user-roles');

    // ===== ORGANIZATIONAL DASHBOARD =====
    Route::get('/organizational', [OrganizationalController::class, 'index'])->name('organizational.index');
    Route::get('/organizational/overview', [OrganizationalController::class, 'overview'])->name('organizational.overview');

    // ===== EVALUATION SYSTEM =====
    Route::get('/evaluations', [EvaluationController::class, 'index'])->name('evaluations.index');
    Route::post('/evaluations', [EvaluationController::class, 'store'])->name('evaluations.store');
    Route::put('/evaluations/{evaluationConfig}', [EvaluationController::class, 'update'])->name('evaluations.update');
    Route::delete('/evaluations/{evaluationConfig}', [EvaluationController::class, 'destroy'])->name('evaluations.destroy');
    Route::post('/evaluations/{evaluationConfig}/types', [EvaluationController::class, 'configureTypes'])->name('evaluations.types.store');
    Route::delete('/evaluations/types/{evaluationType}', [EvaluationController::class, 'destroyType'])->name('evaluations.types.destroy');
    Route::post('/evaluations/set-total-score', [EvaluationController::class, 'setTotalScore'])->name('evaluations.set-total-score');
    Route::post('/evaluations/set-incentives', [EvaluationController::class, 'setIncentives'])->name('evaluations.set-incentives');
    Route::get('/evaluations/users-by-department', [UserEvaluationController::class, 'getUsersByDepartment'])->name('evaluations.users-by-department');
    Route::get('/evaluations/user-courses', [UserEvaluationController::class, 'getUserCourses'])->name('evaluations.user-courses');

    // User Evaluation Routes
    Route::get('/evaluations/user-evaluation', [UserEvaluationController::class, 'index'])->name('evaluations.user-evaluation');
    Route::get('/evaluations/user-evaluation/{userId}', [UserEvaluationController::class, 'show'])->name('evaluations.user-evaluation.show');
    Route::post('/evaluations/user-evaluation', [UserEvaluationController::class, 'store'])->name('evaluations.user-evaluation.store');
    Route::post('/evaluations/user-evaluation/bulk', [UserEvaluationController::class, 'bulkStore'])->name('evaluations.user-evaluation.bulk-store');
    Route::post('/evaluations/user-evaluation/filter', [UserEvaluationController::class, 'filterUsers'])->name('evaluations.user-evaluation.filter');

    // Evaluation History Routes
    Route::get('/evaluations/history', [HistoryController::class, 'index'])->name('evaluations.history');
    Route::get('/evaluations/history/export', [HistoryController::class, 'export'])->name('evaluations.history.export');
    Route::get('/evaluations/history/export-summary', [HistoryController::class, 'exportSummary'])->name('evaluations.history.export-summary');
    Route::get('/evaluations/history/{evaluationId}', [HistoryController::class, 'details'])->name('evaluations.history.details');


    // ===== NEW: ONLINE COURSE EVALUATION ROUTES (ADD THESE) =====
    Route::prefix('evaluations/online')->name('evaluations.online.')->group(function () {
        // Main page - List/Create evaluations for online courses
        Route::get('/', [OnlineCourseEvaluationController::class, 'index'])->name('index');

        // Store online course evaluation
        Route::post('/', [OnlineCourseEvaluationController::class, 'store'])->name('store');

        // Get users by department (for filtering)
        Route::get('/users-by-department', [OnlineCourseEvaluationController::class, 'getUsersByDepartment'])->name('users-by-department');

        // Get user's completed online courses
        Route::get('/user-courses', [OnlineCourseEvaluationController::class, 'getUserOnlineCourses'])->name('user-courses');
    });

    // Evaluation Notification Routes
    Route::get('/evaluations/notifications', [EvaluationNotificationController::class, 'index'])->name('evaluations.notifications');
    Route::post('/evaluations/notifications/filter', [EvaluationNotificationController::class, 'filterEmployees'])->name('evaluations.notifications.filter');
    Route::post('/evaluations/notifications/preview', [EvaluationNotificationController::class, 'previewNotification'])->name('evaluations.notifications.preview');
    Route::post('/evaluations/notifications/send', [EvaluationNotificationController::class, 'sendNotifications'])->name('evaluations.notifications.send');
    Route::get('/evaluations/notifications/history', [EvaluationNotificationController::class, 'history'])->name('evaluations.notifications.history');
    Route::get('/evaluations/notifications/{id}', [EvaluationNotificationController::class, 'show'])->name('evaluations.notifications.show');

    // ===== REPORTS & ANALYTICS =====
    Route::prefix('reports')->name('reports.')->group(function () {
        // Main reports
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/course-registrations', [ReportController::class, 'courseRegistrations'])->name('course-registrations');
        Route::get('/attendance', [ReportController::class, 'attendance'])->name('attendance');
        Route::get('/course-completion', [ReportController::class, 'courseCompletion'])->name('course-completion');
        Route::get('/quiz-attempts', [ReportController::class, 'quizAttempts'])->name('quiz-attempts');

        // Monthly KPI Dashboard
        Route::get('/monthly-kpi', [ReportController::class, 'monthlyKpiDashboard'])->name('monthly-kpi');
        Route::get('/kpi-data', [ReportController::class, 'getKpiData'])->name('kpi-data');
        Route::get('/kpi-comparison', [ReportController::class, 'getKpiComparison'])->name('kpi-comparison');
        Route::get('/kpi-section/{section}', [ReportController::class, 'getKpiSection'])->name('kpi-section');
        Route::get('/kpi-trends', [ReportController::class, 'getKpiTrends'])->name('kpi-trends');
        Route::get('/live-kpi-stats', [ReportController::class, 'getLiveKpiStats'])->name('live-kpi-stats');

        // Export routes
        Route::get('/export-monthly-kpi-csv', [ReportController::class, 'exportMonthlyKpiCsv'])->name('export-monthly-kpi-csv');
        Route::get('/monthly-kpi-screenshot', [ReportController::class, 'monthlyKpiScreenshot'])->name('monthly-kpi-screenshot');
        Route::post('/export-monthly-kpi', [ReportController::class, 'exportMonthlyKpiReport'])->name('export-monthly-kpi');
        Route::get('/export/course-registrations', [ReportController::class, 'exportCourseRegistrations'])->name('export.course-registrations');
        Route::get('/export/attendance', [ReportController::class, 'exportAttendance'])->name('export.attendance');
        Route::get('/export/course-completion', [ReportController::class, 'exportCourseCompletion'])->name('export.course-completion');
        Route::get('/quiz-attempts/export', [ReportController::class, 'exportQuizAttempts'])->name('export.quiz-attempts');
        Route::get('/quiz-detailed/export', [\App\Http\Controllers\Admin\QuizExportController::class, 'exportDetailed'])->name('export.quiz-detailed');

        // Organizational reports
        Route::get('/organizational', [OrganizationalController::class, 'reports'])->name('organizational');
        Route::get('/department-structure', [OrganizationalController::class, 'departmentStructure'])->name('department-structure');
        Route::get('/management-hierarchy', [OrganizationalController::class, 'managementHierarchy'])->name('management-hierarchy');
        Route::get('/user-assignments', [OrganizationalController::class, 'userAssignments'])->name('user-assignments');
    });
});


// ==========================================
// API ROUTES FOR AJAX CALLS
// ==========================================

Route::middleware(['auth'])->prefix('api')->name('api.')->group(function () {

    // ===== COURSE ONLINE PROGRESS TRACKING =====
    Route::prefix('courses-online')->name('courses-online.')->group(function () {

        // Real-time progress updates
        Route::post('/{courseOnline}/progress', [ProgressController::class, 'updateCourseProgress'])->name('progress');

        // Module progress
        Route::post('/{courseOnline}/modules/{courseModule}/progress', [ProgressController::class, 'updateModuleProgress'])->name('modules.progress');

        // Content progress
        Route::post('/content/{content}/progress', [ProgressController::class, 'updateContentProgress'])->name('content.progress');

        // Learning session tracking
        Route::post('/content/{content}/session/start', [ProgressController::class, 'startLearningSession'])->name('session.start');
        Route::post('/content/{content}/session/end', [ProgressController::class, 'endLearningSession'])->name('session.end');
        Route::post('/content/{content}/session/heartbeat', [ProgressController::class, 'sessionHeartbeat'])->name('session.heartbeat');

        // Get user progress
        Route::get('/{courseOnline}/user-progress', [ProgressController::class, 'getUserProgress'])->name('user-progress');


        // Check content accessibility
        Route::get('/content/{content}/accessibility', [ProgressController::class, 'checkContentAccessibility'])->name('content.accessibility');

        Route::post('/content/{content}/pdf/progress', [ContentViewController::class, 'updatePdfProgress'])
            ->name('content.pdf.progress');
        Route::post('/content/{content}/pdf/session/start', [ContentViewController::class, 'startPdfSession'])
            ->name('content.pdf.session.start');
        Route::post('/content/{content}/pdf/session/end', [ContentViewController::class, 'endPdfSession'])
            ->name('content.pdf.session.end');

    });

    // ===== TRADITIONAL PROGRESS TRACKING =====
    Route::post('progress/video', [ProgressController::class, 'updateVideoProgress'])->name('progress.video');
    Route::post('progress/pdf', [ProgressController::class, 'updatePdfProgress'])->name('progress.pdf');

    // ===== DEPARTMENT API =====
    Route::get('/departments/search', [DepartmentController::class, 'search'])->name('departments.search');
    Route::get('/departments/{department}/children', [DepartmentController::class, 'getChildren'])->name('departments.children');
    Route::get('/departments/hierarchy', [DepartmentController::class, 'getFullHierarchy'])->name('departments.full-hierarchy');

    // ===== USER API =====
    Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
    Route::get('/users/by-department/{department}', [UserController::class, 'getByDepartment'])->name('users.by-department');
    Route::get('/users/managers', [UserController::class, 'getManagers'])->name('users.managers');
    Route::get('/users/{user}/manager-chain', [UserController::class, 'getManagerChain'])->name('users.manager-chain');

    // ===== MANAGER ROLE API =====
    Route::get('/manager-roles/by-department/{department}', [UserDepartmentRoleController::class, 'getByDepartment'])->name('manager-roles.by-department');
    Route::get('/manager-roles/check-conflict', [UserDepartmentRoleController::class, 'checkConflict'])->name('manager-roles.check-conflict');
});

// ==========================================
// INCLUDE EXTERNAL ROUTE FILES
// ==========================================

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
