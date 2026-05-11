<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseOnline extends Model
{
    use HasFactory;
    protected $table = 'course_online';

    protected $fillable = [
        'name',
        'description',
        'image_path',
        'estimated_duration',
        'difficulty_level',
        'is_active',
        'created_by',
        'deadline',
        'has_deadline', 
        'deadline_type'  // NEW
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'estimated_duration' => 'integer',
        'deadline' => 'datetime',           // NEW
        'has_deadline' => 'boolean',        // NEW
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('order_number');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CourseOnlineAssignment::class);
    }


    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper methods
    public function getTotalDurationAttribute()
    {
        return $this->modules->sum('estimated_duration');
    }

    /**
     * Get enrollment count - use loaded assignments if available to avoid N+1
     * ✅ FIXED N+1: Use loaded relationship when available
     */
    public function getEnrollmentCountAttribute()
    {
        // If assignments are already loaded, use them
        if ($this->relationLoaded('assignments')) {
            return $this->assignments->count();
        }
        // Otherwise query (will trigger N+1 if used in a loop without eager loading)
        return $this->assignments()->count();
    }

    /**
     * Get completion rate - use loaded assignments if available to avoid N+1
     * ✅ FIXED N+1: Use loaded relationship when available
     */
    public function getCompletionRateAttribute()
    {
        // If assignments are already loaded, use them
        if ($this->relationLoaded('assignments')) {
            $total = $this->assignments->count();
            if ($total === 0) return 0;
            $completed = $this->assignments->where('status', 'completed')->count();
            return round(($completed / $total) * 100, 2);
        }
        
        // Otherwise query
        $total = $this->assignments()->count();
        if ($total === 0) return 0;

        $completed = $this->assignments()->where('status', 'completed')->count();
        return round(($completed / $total) * 100, 2);
    }
    public function analytics()
    {
        return $this->hasOne(CourseAnalytics::class);
    }

    public function learningSessions(): HasMany
    {
        return $this->hasMany(LearningSession::class);
    }

// Method to get or create analytics
    public function getAnalytics(): CourseAnalytics
    {
        // Try to get existing analytics first
        $analytics = $this->analytics()->first();

        if (!$analytics) {
            // Create new analytics record with proper course_online_id
            $analytics = CourseAnalytics::create([
                'course_online_id' => $this->id, // ✅ Make sure $this->id is not null
            ]);

            // Update analytics with calculated data
            $analytics->updateAnalytics();
        }

        return $analytics;
    }

    // NEW: Deadline-related methods
    public function hasDeadline(): bool
    {
        return $this->has_deadline && !is_null($this->deadline);
    }

    public function isDeadlinePassed(): bool
    {
        if (!$this->hasDeadline()) {
            return false;
        }
        return $this->deadline->isPast();
    }

    public function daysUntilDeadline(): ?int
    {
        if (!$this->hasDeadline()) {
            return null;
        }
        return now()->diffInDays($this->deadline, false);
    }

    public function getDeadlineStatusAttribute(): string
    {
        if (!$this->hasDeadline()) {
            return 'none';
        }

        $days = $this->daysUntilDeadline();

        if ($days < 0) {
            return 'overdue';
        } elseif ($days <= 3) {
            return 'urgent';
        } elseif ($days <= 7) {
            return 'warning';
        }

        return 'normal';
    }
    public function quizzes()
    {
        return $this->hasMany(Quiz::class, 'course_online_id');
    }
}
