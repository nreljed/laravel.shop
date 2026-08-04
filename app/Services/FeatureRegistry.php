<?php

namespace App\Services;

use Illuminate\Support\ServiceProvider;
use Nreljed\DashboardCore\Services\FeatureRegistry as BaseFeatureRegistry;

class FeatureRegistry extends BaseFeatureRegistry
{
    /**
     * @return array<int, ServiceProvider>
     */
    public function all(): array
    {
        return array_values($this->providers);
    }
}
