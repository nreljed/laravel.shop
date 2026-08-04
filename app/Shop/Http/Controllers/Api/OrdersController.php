<?php

namespace App\Shop\Http\Controllers\Api;

use App\Shop\Http\Resources\OrderResource;
use App\Shop\Models\Order;
use Illuminate\Http\Request;
use Nreljed\DashboardCore\Http\Controllers\Controller;

class OrdersController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->user()) {
            $query->where('user_id', $request->user()->id);
        }

        $perPage = min((int) ($request->per_page ?? 20), 100);
        $orders = $query->with('items')->orderBy('created_at', 'desc')->paginate($perPage);

        return OrderResource::collection($orders);
    }

    public function show(Order $order)
    {
        $order->load('items');

        return new OrderResource($order);
    }
}
