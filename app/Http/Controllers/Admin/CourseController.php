<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CourseCompletionNotification;
use App\Mail\CourseCreatedNotification;
use App\Mail\CoursePrivacyChangeNotification;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseAvailability;
use App\Models\User;
use App\Mail\CoursePublicAnnouncement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CourseController extends Controller
{
    /**
     * Display a listing of courses
     */
    public function index()
    {
        $courses = Course::with(['availabilities', 'registrations'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'description' => $course->description,
                    'start_date' => $course->start_date,
                    'end_date' => $course->end_date,
                    'status' => $course->status,
                    'privacy' => $course->privacy,
                    'level' => $course->level,
                    'duration' => $course->duration,
                    'image_path' => $course->image_path,
                    'enrolled_count' => $course->total_enrollment,
                    'availabilities_count' => $course->availabilities->count(),
                    'available_spots' => $course->availabilities->sum('capacity') - $course->total_enrollment
                ];
            });

        return Inertia::render('Admin/Courses/Index', [
            'courses' => $courses
        ]);
    }

    /**
     * Show the form for creating a new course
     */
    public function create()
    {
        return Inertia::render('Admin/Courses/Create');
    }

    /**
     * Store a newly created course
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:pending,in_progress,completed',
            'privacy' => 'required|in:public,private',
            'level' => 'nullable|in:beginner,intermediate,advanced',
            'duration' => 'nullable|numeric|min:0.1',

            // Course availabilities validation - FIXED
            'availabilities' => 'required|array|min:1|max:5',
            'availabilities.*.start_date' => 'required|date|after:now',
            'availabilities.*.end_date' => 'required|date|after:availabilities.*.start_date',
            'availabilities.*.capacity' => 'required|integer|min:1|max:1000',
            'availabilities.*.sessions' => 'nullable|integer|min:1|max:100',
            'availabilities.*.notes' => 'nullable|string|max:500',

            // FIXED: Handle days_of_week properly - expect string (comma-separated)
            'availabilities.*.days_of_week' => ['required',  function ($attribute, $value, $fail) {
                $validDays = CourseAvailability::getAvailableDays();

                // Handle both array and string formats
                if (is_array($value)) {
                    $selectedDays = $value;
                } else {
                    $selectedDays = explode(',', $value);
                }

                // Remove empty values
                $selectedDays = array_filter($selectedDays);

                if (empty($selectedDays)) {
                    $fail('At least one day must be selected.');
                    return;
                }

                foreach ($selectedDays as $day) {
                    if (!in_array(trim($day), $validDays)) {
                        $fail('The selected day "' . $day . '" is invalid.');
                    }
                }
            }],
            'availabilities.*.duration_weeks' => 'required|integer|min:1|max:52',
            'availabilities.*.session_time_shift_2' => 'nullable|date_format:H:i',
            'availabilities.*.session_time_shift_3' => 'nullable|date_format:H:i',
            'availabilities.*.session_duration_minutes' => 'nullable|integer|min:15|max:480',
        ]);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('courses', 'public');
        }

        // Format dates
        $startDate = $validated['start_date'] ? Carbon::parse($validated['start_date'])->format('Y-m-d') : null;
        $endDate = $validated['end_date'] ? Carbon::parse($validated['end_date'])->format('Y-m-d') : null;

        // Create course
        $course = Course::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'image_path' => $imagePath,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $validated['status'],
            'privacy' => $validated['privacy'],
            'level' => $validated['level'],
            'duration' => $validated['duration'],
        ]);

        // Create course availabilities - FIXED
        foreach ($validated['availabilities'] as $availability) {
            // FIXED: Handle days_of_week conversion properly
            $daysOfWeek = $availability['days_of_week'];
            if (is_array($daysOfWeek)) {
                $daysOfWeek = implode(',', array_filter($daysOfWeek));
            }

            $courseAvailability = CourseAvailability::create([
                'course_id' => $course->id,
                'start_date' => Carbon::parse($availability['start_date']),
                'end_date' => Carbon::parse($availability['end_date']),
                'capacity' => $availability['capacity'],
                'sessions' => $availability['sessions'] ?? 1,
                'status' => 'active',
                'notes' => $availability['notes'] ?? null,

                // NEW SCHEDULING FIELDS - FIXED
                'days_of_week' => $daysOfWeek, // Now properly converted to string
                'duration_weeks' => $availability['duration_weeks'],
                'session_time' => $availability['session_time'] ?? null,'session_time_shift_2' => $availability['session_time_shift_2'] ?? null,
                'session_time_shift_3' => $availability['session_time_shift_3'] ?? null,
                'session_duration_minutes' => $availability['session_duration_minutes'] ?? 60,
            ]);

            // Auto-calculate end_date and sessions if needed

        }

        if ($course->privacy === 'public') {
            $this->notifyAllUsersOfPublicCourse($course);
        }

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course created successfully with ' . count($validated['availabilities']) . ' availability periods.');
    }

    /**
     * Display the specified course
     */
    public function show(Course $course)
    {
        $course = Course::with([
            'availabilities.registrations.user',
            'registrations.user',
            'registrations.courseAvailability'
        ])->findOrFail($course->id);

        $availabilities = $course->availabilities->map(function ($availability) {
            return [
                'id' => $availability->id,
                'start_date' => $availability->start_date,
                'end_date' => $availability->end_date,
                'formatted_date_range' => $availability->formatted_date_range,
                'capacity' => $availability->capacity,
                'enrolled_count' => $availability->enrolled_count,
                'available_spots' => $availability->available_spots,
                'status' => $availability->status,
                'notes' => $availability->notes,
                'is_full' => $availability->is_full,
                'is_expired' => $availability->is_expired,

                // NEW SCHEDULING FIELDS FOR DISPLAY
                'days_of_week' => $availability->days_of_week,
                'formatted_days' => $availability->formatted_days ?? 'N/A',
                'duration_weeks' => $availability->duration_weeks,

                // FIXED: Check if fields exist and are not null before formatting
                'session_time_shift_2' => $availability->session_time_shift_2 ?
                    $availability->session_time_shift_2->format('H:i') : null,
                'session_time_shift_3' => $availability->session_time_shift_3 ?
                    $availability->session_time_shift_3->format('H:i') : null,

                // Add formatted session times for convenience
                'formatted_session_times' => $availability->formatted_session_times ?? 'No times set',

                'session_duration' => $availability->formatted_session_duration,

                'enrollments' => $availability->registrations->map(function ($registration) {
                    return [
                        'id' => $registration->id,
                        'user' => [
                            'id' => $registration->user->id,
                            'name' => $registration->user->name,
                            'email' => $registration->user->email,
                        ],
                        'user_status' => $registration->user_status,
                        'created_at' => $registration->created_at->toDateTimeString()
                    ];
                })
            ];
        });

        return Inertia::render('Admin/Courses/Show', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'description' => $course->description,
                'status' => $course->status,
                'privacy' => $course->privacy,
                'level' => $course->level,
                'duration' => $course->duration,
                'created_at' => $course->created_at->toDateTimeString(),
            ],
            'availabilities' => $availabilities
        ]);
    }

    /**
     * Show the form for editing the specified course
     */
    public function edit(Course $course)
    {
        $course->load('availabilities');

        return Inertia::render('Admin/Courses/Edit', [
            'course' => $course
        ]);
    }

    /**
     * Update the specified course
     */
    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // ✅ Changed from 2048 to 10240 (10MB)
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:pending,in_progress,completed',
            'privacy' => 'required|in:public,private',
            'level' => 'nullable|in:beginner,intermediate,advanced',
            'duration' => 'nullable|numeric|min:0.1',

            // Course availabilities validation - FIXED
            'availabilities' => 'required|array|min:1|max:5',
            'availabilities.*.id' => 'nullable|exists:course_availabilities,id',
            'availabilities.*.start_date' => 'required|date|after:now',
            'availabilities.*.end_date' => 'required|date|after:availabilities.*.start_date',
            'availabilities.*.capacity' => 'required|integer|min:1|max:1000',
            'availabilities.*.sessions' => 'required|integer|min:1|max:100',
            'availabilities.*.notes' => 'nullable|string|max:500',
            'availabilities.*.status' => 'nullable|in:active,closed',

            // FIXED: Handle days_of_week properly - expect string (comma-separated)
            'availabilities.*.days_of_week' => ['required',  function ($attribute, $value, $fail) {
                $validDays = CourseAvailability::getAvailableDays();

                // Handle both array and string formats
                if (is_array($value)) {
                    $selectedDays = $value;
                } else {
                    $selectedDays = explode(',', $value);
                }

                // Remove empty values
                $selectedDays = array_filter($selectedDays);

                if (empty($selectedDays)) {
                    $fail('At least one day must be selected.');
                    return;
                }

                foreach ($selectedDays as $day) {
                    if (!in_array(trim($day), $validDays)) {
                        $fail('The selected day "' . $day . '" is invalid.');
                    }
                }
            }],
            'availabilities.*.duration_weeks' => 'required|integer|min:1|max:52',
            'availabilities.*.session_time_shift_2' => 'nullable|date_format:H:i',
            'availabilities.*.session_time_shift_3' => 'nullable|date_format:H:i',
            'availabilities.*.session_duration_minutes' => 'nullable|integer|min:15|max:480',
        ]);



        // Detect privacy change from private to public
        $wasPrivate = $course->privacy === 'private';
        $isNowPublic = $validated['privacy'] === 'public';
        $privacyChangedToPublic = $wasPrivate && $isNowPublic;

        // Get previously assigned users BEFORE updating the course
        $previouslyAssignedUserIds = [];
        if ($privacyChangedToPublic) {
            $previouslyAssignedUserIds = CourseAssignment::where('course_id', $course->id)
                ->pluck('user_id')
                ->toArray();


        }

        // Handle image upload
        if ($request->hasFile('image')) {
            if ($course->image_path) {
                Storage::disk('public')->delete($course->image_path);
            }
            $imagePath = $request->file('image')->store('courses', 'public');
        } else {
            $imagePath = $course->image_path;
        }

        // Format dates
        $startDate = $validated['start_date'] ? Carbon::parse($validated['start_date'])->format('Y-m-d') : null;
        $endDate = $validated['end_date'] ? Carbon::parse($validated['end_date'])->format('Y-m-d') : null;

        // Update course
        $course->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'image_path' => $imagePath,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $validated['status'],
            'privacy' => $validated['privacy'],
            'level' => $validated['level'],
            'duration' => $validated['duration'],
        ]);


        // Handle availabilities updates - FIXED
        $existingAvailabilityIds = [];

        foreach ($validated['availabilities'] as $availabilityData) {
            // FIXED: Handle days_of_week conversion properly
            $daysOfWeek = $availabilityData['days_of_week'];
            if (is_array($daysOfWeek)) {
                $daysOfWeek = implode(',', array_filter($daysOfWeek));
            }

            if (isset($availabilityData['id'])) {
                // Update existing availability
                $availability = CourseAvailability::find($availabilityData['id']);
                if ($availability && $availability->course_id === $course->id) {
                    $availability->update([
                        'start_date' => Carbon::parse($availabilityData['start_date']),
                        'end_date' => Carbon::parse($availabilityData['end_date']),
                        'capacity' => $availabilityData['capacity'],
                        'sessions' => $availabilityData['sessions'],
                        'status' => $availabilityData['status'] ?? 'active',
                        'notes' => $availabilityData['notes'] ?? null,

                        // NEW SCHEDULING FIELDS - FIXED
                        'days_of_week' => $daysOfWeek, // Now properly converted to string
                        'duration_weeks' => $availabilityData['duration_weeks'],
                        'session_time_shift_2' => $availability['session_time_shift_2'] ?? null,
                        'session_time_shift_3' => $availability['session_time_shift_3'] ?? null,
                        'session_duration_minutes' => $availabilityData['session_duration_minutes'] ?? 60,
                    ]);

                    // Auto-calculate end_date and sessions if needed


                    $existingAvailabilityIds[] = $availability->id;
                }
            } else {
                // Create new availability
                $availability = CourseAvailability::create([
                    'course_id' => $course->id,
                    'start_date' => Carbon::parse($availabilityData['start_date']),
                    'end_date' => Carbon::parse($availabilityData['end_date']),
                    'capacity' => $availabilityData['capacity'],
                    'sessions' => $availabilityData['sessions'],
                    'status' => $availabilityData['status'] ?? 'active',
                    'notes' => $availabilityData['notes'] ?? null,

                    // NEW SCHEDULING FIELDS - FIXED
                    'days_of_week' => $daysOfWeek, // Now properly converted to string
                    'duration_weeks' => $availabilityData['duration_weeks'],
                    'session_time' => $availabilityData['session_time'] ?? null,
                    'session_duration_minutes' => $availabilityData['session_duration_minutes'] ?? 60,
                ]);

                // Auto-calculate end_date and sessions if needed


                $existingAvailabilityIds[] = $availability->id;
            }
        }

        // Delete removed availabilities (only if they have no enrollments)
        $course->availabilities()
            ->whereNotIn('id', $existingAvailabilityIds)
            ->whereDoesntHave('registrations')
            ->delete();

        // Handle privacy change notification
        if ($privacyChangedToPublic) {


            $this->notifyUsersOnPrivacyChangeToPublic($course, $previouslyAssignedUserIds);
        }

        $successMessage = 'Course updated successfully.';
        if ($privacyChangedToPublic) {
            $successMessage .= ' All eligible users have been notified about the public availability.';
        }

        return redirect()->route('admin.courses.index')
            ->with('success', $successMessage);
    }

    /**
     * Notify users when course changes from private to public
     */
    private function notifyUsersOnPrivacyChangeToPublic(Course $course, array $excludedUserIds = [])
    {
        try {
            $query = User::query();

            if (!empty($excludedUserIds)) {
                $query->whereNotIn('id', $excludedUserIds);
            }

            $users = $query->get();



            if ($users->count() === 0) {

                return;
            }

            $successCount = 0;
            $failureCount = 0;

            foreach ($users as $user) {
                try {
                    if (empty($user->email)) {
                        continue;
                    }

                    $loginLink = method_exists($user, 'generateLoginLink')
                        ? $user->generateLoginLink($course->id)
                        : route('courses.show', $course->id);

                    Mail::to($user->email)->send(new CourseCreatedNotification($course, $user, $loginLink));

                    $successCount++;

                } catch (\Exception $e) {
                    $failureCount++;
                }

                usleep(200000); // 0.2 second delay
            }



        } catch (\Exception $e) {

        }
    }

    /**
     * Remove the specified course
     */
    public function destroy(Course $course)
    {
        if ($course->image_path) {
            Storage::disk('public')->delete($course->image_path);
        }

        $course->delete();

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course deleted successfully.');
    }

    private function notifyAllUsersOfPublicCourse(Course $course)
    {
        try {
            $users = User::where('role', '!=', 'admin')->get();



            $successCount = 0;
            $failureCount = 0;

            foreach ($users as $user) {
                try {
                    $loginLink = $user->generateLoginLink($course->id);
                    Mail::to($user->email)->send(new CoursePublicAnnouncement($course, $user, $loginLink));

                    $successCount++;

                } catch (\Exception $e) {
                    $failureCount++;
                }

                sleep(1);
            }



        } catch (\Exception $e) {

        }
    }
}
