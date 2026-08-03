<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class SiteSettingService
{
    public const CACHE_KEY = 'site_settings:all';

    public const CACHE_TTL = 3600;

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $out = [];
            foreach (SiteSetting::query()->get() as $row) {
                $out[$row->key] = $row->castedValue();
            }

            return $out;
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /**
     * @param  array<string, mixed>  $keysWithDefaults
     * @return array<string, mixed>
     */
    public function getMany(array $keysWithDefaults): array
    {
        $all = $this->all();
        $out = [];
        foreach ($keysWithDefaults as $key => $default) {
            $out[$key] = array_key_exists($key, $all) ? $all[$key] : $default;
        }

        return $out;
    }

    /**
     * @param  array<string, array{value: mixed, type?: string, group?: string}>  $items
     */
    public function putMany(array $items, ?int $updatedBy = null): void
    {
        foreach ($items as $key => $item) {
            $type = $item['type'] ?? $this->inferType($item['value'] ?? null);
            $group = $item['group'] ?? 'general';
            $encoded = SiteSetting::encodeValue($item['value'] ?? null, $type);

            SiteSetting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => $encoded,
                    'type' => $type,
                    'group' => $group,
                    'updated_by' => $updatedBy,
                ]
            );
        }

        $this->forgetCache();
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected function inferType(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_float($value)) {
            return 'float';
        }
        if (is_array($value)) {
            return 'json';
        }

        return 'string';
    }
}
