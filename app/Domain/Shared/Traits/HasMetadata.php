<?php

declare(strict_types=1);

namespace App\Domain\Shared\Traits;

trait HasMetadata
{
    /**
     * Get a metadata value by key.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        $metadata = $this->metadata ?? [];

        return data_get($metadata, $key, $default);
    }

    /**
     * Set a metadata value by key.
     */
    public function setMetadata(string $key, mixed $value): static
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, $key, $value);
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Check if a metadata key exists.
     */
    public function hasMetadata(string $key): bool
    {
        $metadata = $this->metadata ?? [];

        return data_get($metadata, $key) !== null;
    }

    /**
     * Remove a metadata key.
     */
    public function removeMetadata(string $key): static
    {
        $metadata = $this->metadata ?? [];

        // Use array_forget helper or manual unset
        if (function_exists('array_forget')) {
            array_forget($metadata, $key);
        } else {
            // Manual implementation for nested keys
            $keys = explode('.', $key);
            $current = &$metadata;

            for ($i = 0; $i < count($keys) - 1; ++$i) {
                if (! isset($current[$keys[$i]]) || ! is_array($current[$keys[$i]])) {
                    return $this;
                }
                $current = &$current[$keys[$i]];
            }

            unset($current[end($keys)]);
        }

        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Merge metadata with existing data.
     */
    public function mergeMetadata(array $metadata): static
    {
        $existing = $this->metadata ?? [];
        $this->metadata = array_merge_recursive($existing, $metadata);

        return $this;
    }
}
