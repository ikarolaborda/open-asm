<?php

declare(strict_types=1);

namespace App\Domain\Asset\Models;

use App\Domain\Customer\Models\Customer;
use App\Domain\Location\Models\Location;
use App\Domain\Organization\Models\Organization;
use App\Domain\Shared\Models\Oem;
use App\Domain\Shared\Models\Product;
use App\Domain\Shared\Models\Status;
use App\Domain\Shared\Models\Tag;
use App\Domain\Shared\Models\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Asset extends Model
{
    use HasFactory;
    use HasUuids;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'customer_id',
        'location_id',
        'oem_id',
        'product_id',
        'type_id',
        'status_id',
        'serial_number',
        'asset_tag',
        'model_number',
        'part_number',
        'name',
        'description',
        'purchase_date',
        'installation_date',
        'warranty_start_date',
        'warranty_end_date',
        'purchase_price',
        'current_value',
        'is_active',
        'data_quality_score',
        'metadata',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'installation_date' => 'date',
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
        'purchase_price' => 'decimal:2',
        'current_value' => 'decimal:2',
        'is_active' => 'boolean',
        'data_quality_score' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('organization', function (Builder $builder) {
            if (auth()->check() && auth()->user()->organization_id) {
                $builder->where('assets.organization_id', auth()->user()->organization_id);
            }
        });

        static::creating(function (Asset $asset) {
            if (!$asset->organization_id && auth()->check()) {
                $asset->organization_id = auth()->user()->organization_id;
            }
            
            // Calculate initial data quality score
            $asset->data_quality_score = $asset->calculateDataQualityScore();
        });

        static::updating(function (Asset $asset) {
            // Recalculate data quality score on update
            $asset->data_quality_score = $asset->calculateDataQualityScore();
        });
    }

    /**
     * Get the organization that owns the asset.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the customer that owns the asset.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the location of the asset.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the OEM of the asset.
     */
    public function oem(): BelongsTo
    {
        return $this->belongsTo(Oem::class);
    }

    /**
     * Get the product of the asset.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the type of the asset.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    /**
     * Get the status of the asset.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * Get all warranties for this asset.
     */
    public function warranties(): HasMany
    {
        return $this->hasMany(AssetWarranty::class);
    }

    /**
     * Get all tags for this asset.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'asset_tags')
            ->withTimestamps();
    }

    /**
     * Get the current warranty for this asset.
     */
    public function currentWarranty(): HasMany
    {
        return $this->warranties()
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Calculate data quality score based on completeness.
     */
    public function calculateDataQualityScore(): int
    {
        $requiredFields = [
            'name', 'serial_number', 'customer_id', 'type_id'
        ];
        
        $optionalFields = [
            'asset_tag', 'model_number', 'part_number', 'description',
            'purchase_date', 'installation_date', 'warranty_start_date',
            'warranty_end_date', 'purchase_price', 'location_id', 'oem_id',
            'product_id', 'status_id'
        ];

        $requiredScore = 0;
        $optionalScore = 0;

        // Check required fields (70% of total score)
        foreach ($requiredFields as $field) {
            if (!empty($this->attributes[$field])) {
                $requiredScore += 70 / count($requiredFields);
            }
        }

        // Check optional fields (30% of total score)
        foreach ($optionalFields as $field) {
            if (!empty($this->attributes[$field])) {
                $optionalScore += 30 / count($optionalFields);
            }
        }

        return (int) round($requiredScore + $optionalScore);
    }

    /**
     * Check if the asset is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if the asset is retired.
     */
    public function isRetired(): bool
    {
        return !$this->is_active;
    }

    /**
     * Check if the asset has warranty coverage.
     */
    public function hasActiveWarranty(): bool
    {
        return $this->currentWarranty()->exists();
    }

    /**
     * Get warranty expiration status.
     */
    public function getWarrantyStatusAttribute(): string
    {
        $activeWarranty = $this->currentWarranty()->first();
        
        if (!$activeWarranty) {
            return 'no_warranty';
        }

        $daysUntilExpiry = now()->diffInDays($activeWarranty->end_date, false);
        
        if ($daysUntilExpiry < 0) {
            return 'expired';
        } elseif ($daysUntilExpiry <= 30) {
            return 'expiring_soon';
        }
        
        return 'active';
    }

    /**
     * Retire the asset.
     */
    public function retire(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Reactivate the asset.
     */
    public function reactivate(): void
    {
        $this->update(['is_active' => true]);
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
            ->setDescriptionForEvent(fn (string $eventName) => "Asset {$eventName}");
    }

    /**
     * Scope a query to only include active assets.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include retired assets.
     */
    public function scopeRetired(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query to only include assets with warranties expiring soon.
     */
    public function scopeWarrantyExpiringSoon(Builder $query): Builder
    {
        return $query->whereHas('warranties', function ($q) {
            $q->where('is_active', true)
              ->where('end_date', '>=', now())
              ->where('end_date', '<=', now()->addDays(30));
        });
    }

    /**
     * Scope a query to only include assets with expired warranties.
     */
    public function scopeWarrantyExpired(Builder $query): Builder
    {
        return $query->whereHas('warranties', function ($q) {
            $q->where('is_active', true)
              ->where('end_date', '<', now());
        });
    }

    /**
     * Scope a query to search assets by name, serial number, or asset tag.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('serial_number', 'like', "%{$search}%")
                ->orWhere('asset_tag', 'like', "%{$search}%")
                ->orWhere('model_number', 'like', "%{$search}%");
        });
    }

    /**
     * Scope a query to filter by customer.
     */
    public function scopeForCustomer(Builder $query, string $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope a query to filter by location.
     */
    public function scopeAtLocation(Builder $query, string $locationId): Builder
    {
        return $query->where('location_id', $locationId);
    }
} 