<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Services\GoogleDriveService;
use App\Services\ThumbnailService;
use App\Services\FileUploadService;
use App\Services\VideoStorageService; // ✅ NEW
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\VideoCategory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoController extends Controller
{
    // ✅ UPDATED: Inject VideoStorageService
    public function __construct(
        protected GoogleDriveService $googleDriveService,
        protected ThumbnailService $thumbnailService,
        protected FileUploadService $fileService,
        protected VideoStorageService $videoStorageService // ✅ NEW
    ) {}

    /**
     * Display a listing of videos
     * ✅ UPDATED: Include storage_type in response
     */
    public function index()
    {
        $videos = Video::with(['creator', 'category', 'qualities'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($video) {
                return [
                    'id' => $video->id,
                    'name' => $video->name,
                    'description' => $video->description,
                    'duration' => $video->duration,
                    'formatted_duration' => $video->formatted_duration,
                    'streaming_url' => $video->streaming_url,
                    'google_drive_url' => $video->google_drive_url,
                    'is_active' => $video->is_active,

                    // ✅ NEW: Storage information
                    'storage_type' => $video->storage_type,
                    'storage_type_label' => $video->getStorageTypeLabel(),
                    'file_size' => $video->file_size,
                    'formatted_file_size' => $video->formatted_file_size,

                    // ✅ NEW: VPS Transcoding information
                    'transcode_status' => $video->transcode_status,
                    'available_qualities' => $video->getAvailableQualities(),
                    'has_multiple_qualities' => $video->hasMultipleQualities(),

                    // Subtitle information
                    'subtitle_status' => $video->subtitle_status,
                    'subtitle_vtt_path' => $video->subtitle_vtt_path,

                    'category' => $video->category ? [
                        'id' => $video->category->id,
                        'name' => $video->category->name,
                    ] : null,
                    'creator' => [
                        'id' => $video->creator->id,
                        'name' => $video->creator->name,
                    ],
                    'created_at' => $video->created_at->toDateTimeString(),
                ];
            });

        $categories = VideoCategory::active()
            ->ordered()
            ->get()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
            ]);

        // ✅ NEW: Storage statistics
        $storageStats = $this->videoStorageService->getStorageStats();

        $localStorageStats = $this->getLocalVideoStorageStats();

        return Inertia::render('Admin/Video/Index', [
            'videos' => $videos,
            'categories' => $categories,
            'storageStats' => $localStorageStats, // ✅ NEW
        ]);
    }

    /**
     * Show the form for creating a new video
     * ✅ UPDATED: Pass storage options
     */
    public function create()
    {
        $categories = VideoCategory::active()
            ->ordered()
            ->get()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
            ]);

        return Inertia::render('Admin/Video/Create', [
            'categories' => $categories,
            // ✅ NEW: Storage configuration
            'storageOptions' => [
                ['value' => 'google_drive', 'label' => 'Google Drive'],
                ['value' => 'local', 'label' => 'Local Storage'],
            ],
            'maxFileSize' => 10240000, // 10,000 MB (10GB) in KB
            'allowedMimes' => ['mp4', 'webm', 'avi', 'mov', 'mkv'],
        ]);
    }

    /**
     * Store a newly created video
     * ✅ UPDATED: Handle both Google Drive and local storage
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:2000',
        'duration' => 'nullable|integer|min:1|max:86400',
        'content_category_id' => 'nullable|exists:content_categories,id',
        'is_active' => 'boolean',
        'storage_type' => 'required|in:google_drive,local',
        'google_drive_url' => 'required_if:storage_type,google_drive|nullable|url|max:500',
        
        // ✅ CHANGED: Accept video_data as string (JSON from ChunkUploader)
        'video_data' => 'required_if:storage_type,local|nullable|string',
    ]);

    DB::beginTransaction();

    try {
        $videoData = [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'duration' => $validated['duration'],
            'content_category_id' => $validated['content_category_id'],
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth()->id(),
            'storage_type' => $validated['storage_type'],
        ];

        // Google Drive route (unchanged)
        if ($validated['storage_type'] === 'google_drive') {
            if (!$validated['google_drive_url']) {
                throw new \Exception('Google Drive URL is required');
            }

            $streamingUrl = $this->googleDriveService->processUrl($validated['google_drive_url']);

            if (!$streamingUrl) {
                throw new \Exception('Could not process Google Drive URL.');
            }

            $videoData['google_drive_url'] = $validated['google_drive_url'];
            $videoData['streaming_url'] = $streamingUrl;
        }

        // ✅ LOCAL STORAGE: Handle chunked upload response
        elseif ($validated['storage_type'] === 'local') {
            if (empty($validated['video_data'])) {
                throw new \Exception('Video file data is required for local storage');
            }
            
            // Parse the JSON response from ChunkUploader
            $uploadedFileData = json_decode($validated['video_data'], true);
            
            if (!$uploadedFileData || !isset($uploadedFileData['path'])) {
                throw new \Exception('Invalid video upload data. Please try uploading again.');
            }
            
            // Save the metadata from chunked upload
            $videoData['file_path'] = $uploadedFileData['path'];
            $videoData['file_size'] = $uploadedFileData['size'] ?? null;
            $videoData['mime_type'] = $uploadedFileData['mime_type'] ?? 'video/mp4';
            
            if (!$videoData['duration']) {
                $videoData['duration'] = null;
            }
        }

        // Create video record
        $video = Video::create($videoData);

        // Trigger transcoding for local videos (async - doesn't block response)
        if ($validated['storage_type'] === 'local') {
            app(\App\Services\VpsTranscodingService::class)->requestTranscoding($video);
            // app(\App\Services\VpsSubtitleService::class)->requestSubtitle($video);
        }

        DB::commit();

        return redirect()->route('admin.videos.index')
            ->with('success', 'Video created successfully.');

    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('Video creation failed:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return redirect()->back()
            ->withInput()
            ->with('error', $e->getMessage());
    }
}


    /**
     * Display the specified video
     * ✅ UPDATED: Handle both storage types
     */
    public function show(Video $video)
    {
        $video->load(['creator', 'qualities']);

        // ✅ UPDATED: Only refresh Google Drive streaming URL
        $currentStreamingUrl = null;
        if ($video->isGoogleDrive() && $video->google_drive_url) {
            $currentStreamingUrl = $this->googleDriveService->processUrl($video->google_drive_url);
            if ($currentStreamingUrl && $currentStreamingUrl !== $video->streaming_url) {
                $video->update(['streaming_url' => $currentStreamingUrl]);
            }
        }

        // ✅ NEW: Prepare quality variants data
        $qualityVariants = $video->qualities->map(function ($quality) {
            return [
                'quality' => $quality->quality,
                'file_path' => $quality->file_path,
                'file_size' => $quality->file_size,
                'formatted_file_size' => $quality->formatted_file_size,
            ];
        });

        return Inertia::render('Admin/Video/Show', [
            'video' => [
                'id' => $video->id,
                'name' => $video->name,
                'description' => $video->description,
                'google_drive_url' => $video->google_drive_url,
                'streaming_url' => $currentStreamingUrl,
                'duration' => $video->duration,
                'formatted_duration' => $video->formatted_duration,
                'thumbnail_url' => null,
                'is_active' => $video->is_active,

                // ✅ NEW: Storage information
                'storage_type' => $video->storage_type,
                'storage_type_label' => $video->getStorageTypeLabel(),
                'file_size' => $video->file_size,
                'formatted_file_size' => $video->formatted_file_size,

                // ✅ NEW: VPS Transcoding information
                'transcode_status' => $video->transcode_status,
                'available_qualities' => $video->getAvailableQualities(),
                'has_multiple_qualities' => $video->hasMultipleQualities(),
                'quality_variants' => $qualityVariants,

                'creator' => [
                    'id' => $video->creator->id,
                    'name' => $video->creator->name,
                ],
                'created_at' => $video->created_at->toDateTimeString(),
            ],
            'analytics' => [
                'total_viewers' => 0,
                'completed_viewers' => 0,
                'completion_rate' => 0,
                'avg_progress' => 0,
                'total_watch_hours' => 0,
            ],
            'recent_activity' => [],
        ]);
    }

    /**
     * Show the form for editing the specified video
     * ✅ UPDATED: Include storage information
     */
    public function edit(Video $video)
    {
        $video->load(['qualities']);

        $categories = VideoCategory::active()
            ->ordered()
            ->get()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
            ]);

        return Inertia::render('Admin/Video/Edit', [
            'video' => [
                'id' => $video->id,
                'name' => $video->name,
                'description' => $video->description,
                'google_drive_url' => $video->google_drive_url,
                'duration' => $video->duration,
                'thumbnail_url' => $video->thumbnail_url,
                'content_category_id' => $video->content_category_id,
                'is_active' => $video->is_active,

                // ✅ NEW: Storage information
                'storage_type' => $video->storage_type,
                'storage_type_label' => $video->getStorageTypeLabel(),
                'file_path' => $video->file_path,
                'file_size' => $video->file_size,
                'formatted_file_size' => $video->formatted_file_size,

                // ✅ NEW: VPS Transcoding information
                'transcode_status' => $video->transcode_status,
                'available_qualities' => $video->getAvailableQualities(),
                'has_multiple_qualities' => $video->hasMultipleQualities(),

                'total_viewers' => 0,
                'avg_completion' => 0,
            ],
            'categories' => $categories,
            'maxFileSize' => 10240000, // 10,000 MB (10GB) in KB for ChunkUploader
        ]);
    }

    /**
     * Update the specified video
     * ✅ UPDATED: Handle storage type changes carefully
     */
    public function update(Request $request, Video $video)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'duration' => 'nullable|integer|min:1|max:86400',
            'content_category_id' => 'nullable|exists:content_categories,id',
            'is_active' => 'boolean',

            // ✅ NEW: Only allow updating Google Drive URL for Google Drive videos
            'google_drive_url' => $video->isGoogleDrive() ? 'required|url|max:500' : 'nullable',
        ]);

        DB::beginTransaction();

        try {
            $updateData = [
                'name' => $validated['name'],
                'description' => $validated['description'],
                'duration' => $validated['duration'],
                'content_category_id' => $validated['content_category_id'],
                'is_active' => $validated['is_active'] ?? true,
            ];

            // ✅ UPDATED: Only update Google Drive URL if storage type is Google Drive
            if ($video->isGoogleDrive() && isset($validated['google_drive_url'])) {
                if ($video->google_drive_url !== $validated['google_drive_url']) {
                    $newStreamingUrl = $this->googleDriveService->processUrl($validated['google_drive_url']);
                    if ($newStreamingUrl) {
                        $updateData['google_drive_url'] = $validated['google_drive_url'];
                        $updateData['streaming_url'] = $newStreamingUrl;
                    }
                }
            }

            $video->update($updateData);

            DB::commit();



            return redirect()->route('admin.videos.index')
                ->with('success', 'Video updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();


            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update video.');
        }
    }

    /**
     * Remove the specified video
     * ✅ UPDATED: Delete physical file for local storage
     */
    public function destroy(Video $video)
{
    try {
        $videoName = $video->name;
        $storageType = $video->storage_type;

        // ✅ Delete physical file if local storage
        if ($video->isLocal()) {
            $deleted = $video->deleteStoredFile();
            if (!$deleted) {
                // you can log here if you want
                // Log::warning("Could not delete file for video ID {$video->id}");
            }
        }

        // Delete database record
        $video->delete();

        return redirect()->route('admin.videos.index')
            ->with('success', 'Video deleted successfully.');

    } catch (\Exception $e) {
        return redirect()->back()
            ->with('error', 'Failed to delete video. Please try again.');
    }
}

    /**
     * Toggle active status
     * ✅ UNCHANGED: Works for both storage types
     */
    public function toggleActive(Video $video)
    {
        $video->update(['is_active' => !$video->is_active]);
        $status = $video->is_active ? 'activated' : 'deactivated';


        return back()->with('success', "Video {$status} successfully.");
    }

    /**
     * Get fresh streaming URL for video
     * ✅ UPDATED: Only works for Google Drive videos
     */
    public function getStreamingUrl(Video $video)
    {
        try {
            if (!$video->isGoogleDrive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This function only works for Google Drive videos'
                ], 400);
            }

            $streamingUrl = $this->googleDriveService->processUrl($video->google_drive_url);

            if ($streamingUrl) {
                $video->update(['streaming_url' => $streamingUrl]);

                return response()->json([
                    'success' => true,
                    'streaming_url' => $streamingUrl,
                    'updated_at' => now()->toISOString()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Could not generate streaming URL'
            ], 400);

        } catch (\Exception $e) {


            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Migrate a video from Google Drive to Local Storage
     * ✅ NEW: Production-safe migration with data preservation
     */
    public function migrateToLocal(Request $request, Video $video)
    {
        // Validate the video is currently on Google Drive
        if (!$video->isGoogleDrive()) {
            return back()->with('error', 'Only Google Drive videos can be migrated to local storage.');
        }

        // Validate request
        $validated = $request->validate([
            'video_data' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            // Parse the JSON response from ChunkUploader
            $uploadedFileData = json_decode($validated['video_data'], true);

            if (!$uploadedFileData || !isset($uploadedFileData['path'])) {
                throw new \Exception('Invalid video upload data. Please try uploading again.');
            }

            Log::info('Migrating video to local storage', [
                'video_id' => $video->id,
                'video_name' => $video->name,
                'old_google_drive_url' => $video->google_drive_url,
                'new_file_path' => $uploadedFileData['path'],
            ]);

            // Update video record with new local storage data
            $video->update([
                'storage_type' => 'local',
                'file_path' => $uploadedFileData['path'],
                'file_size' => $uploadedFileData['size'] ?? null,
                'mime_type' => $uploadedFileData['mime_type'] ?? 'video/mp4',
                // Clear Google Drive references
                'google_drive_url' => null,
                'streaming_url' => null,
            ]);

            DB::commit();

            Log::info('Video migration successful', [
                'video_id' => $video->id,
                'new_storage_type' => 'local',
            ]);

            return back()->with('success', 'Video migrated to local storage successfully. User progress data has been preserved.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Video migration failed', [
                'video_id' => $video->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Migration failed: ' . $e->getMessage());
        }
    }

      protected function getLocalVideoStorageStats(): array
{
    $disk = 'public';
    $basePath = 'videos'; // where ChunkUploadController saves final files

    // If the folder doesn't exist yet, return zeros
    if (!Storage::disk($disk)->exists($basePath)) {
        return [
            'total_files'      => 0,
            'total_size_bytes' => 0,
            'total_size_mb'    => 0,
            'total_size_gb'    => 0,
            'disk'             => $disk,
        ];
    }

    // Get all files under /videos (recursively)
    $files = Storage::disk($disk)->allFiles($basePath);

    // (Optional) filter only video extensions if you want to be strict:
    $videoExtensions = ['mp4', 'mov', 'mkv', 'avi', 'webm'];
    $files = array_filter($files, function ($file) use ($videoExtensions) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return in_array($ext, $videoExtensions);
    });

    $totalBytes = 0;

    foreach ($files as $file) {
        $totalBytes += Storage::disk($disk)->size($file);
    }

    $totalMb = round($totalBytes / 1024 / 1024, 2);
    $totalGb = round($totalBytes / 1024 / 1024 / 1024, 2);

    return [
        'total_files'      => count($files),
        'total_size_bytes' => $totalBytes,
        'total_size_mb'    => $totalMb,
        'total_size_gb'    => $totalGb,
        'disk'             => $disk,
    ];
}

    /**
     * Retry transcoding for a failed video
     * ✅ NEW: VPS Transcoding Integration
     */
    public function retryTranscode(Video $video)
    {
        if (!$video->isLocal()) {
            return back()->with('error', 'Only local videos can be transcoded');
        }

        $success = app(\App\Services\VpsTranscodingService::class)->retryTranscoding($video);

        return back()->with(
            $success ? 'success' : 'error',
            $success ? 'Transcoding restarted' : 'Failed to restart transcoding'
        );
    }
/**
 * Show the subtitle VTT editor for a video
 */
public function editSubtitle(Video $video)
{
    $vttContent = '';

    if ($video->subtitle_vtt_path && Storage::disk('public')->exists($video->subtitle_vtt_path)) {
        $vttContent = Storage::disk('public')->get($video->subtitle_vtt_path);
    }

    return Inertia::render('Admin/Video/SubtitleEditor', [
        'video' => [
            'id'              => $video->id,
            'name'            => $video->name,
            'subtitle_status' => $video->subtitle_status,
            'subtitle_vtt_path' => $video->subtitle_vtt_path,
            'streaming_url'   => $video->streaming_url,
            'file_path'       => $video->file_path ?? null,
            'storage_type'    => $video->storage_type,
        ],
        'vttContent' => $vttContent,
    ]);
}

/**
 * Save updated VTT content for a video
 */
public function updateSubtitle(Request $request, Video $video)
{
    $request->validate([
        'vtt_content' => 'required|string',
    ]);

    $vttContent = $request->input('vtt_content');

    // Basic VTT format validation
    if (!str_starts_with(trim($vttContent), 'WEBVTT')) {
        return back()->with('error', 'Invalid VTT format. File must start with WEBVTT.');
    }

    $path = $video->subtitle_vtt_path
        ?? 'subtitles/' . $video->id . '_ar.vtt';

    Storage::disk('public')->put($path, $vttContent);

    // Make sure DB is updated if path was null
    if (!$video->subtitle_vtt_path) {
        $video->update([
            'subtitle_vtt_path' => $path,
            'subtitle_status'   => 'completed',
        ]);
    }

    return back()->with('success', 'Subtitle file updated successfully.');
}

/**
 * Retry subtitle generation for a failed video
 */
public function retrySubtitle(Video $video)
{
    if (!$video->isLocal()) {
        return back()->with('error', 'Only local videos can have subtitles generated.');
    }

    $success = app(\App\Services\VpsSubtitleService::class)->retrySubtitle($video);

    return back()->with(
        $success ? 'success' : 'error',
        $success ? 'Subtitle generation restarted.' : 'Failed to restart subtitle generation.'
    );
}
    
}
