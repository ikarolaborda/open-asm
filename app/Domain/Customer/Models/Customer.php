<?php

declare(strict_types=1);

namespace App\Domain\Customer\Models;

use App\Domain\Asset\Models\Asset;
use App\Domain\Customer\Events\CustomerCreated;
use App\Domain\Customer\Events\CustomerUpdated;
use App\Domain\Location\Models\Location;
use App\Domain\Organization\Models\Organization;
use App\Domain\Shared\Models\Contact;
use App\Domain\Shared\Models\Status;
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

class Customer extends Model
{
    use HasFactory;
    use HasUuids;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'email',
        'phone',
        'website',
        'industry',
        'description',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_country',
        'billing_postal_code',
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

    protected $dispatchesEvents = [
        'created' => CustomerCreated::class,
        'updated' => CustomerUpdated::class,
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\CustomerFactory::new();
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('organization', function (Builder $builder) {
            if (auth()->check() && auth()->user()->organization_id) {
                $builder->where('customers.organization_id', auth()->user()->organization_id);
            }
        });
    }

    /**
     * Get the organization that owns the customer.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get all assets belonging to this customer.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Get all locations for this customer.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Get the primary/headquarters location for this customer.
     */
    public function headquartersLocation(): HasMany
    {
        return $this->locations()->where('is_headquarters', true);
    }

    /**
     * Get all contacts for this customer.
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'customer_contacts')
            ->withTimestamps()
            ->withPivot(['contact_type', 'is_primary']);
    }

    /**
     * Get primary contacts for this customer.
     */
    public function primaryContacts(): BelongsToMany
    {
        return $this->contacts()->wherePivot('is_primary', true);
    }

    /**
     * Get all statuses associated with this customer.
     */
    public function statuses(): BelongsToMany
    {
        return $this->belongsToMany(Status::class, 'customer_statuses')
            ->withTimestamps()
            ->withPivot(['is_current']);
    }

    /**
     * Get the current status of the customer.
     */
    public function currentStatus(): BelongsToMany
    {
        return $this->statuses()->wherePivot('is_current', true);
    }

    /**
     * Check if the customer is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Activate the customer.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the customer.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get the total number of assets for this customer.
     */
    public function getAssetsCountAttribute(): int
    {
        return $this->assets()->count();
    }

    /**
     * Get the total number of locations for this customer.
     */
    public function getLocationsCountAttribute(): int
    {
        return $this->locations()->count();
    }

    /**
     * Get the full billing address.
     */
    public function getFullBillingAddressAttribute(): string
    {
        $parts = array_filter([
            $this->billing_address,
            $this->billing_city,
            $this->billing_state,
            $this->billing_postal_code,
            $this->billing_country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Update the customer's status.
     */
    public function updateStatus(Status $status): void
    {
        // Mark all current statuses as not current
        $this->statuses()->updateExistingPivot(
            $this->statuses()->wherePivot('is_current', true)->pluck('statuses.id'),
            ['is_current' => false]
        );

        // Add or update the new status as current
        $this->statuses()->syncWithoutDetaching([
            $status->id => ['is_current' => true],
        ]);
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
            ->setDescriptionForEvent(fn (string $eventName) => "Customer {$eventName}");
    }

    /**
     * Scope a query to only include customers for a specific organization.
     */
    public function scopeForOrganization(Builder $query, Organization $organization): Builder
    {
        return $query->where('organization_id', $organization->id);
    }

    /**
     * Scope a query to only include active customers.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive customers.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query to only include customers in a specific industry.
     */
    public function scopeByIndustry(Builder $query, string $industry): Builder
    {
        return $query->where('industry', $industry);
    }

    /**
     * Scope a query to only include customers with assets.
     */
    public function scopeWithAssets(Builder $query): Builder
    {
        return $query->has('assets');
    }

    /**
     * Scope a query to only include customers without assets.
     */
    public function scopeWithoutAssets(Builder $query): Builder
    {
        return $query->doesntHave('assets');
    }

    /**
     * Scope a query to search customers by name, code, email, or phone.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });
    }
}
