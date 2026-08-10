<?php

namespace App\Shop\Services;

use App\Shop\Models\Currency;
use Illuminate\Support\Facades\Cache;
use Nreljed\DashboardCore\Services\SettingsService;

class CurrencyService
{
    public function getActiveCurrencies()
    {
        $rows = Cache::remember('currencies.active.v2', 3600, function () {
            return Currency::active()->orderBy('code')->get()->toArray();
        });

        return Currency::hydrate($rows);
    }

    public function getDefaultCurrency(): ?Currency
    {
        $row = Cache::remember('currencies.default.v2', 3600, function () {
            $currency = Currency::default()->first() ?? Currency::active()->first();

            return $currency ? $currency->toArray() : null;
        });

        return $row ? Currency::hydrate([$row])->first() : null;
    }

    public function convert(float $amount, string $fromCode, string $toCode): float
    {
        if ($fromCode === $toCode) {
            return $amount;
        }

        $from = $this->getByCode($fromCode);
        $to = $this->getByCode($toCode);

        if (! $from || ! $to) {
            return $amount;
        }

        $baseAmount = $amount / $from->exchange_rate;

        return round($baseAmount * $to->exchange_rate, $to->decimal_places);
    }

    public function getByCode(string $code): ?Currency
    {
        return Currency::where('code', strtoupper($code))->first();
    }

    public function clearCache(): void
    {
        Cache::forget('currencies.active.v2');
        Cache::forget('currencies.default.v2');
    }

    public function detectFromAcceptLanguage(?string $acceptLanguage, array $activeCodes = []): ?string
    {
        if (! $acceptLanguage) {
            return null;
        }

        $activeCodes = array_map('strtoupper', $activeCodes);

        foreach (preg_split('/\s*,\s*/', $acceptLanguage) as $tag) {
            $tag = strtolower(explode(';', $tag)[0]);
            $region = str_contains($tag, '-') ? strtoupper(explode('-', $tag)[1]) : '';

            if ($region === '') {
                continue;
            }

            $mapped = match ($region) {
                'US', 'CA', 'AU' => 'USD',
                'GB', 'JE', 'GG', 'IM' => 'GBP',
                'TN' => 'TND',
                'JP' => 'JPY',
                'KR' => 'KRW',
                'VN' => 'VND',
                default => 'EUR',
            };

            if (in_array($mapped, $activeCodes, true)) {
                return $mapped;
            }
        }

        return null;
    }

    public function format(float $amount, string $code): string
    {
        $currency = $this->getByCode($code);

        if (! $currency) {
            return number_format($amount, 2).' '.$code;
        }

        $formatted = number_format($amount, $currency->decimal_places, '.', ',');

        $symbol = $currency->symbol;
        $position = SettingsService::get('currency_position', 'before');

        if ($position === 'after') {
            return $formatted.' '.$symbol;
        }

        return $symbol.$formatted;
    }

    public function getAllForSelector(): array
    {
        return $this->getActiveCurrencies()->map(fn ($c) => [
            'code' => $c->code,
            'name' => $c->name,
            'symbol' => $c->symbol,
            'is_default' => $c->is_default,
        ])->values()->toArray();
    }
}
