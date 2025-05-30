<?php

declare(strict_types=1);

namespace App\Domain\Organization\Models;

use App\Domain\Asset\Models\Asset;
use App\Domain\Customer\Models\Customer;
use App\Domain\Location\Models\Location;
use App\Domain\Shared\Models\Contact;
use App\Domain\Shared\Models\Coverage;
use App\Domain\Shared\Models\Oem;
use App\Domain\Shared\Models\Product;
use App\Domain\Shared\Models\ProductLine;
use App\Domain\Shared\Models\ServiceLevel;
use App\Domain\Shared\Models\Status;
use App\Domain\Shared\Models\Tag;
use App\Domain\Shared\Models\Type;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Organization extends Model
{
    use HasFactory;
    use HasUuids;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'email',
        'phone',
        'website',
        'description',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
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
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\OrganizationFactory::new();
    }

    /**
     * Get all users for this organization.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all customers for this organization.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get all assets for this organization.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Get all locations for this organization.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Get all contacts for this organization.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get all OEMs for this organization.
     */
    public function oems(): HasMany
    {
        return $this->hasMany(Oem::class);
    }

    /**
     * Get all product lines for this organization.
     */
    public function productLines(): HasMany
    {
        return $this->hasMany(ProductLine::class);
    }

    /**
     * Get all products for this organization.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get all types for this organization.
     */
    public function types(): HasMany
    {
        return $this->hasMany(Type::class);
    }

    /**
     * Get all statuses for this organization.
     */
    public function statuses(): HasMany
    {
        return $this->hasMany(Status::class);
    }

    /**
     * Get all coverages for this organization.
     */
    public function coverages(): HasMany
    {
        return $this->hasMany(Coverage::class);
    }

    /**
     * Get all service levels for this organization.
     */
    public function serviceLevels(): HasMany
    {
        return $this->hasMany(ServiceLevel::class);
    }

    /**
     * Get all tags for this organization.
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * Check if the organization is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Activate the organization.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the organization.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get the full address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get organization statistics.
     */
    public function getStatistics(): array
    {
        return [
            'users_count' => $this->users()->count(),
            'customers_count' => $this->customers()->count(),
            'assets_count' => $this->assets()->count(),
            'locations_count' => $this->locations()->count(),
            'active_assets_count' => $this->assets()->where('is_active', true)->count(),
        ];
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
            ->setDescriptionForEvent(fn (string $eventName) => "Organization {$eventName}");
    }
}
