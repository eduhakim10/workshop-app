<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    public const DASHBOARD_LOOKBACK_DAYS = 'customer_portal.dashboard_lookback_days';

    public const DEFAULT_LOOKBACK_DAYS = 30;

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever($this->cacheKey($key), function () use ($key, $default) {
            $row = Setting::query()->where('key', $key)->first();

            return $row?->value ?? $default;
        });
    }

    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value === null ? null : (string) $value, 'group' => $group],
        );

        Cache::forget($this->cacheKey($key));
    }

    public function dashboardLookbackDays(): int
    {
        $days = (int) $this->get(self::DASHBOARD_LOOKBACK_DAYS, self::DEFAULT_LOOKBACK_DAYS);

        return max(1, min(365, $days ?: self::DEFAULT_LOOKBACK_DAYS));
    }

    /**
     * Resolve from/to for the customer portal dashboard.
     * Empty query params → workshop-app default lookback (inclusive of today).
     *
     * @return array{from: string, to: string, default_from: string, default_to: string, lookback_days: int, is_default: bool}
     */
    public function resolveDashboardPeriod(?string $from, ?string $to): array
    {
        $days = $this->dashboardLookbackDays();
        $defaultTo = now()->toDateString();
        $defaultFrom = now()->subDays($days - 1)->toDateString();

        $isDefault = blank($from) && blank($to);

        $resolvedFrom = $this->validDate($from) ?? $defaultFrom;
        $resolvedTo = $this->validDate($to) ?? $defaultTo;

        if ($resolvedFrom > $resolvedTo) {
            [$resolvedFrom, $resolvedTo] = [$resolvedTo, $resolvedFrom];
        }

        return [
            'from' => $resolvedFrom,
            'to' => $resolvedTo,
            'default_from' => $defaultFrom,
            'default_to' => $defaultTo,
            'lookback_days' => $days,
            'is_default' => $isDefault,
        ];
    }

    private function validDate(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function cacheKey(string $key): string
    {
        return 'settings.'.$key;
    }
}
