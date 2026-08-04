<?php

namespace App\Shop\Http\Controllers;

use App\Shop\Models\ShippingMethod;
use App\Shop\Models\ShippingZone;
use App\Shop\Requests\StoreShippingZoneRequest;
use Inertia\Inertia;
use Nreljed\DashboardCore\Http\Controllers\Controller;

class ShippingZonesController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', ShippingZone::class);

        $zones = ShippingZone::with('methods')->orderBy('name')->paginate(20);

        return Inertia::render('shop/shippingzones/index', [
            'zones' => $zones,
        ]);
    }

    public function create()
    {
        $this->authorize('create', ShippingZone::class);

        return Inertia::render('shop/shippingzones/form', [
            'zone' => null,
            'methods' => ShippingMethod::active()->get(),
        ]);
    }

    public function store(StoreShippingZoneRequest $request)
    {
        $this->authorize('create', ShippingZone::class);

        $validated = $request->validated();

        $zone = ShippingZone::create([
            'name' => $validated['name'],
            'countries' => $validated['countries'] ?? [],
            'regions' => $validated['regions'] ?? [],
            'is_active' => $request->boolean('is_active', true),
        ]);

        if (! empty($validated['method_ids'])) {
            $sync = [];
            foreach ($validated['method_ids'] as $m) {
                $sync[$m['id']] = ['override_price' => $m['override_price'] ?? null];
            }
            $zone->methods()->sync($sync);
        }

        return redirect()->route('dashboard.shop.shipping-zones.index')
            ->with('status', 'Shipping zone created.');
    }

    public function edit(ShippingZone $shippingZone)
    {
        $this->authorize('view', $shippingZone);

        $shippingZone->load('methods');

        return Inertia::render('shop/shippingzones/form', [
            'zone' => $shippingZone,
            'methods' => ShippingMethod::active()->get(),
        ]);
    }

    public function update(StoreShippingZoneRequest $request, ShippingZone $shippingZone)
    {
        $this->authorize('update', $shippingZone);

        $validated = $request->validated();

        $shippingZone->update([
            'name' => $validated['name'],
            'countries' => $validated['countries'] ?? [],
            'regions' => $validated['regions'] ?? [],
            'is_active' => $request->boolean('is_active', true),
        ]);

        if (array_key_exists('method_ids', $validated)) {
            $sync = [];
            foreach ($validated['method_ids'] as $m) {
                $sync[$m['id']] = ['override_price' => $m['override_price'] ?? null];
            }
            $shippingZone->methods()->sync($sync);
        }

        return redirect()->route('dashboard.shop.shipping-zones.index')
            ->with('status', 'Shipping zone updated.');
    }

    public function destroy(ShippingZone $shippingZone)
    {
        $this->authorize('delete', $shippingZone);

        $shippingZone->delete();

        return redirect()->back()->with('status', 'Shipping zone deleted.');
    }
}
