<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'address', 'items.product'])
                      ->withCount('items');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($orders);
    }

    public function show(int $id)
    {
        $order = Order::with(['user', 'address', 'items.product.primaryImage'])
            ->withCount('items')
            ->findOrFail($id);

        return response()->json($order);
    }

    public function updateStatus(Request $request, int $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,arrived,canceled,refunded',
        ]);

        $order->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Rendelés státusza frissítve.',
            'order'   => $order
        ]);
    }

    public function updatePayment(Request $request, int $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'payment_status' => 'required|in:pending,processing,paid,failed,refunded',
        ]);

        $order->update(['payment_status' => $validated['payment_status']]);

        return response()->json([
            'message' => 'Fizetési státusz frissítve.',
            'order'   => $order
        ]);
    }

    public function destroy(int $id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json(['message' => 'Rendelés törölve.']);
    }
}
