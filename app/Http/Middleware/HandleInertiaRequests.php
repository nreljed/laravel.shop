<?php

namespace App\Http\Middleware;

use App\Shop\Models\Cart;
use App\Shop\Models\Wishlist;
use Illuminate\Http\Request;
use Nreljed\DashboardCore\Http\Middleware\HandleInertiaRequests as CoreHandleInertiaRequests;

class HandleInertiaRequests extends CoreHandleInertiaRequests
{
    #[\Override]
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'cart' => function () use ($request) {
                if (! $request->hasSession()) {
                    return ['items' => [], 'subtotal' => 0, 'total' => 0];
                }

                $cart = $request->user()
                    ? Cart::where('user_id', $request->user()->id)->first()
                    : Cart::where('session_id', $request->session()->getId())->first();

                if (! $cart) {
                    return ['items' => [], 'subtotal' => 0, 'total' => 0];
                }

                $cart->load('items.product', 'items.variation');

                return [
                    'id' => $cart->id,
                    'items' => $cart->items->map(fn ($item) => [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'variant_label' => $item->variant_label,
                        'product' => $item->product ? [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'slug' => $item->product->slug,
                            'thumbnail_url' => $item->product->thumbnail_url,
                        ] : null,
                        'variation' => $item->variation ? [
                            'id' => $item->variation->id,
                            'image_url' => $item->variation->image_url,
                        ] : null,
                    ])->values()->all(),
                    'subtotal' => (float) $cart->subtotal,
                    'total' => (float) $cart->total,
                    'coupon_code' => $cart->coupon_code,
                    'discount' => (float) $cart->discount,
                ];
            },
            'wishlistIds' => fn () => $request->user()
                ? Wishlist::where('user_id', $request->user()->id)->pluck('product_id')
                : collect(),
        ];
    }
}
