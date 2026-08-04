<?php

namespace App\Shop\Http\Controllers;

use App\Shop\Models\ShippingMethod;
use App\Shop\Requests\StoreShippingMethodRequest;
use Inertia\Inertia;
use Nreljed\DashboardCore\Http\Controllers\Controller;

class ShippingMethodsController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', ShippingMethod::class);

        $methods = ShippingMethod::orderBy('sort_order')->paginate(20);

        return Inertia::render('shop/shippingmethods/index', [
            'methods' => $methods,
        ]);
    }

    public function store(StoreShippingMethodRequest $request)
    {
        $this->authorize('create', ShippingMethod::class);

        ShippingMethod::create($request->validated());

        return redirect()->back()->with('status', 'Shipping method created.');
    }

    public function update(StoreShippingMethodRequest $request, ShippingMethod $shippingMethod)
    {
        $this->authorize('update', $shippingMethod);

        $shippingMethod->update($request->validated());

        return redirect()->back()->with('status', 'Shipping method updated.');
    }

    public function destroy(ShippingMethod $shippingMethod)
    {
        $this->authorize('delete', $shippingMethod);

        $shippingMethod->delete();

        return redirect()->back()->with('status', 'Shipping method deleted.');
    }

    public function toggleActive(ShippingMethod $shippingMethod)
    {
        $this->authorize('update', $shippingMethod);

        $shippingMethod->update(['is_active' => ! $shippingMethod->is_active]);

        return redirect()->back()->with('status', $shippingMethod->is_active ? 'Method activated.' : 'Method deactivated.');
    }
}
