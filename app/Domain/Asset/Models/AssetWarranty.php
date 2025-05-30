<?php

declare(strict_types=1);

namespace App\Domain\Asset\Models;

use App\Domain\Shared\Models\Coverage;
use App\Domain\Shared\Models\ServiceLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AssetWarranty extends Model
{
    use HasFactory;
    use HasUuids;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'asset_id',
        'coverage_id',
        'service_level_id',
        'warranty_type',
        'start_date',
        'end_date',
        'description',
        'cost',
        'provider',
        'contract_number',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the asset this warranty belongs to.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the coverage type for this warranty.
     */
    public function coverage(): BelongsTo
    {
        return $this->belongsTo(Coverage::class);
    }

    /**
     * Get the service level for this warranty.
     */
    public function serviceLevel(): BelongsTo
    {
        return $this->belongsTo(ServiceLevel::class);
    }

    /**
     * Check if the warranty is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        return $this->is_active
            && $this->start_date <= now()
            && $this->end_date >= now();
    }

    /**
     * Check if the warranty is expired.
     */
    public function isExpired(): bool
    {
        return $this->end_date < now();
    }

    /**
     * Check if the warranty is expiring soon (within 30 days).
     */
    public function isExpiringSoon(): bool
    {
        $daysUntilExpiry = now()->diffInDays($this->end_date, false);

        return $daysUntilExpiry >= 0 && $daysUntilExpiry <= 30;
    }

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Asset Warranty {$eventName}");
    }

    /**
     * Scope a query to only include active warranties.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include current warranties.
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Scope a query to only include warranties expiring soon.
     */
    public function scopeExpiringSoon(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('end_date', '>=', now())
            ->where('end_date', '<=', now()->addDays(30));
    }

    /**
     * Scope a query to only include expired warranties.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('end_date', '<', now());
    }
}
