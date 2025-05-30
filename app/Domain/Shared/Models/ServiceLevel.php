<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use App\Domain\Organization\Models\Organization;
use App\Domain\Shared\Traits\BelongsToOrganization;
use App\Domain\Shared\Traits\HasMetadata;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ServiceLevel extends Model
{
    use BelongsToOrganization;
    use HasFactory;
    use HasMetadata;
    use HasUuids;
    use LogsActivity;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'description',
        'response_time_hours',
        'resolution_time_hours',
        'is_active',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'response_time_hours' => 'integer',
        'resolution_time_hours' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\ServiceLevelFactory::new();
    }

    /**
     * Common service level tiers.
     */
    public const SERVICE_TIERS = [
        'platinum' => 'Platinum',
        'gold' => 'Gold',
        'silver' => 'Silver',
        'bronze' => 'Bronze',
        'basic' => 'Basic',
    ];

    /**
     * Get the organization that owns the service level.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the asset warranties that use this service level.
     */
    public function assetWarranties(): HasMany
    {
        return $this->hasMany(\App\Domain\Asset\Models\AssetWarranty::class);
    }

    /**
     * Get the customers that use this service level.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(\App\Domain\Customer\Models\Customer::class);
    }

    /**
     * Scope a query to only include active service levels.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by response time.
     */
    public function scopeWithResponseTime($query, int $maxHours)
    {
        return $query->where('response_time_hours', '<=', $maxHours);
    }

    /**
     * Scope a query to filter by resolution time.
     */
    public function scopeWithResolutionTime($query, int $maxHours)
    {
        return $query->where('resolution_time_hours', '<=', $maxHours);
    }

    /**
     * Get the display name for the service level.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->code ? "{$this->name} ({$this->code})" : $this->name;
    }

    /**
     * Get the response time in a human-readable format.
     */
    public function getFormattedResponseTimeAttribute(): string
    {
        if (! $this->response_time_hours) {
            return 'Not specified';
        }

        if ($this->response_time_hours < 24) {
            return "{$this->response_time_hours} hours";
        }

        $days = intval($this->response_time_hours / 24);
        $remainingHours = $this->response_time_hours % 24;

        $formatted = "{$days} " . ($days === 1 ? 'day' : 'days');

        if ($remainingHours > 0) {
            $formatted .= " {$remainingHours} " . ($remainingHours === 1 ? 'hour' : 'hours');
        }

        return $formatted;
    }

    /**
     * Get the resolution time in a human-readable format.
     */
    public function getFormattedResolutionTimeAttribute(): string
    {
        if (! $this->resolution_time_hours) {
            return 'Not specified';
        }

        if ($this->resolution_time_hours < 24) {
            return "{$this->resolution_time_hours} hours";
        }

        $days = intval($this->resolution_time_hours / 24);
        $remainingHours = $this->resolution_time_hours % 24;

        $formatted = "{$days} " . ($days === 1 ? 'day' : 'days');

        if ($remainingHours > 0) {
            $formatted .= " {$remainingHours} " . ($remainingHours === 1 ? 'hour' : 'hours');
        }

        return $formatted;
    }

    /**
     * Get the SLA summary string.
     */
    public function getSlaSummaryAttribute(): string
    {
        $response = $this->formatted_response_time;
        $resolution = $this->formatted_resolution_time;

        return "Response: {$response} | Resolution: {$resolution}";
    }

    /**
     * Check if this is a premium service level (response time <= 4 hours).
     */
    public function isPremium(): bool
    {
        return $this->response_time_hours && $this->response_time_hours <= 4;
    }

    /**
     * Check if this is a standard service level (response time > 4 and <= 24 hours).
     */
    public function isStandard(): bool
    {
        return $this->response_time_hours &&
               $this->response_time_hours > 4 &&
               $this->response_time_hours <= 24;
    }

    /**
     * Check if this is a basic service level (response time > 24 hours).
     */
    public function isBasic(): bool
    {
        return $this->response_time_hours && $this->response_time_hours > 24;
    }

    /**
     * Calculate the target response deadline from a given start time.
     */
    public function getResponseDeadline(\DateTime $startTime): ?\DateTime
    {
        if (! $this->response_time_hours) {
            return null;
        }

        $deadline = clone $startTime;
        $deadline->modify("+{$this->response_time_hours} hours");

        return $deadline;
    }

    /**
     * Calculate the target resolution deadline from a given start time.
     */
    public function getResolutionDeadline(\DateTime $startTime): ?\DateTime
    {
        if (! $this->resolution_time_hours) {
            return null;
        }

        $deadline = clone $startTime;
        $deadline->modify("+{$this->resolution_time_hours} hours");

        return $deadline;
    }

    /**
     * Get the options for activity logging.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'code',
                'description',
                'response_time_hours',
                'resolution_time_hours',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
