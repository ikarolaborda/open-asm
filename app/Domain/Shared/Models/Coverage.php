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

class Coverage extends Model
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
        'coverage_type',
        'is_active',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\CoverageFactory::new();
    }

    /**
     * Coverage types enumeration.
     */
    public const COVERAGE_TYPES = [
        'warranty' => 'Warranty',
        'service_contract' => 'Service Contract',
        'maintenance' => 'Maintenance',
    ];

    /**
     * Get the organization that owns the coverage.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the asset warranties that use this coverage.
     */
    public function assetWarranties(): HasMany
    {
        return $this->hasMany(\App\Domain\Asset\Models\AssetWarranty::class);
    }

    /**
     * Scope a query to only include active coverages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by coverage type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('coverage_type', $type);
    }

    /**
     * Get the coverage type label.
     */
    public function getCoverageTypeLabelAttribute(): string
    {
        return self::COVERAGE_TYPES[$this->coverage_type] ?? $this->coverage_type;
    }

    /**
     * Get the display name for the coverage.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->code ? "{$this->name} ({$this->code})" : $this->name;
    }

    /**
     * Check if the coverage is of a specific type.
     */
    public function isType(string $type): bool
    {
        return $this->coverage_type === $type;
    }

    /**
     * Check if the coverage is for warranty.
     */
    public function isWarranty(): bool
    {
        return $this->isType('warranty');
    }

    /**
     * Check if the coverage is for service contract.
     */
    public function isServiceContract(): bool
    {
        return $this->isType('service_contract');
    }

    /**
     * Check if the coverage is for maintenance.
     */
    public function isMaintenance(): bool
    {
        return $this->isType('maintenance');
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
                'coverage_type',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
