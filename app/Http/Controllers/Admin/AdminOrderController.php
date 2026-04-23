<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\AuditLogger;
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

        $oldStatus = $order->status;
        $order->update(['status' => $validated['status']]);

        AuditLogger::log('status_changed', 'Order', $order->id,
            "Rendelés #{$order->id} státusza: {$oldStatus} → {$validated['status']}",
            ['old' => $oldStatus, 'new' => $validated['status']]);

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

        $oldPayment = $order->payment_status;
        $order->update(['payment_status' => $validated['payment_status']]);

        AuditLogger::log('payment_changed', 'Order', $order->id,
            "Rendelés #{$order->id} fizetés: {$oldPayment} → {$validated['payment_status']}",
            ['old' => $oldPayment, 'new' => $validated['payment_status']]);

        return response()->json([
            'message' => 'Fizetési státusz frissítve.',
            'order'   => $order
        ]);
    }

    public function destroy(int $id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        AuditLogger::log('deleted', 'Order', $id, "Rendelés #{$id} törölve");

        return response()->json(['message' => 'Rendelés törölve.']);
    }
}
