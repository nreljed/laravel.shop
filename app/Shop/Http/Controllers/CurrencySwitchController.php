<?php

namespace App\Shop\Http\Controllers;

use App\Shop\Services\CurrencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Nreljed\DashboardCore\Http\Controllers\Controller;

class CurrencySwitchController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency' => 'required|string|size:3',
        ]);

        $currency = app(CurrencyService::class)->getByCode($validated['currency']);

        if ($currency && $currency->is_active) {
            session(['selected_currency' => $currency->code]);
        }

        return redirect()->back();
    }
}
