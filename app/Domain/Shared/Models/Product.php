<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use App\Domain\Asset\Models\Asset;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory;
    use HasUuids;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'oem_id',
        'product_line_id',
        'name',
        'model_number',
        'part_number',
        'description',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
                $builder->where('products.organization_id', auth()->user()->organization_id);
            }
        });

        static::creating(function (Product $product) {
            if (!$product->organization_id && auth()->check()) {
                $product->organization_id = auth()->user()->organization_id;
            }
        });
    }

    /**
     * Get the organization that owns the product.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the OEM of the product.
     */
    public function oem(): BelongsTo
    {
        return $this->belongsTo(Oem::class);
    }

    /**
     * Get the product line of the product.
     */
    public function productLine(): BelongsTo
    {
        return $this->belongsTo(ProductLine::class);
    }

    /**
     * Get all assets using this product.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Scope for active products.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for filtering by name.
     */
    public function scopeNameLike(Builder $query, string $name): Builder
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /**
     * Scope for filtering by OEM.
     */
    public function scopeByOem(Builder $query, string $oemId): Builder
    {
        return $query->where('oem_id', $oemId);
    }

    /**
     * Scope for filtering by product line.
     */
    public function scopeByProductLine(Builder $query, string $productLineId): Builder
    {
        return $query->where('product_line_id', $productLineId);
    }

    /**
     * Get activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'model_number', 'part_number', 'description', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
} 